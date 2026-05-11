<?php
/**
 * ============================================================================
 * NIELIT TPMS - GLOBAL STUDENT RECORDS & REPORTS
 * ============================================================================
 * File: admin_student_reports.php
 * Description: Master reporting hub for Administrators to view, search, analyze, 
 * and export the global database of all students enrolled across all authorized 
 * Training Partners.
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

// ============================================================================
// 2. HANDLE REAL CSV EXPORT FUNCTIONALITY
// ============================================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=NIELIT_Global_Student_Report_' . date('Ymd') . '.csv');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Output column headings
    fputcsv($output, array('Student ID', 'Full Name', 'Mobile Number', 'Email Address', 'Course & Batch', 'Current Status', 'Enrollment Date', 'Training Partner Name', 'TP Contact Email'));
    
    // Fetch data specifically for export
    $export_query = "
        SELECT s.id, s.full_name, s.mobile, s.email, s.course_name, s.status, s.enrollment_date, c.institute_name, c.contact_email 
        FROM students s 
        LEFT JOIN centers c ON s.tp_email = c.contact_email 
        ORDER BY s.id DESC
    ";
    $export_res = $conn->query($export_query);
    
    if ($export_res) {
        while ($row = $export_res->fetch_assoc()) {
            // Format date for CSV
            $date = isset($row['enrollment_date']) ? date('Y-m-d', strtotime($row['enrollment_date'])) : 'N/A';
            
            fputcsv($output, array(
                'STU-' . str_pad($row['id'], 6, '0', STR_PAD_LEFT),
                $row['full_name'],
                $row['mobile'],
                $row['email'] ?? 'N/A',
                $row['course_name'],
                $row['status'],
                $date,
                $row['institute_name'] ?? 'Unknown TP',
                $row['contact_email'] ?? 'N/A'
            ));
        }
    }
    fclose($output);
    exit(); // Stop further rendering of the page
}

// ============================================================================
// 3. FETCH COMPREHENSIVE STUDENT STATISTICS
// ============================================================================
$stats = [
    'total' => 0,
    'active' => 0,
    'completed' => 0,
    'dropped' => 0
];

$res_stats = $conn->query("SELECT status, COUNT(*) as count FROM students GROUP BY status");
if ($res_stats) {
    while ($row = $res_stats->fetch_assoc()) {
        $stats['total'] += $row['count'];
        $stat_status = strtolower($row['status']);
        
        if ($stat_status === 'active') $stats['active'] += $row['count'];
        elseif ($stat_status === 'completed') $stats['completed'] += $row['count'];
        elseif ($stat_status === 'dropped') $stats['dropped'] += $row['count'];
    }
} else {
    $db_errors[] = "Statistics Query Error: " . $conn->error;
}

// ============================================================================
// 4. FETCH ALL GLOBAL STUDENT RECORDS
// ============================================================================
$all_students = [];
// Joining students with centers table to get the human-readable Institute Name
$query = "
    SELECT s.*, c.institute_name 
    FROM students s 
    LEFT JOIN centers c ON s.tp_email = c.contact_email 
    ORDER BY s.id DESC
";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_students[] = $row;
    }
} else {
    $db_errors[] = "Data Fetch Error: " . $conn->error;
}

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
    <title>Global Student Records - NIELIT Admin</title>
    
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
            --shadow-glow-success: 0 0 20px rgba(16, 185, 129, 0.3);
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
        
        .nav-profile-area { display: flex; align-items: center; gap: 20px; margin-left: auto;}
        .nav-profile-info { text-align: right; display: flex; flex-direction: column; justify-content: center;}
        .nav-profile-info span { font-size: 14px; font-weight: 700; color: var(--text-dark); line-height: 1.2;}
        .nav-profile-info small { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;}
        .avatar-circle-admin { width: 45px; height: 45px; background: linear-gradient(135deg, #10B981, #059669); border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 18px; box-shadow: var(--shadow-sm); border: 2px solid white; }

        .dashboard-container { padding: 40px; flex-grow: 1; max-width: 1600px; margin: 0 auto; width: 100%; }

        /* ====================================================================
           HERO BANNER
           ==================================================================== */
        .hero-banner { background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: var(--border-radius-lg); padding: 40px 50px; color: white; display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; box-shadow: var(--shadow-lg); position: relative; overflow: hidden; }
        .hero-banner::after { content: ''; position: absolute; top: -100px; right: -50px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none;}
        .hero-content { position: relative; z-index: 2; max-width: 600px;}
        .hero-content h1 { font-size: 30px; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.5px;}
        .hero-content p { color: #CBD5E1; font-size: 15px; font-weight: 500; margin: 0; line-height: 1.6;}
        
        .hero-actions { display: flex; gap: 15px; position: relative; z-index: 2;}
        .btn-glow-success { background: var(--accent-success); color: white; padding: 12px 24px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 14px; border: none; transition: var(--transition-speed); box-shadow: var(--shadow-glow-success); display: flex; align-items: center; gap: 8px; text-decoration: none;}
        .btn-glow-success:hover { background: #059669; color: white; transform: translateY(-2px); box-shadow: 0 0 25px rgba(16, 185, 129, 0.6);}

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
        .icon-red { background: linear-gradient(135deg, #FEF2F2, #FECACA); color: var(--accent-danger); }
        .stat-data h3 { font-size: 32px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-dark); line-height: 1;}
        .stat-data p { margin: 0; font-size: 13px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;}

        /* ====================================================================
           ANALYTICS & TABLES LAYOUT
           ==================================================================== */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 350px; gap: 24px; align-items: start; }

        .content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); box-shadow: var(--shadow-sm); padding: 30px; margin-bottom: 24px; overflow: hidden; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #F1F5F9;}
        .card-header-flex h5 { font-weight: 800; font-size: 18px; margin: 0; color: var(--text-dark); display: flex; align-items: center; gap: 10px;}

        /* Search Box */
        .search-box { background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 50px; padding: 10px 20px; display: flex; align-items: center; width: 350px; max-width: 100%; transition: var(--transition-speed);}
        .search-box:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); background: white;}
        .search-box i { color: #94A3B8; margin-right: 12px; }
        .search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 14px; color: var(--text-dark); font-weight: 500;}

        /* Table */
        .table-responsive { overflow-x: auto; margin: 0 -30px; padding: 0 30px;}
        .table { margin: 0; width: 100%; border-collapse: collapse; min-width: 1000px; }
        .table th { padding: 15px 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--secondary); letter-spacing: 0.5px; border-bottom: 2px solid var(--border-color); text-align: left; background: #F8FAFC;}
        .table td { padding: 18px 20px; font-size: 14px; font-weight: 600; color: var(--text-dark); border-bottom: 1px dashed #E2E8F0; vertical-align: middle; }
        .table tbody tr:hover { background-color: #F8FAFC; }

        /* Badges */
        .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-active { background: #DBEAFE; color: #2563EB; border: 1px solid #BFDBFE;}
        .badge-completed { background: #D1FAE5; color: #059669; border: 1px solid #A7F3D0;}
        .badge-dropped { background: #FEF2F2; color: #EF4444; border: 1px solid #FECACA;}

        /* Specific Table Data Blocks */
        .student-info-block { display: flex; align-items: center; gap: 15px;}
        .student-avatar { width: 35px; height: 35px; border-radius: 8px; background: #E0E7FF; color: #4338CA; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;}
        .course-tag { background: #F1F5F9; color: var(--secondary); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; border: 1px solid #E2E8F0; display: inline-block;}

        /* Chart Area */
        .chart-container { position: relative; height: 300px; width: 100%; margin-top: 20px;}

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-grid { grid-template-columns: 1fr; } /* Stack charts and table on medium screens */
            .chart-container { height: 250px; }
        }
        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); }
            #main-content { margin-left: 0; }
            .mobile-toggle-btn { display: block; }
            .top-navbar { padding: 15px 20px; }
            .hero-banner { flex-direction: column; text-align: left; align-items: flex-start; gap: 20px;}
            .page-container { padding: 20px; }
            .card-header-flex { flex-direction: column; align-items: flex-start; gap: 15px;}
            .search-box { width: 100%;}
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
            <a href="admin_courses.php"><i class="fas fa-certificate"></i> Course & Approvals</a>
            
            <div class="sidebar-menu-category mt-4">Academic Records</div>
            <a href="admin_student_reports.php" class="active"><i class="fas fa-users"></i> Global Students</a>
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
                <div class="d-none d-md-block">
                    <span class="badge bg-light text-dark border shadow-sm" style="font-weight: 700; padding: 8px 12px;">
                        <i class="fas fa-circle text-success me-1" style="font-size: 10px;"></i> Network Online
                    </span>
                </div>
            </div>
            
            <div class="nav-profile-area">
                <div class="nav-profile-info d-none d-sm-flex">
                    <span>Super Administrator</span>
                    <small>System Authority</small>
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

            <div class="hero-banner">
                <div class="hero-content">
                    <h1>Global Student Records & Analytics</h1>
                    <p>Centralized hub to view, track, and export the massive database of all students enrolled across the entire network of authorized NIELIT Training Partners.</p>
                </div>
                <div class="hero-actions">
                    <a href="?export=csv" class="btn-glow-success">
                        <i class="fas fa-download"></i> Export Full Database (CSV)
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['total']) ?></h3>
                        <p>Total Lifetime Enrollments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-teal"><i class="fas fa-user-check"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['active']) ?></h3>
                        <p>Currently Active Training</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="fas fa-graduation-cap"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['completed']) ?></h3>
                        <p>Successfully Completed</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-red"><i class="fas fa-user-minus"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['dropped']) ?></h3>
                        <p>Dropped / Inactive</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                
                <div class="grid-left">
                    <div class="content-card">
                        <div class="card-header-flex">
                            <h5><i class="fas fa-database text-primary me-2"></i> Master Student Database</h5>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search by student, center, or course...">
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table" id="studentTable">
                                <thead>
                                    <tr>
                                        <th>Student Identity</th>
                                        <th>Program Enrolled</th>
                                        <th>Training Partner Data</th>
                                        <th>Enrollment Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($all_students)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fas fa-folder-open fs-2 mb-3 d-block opacity-50"></i>
                                                No student records found in the database.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($all_students as $student): 
                                            $s_status = strtolower($student['status'] ?? 'active');
                                            $s_class = 'badge-active';
                                            $s_icon = 'fa-circle';
                                            
                                            if ($s_status === 'completed') { $s_class = 'badge-completed'; $s_icon = 'fa-check-circle'; }
                                            elseif ($s_status === 'dropped') { $s_class = 'badge-dropped'; $s_icon = 'fa-times-circle'; }
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="student-info-block">
                                                        <div class="student-avatar">
                                                            <?= strtoupper(substr($student['full_name'], 0, 2)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark fs-6" style="margin-bottom: 2px;"><?= htmlspecialchars($student['full_name']) ?></div>
                                                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;"><i class="fas fa-phone-alt me-1"></i> <?= htmlspecialchars($student['mobile']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="course-tag" title="<?= htmlspecialchars($student['course_name']) ?>">
                                                        <i class="fas fa-book-reader text-primary me-1"></i> 
                                                        <span style="display:inline-block; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; vertical-align: bottom;">
                                                            <?= htmlspecialchars($student['course_name']) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold" style="color: var(--secondary); font-size: 13px;">
                                                        <i class="fas fa-building me-1 text-muted"></i> <?= htmlspecialchars($student['institute_name'] ?? 'Unknown TP') ?>
                                                    </div>
                                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px; margin-left: 18px;">
                                                        <?= htmlspecialchars($student['tp_email']) ?>
                                                    </div>
                                                </td>
                                                <td style="font-size: 13px; font-weight: 600; color: var(--secondary);">
                                                    <?= isset($student['enrollment_date']) ? date('d M Y', strtotime($student['enrollment_date'])) : 'N/A' ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?= $s_class ?>"><i class="fas <?= $s_icon ?>"></i> <?= ucfirst($s_status) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="grid-right d-none d-xl-block">
                    <div class="content-card" style="position: sticky; top: 100px;">
                        <div class="card-header-flex border-bottom-0 pb-0 mb-3">
                            <h5 style="font-size: 16px;"><i class="fas fa-chart-pie text-primary"></i> Status Distribution</h5>
                        </div>
                        <p class="text-muted mb-4" style="font-size: 12px; line-height: 1.5;">This chart visualizes the current retention and completion rates across all globally registered students.</p>
                        
                        <div class="chart-container">
                            <canvas id="studentStatusChart"></canvas>
                        </div>
                        
                        <div class="mt-4 pt-4 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 12px; font-weight: 700;">
                                <span><i class="fas fa-circle text-primary me-2"></i> Active Retained</span>
                                <span class="text-dark"><?= ($stats['total'] > 0) ? round(($stats['active'] / $stats['total']) * 100) : 0 ?>%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 12px; font-weight: 700;">
                                <span><i class="fas fa-circle text-success me-2"></i> Successfully Graduated</span>
                                <span class="text-dark"><?= ($stats['total'] > 0) ? round(($stats['completed'] / $stats['total']) * 100) : 0 ?>%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="font-size: 12px; font-weight: 700;">
                                <span><i class="fas fa-circle text-danger me-2"></i> Dropout Rate</span>
                                <span class="text-dark"><?= ($stats['total'] > 0) ? round(($stats['dropped'] / $stats['total']) * 100) : 0 ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <div class="text-center text-muted mt-5 pt-4 border-top" style="font-size: 13px; font-weight: 600;">
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

            // 2. LIVE SEARCH FILTER
            const searchInput = document.getElementById('searchInput');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#studentTable tbody tr');
                    
                    rows.forEach(row => {
                        if(row.cells.length === 1) return; // Skip empty state row
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }

            // 3. CHART.JS: STUDENT STATUS DISTRIBUTION
            const ctxStatus = document.getElementById('studentStatusChart');
            if(ctxStatus) {
                const total = <?= $stats['total'] ?>;
                
                const data = {
                    labels: ['Active', 'Completed', 'Dropped'],
                    datasets: [{
                        data: total > 0 ? [<?= $stats['active'] ?>, <?= $stats['completed'] ?>, <?= $stats['dropped'] ?>] : [1],
                        backgroundColor: total > 0 ? ['#2563EB', '#10B981', '#EF4444'] : ['#E2E8F0'],
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
                        cutout: '75%', // Modern thin ring
                        plugins: {
                            legend: { display: false }, // Using custom HTML legend below it
                            tooltip: {
                                enabled: total > 0,
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