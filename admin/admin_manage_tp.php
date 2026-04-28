<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('admin');

$message = '';

// Handle Status Update (Approve/Deactivate)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['tp_id'])) {
    $tp_id = (int)$_POST['tp_id'];
    $new_status = ($_POST['action'] == 'approve') ? 'active' : 'inactive';
    
    $update_sql = "UPDATE users SET status = '$new_status' WHERE id = $tp_id AND role = 'tp'";
    if ($conn->query($update_sql)) {
        $message = "Center status updated to " . ucfirst($new_status) . ".";
    } else {
        $message = "Error updating status: " . $conn->error;
    }
}

// Fetch all TPs
$tps = $conn->query("SELECT id, center_id, name, email, phone, status, created_at FROM users WHERE role = 'tp' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Training Partners - Admin Command</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Moving Light Theme */
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; min-height: 100vh; }

        /* Glass Navbar */
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .navbar-brand { font-weight: 800; background: linear-gradient(90deg, #1e293b, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        /* 3D Glass Cards */
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s; overflow: hidden; }
        
        /* Action Buttons 3D */
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }

        /* Table Styling */
        .table-glass { --bs-table-bg: transparent; --bs-table-hover-bg: rgba(241, 245, 249, 0.6); margin-bottom: 0; }
        .table-glass thead th { background: #1e293b; color: white; border: none; padding: 15px; font-weight: 600; letter-spacing: 0.5px; }
        .table-glass tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .table-glass tbody tr:last-child td { border-bottom: none; }

        /* Status Badges */
        .status-badge { font-weight: 700; padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; border: 1px solid transparent; display: inline-block; }
        .status-pending { background: rgba(251, 191, 36, 0.2); color: #d97706; border-color: rgba(251, 191, 36, 0.5); }
        .status-active { background: rgba(52, 211, 153, 0.2); color: #059669; border-color: rgba(52, 211, 153, 0.5); }
        .status-inactive { background: rgba(248, 113, 113, 0.2); color: #dc2626; border-color: rgba(248, 113, 113, 0.5); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4" href="admin_dashboard.php">
                <i class="fas fa-shield-alt text-dark me-2"></i> NIELIT Admin
            </a>
            <div class="d-flex align-items-center">
                <div class="me-4 text-secondary fw-semibold d-none d-md-block">
                    <i class="fas fa-user-astronaut text-dark me-1"></i> <?= htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?>
                </div>
                <a href="../logout.php" class="btn btn-3d btn-dark text-white"><i class="fas fa-power-off me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-network-wired text-primary me-2"></i> Manage TPS Centers</h2>
                <p class="text-muted mb-0">Approve new registrations and manage existing training partner statuses.</p>
            </div>
            <div>
                <a href="admin_dashboard.php" class="btn btn-3d btn-light text-primary px-4 py-2 border">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success card-3d border-success border-start border-5 mb-4 p-3 d-flex align-items-center">
                <i class="fas fa-check-circle fs-4 text-success me-3"></i>
                <div class="fw-bold"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="card-3d p-2 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover table-glass align-middle">
                    <thead>
                        <tr>
                            <th style="border-top-left-radius: 12px;">Center ID</th>
                            <th>Institute Name</th>
                            <th>Contact Details</th>
                            <th>System Status</th>
                            <th>Registration Date</th>
                            <th style="border-top-right-radius: 12px;">Quick Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $tps->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark bg-opacity-10 text-dark border border-dark p-2 rounded px-3 fs-6">
                                    <i class="fas fa-id-badge text-muted me-1"></i> <?= htmlspecialchars($row['center_id']) ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                            <td>
                                <div class="text-primary fw-medium"><i class="fas fa-envelope me-1 opacity-75"></i> <?= htmlspecialchars($row['email']) ?></div>
                                <div class="text-muted small mt-1"><i class="fas fa-phone-alt me-1 opacity-75"></i> <?= htmlspecialchars($row['phone']) ?></div>
                            </td>
                            <td>
                                <?php if($row['status'] == 'pending'): ?>
                                    <span class="status-badge status-pending"><i class="fas fa-hourglass-half me-1"></i> Pending</span>
                                <?php elseif($row['status'] == 'active'): ?>
                                    <span class="status-badge status-active"><i class="fas fa-check-circle me-1"></i> Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive"><i class="fas fa-ban me-1"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted fw-medium"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <form method="POST" action="" class="d-inline m-0">
                                    <input type="hidden" name="tp_id" value="<?= $row['id'] ?>">
                                    <?php if($row['status'] == 'pending' || $row['status'] == 'inactive'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success btn-3d px-3 py-2 text-uppercase" style="font-size: 0.75rem;">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-danger btn-3d px-3 py-2 text-uppercase" style="font-size: 0.75rem;">
                                            <i class="fas fa-times me-1"></i> Deactivate
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if($tps->num_rows == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-folder-open fa-3x text-muted opacity-50 mb-3"></i>
                                    <h5 class="text-muted fw-bold">No TPS centers registered yet.</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
