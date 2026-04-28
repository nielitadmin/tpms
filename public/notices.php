<?php
require __DIR__ . '/../includes/config.php';

$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Notices - NIELIT TPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">NIELIT TPS</a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="courses.php">Courses</a></li>
                    <li class="nav-item"><a class="nav-link active" href="notices.php">Public Notices</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-primary btn-sm mt-1" href="../login.php">Portal Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h2 class="mb-4 border-bottom pb-2">Official Notice Board</h2>
                <div class="card shadow-sm border-0">
                    <div class="list-group list-group-flush">
                        <?php while($row = $notices->fetch_assoc()): ?>
                            <div class="list-group-item p-4">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h5 class="mb-1 text-primary"><?= htmlspecialchars($row['title']) ?></h5>
                                    <small class="text-muted fw-bold"><?= date('d F Y', strtotime($row['created_at'])) ?></small>
                                </div>
                                <?php if(!empty($row['description'])): ?>
                                    <p class="mb-2 mt-2 text-secondary"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a href="../<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-danger">
                                        📄 Download / View PDF
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php if($notices->num_rows == 0): ?>
                            <div class="p-5 text-center text-muted">
                                <h5>No active notices at this time.</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <div class="container">
            <small>&copy; <?= date('Y') ?> NIELIT Bhubaneswar. All Rights Reserved.</small>
        </div>
    </footer>
</body>
</html>
