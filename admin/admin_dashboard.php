<?php
/**
 * ============================================================================
 * NIELIT TPMS - SUPER ADMIN MASTER DASHBOARD
 * ============================================================================
 * File: admin_dashboard.php
 * Description: The central command hub for NIELIT Administrators. Provides 
 * high-level analytics, pending action queues (center approvals & course 
 * accreditations), and system health monitoring.
 * ============================================================================
 */

// 1. SECURITY & SESSION INITIALIZATION
session_name('NIELIT_TPMS');
session_start();

// Strict Role Checking: Kick out unauthorized users immediately
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
// 2. HANDLE QUICK ACTIONS (APPROVE / REJECT CENTERS)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['target_email'])) {
    $target_email = trim($_POST['target_email']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE users SET status = 'active' WHERE email = ?");
            $stmt1->bind_param("s", $target_email); 
            $stmt1->execute(); $stmt1->close();

            $stmt2 = $conn->prepare("UPDATE centers SET status = 'Approved' WHERE contact_email = ?");
            $stmt2->bind_param("s", $target_email); 
            $stmt2->execute(); $stmt2->close();

            $conn->commit();
            $message = "Success! Center approved. They have been granted full portal access.";
            $msg_type = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $db_errors[] = "Approve Error: " . $e->getMessage();
        }
    } elseif ($action === 'reject') {
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE users SET status = 'rejected' WHERE email = ?");
            $stmt1->bind_param("s", $target_email); 
            $stmt1->execute(); $stmt1->close();

            $stmt2 = $conn->prepare("UPDATE centers SET status = 'Rejected' WHERE contact_email = ?");
            $stmt2->bind_param("s", $target_email); 
            $stmt2->execute(); $stmt2->close();

            $conn->commit();
            $message = "Center application has been rejected and access denied.";
            $msg_type = "danger";
        } catch (Exception $e) {
            $conn->rollback();
            $db_errors[] = "Reject Error: " . $e->getMessage();
        }
    }
}

// ============================================================================
// 3. FETCH COMPREHENSIVE SYSTEM STATISTICS
// ============================================================================
$stats = [
    'active_centers' => 0,
    'pending_centers' => 0,
    'rejected_centers' => 0,
    'total_students' => 0,
    'total_courses' => 0,
    'pending_accreditations' => 0,
    'total_placements' => 0
];

// A. Center Status Distribution
$res_centers = $conn->query("SELECT status, COUNT(*) as count FROM centers GROUP BY status");
if ($res_centers) {
    while ($row = $res_centers->fetch_assoc()) {
        $stat_val = strtolower($row['status'] ?? 'pending'); 
        if (empty($stat_val)) $stat_val = 'pending';

        if ($stat_val === 'approved') $stats['active_centers'] += $row['count'];
        if ($stat_val === 'pending') $stats['pending_centers'] += $row['count'];
        if ($stat_val === 'rejected') $stats['rejected_centers'] += $row['count'];
    }
} else { $db_errors[] = "Center Stats Query Error: " . $conn->error; }

// B. Global Student Count
$res_students = $conn->query("SELECT COUNT(*) as count FROM students");
if ($res_students) { $stats['total_students'] = $res_students->fetch_assoc()['count']; }

// C. Active Courses in Catalog
$res_courses = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
if ($res_courses) { $stats['total_courses'] = $res_courses->fetch_assoc()['count']; }

// D. Pending Accreditation Requests (From TP Batches)
$res_accr = $conn->query("SELECT COUNT(*) as count FROM tp_batches WHERE status = 'pending'");
if ($res_accr) { $stats['pending_accreditations'] = $res_accr->fetch_assoc()['count']; }

// ============================================================================
// 4. FETCH DATA FOR DASHBOARD TABLES
// ============================================================================

// A. Fetch Recent Center Registrations (Limit 15)
$recent_centers = [];
$query_centers = "SELECT id, institute_name, contact_email, state, district, status, created_at FROM centers ORDER BY created_at DESC LIMIT 15";
$result_centers = $conn->query($query_centers);
if ($result_centers) {
    while ($row = $result_centers->fetch_assoc()) {
        $recent_centers[] = $row;
    }
} else { $db_errors[] = "Fetch Centers Error: " . $conn->error; }

// B. Fetch Pending Accreditation Requests (Requires Admin Action)
$pending_requests = [];
$query_reqs = "
    SELECT b.id as req_id, b.batch_number, b.created_at, 
           c.course_name, 
           u.name as tp_name, u.email as tp_email,
           (SELECT institute_name FROM centers WHERE contact_email = u.email LIMIT 1) as institute_name
    FROM tp_batches b
    JOIN courses c ON b.course_id = c.id
    JOIN users u ON b.tp_id = u.id
    WHERE b.status = 'pending'
    ORDER BY b.created_at ASC LIMIT 10
