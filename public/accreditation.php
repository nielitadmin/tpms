<?php
// 1. CRITICAL: Use the unified session name for the entire application
session_name('NIELIT_TPMS');
session_start();

// 2. LOAD CONFIG & HELPERS (Adjusted paths for being inside the /public folder)
require_once __DIR__ . '/../includes/config.php';

if (file_exists(__DIR__ . '/../includes/theme_loader.php')) {
    require_once __DIR__ . '/../includes/theme_loader.php';
}
if (file_exists(__DIR__ . '/../includes/navigation_helper.php')) {
    require_once __DIR__ . '/../includes/navigation_helper.php';
}

// Safe APP_URL check
$safe_app_url = defined('APP_URL') ? APP_URL : '';

// 3. INITIALIZE VARIABLES FOR HEADER
$active_theme = function_exists('loadActiveTheme') && isset($conn) && is_object($conn) ? loadActiveTheme($conn) : null;
$theme_logo = function_exists('getThemeLogo') ? getThemeLogo($active_theme) : '';

$navigation_menu_html = '';
if (isset($conn) && is_object($conn) && function_exists('navigationMenuTableExists') && navigationMenuTableExists($conn)) {
    $menu_items = getNavigationMenu($conn);
    $current_page = basename($_SERVER['PHP_SELF']);
    $navigation_menu_html = renderNavigationMenu($menu_items, $current_page);
}
if (empty($navigation_menu_html) && function_exists('getFallbackNavigationMenu')) {
    $navigation_menu_html = getFallbackNavigationMenu();
}

