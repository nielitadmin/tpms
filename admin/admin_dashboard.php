<?php
// Start session and check admin authentication
session_name('NIELIT_TPMS');
session_start();

// NOTE: Uncomment this block when your login system is ready to enforce security
/*
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NIELIT TPMS</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #155E75;        
            --primary-light: #0284C7;  
            --primary-bg: #EFF6FF;     
            --candidate: #059669;      
            --tp: #0D9488;
            --text-dark: #0F172A;
            --text-muted: #475569;
            --bg-body: #F8FAFC;
            --sidebar-bg: #0F172A; /* Dark command center look */
            --border: #E2E8F0;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* --- SIDEBAR --- */
        #sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h4 { font-weight: 800; font-size: 20px; margin: 0; letter-spacing: 1px; color: #E0F2FE;}
        .sidebar-header span { font-size: 11px; color: #94A3B8; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px;}

        .sidebar-menu {
            padding: 20px 0;
            flex-grow: 1;
            overflow-y: auto;
        }

        .sidebar-menu a {
            padding: 14px 25px;
            display: flex;
            align-items: center;
            color: #CBD5E1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.05);
            color: #FFFFFF;
            border-left-color: var(--primary-light);
        }

        .sidebar-menu a i { width: 25px; font-size: 16px; color: #94A3B8; transition: 0.2s;}
        .sidebar-menu a:hover i, .sidebar-menu a.active i { color: var(--primary-light); }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .btn-logout {
            width: 100%; padding: 10px; background: rgba(239, 68, 68, 0.1); color: #F87171;
            border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; font-weight: 600;
            transition: 0.3s;
        }
        .btn-logout:hover { background: #EF4444; color: white; }

        /* --- MAIN CONTENT --- */
        #main-content {
            margin-left: 260px;
            transition: all 0.3s;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .menu-toggle { font-size: 24px; color: var(--text-dark); cursor: pointer; display: none; }
        
        .nav-profile { display: flex; align-items: center; gap: 15px; }
        .nav-profile img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--primary-bg); }
        .nav-profile .user-info { display: flex; flex-direction: column; }
        .nav-profile .user-info strong { font-size: 14px; font-weight: 700; color: var(--text-dark); }
        .nav-profile .user-info span { font-size: 12px; color: var(--text-muted); font-weight: 500; }

        /* Dashboard Content */
        .dashboard-container { padding: 30px; flex-grow: 1; }
        .page-title { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 5px; }
        .page-sub { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-bottom: 30px; }

        /* Stat Cards */
        .stat-card {
            background: white; border: 1px solid var(--border); border-radius: 16px;
            padding: 20px; display: flex; align-items: center; gap: 20px;
            box-shadow: var(--shadow-sm); transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-icon {
            width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;
        }
        .icon-blue { background: #E0F2FE; color: #0284C7; }
        .icon-green { background: #D1FAE5; color: #059669; }
        .icon-teal { background: #CCFBF1; color: #0D9488; }
        .icon-orange { background: #FFEDD5; color: #EA580C; }
        
        .stat-data h3 { font-size: 28px; font-weight: 800; margin: 0; color: var(--text-dark); line-height: 1; }
        .stat-data p { margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}

        /* Data Tables */
        .content-card {
            background: white; border: 1px solid var(--border); border-radius: 16px;
            box-shadow: var(--shadow-sm); padding: 25px; margin-top: 30px;
        }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header-flex h5 { font-weight: 800; font-size: 18px; margin: 0; }
        
        .table-custom { margin: 0; width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-custom th { font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; border-bottom: 2px solid var(--border); padding: 12px 15px; background: #F8FAFC; }
        .table-custom td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 14px; font-weight: 500; vertical-align: middle; }
        .table-custom tbody tr:hover { background-color: #F8FAFC; }
        .table-custom tbody tr:last-child td { border-bottom: none; }

        .badge-status { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; }
        .badge-active { background: #D1FAE5; color: #059669; }
        .badge-pending { background: #FEF3C7; color: #D97706; }

        .btn-action { width: 32px; height: 32px; border-radius: 8px; border: none; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; cursor: pointer; background: #F1F5F9; color: var(--text-muted);}
        .btn-action:hover { background: var(--primary-light); color: white; }

        /* Responsive */
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
            <span>Admin Console</span>
        </div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard Overview</a>
            <a href="#"><i class="fas fa-chalkboard-teacher"></i> Manage TP Centers</a>
            <a href="#"><i class="fas fa-users"></i> Student Records</a>
            <a href="#"><i class="fas fa-book"></i> Course Mapping</a>
            <a href="#"><i class="fas fa-bullhorn"></i> Public Notices</a>
            <a href="#"><i class="fas fa-chart-bar"></i> CBT Reports</a>
            <a href="#"><i class="fas fa-cog"></i> System Settings</a>
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
                <div class="input-group d-none d-md-flex" style="width: 300px;">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" placeholder="Search centers, students...">
                </div>
            </div>
            
            <div class="nav-profile">
                <div class="text-end d-none d-sm-block user-info">
                    <strong>Super Administrator</strong>
                    <span>NIELIT Bhubaneswar</span>
                </div>
                <div style="width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border: 2px solid var(--primary-bg);">
                    SA
                </div>
            </div>
        </header>

        <!-- Dashboard Widgets -->
        <div class="dashboard-container">
            <h1 class="page-title">Welcome back, Admin</h1>
            <p class="page-sub">Here is what's happening across your regional training partners today.</p>

            <div class="row g-4">
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-blue"><i class="fas fa-network-wired"></i></div>
                        <div class="stat-data">
                            <h3>54</h3>
                            <p>Active Centers</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-teal"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-data">
                            <h3>12,450</h3>
                            <p>Registered Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-orange"><i class="fas fa-clock"></i></div>
                        <div class="stat-data">
                            <h3>8</h3>
                            <p>Pending Approvals</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-data">
                            <h3>4,200</h3>
                            <p>CBT Completed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent TP Registrations Table -->
            <div class="content-card">
                <div class="card-header-flex">
                    <h5>Recent Center Registrations</h5>
                    <button class="btn btn-primary btn-sm fw-bold"><i class="fas fa-plus"></i> Add New Center</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Center Name</th>
                                <th>Location (State)</th>
                                <th>Contact Email</th>
                                <th>Date Applied</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong class="text-dark">TechVision Institute</strong></td>
                                <td>Bhubaneswar, Odisha</td>
                                <td>admin@techvision.in</td>
                                <td>Oct 12, 2023</td>
                                <td><span class="badge-status badge-active">Approved</span></td>
                                <td>
                                    <button class="btn-action"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong class="text-dark">Raipur Skill Academy</strong></td>
                                <td>Raipur, Chhattisgarh</td>
                                <td>contact@raipurskill.org</td>
                                <td>Oct 10, 2023</td>
                                <td><span class="badge-status badge-pending">Pending</span></td>
                                <td>
                                    <button class="btn-action"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong class="text-dark">Kalinga IT Center</strong></td>
                                <td>Cuttack, Odisha</td>
                                <td>info@kalingait.com</td>
                                <td>Oct 08, 2023</td>
                                <td><span class="badge-status badge-active">Approved</span></td>
                                <td>
                                    <button class="btn-action"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong class="text-dark">Bilaspur Cyber Hub</strong></td>
                                <td>Bilaspur, Chhattisgarh</td>
                                <td>support@bcyberhub.in</td>
                                <td>Oct 05, 2023</td>
                                <td><span class="badge-status badge-active">Approved</span></td>
                                <td>
                                    <button class="btn-action"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
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