<?php
// Start session and check TP authentication
session_name('NIELIT_TPMS');
session_start();

// 1. SECURITY: Enforce Training Partner Access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tp') {
    header("Location: ../login.php");
    exit();
}

// 2. DATABASE CONNECTION
require_once '../includes/config.php'; // Ensure this points to your MySQLi config.php

// 3. FETCH REAL DATA FOR THIS SPECIFIC TP
$userEmail = $_SESSION['user_email'];
$centerDetails = null;

// Fetch Center Status based on the logged-in user's email
$stmt = $conn->prepare("SELECT * FROM centers WHERE contact_email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $centerDetails = $result->fetch_assoc();
    }
    $stmt->close();
}

// Initialize real counters
$studentCount = 0;
$batchCount = 0;
$courseCount = 0;
$recentBatches = [];

// Fetch Total Students
$resStudents = $conn->query("SELECT COUNT(*) as count FROM students");
if ($resStudents) {
    $studentCount = $resStudents->fetch_assoc()['count'];
}

// Fetch Total Batches
$resBatches = $conn->query("SELECT COUNT(*) as count FROM tp_batches");
if ($resBatches) {
    $batchCount = $resBatches->fetch_assoc()['count'];
}

// Fetch Active Courses
$resCourses = $conn->query("SELECT COUNT(*) as count FROM courses");
if ($resCourses) {
    $courseCount = $resCourses->fetch_assoc()['count'];
}

