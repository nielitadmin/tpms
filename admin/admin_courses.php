<?php
/**
 * ============================================================================
 * NIELIT TPMS - COURSE MAPPING & ACCREDITATION HUB
 * ============================================================================
 * File: admin_courses.php
 * Description: The Master Academic Hub for Administrators. Allows admins to 
 * add/edit official NIELIT courses in the global catalog and approve or reject 
 * course accreditation requests submitted by Training Partners.
 * ============================================================================
 */

// 1. SECURITY & SESSION INITIALIZATION
session_name('NIELIT_TPMS');
session_start();

// Strict Role Checking: Admin Only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';

// Global Notification Variables
$message = '';
$msg_type = '';
$db_errors = [];
$timestamp_now = date('Y-m-d H:i:s');

// ============================================================================
// 2. HANDLE POST REQUESTS (ADD COURSE / APPROVE ACCREDITATION)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // A. Add New Course to Master Catalog
    if ($_POST['action'] === 'add_course') {
        $course_name = trim($_POST['course_name']);
        $duration = trim($_POST['duration']);
        $eligibility = trim($_POST['eligibility']);
        $carpet_area = trim($_POST['carpet_area'] ?? '');
        $system_req = trim($_POST['system_requirements'] ?? '');
        $faculty_req = trim($_POST['faculty_requirements'] ?? '');
        $status = 'active';

        if (!empty($course_name) && !empty($duration)) {
            $stmt = $conn->prepare("INSERT INTO courses (course_name, duration, eligibility, carpet_area, system_requirements, faculty_requirements, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssssss", $course_name, $duration, $eligibility, $carpet_area, $system_req, $faculty_req, $status);
                if ($stmt->execute()) {
                    $message = "Success! New master program added to the official NIELIT catalog.";
                    $msg_type = "success";
                } else {
                    $message = "Database error: " . $conn->error;
                    $msg_type = "danger";
                }
                $stmt->close();
            } else {
                $db_errors[] = "Prepare failed: " . $conn->error;
            }
        } else {
            $message = "Validation Error: Course Name and Duration are required fields.";
            $msg_type = "warning";
        }
    }

    // B. Approve Accreditation Request
    if ($_POST['action'] === 'approve_request') {
        $req_id = intval($_POST['request_id']);
        if ($req_id > 0) {
            $stmt = $conn->prepare("UPDATE tp_batches SET status = 'active' WHERE id = ? AND status = 'pending'");
            if ($stmt) {
                $stmt->bind_param("i", $req_id);
                if ($stmt->execute()) {
                    $message = "Accreditation Approved! The Training Partner can now create batches for this course.";
                    $msg_type = "success";
                } else {
                    $message = "Database error: Could not approve request.";
                    $msg_type = "danger";
                }
                $stmt->close();
            }
        }
    }

    // C. Reject Accreditation Request
    if ($_POST['action'] === 'reject_request') {
        $req_id = intval($_POST['request_id']);
        if ($req_id > 0) {
            $stmt = $conn->prepare("UPDATE tp_batches SET status = 'rejected' WHERE id = ? AND status = 'pending'");
            if ($stmt) {
                $stmt->bind_param("i", $req_id);
                if ($stmt->execute()) {
                    $message = "Accreditation request rejected.";
                    $msg_type = "warning";
                }
                $stmt->close();
            }
        }
    }
}

// ============================================================================
// 3. FETCH COMPREHENSIVE STATISTICS
// ============================================================================
$stats = [
    'total_courses' => 0,
    'active_courses' => 0,
    'inactive_courses' => 0,
    'pending_requests' => 0,
    'total_approved_accr' => 0
];

// Fetch Course Stats
$res_course_stats = $conn->query("SELECT status, COUNT(*) as count FROM courses GROUP BY status");
if ($res_course_stats) {
    while ($row = $res_course_stats->fetch_assoc()) {
        $stats['total_courses'] += $row['count'];
        if ($row['status'] === 'active') $stats['active_courses'] = $row['count'];
        if ($row['status'] === 'inactive') $stats['inactive_courses'] = $row['count'];
    }
}

