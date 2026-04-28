<?php
require __DIR__ . '/../includes/config.php';

$sql = "SELECT course_name, duration, eligibility FROM courses WHERE status = 'active' ORDER BY id DESC";
$courses = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Courses - NIELIT TPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">NIELIT TPS</a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="courses.php">Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="notices.php">Public Notices</a></li>
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
                <div class="text-center mb-5">
                    <h2 class="fw-bold">Our Training Courses</h2>
                    <p class="text-muted">Explore the certification and training programs offered through our authorized partner network.</p>
                </div>
                <div class="row g-4">
                    <?php while($row = $courses->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm border-0 border-start border-primary border-4">
                                <div class="card-body p-4">
                                    <h4 class="card-title text-primary mb-3"><?= htmlspecialchars($row['course_name']) ?></h4>
                                    <div class="d-flex mb-2">
                                        <div class="text-muted me-2" style="width: 100px;"><strong>Duration:</strong></div>
                                        <div><?= htmlspecialchars($row['duration']) ?></div>
                                    </div>
                                    <div class="d-flex">
                                        <div class="text-muted me-2" style="width: 100px;"><strong>Eligibility:</strong></div>
                                        <div><?= htmlspecialchars($row['eligibility']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php if($courses->num_rows == 0): ?>
                        <div class="col-12 text-center py-5">
                            <h5 class="text-muted">Course list is currently being updated. Please check back later.</h5>
                        </div>
                    <?php endif; ?>
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
