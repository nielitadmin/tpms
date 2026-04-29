<?php
// Start a clean session
session_name('NIELIT_LANDING');
session_start();

// Clear any existing sessions when coming to the landing page
session_destroy();
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
    
    <style>
        :root {
            /* Premium Color Palette */
            --primary: #155E75;        /* Official NIELIT Blue */
            --primary-light: #0284C7;  
            --primary-bg: #EFF6FF;     
            --candidate: #059669;      
            --candidate-bg: #ECFDF5;
            --tp: #0D9488;
            --tp-bg: #CCFBF1;
            --text-dark: #0F172A;
            --text-muted: #475569;
            --bg-body: #F8FAFC;
            --surface: #FFFFFF;
            --border: #E2E8F0;
            --gold: #D97706;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            --radius-lg: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            background-color: var(--bg-body);
            min-height: 100vh; 
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* --- 1. OFFICIAL TOP HEADER (WHITE) --- */
        .top-header {
            background: #FFFFFF;
            border-bottom: 1px solid var(--border);
            z-index: 100;
            position: relative;
            width: 100%;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1380px; 
            margin: 0 auto;
            padding: 12px 40px;
            width: 100%;
        }
        
        .header-left { display: flex; align-items: center; gap: 15px; }
        .nielit-logo { height: 50px; width: auto; object-fit: contain; }
        
        .header-titles { display: flex; flex-direction: column; }
        .hindi-title { font-family: 'Noto Sans Devanagari', sans-serif; font-size: 15px; color: var(--primary); font-weight: 700; }
        .eng-title { font-size: 13px; font-weight: 600; color: var(--text-dark); }

        .header-right { display: flex; align-items: center; gap: 15px; text-align: right; }
        .ministry-text { display: flex; flex-direction: column; font-size: 11px; color: var(--text-muted); font-weight: 600; }
        .ministry-text strong { font-size: 12px; color: var(--text-dark); }
        .emblem { height: 50px; width: auto; object-fit: contain; margin-left: 5px; }

        /* --- 2. OFFICIAL NAVIGATION BAR (BLUE) --- */
        .main-nav {
            background: var(--primary);
            box-shadow: var(--shadow-sm);
            z-index: 99;
            position: relative;
            width: 100%;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1380px; 
            margin: 0 auto;
            padding: 0 40px;
            width: 100%;
            flex-wrap: wrap;
        }

        .nav-home-btn {
            color: #FFFFFF; text-decoration: none; font-weight: 700; font-size: 15px;
            display: flex; align-items: center; gap: 8px; padding: 15px 0;
            transition: color 0.3s;
        }
        .nav-home-btn:hover { color: #E0F2FE;}
        .nav-custom-icon { height: 18px; width: auto; object-fit: contain; filter: brightness(0) invert(1); }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #FFFFFF;
            font-size: 24px;
            cursor: pointer;
            padding: 10px 0;
        }

        .nav-links { display: flex; height: 100%; align-items: center; }
        .nav-link {
            color: #E0F2FE; text-decoration: none; font-weight: 600; font-size: 14px;
            padding: 16px 20px; transition: 0.3s; display: flex; align-items: center; gap: 6px;
        }
        .nav-link:hover { color: #FFFFFF; background: rgba(255, 255, 255, 0.1); }

        /* Dropdown specific styles */
        .dropdown { position: relative; display: inline-block; height: 100%; }
        .dropbtn {
            background: transparent; color: #E0F2FE; border: none; font-family: inherit;
            font-weight: 600; font-size: 14px; padding: 16px 20px; cursor: pointer;
            display: flex; align-items: center; gap: 6px; transition: 0.3s; height: 100%;
            outline: none;
        }
        .dropdown:hover .dropbtn { color: #FFFFFF; background: rgba(255, 255, 255, 0.1); }
        
        .dropdown-content {
            display: none; position: absolute; background-color: #FFFFFF; min-width: 250px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12); z-index: 200; top: 100%; right: 0;
            overflow: hidden; border: 1px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: 0 0 12px 12px;
            padding: 10px 0;
        }
        .dropdown:hover .dropdown-content { display: block; animation: dropFade 0.2s ease forwards; }
        
        @keyframes dropFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .dropdown-content a {
            color: var(--text-dark); padding: 12px 20px; text-decoration: none; display: flex;
            align-items: flex-start; gap: 10px; font-size: 13.5px; font-weight: 600; transition: 0.2s;
            border-bottom: 1px solid #F1F5F9;
        }
        .dropdown-content a:last-child { border-bottom: none; }
        .dropdown-content a:hover { background-color: var(--primary-bg); color: var(--primary); padding-left: 25px;}
        
        .dropdown-content a i {
            color: var(--primary-light); width: 18px; text-align: center; font-size: 15px; margin-top: 2px;
        }
        .dropdown-content .ac-text { flex: 1; display: flex; flex-direction: column; }
        .dropdown-content .ac-desc { font-weight: 500; font-size: 11px; color: var(--text-muted); margin-top: 2px; line-height: 1.4; }

        /* --- 3. TICKER --- */
        .ticker-wrap {
            background: var(--text-dark); color: white; padding: 6px 0; overflow: hidden; 
            position: relative; z-index: 10; font-size: 12px; font-weight: 600; display: flex; align-items: center;
        }
        .ticker-label { background: var(--gold); color: white; padding: 2px 10px; border-radius: 4px; font-weight: 800; margin: 0 15px; position: relative; z-index: 2; white-space: nowrap; font-size: 11px; letter-spacing: 0.5px;}
        .ticker-move { display: inline-block; white-space: nowrap; animation: ticker 35s linear infinite; }
        @keyframes ticker { 0% { transform: translateX(100vw); } 100% { transform: translateX(-100%); } }

        /* --- 4. 3D AMBIENT BACKGROUND --- */
        .ambient-bg {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1; pointer-events: none;
            background: radial-gradient(circle at 50% 0%, #E0F2FE 0%, #F8FAFC 60%);
            perspective: 1000px;
        }
        .shape {
            position: absolute; background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.2));
            backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 15px 35px rgba(21, 94, 117, 0.05), inset 0 0 20px rgba(255, 255, 255, 0.5);
            animation: float-3d 25s infinite linear;
        }
        .cube { width: 160px; height: 160px; border-radius: 32px; top: 20%; left: 5%; animation-duration: 30s; }
        .ring { width: 240px; height: 240px; border-radius: 50%; border: 40px solid rgba(255,255,255,0.4); top: 50%; right: 2%; animation-duration: 35s; animation-direction: reverse; background: transparent; }
        @keyframes float-3d { 0% { transform: translateY(0) rotateX(0deg) rotateY(0deg) rotateZ(0deg); } 50% { transform: translateY(-40px) rotateX(180deg) rotateY(90deg) rotateZ(45deg); } 100% { transform: translateY(0) rotateX(360deg) rotateY(180deg) rotateZ(90deg); } }

        /* --- 5. SPLIT SCREEN LAYOUT --- */
        .wrapper {
            display: flex; align-items: center; justify-content: space-between;
            max-width: 1300px; margin: 0 auto; width: 100%; padding: 40px 40px 20px 40px; gap: 40px;
            z-index: 10;
        }

        .hero { flex: 1.2; animation: fadeRight 0.8s ease both; max-width: 600px;}
        .hero-title { font-size: 42px; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; line-height: 1.1; margin-bottom: 15px;}
        .hero-title span { color: var(--primary); }
        .hero-sub { font-size: 15px; color: var(--text-muted); font-weight: 500; line-height: 1.6; margin-bottom: 25px;}
        
        .system-badge {
            display: inline-flex; align-items: center; gap: 8px; background: white; border: 1px solid var(--border);
            padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 800;
            color: var(--candidate); box-shadow: var(--shadow-sm); margin-bottom: 30px;
        }
        .live-dot { width: 8px; height: 8px; background: var(--candidate); border-radius: 50%; box-shadow: 0 0 12px var(--candidate); animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.2); } 100% { opacity: 1; transform: scale(1); } }

        .stats-row { display: flex; gap: 15px; flex-wrap: wrap;}
        .stat { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); padding: 15px 20px; border-radius: 16px; border: 1px solid white; flex: 1; min-width: 120px; box-shadow: var(--shadow-sm);}
        .stat-num { font-size: 24px; font-weight: 800; color: var(--text-dark); line-height: 1;}
        .stat-num span { color: var(--primary); }
        .stat-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; margin-top: 6px;}

        /* --- 6. GLASS LOGIN CARD (Replaces Action Cards) --- */
        .login-section { 
            flex: 1; display: flex; flex-direction: column;
            max-width: 450px; width: 100%; animation: fadeLeft 0.8s ease both; animation-delay: 0.2s;
        }
        .glass-login-card {
            background: rgba(255, 255, 255, 0.90);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            padding: 35px 30px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        .glass-login-card::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }
        .glass-login-card h2 { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 5px; text-align: center;}
        .glass-login-card p { font-size: 13px; color: var(--text-muted); text-align: center; margin-bottom: 25px; font-weight: 500;}

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; }
        .form-control { 
            width: 100%; padding: 14px 15px; border: 1px solid var(--border); border-radius: 12px; 
            font-family: inherit; font-size: 14px; background: #F8FAFC; transition: 0.3s; 
        }
        .form-control:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 4px var(--primary-bg); background: white; }
        
        .btn-submit { 
            width: 100%; padding: 15px; background: var(--primary); color: white; border: none; 
            border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; 
            margin-top: 10px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2);
        }
        .btn-submit:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(2, 132, 199, 0.3); }

        .form-footer { text-align: center; margin-top: 20px; font-size: 13px; font-weight: 600; color: var(--text-muted); }
        .form-footer a { color: var(--primary-light); text-decoration: none; transition: 0.2s; }
        .form-footer a:hover { color: var(--primary); text-decoration: underline; }

        /* --- 7. MAPS & PLATFORM DETAILS --- */
        .platform-details {
            max-width: 1300px; margin: 0 auto; width: 100%; padding: 20px 40px 60px 40px;
            z-index: 10; position: relative; animation: fadeUp 0.8s ease both; animation-delay: 0.4s;
        }
        .section-title { font-size: 28px; font-weight: 800; text-align: center; margin-bottom: 30px; letter-spacing: -0.5px; }
        
        .maps-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; margin-bottom: 50px;
        }
        .map-box {
            background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(15px); border: 1px solid white;
            padding: 15px; border-radius: 20px; box-shadow: var(--shadow-sm); transition: 0.3s;
            text-align: center;
        }
        .map-box:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); background: #FFFFFF; }
        .map-box img { width: 100%; height: 260px; object-fit: contain; border-radius: 12px; background: #FAFAFA; padding: 10px; border: 1px solid var(--border);}
        .map-box h4 { margin-top: 15px; font-size: 18px; font-weight: 800; color: var(--text-dark); }
        .map-box p { font-size: 13px; color: var(--text-muted); font-weight: 500; margin-top: 5px; }

        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .feature-box {
            background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border: 1px solid white;
            padding: 25px; border-radius: 20px; box-shadow: var(--shadow-sm); display: flex; gap: 15px;
            transition: 0.3s;
        }
        .feature-box:hover { background: #FFFFFF; transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .f-icon { width: 45px; height: 45px; border-radius: 12px; background: var(--primary-bg); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .f-content h3 { font-size: 16px; font-weight: 800; margin-bottom: 8px; color: var(--text-dark); }
        .f-content p { font-size: 13px; color: var(--text-muted); line-height: 1.5; font-weight: 500;}

        /* --- 8. FOOTER --- */
        .footer { 
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
            padding: 20px 40px; background: white; border-top: 1px solid var(--border);
            font-size: 13px; font-weight: 600; color: var(--text-muted); z-index: 10; margin-top: auto;
        }
        .footer-left { display: flex; flex-direction: column; gap: 4px; }
        .credit-text { font-size: 12px; color: var(--primary-light); font-weight: 700; display: flex; align-items: center; gap: 6px; }
        
        .footer-links { display: flex; gap: 20px; flex-wrap: wrap;}
        .footer-links a { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .footer-links a:hover { color: var(--primary); }

        /* ANIMATIONS */
        @keyframes fadeRight { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeLeft { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* RESPONSIVE MEDIA QUERIES */
        @media (max-width: 1024px) {
            .wrapper { flex-direction: column; padding: 40px 20px 20px 20px; gap: 40px;}
            .hero { max-width: 100%; text-align: center; }
            .hero-title { font-size: 38px; }
            .stats-row { justify-content: center; }
            .platform-details { padding: 20px; }
        }

        @media (max-width: 768px) {
            .header-container { flex-direction: column; gap: 15px; text-align: center; padding: 15px 20px; }
            .header-left, .header-right { flex-direction: column; align-items: center; justify-content: center; text-align: center;}
            .ministry-text { text-align: center; }
            .hindi-title { font-size: 13px; }
            .eng-title { font-size: 12px; }
            
            .nav-container { padding: 10px 20px; }
            .mobile-menu-btn { display: block; }
            .nav-links { display: none; width: 100%; flex-direction: column; align-items: flex-start; padding-bottom: 15px; }
            .nav-links.active { display: flex; }
            .nav-link, .dropbtn { width: 100%; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); justify-content: space-between;}
            .dropdown { width: 100%; }
            .dropdown-content { position: static; box-shadow: none; border: none; border-radius: 8px; background: rgba(0,0,0,0.1); width: 100%; margin-top: 5px; }
            .dropdown-content a { color: #E0F2FE; }
            
            .footer { flex-direction: column; gap: 15px; text-align: center; justify-content: center; }
            .footer-left { align-items: center; }
            .footer-links { justify-content: center; }
        }

        @media (max-width: 480px) {
            .hero-title { font-size: 32px; }
            .stat { min-width: 45%; padding: 10px; } 
            .stat-num { font-size: 20px; }
            .glass-login-card { padding: 25px 20px; }
        }
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
                <img src="assets/images/RR.png" alt="NIELIT Logo" class="nielit-logo">
                <div class="header-titles">
                    <span class="hindi-title">राष्ट्रीय इलेक्ट्रॉनिकी एवं सूचना प्रौद्योगिकी संस्थान, भुवनेश्वर</span>
                    <span class="eng-title">National Institute of Electronics & Information Technology, Bhubaneswar</span>
                </div>
            </div>
            <div class="header-right">
                <div class="ministry-text">
                    <strong>Ministry of Electronics & IT</strong>
                    Government of India
                </div>
                <img src="assets/images/image_7c2b82.png" alt="Government of India Emblem" class="emblem">
            </div>
        </div>
    </header>

    <nav class="main-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-home-btn">
                <img src="assets/images/image_86242d.png" alt="Home" class="nav-custom-icon" onerror="this.outerHTML='<i class=\'fas fa-home\'></i>'"> 
                NIELIT TPS
            </a>
            
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-links" id="navLinks">
                <a href="public/courses.php" class="nav-link">Courses</a>
                <a href="public/notices.php" class="nav-link">Public Notices</a>
                <a href="public/contact.php" class="nav-link">Contact Us</a>
                <a href="tp/tp_signup.php" class="nav-link" style="color: var(--gold);"><i class="fas fa-user-plus"></i> Register Center</a>
            </div>
        </div>
    </nav>

    <div class="ticker-wrap">
        <div class="ticker-label">SYSTEM ALERTS</div>
        <div class="ticker-move">
            &bull; Registration for new Training Partners in Odisha and Chhattisgarh is now open for the upcoming session. 
            &nbsp;&nbsp;&nbsp;&bull; Existing partners must upload their student batch records via CSV before the deadline.
            &nbsp;&nbsp;&nbsp;&bull; Download updated operational guidelines from the Public Notices section.
        </div>
    </div>

    <main class="wrapper">
        
        <div class="hero">
            <div class="system-badge">
                <span class="live-dot"></span> TPMS Portal Online
            </div>
            
            <h1 class="hero-title">Training Partner<br><span>Management System</span></h1>
            <p class="hero-sub">Empowering educational centers across Odisha and Chhattisgarh with streamlined student management, bulk CBT tracking, and direct administration integration. A secure, centralized platform designed exclusively for our authorized Training Partners.</p>

            <div class="stats-row">
                <div class="stat">
                    <div class="stat-num">50<span>+</span></div>
                    <div class="stat-label">Active Centers</div>
                </div>
                <div class="stat">
                    <div class="stat-num">10<span>K+</span></div>
                    <div class="stat-label">Students Tracked</div>
                </div>
                <div class="stat">
                    <div class="stat-num">2</div>
                    <div class="stat-label">Major States</div>
                </div>
            </div>
        </div>

        <div class="login-section">
            <div class="glass-login-card">
                <h2>Portal Access</h2>
                <p>Secure login for active partners & administrators</p>
                
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@center.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Select Role</label>
                        <select class="form-control" id="role" name="role">
                            <option value="tp">Training Partner (TP)</option>
                            <option value="admin">System Administrator</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-submit">Secure Sign In <i class="fas fa-arrow-right" style="margin-left: 5px;"></i></button>
                    
                    <div class="form-footer">
                        Forgot your password? <a href="#">Click here</a>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <section class="platform-details">
        
        <h2 class="section-title">Our Regional Presence</h2>
        <div class="maps-grid">
            <div class="map-box">
                <img src="uploads/images/odisha_map.png" alt="Odisha Regional Map">
                <h4>Odisha State</h4>
                <p>Supporting educational initiatives across all major districts.</p>
            </div>
            <div class="map-box">
                <img src="uploads/images/chhattisgarh_map.png" alt="Chhattisgarh Regional Map">
                <h4>Chhattisgarh State</h4>
                <p>Expanding digital literacy through integrated partner centers.</p>
            </div>
        </div>

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
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }
    </script>
</body>
</html>