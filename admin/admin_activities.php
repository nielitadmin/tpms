<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('admin');

$message = '';
$messageType = '';

// Handle Activity Deletion (Admin Moderation Feature)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_activity'])) {
    $activity_id = (int)$_POST['activity_id'];
    
    // Fetch image path to delete from server storage
    $check_sql = "SELECT image_path FROM activities WHERE id = $activity_id";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_to_delete = $row['image_path'];
        
        // Delete from Database
        if ($conn->query("DELETE FROM activities WHERE id = $activity_id")) {
            // Delete actual image file from Hostinger server to save space
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete);
            }
            $message = "Activity and associated image deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Error deleting activity: " . $conn->error;
            $messageType = "danger";
        }
    }
}

// Fetch all activities across all centers, joined with the TP's details
$sql = "SELECT a.id, a.title, a.description, a.image_path, a.created_at, 
               u.center_id, u.name as tp_name 
        FROM activities a 
        JOIN users u ON a.tp_id = u.id 
        ORDER BY a.created_at DESC";
        
$activities = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Activities - Admin Command</title>
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
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s; overflow: hidden; display: flex; flex-direction: column; }
        .card-3d:hover { transform: translateY(-8px); box-shadow: 15px 15px 25px rgba(166, 180, 200, 0.5), -15px -15px 25px rgba(255, 255, 255, 1); }

        /* Action Buttons 3D */
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }

        /* Image Hover Zoom Effect */
        .img-container { position: relative; overflow: hidden; border-top-left-radius: 20px; border-top-right-radius: 20px; cursor: pointer; }
        .img-container img { transition: transform 0.5s ease; width: 100%; height: 220px; object-fit: cover; }
        .img-container:hover img { transform: scale(1.1); }
        .img-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; color: white; font-size: 2rem; }
        .img-container:hover .img-overlay { opacity: 1; }

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
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-photo-video text-info me-2"></i> Center Activities Feed</h2>
                <p class="text-muted mb-0">Monitor campus drives, seminars, and events uploaded by Training Partners.</p>
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

        <div class="row justify-content-end mb-4">
            <div class="col-md-5 col-lg-4">
                <div class="input-group card-3d" style="border-radius: 12px; padding: 5px;">
                    <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="activitySearch" class="form-control border-0 bg-transparent shadow-none" placeholder="Search by title or center name...">
                </div>
            </div>
        </div>

        <div class="row g-4" id="activityGrid">
            <?php while($act = $activities->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 activity-card">
                    <div class="card-3d h-100">
                        
                        <div class="img-container" onclick="openLightbox('<?= htmlspecialchars($act['image_path']) ?>', '<?= htmlspecialchars(addslashes($act['title'])) ?>')">
                            <img src="<?= htmlspecialchars($act['image_path']) ?>" alt="Activity Image">
                            <div class="img-overlay">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </div>
                        </div>
                        
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title text-dark fw-bold mb-0 activity-title"><?= htmlspecialchars($act['title']) ?></h5>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1 rounded" style="font-size: 0.7rem;">
                                    <?= date('d M Y', strtotime($act['created_at'])) ?>
                                </span>
                            </div>
                            
                            <p class="card-text text-secondary small flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= nl2br(htmlspecialchars($act['description'])) ?>
                            </p>
                            
                            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                <div class="tp-info text-truncate">
                                    <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($act['tp_name']) ?>"><?= htmlspecialchars($act['tp_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;"><i class="fas fa-map-marker-alt me-1"></i> ID: <?= htmlspecialchars($act['center_id']) ?></div>
                                </div>
                                
                                <form method="POST" action="" class="m-0" onsubmit="return confirm('Are you sure you want to delete this activity? This cannot be undone.');">
                                    <input type="hidden" name="activity_id" value="<?= $act['id'] ?>">
                                    <button type="submit" name="delete_activity" class="btn btn-sm btn-outline-danger btn-3d px-2 py-1" title="Delete Post">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if($activities->num_rows == 0): ?>
                <div class="col-12">
                    <div class="card-3d p-5 text-center text-muted">
                        <i class="fas fa-images fa-4x mb-3 text-secondary opacity-25"></i>
                        <h4 class="fw-bold">No Activity Feeds Yet</h4>
                        <p>Training centers haven't uploaded any event photos yet.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="background: transparent; border: none;">
                <div class="modal-header border-0 p-0 mb-2 justify-content-end">
                    <button type="button" class="btn btn-light rounded-circle shadow" data-bs-dismiss="modal" aria-label="Close" style="width: 40px; height: 40px;"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body p-0 text-center position-relative">
                    <img id="lightboxImage" src="" class="img-fluid rounded-3 shadow-lg" alt="Full size activity image" style="max-height: 80vh;">
                    <div id="lightboxTitle" class="position-absolute bottom-0 start-0 w-100 p-3 text-white fw-bold" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                        </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Real-time Search Filter
        document.getElementById('activitySearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let cards = document.querySelectorAll('.activity-card');
            
            cards.forEach(card => {
                let title = card.querySelector('.activity-title').textContent.toLowerCase();
                let tpName = card.querySelector('.tp-info').textContent.toLowerCase();
                
                if(title.includes(filter) || tpName.includes(filter)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Lightbox Modal Logic
        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        
        function openLightbox(imageSrc, titleText) {
            document.getElementById('lightboxImage').src = imageSrc;
            document.getElementById('lightboxTitle').textContent = titleText;
            imageModal.show();
        }
    </script>
</body>
</html>
