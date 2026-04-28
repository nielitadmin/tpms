<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Partner System - NIELIT Bhubaneswar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 80px 0;
        }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">NIELIT TPS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="public/courses.php">Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="public/notices.php">Public Notices</a></li>
                    <li class="nav-item"><a class="nav-link" href="public/contact.php">Contact Us</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-primary btn-sm mt-1" href="login.php">Portal Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Training Partner Management System</h1>
            <p class="lead mb-4">Empowering educational centers with streamlined student management, course tracking, and direct communication.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="tp/tp_signup.php" class="btn btn-light btn-lg px-4 fw-bold text-primary">Register New Center</a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-4">TP / Admin Login</a>
            </div>
        </div>
    </section>

    <section class="container py-5 mt-4">
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <h4 class="fw-bold text-primary">Bulk Uploads</h4>
                    <p class="text-muted">Easily upload student records via CSV formatting, mapped directly to active NIELIT courses.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <h4 class="fw-bold text-success">Real-Time Notices</h4>
                    <p class="text-muted">Stay updated with instant PDF notices and operational guidelines straight from the administration.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <h4 class="fw-bold text-info">Activity Tracking</h4>
                    <p class="text-muted">Showcase your center's success by logging placements, testimonials, and campus activities.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <div class="container">
            <small>&copy; <?= date('Y') ?> NIELIT Bhubaneswar. All Rights Reserved. Designed for Training Partners.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>