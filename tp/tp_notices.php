<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp');

// Fetch all notices
$sql = "SELECT * FROM notices ORDER BY created_at DESC";
$notices = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Notices - TP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* styles copied from original tp_notices.php */
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', sans-serif; color: #334155; min-height: 100vh; }
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .navbar-brand { font-weight: 800; background: linear-gradient(90deg, #2563eb, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 15px; box-shadow: 5px 5px 15px rgba(166, 180, 200, 0.4), -5px -5px 15px rgba(255, 255, 255, 0.9); transition: all 0.3s; }
        .card-3d:hover { transform: translateY(-3px); box-shadow: 8px 8px 20px rgba(166, 180, 200, 0.5), -8px -8px 20px rgba(255, 255, 255, 1); }
        .btn-3d { border-radius: 10px; font-weight: 600; box-shadow: 3px 3px 8px rgba(0,0,0,0.1), -3px -3px 8px rgba(255,255,255,0.8); border: none; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4" href="tp_dashboard.php">
                <i class="fas fa-layer-group text-primary me-2"></i> NIELIT TPS
            </a>
            <div class="d-flex align-items-center">
                <a href="tp_dashboard.php" class="btn btn-3d btn-light text-primary me-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                <a href="../logout.php" class="btn btn-3d btn-light text-danger"><i class="fas fa-power-off"></i></a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-dark border-opacity-10 pb-3">
                    <h2 class="fw-bold text-dark m-0"><i class="fas fa-bullhorn text-danger me-2"></i> Official Notice Board</h2>
                </div>

                <div class="d-flex flex-column gap-3">
                    <?php while($row = $notices->fetch_assoc()): ?>
                        <div class="card-3d p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-start border-danger border-4">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['title']) ?></h5>
                                <p class="text-muted small mb-2"><i class="fas fa-calendar-alt me-1"></i> Published: <?= date('d F Y', strtotime($row['created_at'])) ?></p>
                                <?php if(!empty($row['description'])): ?>
                                    <p class="mb-0 text-secondary" style="font-size: 0.9rem;"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3 mt-md-0 text-md-end shrink-0">
                                <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-danger btn-3d px-4 py-2">
                                    <i class="fas fa-file-pdf me-2"></i> View PDF
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <?php if($notices->num_rows == 0): ?>
                        <div class="card-3d p-5 text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                            <h5>No active notices at this time.</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
