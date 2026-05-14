<?php
// 1. CRITICAL: Use the unified session name for the entire application
session_name('NIELIT_TPMS');
session_start();

// Include Database connection (which loads your .env file and creates $conn)
require_once 'includes/config.php';

// 2. AUTO-REDIRECT: If user is already logged in, send them to their dashboard
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
        exit();
    } else {
        header("Location: tp/tp_dashboard.php");
        exit();
    }
}

// 3. INTEGRATED LOGIN LOGIC
$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $login_error = "Please enter both email and password.";
    } else {
        // Secure Prepared Statement
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Optional Status Check
                if (isset($user['status']) && $user['status'] !== 'active') {
                    $login_error = "Account is pending or inactive. Contact Admin.";
                } 
                // Verify Password
                elseif (password_verify($password, $user['password'])) {
                    // Set Session Variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role']; 
                    
                    if(isset($user['name'])) {
                        $_SESSION['name'] = $user['name'];
                    }

                    // Smart Routing
                    if ($user['role'] === 'admin') {
                        header("Location: admin/admin_dashboard.php");
                    } else {
                        header("Location: tp/tp_dashboard.php");
                    }
                    exit();
                } else {
                    $login_error = "Invalid email or password.";
                }
            } else {
                $login_error = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            error_log("Login Query Error: " . $conn->error);
            $login_error = "System error. Please try again later.";
        }
    }
}

// 4. MAP DATA LOGIC
$districtTPCounts = [];

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $query = "SELECT district, COUNT(*) as count FROM centers WHERE status = 'Approved' AND district IS NOT NULL GROUP BY district";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $districtName = strtolower(trim($row['district']));
            $districtTPCounts[$districtName] = $row['count'];
        }
    } else {
        error_log("Map Data Query Failed: " . $conn->error);
    }
} else {
    error_log("Database connection failed while fetching map data.");
}

