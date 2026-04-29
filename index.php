<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Partner System | NIELIT Bhubaneswar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gov-navy:   #1a3a6b;
            --gov-blue:   #1e5fa8;
            --gov-ltblue: #2878c8;
            --gov-orange: #e07b20;
            --gov-saffron:#f4a12a;
            --gov-green:  #177a3c;
            --gov-red:    #c0392b;
            --bg:         #f2f4f7;
            --white:      #ffffff;
            --border:     #cdd4df;
            --text:       #1c2b3a;
            --text-muted: #5a6a7e;
            --radius:     6px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Noto Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        /* TOP STRIP */
        .gov-topstrip {
            background: #1a3a6b;
            padding: 5px 0;
        }
        .gov-topstrip-inner {
            max-width: 1280px; margin: 0 auto; padding: 0 20px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;
        }
        .gov-topstrip a, .gov-topstrip span {
            color: rgba(255,255,255,0.80); font-size: 11.5px; text-decoration: none; padding: 2px 8px;
        }
        .gov-topstrip a:hover { color: #fff; text-decoration: underline; }
        .gov-topstrip .left, .gov-topstrip .right { display: flex; align-items: center; gap: 4px; }
        .topstrip-divider { color: rgba(255,255,255,0.3); }

        /* FLAG BAR */
        .flag-bar {
            height: 4px;
            background: linear-gradient(to right,
                #ff9933 0%, #ff9933 33.33%,
                #ffffff 33.33%, #ffffff 66.66%,
                #138808 66.66%, #138808 100%);
        }

        /* HEADER */
        .site-header {
            background: var(--white);
            border-bottom: 3px solid var(--gov-orange);
            padding: 14px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .header-inner {
            max-width: 1280px; margin: 0 auto; padding: 0 20px;
            display: flex; align-items: center; gap: 20px;
        }
        .header-logo {
            width: 72px; height: 72px; background: var(--gov-navy); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0;
        }
        .header-text { flex: 1; }
        .header-text .ministry { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; }
        .header-text h1 { font-family: 'Noto Serif', serif; font-size: 1.55rem; font-weight: 700; color: var(--gov-navy); line-height: 1.2; margin: 3px 0; }
        .header-text .subtitle { font-size: 12px; color: var(--text-muted); }
        .header-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
        .header-right .emblem { font-size: 2.6rem; line-height: 1; }
        .header-right .tagline { font-size: 11px; color: var(--gov-navy); font-weight: 600; letter-spacing: 0.05em; }

        /* MAIN NAV */
        .main-nav { background: var(--gov-navy); }
        .nav-inner {
            max-width: 1280px; margin: 0 auto; padding: 0 20px;
            display: flex; align-items: center; flex-wrap: wrap;
        }
        .nav-inner a {
            color: rgba(255,255,255,0.88); text-decoration: none; padding: 12px 18px;
            font-size: 13px; font-weight: 500; border-right: 1px solid rgba(255,255,255,0.1);
            transition: background 0.15s; display: inline-flex; align-items: center; gap: 6px;
        }
        .nav-inner a:first-child { border-left: 1px solid rgba(255,255,255,0.1); }
        .nav-inner a:hover { background: var(--gov-ltblue); color: #fff; }
        .nav-inner a.active { background: var(--gov-orange); }
        .nav-spacer { flex: 1; }
        .nav-inner .nav-login-btn {
            background: var(--gov-orange); color: #fff; padding: 10px 22px; font-weight: 600;
            border-radius: 3px; margin: 6px 0; border-right: none !important; border-left: none !important;
        }
        .nav-inner .nav-login-btn:hover { background: #c96a10; }

        /* NOTICE TICKER */
        .notice-ticker {
            background: #fef9ec; border-top: 1px solid #e8d89a;
            border-bottom: 2px solid var(--gov-orange); padding: 7px 0; overflow: hidden;
        }
        .ticker-inner { max-width: 1280px; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; gap: 14px; }
        .ticker-label {
            background: var(--gov-red); color: #fff; font-size: 11px; font-weight: 700;
            padding: 3px 10px; border-radius: 3px; letter-spacing: 0.06em; white-space: nowrap; flex-shrink: 0;
        }
        .ticker-track { flex: 1; overflow: hidden; }
        .ticker-scroll {
            display: inline-flex; gap: 60px; white-space: nowrap;
            animation: tickerMove 35s linear infinite;
        }
        .ticker-scroll span { font-size: 12.5px; color: var(--text); }
        .ticker-scroll span a { color: var(--gov-blue); text-decoration: none; }
        .ticker-scroll span a:hover { text-decoration: underline; }
        @keyframes tickerMove { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }

        /* BREADCRUMB */
        .breadcrumb-bar { background: #e8ecf4; border-bottom: 1px solid var(--border); padding: 7px 0; }
        .breadcrumb-inner { max-width: 1280px; margin: 0 auto; padding: 0 20px; font-size: 12px; color: var(--text-muted); }
        .breadcrumb-inner a { color: var(--gov-blue); text-decoration: none; }
        .breadcrumb-inner a:hover { text-decoration: underline; }
        .breadcrumb-inner .sep { margin: 0 6px; color: #aaa; }

        /* HERO BANNER */
        .hero-banner {
            background: linear-gradient(135deg, #1a3a6b 0%, #1e5fa8 50%, #2878c8 100%);
            padding: 40px 0 36px; border-bottom: 4px solid var(--gov-orange);
            position: relative; overflow: hidden;
        }
        .hero-banner::before {
            content: ''; position: absolute; right: -60px; top: -60px;
            width: 320px; height: 320px; border: 40px solid rgba(255,255,255,0.04); border-radius: 50%;
        }
        .hero-inner { max-width: 1280px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 2; }
        .hero-inner h2 {
            font-family: 'Noto Serif', serif; font-size: 1.8rem; font-weight: 700;
            color: #fff; line-height: 1.3; margin-bottom: 10px;
        }
        .hero-inner p { color: rgba(255,255,255,0.75); font-size: 13.5px; max-width: 600px; line-height: 1.7; margin-bottom: 22px; }
        .hero-btn {
            display: inline-block; background: var(--gov-orange); color: #fff; text-decoration: none;
            font-weight: 600; font-size: 13px; padding: 10px 26px; border-radius: var(--radius);
            border: 2px solid rgba(255,255,255,0.2); transition: background 0.2s;
        }
        .hero-btn:hover { background: #c96a10; }
        .hero-stats-row {
            display: flex; margin-top: 30px; background: rgba(0,0,0,0.25);
            border-radius: var(--radius); overflow: hidden; border: 1px solid rgba(255,255,255,0.1);
            max-width: 560px;
        }
        .hero-stat { flex: 1; padding: 14px 16px; text-align: center; border-right: 1px solid rgba(255,255,255,0.1); }
        .hero-stat:last-child { border-right: none; }
        .hero-stat .num { font-family: 'Noto Serif', serif; font-size: 1.5rem; font-weight: 700; color: var(--gov-saffron); line-height: 1; }
        .hero-stat .lbl { font-size: 10.5px; color: rgba(255,255,255,0.6); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em; }

        /* PAGE BODY */
        .page-body {
            max-width: 1280px; margin: 28px auto; padding: 0 20px;
            display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start;
        }

        /* GOV BOX */
        .gov-box {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        .gov-box-header {
            background: var(--gov-navy); padding: 10px 18px;
            display: flex; align-items: center; gap: 10px;
        }
        .gov-box-header.orange { background: var(--gov-orange); }
        .gov-box-header.green  { background: var(--gov-green); }
        .gov-box-header.gray   { background: #4a5568; }
        .gov-box-header h3 { font-size: 13.5px; font-weight: 600; color: #fff; letter-spacing: 0.02em; }
        .gov-box-header .icon { font-size: 1.1rem; }
        .gov-box-body { padding: 20px 18px; }

        /* MAPS */
        .maps-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .map-card { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; background: #f8faff; transition: box-shadow 0.2s; }
        .map-card:hover { box-shadow: 0 4px 14px rgba(26,58,107,0.12); }
        .map-img-wrap {
            background: #eef2fa; padding: 12px;
            display: flex; align-items: center; justify-content: center; height: 190px;
            border-bottom: 1px solid var(--border);
        }
        .map-img-wrap img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .map-label { padding: 8px 14px; display: flex; align-items: center; justify-content: space-between; background: #fff; }
        .map-label .name { font-weight: 600; font-size: 13px; color: var(--gov-navy); }
        .map-label .badge {
            background: #e6f4ee; color: var(--gov-green); font-size: 10px; font-weight: 700;
            padding: 3px 9px; border-radius: 3px; border: 1px solid #b2ddc8; text-transform: uppercase; letter-spacing: 0.05em;
        }

        /* INFO TABLE */
        .info-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .info-table tr { border-bottom: 1px solid #eef0f5; }
        .info-table tr:last-child { border-bottom: none; }
        .info-table td { padding: 9px 12px; }
        .info-table td:first-child { color: var(--text-muted); font-weight: 500; width: 40%; background: #f8f9fc; }
        .info-table td:last-child { color: var(--text); font-weight: 600; }

        /* FEATURES */
        .feature-list { list-style: none; }
        .feature-list li { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f2f7; font-size: 13px; }
        .feature-list li:last-child { border-bottom: none; }
        .feat-icon { width: 36px; height: 36px; flex-shrink: 0; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .feat-icon.blue   { background: #ddeeff; }
        .feat-icon.green  { background: #dff5ea; }
        .feat-icon.orange { background: #fef0dd; }
        .feat-text strong { display: block; font-weight: 600; color: var(--gov-navy); margin-bottom: 2px; }
        .feat-text span { color: var(--text-muted); font-size: 12px; line-height: 1.5; }

        /* LOGIN FORM */
        .login-box { position: sticky; top: 16px; }
        .login-form-wrap { padding: 20px 18px 22px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
        .form-group input, .form-group select {
            width: 100%; border: 1px solid var(--border); border-radius: var(--radius);
            padding: 9px 12px; font-family: 'Noto Sans', sans-serif; font-size: 13px;
            color: var(--text); background: #f8f9fc; outline: none; transition: border-color 0.2s, box-shadow 0.2s; -webkit-appearance: none;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--gov-blue); background: #fff; box-shadow: 0 0 0 3px rgba(30,95,168,0.12);
        }
        .role-select-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
        .role-opt {
            border: 1.5px solid var(--border); border-radius: var(--radius);
            padding: 10px 8px; text-align: center; cursor: pointer; transition: all 0.15s; background: #f8f9fc;
        }
        .role-opt input { display: none; }
        .role-opt .r-icon { font-size: 1.3rem; display: block; margin-bottom: 3px; }
        .role-opt .r-label { font-size: 11.5px; font-weight: 600; color: var(--text-muted); }
        .role-opt.active { border-color: var(--gov-blue); background: #e8f0fb; }
        .role-opt.active .r-label { color: var(--gov-navy); }
        .btn-signin {
            width: 100%; background: var(--gov-navy); color: #fff; border: none;
            border-radius: var(--radius); padding: 11px; font-family: 'Noto Sans', sans-serif;
            font-size: 13.5px; font-weight: 600; cursor: pointer; letter-spacing: 0.03em; transition: background 0.2s;
        }
        .btn-signin:hover { background: var(--gov-blue); }
        .login-links { display: flex; justify-content: space-between; margin-top: 12px; }
        .login-links a { font-size: 11.5px; color: var(--gov-blue); text-decoration: none; }
        .login-links a:hover { text-decoration: underline; }
        .login-note {
            background: #f0f5ff; border: 1px solid #c5d7f5; border-radius: var(--radius);
            padding: 10px 14px; font-size: 11.5px; color: var(--gov-navy); margin-top: 14px; line-height: 1.6;
        }
        .login-note strong { display: block; margin-bottom: 3px; }

        /* QUICK LINKS */
        .quick-links-list { list-style: none; }
        .quick-links-list li { border-bottom: 1px solid #f0f2f7; }
        .quick-links-list li:last-child { border-bottom: none; }
        .quick-links-list a {
            display: flex; align-items: center; gap: 10px; padding: 10px 18px;
            text-decoration: none; color: var(--gov-blue); font-size: 12.5px; font-weight: 500; transition: background 0.15s;
        }
        .quick-links-list a:hover { background: #f0f5ff; color: var(--gov-navy); }
        .quick-links-list .arr { color: #aaa; margin-left: auto; font-size: 11px; }

        /* HELPDESK */
        .helpdesk-box { padding: 16px 18px; }
        .helpdesk-row { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f0f2f7; font-size: 12.5px; }
        .helpdesk-row:last-child { border-bottom: none; }
        .helpdesk-row .hd-icon { font-size: 1.1rem; flex-shrink: 0; }
        .helpdesk-row .hd-label { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
        .helpdesk-row .hd-value { font-weight: 600; color: var(--text); }

        /* FOOTER */
        .site-footer { background: var(--gov-navy); color: rgba(255,255,255,0.7); margin-top: 36px; border-top: 4px solid var(--gov-orange); }
        .footer-main {
            max-width: 1280px; margin: 0 auto; padding: 28px 20px;
            display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 32px;
        }
        .footer-about h4 { font-family: 'Noto Serif', serif; font-size: 1rem; color: #fff; margin-bottom: 10px; }
        .footer-about p { font-size: 12px; line-height: 1.7; }
        .footer-col h5 { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.9); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.06em; }
        .footer-col a { display: block; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 12px; margin-bottom: 6px; }
        .footer-col a:hover { color: #fff; }
        .footer-bottom {
            background: rgba(0,0,0,0.2); text-align: center; padding: 12px 20px;
            font-size: 11.5px; color: rgba(255,255,255,0.5); border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .page-body { grid-template-columns: 1fr; }
            .login-box { position: static; }
            .footer-main { grid-template-columns: 1fr; gap: 20px; }
        }
        @media (max-width: 600px) {
            .maps-row { grid-template-columns: 1fr; }
            .header-right { display: none; }
            .hero-stats-row { flex-wrap: wrap; }
            .nav-inner a:not(.nav-login-btn) { font-size: 12px; padding: 10px 10px; }
        }
    </style>
</head>
<body>

<!-- TOP STRIP -->
<div class="gov-topstrip">
    <div class="gov-topstrip-inner">
        <div class="left">
            <span>🇮🇳 Government of India</span>
            <span class="topstrip-divider">|</span>
            <a href="#">Ministry of Electronics &amp; IT</a>
            <span class="topstrip-divider">|</span>
            <a href="#">NIELIT Official Website</a>
        </div>
        <div class="right">
            <a href="#">Skip to Main Content</a>
            <span class="topstrip-divider">|</span>
            <a href="#">Screen Reader</a>
            <span class="topstrip-divider">|</span>
            <a href="#">हिंदी</a>
        </div>
    </div>
</div>

<!-- FLAG BAR -->
<div class="flag-bar"></div>

<!-- HEADER -->
<header class="site-header">
    <div class="header-inner">
        <div class="header-logo">🖥️</div>
        <div class="header-text">
            <div class="ministry">National Institute of Electronics &amp; Information Technology</div>
            <h1>Training Partner Management System</h1>
            <div class="subtitle">NIELIT Bhubaneswar — Odisha &amp; Chhattisgarh Region</div>
        </div>
        <div class="header-right">
            <div class="emblem">⚖️</div>
            <div class="tagline">सत्यमेव जयते</div>
        </div>
    </div>
</header>

<!-- MAIN NAV -->
<nav class="main-nav">
    <div class="nav-inner">
        <a href="index.php" class="active">🏠 Home</a>
        <a href="public/courses.php">📚 Courses</a>
        <a href="public/notices.php">📢 Public Notices</a>
        <a href="public/contact.php">📞 Contact Us</a>
        <a href="tp/tp_signup.php">🏫 Register Center</a>
        <div class="nav-spacer"></div>
        <a href="#login-section" class="nav-login-btn">🔐 Partner Login</a>
    </div>
</nav>

<!-- NOTICE TICKER -->
<div class="notice-ticker">
    <div class="ticker-inner">
        <span class="ticker-label">📢 NOTICE</span>
        <div class="ticker-track">
            <div class="ticker-scroll">
                <span>📌 New batch registration open for O Level &amp; A Level courses — <a href="#">View Details</a></span>
                <span>📌 CBT Schedule for Jan–Mar 2025 published — <a href="#">Download PDF</a></span>
                <span>📌 All Training Partners to update center profiles by 31st Jan 2025 — <a href="#">Click Here</a></span>
                <span>📌 Revised fee structure effective from 1st Feb 2025 — <a href="#">Read Circular</a></span>
            </div>
        </div>
    </div>
</div>

<!-- BREADCRUMB -->
<div class="breadcrumb-bar">
    <div class="breadcrumb-inner">
        <a href="#">Home</a>
        <span class="sep">›</span>
        Training Partner System
    </div>
</div>

<!-- HERO BANNER -->
<section class="hero-banner">
    <div class="hero-inner">
        <h2>Training Partner Management System<br>NIELIT Bhubaneswar</h2>
        <p>A centralized digital platform for managing training partners, student enrollments, CBT examination scheduling, and administrative operations across Odisha and Chhattisgarh.</p>
        <a href="tp/tp_signup.php" class="hero-btn">Register New Training Center →</a>
        <div class="hero-stats-row">
            <div class="hero-stat"><div class="num">2</div><div class="lbl">States</div></div>
            <div class="hero-stat"><div class="num">50+</div><div class="lbl">Centers</div></div>
            <div class="hero-stat"><div class="num">1000+</div><div class="lbl">Students</div></div>
            <div class="hero-stat"><div class="num">10+</div><div class="lbl">Courses</div></div>
        </div>
    </div>
</section>

<!-- PAGE BODY -->
<main class="page-body" id="login-section">

    <!-- LEFT COLUMN -->
    <div>
        <!-- REGIONAL PRESENCE -->
        <div class="gov-box" style="margin-bottom:22px;">
            <div class="gov-box-header">
                <span class="icon">🗺️</span>
                <h3>Regional Presence — Odisha &amp; Chhattisgarh</h3>
            </div>
            <div class="gov-box-body">
                <div class="maps-row">
                    <div class="map-card">
                        <div class="map-img-wrap">
                            <img src="odisha_map.png" alt="Map of Odisha">
                        </div>
                        <div class="map-label">
                            <span class="name">🗺️ Odisha State</span>
                            <span class="badge">Active</span>
                        </div>
                    </div>
                    <div class="map-card">
                        <div class="map-img-wrap">
                            <img src="chhattisgarh_map.png" alt="Map of Chhattisgarh">
                        </div>
                        <div class="map-label">
                            <span class="name">🗺️ Chhattisgarh State</span>
                            <span class="badge">Active</span>
                        </div>
                    </div>
                </div>
                <table class="info-table">
                    <tr><td>Nodal Centre</td><td>NIELIT Bhubaneswar, Odisha</td></tr>
                    <tr><td>States Covered</td><td>Odisha, Chhattisgarh</td></tr>
                    <tr><td>Courses Offered</td><td>O Level, A Level, B Level, CCC, BCC, ECC</td></tr>
                    <tr><td>Accreditation</td><td>NIELIT HQ, New Delhi</td></tr>
                </table>
            </div>
        </div>

        <!-- FEATURES -->
        <div class="gov-box">
            <div class="gov-box-header orange">
                <span class="icon">⚙️</span>
                <h3>System Features &amp; Services</h3>
            </div>
            <div class="gov-box-body" style="padding-top:10px;padding-bottom:10px;">
                <ul class="feature-list">
                    <li>
                        <div class="feat-icon blue">📁</div>
                        <div class="feat-text">
                            <strong>Bulk Student Data Upload</strong>
                            <span>Upload student records and photographs in bulk via standardized CSV format, mapped to active NIELIT courses.</span>
                        </div>
                    </li>
                    <li>
                        <div class="feat-icon green">📢</div>
                        <div class="feat-text">
                            <strong>Real-Time Official Notices</strong>
                            <span>Receive PDF circulars, syllabus updates, exam schedules, and administrative guidelines from NIELIT directly.</span>
                        </div>
                    </li>
                    <li>
                        <div class="feat-icon orange">📊</div>
                        <div class="feat-text">
                            <strong>CBT Examination Tracking</strong>
                            <span>Track student test appearances, results, placement records, and campus activity reports through the dashboard.</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div>
        <!-- LOGIN -->
        <div class="gov-box login-box" style="margin-bottom:20px;">
            <div class="gov-box-header">
                <span class="icon">🔐</span>
                <h3>Partner Portal Login</h3>
            </div>
            <div class="login-form-wrap">
                <form action="login.php" method="POST">
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">Select Role</label>
                        <div class="role-select-row">
                            <label class="role-opt active">
                                <input type="radio" name="role" value="tp" checked>
                                <span class="r-icon">🏫</span>
                                <span class="r-label">Training Partner</span>
                            </label>
                            <label class="role-opt">
                                <input type="radio" name="role" value="admin">
                                <span class="r-icon">⚙️</span>
                                <span class="r-label">Administrator</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter registered email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>
                    <button type="submit" class="btn-signin">Sign In to Portal</button>
                    <div class="login-links">
                        <a href="#">Forgot Password?</a>
                        <a href="tp/tp_signup.php">New Registration →</a>
                    </div>
                </form>
                <div class="login-note">
                    <strong>⚠️ Important Notice</strong>
                    Use only your official NIELIT-registered email address. For access issues, contact the helpdesk.
                </div>
            </div>
        </div>

        <!-- QUICK LINKS -->
        <div class="gov-box" style="margin-bottom:20px;">
            <div class="gov-box-header green">
                <span class="icon">🔗</span>
                <h3>Quick Links</h3>
            </div>
            <ul class="quick-links-list">
                <li><a href="public/courses.php">📚 View All Courses <span class="arr">›</span></a></li>
                <li><a href="public/notices.php">📄 Download Notices / Circulars <span class="arr">›</span></a></li>
                <li><a href="tp/tp_signup.php">🏫 Register Training Center <span class="arr">›</span></a></li>
                <li><a href="public/contact.php">📞 Contact NIELIT Bhubaneswar <span class="arr">›</span></a></li>
                <li><a href="#">🌐 NIELIT Official Website <span class="arr">›</span></a></li>
            </ul>
        </div>

        <!-- HELPDESK -->
        <div class="gov-box">
            <div class="gov-box-header gray">
                <span class="icon">📞</span>
                <h3>Helpdesk &amp; Support</h3>
            </div>
            <div class="helpdesk-box">
                <div class="helpdesk-row">
                    <span class="hd-icon">📞</span>
                    <div>
                        <div class="hd-label">Phone</div>
                        <div class="hd-value">0674-XXXXXXX</div>
                    </div>
                </div>
                <div class="helpdesk-row">
                    <span class="hd-icon">✉️</span>
                    <div>
                        <div class="hd-label">Email</div>
                        <div class="hd-value">tps@nielit.gov.in</div>
                    </div>
                </div>
                <div class="helpdesk-row">
                    <span class="hd-icon">🕐</span>
                    <div>
                        <div class="hd-label">Working Hours</div>
                        <div class="hd-value">Mon–Fri, 10:00 AM – 5:30 PM</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="footer-main">
        <div class="footer-about">
            <h4>NIELIT Bhubaneswar — TPS Portal</h4>
            <p>The Training Partner Management System is a centralized portal developed for NIELIT Bhubaneswar to manage its network of accredited training centers across Odisha and Chhattisgarh. It supports student enrollment, CBT tracking, and administrative operations.</p>
        </div>
        <div class="footer-col">
            <h5>Useful Links</h5>
            <a href="#">NIELIT HQ</a>
            <a href="#">Ministry of MeitY</a>
            <a href="#">Digital India</a>
            <a href="#">NIC</a>
            <a href="#">India.gov.in</a>
        </div>
        <div class="footer-col">
            <h5>Portal Links</h5>
            <a href="public/courses.php">Courses</a>
            <a href="public/notices.php">Notices</a>
            <a href="tp/tp_signup.php">Register Center</a>
            <a href="public/contact.php">Contact Us</a>
            <a href="#">Sitemap</a>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> NIELIT Bhubaneswar. All Rights Reserved. | Content owned by NIELIT Bhubaneswar | Designed &amp; Developed for Regional Training Partners | Last Updated: <?= date('d M Y') ?>
    </div>
</footer>

<script>
    document.querySelectorAll('.role-opt').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.role-opt').forEach(o => o.classList.remove('active'));
            opt.classList.add('active');
        });
    });
</script>

</body>
</html>