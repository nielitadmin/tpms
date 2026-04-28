<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp');

$tp_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle Image & Activity Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['activity_image'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $target_dir = "uploads/activities/";
    
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["activity_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = 'act_' . time() . '_' . rand(100, 999) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($file_extension, $allowed_types)) {
        $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
        $messageType = "danger";
    } elseif ($_FILES["activity_image"]["size"] > 3000000) { // 3MB Limit
        $message = "Image size should not exceed 3MB.";
        $messageType = "danger";
    } else {
        if (move_uploaded_file($_FILES["activity_image"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO activities (tp_id, title, description, image_path) VALUES ('$tp_id', '$title', '$description', '$target_file')";
            if ($conn->query($sql)) {
                $message = "Activity uploaded successfully!";
                $messageType = "success";
            } else {
                $message = "Database Error: " . $conn->error;
                $messageType = "danger";
            }
        } else {
            $message = "Error uploading image to server.";
            $messageType = "danger";
        }
    }
}

// Fetch activities for this TP
$activities = $conn->query("SELECT * FROM activities WHERE tp_id = $tp_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Activities - TP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #f8fafc; }
        .card-img-top { height: 200px; object-fit: cover; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="tp_dashboard.php">NIELIT TPS Portal</a>
            <div class="d-flex text-white align-items-center">
                <a href="tp_dashboard.php" class="btn btn-3d btn-light text-primary me-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Center Activities & Placements</h2>
            <a href="tp_dashboard.php" class="btn btn-secondary">Back</a>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">Log New Activity</div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label>Activity Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g., Campus Placement Drive">
                            </div>
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="4" required placeholder="Details about the event..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Upload Photo</label>
                                <input type="file" name="activity_image" class="form-control" accept="image/*" required>
                                <small class="text-muted">Max size: 3MB (JPG, PNG)</small>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Upload Activity</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="row g-3">
                    <?php while($act = $activities->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm">
                                <img src="<?= htmlspecialchars($act['image_path']) ?>" class="card-img-top" alt="Activity Image">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($act['title']) ?></h5>
                                    <p class="card-text small text-muted"><?= nl2br(htmlspecialchars($act['description'])) ?></p>
                                </div>
                                <div class="card-footer bg-white border-0 text-muted small">
                                    Uploaded: <?= date('d M Y', strtotime($act['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if($activities->num_rows == 0): ?>
                        <div class="col-12">
                            <div class="alert alert-secondary text-center">No activities logged yet. Upload your first event photo!</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
