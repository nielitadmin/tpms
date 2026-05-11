<?php
/**
 * ============================================================================
 * NIELIT TPMS - GLOBAL PLACEMENT RECORDS
 * ============================================================================
 * File: admin_placements.php
 * Description: Master reporting hub for Administrators to track, verify, and 
 * analyze successful job placements submitted by Training Partners.
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
// 2. HANDLE STATUS VERIFICATION ACTIONS
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $placement_id = (int)$_POST['placement_id'];
    
    if ($_POST['action'] === 'verify') {
        $stmt = $conn->prepare("UPDATE placements SET status = 'Verified' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $placement_id);
            if ($stmt->execute()) {
                $message = "Success! Placement record has been officially verified.";
                $msg_type = "success";
            } else {
                $message = "Database error: Could not verify record.";
                $msg_type = "danger";
            }
            $stmt->close();
        } else {
            $db_errors[] = "Prepare statement failed (Verify): " . $conn->error;
        }
    } elseif ($_POST['action'] === 'reject') {
        $stmt = $conn->prepare("UPDATE placements SET status = 'Rejected' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $placement_id);
            if ($stmt->execute()) {
                $message = "Placement record has been rejected.";
                $msg_type = "warning";
            } else {
                $message = "Database error: Could not reject record.";
                $msg_type = "danger";
            }
            $stmt->close();
        } else {
            $db_errors[] = "Prepare statement failed (Reject): " . $conn->error;
        }
    }
}

// ============================================================================
// 3. FETCH COMPREHENSIVE PLACEMENT STATISTICS
// ============================================================================
$stats = [
    'total' => 0, 
    'verified' => 0, 
    'pending' => 0, 
    'rejected' => 0,
    'top_company' => 'N/A',
    'top_salary' => 0
];

// A. Status Distribution
$res_stats = $conn->query("SELECT status, COUNT(*) as count FROM placements GROUP BY status");
if ($res_stats) {
    while ($row = $res_stats->fetch_assoc()) {
        $count = (int)$row['count'];
        $stats['total'] += $count;
        $stat_status = strtolower($row['status']);
        
        if ($stat_status === 'verified') $stats['verified'] += $count;
        elseif ($stat_status === 'pending') $stats['pending'] += $count;
        elseif ($stat_status === 'rejected') $stats['rejected'] += $count;
    }
} else {
    $db_errors[] = "Statistics Query Error: " . $conn->error;
}

// B. Top Hiring Company (Based on Verified Hires)
$res_company = $conn->query("SELECT company_name, COUNT(*) as hires FROM placements WHERE status = 'Verified' GROUP BY company_name ORDER BY hires DESC LIMIT 1");
if ($res_company && $res_company->num_rows > 0) {
    $top = $res_company->fetch_assoc();
    $stats['top_company'] = $top['company_name'];
}

// C. Highest Salary Package (Verified)
$res_salary = $conn->query("SELECT MAX(CAST(REPLACE(salary_package, ',', '') AS DECIMAL(10,2))) as max_salary FROM placements WHERE status = 'Verified'");
if ($res_salary && $res_salary->num_rows > 0) {
    $top_sal = $res_salary->fetch_assoc();
    $stats['top_salary'] = $top_sal['max_salary'] ?? 0;
}

// ============================================================================
// 4. FETCH ALL PLACEMENT RECORDS
// ============================================================================
$all_placements = [];
// Joining with centers table to get human-readable Institute Name
$query = "
    SELECT p.*, c.institute_name 
    FROM placements p 
    LEFT JOIN centers c ON p.tp_email = c.contact_email 
    ORDER BY p.created_at DESC
";
          
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_placements[] = $row;
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
    <title>Placement Analytics - NIELIT Admin</title>
    
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
        .hero-banner::after { content: ''; position: absolute; top: -100px; right: -50px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none;}
        .hero-content { position: relative; z-index: 2; max-width: 600px;}
        .hero-content h1 { font-size: 30px; font-weight: 800; margin-bottom: 10px; letter-spacing: -0.5px;}
        .hero-content p { color: #CBD5E1; font-size: 15px; font-weight: 500; margin: 0; line-height: 1.6;}

        /* ====================================================================
           STATISTICS GRID
           ==================================================================== */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 35px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 25px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-sm); transition: transform var(--transition-speed); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md);}
        .stat-icon { width: 65px; height: 65px; border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; }
        .icon-blue { background: linear-gradient(135deg, #EFF6FF, #DBEAFE); color: var(--primary); }
        .icon-teal { background: linear-gradient(135deg, #ECFDF5, #D1FAE5); color: var(--accent-success); }
        .icon-orange { background: linear-gradient(135deg, #FFFBEB, #FEF3C7); color: var(--accent-warning); }
        .icon-purple { background: linear-gradient(135deg, #F5F3FF, #E0E7FF); color: var(--accent-purple); }
        .stat-data h3 { font-size: 32px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-dark); line-height: 1;}
        .stat-data p { margin: 0; font-size: 13px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;}

        /* ====================================================================
           ANALYTICS & TABLES LAYOUT
           ==================================================================== */
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start; }

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
        .badge-verified { background: #D1FAE5; color: #059669; border: 1px solid #A7F3D0;}
        .badge-pending { background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A;}
        .badge-rejected { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA;}

        /* Specific Table Data Blocks */
        .student-info-block { display: flex; align-items: center; gap: 15px;}
        .student-avatar { width: 35px; height: 35px; border-radius: 8px; background: #E0E7FF; color: #4338CA; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;}
        
        .action-btns { display: flex; gap: 8px; align-items: center;}
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border-color); background: white; color: var(--secondary); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; padding: 0; text-decoration: none;}
        .btn-icon:hover { background: #F1F5F9; color: var(--text-dark); transform: translateY(-2px); box-shadow: var(--shadow-sm);}
        
        .btn-approve-action { background: #ECFDF5; color: #059669; border-color: #A7F3D0;}
        .btn-approve-action:hover { background: #10B981; color: white; border-color: #10B981; }
        
        .btn-reject-action { background: #FEF2F2; color: #EF4444; border-color: #FECACA;}
        .btn-reject-action:hover { background: #EF4444; color: white; border-color: #EF4444; }

        /* Chart Area */
        .chart-container { position: relative; height: 300px; width: 100%; margin-top: 20px;}

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-grid { grid-template-columns: 1fr; } 
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
            <a href="admin_student_reports.php"><i class="fas fa-users"></i> Global Students</a>
            <a href="admin_placements.php" class="active"><i class="fas fa-briefcase"></i> Placements</a>
            
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

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: var(--border-radius-md); font-weight: 600; border-left: 5px solid <?= $msg_type=='success'?'var(--accent-success)':'var(--accent-warning)' ?> !important;">
                    <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-warning' ?> me-2 fs-5"></i> 
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="hero-banner">
                <div class="hero-content">
                    <h1>Placement Verification Hub</h1>
                    <p>Track, audit, and formally verify successful job placements submitted by Training Partners to maintain accurate outcome records.</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue"><i class="fas fa-briefcase"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['total']) ?></h3>
                        <p>Total Submitted Records</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-teal"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['verified']) ?></h3>
                        <p>Verified Placements</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($stats['pending']) ?></h3>
                        <p>Pending Verification</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="fas fa-building"></i></div>
                    <div class="stat-data">
                        <h3 style="font-size: 18px; line-height: 1.2; margin-bottom: 6px;"><?= htmlspecialchars($stats['top_company']) ?></h3>
                        <p>Top Hiring Company</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                
                <div class="grid-left">
                    <div class="content-card">
                        <div class="card-header-flex">
                            <h5><i class="fas fa-list-alt text-primary me-2"></i> Placement Database</h5>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search student, company, or TP...">
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table" id="placementTable">
                                <thead>
                                    <tr>
                                        <th>Student Identity</th>
                                        <th>Originating Center</th>
                                        <th>Company & Role</th>
                                        <th>Salary & Date</th>
                                        <th>Status</th>
                                        <th>Action Panel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($all_placements)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fas fa-folder-open fs-2 mb-3 d-block opacity-50"></i>
                                                No placement records have been submitted yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($all_placements as $place): 
                                            $p_status = strtolower($place['status'] ?? 'pending');
                                            $p_class = 'badge-pending';
                                            $p_icon = 'fa-hourglass-half';
                                            
                                            if ($p_status === 'verified') { $p_class = 'badge-verified'; $p_icon = 'fa-check-circle'; }
                                            elseif ($p_status === 'rejected') { $p_class = 'badge-rejected'; $p_icon = 'fa-times-circle'; }
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="student-info-block">
                                                        <div class="student-avatar">
                                                            <?= strtoupper(substr($place['student_name'], 0, 2)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark fs-6" style="margin-bottom: 2px;"><?= htmlspecialchars($place['student_name']) ?></div>
                                                            <div style="font-size: 11px; color: var(--primary); font-weight: 700;"><i class="fas fa-graduation-cap me-1"></i> <?= htmlspecialchars($place['course_completed']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold" style="color: var(--secondary); font-size: 13px;">
                                                        <i class="fas fa-building me-1 text-muted"></i> <?= htmlspecialchars($place['institute_name'] ?? 'Unknown TP') ?>
                                                    </div>
                                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px; margin-left: 18px;">
                                                        <?= htmlspecialchars($place['tp_email']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark" style="font-size: 14px;"><?= htmlspecialchars($place['company_name']) ?></div>
                                                    <div style="font-size: 12px; color: var(--text-muted); font-weight: 500; margin-top: 2px;"><?= htmlspecialchars($place['designation']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold" style="color: #059669; font-size: 14px;">₹<?= htmlspecialchars($place['salary_package'] ?? 'N/A') ?></div>
                                                    <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; margin-top: 2px;"><i class="far fa-calendar-alt me-1"></i> Joined: <?= date('M Y', strtotime($place['placement_date'])) ?></div>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?= $p_class ?>"><i class="fas <?= $p_icon ?>"></i> <?= ucfirst($p_status) ?></span>
                                                </td>
                                                <td>
                                                    <div class="action-btns">
                                                        <?php if($place['status'] === 'Pending'): ?>
                                                            <form method="POST" action="" onsubmit="return confirm('Officially VERIFY this placement record?');" style="margin:0;">
                                                                <input type="hidden" name="placement_id" value="<?= $place['id'] ?>">
                                                                <input type="hidden" name="action" value="verify">
                                                                <button type="submit" class="btn-icon btn-approve-action" title="Verify Record"><i class="fas fa-check"></i></button>
                                                            </form>
                                                            <form method="POST" action="" onsubmit="return confirm('REJECT this placement record?');" style="margin:0;">
                                                                <input type="hidden" name="placement_id" value="<?= $place['id'] ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <button type="submit" class="btn-icon btn-reject-action" title="Reject Record"><i class="fas fa-times"></i></button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button class="btn-icon" title="View Verification Details" onclick="alert('Verification logged on <?= date('d M Y') ?>');"><i class="fas fa-eye"></i></button>
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
                </div>

                <div class="grid-right d-none d-xl-block">
                    <div class="content-card" style="position: sticky; top: 100px;">
                        <div class="card-header-flex border-bottom-0 pb-0 mb-3">
                            <h5 style="font-size: 16px;"><i class="fas fa-chart-pie text-primary"></i> Verification Pipeline</h5>
                        </div>
                        <p class="text-muted mb-4" style="font-size: 12px; line-height: 1.5;">Breakdown of placement submission statuses across the network.</p>
                        
                        <div class="chart-container">
                            <canvas id="placementStatusChart"></canvas>
                        </div>
                        
                        <div class="mt-4 pt-4 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 12px; font-weight: 700;">
                                <span><i class="fas fa-circle text-success me-2"></i> Verified Placements</span>
                                <span class="text-dark"><?= ($stats['total'] > 0) ? round(($stats['verified'] / $stats['total']) * 100) : 0 ?>%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 12px; font-weight: 700;">
                                <span><i class="fas fa-circle text-warning me-2"></i> Pending Review</span>
                                <span class="text-dark"><?= ($stats['total'] > 0) ? round(($stats['pending'] / $stats['total']) * 100) : 0 ?>%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="font-size: 12px; font-weight: 700;">
                                <span><i class="fas fa-circle text-danger me-2"></i> Rejected Records</span>
                                <span class="text-dark"><?= ($stats['total'] > 0) ? round(($stats['rejected'] / $stats['total']) * 100) : 0 ?>%</span>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded border border-dashed text-center">
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">Highest Salary Logged</div>
                            <div style="font-size: 24px; font-weight: 800; color: #059669; margin-top: 5px;">₹<?= number_format($stats['top_salary']) ?></div>
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
                    const rows = document.querySelectorAll('#placementTable tbody tr');
                    
                    rows.forEach(row => {
                        if(row.cells.length === 1) return; // Skip empty state row
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }

            // 3. CHART.JS: PLACEMENT STATUS DISTRIBUTION
            const ctxStatus = document.getElementById('placementStatusChart');
            if(ctxStatus) {
                const total = <?= $stats['total'] ?>;
                
                const data = {
                    labels: ['Verified', 'Pending', 'Rejected'],
                    datasets: [{
                        data: total > 0 ? [<?= $stats['verified'] ?>, <?= $stats['pending'] ?>, <?= $stats['rejected'] ?>] : [1],
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
                        cutout: '75%', 
                        plugins: {
                            legend: { display: false }, 
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