<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp');

$videos = $conn->query("SELECT * FROM helpdesk_videos ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk - TP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; min-height: 100vh; }
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s; overflow: hidden; }
        .card-3d:hover { transform: translateY(-5px); }
        .btn-3d { border-radius: 12px; font-weight: 600; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4 fw-bold text-primary" href="tp_dashboard.php">
                <i class="fas fa-layer-group me-2"></i> NIELIT TPS
            </a>
            <div class="d-flex align-items-center">
                <div class="me-3 text-secondary fw-semibold d-none d-md-block">
                    <i class="fas fa-user-shield text-primary me-1"></i> <?= htmlspecialchars($_SESSION['name'] ?? 'Partner'); ?>
                </div>
                <a href="../logout.php" class="btn btn-3d btn-light text-danger"><i class="fas fa-power-off me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-chalkboard-teacher text-primary me-2"></i> Portal Helpdesk & Tutorials</h2>
                <p class="text-muted mb-0">Video guides to help you navigate the TPS portal.</p>
            </div>
            <a href="tp_dashboard.php" class="btn btn-3d btn-light text-primary px-4 py-2 border">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>

        <div class="row g-4">
            <?php while($v = $videos->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card-3d h-100 d-flex flex-column">
                        <div class="p-4 flex-grow-1">
                            <h5 class="fw-bold text-primary mb-2"><?= htmlspecialchars($v['title']) ?></h5>
                            <p class="text-muted small"><?= nl2br(htmlspecialchars($v['description'])) ?></p>
                        </div>
                        <div class="p-3 pt-0">
                            <a href="<?= htmlspecialchars($v['video_url']) ?>" target="_blank" class="btn btn-outline-danger btn-3d w-100 fw-bold">
                                <i class="fas fa-play me-2"></i> Watch Tutorial
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if($videos->num_rows == 0): ?>
                <div class="col-12">
                    <div class="card-3d p-5 text-center text-muted">
                        <i class="fas fa-film fa-3x mb-3 opacity-25"></i>
                        <h5 class="fw-bold">No tutorials available right now.</h5>
                        <p>Contact Admin if you need assistance.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
