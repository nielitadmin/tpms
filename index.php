<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Partner System - NIELIT Bhubaneswar</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand {
            letter-spacing: 1px;
        }
        .hero-section {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            color: white;
            padding: 80px 0 70px;
        }
        
        /* Map Container Styling */
        .map-wrapper {
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            border: 3px solid #ffffff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #fff;
        }
        .map-wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .map-image {
            width: 100%;
            height: 250px;
            object-fit: contain; /* Ensures the whole map is visible */
            background-color: #fdfdfd;
            padding: 5px;
        }

        /* Login Card Styling */
        .login-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            background: #ffffff;
        }
        .login-header {
            background: linear-gradient(135deg, #0056b3, #0dcaf0);
            border-radius: 16px 16px 0 0 !important;
            padding: 25px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(13, 202, 240, 0.2);
            border-color: #0dcaf0;
            background-color: #ffffff;
        }
        
        /* Feature Cards */
        .feature-card {
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
        }
        .icon-circle {
            width: 60px;
            height: 60px;
            background: #e9ecef;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-info">NIELIT</span> TPS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link" href="public/courses.php">Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="public/notices.php">Public Notices</a></li>
                    <li class="nav-item"><a class="nav-link" href="public/contact.php">Contact Us</a></li>
                    <li class="nav-item">
                        <a class="btn btn-info text-white fw-bold px-4 rounded-pill shadow-sm" href="#login-section">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bolder mb-3">Training Partner Management System</h1>
            <p class="lead mb-4 mx-auto text-light opacity-75" style="max-width: 700px;">
                Empowering educational centers across Odisha and Chhattisgarh with streamlined student management, CBT tracking, and administration.
            </p>
            <a href="tp/tp_signup.php" class="btn btn-light btn-lg px-5 fw-bold text-dark rounded-pill shadow">Register New Center</a>
        </div>
    </section>

    <section class="container py-5 mt-3" id="login-section">
        <div class="row g-5 align-items-center">
            
            <div class="col-lg-7">
                <div class="mb-4">
                    <h3 class="fw-bold text-dark mb-1">Our Regional Presence</h3>
                    <p class="text-muted">Operating across key districts in Odisha and Chhattisgarh.</p>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="map-wrapper">
                            <img src="uploads\images\odisha_map.png" alt="Map of Odisha" class="map-image">
                            <div class="bg-light text-center py-2 border-top">
                                <span class="fw-bold text-secondary">Odisha State</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="map-wrapper">
                            <img src="uploads\images\chhattisgarh_map.png" alt="Map of Chhattisgarh" class="map-image">
                            <div class="bg-light text-center py-2 border-top">
                                <span class="fw-bold text-secondary">Chhattisgarh State</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card login-card h-100">
                    <div class="card-header login-header text-white text-center border-0">
                        <h4 class="mb-0 fw-bold">System Portal</h4>
                        <small class="text-white-50">Secure access for active partners</small>
                    </div>
                    <div class="card-body p-4 p-sm-5 bg-white rounded-bottom">
                        <form action="login.php" method="POST">
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold text-secondary">Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="name@center.com" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="••••••••" required>
                            </div>
                            <div class="mb-5">
                                <label for="role" class="form-label fw-semibold text-secondary">Select Role</label>
                                <select class="form-select form-select-lg" id="role" name="role">
                                    <option value="tp">Training Partner (TP)</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-pill shadow-sm">Secure Sign In</button>
                            
                            <div class="text-center mt-4">
                                <a href="#" class="text-decoration-none text-muted small hover-primary">Forgot your password?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section class="container py-5 mb-4 border-top">
        <div class="row text-center g-4 mt-2">
            <div class="col-md-4">
                <div class="card feature-card h-100 border-0 shadow-sm p-4 bg-white">
                    <div>
                        <div class="icon-circle text-primary">📁</div>
                    </div>
                    <h5 class="fw-bold text-dark">Bulk Data Uploads</h5>
                    <p class="text-muted mb-0 small">Easily upload student records and images via CSV formatting, mapped directly to active NIELIT courses.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 border-0 shadow-sm p-4 bg-white">
                    <div>
                        <div class="icon-circle text-success">📢</div>
                    </div>
                    <h5 class="fw-bold text-dark">Real-Time Notices</h5>
                    <p class="text-muted mb-0 small">Stay updated with instant PDF notices, syllabus updates, and operational guidelines straight from the administration.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 border-0 shadow-sm p-4 bg-white">
                    <div>
                        <div class="icon-circle text-info">📊</div>
                    </div>
                    <h5 class="fw-bold text-dark">CBT Tracking</h5>
                    <p class="text-muted mb-0 small">Showcase your center's success by logging test appearances, placements, and campus activities.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white text-center py-4 mt-auto">
        <div class="container">
            <p class="mb-1 fw-semibold">&copy; <?= date('Y') ?> NIELIT Bhubaneswar.</p>
            <small class="text-white-50">All Rights Reserved. Designed & Developed for Regional Training Partners.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>