// Fetch Recent Batches for the Data Table
$resRecent = $conn->query("SELECT * FROM tp_batches ORDER BY id DESC LIMIT 5");
if ($resRecent) {
    while ($row = $resRecent->fetch_assoc()) {
        $recentBatches[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Partner Dashboard - NIELIT TPMS</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0D9488;        /* TP Theme Color (Teal) */
            --primary-light: #14B8A6;  
            --primary-bg: #CCFBF1;     
            --secondary: #155E75;      /* NIELIT Blue */
            --text-dark: #0F172A;
            --text-muted: #475569;
            --bg-body: #F8FAFC;
            --sidebar-bg: #FFFFFF;     /* Light sidebar for TP */
            --border: #E2E8F0;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-dark); overflow-x: hidden; }

        /* --- SIDEBAR (Light Theme for TP) --- */
        #sidebar { width: 260px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border); position: fixed; top: 0; left: 0; height: 100vh; z-index: 1000; transition: all 0.3s; display: flex; flex-direction: column; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid var(--border); }
        .sidebar-header h4 { font-weight: 800; font-size: 20px; margin: 0; color: var(--secondary); letter-spacing: 0.5px;}
        .sidebar-header span { font-size: 11px; color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;}
        
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .sidebar-menu a { padding: 14px 25px; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; font-size: 14.5px; font-weight: 600; transition: 0.2s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--primary-bg); color: var(--primary); border-left-color: var(--primary); }
        .sidebar-menu a i { width: 25px; font-size: 16px; color: #94A3B8; transition: 0.2s;}
        .sidebar-menu a:hover i, .sidebar-menu a.active i { color: var(--primary); }
        
        .sidebar-footer { padding: 20px; border-top: 1px solid var(--border); }
        .btn-logout { width: 100%; padding: 10px; background: rgba(239, 68, 68, 0.1); color: #F87171; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; font-weight: 600; transition: 0.3s; }
        .btn-logout:hover { background: #EF4444; color: white; }

        /* --- MAIN CONTENT --- */
        #main-content { margin-left: 260px; transition: all 0.3s; min-height: 100vh; display: flex; flex-direction: column; }
        .top-navbar { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 999; }
        .menu-toggle { font-size: 24px; color: var(--text-dark); cursor: pointer; display: none; }
        
        .nav-profile { display: flex; align-items: center; gap: 15px; }
        .nav-profile .user-info { display: flex; flex-direction: column; text-align: right; }
        .nav-profile .user-info strong { font-size: 14px; font-weight: 700; color: var(--text-dark); }
        .nav-profile .user-info span { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .profile-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border: 2px solid var(--primary-bg); }

        .dashboard-container { padding: 30px; flex-grow: 1; }
        .page-title { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 5px; }
        .page-sub { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-bottom: 30px; }

        /* Status Alert */
        .status-alert { padding: 15px 20px; border-radius: 12px; font-weight: 600; font-size: 14px; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .alert-pending { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
        .alert-approved { background: #D1FAE5; color: #047857; border: 1px solid #A7F3D0; }
        .alert-rejected { background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; }
        .alert-missing { background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; }

        /* Stat Cards */
        .stat-card { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-sm); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
        .icon-blue { background: #E0F2FE; color: #0284C7; }
        .icon-green { background: #D1FAE5; color: #059669; }
        .icon-teal { background: #CCFBF1; color: #0D9488; }
        .icon-purple { background: #F3E8FF; color: #7E22CE; }
        
        .stat-data h3 { font-size: 28px; font-weight: 800; margin: 0; color: var(--text-dark); line-height: 1; }
        .stat-data p { margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}

        /* Data Tables */
        .content-card { background: white; border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-sm); padding: 25px; margin-top: 30px; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header-flex h5 { font-weight: 800; font-size: 18px; margin: 0; color: var(--secondary);}
        
        .table-custom { margin: 0; width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-custom th { font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; border-bottom: 2px solid var(--border); padding: 12px 15px; background: #F8FAFC; }
        .table-custom td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 14px; font-weight: 500; vertical-align: middle; }
        .table-custom tbody tr:hover { background-color: #F8FAFC; }
        .table-custom tbody tr:last-child td { border-bottom: none; }

        .btn-action { padding: 8px 16px; border-radius: 8px; border: none; font-size: 13px; font-weight: 700; transition: 0.2s; cursor: pointer; background: var(--primary-bg); color: var(--primary);}
        .btn-action:hover { background: var(--primary); color: white; }

        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.active { transform: translateX(0); }
            #main-content { margin-left: 0; }
            .menu-toggle { display: block; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside id="sidebar">
        <div class="sidebar-header">
            <h4>NIELIT TPS</h4>
            <span>Partner Portal</span>
        </div>
        <div class="sidebar-menu">
            <a href="tp_dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="#"><i class="fas fa-building"></i> Center Profile</a>
            <a href="#"><i class="fas fa-users"></i> My Students</a>
            <a href="#"><i class="fas fa-layer-group"></i> Manage Batches</a>
            <a href="#"><i class="fas fa-file-invoice"></i> CBT Records</a>
            <a href="#"><i class="fas fa-bell"></i> Notices</a>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" class="btn btn-logout text-center text-decoration-none">
                <i class="fas fa-sign-out-alt"></i> Secure Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main id="main-content">
        
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-bars menu-toggle" id="menu-toggle"></i>
            </div>
            
            <div class="nav-profile">
                <div class="user-info d-none d-sm-block">
                    <strong><?= htmlspecialchars($centerDetails ? $centerDetails['center_name'] : 'New Training Partner') ?></strong>
                    <span><?= htmlspecialchars($_SESSION['user_email']) ?></span>
                </div>
                <div class="profile-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
        </header>

        <!-- Dashboard Widgets -->
        <div class="dashboard-container">
            <h1 class="page-title">Center Overview</h1>
            <p class="page-sub">Manage your students, track batches, and view real-time data.</p>

            <!-- Dynamic Center Status Banner -->
            <?php if (!$centerDetails): ?>
                <div class="status-alert alert-missing">
                    <i class="fas fa-info-circle fs-5"></i> 
                    Your center profile is incomplete. Please submit your center details for approval to unlock all features.
                </div>
            <?php elseif ($centerDetails['status'] === 'Pending'): ?>
                <div class="status-alert alert-pending">
                    <i class="fas fa-clock fs-5"></i> 
                    Your center registration is currently <strong>Under Review</strong> by NIELIT Administration.
                </div>
            <?php elseif ($centerDetails['status'] === 'Approved'): ?>
                <div class="status-alert alert-approved">
                    <i class="fas fa-check-circle fs-5"></i> 
                    Your center is <strong>Approved and Active</strong>. You may now upload student batches.
                </div>
            <?php elseif ($centerDetails['status'] === 'Rejected'): ?>
                <div class="status-alert alert-rejected">
                    <i class="fas fa-times-circle fs-5"></i> 
                    Your center registration was <strong>Rejected</strong>. Please check notices or contact administration.
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-teal"><i class="fas fa-users"></i></div>
                        <div class="stat-data">
                            <h3><?= number_format($studentCount) ?></h3>
                            <p>Enrolled Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-blue"><i class="fas fa-layer-group"></i></div>
                        <div class="stat-data">
                            <h3><?= number_format($batchCount) ?></h3>
                            <p>Active Batches</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-purple"><i class="fas fa-book"></i></div>
                        <div class="stat-data">
                            <h3><?= number_format($courseCount) ?></h3>
                            <p>Courses Available</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-green"><i class="fas fa-award"></i></div>
                        <div class="stat-data">
                            <h3>0</h3>
                            <p>CBT Passed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real Database Table: Recent Batches -->
            <div class="content-card">
                <div class="card-header-flex">
                    <h5>Recent Batches</h5>
                    <button class="btn btn-action"><i class="fas fa-plus"></i> Create Batch</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Batch Name / Code</th>
                                <th>Course Reference</th>
                                <th>Created Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentBatches)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No batches found in the database.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentBatches as $batch): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($batch['id'] ?? 'N/A') ?></td>
                                        
                                        <!-- Fallback to different column names depending on your exact schema -->
                                        <td><strong><?= htmlspecialchars($batch['batch_name'] ?? $batch['batch_code'] ?? 'Unnamed Batch') ?></strong></td>
                                        
                                        <td><?= htmlspecialchars($batch['course_id'] ?? $batch['course'] ?? 'N/A') ?></td>
                                        
                                        <td>
                                            <?php 
                                            // Safely format date if created_at column exists
                                            echo isset($batch['created_at']) ? date('M d, Y', strtotime($batch['created_at'])) : 'N/A'; 
                                            ?>
                                        </td>
                                        
                                        <td><button class="btn-action">View</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle Logic for Mobile Devices
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Close sidebar if clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menu-toggle');
            
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>