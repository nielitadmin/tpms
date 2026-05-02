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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f7f7f7;
            color: #333;
        }

        /* Govt Accessibility Top Bar */
        .top-bar {
            background-color: #f0f0f0;
            border-bottom: 1px solid #ccc;
            font-size: 12px;
            padding: 4px 0;
        }
        .top-bar a {
            color: #333;
            text-decoration: none;
            margin-right: 15px;
        }
        .top-bar a:hover {
            text-decoration: underline;
        }
        .font-resize {
            display: inline-block;
            border: 1px solid #ccc;
            padding: 1px 6px;
            margin-left: 3px;
            background: #fff;
            cursor: pointer;
        }

        /* Official Header */
        .official-header {
            background-color: #ffffff;
            padding: 15px 0;
        }
        .inst-title-hi {
            font-size: 18px;
            color: #1a498b;
            font-weight: bold;
            margin: 0;
        }
        .inst-title-en {
            font-size: 14px;
            color: #333;
            font-weight: bold;
            margin: 0;
        }
        .ministry-text {
            font-size: 12px;
            text-align: right;
            color: #555;
        }

        /* Standard Govt Blue Navbar */
        .gov-navbar {
            background-color: #0a4a91; /* NIC standard blue */
            border-bottom: 3px solid #ff9933; /* Saffron accent line */
        }
        .gov-navbar .nav-link {
            color: #ffffff !important;
            font-size: 14px;
            padding: 12px 20px !important;
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        .gov-navbar .nav-link:hover, .gov-navbar .nav-link.active {
            background-color: #06356e;
        }
        .gov-navbar .nav-item:first-child .nav-link {
            border-left: 1px solid rgba(255,255,255,0.2);
        }

        /* Breadcrumb */
        .breadcrumb-container {
            background: #e9ecef;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        .breadcrumb {
            margin: 0;
        }

        /* Content Area */
        .main-content {
            background-color: #ffffff;
            border: 1px solid #ddd;
            padding: 30px;
            margin-bottom: 40px;
            border-top: 3px solid #1a498b;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .page-heading {
            color: #1a498b;
            font-size: 24px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-weight: bold;
        }

        /* Table Styling */
        .table-custom th {
            background-color: #1a498b;
            color: #ffffff;
            font-weight: normal;
            border: 1px solid #103468;
        }
        .table-custom td {
            border: 1px solid #ddd;
            vertical-align: middle;
            font-size: 14px;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }

        /* Footer */
        .gov-footer {
            background-color: #333333;
            color: #fff;
            font-size: 13px;
            padding: 20px 0;
        }
        .gov-footer a {
            color: #ffcc00;
            text-decoration: none;
        }
        .gov-footer a:hover {
            text-decoration: underline;
        }
        .footer-bottom {
            background-color: #222222;
            padding: 10px 0;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Accessibility Top Bar -->
    <div class="top-bar d-none d-md-block">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <a href="#main-content">Skip to Main Content</a> | 
                <a href="#">Screen Reader Access</a>
            </div>
            <div>
                <span class="me-2">Font Size: 
                    <span class="font-resize">A-</span>
                    <span class="font-resize">A</span>
                    <span class="font-resize">A+</span>
                </span>
                <span class="border-start ps-3 ms-2">
                    <a href="#">English</a> | <a href="#">हिन्दी</a>
                </span>
            </div>
        </div>
    </div>

    <!-- Official Header -->
    <header class="official-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <!-- Replace with your actual RR.png logo path -->
                <img src="../RR.png" alt="NIELIT Logo" height="70" class="me-3" onerror="this.src='https://via.placeholder.com/70x70?text=Logo'">
                <div>
                    <h1 class="inst-title-hi">राष्ट्रीय इलेक्ट्रॉनिकी एवं सूचना प्रौद्योगिकी संस्थान, भुवनेश्वर</h1>
                    <h2 class="inst-title-en">National Institute of Electronics & Information Technology, Bhubaneswar</h2>
                </div>
            </div>
            <div class="d-none d-lg-block text-end">
                <div class="ministry-text mb-1">
                    <strong>Ministry of Electronics & Information Technology</strong><br>
                    Government of India
                </div>
                <!-- Replace with your actual Emblem image path -->
                <img src="../image_7c2b82.png" alt="Emblem" height="50" onerror="this.src='https://via.placeholder.com/40x50?text=Emblem'">
            </div>
        </div>
    </header>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg gov-navbar p-0">
        <div class="container">
            <button class="navbar-toggler text-white my-2" type="button" data-bs-toggle="collapse" data-bs-toggle="target="#navbarNav">
                <i class="fas fa-bars"></i> Menu
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav w-100">
                    <li class="nav-item"><a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="courses.php">Training Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="notices.php">Public Notices</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                    <li class="nav-item ms-lg-auto">
                        <a class="nav-link" style="background-color: #dc3545;" href="../login.php"><i class="fas fa-lock"></i> TP Portal Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="breadcrumb-container">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Training Courses</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-4 flex-grow-1" id="main-content">
        <div class="main-content">
            <h3 class="page-heading">List of Approved Training Courses</h3>
            
            <p style="font-size: 14px; margin-bottom: 20px;">
                Below is the list of active certification and training programs offered through our authorized Training Partner (TP) network. Candidates are advised to check the eligibility criteria carefully before enrollment.
            </p>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-custom">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 5%;">S.No</th>
                            <th scope="col" style="width: 45%;">Course Name</th>
                            <th scope="col" style="width: 15%;">Duration</th>
                            <th scope="col" style="width: 35%;">Eligibility Criteria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($courses && $courses->num_rows > 0): 
                            $counter = 1;
                            while($row = $courses->fetch_assoc()): 
                        ?>
                            <tr>
                                <td class="text-center"><?= $counter++ ?></td>
                                <td><strong><?= htmlspecialchars($row['course_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['duration']) ?></td>
                                <td><?= htmlspecialchars($row['eligibility']) ?></td>
                            </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-danger">No active courses found at the moment. Please check back later.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="gov-footer mt-auto">
        <div class="container text-center text-md-start">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h5>Important Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="https://www.india.gov.in/" target="_blank">National Portal of India</a></li>
                        <li><a href="https://www.meity.gov.in/" target="_blank">MeitY</a></li>
                        <li><a href="https://nielit.gov.in/" target="_blank">NIELIT HQ</a></li>
                    </ul>
                </div>
                <div class="col-md-6 mb-3 text-md-end">
                    <h5>Contact Support</h5>
                    <p class="mb-0">Helpdesk: 0674-2960354</p>
                    <p>Email: dir-bhubaneswar@nielit.gov.in</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom mt-3 border-top border-secondary pt-3">
            <div class="container">
                Site designed and developed by <a href="#">National Institute of Electronics & Information Technology, Bhubaneswar</a>.<br>
                &copy; <?= date('Y') ?> All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>