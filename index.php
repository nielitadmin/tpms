<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Partner System - NIELIT Bhubaneswar</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            letter-spacing: 1px;
        }
        .hero-section {
            background: linear-gradient(135deg, #0056b3 0%, #0dcaf0 100%);
            color: white;
            padding: 70px 0 60px;
            box-shadow: inset 0 -5px 15px rgba(0,0,0,0.05);
        }
        .hero-section h1 {
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }
        /* Map Container Styling */
        #region-map {
            height: 100%;
            min-height: 450px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #fff;
            z-index: 1;
        }
        /* Login Card Styling */
        .login-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-card .card-header {
            background-color: #004085;
            border-bottom: none;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border-radius: 10px;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-info">NIELIT</span> TPS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link" href="public/courses.php">Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="public/notices.php">Public Notices</a></li>
                    <li class="nav-item"><a class="nav-link" href="public/contact.php">Contact Us</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-info btn-sm fw-bold px-3" href="#login-section">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Training Partner Management System</h1>
            <p class="lead mb-4 mx-auto" style="max-width: 700px;">
                Empowering educational centers across Odisha and Chhattisgarh with streamlined student management, CBT tracking, and administration.
            </p>
            <div class="d-flex justify-content-center gap-3 mt-2">
                <a href="tp/tp_signup.php" class="btn btn-light btn-lg px-4 fw-bold text-primary shadow-sm">Register New Center</a>
            </div>
        </div>
    </section>

    <section class="container py-5 mt-2" id="login-section">
        <div class="row g-4 align-items-stretch">
            
            <div class="col-lg-7 d-flex flex-column">
                <h4 class="fw-bold mb-3 text-dark">Our Regional Centers & Coverage</h4>
                <div id="region-map" class="flex-grow-1"></div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100 login-card">
                    <div class="card-header text-white text-center py-4">
                        <h4 class="mb-0 fw-bold">System Portal Login</h4>
                        <small class="text-light opacity-75">Secure access for active partners</small>
                    </div>
                    <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center bg-white">
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold text-secondary">Email Address</label>
                                <input type="email" class="form-control form-control-lg bg-light" id="email" name="email" placeholder="name@center.com" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold text-secondary">Password</label>
                                <input type="password" class="form-control form-control-lg bg-light" id="password" name="password" placeholder="••••••••" required>
                            </div>
                            <div class="mb-4">
                                <label for="role" class="form-label fw-semibold text-secondary">Select Role</label>
                                <select class="form-select form-select-lg bg-light" id="role" name="role">
                                    <option value="tp">Training Partner (TP)</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">Secure Sign In</button>
                            
                            <div class="text-center mt-4">
                                <a href="#" class="text-decoration-none text-muted small">Forgot Password?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section class="container py-4 mb-5">
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100 border-0 shadow-sm p-4 bg-white">
                    <div class="mb-3">
                        <span class="fs-1">📁</span>
                    </div>
                    <h5 class="fw-bold text-primary">Bulk Data Uploads</h5>
                    <p class="text-muted mb-0 small">Easily upload student records and images via CSV formatting, mapped directly to active NIELIT courses.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 border-0 shadow-sm p-4 bg-white">
                    <div class="mb-3">
                        <span class="fs-1">📢</span>
                    </div>
                    <h5 class="fw-bold text-success">Real-Time Notices</h5>
                    <p class="text-muted mb-0 small">Stay updated with instant PDF notices, syllabus updates, and operational guidelines straight from the administration.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card h-100 border-0 shadow-sm p-4 bg-white">
                    <div class="mb-3">
                        <span class="fs-1">📊</span>
                    </div>
                    <h5 class="fw-bold text-info">CBT Tracking</h5>
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
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize map centered on the border of Odisha and Chhattisgarh
            var map = L.map('region-map').setView([20.9, 83.5], 6);

            // Load OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 12,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Custom styling for the red dots (District Headquarters)
            var redDotStyle = {
                color: '#cc0000',
                fillColor: '#ff0000',
                fillOpacity: 0.9,
                radius: 6,
                weight: 1
            };

            // Array of locations (You can add all districts here by finding their lat/lng coordinates)
            var locations = [
                // Odisha Locations
                { name: "Bhubaneswar (HQ)", lat: 20.2961, lng: 85.8245, state: "Odisha" },
                { name: "Cuttack", lat: 20.4625, lng: 85.8828, state: "Odisha" },
                { name: "Sambalpur", lat: 21.4669, lng: 83.9812, state: "Odisha" },
                { name: "Berhampur", lat: 19.3150, lng: 84.7941, state: "Odisha" },
                { name: "Rourkela", lat: 22.2604, lng: 84.8536, state: "Odisha" },
                { name: "Balasore", lat: 21.4869, lng: 86.9246, state: "Odisha" },
                { name: "Bhadrak", lat: 21.0555, lng: 86.5075, state: "Odisha" },
                
                // Chhattisgarh Locations
                { name: "Raipur (HQ)", lat: 21.2514, lng: 81.6296, state: "Chhattisgarh" },
                { name: "Bilaspur", lat: 22.0797, lng: 82.1409, state: "Chhattisgarh" },
                { name: "Durg", lat: 21.1938, lng: 81.2849, state: "Chhattisgarh" },
                { name: "Jagdalpur", lat: 19.0775, lng: 82.0239, state: "Chhattisgarh" },
                { name: "Korba", lat: 22.3595, lng: 82.7501, state: "Chhattisgarh" }
            ];

            // Loop through the array and plot each red dot on the map
            locations.forEach(function(loc) {
                L.circleMarker([loc.lat, loc.lng], redDotStyle)
                 .addTo(map)
                 .bindPopup('<b>' + loc.name + '</b><br>' + loc.state);
            });
        });
    </script>
</body>
</html>