$tpCountsJson = json_encode($districtTPCounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Partner System - NIELIT Bhubaneswar</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Devanagari:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://d3js.org/d3.v7.min.js"></script>

    <style>
        :root {
            --primary: #155E75; --primary-light: #0284C7; --primary-bg: #EFF6FF; 
            --candidate: #059669; --candidate-bg: #ECFDF5; --tp: #0D9488; --tp-bg: #CCFBF1;
            --text-dark: #0F172A; --text-muted: #475569; --bg-body: #F8FAFC; --surface: #FFFFFF;
            --border: #E2E8F0; --gold: #D97706; --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 30px -5px rgba(0, 0, 0, 0.08); --radius-lg: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-dark); background-color: var(--bg-body); min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }

        /* HEADER & NAV */
        .top-header { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); z-index: 100; position: relative; width: 100%; }
        .header-container { display: flex; justify-content: space-between; align-items: center; max-width: 1440px; margin: 0 auto; padding: 12px 40px; width: 100%; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .nielit-logo { height: 55px; width: auto; object-fit: contain; }
        .header-titles { display: flex; flex-direction: column; justify-content: center; }
        .hindi-title { font-family: 'Noto Sans Devanagari', sans-serif; font-size: 15px; color: var(--primary); font-weight: 700; line-height: 1.2; }
        .eng-title { font-size: 13px; font-weight: 600; color: var(--text-dark); margin-top: 2px;}
        .header-right { display: flex; align-items: center; gap: 15px; text-align: right; }
        .ministry-text { display: flex; flex-direction: column; font-size: 11px; color: var(--text-muted); font-weight: 600; }
        .ministry-text strong { font-size: 13px; color: var(--text-dark); }
        .emblem { height: 50px; width: auto; object-fit: contain; margin-left: 5px; }

        .main-nav { background: var(--primary); box-shadow: var(--shadow-sm); z-index: 99; position: relative; width: 100%; }
        .nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1440px; margin: 0 auto; padding: 0 40px; width: 100%; flex-wrap: wrap; }
        .nav-home-btn { color: #FFFFFF; text-decoration: none; font-weight: 700; font-size: 15px; display: flex; align-items: center; gap: 10px; padding: 15px 0; transition: color 0.3s; }
        .nav-home-btn:hover { color: #E0F2FE;}
        .mobile-menu-btn { display: none; background: none; border: none; color: #FFFFFF; font-size: 24px; cursor: pointer; padding: 10px 0; }
        .nav-links { display: flex; height: 100%; align-items: center; }
        .nav-link { color: #E0F2FE; text-decoration: none; font-weight: 600; font-size: 14px; padding: 16px 20px; transition: 0.3s; display: flex; align-items: center; gap: 8px; border-radius: 8px; margin: 0 2px;}
        .nav-link:hover { color: #FFFFFF; background: rgba(255, 255, 255, 0.15); }
        .nav-btn-highlight { color: #fef08a !important; font-weight: 700; }
        .nav-btn-highlight:hover { background: rgba(254, 240, 138, 0.1) !important; color: #fde047 !important; }

        /* ===== RED BLINKING TEXT ONLY ===== */
        .nav-blink-text {
            color: #ef4444 !important; /* Bright red text */
            font-weight: 700 !important;
            background-color: transparent !important; /* Ensure NO box background */
            animation: textRedBlink 1.2s infinite ease-in-out;
        }
        .nav-blink-text i {
            color: #ef4444 !important;
        }
        @keyframes textRedBlink {
            0%, 100% { opacity: 1; text-shadow: 0 0 8px rgba(239, 68, 68, 0.5); }
            50% { opacity: 0.3; text-shadow: none; }
        }

        .ticker-wrap { background: var(--text-dark); color: white; padding: 8px 0; overflow: hidden; position: relative; z-index: 10; font-size: 13px; font-weight: 600; display: flex; align-items: center; }
        .ticker-label { background: var(--gold); color: white; padding: 3px 12px; border-radius: 4px; font-weight: 800; margin: 0 15px; position: relative; z-index: 2; white-space: nowrap; font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;}
        .ticker-move { display: inline-block; white-space: nowrap; animation: ticker 40s linear infinite; padding-left: 20px;}
        @keyframes ticker { 0% { transform: translateX(100vw); } 100% { transform: translateX(-100%); } }

        /* AMBIENT BG */
        .ambient-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; pointer-events: none; background: radial-gradient(circle at 50% 0%, #E0F2FE 0%, #F8FAFC 70%); perspective: 1000px; }
        .shape { position: absolute; background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.3)); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 15px 35px rgba(21, 94, 117, 0.05), inset 0 0 20px rgba(255, 255, 255, 0.5); animation: float-3d 25s infinite linear; }
        .cube { width: 180px; height: 180px; border-radius: 35px; top: 15%; left: 5%; animation-duration: 35s; }
        .ring { width: 260px; height: 260px; border-radius: 50%; border: 40px solid rgba(255,255,255,0.5); top: 50%; right: 2%; animation-duration: 40s; animation-direction: reverse; background: transparent; }
        @keyframes float-3d { 0% { transform: translateY(0) rotateX(0deg) rotateY(0deg) rotateZ(0deg); } 50% { transform: translateY(-30px) rotateX(180deg) rotateY(90deg) rotateZ(45deg); } 100% { transform: translateY(0) rotateX(360deg) rotateY(180deg) rotateZ(90deg); } }

        /* DASHBOARD LAYOUT CSS */
        .main-showcase { max-width: 1440px; margin: 0 auto; width: 100%; padding: 50px 40px 30px 40px; z-index: 10; }
        .hero-top { text-align: center; max-width: 900px; margin: 0 auto 40px auto; animation: fadeUp 0.8s ease both; }
        
        .system-badge { display: inline-flex; align-items: center; gap: 8px; background: white; border: 1px solid var(--border); padding: 8px 18px; border-radius: 50px; font-size: 13px; font-weight: 800; color: var(--candidate); box-shadow: var(--shadow-sm); margin-bottom: 25px; }
        .live-dot { width: 8px; height: 8px; background: var(--candidate); border-radius: 50%; box-shadow: 0 0 12px var(--candidate); animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.3); } 100% { opacity: 1; transform: scale(1); } }
        
        .hero-title { font-size: 46px; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; line-height: 1.15; margin-bottom: 18px;}
        .hero-title span { color: var(--primary); }
        .hero-sub { font-size: 16px; color: var(--text-muted); font-weight: 500; line-height: 1.6; margin-bottom: 30px; padding: 0 20px;}
        
        .stats-row { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;}
        .stat { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); padding: 18px 30px; border-radius: 20px; border: 1px solid white; box-shadow: var(--shadow-sm); transition: transform 0.3s;}
        .stat:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .stat-num { font-size: 28px; font-weight: 800; color: var(--text-dark); line-height: 1;}
        .stat-num span { color: var(--primary); }
        .stat-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; margin-top: 6px;}

        /* THE 3-COLUMN ROW */
        .dashboard-row { display: grid; grid-template-columns: 1fr 1fr 380px; gap: 30px; align-items: stretch; animation: fadeUp 0.8s ease both; animation-delay: 0.2s; }
        .map-box { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.8); padding: 25px; border-radius: 24px; box-shadow: var(--shadow-md); text-align: center; display: flex; flex-direction: column; height: 100%; }
        .map-box h4 { margin-top: 20px; font-size: 20px; font-weight: 800; color: var(--text-dark); }
        .map-box p { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-top: 5px; }
        
        .map-box.odisha-highlight { background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(224, 242, 254, 0.6)); border-color: rgba(2, 132, 199, 0.1); }
        .map-box.odisha-highlight h4 { color: var(--primary); }
        .district-odisha { fill: #bae6fd; stroke: #ffffff; stroke-width: 0.8px; transition: all 0.3s ease; cursor: pointer; } 
        .district-odisha:hover { fill: #0284C7; stroke: #0F172A; stroke-width: 1.5px; } 

        .map-box.chhattisgarh-highlight { background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(204, 251, 241, 0.6)); border-color: rgba(13, 148, 136, 0.1); }
        .map-box.chhattisgarh-highlight h4 { color: var(--tp); }
        .district-chhattisgarh { fill: #a7f3d0; stroke: #ffffff; stroke-width: 0.8px; transition: all 0.3s ease; cursor: pointer; } 
        .district-chhattisgarh:hover { fill: #059669; stroke: #0F172A; stroke-width: 1.5px; }

        .svg-container { width: 100%; flex-grow: 1; min-height: 280px; background: rgba(255, 255, 255, 0.7); border-radius: 16px; border: 1px solid rgba(255,255,255,0.5); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }

        .map-tooltip { 
            position: absolute; opacity: 0; background: rgba(15, 23, 42, 0.95); color: #ffffff; 
            padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 600; 
            pointer-events: none; transition: opacity 0.2s ease; box-shadow: 0 10px 25px rgba(0,0,0,0.3); 
            z-index: 1000; min-width: 160px; border: 1px solid rgba(255,255,255,0.15);
        }

        /* UPGRADED LOGIN CARD */
        .login-section { display: flex; flex-direction: column; width: 100%; height: 100%; }
        .glass-login-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.8); border-radius: 24px; padding: 35px 30px; box-shadow: var(--shadow-md); position: relative; overflow: hidden; height: 100%; display: flex; flex-direction: column; justify-content: center; }
        .glass-login-card::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
        .glass-login-card h2 { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 6px; text-align: center;}
        .glass-login-card p { font-size: 14px; color: var(--text-muted); text-align: center; margin-bottom: 20px; font-weight: 500;}
        
        /* New Error Alert CSS */
        .login-error-alert { background-color: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; padding: 10px 15px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 14px 16px; border: 1px solid var(--border); border-radius: 12px; font-family: inherit; font-size: 14px; background: #F8FAFC; transition: 0.3s; font-weight: 500;}
        .form-control:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 4px var(--primary-bg); background: white; }
        .btn-submit { width: 100%; padding: 16px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 5px; box-shadow: 0 4px 15px rgba(2, 132, 199, 0.25); display: flex; justify-content: center; align-items: center; gap: 8px;}
        .btn-submit:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 8px 25px rgba(2, 132, 199, 0.35); }
        .form-footer { text-align: center; margin-top: 20px; font-size: 13px; font-weight: 600; color: var(--text-muted); }
        .form-footer a { color: var(--primary-light); text-decoration: none; transition: 0.2s; }
        .form-footer a:hover { color: var(--primary); text-decoration: underline; }

        .platform-details { max-width: 1440px; margin: 0 auto; width: 100%; padding: 20px 40px 60px 40px; z-index: 10; position: relative; animation: fadeUp 0.8s ease both; animation-delay: 0.4s; }
        .section-title { font-size: 30px; font-weight: 800; text-align: center; margin-bottom: 10px; letter-spacing: -0.5px; color: var(--text-dark); }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; margin-top: 40px;}
        .feature-box { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(15px); border: 1px solid white; padding: 30px; border-radius: 20px; box-shadow: var(--shadow-sm); display: flex; gap: 20px; transition: 0.3s; }
        .feature-box:hover { background: #FFFFFF; transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .f-icon { width: 55px; height: 55px; border-radius: 14px; background: var(--primary-bg); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(2,132,199,0.1);}
        .f-content h3 { font-size: 18px; font-weight: 800; margin-bottom: 8px; color: var(--text-dark); }
        .f-content p { font-size: 14px; color: var(--text-muted); line-height: 1.6; font-weight: 500;}

        .footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 25px 40px; background: white; border-top: 1px solid var(--border); font-size: 13px; font-weight: 600; color: var(--text-muted); z-index: 10; margin-top: auto; }
        .footer-left { display: flex; flex-direction: column; gap: 6px; }
        .credit-text { font-size: 12px; color: var(--primary-light); font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .footer-links { display: flex; gap: 25px; flex-wrap: wrap;}
        .footer-links a { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .footer-links a:hover { color: var(--primary); }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1200px) { .dashboard-row { grid-template-columns: 1fr 1fr; } .login-section { grid-column: span 2; max-width: 500px; margin: 0 auto; height: auto;} .glass-login-card { padding: 40px 30px; } }
        @media (max-width: 992px) { .hero-title { font-size: 38px; } }
        @media (max-width: 768px) { .header-container { flex-direction: column; gap: 15px; text-align: center; padding: 15px 20px; } .header-left, .header-right { flex-direction: column; align-items: center; justify-content: center; text-align: center;} .nav-container { padding: 10px 20px; } .mobile-menu-btn { display: block; } .nav-links { display: none; width: 100%; flex-direction: column; align-items: flex-start; padding-bottom: 15px; } .nav-links.active { display: flex; } .nav-link { width: 100%; padding: 12px 10px; justify-content: flex-start;} .dashboard-row { grid-template-columns: 1fr; } .login-section { grid-column: span 1; } .footer { flex-direction: column; gap: 15px; text-align: center; justify-content: center; } .footer-left { align-items: center; } .footer-links { justify-content: center; } .main-showcase, .platform-details { padding: 30px 20px; } }
        @media (max-width: 480px) { .hero-title { font-size: 32px; } .stat { min-width: 100%; padding: 15px; } .svg-container { min-height: 220px; } }
    </style>
</head>
<body>
    
    <div class="ambient-bg">
        <div class="shape cube"></div>
        <div class="shape ring"></div>
    </div>

    <header class="top-header">
        <div class="header-container">
            <div class="header-left">
                <img src="RR.png" alt="NIELIT Logo" class="nielit-logo">
                <div class="header-titles">
                    <span class="hindi-title">राष्ट्रीय इलेक्ट्रॉनिकी एवं सूचना प्रौद्योगिकी संस्थान, भुवनेश्वर</span>
                    <span class="eng-title">National Institute of Electronics & Information Technology, Bhubaneswar</span>
                </div>
            </div>
            <div class="header-right">
                <div class="ministry-text">
                    <strong>Ministry of Electronics & IT</strong> Government of India
                </div>
                <img src="image_7c2b82.png" alt="Government of India Emblem" class="emblem">
            </div>
        </div>
    </header>

    <nav class="main-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-home-btn">
                <i class="fas fa-layer-group"></i> NIELIT TPMS
            </a>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links" id="navLinks">
                <a href="public/courses.php" class="nav-link"><i class="fas fa-book"></i> Courses</a>
                <a href="public/notices.php" class="nav-link"><i class="fas fa-bullhorn"></i> Public Notices</a>
                <a href="public/contact.php" class="nav-link"><i class="fas fa-headset"></i> Contact Us</a>
                <a href="public/accreditation.php" class="nav-link nav-blink-text"><i class="fas fa-certificate"></i> Become an Accreditation Partner</a>
                <a href="tp/tp_signup.php" class="nav-link nav-btn-highlight"><i class="fas fa-user-plus"></i> Register Center</a>
            </div>
        </div>
    </nav>

    <div class="ticker-wrap">
        <div class="ticker-label">System Alerts</div>
        <div class="ticker-move">
            &bull; Registration for new Training Partners in Odisha and Chhattisgarh is now open. &bull; Existing partners must upload records via CSV before the deadline. &bull; Download operational guidelines from the Notices section.
        </div>
    </div>

    <main class="main-showcase">
        
        <div class="hero-top">
            <div class="system-badge">
                <span class="live-dot"></span> TPMS Portal Online
            </div>
            <h1 class="hero-title">Training Partner <span>Management System</span></h1>
            <p class="hero-sub">Empowering educational centers across Odisha and Chhattisgarh with streamlined student management, bulk CBT tracking, and direct administration integration.</p>
            <div class="stats-row">
                <div class="stat"><div class="stat-num">50<span>+</span></div><div class="stat-label">Active Centers</div></div>
                <div class="stat"><div class="stat-num">10<span>K+</span></div><div class="stat-label">Students Tracked</div></div>
                <div class="stat"><div class="stat-num">2</div><div class="stat-label">Major States</div></div>
            </div>
        </div>

        <div class="dashboard-row">
            
            <div class="map-box odisha-highlight">
                <div id="odisha-map-container" class="svg-container"></div>
                <h4>Odisha State</h4>
                <p>Supporting educational initiatives across all districts.</p>
            </div>

            <div class="map-box chhattisgarh-highlight">
                <div id="chhattisgarh-map-container" class="svg-container"></div>
                <h4>Chhattisgarh State</h4>
                <p>Expanding digital literacy through partners.</p>
            </div>

            <div class="login-section">
                <div class="glass-login-card">
                    <h2>Portal Access</h2>
                    <p>Secure login for registered partners & staff</p>
                    
                    <?php if(!empty($login_error)): ?>
                        <div class="login-error-alert">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($login_error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="index.php" method="POST">
                        <input type="hidden" name="login_submit" value="1">
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@center.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                        
                        <button type="submit" class="btn-submit">Secure Sign In <i class="fas fa-arrow-right"></i></button>
                        
                        <div class="form-footer">
                            Forgot your password? <a href="#">Recover here</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <div id="map-tooltip" class="map-tooltip"></div>

    <section class="platform-details">
        <h2 class="section-title">Platform Capabilities</h2>
        <div class="features-grid">
            <div class="feature-box">
                <div class="f-icon"><i class="fas fa-file-csv"></i></div>
                <div class="f-content">
                    <h3>Bulk Data Uploads</h3>
                    <p>Easily upload student records and images via standard CSV formatting, mapped directly to active NIELIT courses to save time.</p>
                </div>
            </div>
            <div class="feature-box">
                <div class="f-icon"><i class="fas fa-bell"></i></div>
                <div class="f-content">
                    <h3>Real-Time Notices</h3>
                    <p>Stay updated with instant PDF notices, syllabus modifications, and critical operational guidelines straight from the administration desk.</p>
                </div>
            </div>
            <div class="feature-box">
                <div class="f-icon"><i class="fas fa-chart-line"></i></div>
                <div class="f-content">
                    <h3>CBT Tracking & Success</h3>
                    <p>Showcase your center's success by logging computer-based test appearances, local placements, and ongoing campus activities.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-left">
            <p>&copy; <?= date('Y') ?> NIELIT Bhubaneswar. All Rights Reserved.</p>
            <div class="credit-text"><i class="fas fa-code"></i> Designed & Developed for Regional Training Partners</div>
        </div>
        <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Use</a>
            <a href="#">Helpdesk: 0674-2960354</a>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('active');
        }

        document.addEventListener("DOMContentLoaded", function() {
            const tooltip = d3.select("#map-tooltip");

            // 1. Get the PHP data array into JavaScript
            const tpData = <?= $tpCountsJson ?>;

            // 2. Smart function to automatically find the correct District Name from the GeoJSON file
            function getDistrictName(properties) {
                const possibleKeys = ['NAME_2', 'DISTRICT', 'district', 'dtname', 'Dist_Name', 'name', 'NAME_3'];
                for (let key of possibleKeys) {
                    if (properties[key]) return properties[key];
                }
                return "Unknown District";
            }

            function drawInteractiveMap(containerId, geojsonPath, districtClass) {
                const container = d3.select("#" + containerId);
                const rect = container.node().getBoundingClientRect();
                const width = rect.width || 400;
                const height = rect.height || 280;

                const svg = container.append("svg")
                    .attr("width", "100%")
                    .attr("height", "100%")
                    .attr("viewBox", `0 0 ${width} ${height}`)
                    .attr("preserveAspectRatio", "xMidYMid meet");

                const mapGroup = svg.append("g");

                d3.json(geojsonPath).then(function(geoData) {
                    const projection = d3.geoMercator().fitSize([width - 30, height - 30], geoData);
                    const pathGenerator = d3.geoPath().projection(projection);

                    mapGroup.selectAll("path")
                        .data(geoData.features)
                        .enter()
                        .append("path")
                        .attr("d", pathGenerator)
                        .attr("class", `district-path ${districtClass}`)
                        .on("mouseover", function(event, d) {
                            // Extract District Name
                            const districtName = getDistrictName(d.properties);
                            
                            // Look up the Training Partner count (case insensitive match)
                            const searchKey = districtName.toLowerCase().trim();
                            const activeTPs = tpData[searchKey] || 0; // Show 0 if not found in database

                            // Generate Dynamic HTML for Tooltip
                            const tooltipContent = `
                                <div style="margin-bottom: 5px;">
                                    <i class="fas fa-map-marker-alt" style="color:#fbbf24; margin-right:6px;"></i> 
                                    <strong>${districtName}</strong>
                                </div>
                                <div style="font-size: 12px; color: #cbd5e1; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 6px;">
                                    Registered TPs: <span style="color: #4ade80; font-weight: 800; font-size: 14px;">${activeTPs}</span>
                                </div>
                            `;
                            
                            tooltip.style("opacity", 1).html(tooltipContent);
                        })
                        .on("mousemove", function(event) {
                            tooltip.style("left", (event.pageX + 15) + "px")
                                   .style("top", (event.pageY - 25) + "px");
                        })
                        .on("mouseout", function() {
                            tooltip.style("opacity", 0);
                        });

                }).catch(function(error) {
                    console.error("Map Error for " + containerId + ": ", error);
                });
            }

            // Draw Maps
            drawInteractiveMap("odisha-map-container", "public/data/odisha.geojson", "district-odisha");
            drawInteractiveMap("chhattisgarh-map-container", "public/data/chhattisgarh.geojson", "district-chhattisgarh");
        });
    </script>
</body>
</html>