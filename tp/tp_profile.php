<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp');

$tp_id = $_SESSION['user_id'];
$message = '';

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $gps_link = $conn->real_escape_string($_POST['gps_link']);

    $update_sql = "UPDATE users SET name='$name', phone='$phone', address='$address', gps_link='$gps_link' WHERE id=$tp_id";
    
    if ($conn->query($update_sql)) {
        $_SESSION['name'] = $name; // Update session variable
        $message = "Profile updated successfully!";
    } else {
        $message = "Error updating profile: " . $conn->error;
    }
}

// Fetch current user data
$user_data = $conn->query("SELECT * FROM users WHERE id=$tp_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - TP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { background: #f8fafc; } </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="tp_dashboard.php">NIELIT TPS Portal</a>
            <div class="d-flex text-white align-items-center">
                <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Center Profile</h2>
                    <a href="tp_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>

                <?php if($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Center ID (Unchangeable)</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user_data['center_id']) ?>" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email (Unchangeable)</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user_data['email']) ?>" readonly disabled>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Institute Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user_data['name']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user_data['phone']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Physical Address</label>
                                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Google Maps Link (Optional)</label>
                                <input type="url" name="gps_link" class="form-control" value="<?= htmlspecialchars($user_data['gps_link'] ?? '') ?>" placeholder="https://maps.google.com/...">
                                <small class="text-muted">Paste the shareable link from Google Maps here.</small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Save Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
