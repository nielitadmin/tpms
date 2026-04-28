<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('admin'); // Restrict to Admin only

// Fetch Quick Stats
$total_tps = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='tp'")->fetch_assoc()['count'];
$pending_tps = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='tp' AND status='pending'")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_courses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];

// Added: Dynamically count notices
$total_notices_query = $conn->query("SELECT COUNT(*) as count FROM notices");
$total_notices = $total_notices_query ? $total_notices_query->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center - NIELIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* 1. Moving Light Theme Background */
        @keyframes moveGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        body {
            background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1);
            background-size: 400% 400%;
            animation: moveGradient 15s ease infinite;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #334155;
            min-height: 100vh;
        }

        /* 2. Glassmorphism Navbar */
        .navbar-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            background: linear-gradient(90deg, #1e293b, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* 3. 3D Floating Cards (Neumorphism + Glass) */
        .card-3d {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 1);
            border-radius: 20px;
            box-shadow: 
                10px 10px 20px rgba(166, 180, 200, 0.4), 
                -10px -10px 20px rgba(255, 255, 255, 0.9);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
        }

        .card-3d:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                15px 15px 25px rgba(166, 180, 200, 0.5), 
                -15px -15px 25px rgba(255, 255, 255, 1);
        }

        /* Animated decorative blobs inside cards */
        .card-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(30px);
            z-index: 0;
            opacity: 0.4;
            animation: float 6s ease-in-out infinite;
        }
        .blob-1 { top: -20px; right: -20px; width: 100px; height: 100px; background: #60a5fa; } /* Blue */
        .blob-2 { bottom: -20px; left: -20px; width: 80px; height: 80px; background: #34d399; animation-delay: 1s; } /* Green */
        .blob-3 { top: 40%; right: -10px; width: 90px; height: 90px; background: #fbbf24; animation-delay: 3s; } /* Yellow */
        .blob-4 { bottom: -10px; right: 20px; width: 85px; height: 85px; background: #f87171; animation-delay: 2s; } /* Red */

        @keyframes float {
            0% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-10px) scale(1.1); }
            100% { transform: translateY(0px) scale(1); }
        }

        /* 4. Action Buttons 3D */
        .btn-3d {
            border-radius: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.2s;
            box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8);
            border: none;
        }
        .btn-3d:active {
            transform: scale(0.95);
            box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8);
        }

        /* Action Cards Hover effect */
        .action-card { text-decoration: none; display: block; }
        .action-card .card-3d:hover i { transform: scale(1.2); transition: 0.3s; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Layout utilities */
        .icon-wrapper {
            width: 60px; height: 60px;
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; z-index: 1; position: relative;
            background: rgba(255,255,255,0.9);
            box-shadow: inset 2px 2px 5px rgba(255,255,255,1), inset -3px -3px 7px rgba(0,0,0,0.05), 3px 3px 6px rgba(0,0,0,0.05);
        }
        .content-wrapper { z-index: 1; position: relative; }

        /* Timeline */
        .timeline { border-left: 2px solid #e2e8f0; padding-left: 20px; position: relative; }
        .timeline-item { margin-bottom: 1.5rem; position: relative; }
        .timeline-item::before {
            content: ''; position: absolute; left: -26px; top: 4px;
            width: 12px; height: 12px; border-radius: 50%;
            background: #1e293b; border: 2px solid white;
            box-shadow: 0 0 0 3px rgba(30, 41, 59, 0.2);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4" href="#">
                <i class="fas fa-shield-alt text-dark me-2"></i> NIELIT Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <div class="d-flex align-items-center">
                    <div class="me-4 text-secondary fw-semibold">
                        <i class="fas fa-user-astronaut text-dark me-1"></i> <?= htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?>
                    </div>
                    <a href="../logout.php" class="btn btn-3d btn-dark text-white"><i class="fas fa-power-off me-1"></i> Secure Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card-3d p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="content-wrapper">
                        <h2 class="fw-bold text-dark mb-1">Master Control System</h2>
                        <p class="text-muted mb-0">Oversee all Training Partners, manage courses, and monitor state-wide data.</p>
                    </div>
                    <div class="content-wrapper d-flex gap-2">
                        <span class="badge bg-dark bg-opacity-10 text-dark border border-dark p-2 rounded-pill px-3">
                            <i class="fas fa-server me-1"></i> System Online
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if($pending_tps > 0): ?>
        <div class="row mb-4 animate__animated animate__fadeInDown">
            <div class="col-12">
                <div class="card-3d p-3 border-start border-warning border-5" style="background: rgba(255, 251, 235, 0.85);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="icon-wrapper text-warning me-3 shadow-none bg-white"><i class="fas fa-exclamation-triangle"></i></div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Action Required</h5>
                                <p class="text-muted mb-0 small">There are <strong><?= $pending_tps ?> pending TPS center registrations</strong> awaiting your approval.</p>
                            </div>
                        </div>
                        <a href="admin_manage_tp.php" class="btn btn-warning btn-3d px-4 text-dark fw-bold">Review Now</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>

</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4" href="#">
                <i class="fas fa-shield-alt text-dark me-2"></i> NIELIT Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <div class="d-flex align-items-center">
                    <div class="me-4 text-secondary fw-semibold">
                        <i class="fas fa-user-astronaut text-dark me-1"></i> <?= htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?>
                    </div>
                    <a href="../logout.php" class="btn btn-3d btn-dark text-white"><i class="fas fa-power-off me-1"></i> Secure Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <!-- Content unchanged -->
    </div>
</body>
</html>
