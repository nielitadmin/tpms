<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('admin');

$message = '';
$messageType = '';

// Helper function to extract YouTube ID for thumbnails
function getYouTubeThumbnail($url) {
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $match)) {
        return "https://img.youtube.com/vi/" . $match[1] . "/mqdefault.jpg";
    }
    return false;
}

// 1. Handle Adding a New Video
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_video'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $video_url = $conn->real_escape_string($_POST['video_url']);
    $description = $conn->real_escape_string($_POST['description']);

    $sql = "INSERT INTO helpdesk_videos (title, video_url, description) VALUES ('$title', '$video_url', '$description')";
    if ($conn->query($sql)) {
        $message = "Tutorial published successfully and is now visible to all centers!";
        $messageType = "success";
    } else {
        $message = "Database Error: " . $conn->error;
        $messageType = "danger";
    }
}

// 2. Handle Deleting a Video
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_video'])) {
    $video_id = (int)$_POST['video_id'];
    if ($conn->query("DELETE FROM helpdesk_videos WHERE id = $video_id")) {
        $message = "Tutorial successfully deleted from the portal.";
        $messageType = "success";
    } else {
        $message = "Error deleting tutorial: " . $conn->error;
        $messageType = "danger";
    }
}

// Fetch all published tutorials
$videos = $conn->query("SELECT * FROM helpdesk_videos ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk Control - Admin Command</title>
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
        .vid-card:hover { transform: translateY(-5px); box-shadow: 15px 15px 25px rgba(166, 180, 200, 0.5), -15px -15px 25px rgba(255, 255, 255, 1); }

        /* Action Buttons 3D */
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }

        /* Form Inputs */
        .form-control-3d { border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05); background: rgba(255,255,255,0.9); padding: 12px 15px; }
        .form-control-3d:focus { border-color: #3b82f6; box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05), 0 0 0 0.25rem rgba(59, 130, 246, 0.25); }

        /* Thumbnail Wrapper */
        .thumb-wrapper { position: relative; width: 100%; height: 160px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .thumb-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .vid-card:hover .thumb-wrapper img { transform: scale(1.05); }
        .play-overlay { position: absolute; background: rgba(0,0,0,0.4); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; opacity: 0.8; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.3); backdrop-filter: blur(2px); }
        .vid-card:hover .play-overlay { opacity: 1; transform: scale(1.1); background: #ef4444; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
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
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-chalkboard-teacher text-success me-2"></i> Helpdesk Control</h2>
                <p class="text-muted mb-0">Publish video tutorials and system guides to help Training Partners navigate the portal.</p>
            </div>
            <div>
                <a href="admin_dashboard.php" class="btn btn-3d btn-light text-primary px-4 py-2 border">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?= $messageType ?> card-3d border-<?= $messageType ?> border-start border-5 mb-4 p-3 d-flex align-items-center animate__animated animate__fadeIn">
                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> fs-4 text-<?= $messageType ?> me-3"></i>
                <div class="fw-bold"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card-3d p-4 border-top border-primary border-4 h-100" style="background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(241,245,249,0.9));">
                    <h4 class="fw-bold text-dark mb-4"><i class="fas fa-link text-primary me-2"></i> Share Tutorial Link</h4>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Tutorial Title</label>
                            <input type="text" name="title" class="form-control form-control-3d" required placeholder="e.g., How to Bulk Upload Students">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Video URL (YouTube/Drive)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-danger"><i class="fab fa-youtube"></i></span>
                                <input type="url" name="video_url" class="form-control form-control-3d border-start-0 ps-0" required placeholder="https://youtube.com/watch?v=...">
                            </div>
                            <small class="text-muted d-block mt-1"><i class="fas fa-magic text-warning me-1"></i> System automatically detects YouTube thumbnails.</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Brief Description</label>
                            <textarea name="description" class="form-control form-control-3d" rows="4" placeholder="Explain what the center will learn from this video..."></textarea>
                        </div>
                        
                        <button type="submit" name="add_video" class="btn btn-primary btn-3d w-100 py-3 fs-5">
                            <i class="fas fa-upload me-2"></i> Publish to Portal
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-3d p-4 h-100">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h5 class="fw-bold text-dark m-0"><i class="fas fa-photo-video text-info me-2"></i> Published Library</h5>
                        <div class="input-group w-50">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="videoSearch" class="form-control border-start-0 ps-0 form-control-sm" placeholder="Search tutorials...">
                        </div>
                    </div>

                    <div class="row g-4" id="videoLibrary">
                        <?php while($v = $videos->fetch_assoc()): 
                            $thumb = getYouTubeThumbnail($v['video_url']);
                        ?>
                            <div class="col-md-6 video-item">
                                <div class="card-3d vid-card h-100 d-flex flex-column" style="border-radius: 15px;">
                                    
                                    <div class="thumb-wrapper">
                                        <?php if($thumb): ?>
                                            <img src="<?= $thumb ?>" alt="Video Thumbnail">
                                        <?php else: ?>
                                            <i class="fas fa-play-circle fa-4x text-muted opacity-50"></i>
                                        <?php endif; ?>
                                        <div class="play-overlay"><i class="fas fa-play fs-4 ms-1"></i></div>
                                    </div>
                                    
                                    <div class="p-3 d-flex flex-column flex-grow-1 bg-white bg-opacity-50">
                                        <h6 class="fw-bold text-dark mb-1 video-title" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= htmlspecialchars($v['title']) ?>
                                        </h6>
                                        <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= htmlspecialchars($v['description']) ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                            <a href="<?= htmlspecialchars($v['video_url']) ?>" target="_blank" class="btn btn-sm btn-primary btn-3d px-3">
                                                Watch
                                            </a>
                                            <form method="POST" action="" class="m-0" onsubmit="return confirm('Delete this tutorial from the system?');">
                                                <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
                                                <button type="submit" name="delete_video" class="btn btn-sm btn-outline-danger border-0" title="Delete Tutorial">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php if($videos->num_rows == 0): ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-film fa-3x text-muted opacity-25 mb-3"></i>
                                <h6 class="text-muted fw-bold">No tutorials published yet.</h6>
                                <p class="text-muted small">Use the form to share helpful links with your centers.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Real-time Search Logic
        document.getElementById('videoSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.video-item');
            
            items.forEach(item => {
                let title = item.querySelector('.video-title').textContent.toLowerCase();
                if(title.includes(filter)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