// Fetch Accreditation Stats (From tp_batches)
$res_accr_stats = $conn->query("SELECT status, COUNT(*) as count FROM tp_batches GROUP BY status");
if ($res_accr_stats) {
    while ($row = $res_accr_stats->fetch_assoc()) {
        if ($row['status'] === 'pending') $stats['pending_requests'] = $row['count'];
        if ($row['status'] === 'active') $stats['total_approved_accr'] = $row['count'];
    }
}

// ============================================================================
// 4. FETCH DATA TABLES
// ============================================================================

// A. Fetch Master Catalog
$master_catalog = [];
$query_catalog = "SELECT * FROM courses ORDER BY id DESC"; 
$result_catalog = $conn->query($query_catalog);
if ($result_catalog) {
    while ($row = $result_catalog->fetch_assoc()) {
        $master_catalog[] = $row;
    }
} else { $db_errors[] = "Catalog Fetch Error: " . $conn->error; }

// B. Fetch Pending Accreditation Requests
$pending_requests = [];
$query_reqs = "
    SELECT b.id as req_id, b.batch_number, b.created_at, 
           c.course_name, c.duration,
           u.name as tp_name, u.email as tp_email,
           (SELECT institute_name FROM centers WHERE contact_email = u.email LIMIT 1) as institute_name
    FROM tp_batches b
    JOIN courses c ON b.course_id = c.id
    JOIN users u ON b.tp_id = u.id
    WHERE b.status = 'pending'
    ORDER BY b.created_at ASC
