<?php
/**
 * ============================================================================
 * NIELIT TPMS - ADMIN AUDIT HUB & TP PROFILE VIEW
 * ============================================================================
 * File: tp_profile.php
 * Description: A 360-degree, read-only audit dashboard for Administrators to 
 * verify a Training Partner's complete profile, infrastructure, legal data, 
 * faculty roster, and document vault. Now featuring a fluid responsive layout.
 * ============================================================================
 */

// 1. SECURITY & SESSION
session_name('NIELIT_TPMS');
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/config.php';

// Global Notification Variables
$message = '';
$msg_type = '';
$timestamp_now = date('Y-m-d H:i:s');

// 2. VALIDATE & FETCH CENTER ID
$center_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($center_id === 0) {
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'><h2>Critical Error</h2><p>Invalid or missing Center ID parameter.</p><a href='admin_manage_tp.php'>Return to Center Directory</a></div>");
}

// ----------------------------------------------------------------------------
// 3. ADMIN ACTION HANDLERS (POST)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
    
    // A. Update TP Status
    if ($_POST['admin_action'] === 'update_status') {
        $new_status = $conn->real_escape_string($_POST['new_status']);
        $admin_remarks = $conn->real_escape_string($_POST['admin_remarks']);
        
        $update_stmt = $conn->prepare("UPDATE centers SET status = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("si", $new_status, $center_id);
            if ($update_stmt->execute()) {
                $message = "Training Partner status successfully updated to: " . strtoupper($new_status);
                $msg_type = "success";
                
                // Keep the users table in sync
                $user_status = ($new_status === 'Approved') ? 'active' : (($new_status === 'Rejected') ? 'rejected' : 'pending');
                $sync_stmt = $conn->prepare("UPDATE users SET status = ? WHERE center_id = (SELECT contact_email FROM centers WHERE id = ? LIMIT 1)");
                if($sync_stmt) {
                    $sync_stmt->bind_param("si", $user_status, $center_id);
                    $sync_stmt->execute();
                    $sync_stmt->close();
                }
            } else {
                $message = "Database Error: Could not update status.";
                $msg_type = "danger";
            }
            $update_stmt->close();
        }
    }
}

// ----------------------------------------------------------------------------
// 4. FETCH COMPREHENSIVE TP DATA
// ----------------------------------------------------------------------------
$center = null;
$stmt = $conn->prepare("SELECT * FROM centers WHERE id = ?");
$stmt->bind_param("i", $center_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $center = $result->fetch_assoc();
} else {
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'><h2>404 Not Found</h2><p>The requested Center profile does not exist in the database.</p><a href='admin_manage_tp.php'>Return to Center Directory</a></div>");
}
$stmt->close();

$tp_email = $center['contact_email'];
$tp_status = strtolower($center['status'] ?? 'pending');

// 5. FETCH USER ACCOUNT DETAILS
$tp_user_id = 0;
$tp_account_created = 'N/A';
$stmt_user = $conn->prepare("SELECT id, created_at FROM users WHERE email = ?");
$stmt_user->bind_param("s", $tp_email);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
if ($row = $res_user->fetch_assoc()) {
    $tp_user_id = $row['id'];
    $tp_account_created = date('d M Y, h:i A', strtotime($row['created_at']));
}
$stmt_user->close();

// ----------------------------------------------------------------------------
// 6. FETCH ACADEMIC & ENROLLMENT STATS (SYNCED WITH NEW WORKFLOW)
// ----------------------------------------------------------------------------

// A. Global Student Counts for this TP
$stats = ['total' => 0, 'active' => 0, 'completed' => 0, 'dropped' => 0];
$stmt_std = $conn->prepare("SELECT status, COUNT(*) as count FROM students WHERE tp_email = ? GROUP BY status");
$stmt_std->bind_param("s", $tp_email);
$stmt_std->execute();
$res_std = $stmt_std->get_result();
while($row = $res_std->fetch_assoc()) {
    $status_key = strtolower($row['status']);
    $stats[$status_key] = $row['count'];
    $stats['total'] += $row['count'];
}
$stmt_std->close();

