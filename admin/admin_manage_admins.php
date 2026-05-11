<?php
/**
 * ============================================================================
 * NIELIT TPMS - ADMINISTRATOR ACCESS MANAGEMENT
 * ============================================================================
 * File: admin_manage_admins.php
 * Description: High-security portal for the Super Admin to grant, monitor, 
 * and revoke access for other regional or system administrators.
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
// 2. HANDLE POST REQUESTS (ADD & REVOKE ADMINS)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // A. Grant New Admin Access
    if ($_POST['action'] === 'add_admin') {
        $admin_name = trim($_POST['admin_name']);
        $admin_email = trim($_POST['admin_email']);
        $admin_password = $_POST['admin_password'];

        if (!empty($admin_email) && !empty($admin_password)) {
            // 1. Check for Duplicate Email
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            if ($check_stmt) {
                $check_stmt->bind_param("s", $admin_email);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();

                if ($check_res->num_rows > 0) {
                    $message = "Conflict: A user with this email address already exists in the system.";
                    $msg_type = "warning";
                } else {
                    // 2. Hash Password Securely
                    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                    $role = 'admin';
                    $status = 'active';

                    // 3. Insert new admin
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ssssss", $admin_name, $admin_email, $hashed_password, $role, $status, $timestamp_now);
                        if ($stmt->execute()) {
                            $message = "Success! Administrative privileges have been granted to " . htmlspecialchars($admin_name) . ".";
                            $msg_type = "success";
                        } else {
                            $message = "Database Error: Could not provision new admin account.";
                            $msg_type = "danger";
                        }
                        $stmt->close();
                    } else {
                        $db_errors[] = "Insert Prepare Error: " . $conn->error;
                    }
                }
                $check_stmt->close();
            } else {
                $db_errors[] = "Check Prepare Error: " . $conn->error;
            }
        } else {
            $message = "Validation Error: Official Email and a Secure Password are required.";
            $msg_type = "danger";
        }
    }

    // B. Revoke Admin Access
    elseif ($_POST['action'] === 'delete_admin') {
        $delete_id = (int)$_POST['admin_id'];
        
        // Failsafe: Prevent the admin from deleting their own active session
        if ($delete_id === $_SESSION['user_id']) {
            $message = "Security Protocol: You cannot revoke your own administrative access while logged in.";
            $msg_type = "danger";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
            if ($stmt) {
                $stmt->bind_param("i", $delete_id);
                if ($stmt->execute()) {
                    $message = "Administrative access successfully revoked and account permanently removed.";
                    $msg_type = "success";
                } else {
                    $message = "Database Error: Could not revoke access.";
                    $msg_type = "danger";
                }
                $stmt->close();
            } else {
                $db_errors[] = "Delete Prepare Error: " . $conn->error;
            }
        }
    }
}

// ============================================================================
// 3. FETCH ALL ADMINISTRATORS
// ============================================================================
$all_admins = [];
$query = "SELECT id, name, email, status, created_at FROM users WHERE role = 'admin' ORDER BY id ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_admins[] = $row;
    }
} else {
    $db_errors[] = "Fetch Admins Error: " . $conn->error;
}

$total_admins = count($all_admins);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Administrators - NIELIT TPMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 35px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 25px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-sm); transition: transform var(--transition-speed); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md);}
        .stat-icon { width: 65px; height: 65px; border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; }
        .icon-blue { background: linear-gradient(135deg, #EFF6FF, #DBEAFE); color: var(--primary); }
        .icon-teal { background: linear-gradient(135deg, #ECFDF5, #D1FAE5); color: var(--accent-success); }
        .icon-purple { background: linear-gradient(135deg, #F5F3FF, #E0E7FF); color: var(--accent-purple); }
        .stat-data h3 { font-size: 32px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-dark); line-height: 1;}
        .stat-data p { margin: 0; font-size: 13px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;}

        /* ====================================================================
           DATA TABLES
           ==================================================================== */
        .content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); box-shadow: var(--shadow-sm); padding: 30px; margin-bottom: 24px; overflow: hidden; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #F1F5F9;}
        .card-header-flex h5 { font-weight: 800; font-size: 18px; margin: 0; color: var(--text-dark); display: flex; align-items: center; gap: 10px;}

        .table-responsive { overflow-x: auto; margin: 0 -30px; padding: 0 30px;}
        .table { margin: 0; width: 100%; border-collapse: collapse; min-width: 900px; }
        .table th { padding: 15px 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; border-bottom: 2px solid var(--border-color); text-align: left; background: #F8FAFC;}
        .table td { padding: 18px 20px; font-size: 14px; font-weight: 600; color: var(--text-dark); border-bottom: 1px dashed #E2E8F0; vertical-align: middle; }
        .table tbody tr:hover { background-color: #F8FAFC; }

        .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-active { background: #D1FAE5; color: #059669; border: 1px solid #A7F3D0;}

        /* Current User Badge */
        .current-user-badge { font-size: 11px; font-weight: 800; color: var(--primary); background: var(--primary-light); padding: 6px 12px; border-radius: 6px; border: 1px solid #BFDBFE; display: inline-flex; align-items: center; gap: 6px;}

        .action-btns { display: flex; gap: 8px; align-items: center;}
        .btn-icon-danger { width: 35px; height: 35px; border-radius: 8px; border: 1px solid #FECACA; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; padding: 0; text-decoration: none;}
        .btn-icon-danger:hover { background: #EF4444; color: white; border-color: #EF4444; transform: translateY(-2px); box-shadow: var(--shadow-sm);}

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
            <a href="admin_courses.php"><i class="fas fa-certificate"></i> Course & Approvals</a>
            
            <div class="sidebar-menu-category mt-4">Academic Records</div>
            <a href="admin_student_reports.php"><i class="fas fa-users"></i> Global Students</a>
            <a href="admin_placements.php"><i class="fas fa-briefcase"></i> Placements</a>
            
            <div class="sidebar-menu-category mt-4">Administration</div>
            <a href="admin_upload_notice.php"><i class="fas fa-bullhorn"></i> Push Notices</a>
            <a href="admin_helpdesk_upload.php"><i class="fas fa-headset"></i> Support Tickets</a>
            <a href="admin_manage_admins.php" class="active"><i class="fas fa-user-shield"></i> Manage Admins</a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="btn-logout"><i class="fas fa-power-off"></i> Secure Logout</a>
        </div>
    </aside>

    <main id="main-content">
        
        <header class="top-navbar">
            <div class="top-nav-left">
                <button class="mobile-toggle-btn" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <div class="search-bar d-none d-lg-flex">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Search administrators by name or email...">
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
                    <h1>Administrator Access Management</h1>
                    <p>Provision new administrative accounts, oversee system roles, and revoke access to the master system ecosystem.</p>
                </div>
                <div class="hero-actions">
                    <button type="button" class="btn-glow" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="fas fa-user-plus"></i> Grant Admin Access
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue"><i class="fas fa-users-cog"></i></div>
                    <div class="stat-data">
                        <h3><?= number_format($total_admins) ?></h3>
                        <p>Total Administrators</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-purple"><i class="fas fa-shield-check"></i></div>
                    <div class="stat-data">
                        <h3>Master</h3>
                        <p>System Authority Level</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-teal"><i class="fas fa-lock"></i></div>
                    <div class="stat-data">
                        <h3>Protected</h3>
                        <p>Security Status</p>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header-flex">
                    <h5><i class="fas fa-server text-primary"></i> Registered System Administrators</h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table" id="adminTable">
                        <thead>
                            <tr>
                                <th style="width: 15%;">System ID</th>
                                <th style="width: 25%;">Admin Name</th>
                                <th style="width: 25%;">Email Address</th>
                                <th style="width: 15%;">Date Provisioned</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_admins)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-users-slash fs-2 mb-3 opacity-50 d-block"></i>
                                        No administrators found in the database.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_admins as $admin): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold" style="font-family: monospace; color: var(--primary);">SYS-<?= str_pad($admin['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($admin['name'] ?? 'Super Admin') ?></div>
                                        </td>
                                        <td>
                                            <div class="text-muted fw-bold" style="font-size: 13px;"><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($admin['email']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold" style="color: var(--secondary); font-size: 13px;">
                                                <?= isset($admin['created_at']) && !empty($admin['created_at']) ? date('d M Y', strtotime($admin['created_at'])) : 'System Default' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge badge-active"><i class="fas fa-check-circle"></i> Active</span>
                                        </td>
                                        <td>
                                            <?php if ($admin['id'] !== $_SESSION['user_id']): ?>
                                                <form method="POST" action="" onsubmit="return confirm('WARNING: Are you sure you want to completely REVOKE access for this administrator? This action cannot be undone.');" style="margin:0;">
                                                    <input type="hidden" name="action" value="delete_admin">
                                                    <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                                    <button type="submit" class="btn-icon-danger" title="Revoke Access">
                                                        <i class="fas fa-user-times"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="current-user-badge" title="This is your active session account.">
                                                    <i class="fas fa-user"></i> Yours
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-shield text-primary"></i> Grant System Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="addAdminForm">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="action" value="add_admin">
                        
                        <div class="p-4 bg-white rounded border shadow-sm mb-4">
                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Administrator Details</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Admin Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="admin_name" class="form-control bg-light" required placeholder="e.g., Jane Doe">
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Official Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" name="admin_email" class="form-control bg-light" required placeholder="admin@nielit.gov.in">
                                </div>
                                <small class="text-muted mt-2 d-block" style="font-size: 11px;">
                                    This email will be used as the login ID for the administrator.
                                </small>
                            </div>
                        </div>

                        <div class="p-4 bg-white rounded border shadow-sm">
                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Security Credentials</h6>
                            <div class="mb-0">
                                <label class="form-label">Initial Secure Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-key text-muted"></i></span>
                                    <input type="text" name="admin_password" class="form-control bg-light" required placeholder="Assign a strong, complex password">
                                </div>
                                <small class="text-muted mt-2 d-block" style="font-size: 11px;">
                                    <i class="fas fa-shield-alt text-success me-1"></i> This password will be heavily encrypted using BCRYPT before being saved to the database.
                                </small>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="submitAdminBtn">Provision Account</button>
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
            const form = document.getElementById('addAdminForm');
            if(form) {
                form.addEventListener('submit', function() {
                    const btn = document.getElementById('submitAdminBtn');
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Provisioning...';
                    btn.classList.add('disabled');
                });
            }

            // 3. GLOBAL SEARCH LOGIC (Filters the administrator table)
            const searchInput = document.getElementById('globalSearch');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#adminTable tbody tr');
                    
                    rows.forEach(row => {
                        if(row.cells.length === 1) return; // Skip empty state row
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>