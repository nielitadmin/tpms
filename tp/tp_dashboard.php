<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp'); // Restrict to TPs only

// Simulating some feature data for the new UI elements
$courses_count = 0;
$students_count = 0;
$activities_count = 0;
$storage_used = 45; // Percentage
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NIELIT TPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* styles copied from original tp_dashboard.php */
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #334155; min-height: 100vh; }
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .navbar-brand { font-weight: 800; background: linear-gradient(90deg, #2563eb, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); overflow: hidden; position: relative; }
        .card-3d:hover { transform: translateY(-8px) scale(1.02); box-shadow: 15px 15px 25px rgba(166, 180, 200, 0.5), -15px -15px 25px rgba(255, 255, 255, 1); }
        .card-blob { position: absolute; border-radius: 50%; filter: blur(30px); z-index: 0; opacity: 0.4; animation: float 6s ease-in-out infinite; }
        .blob-1 { top: -20px; right: -20px; width: 100px; height: 100px; background: #93c5fd; }
        .blob-2 { bottom: -20px; left: -20px; width: 80px; height: 80px; background: #fca5a5; animation-delay: 2s; }
        .blob-3 { top: 40%; right: -10px; width: 90px; height: 90px; background: #fde047; animation-delay: 4s; }
        @keyframes float { 0% { transform: translateY(0px) scale(1); } 50% { transform: translateY(-10px) scale(1.1); } 100% { transform: translateY(0px) scale(1); } }
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: #f1f5f9; } ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; } ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .timeline { border-left: 2px solid #e2e8f0; padding-left: 20px; position: relative; } .timeline-item { margin-bottom: 1.5rem; position: relative; } .timeline-item::before { content: ''; position: absolute; left: -26px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: #3b82f6; border: 2px solid white; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .icon-wrapper { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; z-index: 1; position: relative; background: rgba(255,255,255,0.9); box-shadow: inset 2px 2px 5px rgba(255,255,255,1), inset -3px -3px 7px rgba(0,0,0,0.05), 3px 3px 6px rgba(0,0,0,0.05); }
        .content-wrapper { z-index: 1; position: relative; }
    </style>
</head>
<body>

    <div class="bg-primary text-white py-2 px-3 small d-flex align-items-center" style="background: linear-gradient(90deg, #1e3a8a, #3b82f6) !important;">
        <span class="badge bg-danger me-3 animate__animated animate__pulse animate__infinite">NEW</span>
        <marquee behavior="scroll" direction="left" scrollamount="5" class="m-0 fw-medium">
            Welcome to the new modernized NIELIT TPS Portal. Ensure all student records for the current session are uploaded via the Quick Actions panel before the 30th of this month.
        </marquee>
    </div>

    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4" href="#">
                <i class="fas fa-layer-group text-primary me-2"></i> NIELIT TPS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="topNav">
                <div class="d-flex align-items-center">
                    <div class="me-4 text-secondary fw-semibold">
                        <i class="fas fa-user-shield text-primary me-1"></i> Hello, <?= htmlspecialchars($_SESSION['name'] ?? 'Partner'); ?>
                    </div>
                    <a href="../logout.php" class="btn btn-3d btn-light text-danger"><i class="fas fa-power-off me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <!-- Content unchanged -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