";
$result_reqs = $conn->query($query_reqs);
if ($result_reqs) {
    while ($row = $result_reqs->fetch_assoc()) {
        $pending_requests[] = $row;
    }
} else { $db_errors[] = "Fetch Requests Error: " . $conn->error; }

// Time-based greeting
$hour = date('H');
$greeting = ($hour < 12) ? 'Good Morning' : (($hour < 17) ? 'Good Afternoon' : 'Good Evening');
$greeting_icon = ($hour < 12) ? 'fa-sun text-warning' : (($hour < 17) ? 'fa-cloud-sun text-orange' : 'fa-moon text-indigo');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NIELIT Master Admin Command Center">
    <title>Master Admin Dashboard - NIELIT TPMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            /* Sidebar Palette (Deep Navy) */
            --sidebar-bg: #0B1121; 
            --sidebar-hover: #1E293B;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            
            /* Main Content Palette */
            --bg-body: #F4F7F9;
            --card-bg: #FFFFFF;
            
            /* Text Colors */
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --text-light: #F8FAFC;
            
            /* Brand Colors */
            --primary: #2563EB; 
            --primary-hover: #1D4ED8;
            --primary-light: #EFF6FF;
            --secondary: #475569;
            
            /* Accent Colors */
            --accent-success: #10B981;
            --accent-warning: #F59E0B;
            --accent-danger: #EF4444;
            --accent-purple: #8B5CF6;
            
            /* Structural Variables */
            --border-color: #E2E8F0;
            --sidebar-width: 280px;
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
            --transition-speed: 0.3s;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.02);
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
        
        /* Notifications Bell */
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
        .hero-banner::after { content: ''; position: absolute; bottom: -100px; right: -50px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none;}
        .hero-content { position: relative; z-index: 2; max-width: 600px;}
        .hero-content h1 { font-size: 30px; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.5px;}
        .hero-content p { color: #CBD5E1; font-size: 15px; font-weight: 500; margin: 0; line-height: 1.6;}
        
        .btn-glow { background: var(--primary); color: white; padding: 12px 24px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 14px; border: none; transition: var(--transition-speed); box-shadow: var(--shadow-glow); display: flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; position: relative; z-index: 2;}
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
           LAYOUT GRID (CHARTS & TABLES)
           ==================================================================== */
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start; }
        
        .content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); box-shadow: var(--shadow-sm); padding: 30px; margin-bottom: 24px; overflow: hidden; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #F1F5F9;}
        .card-header-flex h5 { font-weight: 800; font-size: 18px; margin: 0; color: var(--text-dark); display: flex; align-items: center; gap: 10px;}
        .btn-view-all { font-size: 13px; font-weight: 700; color: var(--primary); text-decoration: none; background: var(--primary-light); padding: 6px 15px; border-radius: 50px; transition: var(--transition-speed);}
        .btn-view-all:hover { background: #DBEAFE; color: var(--primary-hover);}

        /* Tabs inside Cards */
        .custom-nav-pills { display: flex; gap: 10px; border-bottom: 2px solid #F1F5F9; padding-bottom: 15px; margin-bottom: 20px; overflow-x: auto; white-space: nowrap;}
        .custom-nav-pills .nav-link { color: var(--text-muted); font-weight: 700; padding: 8px 18px; border-radius: 50px; font-size: 13px; border: 1px solid transparent; transition: var(--transition-speed);}
        .custom-nav-pills .nav-link.active { background: var(--primary-light); color: var(--primary-hover); border-color: #BFDBFE;}

        /* ====================================================================
           DATA TABLES & BUTTONS
           ==================================================================== */
        .table-responsive { overflow-x: auto; margin: 0 -30px; padding: 0 30px;}
        .table { margin: 0; width: 100%; border-collapse: collapse; min-width: 800px; }
        .table th { padding: 15px 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; border-bottom: 2px solid var(--border-color); text-align: left; background: #F8FAFC;}
        .table td { padding: 18px 20px; font-size: 14px; font-weight: 600; color: var(--text-dark); border-bottom: 1px solid #F1F5F9; vertical-align: middle; }
        .table tbody tr:hover { background-color: #F8FAFC; }

        .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-approved { background: #D1FAE5; color: #059669; border: 1px solid #A7F3D0;}
        .badge-pending { background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A;}
        .badge-rejected { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA;}

        .action-btns { display: flex; gap: 8px; align-items: center;}
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: white; color: var(--secondary); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; padding: 0; text-decoration: none;}
        .btn-icon:hover { background: #F1F5F9; color: var(--text-dark); transform: translateY(-2px); box-shadow: var(--shadow-sm);}
        .btn-approve-action:hover { background: var(--accent-success); color: white; border-color: var(--accent-success); }
        .btn-reject-action:hover { background: var(--accent-danger); color: white; border-color: var(--accent-danger); }
        .btn-view-action { color: var(--primary); background: var(--primary-light); border-color: #BFDBFE;}
        .btn-view-action:hover { background: var(--primary); color: white;}

        /* ====================================================================
           WIDGETS (Right Column)
           ==================================================================== */
        .chart-container { position: relative; height: 250px; width: 100%; margin-bottom: 20px;}
        
        .activity-timeline { position: relative; padding-left: 30px; margin-top: 20px;}
        .activity-timeline::before { content: ''; position: absolute; top: 0; left: 11px; height: 100%; width: 2px; background: #E2E8F0; border-radius: 2px;}
        .timeline-item { position: relative; margin-bottom: 20px;}
        .timeline-item:last-child { margin-bottom: 0;}
        .timeline-marker { position: absolute; left: -30px; top: 2px; width: 24px; height: 24px; border-radius: 50%; border: 4px solid white; box-shadow: var(--shadow-sm); z-index: 2;}
        .timeline-content { background: #F8FAFC; padding: 15px; border-radius: 12px; border: 1px solid #F1F5F9;}
        .timeline-title { font-size: 13px; font-weight: 700; color: var(--text-dark); margin-bottom: 3px;}
        .timeline-time { font-size: 11px; font-weight: 600; color: var(--text-muted);}

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); }
            #main-content { margin-left: 0; }
            .mobile-toggle-btn { display: block; }
            .top-navbar { padding: 15px 20px; }
            .search-bar { display: none; } /* Hide search on mobile to save space */
            .hero-banner { flex-direction: column; text-align: left; align-items: flex-start; gap: 20px;}
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
            <a href="admin_dashboard.php" class="active"><i class="fas fa-border-all"></i> Master Dashboard</a>
            
            <div class="sidebar-menu-category mt-4">Partner Management</div>
            <a href="admin_manage_tp.php"><i class="fas fa-building"></i> Center Directory</a>
            <a href="admin_courses.php"><i class="fas fa-certificate"></i> Course & Approvals</a>
            
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
                    <input type="text" placeholder="Global search: centers, students, batches...">
                </div>
            </div>
            
            <div class="nav-profile-area">
                <div class="nav-notification">
                    <i class="fas fa-bell"></i>
                    <?php if($stats['pending_centers'] > 0 || $stats['pending_accreditations'] > 0): ?>
                        <span class="badge-count"><?= $stats['pending_centers'] + $stats['pending_accreditations'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="nav-profile-info d-none d-sm-flex">
                    <span>Super Administrator</span>
                    <small>System Authority</small>
                </div>
                <div class="avatar-circle-admin">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="dashboard-container">
            
            <?php if (!empty($db_errors)): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-4" style="border-radius: var(--border-radius-md); font-weight: 600; border-left: 5px solid var(--accent-danger) !important;">
                    <i class="fas fa-exclamation-triangle me-2 fs-5 text-danger"></i> <strong>System Alert:</strong>
                    <ul class="mb-0 mt-2" style="font-size: 13px;">
                        <?php foreach($db_errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: var(--border-radius-md); font-weight: 600; border-left: 5px solid <?= $msg_type=='success'?'var(--accent-success)':'var(--accent-danger)' ?> !important;">
                    <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger' ?> me-2 fs-5"></i> 
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="hero-banner">
                <div class="hero-content">
                    <h1><i class="fas <?= $greeting_icon ?> me-2"></i> <?= $greeting ?>, Admin!</h1>
                    <p>Welcome to the Master Command Center. Monitor ecosystem growth, approve new Training Partners, and authorize course accreditations in real-time.</p>
                </div>
                <div class="hero-actions">
                    <a href="admin_manage_tp.php" class="btn-glow">
                        <i class="fas fa-tasks"></i> Audit Center Directory
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue"><i class="fas fa-sitemap"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['active_centers']) ?></h3>
                        <p>Active Training Partners</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-teal"><i class="fas fa-users"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['total_students']) ?></h3>
                        <p>Total Registered Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-orange"><i class="fas fa-certificate"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['pending_accreditations']) ?></h3>
                        <p>Pending Accreditations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="fas fa-book"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['total_courses']) ?></h3>
                        <p>Master Catalog Courses</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                
                <div class="grid-left">
                    <div class="content-card p-0">
                        
                        <div class="px-4 pt-4 border-bottom bg-white rounded-top" style="border-radius: var(--border-radius-lg);">
                            <div class="nav custom-nav-pills" id="adminTabs" role="tablist">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-recent-tp" type="button">
                                    <i class="fas fa-building me-2"></i> Recent Registrations
                                    <?php if($stats['pending_centers'] > 0): ?>
                                        <span class="badge bg-danger ms-2 rounded-pill"><?= $stats['pending_centers'] ?></span>
                                    <?php endif; ?>
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-accreditations" type="button">
                                    <i class="fas fa-certificate me-2"></i> Accreditation Requests
                                    <?php if($stats['pending_accreditations'] > 0): ?>
                                        <span class="badge bg-danger ms-2 rounded-pill"><?= $stats['pending_accreditations'] ?></span>
                                    <?php endif; ?>
                                </button>
                            </div>
                        </div>

                        <div class="tab-content" id="adminTabsContent">
                            
                            <div class="tab-pane fade show active" id="tab-recent-tp" role="tabpanel">
                                <div class="p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="fw-bold m-0" style="font-size: 16px;">Latest Training Partner Applications</h5>
                                        <p class="text-muted m-0 mt-1" style="font-size: 12px;">Review and approve new center registrations.</p>
                                    </div>
                                    <a href="admin_manage_tp.php" class="btn-view-all">View Directory</a>
                                </div>

                                <div class="table-responsive pb-4">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Institute Name</th>
                                                <th>Location</th>
                                                <th>Applied On</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_centers)): ?>
                                                <tr><td colspan="5" class="text-center py-5 text-muted">No center registrations found in the database.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_centers as $tp): 
                                                    $status = strtolower($tp['status'] ?? 'pending');
                                                    $s_class = ($status == 'approved') ? 'badge-approved' : (($status == 'rejected') ? 'badge-rejected' : 'badge-pending');
                                                    $s_icon = ($status == 'approved') ? 'fa-check-circle' : (($status == 'rejected') ? 'fa-times-circle' : 'fa-hourglass-half');
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($tp['institute_name']) ?></div>
                                                            <div style="font-size: 11px; color: var(--primary); font-weight: 700;">ID: <?= str_pad($tp['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                                        </td>
                                                        <td>
                                                            <div class="fw-bold" style="color: var(--secondary); font-size: 13px;"><?= htmlspecialchars($tp['district']) ?></div>
                                                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;"><?= htmlspecialchars($tp['state']) ?></div>
                                                        </td>
                                                        <td style="font-size: 13px; font-weight: 600; color: var(--secondary);">
                                                            <?= date('d M Y', strtotime($tp['created_at'])) ?>
                                                        </td>
                                                        <td>
                                                            <span class="status-badge <?= $s_class ?>"><i class="fas <?= $s_icon ?>"></i> <?= ucfirst($status) ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="action-btns">
                                                                <a href="tp_profile.php?id=<?= $tp['id'] ?>" class="btn-icon btn-view-action" title="Full 360 Audit">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                                
                                                                <?php if($status === 'pending'): ?>
                                                                    <form method="POST" action="" onsubmit="return confirm('APPROVE this center? This grants portal access immediately.');" class="m-0 p-0">
                                                                        <input type="hidden" name="target_email" value="<?= htmlspecialchars($tp['contact_email']) ?>">
                                                                        <input type="hidden" name="action" value="approve">
                                                                        <button type="submit" class="btn-icon btn-approve-action text-success border-success" title="Quick Approve"><i class="fas fa-check"></i></button>
                                                                    </form>
                                                                    <form method="POST" action="" onsubmit="return confirm('REJECT this center?');" class="m-0 p-0">
                                                                        <input type="hidden" name="target_email" value="<?= htmlspecialchars($tp['contact_email']) ?>">
                                                                        <input type="hidden" name="action" value="reject">
                                                                        <button type="submit" class="btn-icon btn-reject-action text-danger border-danger" title="Quick Reject"><i class="fas fa-times"></i></button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-accreditations" role="tabpanel">
                                <div class="p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="fw-bold m-0" style="font-size: 16px;">Pending Course Accreditations</h5>
                                        <p class="text-muted m-0 mt-1" style="font-size: 12px;">Approve program requests to allow TPs to create batches.</p>
                                    </div>
                                    <a href="admin_courses.php" class="btn-view-all">Manage Accreditations</a>
                                </div>

                                <div class="table-responsive pb-4">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Training Partner</th>
                                                <th>Requested Course</th>
                                                <th>Date Submitted</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($pending_requests)): ?>
                                                <tr><td colspan="5" class="text-center py-5 text-muted">No pending accreditation requests found.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($pending_requests as $req): ?>
                                                    <tr>
                                                        <td class="fw-bold" style="font-family: monospace; color: var(--primary);"><?= htmlspecialchars($req['batch_number']) ?></td>
                                                        <td>
                                                            <div class="fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($req['institute_name']) ?></div>
                                                            <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($req['tp_email']) ?></div>
                                                        </td>
                                                        <td class="fw-bold" style="color: var(--secondary); font-size: 13px;"><?= htmlspecialchars($req['course_name']) ?></td>
                                                        <td style="font-size: 13px; font-weight: 600; color: var(--secondary);">
                                                            <?= date('d M Y', strtotime($req['created_at'])) ?>
                                                        </td>
                                                        <td>
                                                            <a href="admin_courses.php" class="btn-view-all" style="font-size: 11px; background: var(--accent-warning); color: white;">Review Request</a>
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

                <div class="grid-right">
                    
                    <div class="content-card">
                        <div class="card-header-flex border-bottom-0 pb-0 mb-3">
                            <h5 style="font-size: 16px;"><i class="fas fa-chart-pie text-primary"></i> Center Distribution</h5>
                        </div>
                        <div class="chart-container" style="height: 220px;">
                            <canvas id="centerStatusChart"></canvas>
                        </div>
                    </div>

                    <div class="content-card" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);">
                        <div class="card-header-flex border-bottom-0 pb-0 mb-3">
                            <h5 style="font-size: 16px;"><i class="fas fa-heartbeat text-success"></i> System Health</h5>
                        </div>
                        
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-white rounded border shadow-sm">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #ECFDF5; color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 18px;"><i class="fas fa-database"></i></div>
                            <div style="flex-grow: 1;">
                                <div class="fw-bold" style="font-size: 13px;">Database Connection</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Stable - Latency 12ms</div>
                            </div>
                            <span class="badge bg-success">Online</span>
                        </div>

                        <div class="d-flex align-items-center gap-3 p-3 bg-white rounded border shadow-sm">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #EFF6FF; color: #2563EB; display: flex; align-items: center; justify-content: center; font-size: 18px;"><i class="fas fa-server"></i></div>
                            <div style="flex-grow: 1;">
                                <div class="fw-bold" style="font-size: 13px;">Server Load</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Optimal - 14% Utilization</div>
                            </div>
                            <span class="badge bg-primary">Normal</span>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="card-header-flex border-bottom-0 pb-0 mb-2">
                            <h5 style="font-size: 16px;"><i class="fas fa-stream text-purple"></i> Activity Timeline</h5>
                        </div>
                        <div class="activity-timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker" style="background: var(--primary);"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">System Backup Completed</div>
                                    <div class="timeline-time">Today, 03:00 AM</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker" style="background: var(--accent-warning);"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">2 New Accreditations Requested</div>
                                    <div class="timeline-time">Yesterday, 14:45 PM</div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker" style="background: var(--accent-success);"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Apex Institute Approved</div>
                                    <div class="timeline-time">May 05, 10:15 AM</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> </div>
            
            <div class="text-center text-muted mt-5 pt-4 border-top" style="font-size: 13px; font-weight: 600;">
                &copy; <?= date('Y') ?> National Institute of Electronics & Information Technology (NIELIT). All Rights Reserved.
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

            // 2. CHART.JS: CENTER STATUS DISTRIBUTION
            const ctxStatus = document.getElementById('centerStatusChart');
            if(ctxStatus) {
                // Determine if we have any data to show, otherwise show empty state
                const total = <?= $stats['active_centers'] + $stats['pending_centers'] + $stats['rejected_centers'] ?>;
                
                const data = {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: total > 0 ? [<?= $stats['active_centers'] ?>, <?= $stats['pending_centers'] ?>, <?= $stats['rejected_centers'] ?>] : [1],
                        backgroundColor: total > 0 ? ['#10B981', '#F59E0B', '#EF4444'] : ['#E2E8F0'],
                        borderWidth: 0,
                        hoverOffset: total > 0 ? 4 : 0
                    }]
                };

                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 'bold' }, color: '#475569' }
                            },
                            tooltip: {
                                enabled: total > 0, // Disable tooltip if no data
                                backgroundColor: '#0F172A',
                                titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13 },
                                bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 14, weight: 'bold' },
                                padding: 12,
                                cornerRadius: 8
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>