";
$result_reqs = $conn->query($query_reqs);
if ($result_reqs) {
    while ($row = $result_reqs->fetch_assoc()) {
        $pending_requests[] = $row;
    }
} else { $db_errors[] = "Requests Fetch Error: " . $conn->error; }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Catalog & Accreditations - NIELIT Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: #0B1121; 
            --sidebar-hover: #1E293B;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --bg-body: #F4F7F9;
            --card-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --text-light: #F8FAFC;
            --primary: #2563EB; 
            --primary-hover: #1D4ED8;
            --primary-light: #EFF6FF;
            --secondary: #475569;
            --accent-success: #10B981;
            --accent-warning: #F59E0B;
            --accent-danger: #EF4444;
            --accent-purple: #8B5CF6;
            --border-color: #E2E8F0;
            --sidebar-width: 280px;
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
            --transition-speed: 0.3s;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05);
            --shadow-glow: 0 0 20px rgba(37, 99, 235, 0.3);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-dark); overflow-x: hidden; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-body); }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

        /* ====================================================================
           SIDEBAR
           ==================================================================== */
        #sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); position: fixed; top: 0; left: 0; height: 100vh; z-index: 1050; display: flex; flex-direction: column; border-right: 1px solid var(--sidebar-border); transition: transform var(--transition-speed) ease; }
        .sidebar-brand { padding: 30px 25px; border-bottom: 1px solid var(--sidebar-border); text-align: center; display: flex; flex-direction: column; align-items: center; }
        .sidebar-brand-icon { width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary), var(--accent-purple)); border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px; box-shadow: var(--shadow-glow); }
        .sidebar-brand h4 { font-weight: 800; font-size: 20px; margin: 0; color: var(--text-light); letter-spacing: 0.5px; }
        .sidebar-brand span { font-size: 11px; color: #94A3B8; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 5px; }
        
        .sidebar-menu { padding: 25px 15px; flex-grow: 1; overflow-y: auto; }
        .sidebar-menu-category { font-size: 10px; color: #475569; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin: 15px 0 5px 15px; }
        .sidebar-menu a { padding: 12px 18px; margin-bottom: 5px; display: flex; align-items: center; color: #94A3B8; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: var(--border-radius-sm); transition: all var(--transition-speed) ease; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--sidebar-hover); color: white; transform: translateX(4px); }
        .sidebar-menu a i { width: 30px; font-size: 16px; transition: var(--transition-speed); }
        .sidebar-menu a.active i { color: #60A5FA; }
        
        .sidebar-footer { padding: 20px 15px; border-top: 1px solid var(--sidebar-border);}
        .btn-logout { width: 100%; padding: 12px; background: rgba(239, 68, 68, 0.05); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.1); border-radius: var(--border-radius-sm); font-weight: 600; font-size: 14px; transition: var(--transition-speed); display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .btn-logout:hover { background: var(--accent-danger); color: white; border-color: var(--accent-danger); box-shadow: 0 0 15px rgba(239, 68, 68, 0.3); }

        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1040; display: none; backdrop-filter: blur(3px); }

        /* ====================================================================
           MAIN CONTENT & TOPBAR
           ==================================================================== */
        #main-content { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; transition: margin var(--transition-speed) ease; }
        .top-navbar { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 999; box-shadow: var(--shadow-sm); }
        
        .top-nav-left { display: flex; align-items: center; gap: 20px; }
        .mobile-toggle-btn { display: none; background: none; border: none; font-size: 24px; color: var(--text-dark); cursor: pointer; }
        
        .search-bar { background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 50px; padding: 10px 20px; display: flex; align-items: center; width: 350px; max-width: 100%; transition: var(--transition-speed);}
        .search-bar:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); background: white;}
        .search-bar i { color: #94A3B8; margin-right: 12px; }
        .search-bar input { border: none; background: transparent; outline: none; width: 100%; font-size: 14px; color: var(--text-dark); font-weight: 500;}
        
        .nav-profile-area { display: flex; align-items: center; gap: 20px; margin-left: auto;}
        
        .nav-notification { position: relative; cursor: pointer; color: var(--secondary); font-size: 20px; transition: 0.3s;}
        .nav-notification:hover { color: var(--primary);}
        .nav-notification .badge-count { position: absolute; top: -5px; right: -5px; background: var(--accent-danger); color: white; font-size: 10px; font-weight: 800; padding: 2px 5px; border-radius: 50px; border: 2px solid white;}

        .nav-profile-info { text-align: right; display: flex; flex-direction: column; justify-content: center;}
        .nav-profile-info span { font-size: 14px; font-weight: 700; color: var(--text-dark); line-height: 1.2;}
        .nav-profile-info small { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;}
        .avatar-circle-admin { width: 45px; height: 45px; background: linear-gradient(135deg, #10B981, #059669); border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 18px; box-shadow: var(--shadow-sm); border: 2px solid white; }

        .dashboard-container { padding: 40px; flex-grow: 1; max-width: 1600px; margin: 0 auto; width: 100%; }

        /* ====================================================================
           HERO BANNER
           ==================================================================== */
        .hero-banner { background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: var(--border-radius-lg); padding: 40px 50px; color: white; display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; box-shadow: var(--shadow-lg); position: relative; overflow: hidden; }
        .hero-banner::after { content: ''; position: absolute; bottom: -100px; right: -50px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none;}
        .hero-content { position: relative; z-index: 2; max-width: 600px;}
        .hero-content h1 { font-size: 30px; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.5px;}
        .hero-content p { color: #CBD5E1; font-size: 15px; font-weight: 500; margin: 0; line-height: 1.6;}
        
        .hero-actions { display: flex; gap: 15px; position: relative; z-index: 2;}
        .btn-glow { background: var(--primary); color: white; padding: 12px 24px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 14px; border: none; transition: var(--transition-speed); box-shadow: var(--shadow-glow); display: flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none;}
        .btn-glow:hover { background: var(--primary-hover); color: white; transform: translateY(-2px); box-shadow: 0 0 25px rgba(37, 99, 235, 0.6);}

        /* ====================================================================
           STATISTICS GRID
           ==================================================================== */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 35px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 25px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-sm); transition: transform var(--transition-speed); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md);}
        .stat-icon { width: 65px; height: 65px; border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; }
        .icon-blue { background: linear-gradient(135deg, #EFF6FF, #DBEAFE); color: var(--primary); }
        .icon-teal { background: linear-gradient(135deg, #ECFDF5, #D1FAE5); color: var(--accent-success); }
        .icon-purple { background: linear-gradient(135deg, #F5F3FF, #E0E7FF); color: var(--accent-purple); }
        .icon-orange { background: linear-gradient(135deg, #FFFBEB, #FEF3C7); color: var(--accent-warning); }
        .stat-data h3 { font-size: 32px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-dark); line-height: 1;}
        .stat-data p { margin: 0; font-size: 13px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;}

        /* ====================================================================
           LAYOUT GRID (TABLES)
           ==================================================================== */
        .content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); box-shadow: var(--shadow-sm); padding: 0; margin-bottom: 24px; overflow: hidden; }
        
        /* Tabs inside Cards */
        .custom-nav-pills { display: flex; gap: 10px; border-bottom: 2px solid #F1F5F9; padding: 20px 30px; background: #F8FAFC; margin: 0; overflow-x: auto; white-space: nowrap;}
        .custom-nav-pills .nav-link { color: var(--text-muted); font-weight: 700; padding: 10px 20px; border-radius: 50px; font-size: 14px; border: 1px solid transparent; transition: var(--transition-speed);}
        .custom-nav-pills .nav-link.active { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(37,99,235,0.2);}
        
        /* ====================================================================
           DATA TABLES
           ==================================================================== */
        .table-responsive { overflow-x: auto; padding: 0 30px 30px 30px;}
        .table { margin: 0; width: 100%; border-collapse: collapse; min-width: 900px; }
        .table th { padding: 15px 20px; font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--secondary); letter-spacing: 0.5px; border-bottom: 2px solid var(--border-color); text-align: left;}
        .table td { padding: 18px 20px; font-size: 14px; font-weight: 600; color: var(--text-dark); border-bottom: 1px dashed #E2E8F0; vertical-align: middle; }
        .table tbody tr:hover { background-color: #F8FAFC; }

        .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-active { background: #D1FAE5; color: #059669; border: 1px solid #A7F3D0;}
        .badge-inactive { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA;}
        .badge-pending { background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A;}

        .action-btns { display: flex; gap: 8px; align-items: center;}
        .btn-icon { width: 35px; height: 35px; border-radius: 8px; border: 1px solid var(--border-color); background: white; color: var(--secondary); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; padding: 0; text-decoration: none;}
        .btn-icon:hover { background: #F1F5F9; color: var(--text-dark); transform: translateY(-2px); box-shadow: var(--shadow-sm);}
        .btn-approve-action { background: #ECFDF5; color: #059669; border-color: #A7F3D0;}
        .btn-approve-action:hover { background: #10B981; color: white; border-color: #10B981; }
        .btn-reject-action { background: #FEF2F2; color: #EF4444; border-color: #FECACA;}
        .btn-reject-action:hover { background: #EF4444; color: white; border-color: #EF4444; }

        /* Course Info Block */
        .course-info-block { display: flex; flex-direction: column; gap: 3px;}
        .course-code { font-size: 11px; color: var(--primary); font-weight: 800; background: var(--primary-light); padding: 3px 8px; border-radius: 4px; display: inline-block; width: max-content; border: 1px solid #BFDBFE;}
        
        /* Req Tags */
        .req-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;}
        .req-tag { font-size: 10px; background: #F1F5F9; color: var(--secondary); padding: 3px 8px; border-radius: 4px; font-weight: 700; border: 1px solid #E2E8F0; display: inline-flex; align-items: center; gap: 4px;}

        /* ====================================================================
           MODALS
           ==================================================================== */
        .modal-content { border-radius: var(--border-radius-lg); border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { border-bottom: 1px solid var(--border-color); padding: 25px 30px; background: #F8FAFC; border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;}
        .modal-title { font-weight: 800; font-size: 20px; color: var(--text-dark); display: flex; align-items: center; gap: 10px;}
        .modal-body { padding: 30px; }
        .modal-footer { border-top: 1px solid var(--border-color); padding: 20px 30px; background: #F8FAFC; border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);}
        
        .form-label { font-size: 13px; font-weight: 800; color: var(--secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
        .form-control, .form-select { border-radius: var(--border-radius-sm); font-weight: 600; padding: 14px 16px; border: 1px solid var(--border-color); background-color: #FFFFFF; color: var(--text-dark); transition: all var(--transition-speed); font-size: 15px;}
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); outline: none;}

        /* Responsive Design */
        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); }
            #main-content { margin-left: 0; }
            .mobile-toggle-btn { display: block; }
            .top-navbar { padding: 15px 20px; }
            .search-bar { display: none; }
            .hero-banner { flex-direction: column; text-align: left; align-items: flex-start; gap: 20px;}
            .page-container { padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon"><i class="fas fa-shield-alt"></i></div>
            <h4>NIELIT<span style="color: #94A3B8; font-weight: 500;">TPMS</span></h4>
            <span>Super Admin Hub</span>
        </div>
        
        <div class="sidebar-menu">
            <div class="sidebar-menu-category">System Overview</div>
            <a href="admin_dashboard.php"><i class="fas fa-border-all"></i> Master Dashboard</a>
            
            <div class="sidebar-menu-category mt-4">Partner Management</div>
            <a href="admin_manage_tp.php"><i class="fas fa-building"></i> Center Directory</a>
            <a href="admin_courses.php" class="active"><i class="fas fa-certificate"></i> Course & Approvals</a>
            
            <div class="sidebar-menu-category mt-4">Academic Records</div>
            <a href="admin_student_reports.php"><i class="fas fa-users"></i> Global Students</a>
            <a href="admin_placements.php"><i class="fas fa-briefcase"></i> Placements</a>
            
            <div class="sidebar-menu-category mt-4">Administration</div>
            <a href="admin_upload_notice.php"><i class="fas fa-bullhorn"></i> Push Notices</a>
            <a href="admin_helpdesk_upload.php"><i class="fas fa-headset"></i> Support Tickets</a>
            <a href="admin_manage_admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="btn-logout"><i class="fas fa-power-off"></i> Secure Logout</a>
        </div>
    </aside>

    <main id="main-content">
        
        <header class="top-navbar">
            <div class="top-nav-left">
                <button class="mobile-toggle-btn" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Search catalog or pending requests...">
                </div>
            </div>
            
            <div class="nav-profile-area">
                <div class="nav-notification">
                    <i class="fas fa-bell"></i>
                    <?php if($stats['pending_requests'] > 0): ?>
                        <span class="badge-count"><?= $stats['pending_requests'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="nav-profile-info d-none d-sm-flex">
                    <span>Super Administrator</span>
                    <small>Academic Authority</small>
                </div>
                <div class="avatar-circle-admin">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="page-container">
            
            <?php if (!empty($db_errors)): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-4" style="border-radius: var(--border-radius-md); font-weight: 600; border-left: 5px solid var(--accent-danger) !important;">
                    <i class="fas fa-exclamation-triangle me-2 fs-5 text-danger"></i> <strong>System Alert:</strong>
                    <ul class="mb-0 mt-2" style="font-size: 13px;">
                        <?php foreach($db_errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: var(--border-radius-md); font-weight: 600; border-left: 5px solid <?= $msg_type=='success'?'var(--accent-success)':'var(--accent-warning)' ?> !important;">
                    <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-warning' ?> me-2 fs-5"></i> 
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="hero-banner">
                <div class="hero-content">
                    <h1>Academic Catalog & Approvals</h1>
                    <p>Manage the master repository of NIELIT courses. Approve or reject accreditation requests submitted by Training Partners to grant them batch creation access.</p>
                </div>
                <div class="hero-actions">
                    <button type="button" class="btn-glow" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                        <i class="fas fa-plus"></i> Add New Master Course
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue"><i class="fas fa-book"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['total_courses']) ?></h3>
                        <p>Total Master Courses</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-teal"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['active_courses']) ?></h3>
                        <p>Active & Available</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-orange"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['pending_requests']) ?></h3>
                        <p>Pending Accreditations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="fas fa-certificate"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['total_approved_accr']) ?></h3>
                        <p>Approved Accreditations</p>
                    </div>
                </div>
            </div>

            <div class="content-card">
                
                <div class="nav custom-nav-pills" id="adminCourseTabs" role="tablist">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-catalog" type="button">
                        <i class="fas fa-book-open me-2"></i> Master Course Catalog
                    </button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-requests" type="button">
                        <i class="fas fa-certificate me-2"></i> Accreditation Requests
                        <?php if($stats['pending_requests'] > 0): ?>
                            <span class="badge bg-danger ms-2 rounded-pill"><?= $stats['pending_requests'] ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <div class="tab-content" id="adminCourseTabsContent">
                    
                    <div class="tab-pane fade show active" id="tab-catalog" role="tabpanel">
                        <div class="p-4 pt-2 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold m-0" style="font-size: 16px;">Global Academic Portfolio</h5>
                                <p class="text-muted m-0 mt-1" style="font-size: 12px;">Courses listed here are available for TPs to request.</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table" id="catalogTable">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Course Identification</th>
                                        <th style="width: 15%;">Duration</th>
                                        <th style="width: 25%;">Infrastructure & Faculty Req.</th>
                                        <th style="width: 15%;">Global Status</th>
                                        <th style="width: 10%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($master_catalog)): ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted">No courses found in the master catalog.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($master_catalog as $course): 
                                            $c_status = strtolower($course['status'] ?? 'active');
                                            $s_badge = ($c_status === 'active') ? 'badge-active' : 'badge-inactive';
                                            $s_icon = ($c_status === 'active') ? 'fa-check-circle' : 'fa-times-circle';
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="course-info-block">
                                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($course['course_name']) ?></div>
                                                        <div class="course-code">ID: #<?= str_pad($course['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                                        <?php if(!empty($course['eligibility'])): ?>
                                                            <div class="text-muted mt-1" style="font-size: 12px;">Elig: <?= htmlspecialchars($course['eligibility']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-secondary"><i class="far fa-clock me-1"></i> <?= htmlspecialchars($course['duration']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="req-tags">
                                                        <?php if(!empty($course['carpet_area'])): ?>
                                                            <span class="req-tag" title="<?= htmlspecialchars($course['carpet_area']) ?>"><i class="fas fa-expand-arrows-alt text-primary"></i> <?= htmlspecialchars($course['carpet_area']) ?> Sq.ft</span>
                                                        <?php endif; ?>
                                                        <?php if(!empty($course['system_requirements'])): ?>
                                                            <span class="req-tag" title="<?= htmlspecialchars($course['system_requirements']) ?>"><i class="fas fa-desktop text-primary"></i> Lab Systems</span>
                                                        <?php endif; ?>
                                                        <?php if(!empty($course['faculty_requirements'])): ?>
                                                            <span class="req-tag" title="<?= htmlspecialchars($course['faculty_requirements']) ?>"><i class="fas fa-user-tie text-primary"></i> Specialized Faculty</span>
                                                        <?php endif; ?>
                                                        <?php if(empty($course['carpet_area']) && empty($course['system_requirements']) && empty($course['faculty_requirements'])): ?>
                                                            <span class="text-muted" style="font-size: 12px; font-style: italic;">Standard Requirements</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?= $s_badge ?>"><i class="fas <?= $s_icon ?>"></i> <?= ucfirst($c_status) ?></span>
                                                </td>
                                                <td>
                                                    <div class="action-btns">
                                                        <button class="btn-icon" title="Edit Course Details"><i class="fas fa-edit"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-requests" role="tabpanel">
                        <div class="p-4 pt-2 d-flex justify-content-between align-items-center border-bottom mb-2">
                            <div>
                                <h5 class="fw-bold m-0" style="font-size: 16px;">Pending Accreditation Approvals</h5>
                                <p class="text-muted m-0 mt-1" style="font-size: 12px;">Review applications from TPs. Approving grants them permission to create active batches.</p>
                            </div>
                        </div>

                        <div class="table-responsive pb-4">
                            <table class="table" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th>Request Data</th>
                                        <th>Training Partner</th>
                                        <th>Requested Program</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pending_requests)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fas fa-check-circle fs-1 text-success mb-3 opacity-50 d-block"></i>
                                                <div class="fw-bold">All Caught Up!</div>
                                                <div style="font-size: 13px;">There are no pending accreditation requests at this time.</div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pending_requests as $req): ?>
                                            <tr>
                                                <td>
                                                    <div class="course-info-block">
                                                        <div class="course-code" style="background: var(--accent-warning); color: white; border: none;">REQ: <?= htmlspecialchars($req['batch_number']) ?></div>
                                                        <div class="text-muted mt-1" style="font-size: 12px;"><i class="far fa-calendar-alt"></i> Applied: <?= date('d M Y', strtotime($req['created_at'])) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($req['institute_name'] ?? 'Unknown Institute') ?></div>
                                                    <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($req['tp_name']) ?> | <?= htmlspecialchars($req['tp_email']) ?></div>
                                                    <a href="admin_manage_tp.php" class="text-primary mt-1 d-inline-block" style="font-size: 11px; font-weight: 700; text-decoration: none;"><i class="fas fa-external-link-alt me-1"></i> Verify Center Infrastructure</a>
                                                </td>
                                                <td>
                                                    <div class="fw-bold" style="color: var(--secondary); font-size: 14px;"><?= htmlspecialchars($req['course_name']) ?></div>
                                                    <div style="font-size: 12px; color: var(--text-muted);"><i class="far fa-clock"></i> <?= htmlspecialchars($req['duration']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="action-btns">
                                                        <form method="POST" action="" onsubmit="return confirm('APPROVE accreditation? The TP will be able to create batches immediately.');" class="m-0 p-0">
                                                            <input type="hidden" name="request_id" value="<?= $req['req_id'] ?>">
                                                            <input type="hidden" name="action" value="approve_request">
                                                            <button type="submit" class="btn-icon btn-approve-action text-success border-success" title="Approve Request">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="" onsubmit="return confirm('REJECT this accreditation request?');" class="m-0 p-0">
                                                            <input type="hidden" name="request_id" value="<?= $req['req_id'] ?>">
                                                            <input type="hidden" name="action" value="reject_request">
                                                            <button type="submit" class="btn-icon btn-reject-action text-danger border-danger" title="Reject Request">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle text-primary"></i> Add Course to Master Catalog</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="addCourseForm">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="action" value="add_course">
                        
                        <div class="p-4 bg-white rounded border shadow-sm mb-4">
                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Academic Details</h6>
                            <div class="mb-3">
                                <label class="form-label">Official Course Name <span class="text-danger">*</span></label>
                                <input type="text" name="course_name" class="form-control bg-light" required placeholder="e.g., O Level (IT), Cyber Security Assistant...">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Total Duration <span class="text-danger">*</span></label>
                                    <input type="text" name="duration" class="form-control bg-light" required placeholder="e.g., 540 Hours">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Candidate Eligibility Requirement</label>
                                    <input type="text" name="eligibility" class="form-control bg-light" placeholder="e.g., Level 4 (18 Credits), 10+2 Passed...">
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-white rounded border shadow-sm">
                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Mandatory Center Infrastructure Requirements</h6>
                            <p class="text-muted mb-3" style="font-size: 12px;">Define the minimum infrastructure a Training Partner must have to be approved for this course.</p>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Min. Carpet Area</label>
                                    <input type="text" name="carpet_area" class="form-control bg-light" placeholder="e.g., 500 Sq.Ft.">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">System / Hardware Requirements</label>
                                    <input type="text" name="system_requirements" class="form-control bg-light" placeholder="e.g., i5, 8GB RAM, High-speed Internet...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Specific Faculty Requirements</label>
                                    <input type="text" name="faculty_requirements" class="form-control bg-light" placeholder="e.g., MCA/B.Tech with 2 years teaching experience...">
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="submitCourseBtn">Publish to Catalog</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

            // 2. FORM SUBMISSION UI UX (Prevent Double Clicks)
            const form = document.getElementById('addCourseForm');
            if(form) {
                form.addEventListener('submit', function() {
                    const btn = document.getElementById('submitCourseBtn');
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Publishing...';
                    btn.classList.add('disabled');
                });
            }

            // 3. GLOBAL SEARCH LOGIC (Filters active table)
            const searchInput = document.getElementById('globalSearch');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = this.value.toLowerCase();
                    
                    // Determine which tab is active to filter the correct table
                    const activeTab = document.querySelector('.tab-pane.active');
                    let targetTableId = '';
                    if(activeTab.id === 'tab-catalog') targetTableId = 'catalogTable';
                    else if(activeTab.id === 'tab-requests') targetTableId = 'requestsTable';
                    
                    if(targetTableId !== '') {
                        const rows = document.querySelectorAll(`#${targetTableId} tbody tr`);
                        rows.forEach(row => {
                            if(row.cells.length === 1) return; // Skip empty state row
                            const text = row.innerText.toLowerCase();
                            row.style.display = text.includes(filter) ? '' : 'none';
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>