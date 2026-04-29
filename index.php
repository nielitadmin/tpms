<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Partner System — NIELIT Bhubaneswar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0a0e17;
            --ink-2: #1c2333;
            --surface: #f0f3fa;
            --card: #ffffff;
            --accent: #00c6ff;
            --accent-2: #0057ff;
            --gold: #f5b800;
            --muted: #6b7794;
            --border: rgba(255,255,255,0.10);
            --radius: 20px;
            --radius-sm: 12px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            color: var(--ink);
            overflow-x: hidden;
        }

        /* ─── NAV ─────────────────────────────────────────────── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 5vw;
            background: rgba(10,14,23,0.75);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
        }
        .nav-logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.35rem;
            color: #fff;
            text-decoration: none;
            letter-spacing: -0.02em;
        }
        .nav-logo span { color: var(--accent); }
        .nav-links { display: flex; align-items: center; gap: 8px; }
        .nav-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 100px;
            transition: all 0.2s;
        }
        .nav-links a:hover { color: #fff; background: rgba(255,255,255,0.08); }
        .nav-links .cta {
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            color: #fff !important;
            font-weight: 600;
            padding: 8px 22px;
        }
        .nav-links .cta:hover { opacity: 0.88; background: linear-gradient(135deg, var(--accent-2), var(--accent)); }

        /* ─── HERO ────────────────────────────────────────────── */
        .hero {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            text-align: center;
            padding: 130px 5vw 80px;
            background: var(--ink);
            position: relative;
            overflow: hidden;
        }
        /* Animated mesh background */
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 40%, rgba(0,87,255,0.28) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 70%, rgba(0,198,255,0.22) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 50% 10%, rgba(245,184,0,0.10) 0%, transparent 60%);
            animation: meshShift 12s ease-in-out infinite alternate;
        }
        @keyframes meshShift {
            0%   { transform: scale(1) translate(0,0); }
            100% { transform: scale(1.08) translate(2%, 2%); }
        }
        /* Grid overlay */
        .hero::after {
            content: '';
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }
        .hero-inner { position: relative; z-index: 2; max-width: 820px; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(0,198,255,0.12);
            border: 1px solid rgba(0,198,255,0.3);
            color: var(--accent);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 7px 18px;
            border-radius: 100px;
            margin-bottom: 28px;
            animation: fadeUp 0.7s ease both;
        }
        .hero-badge .dot {
            width: 7px; height: 7px;
            background: var(--accent);
            border-radius: 50%;
            animation: blink 1.5s ease infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.6rem, 6vw, 5rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.08;
            letter-spacing: -0.03em;
            margin-bottom: 22px;
            animation: fadeUp 0.7s 0.1s ease both;
        }
        .hero h1 em {
            font-style: normal;
            background: linear-gradient(90deg, var(--accent), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.55);
            line-height: 1.7;
            max-width: 580px;
            margin: 0 auto 40px;
            font-weight: 300;
            animation: fadeUp 0.7s 0.2s ease both;
        }
        .hero-cta {
            display: flex; align-items: center; justify-content: center; gap: 14px;
            flex-wrap: wrap;
            animation: fadeUp 0.7s 0.3s ease both;
        }
        .btn-primary-hero {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 14px 32px;
            border-radius: 100px;
            transition: all 0.25s;
            box-shadow: 0 8px 32px rgba(0,87,255,0.35);
        }
        .btn-primary-hero:hover { transform: translateY(-2px); box-shadow: 0 14px 40px rgba(0,87,255,0.45); }
        .btn-ghost-hero {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 14px 28px;
            border-radius: 100px;
            transition: all 0.25s;
        }
        .btn-ghost-hero:hover { background: rgba(255,255,255,0.12); color: #fff; }

        /* Hero stats */
        .hero-stats {
            display: flex; justify-content: center; gap: 48px;
            margin-top: 64px;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.08);
            animation: fadeUp 0.7s 0.4s ease both;
            flex-wrap: wrap;
        }
        .stat-item { text-align: center; }
        .stat-num {
            font-family: 'Syne', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }
        .stat-num span { color: var(--accent); }
        .stat-label { font-size: 0.78rem; color: rgba(255,255,255,0.4); margin-top: 5px; letter-spacing: 0.05em; text-transform: uppercase; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── MAIN SECTION ────────────────────────────────────── */
        .main-section {
            padding: 90px 5vw;
            max-width: 1280px;
            margin: 0 auto;
        }

        .section-tag {
            display: inline-block;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent-2);
            margin-bottom: 12px;
        }
        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: clamp(1.7rem, 3vw, 2.5rem);
            font-weight: 800;
            color: var(--ink);
            line-height: 1.15;
            letter-spacing: -0.03em;
        }

        /* ─── MAPS & LOGIN ROW ───────────────────────────────── */
        .content-row {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 32px;
            align-items: start;
        }

        /* Maps Panel */
        .maps-panel {}
        .maps-header { margin-bottom: 28px; }
        .maps-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .map-card {
            background: var(--card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .map-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,0.12); }
        .map-img-wrap {
            background: #f8faff;
            display: flex; align-items: center; justify-content: center;
            padding: 16px;
            height: 220px;
        }
        .map-img-wrap img {
            max-width: 100%; max-height: 100%;
            object-fit: contain;
        }
        .map-footer {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px;
            background: #fff;
            border-top: 1px solid #f0f2f8;
        }
        .map-footer .label {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--ink);
        }
        .map-footer .pill {
            background: linear-gradient(135deg,#e8f0ff,#dceeff);
            color: var(--accent-2);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 100px;
            letter-spacing: 0.04em;
        }

        /* Presence strip */
        .presence-strip {
            margin-top: 22px;
            background: linear-gradient(135deg, var(--ink), var(--ink-2));
            border-radius: var(--radius-sm);
            padding: 20px 24px;
            display: flex; align-items: center; gap: 16px;
        }
        .presence-icon { font-size: 1.8rem; flex-shrink: 0; }
        .presence-text { color: rgba(255,255,255,0.6); font-size: 0.88rem; line-height: 1.5; }
        .presence-text strong { color: #fff; display: block; font-weight: 600; margin-bottom: 2px; }

        /* ─── LOGIN CARD ──────────────────────────────────────── */
        .login-panel {}
        .login-card {
            background: var(--card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            position: sticky;
            top: 90px;
        }
        .login-card-head {
            background: linear-gradient(135deg, #0a0e17 0%, #0d2050 60%, #0057ff 100%);
            padding: 36px 36px 28px;
            position: relative;
            overflow: hidden;
        }
        .login-card-head::before {
            content: '';
            position: absolute;
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(0,198,255,0.2) 0%, transparent 70%);
            right: -60px; top: -60px;
            border-radius: 50%;
        }
        .login-card-head .shield {
            width: 52px; height: 52px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }
        .login-card-head h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }
        .login-card-head p { color: rgba(255,255,255,0.5); font-size: 0.85rem; }

        .login-card-body { padding: 32px 36px 36px; }

        .field-group { margin-bottom: 22px; }
        .field-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .field-wrap { position: relative; }
        .field-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            font-size: 1rem; opacity: 0.4; pointer-events: none;
        }
        .field-wrap input, .field-wrap select {
            width: 100%;
            background: #f5f7fc;
            border: 1.5px solid #e4e9f4;
            border-radius: var(--radius-sm);
            padding: 13px 16px 13px 44px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.93rem;
            color: var(--ink);
            outline: none;
            transition: all 0.2s;
            -webkit-appearance: none;
        }
        .field-wrap input::placeholder { color: #b0b8cc; }
        .field-wrap input:focus, .field-wrap select:focus {
            border-color: var(--accent-2);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0,87,255,0.1);
        }
        .field-wrap select { cursor: pointer; }

        .role-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 28px;
        }
        .role-tab {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            padding: 11px;
            border-radius: var(--radius-sm);
            border: 1.5px solid #e4e9f4;
            background: #f5f7fc;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--muted);
            transition: all 0.2s;
        }
        .role-tab input[type="radio"] { display: none; }
        .role-tab.active, .role-tab:has(input:checked) {
            border-color: var(--accent-2);
            background: linear-gradient(135deg, #eef2ff, #e6f3ff);
            color: var(--accent-2);
        }
        .role-tab .tab-icon { font-size: 1.1rem; }

        .btn-signin {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            color: #fff;
            border: none;
            border-radius: 100px;
            padding: 15px;
            font-family: 'Syne', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.02em;
            transition: all 0.25s;
            box-shadow: 0 8px 24px rgba(0,87,255,0.28);
        }
        .btn-signin:hover { transform: translateY(-2px); box-shadow: 0 14px 36px rgba(0,87,255,0.38); }

        .forgot { display: block; text-align: center; margin-top: 16px; color: var(--muted); font-size: 0.82rem; text-decoration: none; }
        .forgot:hover { color: var(--accent-2); }

        /* ─── FEATURES ────────────────────────────────────────── */
        .features-section {
            background: var(--ink);
            padding: 90px 5vw;
        }
        .features-inner {
            max-width: 1280px;
            margin: 0 auto;
        }
        .features-head {
            text-align: center;
            margin-bottom: 56px;
        }
        .features-head .section-tag { color: var(--accent); }
        .features-head .section-title { color: #fff; }
        .features-head p { color: rgba(255,255,255,0.4); margin-top: 12px; font-size: 1rem; max-width: 480px; margin-left: auto; margin-right: auto; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .feature-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: var(--radius);
            padding: 36px 30px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,87,255,0.08) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .feature-card:hover { transform: translateY(-6px); border-color: rgba(0,198,255,0.25); }
        .feature-card:hover::before { opacity: 1; }

        .feature-icon {
            width: 54px; height: 54px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 22px;
        }
        .fi-blue  { background: rgba(0,87,255,0.18); }
        .fi-green { background: rgba(0,210,120,0.18); }
        .fi-gold  { background: rgba(245,184,0,0.18); }

        .feature-card h4 {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
        }
        .feature-card p { color: rgba(255,255,255,0.45); font-size: 0.88rem; line-height: 1.7; }

        .feature-card .feature-tag {
            display: inline-block;
            margin-top: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 100px;
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.35);
        }

        /* ─── FOOTER ──────────────────────────────────────────── */
        footer {
            background: #050810;
            color: rgba(255,255,255,0.4);
            text-align: center;
            padding: 36px 5vw;
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
        }
        footer strong { color: rgba(255,255,255,0.75); }

        /* ─── RESPONSIVE ──────────────────────────────────────── */
        @media (max-width: 1024px) {
            .content-row { grid-template-columns: 1fr; }
            .login-card { position: static; }
            .maps-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .features-grid { grid-template-columns: 1fr; }
            .maps-grid { grid-template-columns: 1fr; }
            .nav-links a:not(.cta) { display: none; }
            .hero-stats { gap: 28px; }
        }
    </style>
</head>
<body>

<!-- ═══ NAV ═══════════════════════════════════════════════════ -->
<nav>
    <a class="nav-logo" href="index.php"><span>NIELIT</span> TPS</a>
    <div class="nav-links">
        <a href="public/courses.php">Courses</a>
        <a href="public/notices.php">Notices</a>
        <a href="public/contact.php">Contact</a>
        <a href="#login-section" class="cta">Sign In →</a>
    </div>
</nav>

<!-- ═══ HERO ═══════════════════════════════════════════════════ -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge">
            <span class="dot"></span>
            NIELIT Bhubaneswar — Official Portal
        </div>
        <h1>Training Partner<br><em>Management System</em></h1>
        <p>A unified platform empowering educational centers across Odisha & Chhattisgarh with streamlined enrollment, CBT tracking, and real-time administration.</p>
        <div class="hero-cta">
            <a href="tp/tp_signup.php" class="btn-primary-hero">🏫 Register Your Center</a>
            <a href="#login-section" class="btn-ghost-hero">Sign In to Portal</a>
        </div>
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-num">2<span>+</span></div>
                <div class="stat-label">States Covered</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">50<span>+</span></div>
                <div class="stat-label">Training Partners</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">1000<span>+</span></div>
                <div class="stat-label">Students Enrolled</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">10<span>+</span></div>
                <div class="stat-label">Active Courses</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ MAIN CONTENT ═══════════════════════════════════════════ -->
<section class="main-section" id="login-section">
    <div class="content-row">

        <!-- MAPS PANEL -->
        <div class="maps-panel">
            <div class="maps-header">
                <span class="section-tag">Regional Presence</span>
                <h2 class="section-title">Our Operational<br>Footprint</h2>
            </div>
            <div class="maps-grid">
                <div class="map-card">
                    <div class="map-img-wrap">
                        <img src="odisha_map.png" alt="Map of Odisha">
                    </div>
                    <div class="map-footer">
                        <span class="label">🗺️ Odisha</span>
                        <span class="pill">Active</span>
                    </div>
                </div>
                <div class="map-card">
                    <div class="map-img-wrap">
                        <img src="chhattisgarh_map.png" alt="Map of Chhattisgarh">
                    </div>
                    <div class="map-footer">
                        <span class="label">🗺️ Chhattisgarh</span>
                        <span class="pill">Active</span>
                    </div>
                </div>
            </div>
            <div class="presence-strip">
                <div class="presence-icon">📡</div>
                <div class="presence-text">
                    <strong>Pan-Regional Operations</strong>
                    Serving key districts across both states with certified NIELIT courses, computer-based testing, and placement support.
                </div>
            </div>
        </div>

        <!-- LOGIN PANEL -->
        <div class="login-panel">
            <div class="login-card">
                <div class="login-card-head">
                    <div class="shield">🔐</div>
                    <h2>System Portal</h2>
                    <p>Secure access for authorized partners</p>
                </div>
                <div class="login-card-body">
                    <form action="login.php" method="POST">

                        <!-- Role Selection -->
                        <div style="margin-bottom:24px;">
                            <span class="field-label">Select Role</span>
                            <div class="role-tabs">
                                <label class="role-tab active" onclick="this.classList.add('active'); this.nextElementSibling.classList.remove('active')">
                                    <input type="radio" name="role" value="tp" checked>
                                    <span class="tab-icon">🏫</span> Training Partner
                                </label>
                                <label class="role-tab" onclick="this.classList.add('active'); this.previousElementSibling.classList.remove('active')">
                                    <input type="radio" name="role" value="admin">
                                    <span class="tab-icon">⚙️</span> Administrator
                                </label>
                            </div>
                        </div>

                        <div class="field-group">
                            <label class="field-label" for="email">Email Address</label>
                            <div class="field-wrap">
                                <span class="field-icon">✉️</span>
                                <input type="email" id="email" name="email" placeholder="name@center.com" required>
                            </div>
                        </div>

                        <div class="field-group">
                            <label class="field-label" for="password">Password</label>
                            <div class="field-wrap">
                                <span class="field-icon">🔑</span>
                                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-signin">Sign In Securely →</button>
                        <a href="#" class="forgot">Forgot your password?</a>
                    </form>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ═══ FEATURES ═══════════════════════════════════════════════ -->
<section class="features-section">
    <div class="features-inner">
        <div class="features-head">
            <span class="section-tag">What We Offer</span>
            <h2 class="section-title" style="color:#fff;">Everything your center<br>needs to thrive</h2>
            <p>Built specifically for NIELIT training partners — no bloat, just power.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon fi-blue">📁</div>
                <h4>Bulk Data Uploads</h4>
                <p>Seamlessly upload student records and photos via structured CSV formats, mapped directly to active NIELIT courses.</p>
                <span class="feature-tag">Data Management</span>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-green">📢</div>
                <h4>Real-Time Notices</h4>
                <p>Receive instant PDF notices, syllabus updates, and operational circulars directly from NIELIT administration.</p>
                <span class="feature-tag">Communication</span>
            </div>
            <div class="feature-card">
                <div class="feature-icon fi-gold">📊</div>
                <h4>CBT Tracking</h4>
                <p>Log test appearances, placement records, and campus activities to showcase your center's performance metrics.</p>
                <span class="feature-tag">Examinations</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══ FOOTER ═══════════════════════════════════════════════ -->
<footer>
    <p><strong>&copy; <?= date('Y') ?> NIELIT Bhubaneswar.</strong> All Rights Reserved.</p>
    <p style="margin-top:6px;">Designed & Developed for Regional Training Partners.</p>
</footer>

<script>
    // Role tab toggle
    document.querySelectorAll('.role-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
        });
    });

    // Scroll reveal
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.style.animation = 'fadeUp 0.6s ease both';
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.map-card, .feature-card').forEach(el => observer.observe(el));
</script>

</body>
</html>