// Fetch active notices for ticker
$active_notices = [];
if (isset($conn) && is_object($conn)) {
    try {
        $notices_result = @$conn->query("SELECT * FROM notices WHERE LOWER(status) IN ('active', 'published', '1') ORDER BY created_at DESC LIMIT 6");
        if ($notices_result) {
            while ($row = $notices_result->fetch_assoc()) $active_notices[] = $row;
        }
    } catch (Throwable $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become an Accreditation Partner - NIELIT</title>

    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Devanagari:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?php 
    if (function_exists('injectThemeCSS')) {
        injectThemeCSS($active_theme);
    }
    ?>

    <style>
        :root {
            --navy: #0a1628;
            --navy-mid: #112240;
            --primary: #155E75; 
            --primary-light: #0284C7; 
            --primary-bg: #EFF6FF; 
            --candidate: #059669; 
            --candidate-bg: #ECFDF5; 
            --tp: #0D9488; 
            --tp-bg: #CCFBF1;
            --blue: #1a56db;
            --gold: #f59e0b;
            --gold-light: #fcd34d;
            --cream: #fafaf8;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-body: #F8FAFC;
            --border: rgba(0,0,0,0.08);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 30px -5px rgba(0, 0, 0, 0.08); 
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--cream);
            color: var(--text-dark);
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        h1,h2,h3,h4,h5,h6 { font-family: 'Sora', sans-serif; }

        /* ===== TOP BAR ===== */
        .top-bar { background: #fff; border-bottom: 1px solid var(--border); padding: 10px 0; position: relative; z-index: 100; }
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

        /* ===== NAVBAR ===== */
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

        /* --- RED BLINKING TEXT ONLY --- */
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

        /* ===== NOTICE TICKER ===== */
        .ticker-wrap { background: var(--text-dark); color: white; padding: 8px 0; overflow: hidden; position: relative; z-index: 10; font-size: 13px; font-weight: 600; display: flex; align-items: center; }
        .ticker-label { background: var(--gold); color: white; padding: 3px 12px; border-radius: 4px; font-weight: 800; margin: 0 15px; position: relative; z-index: 2; white-space: nowrap; font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;}
        .ticker-move { display: inline-block; white-space: nowrap; animation: ticker 40s linear infinite; padding-left: 20px;}
        .ticker-move a { color: white; text-decoration: none; }
        .ticker-move a:hover { text-decoration: underline; }
        @keyframes ticker { 0% { transform: translateX(100vw); } 100% { transform: translateX(-100%); } }

        /* AMBIENT BG */
        .ambient-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; pointer-events: none; background: radial-gradient(circle at 50% 0%, #E0F2FE 0%, #F8FAFC 70%); perspective: 1000px; }
        .shape { position: absolute; background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.3)); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 15px 35px rgba(21, 94, 117, 0.05), inset 0 0 20px rgba(255, 255, 255, 0.5); animation: float-3d 25s infinite linear; }
        .cube { width: 180px; height: 180px; border-radius: 35px; top: 15%; left: 5%; animation-duration: 35s; }
        .ring { width: 260px; height: 260px; border-radius: 50%; border: 40px solid rgba(255,255,255,0.5); top: 50%; right: 2%; animation-duration: 40s; animation-direction: reverse; background: transparent; }
        @keyframes float-3d { 0% { transform: translateY(0) rotateX(0deg) rotateY(0deg) rotateZ(0deg); } 50% { transform: translateY(-30px) rotateX(180deg) rotateY(90deg) rotateZ(45deg); } 100% { transform: translateY(0) rotateX(360deg) rotateY(180deg) rotateZ(90deg); } }

        /* ===== INNER PAGE HEADER ===== */
        .inner-header {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            padding: 70px 0;
            text-align: center;
            color: white;
            border-bottom: 4px solid var(--gold);
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        .inner-header::before {
            content: ''; position: absolute; right: -50px; top: -50px; width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(245,158,11,0.15) 0%, transparent 70%); pointer-events: none;
        }
        .inner-header h1 { font-weight: 800; font-size: 2.8rem; margin-bottom: 15px; position: relative; z-index: 2; letter-spacing: -1px;}
        .inner-header p { color: rgba(255,255,255,0.7); font-size: 1.15rem; position: relative; z-index: 2; max-width: 700px; margin: 0 auto;}

        /* ===== CONTENT SECTION ===== */
        .accreditation-content { padding: 70px 0; position: relative; z-index: 5; flex-grow: 1; }
        .section-eyebrow { font-size: 0.75rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--blue); font-family: 'Sora', sans-serif; margin-bottom: 10px; display: block; }
        .section-title { font-size: 32px; font-weight: 800; color: var(--navy); letter-spacing: -0.5px; margin-bottom: 20px; }
        
        .content-card {
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(15px); border-radius: 20px;
            padding: 40px; box-shadow: var(--shadow-md); border: 1px solid white; margin-bottom: 40px;
            animation: fadeUp 0.8s ease both;
        }
        
        .benefit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-top: 30px; }
        .benefit-item { background: var(--cream); border: 1px solid var(--border); padding: 25px; border-radius: 16px; display: flex; gap: 18px; transition: 0.3s; }
        .benefit-item:hover { background: white; box-shadow: var(--shadow-sm); transform: translateY(-5px); }
        .benefit-icon { width: 50px; height: 50px; flex-shrink: 0; background: var(--primary-bg); color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .benefit-text h5 { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;}
        .benefit-text p { font-size: 14px; color: var(--text-muted); line-height: 1.5; margin: 0;}

        .criteria-list { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        .criteria-list li { background: white; border: 1px solid var(--border); padding: 15px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 14px; color: var(--text-dark); box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .criteria-list li i { color: var(--candidate); font-size: 18px; }

        .step-container { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-top: 40px; position: relative; }
        .step-container::before { content: ''; position: absolute; top: 30px; left: 10%; right: 10%; height: 2px; background: var(--border); z-index: 1; }
        .step-box { position: relative; z-index: 2; text-align: center; width: 25%; }
        .step-circle { width: 60px; height: 60px; margin: 0 auto 15px auto; background: var(--navy); color: var(--gold); border: 4px solid white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; box-shadow: var(--shadow-sm); transition: 0.3s; }
        .step-box:hover .step-circle { background: var(--gold); color: var(--navy); transform: scale(1.1); }
        .step-box h6 { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 15px; color: var(--text-dark); margin-bottom: 5px;}
        .step-box p { font-size: 13px; color: var(--text-muted); }

        .cta-box {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 20px; padding: 50px 40px; text-align: center; color: white; margin-top: 40px;
            box-shadow: 0 15px 30px rgba(2, 132, 199, 0.2); animation: fadeUp 0.8s ease both; animation-delay: 0.2s;
        }
        .cta-box h2 { font-weight: 800; font-size: 32px; margin-bottom: 15px; }
        .cta-box p { font-size: 16px; opacity: 0.9; margin-bottom: 30px; max-width: 600px; margin-left: auto; margin-right: auto;}
        
        .btn-register-now {
            background: var(--gold); color: var(--navy); padding: 16px 36px; border-radius: 50px; font-weight: 800; font-size: 16px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; border: none; box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
        }
        .btn-register-now:hover { background: var(--gold-light); color: var(--navy); transform: translateY(-3px); box-shadow: 0 12px 25px rgba(245, 158, 11, 0.4); }

        /* ===== FOOTER ===== */
        .footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 25px 40px; background: white; border-top: 1px solid var(--border); font-size: 13px; font-weight: 600; color: var(--text-muted); z-index: 10; margin-top: auto; }
        .footer-left { display: flex; flex-direction: column; gap: 6px; }
        .credit-text { font-size: 12px; color: var(--primary-light); font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .footer-links { display: flex; gap: 25px; flex-wrap: wrap;}
        .footer-links a { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .footer-links a:hover { color: var(--primary); }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        @media (max-width: 992px) { 
            .step-container { flex-direction: column; align-items: center; gap: 30px; }
            .step-container::before { display: none; }
            .step-box { width: 100%; max-width: 300px; display: flex; text-align: left; align-items: center; gap: 20px; }
            .step-circle { margin: 0; }
        }
        @media (max-width: 768px) { 
            .header-container { flex-direction: column; gap: 15px; text-align: center; padding: 15px 20px; } 
            .header-left, .header-right { flex-direction: column; align-items: center; justify-content: center; text-align: center;} 
            .nav-container { padding: 10px 20px; } .mobile-menu-btn { display: block; } 
            .nav-links { display: none; width: 100%; flex-direction: column; align-items: flex-start; padding-bottom: 15px; } 
            .nav-links.active { display: flex; } .nav-link { width: 100%; padding: 12px 10px !important; justify-content: flex-start;} 
            .nav-blink-text { margin: 10px 0; justify-content: flex-start;} 
            .footer { flex-direction: column; gap: 15px; text-align: center; justify-content: center; } .footer-left { align-items: center; } .footer-links { justify-content: center; } 
            .accreditation-content { padding: 40px 20px; }
            .content-card { padding: 25px; }
            .inner-header h1 { font-size: 2.2rem; }
            .cta-box { padding: 40px 25px; }
            .cta-box h2 { font-size: 26px; }
        }
    </style>
</head>
<body>

    <header class="top-header">
        <div class="header-container">
            <div class="header-left">
                <?php if(!empty($theme_logo)): ?>
                    <img src="<?php echo $safe_app_url . '/' . htmlspecialchars($theme_logo); ?>" alt="NIELIT Logo" class="nielit-logo">
                <?php else: ?>
                    <img src="../RR.png" alt="NIELIT Logo" class="nielit-logo">
                <?php endif; ?>
                <div class="header-titles">
                    <span class="hindi-title">राष्ट्रीय इलेक्ट्रॉनिकी एवं सूचना प्रौद्योगिकी संस्थान, भुवनेश्वर</span>
                    <span class="eng-title">National Institute of Electronics & Information Technology, Bhubaneswar</span>
                </div>
            </div>
            <div class="header-right">
                <div class="ministry-text">
                    <strong>Ministry of Electronics & IT</strong> Government of India
                </div>
                <img src="../image_7c2b82.png" alt="Government of India Emblem" class="emblem">
            </div>
        </div>
    </header>

    <nav class="main-nav">
        <div class="nav-container">
            <a href="../index.php" class="nav-home-btn">
                <i class="fas fa-layer-group"></i> NIELIT TPMS
            </a>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links" id="navLinks">
                <a href="../index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="courses.php" class="nav-link"><i class="fas fa-book"></i> Courses</a>
                <a href="notices.php" class="nav-link"><i class="fas fa-bullhorn"></i> Public Notices</a>
                <a href="contact.php" class="nav-link"><i class="fas fa-headset"></i> Contact Us</a>
                
                <a href="accreditation.php" class="nav-link nav-blink-text active">
                    <i class="fas fa-certificate"></i> Become an Accreditation Partner
                </a>
                
                <a href="../tp/tp_signup.php" class="nav-link nav-btn-highlight"><i class="fas fa-user-plus"></i> Register Center</a>
            </div>
        </div>
    </nav>

    <div class="ticker-wrap">
        <div class="ticker-label">System Alerts</div>
        <div class="ticker-move">
            <?php if (!empty($active_notices)): ?>
                <?php foreach ($active_notices as $notice): 
                    $file_url = $notice['file_path'];
                    if (!preg_match('~^(http|https)://~i', $file_url)) {
                        $file_url = $safe_app_url . '/' . ltrim($file_url, '/');
                    }
                ?>
                    <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank">
                        &bull; <?php echo htmlspecialchars($notice['title']); ?>
                    </a> &nbsp;&nbsp;&nbsp; 
                <?php endforeach; ?>
            <?php else: ?>
                &bull; Registration for new Training Partners in Odisha and Chhattisgarh is now open. &bull; Existing partners must upload records via CSV before the deadline.
            <?php endif; ?>
        </div>
    </div>

    <div class="inner-header">
        <div class="container">
            <h1>Partner with NIELIT</h1>
            <p>Join a prestigious network of educational institutions driving digital literacy and skill development across India.</p>
        </div>
    </div>

    <main class="accreditation-content">
        <div class="container" style="max-width: 1200px;">
            
            <div class="content-card">
                <span class="section-eyebrow">The NIELIT Advantage</span>
                <h2 class="section-title">Why Become an Accredited Center?</h2>
                
                <div class="benefit-grid">
                    <div class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-award"></i></div>
                        <div class="benefit-text">
                            <h5>Government Certification</h5>
                            <p>Offer courses backed by the Ministry of Electronics & IT, highly valued by employers nationwide.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="benefit-text">
                            <h5>NSQF Aligned Curriculum</h5>
                            <p>Gain access to standardized, high-quality course material that aligns with the National Skills Qualification Framework.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-users-cog"></i></div>
                        <div class="benefit-text">
                            <h5>Centralized Management</h5>
                            <p>Utilize this TPMS portal to bulk upload students, track CBT tests, and manage center operations seamlessly.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="benefit-text">
                            <h5>Revenue Growth</h5>
                            <p>Expand your student base by associating with a trusted national brand in digital education.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card" style="animation-delay: 0.1s;">
                <span class="section-eyebrow">Prerequisites</span>
                <h2 class="section-title">Eligibility Criteria</h2>
                <p class="text-muted mb-4">To maintain high educational standards, prospective partners must meet the following minimum infrastructure and academic requirements before applying.</p>
                
                <ul class="criteria-list">
                    <li><i class="fas fa-check-circle"></i> Registered Legal Entity (Trust, Society, or Pvt Ltd)</li>
                    <li><i class="fas fa-check-circle"></i> Minimum Carpet Area of 90 Sq. Meters</li>
                    <li><i class="fas fa-check-circle"></i> At least 10 Desktop/Laptop Computers</li>
                    <li><i class="fas fa-check-circle"></i> High-Speed Broadband Internet Connection</li>
                    <li><i class="fas fa-check-circle"></i> Qualified Faculty (MCA, B.Tech, or NIELIT A/B Level)</li>
                    <li><i class="fas fa-check-circle"></i> Power Backup & CCTV Surveillance</li>
                </ul>
            </div>

            <div class="content-card" style="animation-delay: 0.2s; background: transparent; border: none; box-shadow: none; padding: 0;">
                <h2 class="section-title text-center">The Application Process</h2>
                
                <div class="step-container">
                    <div class="step-box">
                        <div class="step-circle">1</div>
                        <h6>Online Registration</h6>
                        <p>Fill out the TP application form on this portal and upload basic institute details.</p>
                    </div>
                    <div class="step-box">
                        <div class="step-circle">2</div>
                        <h6>Document Upload</h6>
                        <p>Submit required legal, infrastructure, and faculty documents for online verification.</p>
                    </div>
                    <div class="step-box">
                        <div class="step-circle">3</div>
                        <h6>Physical Inspection</h6>
                        <p>A NIELIT committee will inspect your center to verify infrastructure and facilities.</p>
                    </div>
                    <div class="step-box">
                        <div class="step-circle">4</div>
                        <h6>Approval & Login</h6>
                        <p>Upon success, your center is approved and you receive access to the TP Dashboard.</p>
                    </div>
                </div>
            </div>

            <div class="cta-box">
                <h2>Ready to Transform Digital Education?</h2>
                <p>Join hands with NIELIT Bhubaneswar today and start offering standardized IT and electronics courses to students in your region.</p>
                <a href="../tp/tp_signup.php" class="btn-register-now">
                    Proceed to Registration Form <i class="fas fa-arrow-right"></i>
                </a>
            </div>

        </div>
    </main>

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
    </script>
</body>
</html>