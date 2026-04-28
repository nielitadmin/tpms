<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('admin');

$message = '';
$messageType = '';

// 1. Handle File Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['notice_file'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $admin_id = $_SESSION['user_id'];
    
    $target_dir = __DIR__ . "/../uploads/notices/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["notice_file"]["name"], PATHINFO_EXTENSION));
    $new_filename = 'notice_' . time() . '_' . rand(1000, 9999) . '.pdf';
    $target_file = $target_dir . $new_filename;
    $db_path = "uploads/notices/" . $new_filename;

    if ($file_extension != "pdf") {
        $message = "Invalid format. Only PDF files are allowed.";
        $messageType = "danger";
    } elseif ($_FILES["notice_file"]["size"] > 5000000) {
        $message = "File is too large. Maximum size is 5MB.";
        $messageType = "danger";
    } else {
        if (move_uploaded_file($_FILES["notice_file"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO notices (title, description, file_path, published_by) 
                    VALUES ('$title', '$description', '$db_path', '$admin_id')";
            if ($conn->query($sql)) {
                $message = "Notice published successfully and broadcasted to all centers!";
                $messageType = "success";
            } else {
                $message = "Database Error: " . $conn->error;
                $messageType = "danger";
            }
        } else {
            $message = "Error uploading the file. Check folder permissions.";
            $messageType = "danger";
        }
    }
}

// 2. Handle Notice Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_notice'])) {
    $notice_id = (int)$_POST['notice_id'];
    $check_sql = "SELECT file_path FROM notices WHERE id = $notice_id";
    $result = $conn->query($check_sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_to_delete = __DIR__ . '/../' . $row['file_path'];
        if ($conn->query("DELETE FROM notices WHERE id = $notice_id")) {
            if (file_exists($file_to_delete)) unlink($file_to_delete);
            $message = "Notice and associated PDF file deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Error deleting notice: " . $conn->error;
            $messageType = "danger";
        }
    }
}

$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board Control - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; min-height: 100vh; }
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .navbar-brand { font-weight: 800; background: linear-gradient(90deg, #1e293b, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s; }
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }
        .form-control-3d { border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05); background: rgba(255,255,255,0.9); padding: 12px 15px; }
        .form-control-3d:focus { border-color: #ef4444; box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05), 0 0 0 0.25rem rgba(239, 68, 68, 0.25); }
        .file-upload-wrapper { position: relative; width: 100%; height: 120px; border: 2px dashed #cbd5e1; border-radius: 15px; background: rgba(248, 250, 252, 0.8); display: flex; align-items: center; justify-content: center; flex-direction: column; transition: all 0.3s; cursor: pointer; }
        .file-upload-wrapper:hover { border-color: #ef4444; background: rgba(254, 242, 242, 0.8); }
        .file-upload-wrapper input[type="file"] { position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; }
        .notice-item { border: none; border-bottom: 1px solid rgba(0,0,0,0.05); background: transparent; padding: 15px 20px; transition: 0.2s; }
        .notice-item:hover { background: rgba(241, 245, 249, 0.8); border-radius: 10px; }
        .notice-item:last-child { border-bottom: none; }
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
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-bullhorn text-danger me-2"></i> Notice Board Control</h2>
                <p class="text-muted mb-0">Publish PDF circulars, guidelines, and updates to all Training Partners.</p>
            </div>
            <div>
                <a href="admin_dashboard.php" class="btn btn-3d btn-light text-primary px-4 py-2 border">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?= $messageType ?> card-3d border-<?= $messageType ?> border-start border-5 mb-4 p-3 d-flex align-items-center">
                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> fs-4 text-<?= $messageType ?> me-3"></i>
                <div class="fw-bold"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card-3d p-4 border-top border-danger border-4 h-100" style="background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(254,242,242,0.6));">
                    <h4 class="fw-bold text-dark mb-4"><i class="fas fa-cloud-upload-alt text-danger me-2"></i> Publish Notice</h4>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Notice Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-3d" required placeholder="e.g., Exam Schedule 2026">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Brief Description</label>
                            <textarea name="description" class="form-control form-control-3d" rows="3" placeholder="Optional details or context..."></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Upload PDF Document <span class="text-danger">*</span></label>
                            <div class="file-upload-wrapper">
                                <i class="fas fa-file-pdf fa-2x text-danger mb-2 opacity-75"></i>
                                <span class="fw-bold text-dark">Click to browse or drag file here</span>
                                <small class="text-muted">Only .pdf files up to 5MB</small>
                                <input type="file" name="notice_file" accept=".pdf" required id="fileInput">
                            </div>
                            <div id="fileNameDisplay" class="text-success small fw-bold mt-2 text-center" style="display:none;"></div>
                        </div>
                        <button type="submit" class="btn btn-danger btn-3d w-100 py-3 fs-5">
                            <i class="fas fa-broadcast-tower me-2"></i> Broadcast Notice
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card-3d p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h5 class="fw-bold text-dark m-0"><i class="fas fa-list text-primary me-2"></i> Published Library</h5>
                        <div class="input-group w-50">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="noticeSearch" class="form-control border-start-0 ps-0 form-control-sm" placeholder="Search notices...">
                        </div>
                    </div>
                    <div class="list-group list-group-flush" id="noticeList" style="max-height: 500px; overflow-y: auto;">
                        <?php while($n = $notices->fetch_assoc()): 
                            $is_new = (strtotime($n['created_at']) > strtotime('-24 hours'));
                        ?>
                            <div class="list-group-item notice-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="fw-bold text-dark mb-0 me-2 notice-title"><?= htmlspecialchars($n['title']) ?></h6>
                                        <?php if($is_new): ?><span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">NEW</span><?php endif; ?>
                                    </div>
                                    <div class="text-muted small mb-1"><i class="fas fa-clock me-1"></i> <?= date('M d, Y - h:i A', strtotime($n['created_at'])) ?></div>
                                    <?php if(!empty($n['description'])): ?>
                                        <p class="text-secondary small mb-0 text-truncate" style="max-width: 300px;"><?= htmlspecialchars($n['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="../<?= htmlspecialchars($n['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-3d" title="View PDF">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <form method="POST" action="" class="m-0" onsubmit="return confirm('Delete this notice permanently?');">
                                        <input type="hidden" name="notice_id" value="<?= $n['id'] ?>">
                                        <button type="submit" name="delete_notice" class="btn btn-sm btn-outline-danger btn-3d" title="Delete Notice">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php if($notices->num_rows == 0): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted opacity-25 mb-3"></i>
                                <h6 class="text-muted fw-bold">No notices published yet.</h6>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('fileInput').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var displayDiv = document.getElementById('fileNameDisplay');
            displayDiv.innerHTML = '<i class="fas fa-check me-1"></i> Selected: ' + fileName;
            displayDiv.style.display = 'block';
        });
        document.getElementById('noticeSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.notice-item').forEach(item => {
                let title = item.querySelector('.notice-title').textContent.toLowerCase();
                item.style.display = title.includes(filter) ? 'flex' : 'none';
            });
        });
    </script>
</body>
</html>