// B. Fetch Master Accreditations (Approved Courses where capacity = 0)
$accreditations = [];
if ($tp_user_id > 0) {
    $stmt_acc = $conn->prepare("
        SELECT c.course_name, c.duration, b.created_at 
        FROM tp_batches b 
        JOIN courses c ON b.course_id = c.id 
        WHERE b.tp_id = ? AND b.batch_capacity = 0 AND b.status = 'active'
        ORDER BY b.created_at DESC
    ");
    $stmt_acc->bind_param("i", $tp_user_id);
    $stmt_acc->execute();
    $res_acc = $stmt_acc->get_result();
    while ($row = $res_acc->fetch_assoc()) {
        $accreditations[] = $row;
    }
    $stmt_acc->close();
}

// C. Fetch Active Batches & Auto-Calculated Students
$ongoing_batches = [];
$total_approved_capacity = 0;
if ($tp_user_id > 0) {
    $stmt_batch = $conn->prepare("
        SELECT 
            b.id as batch_id, b.batch_number, b.batch_capacity, b.batch_timing, c.course_name,
            (SELECT COUNT(*) FROM students s WHERE s.batch_id = b.id AND s.status != 'Dropped') as enrolled_count
        FROM tp_batches b 
        JOIN courses c ON b.course_id = c.id 
        WHERE b.tp_id = ? AND b.status = 'active_batch'
        ORDER BY b.created_at DESC
    ");
    $stmt_batch->bind_param("i", $tp_user_id);
    $stmt_batch->execute();
    $res_batch = $stmt_batch->get_result();
    while ($row = $res_batch->fetch_assoc()) {
        $ongoing_batches[] = $row;
        $total_approved_capacity += intval($row['batch_capacity']);
    }
    $stmt_batch->close();
}

// 7. PREPARE MEDIA & DOCUMENTS
$gallery_images = !empty($center['center_gallery']) ? json_decode($center['center_gallery'], true) : [];
$profile_img = !empty($center['profile_photo']) ? '../' . $center['profile_photo'] : 'https://ui-avatars.com/api/?name='.urlencode($center['institute_name']).'&background=0F172A&color=fff&size=200';
$cover_img = !empty($center['cover_photo']) ? '../' . $center['cover_photo'] : 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=1600&h=400';

// Document Vault Schema mapped to DB Columns
$document_vault = [
    'doc_s17_reg_cert' => 'S17: Institute Registration Certificate',
    'doc_s9_legal_doc' => 'S9: Legal Status Document',
    'doc_s9_moa_doc' => 'S9: Memorandum of Association (MoA)',
    'doc_s17_pan_card' => 'S17: Institute PAN Card',
    'doc_s3_id_proof' => 'S3: Signatory ID Proof',
    'doc_s3_signature' => 'S3: Signatory Digital Signature',
    'doc_s4_layout_map' => 'S4: Premises Layout Map',
    'doc_s4_building_photo' => 'S4: Building Facade Photo',
    'doc_s4_agreement' => 'S4: Premises Ownership/Lease Agreement',
    'doc_s12_faculty1_cert' => 'S12: Faculty 1 Qualification',
    'doc_s12_faculty2_cert' => 'S12: Faculty 2 Qualification',
    'doc_s17_franchise_agmt' => 'S17: Franchise Agreement',
    'doc_s17_tax_reg' => 'S17: Tax Registration Certificate'
];

// Status Badge Colors
$status_colors = [
    'approved' => ['bg' => '#D1FAE5', 'text' => '#059669', 'icon' => 'fa-check-circle'],
    'pending' => ['bg' => '#FEF3C7', 'text' => '#D97706', 'icon' => 'fa-hourglass-half'],
    'rejected' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'icon' => 'fa-times-circle'],
    'suspended' => ['bg' => '#F3E8FF', 'text' => '#7E22CE', 'icon' => 'fa-ban']
];
$current_status_style = $status_colors[$tp_status] ?? $status_colors['pending'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit: <?= htmlspecialchars($center['institute_name']) ?> - NIELIT Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ====================================================================
           GLOBAL VARIABLES & RESET
           ==================================================================== */
        :root {
            --sidebar-bg: #0B1121; 
            --sidebar-hover: #1E293B;
            --sidebar-border: rgba(255,255,255,0.05);
            --bg-body: #F4F7F9;
            --card-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --primary: #2563EB; 
            --primary-hover: #1D4ED8;
            --secondary: #475569;
            --accent-success: #10B981;
            --accent-warning: #F59E0B;
            --accent-danger: #EF4444;
            --border-color: #E2E8F0;
            --sidebar-width: 260px;
            --transition-speed: 0.3s;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-dark); overflow-x: hidden; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }

        /* ====================================================================
           ADMIN SIDEBAR & TOPBAR
           ==================================================================== */
        #sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); position: fixed; top: 0; left: 0; height: 100vh; z-index: 1050; display: flex; flex-direction: column; transition: transform var(--transition-speed); }
        .sidebar-brand { padding: 25px 20px; border-bottom: 1px solid var(--sidebar-border); display: flex; justify-content: space-between; align-items: center; }
        .sidebar-brand h4 { font-weight: 800; font-size: 20px; margin: 0; color: white; letter-spacing: 0.5px;}
        .sidebar-brand span { font-size: 11px; color: #94A3B8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;}
        
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .sidebar-menu a { padding: 15px 25px; display: flex; align-items: center; color: #D1D5DB; text-decoration: none; font-size: 14px; font-weight: 600; transition: var(--transition-speed); border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--sidebar-hover); color: white; border-left-color: var(--primary); }
        .sidebar-menu a i { width: 30px; font-size: 16px; color: #9CA3AF; transition: var(--transition-speed);}
        .sidebar-menu a.active i { color: var(--primary); }
        
        .sidebar-footer { padding: 20px; border-top: 1px solid var(--sidebar-border); }
        .btn-logout { width: 100%; padding: 12px; background: rgba(239, 68, 68, 0.1); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: var(--transition-speed);}
        .btn-logout:hover { background: var(--accent-danger); color: white; }

        #main-content { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; transition: margin var(--transition-speed); }
        .top-navbar { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 999; box-shadow: var(--shadow-sm); }
        
        .breadcrumb { margin: 0; font-size: 13px; font-weight: 600; color: var(--text-muted); }
        .breadcrumb a { color: var(--primary); text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        .nav-profile { display: flex; align-items: center; gap: 15px; margin-left: auto;}
        .nav-profile span { font-size: 13px; font-weight: 700; color: var(--text-dark); text-align: right;}
        .nav-profile span small { font-weight: 500; color: var(--text-muted); display: block;}
        .avatar-circle-admin { width: 35px; height: 35px; background: #0F766E; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 14px; }

        .page-container { padding: 40px; max-width: 1600px; margin: 0 auto; width: 100%; flex-grow: 1;}

        /* ====================================================================
           FLUID PROFILE HERO BANNER
           ==================================================================== */
        .profile-cover { width: 100%; height: 260px; background-image: url('<?= $cover_img ?>'); background-size: cover; background-position: center; border-radius: 16px; position: relative; box-shadow: var(--shadow-md); margin-bottom: 0; }
        .profile-cover-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.1) 100%); border-radius: 16px;}
        
        /* Flexbox instead of absolute positioning to prevent overlap */
        .profile-header-container { display: flex; align-items: flex-end; gap: 25px; padding: 0 40px; margin-top: -70px; position: relative; z-index: 5; margin-bottom: 40px; }
        .profile-avatar { width: 140px; height: 140px; border-radius: 16px; border: 6px solid var(--bg-body); background: white; object-fit: cover; box-shadow: var(--shadow-lg); flex-shrink: 0; background-color: var(--card-bg);}
        
        .profile-titles { margin-bottom: 10px; color: var(--text-dark); flex-grow: 1;}
        .profile-titles h1 { font-weight: 800; font-size: 30px; margin: 0 0 8px 0; display: flex; align-items: center; flex-wrap: wrap; gap: 15px;}
        .profile-titles p { font-size: 14px; font-weight: 600; margin: 0; color: var(--secondary); display: flex; align-items: center; flex-wrap: wrap; gap: 15px;}
        .profile-titles p i { color: #94A3B8;}
        
        .status-badge-hero { padding: 6px 16px; border-radius: 50px; font-size: 13px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);}

        /* ====================================================================
           STATISTICS GRID
           ==================================================================== */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-sm); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
        .icon-blue { background: #EFF6FF; color: var(--primary); }
        .icon-teal { background: #ECFDF5; color: var(--accent-success); }
        .icon-orange { background: #FFFBEB; color: var(--accent-warning); }
        .icon-purple { background: #F5F3FF; color: var(--accent-purple); }
        .stat-data h3 { font-size: 28px; font-weight: 800; margin: 0 0 5px 0; color: var(--text-dark); line-height: 1;}
        .stat-data p { margin: 0; font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;}

        /* ====================================================================
           FLUID AUDIT LAYOUT (TABS + CONTENT)
           ==================================================================== */
        .audit-grid { display: grid; grid-template-columns: 280px 1fr; gap: 30px; align-items: start; }
        
        /* Navigation Sidebar for Profile */
        .audit-nav { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; position: sticky; top: 90px; box-shadow: var(--shadow-sm);}
        .audit-nav .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 18px; border-radius: 8px; margin-bottom: 5px; transition: var(--transition-speed); font-size: 14px; display: flex; align-items: center; gap: 10px; border: 1px solid transparent; text-align: left; width: 100%;}
        .audit-nav .nav-link.active { background-color: var(--primary-light); color: var(--primary-hover); border-color: #BFDBFE; font-weight: 700; }
        .audit-nav .nav-link:hover:not(.active) { background-color: #F8FAFC; color: var(--text-dark); }
        .nav-section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94A3B8; margin: 15px 0 10px 15px; letter-spacing: 1px;}

        /* Content Cards */
        .content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 35px; margin-bottom: 25px; box-shadow: var(--shadow-sm); animation: fadeIn 0.4s ease-out;}
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card-header-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #F1F5F9; padding-bottom: 15px;}
        .card-header-title h5 { font-weight: 800; font-size: 18px; margin: 0; color: var(--text-dark); display: flex; align-items: center; gap: 10px;}
        .card-header-title i { color: var(--primary); font-size: 20px;}

        /* Data Grids */
        .data-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .data-item { background: #F8FAFC; padding: 18px; border-radius: 12px; border: 1px solid #F1F5F9;}
        .data-label { font-size: 11px; color: var(--text-muted); font-weight: 800; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;}
        .data-value { font-size: 15px; color: var(--text-dark); font-weight: 600; word-break: break-word;}

        /* ====================================================================
           SPECIFIC TAB WIDGETS
           ==================================================================== */
        
        /* Batch & Accreditation Cards */
        .course-badge { display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; border: 1px solid var(--border-color); padding: 15px 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: var(--shadow-sm);}
        .course-badge-info { display: flex; align-items: center; gap: 15px;}
        .course-badge-info i { font-size: 24px; color: var(--primary); background: var(--primary-light); padding: 12px; border-radius: 10px;}
        .course-title { font-size: 15px; font-weight: 800; color: var(--text-dark); margin-bottom: 2px;}
        .course-meta { font-size: 12px; color: var(--text-muted); font-weight: 600;}
        
        /* Capacity Bar */
        .progress-bar-bg { height: 8px; width: 150px; background: #E2E8F0; border-radius: 10px; overflow: hidden;}
        .progress-bar-fill { height: 100%; border-radius: 10px;}
        
        /* Document Vault */
        .vault-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;}
        .vault-item { display: flex; align-items: center; justify-content: space-between; background: white; border: 1px solid var(--border-color); padding: 15px; border-radius: 12px; transition: var(--transition-speed);}
        .vault-item:hover { border-color: var(--primary); box-shadow: var(--shadow-md); transform: translateY(-3px);}
        .vault-icon { width: 40px; height: 40px; background: #FEF2F2; color: #EF4444; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;}
        .vault-info { flex-grow: 1; padding: 0 15px;}
        .vault-info strong { display: block; font-size: 13px; color: var(--text-dark); margin-bottom: 4px; line-height: 1.3;}
        .vault-info span { font-size: 11px; color: var(--accent-success); font-weight: 800; text-transform: uppercase; background: #ECFDF5; padding: 3px 8px; border-radius: 4px;}
        .btn-download { background: var(--primary-light); color: var(--primary); width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: var(--transition-speed);}
        .btn-download:hover { background: var(--primary); color: white;}

        /* Image Gallery */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .gallery-item { width: 100%; height: 150px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); cursor: pointer; transition: 0.3s;}
        .gallery-item:hover { transform: scale(1.03); box-shadow: var(--shadow-md);}

        /* Admin Action Form */
        .admin-action-box { background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 12px; padding: 25px;}
        .form-label { font-weight: 800; font-size: 13px; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;}
        .form-select, .form-control { border-radius: 8px; padding: 12px 15px; font-weight: 600; font-size: 15px; border-color: var(--border-color);}
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37,99,235,0.1); outline: none;}

        /* ====================================================================
           RESPONSIVE DESIGN RULES
           ==================================================================== */
        @media (max-width: 1200px) {
            /* Transform Audit Grid from Side-by-Side to Top-Down */
            .audit-grid { grid-template-columns: 1fr; gap: 20px; }
            .audit-nav { position: static; display: flex; overflow-x: auto; white-space: nowrap; padding: 10px; margin-bottom: 10px;}
            .audit-nav .nav-link { display: inline-flex; width: auto; margin-bottom: 0; margin-right: 10px; justify-content: center;}
            .nav-section-title { display: none; }
        }

        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); }
            #main-content { margin-left: 0; }
            .mobile-toggle-btn { display: block; }
            .profile-header-container { flex-direction: column; align-items: flex-start; text-align: left; padding: 0 20px; margin-top: -50px; }
            .profile-avatar { width: 100px; height: 100px; border-width: 4px; }
            .page-container { padding: 20px; }
            .course-badge { flex-direction: column; align-items: flex-start; gap: 15px; }
            .course-badge .text-end { text-align: left !important; width: 100% !important; }
        }
    </style>
</head>
<body>

    <aside id="sidebar">
        <div class="sidebar-brand">
            <div>
                <h4>NIELIT TPS</h4>
                <span>Admin Console</span>
            </div>
        </div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-border-all"></i> Dashboard Overview</a>
            <a href="admin_manage_tp.php" class="active"><i class="fas fa-building"></i> Manage TP Centers</a>
            <a href="admin_student_reports.php"><i class="fas fa-users"></i> Student Records</a>
            <a href="admin_courses.php"><i class="fas fa-book"></i> Master Catalog</a>
            <a href="admin_upload_notice.php"><i class="fas fa-bullhorn"></i> Public Notices</a>
            <a href="admin_placements.php"><i class="fas fa-briefcase"></i> Placements</a>
            <a href="admin_manage_admins.php" style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;"><i class="fas fa-user-shield"></i> Manage Admins</a>
            <a href="admin_helpdesk_upload.php"><i class="fas fa-headset"></i> Support Tickets</a>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Secure Logout</a>
        </div>
    </aside>

    <main id="main-content">
        
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="mobile-toggle-btn" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <div class="breadcrumb d-none d-md-block">
                    <a href="admin_manage_tp.php"><i class="fas fa-arrow-left me-2"></i> Centers Directory</a> / Audit Profile
                </div>
            </div>
            
            <div class="nav-profile">
                <span>Super Administrator <small>NIELIT Hub</small></span>
                <div class="avatar-circle-admin">SA</div>
            </div>
        </header>

        <div class="page-container">
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; font-weight: 600; border-left: 5px solid <?= $msg_type=='success'?'var(--accent-success)':'var(--accent-danger)' ?> !important;">
                    <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-danger' ?> me-2 fs-5"></i> 
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="profile-cover">
                <div class="profile-cover-overlay"></div>
            </div>
            
            <div class="profile-header-container">
                <img src="<?= $profile_img ?>" alt="TP Logo" class="profile-avatar">
                <div class="profile-titles">
                    <h1>
                        <?= htmlspecialchars($center['institute_name']) ?>
                        <span class="status-badge-hero" style="background: <?= $current_status_style['bg'] ?>; color: <?= $current_status_style['text'] ?>;">
                            <i class="fas <?= $current_status_style['icon'] ?>"></i> <?= ucfirst($tp_status) ?>
                        </span>
                    </h1>
                    <p>
                        <span><i class="fas fa-id-badge"></i> Center ID: <?= htmlspecialchars($center['id']) ?></span>
                        <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($tp_email) ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($center['district']) ?>, <?= htmlspecialchars($center['state']) ?></span>
                    </p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['total']) ?></h3>
                        <p>Total Enrolled Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-teal"><i class="fas fa-certificate"></i></div>
                    <div class="stat-data">
                        <h3><?= count($accreditations) ?></h3>
                        <p>Approved Accreditations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-orange"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-data">
                        <h3><?= count($ongoing_batches) ?></h3>
                        <p>Active Training Batches</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="fas fa-briefcase"></i></div>
                    <div class="stat-data">
                        <?php
                            $trained = intval($center['s14_students_trained'] ?? 0);
                            $placed = intval($center['s14_students_placed'] ?? 0);
                            $rate = ($trained > 0) ? round(($placed/$trained)*100) : 0;
                        ?>
                        <h3><?= $rate ?>%</h3>
                        <p>Historical Placement Rate</p>
                    </div>
                </div>
            </div>

            <div class="audit-grid">
                
                <div class="audit-nav">
                    <div class="nav-section-title">Audit Sections</div>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button"><i class="fas fa-chart-pie"></i> Academic Overview</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-core" type="button"><i class="fas fa-building"></i> Core Identity (S1)</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-signatory" type="button"><i class="fas fa-user-tie"></i> Signatory (S3)</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-infra" type="button"><i class="fas fa-laptop-house"></i> Infrastructure (S4)</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-legal" type="button"><i class="fas fa-balance-scale"></i> Legal Status (S9)</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-faculty" type="button"><i class="fas fa-chalkboard-teacher"></i> Faculty List (S12)</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-financial" type="button"><i class="fas fa-rupee-sign"></i> Financials (S14)</button>
                    </div>
                    
                    <div class="nav-section-title mt-4">Verification & Action</div>
                    <div class="nav flex-column nav-pills" role="tablist">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-vault" type="button" style="color: var(--primary);"><i class="fas fa-folder-open"></i> Document Vault</button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-admin" type="button" style="color: var(--accent-danger);"><i class="fas fa-gavel"></i> Admin Action Hub</button>
                    </div>
                </div>

                <div class="tab-content w-100" id="v-pills-tabContent">
                    
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        
                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-chart-line"></i> Global Student Enrollment Distribution</h5>
                            </div>
                            <div style="height: 300px; width: 100%;">
                                <canvas id="enrollmentChart"></canvas>
                            </div>
                        </div>

                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-certificate"></i> Approved Course Accreditations</h5>
                            </div>
                            <p class="text-muted mb-4" style="font-size: 13px;">Courses this center is officially authorized to teach by NIELIT Administration.</p>
                            
                            <?php if(empty($accreditations)): ?>
                                <div class="text-center text-muted py-4 bg-light rounded border border-dashed">No master accreditations granted to this center.</div>
                            <?php else: ?>
                                <div>
                                    <?php foreach($accreditations as $c): ?>
                                        <div class="course-badge">
                                            <div class="course-badge-info">
                                                <i class="fas fa-award"></i> 
                                                <div>
                                                    <div class="course-title"><?= htmlspecialchars($c['course_name']) ?></div>
                                                    <div class="course-meta"><i class="far fa-clock"></i> Duration: <?= htmlspecialchars($c['duration']) ?></div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Accredited On</div>
                                                <div style="font-size: 13px; font-weight: 800; color: var(--text-dark);"><?= date('d M Y', strtotime($c['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-users"></i> Ongoing Training Batches</h5>
                            </div>
                            <p class="text-muted mb-4" style="font-size: 13px;">Batches currently being conducted by this center, along with auto-calculated student enrollment numbers.</p>
                            
                            <?php if(empty($ongoing_batches)): ?>
                                <div class="text-center text-muted py-4 bg-light rounded border border-dashed">This center has not created any active training batches yet.</div>
                            <?php else: ?>
                                <div>
                                    <?php foreach($ongoing_batches as $b): 
                                        $cap = intval($b['batch_capacity']);
                                        $enr = intval($b['enrolled_count']);
                                        $pct = ($cap > 0) ? round(($enr / $cap) * 100) : 0;
                                        $p_color = ($pct >= 100) ? 'var(--accent-danger)' : 'var(--accent-success)';
                                    ?>
                                        <div class="course-badge">
                                            <div class="course-badge-info">
                                                <i class="fas fa-chalkboard-teacher" style="background: #F1F5F9; color: var(--secondary);"></i> 
                                                <div>
                                                    <div class="course-title"><?= htmlspecialchars($b['course_name']) ?></div>
                                                    <div class="course-meta">
                                                        <span class="me-3"><i class="fas fa-fingerprint"></i> Batch ID: <?= htmlspecialchars($b['batch_number']) ?></span>
                                                        <span><i class="far fa-clock"></i> Timing: <?= htmlspecialchars($b['batch_timing']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-end" style="width: 150px;">
                                                <div class="d-flex justify-content-between mb-1" style="font-size: 11px; font-weight: 800; color: var(--text-dark);">
                                                    <span><?= $enr ?> Students</span>
                                                    <span><?= $cap ?> Max</span>
                                                </div>
                                                <div class="progress-bar-bg">
                                                    <div class="progress-bar-fill" style="width: <?= min(100, $pct) ?>%; background: <?= $p_color ?>;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-images"></i> Center Infrastructure Gallery</h5>
                            </div>
                            <?php if(empty($gallery_images)): ?>
                                <div class="text-center text-muted py-4 bg-light rounded border border-dashed"><i class="fas fa-camera fs-3 mb-2 d-block"></i> No infrastructure photos uploaded.</div>
                            <?php else: ?>
                                <div class="gallery-grid">
                                    <?php foreach($gallery_images as $img): ?>
                                        <img src="../<?= htmlspecialchars($img) ?>" class="gallery-item" alt="Center Photo" onclick="window.open(this.src, '_blank')">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-core" role="tabpanel">
                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-building"></i> Section 1: Core Identity & Contact</h5>
                            </div>
                            <div class="data-grid mb-4">
                                <div class="data-item"><div class="data-label">Institute Name</div><div class="data-value"><?= htmlspecialchars($center['institute_name'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Official Email</div><div class="data-value"><a href="mailto:<?= htmlspecialchars($center['contact_email']) ?>"><?= htmlspecialchars($center['contact_email'] ?? 'N/A') ?></a></div></div>
                                <div class="data-item"><div class="data-label">Mobile Number</div><div class="data-value"><?= htmlspecialchars($center['mobile'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Landline</div><div class="data-value"><?= htmlspecialchars($center['landline'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">PAN Number</div><div class="data-value"><?= htmlspecialchars($center['pan_number'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Official Website</div><div class="data-value"><a href="<?= htmlspecialchars($center['website']) ?>" target="_blank"><?= htmlspecialchars($center['website'] ?? 'N/A') ?></a></div></div>
                                <div class="data-item"><div class="data-label">Category / Target Gender</div><div class="data-value"><?= htmlspecialchars($center['category'] ?? 'N/A') ?> / <?= htmlspecialchars($center['gender'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Year Established</div><div class="data-value"><?= htmlspecialchars($center['est_year'] ?? 'N/A') ?></div></div>
                            </div>
                            
                            <h6 class="fw-bold text-secondary text-uppercase mb-3" style="font-size: 12px; letter-spacing: 1px;">Registered Location</h6>
                            <div class="data-grid">
                                <div class="data-item" style="grid-column: 1 / -1;"><div class="data-label">Full Address</div><div class="data-value"><?= htmlspecialchars($center['institute_address'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">District</div><div class="data-value"><?= htmlspecialchars($center['district'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">State</div><div class="data-value"><?= htmlspecialchars($center['state'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">PIN Code</div><div class="data-value"><?= htmlspecialchars($center['pincode'] ?? 'N/A') ?></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-signatory" role="tabpanel">
                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-user-tie"></i> Section 3: Authorized Signatory</h5>
                            </div>
                            <div class="data-grid">
                                <div class="data-item"><div class="data-label">Full Name</div><div class="data-value"><?= htmlspecialchars($center['s3_name'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Father's Name</div><div class="data-value"><?= htmlspecialchars($center['s3_father_name'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Designation</div><div class="data-value"><?= htmlspecialchars($center['s3_designation'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Highest Qualification</div><div class="data-value"><?= htmlspecialchars($center['s3_qualification'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Experience</div><div class="data-value"><?= htmlspecialchars($center['s3_experience'] ?? '0') ?> Years</div></div>
                                <div class="data-item"><div class="data-label">ID Proof Type</div><div class="data-value"><?= htmlspecialchars($center['s3_id_type'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">ID Proof Number</div><div class="data-value"><?= htmlspecialchars($center['s3_id_number'] ?? 'N/A') ?></div></div>
                                <div class="data-item" style="grid-column: 1 / -1;"><div class="data-label">Residential Address</div><div class="data-value"><?= htmlspecialchars($center['s3_address'] ?? 'N/A') ?></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-infra" role="tabpanel">
                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-laptop-house"></i> Section 4: Infrastructure Capability</h5>
                            </div>
                            <div class="data-grid">
                                <div class="data-item"><div class="data-label">Premises Ownership</div><div class="data-value"><?= htmlspecialchars($center['s4_premises_type'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Total Carpet Area</div><div class="data-value"><?= htmlspecialchars($center['s4_carpet_area'] ?? '0') ?> Sq.Ft.</div></div>
                                <div class="data-item"><div class="data-label">Number of Computers</div><div class="data-value"><?= htmlspecialchars($center['s4_computers'] ?? '0') ?> Terminals</div></div>
                                <div class="data-item"><div class="data-label">Classroom Seating Capacity</div><div class="data-value"><?= htmlspecialchars($center['s4_seating'] ?? '0') ?> Seats</div></div>
                                <div class="data-item"><div class="data-label">Internet Infrastructure</div><div class="data-value"><?= htmlspecialchars($center['s4_internet'] ?? 'N/A') ?></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-legal" role="tabpanel">
                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-balance-scale"></i> Section 9: Legal Entity Status</h5>
                            </div>
                            <div class="data-grid">
                                <div class="data-item"><div class="data-label">Legal Status Type Code</div><div class="data-value"><?= htmlspecialchars($center['s9_legal_status'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Proprietor / Organization Name</div><div class="data-value"><?= htmlspecialchars($center['s9_prop_name'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Partnership Date</div><div class="data-value"><?= htmlspecialchars($center['s9_partnership_date'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Partnership Registration</div><div class="data-value"><?= htmlspecialchars($center['s9_partnership_reg'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Society Registration No.</div><div class="data-value"><?= htmlspecialchars($center['s9_society_reg'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Society Registration Date</div><div class="data-value"><?= htmlspecialchars($center['s9_society_date'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Company CIN</div><div class="data-value"><?= htmlspecialchars($center['s9_cin'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Incorporation Date</div><div class="data-value"><?= htmlspecialchars($center['s9_incorp_date'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Govt. Department Name</div><div class="data-value"><?= htmlspecialchars($center['s9_dept_name'] ?? 'N/A') ?></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-faculty" role="tabpanel">
                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-chalkboard-teacher"></i> Section 12 & 13: Faculty Roster</h5>
                            </div>
                            
                            <h6 class="fw-bold text-secondary text-uppercase mb-3" style="font-size: 13px; letter-spacing: 1px;">Primary Faculty (F1)</h6>
                            <div class="data-grid mb-4">
                                <div class="data-item"><div class="data-label">Full Name</div><div class="data-value"><?= htmlspecialchars($center['s12_f1_name'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Qualification</div><div class="data-value"><?= htmlspecialchars($center['s12_f1_qual'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Board / University</div><div class="data-value"><?= htmlspecialchars($center['s12_f1_board'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Passing Year</div><div class="data-value"><?= htmlspecialchars($center['s12_f1_year'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Designation</div><div class="data-value"><?= htmlspecialchars($center['s13_f1_desig'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Organization</div><div class="data-value"><?= htmlspecialchars($center['s13_f1_org'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Tenure (From - To)</div><div class="data-value"><?= htmlspecialchars($center['s13_f1_from'] ?? '') ?> to <?= htmlspecialchars($center['s13_f1_to'] ?? '') ?></div></div>
                            </div>

                            <h6 class="fw-bold text-secondary text-uppercase mb-3 pt-3 border-top" style="font-size: 13px; letter-spacing: 1px;">Secondary Faculty (F2)</h6>
                            <div class="data-grid">
                                <div class="data-item"><div class="data-label">Full Name</div><div class="data-value"><?= htmlspecialchars($center['s12_f2_name'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Qualification</div><div class="data-value"><?= htmlspecialchars($center['s12_f2_qual'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Board / University</div><div class="data-value"><?= htmlspecialchars($center['s12_f2_board'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Passing Year</div><div class="data-value"><?= htmlspecialchars($center['s12_f2_year'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Designation</div><div class="data-value"><?= htmlspecialchars($center['s13_f2_desig'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Organization</div><div class="data-value"><?= htmlspecialchars($center['s13_f2_org'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">Tenure (From - To)</div><div class="data-value"><?= htmlspecialchars($center['s13_f2_from'] ?? '') ?> to <?= htmlspecialchars($center['s13_f2_to'] ?? '') ?></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-financial" role="tabpanel">
                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-rupee-sign"></i> Section 14: Financials & Track Record</h5>
                            </div>
                            <div class="data-grid mb-4">
                                <div class="data-item"><div class="data-label">Financial Year (FY)</div><div class="data-value"><?= htmlspecialchars($center['s14_fy'] ?? 'N/A') ?></div></div>
                                <div class="data-item"><div class="data-label">IT Training Turnover</div><div class="data-value">₹ <?= number_format(floatval($center['s14_turnover_it']), 2) ?></div></div>
                                <div class="data-item"><div class="data-label">Other Turnover</div><div class="data-value">₹ <?= number_format(floatval($center['s14_turnover_other']), 2) ?></div></div>
                                <div class="data-item"><div class="data-label">Tax Exemption Status</div><div class="data-value"><?= htmlspecialchars($center['s14_tax_exempt'] ?? 'N/A') ?></div></div>
                            </div>
                            <h6 class="fw-bold text-secondary text-uppercase mb-3 pt-3 border-top" style="font-size: 13px; letter-spacing: 1px;">Past Performance Data</h6>
                            <div class="data-grid">
                                <div class="data-item"><div class="data-label">Students Trained (Historical)</div><div class="data-value"><?= htmlspecialchars($center['s14_students_trained'] ?? '0') ?> Students</div></div>
                                <div class="data-item"><div class="data-label">Students Placed (Historical)</div><div class="data-value"><?= htmlspecialchars($center['s14_students_placed'] ?? '0') ?> Students</div></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-vault" role="tabpanel">
                        <div class="content-card">
                            <div class="card-header-title">
                                <h5><i class="fas fa-folder-open text-primary"></i> Master Document Vault</h5>
                            </div>
                            <p class="text-muted mb-4" style="font-size: 14px;">These are the official registration PDFs uploaded by the Training Partner. Verify these documents before granting approval.</p>
                            
                            <div class="vault-grid">
                                <?php 
                                $docs_found = false;
                                foreach($document_vault as $db_col => $doc_title): 
                                    if(!empty($center[$db_col])):
                                        $docs_found = true;
                                ?>
                                    <div class="vault-item">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="vault-icon"><i class="fas fa-file-pdf"></i></div>
                                            <div class="vault-info">
                                                <strong><?= htmlspecialchars($doc_title) ?></strong>
                                                <span>System Verified</span>
                                            </div>
                                        </div>
                                        <a href="../<?= htmlspecialchars($center[$db_col]) ?>" target="_blank" class="btn-download" title="View/Download Document">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                <?php 
                                    endif; 
                                endforeach; 
                                
                                if(!$docs_found): ?>
                                    <div class="col-12 text-center text-muted p-5 bg-light rounded border border-dashed">
                                        <i class="fas fa-exclamation-circle fs-2 text-warning mb-3 d-block"></i>
                                        <strong>No Documents Found</strong><br>
                                        The Training Partner has not uploaded any official documents yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-admin" role="tabpanel">
                        <div class="content-card border-danger" style="border-width: 2px;">
                            <div class="card-header-title">
                                <h5 class="text-danger"><i class="fas fa-gavel"></i> Administrator Action Hub</h5>
                            </div>
                            <p class="text-muted mb-4" style="font-size: 14px;">Modify the operational status of this Training Partner. Changes here will immediately affect their ability to access the portal and request accreditations.</p>
                            
                            <form method="POST" action="" class="admin-action-box">
                                <input type="hidden" name="admin_action" value="update_status">
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Current Account Status</label>
                                        <select name="new_status" class="form-select bg-white shadow-sm" required>
                                            <option value="pending" <?= $tp_status == 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                                            <option value="approved" <?= $tp_status == 'approved' ? 'selected' : '' ?>>Fully Approved / Active</option>
                                            <option value="suspended" <?= $tp_status == 'suspended' ? 'selected' : '' ?>>Suspended (Action Required)</option>
                                            <option value="rejected" <?= $tp_status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Internal Admin Remarks (Optional)</label>
                                        <textarea name="admin_remarks" class="form-control bg-white shadow-sm" rows="3" placeholder="Enter notes regarding this status change. These are for internal NIELIT use only."></textarea>
                                    </div>
                                    <div class="col-12 text-end mt-4 pt-3 border-top">
                                        <button type="button" class="btn btn-outline-secondary fw-bold px-4 me-2">Cancel</button>
                                        <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm"><i class="fas fa-save me-2"></i> Update Center Status</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // 1. SIDEBAR MOBILE TOGGLE LOGIC
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                const isOpen = sidebar.style.transform === 'translateX(0px)';
                if (isOpen) {
                    sidebar.style.transform = 'translateX(-100%)';
                    overlay.style.display = 'none';
                } else {
                    sidebar.style.transform = 'translateX(0px)';
                    overlay.style.display = 'block';
                }
            }

            if(toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);

            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    sidebar.style.transform = ''; 
                    overlay.style.display = 'none';
                }
            });

            // 2. Chart.js: Student Status Distribution
            const ctx = document.getElementById('enrollmentChart');
            if(ctx) {
                const data = {
                    labels: ['Active Students', 'Completed Training', 'Dropped/Inactive'],
                    datasets: [{
                        data: [<?= $stats['active'] ?>, <?= $stats['completed'] ?>, <?= $stats['dropped'] ?>],
                        backgroundColor: ['#2563EB', '#10B981', '#EF4444'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                };

                new Chart(ctx, {
                    type: 'doughnut',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { position: 'right', labels: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 13, weight: 'bold' }, color: '#475569' } },
                            tooltip: { backgroundColor: '#0F172A', padding: 12, cornerRadius: 8 }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>