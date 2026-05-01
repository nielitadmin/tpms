<?php
// CERT-In Compliant: Add security headers via .htaccess or server config:
// Content-Security-Policy, X-Frame-Options: DENY, X-XSS-Protection, Strict-Transport-Security
session_start();
require __DIR__ . '/../includes/config.php'; 

if (isset($_POST['action']) && $_POST['action'] === 'save_draft') {
    $_SESSION['tp_draft'] = $_POST;
    echo json_encode(['status' => 'ok']);
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'clear_draft') {
    unset($_SESSION['tp_draft']);
    exit;
}

$errors  = [];
$success = false;
$draft   = $_SESSION['tp_draft'] ?? [];

function handle_upload($key, $folder = 'tp_documents') {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$key];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;
    if (!in_array($f['type'], ['image/jpeg','image/png','application/pdf'])) return null;
    if ($f['size'] > 5 * 1024 * 1024) return null;
    $ext  = pathinfo($f['name'], PATHINFO_EXTENSION);
    $name = $key . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dir  = "uploads/$folder/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($f['tmp_name'], $dir . $name)) return $dir . $name;
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'final_submit') {
    if (empty($_POST['institute_name']))   $errors[] = "Institute name is required.";
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
    if (empty($_POST['password']) || strlen($_POST['password']) < 8) $errors[] = "Password minimum 8 characters.";
    if ($_POST['password'] !== $_POST['confirm_password']) $errors[] = "Passwords do not match.";
    if (empty($_POST['mobile']) || !preg_match('/^[6-9]\d{9}$/', $_POST['mobile'])) $errors[] = "Valid 10-digit mobile required.";
    if (empty($_POST['declaration'])) $errors[] = "Please accept the declaration.";

    if (empty($errors)) {
        $upload_fields = [
            's3_id_proof','s3_signature','s4_layout_map','s4_building_photo','s4_agreement',
            's9_legal_doc','s9_moa_doc','s12_faculty1_cert','s12_faculty2_cert',
            's17_id_proof','s17_signatory_sig','s17_layout_map','s17_reg_cert',
            's17_franchise_agmt','s17_registrar_reg','s17_tax_reg','s17_lease_deed',
            's17_other_doc','s17_building_photos'
        ];
        $uploads = [];
        foreach ($upload_fields as $uf) $uploads[$uf] = handle_upload($uf);
        // TODO: INSERT into database
        unset($_SESSION['tp_draft']);
        $success = true;
    }
}

$v = array_merge($draft, $_POST ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Register as a Training Partner with NIELIT - National Institute of Electronics and Information Technology, Government of India">
<meta name="keywords" content="NIELIT, Training Partner, Registration, TPMS, Government of India, MeitY">
<meta name="author" content="NIELIT, Government of India">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Training Partner Registration | NIELIT TPMS | Government of India</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --navy:    #0d3b6e;
  --navy2:   #1a5fa8;
  --teal:    #0a7c8c;
  --saffron: #e07b00;
  --gold:    #b8860b;
  --green:   #0a7c3e;
  --white:   #ffffff;
  --border:  #e2e8f0;
  --text:    #1a202c;
  --muted:   #718096;
  --error:   #c53030;
  --success: #276749;
  --bg:      #f7f9fc;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', sans-serif;
  color: var(--text);
  font-size: 14px;
  line-height: 1.65;
  min-height: 100vh;
  overflow-x: hidden;
  background: #ffffff;
}

/* ══════════════════════════════════════
   PLAIN WHITE BACKGROUND
══════════════════════════════════════ */
.page-bg { display: none; }

/* ══════════════════════════════════════
   LAYOUT WRAPPER (above canvas)
══════════════════════════════════════ */
.page-wrapper {
  position: relative;
  z-index: 1;
}

/* ══════════════════════════════════════
   TOP GOVERNMENT BAR
══════════════════════════════════════ */
.gov-topbar {
  background: #fff;
  color: var(--text);
  font-size: 12px;
  padding: 8px 24px;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.gov-topbar a { color: var(--navy2); text-decoration: none; margin: 0 8px; font-weight: 500; }
.gov-topbar a:hover { color: var(--teal); text-decoration: underline; }
.gov-topbar .topbar-left { display:flex; align-items:center; gap:6px; color:var(--muted); font-size:11.5px; }
.gov-topbar .topbar-right { display:flex; align-items:center; gap:4px; }

/* ══════════════════════════════════════
   HEADER (glass)
══════════════════════════════════════ */
.header {
  background: #ffffff;
  border-bottom: 1px solid var(--border);
  box-shadow: 0 1px 6px rgba(0,0,0,0.06);
}
.tricolor { height: 4px; background: linear-gradient(to right, #FF9933 33.3%, #ffffff 33.3% 66.6%, #138808 66.6%); }
.header-inner {
  max-width: 1200px; margin: auto;
  display: flex; align-items: center; gap: 14px;
  padding: 12px 24px;
}
.hdr-text { flex: 1; }
.hdr-text .ministry-hi { font-size: 15px; color: var(--navy); font-weight: 600; line-height: 1.3; }
.hdr-text .ministry-en { font-size: 12.5px; color: var(--muted); margin-top: 1px; }
.hdr-text h1 { display:none; }
.hdr-right-block { text-align: right; }
.hdr-right-block .min-name { font-size: 12px; font-weight: 700; color: var(--text); }
.hdr-right-block .min-sub { font-size: 11px; color: var(--muted); }

/* ══════════════════════════════════════
   NAVBAR
══════════════════════════════════════ */
.navbar {
  background: #0d3b6e;
  box-shadow: 0 2px 8px rgba(0,0,0,0.18);
}
.navbar-inner { max-width: 1200px; margin: auto; display:flex; align-items:center; justify-content:space-between; padding: 0 24px; }
.navbar-brand { display:flex; align-items:center; gap:8px; color:#fff; font-weight:700; font-size:14px; text-decoration:none; padding:10px 0; }
.navbar-brand svg { flex-shrink:0; }
.navbar ul { list-style: none; display: flex; flex-wrap: wrap; margin:0; padding:0; }
.navbar ul li a {
  display: block; padding: 12px 16px; color: rgba(255,255,255,0.8);
  text-decoration: none; font-size: 13px; font-weight: 500;
  border-bottom: 3px solid transparent; transition: .18s;
}
.navbar ul li a:hover { color: #fff; border-bottom-color: var(--saffron); }
.navbar ul li a.active { color: #fff; border-bottom-color: var(--saffron); }
.navbar ul li a.btn-register {
  background: var(--saffron); color: #fff; border-radius: 6px;
  margin: 6px 0 6px 8px; padding: 6px 14px; border-bottom: none; font-weight:600;
}
.navbar ul li a.btn-register:hover { background: #c96e00; }

/* ══════════════════════════════════════
   PAGE TITLE BAR (glass)
══════════════════════════════════════ */
.page-title {
  background: #f7f9fc;
  border-bottom: 1px solid var(--border);
  color: var(--text); text-align: center; padding: 22px 20px 18px;
}
.page-title .bc { font-size: 12px; color: var(--muted); margin-bottom: 6px; }
.page-title .bc span { color: var(--teal); font-weight: 600; }
.page-title h2 { font-size: 22px; font-weight: 800; color: var(--navy); letter-spacing: -0.3px; }
.page-title h2 em { color: var(--teal); font-style: normal; }
.page-title p { font-size: 13px; color: var(--muted); margin-top: 5px; }

/* ══════════════════════════════════════
   WIZARD WRAP
══════════════════════════════════════ */
.wizard-wrap {
  max-width: 840px; margin: 24px auto 48px;
  padding: 0 14px;
}

/* ══════════════════════════════════════
   STEP INDICATOR (glass card)
══════════════════════════════════════ */
.step-indicator {
  background: #ffffff;
  backdrop-filter: none;
  border: 1px solid #d0dde8;
  border-radius: 14px;
  padding: 18px 22px 14px;
  margin-bottom: 18px;
  box-shadow: 0 1px 8px rgba(0,0,0,0.06);
}
.step-bar-wrap {
  display: flex; align-items: flex-start; justify-content: space-between;
  position: relative; margin-bottom: 12px;
}
.step-bar-wrap::before {
  content: ''; position: absolute;
  top: 16px; left: 0; right: 0; height: 3px;
  background: #dce8f4; z-index: 0;
  border-radius: 2px;
}
.step-item { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 1; }
.step-circle {
  width: 34px; height: 34px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 13px;
  border: 2px solid #c8d8e8; background: #f5f8fc; color: #8090a0;
  transition: all .35s cubic-bezier(.34,1.56,.64,1);
}
.step-circle.done {
  background: linear-gradient(135deg, var(--green), #14a05a);
  border-color: var(--green); color: #fff;
  box-shadow: 0 4px 16px rgba(10,124,62,0.4);
}
.step-circle.active {
  background: var(--teal);
  border-color: var(--teal); color: #fff;
  box-shadow: 0 4px 16px rgba(10,124,140,0.35);
  transform: scale(1.12);
}
.step-label { font-size: 10.5px; color: var(--muted); margin-top: 6px; text-align: center; font-weight: 500; line-height: 1.3; max-width: 86px; }
.step-label.active { color: var(--teal); font-weight: 700; }
.step-label.done { color: var(--green); }

.progress-row { display: flex; align-items: center; gap: 10px; }
.progress-track { flex: 1; height: 9px; background: #dce8f4; border-radius: 6px; overflow: hidden; }
.progress-fill {
  height: 100%;
  background: linear-gradient(to right, var(--teal), #0fa3b1);
  border-radius: 6px; transition: width .5s cubic-bezier(.4,0,.2,1);
  box-shadow: none;
}
.progress-pct { font-size: 12px; font-weight: 700; color: var(--teal); min-width: 36px; text-align: right; }

/* ══════════════════════════════════════
   FORM CARD (glass)
══════════════════════════════════════ */
.form-card {
  background: #ffffff;
  backdrop-filter: none;
  border: 1px solid #d0dde8;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 16px rgba(0,20,80,0.10);
}
.step-panel { display: none; }
.step-panel.active { display: block; animation: panelIn .35s ease; }
@keyframes panelIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Panel header */
.panel-header {
  background: #f0f7ff;
  border-left: 4px solid var(--teal);
  padding: 13px 20px; display: flex; align-items: center; gap: 12px;
  border-bottom: 1px solid #dce8f4;
}
.panel-num {
  background: var(--teal);
  color: #fff; width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; flex-shrink: 0;
  box-shadow: 0 3px 12px rgba(0,68,138,0.4);
}
.panel-title { font-size: 13.5px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: .4px; }
.panel-body { padding: 20px; }

/* ══════════════════════════════════════
   FORM FIELDS
══════════════════════════════════════ */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
.form-grid.three { grid-template-columns: 1fr 1fr 1fr; }
.col-full { grid-column: 1 / -1; }

.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-size: 12.5px; font-weight: 600; color: var(--text); }
.form-group label .req { color: var(--error); margin-left: 2px; }
.form-group label .opt { color: var(--muted); font-size: 11px; font-weight: 400; }

.form-group input,
.form-group select,
.form-group textarea {
  border: 1.5px solid #c8d8e8;
  border-radius: 6px;
  padding: 8px 11px;
  font-size: 13px;
  font-family: 'Inter', sans-serif;
  color: var(--text);
  background: #ffffff;
  width: 100%;
  transition: all .18s;
  box-shadow: inset 0 1px 3px rgba(0,20,60,0.04);
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--teal);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(10,124,140,0.12);
}
.form-group textarea { resize: vertical; min-height: 68px; }

/* Upload box */
.upload-box {
  border: 2px dashed #b8cce0;
  border-radius: 8px;
  padding: 11px 13px;
  background: #f4f8fd;
  transition: all .18s;
}
.upload-box:hover { border-color: var(--navy2); background: #e8f2ff; }
.upload-box label { font-size: 12.5px; font-weight: 600; color: var(--navy); display: block; margin-bottom: 6px; }
.upload-box label .req { color: var(--error); }
.upload-box label .opt { color: var(--muted); font-weight: 400; font-size: 11px; }
.upload-box input[type=file] { width: 100%; font-size: 12px; color: var(--muted); cursor: pointer; }
.upload-hint { font-size: 10.5px; color: var(--muted); margin-top: 3px; line-height: 1.4; }

/* Radio group */
.radio-group { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
.radio-group label {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 12px;
  border: 1.5px solid #c8d8e8; border-radius: 6px;
  cursor: pointer; font-size: 13px; font-weight: 500;
  background: #f8fbff;
  transition: all .15s;
}
.radio-group label:hover { border-color: var(--navy2); background: #e8f2ff; }
.radio-group input[type=radio] { accent-color: var(--navy2); }

/* Sub section */
.sub-sec {
  background: #f4f8fd;
  border: 1px solid #d8e8f4;
  border-radius: 8px; margin-top: 14px; overflow: hidden;
}
.sub-sec-head {
  background: #e4eef8;
  padding: 8px 14px; font-size: 12.5px; font-weight: 700;
  color: var(--navy); border-left: 3px solid var(--saffron);
}
.sub-sec-body { padding: 14px; }

/* Conditional blocks */
.cond-block { display: none; }
.cond-block.visible { display: block; animation: panelIn .3s ease; }

/* Document table */
.doc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.doc-table thead tr { background: var(--navy); color: #fff; }
.doc-table thead th { padding: 10px 12px; text-align: left; font-weight: 600; font-size: 12.5px; }
.doc-table tbody tr:nth-child(even) { background: #eef4ff; }
.doc-table tbody tr { border-bottom: 1px solid #e2e8f0; }
.doc-table tbody td { padding: 9px 12px; vertical-align: middle; }

/* Alerts */
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 14px; font-size: 13px; }
.alert-error { background: #fff5f5; border-left: 4px solid var(--error); color: var(--error); }
.alert-success { background: #f0faf5; border-left: 4px solid var(--success); color: var(--success); font-size: 14px; }
.alert ul { padding-left: 16px; margin-top: 4px; }

/* Draft banner */
.draft-banner {
  background: #fffbea;
  border: 1px solid var(--gold); border-radius: 8px;
  padding: 10px 14px; font-size: 12.5px; color: #7a5a00;
  margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}

/* Nav bar */
.nav-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 20px;
  background: #f7f9fc;
  border-top: 1px solid var(--border);
  flex-wrap: wrap; gap: 10px;
}
.btn {
  padding: 9px 22px; font-size: 13px; font-weight: 700;
  font-family: 'Inter', sans-serif; border-radius: 6px;
  cursor: pointer; border: none; transition: all .2s;
  text-transform: uppercase; letter-spacing: .5px;
}
.btn-prev {
  background: #ffffff;
  color: var(--navy);
  border: 1.5px solid #b0c4d8;
  display: inline-flex; align-items: center; gap: 6px;
  font-weight: 600;
}
.btn-prev::before { content: '\2190'; font-size: 15px; }
.btn-prev:hover { background: #eef4fb; border-color: var(--teal); color: var(--teal); }
.btn-next {
  background: var(--teal);
  color: #fff;
  box-shadow: 0 2px 10px rgba(10,124,140,0.3);
}
.btn-next:hover { background: #086e7c; box-shadow: 0 4px 16px rgba(10,124,140,0.4); transform: translateY(-1px); }
.btn-draft {
  background: #fffbea; color: #7a5a00;
  border: 1.5px solid var(--gold); font-size: 12px; padding: 8px 15px;
}
.btn-draft:hover { background: #fff3c0; }
.btn-submit {
  background: linear-gradient(135deg, var(--green), #14a05a);
  color: #fff; padding: 10px 28px; font-size: 14px;
  box-shadow: 0 4px 18px rgba(10,124,62,0.4);
}
.btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 28px rgba(10,124,62,0.5); }
.draft-saved-msg { font-size: 12px; color: var(--success); font-weight: 600; display: none; }

/* Declaration */
.decl-box {
  background: #fffbea; border: 2px solid var(--gold);
  border-radius: 8px; padding: 14px; margin-top: 18px;
}
.decl-box label { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13px; }
.decl-box input[type=checkbox] { margin-top: 3px; accent-color: var(--navy2); flex-shrink: 0; width: 16px; height: 16px; }

/* Footer */
.site-footer {
  background: var(--navy);
  color: #8faecf;
  padding: 14px 24px;
  font-size: 12px;
  margin-top: 0;
}
.site-footer a { color: #8faecf; text-decoration: none; }
.site-footer a:hover { color: #fff; }


/* ── WCAG: Keyboard focus ring ── */
:focus-visible {
  outline: 3px solid var(--teal);
  outline-offset: 2px;
}
button:focus-visible,
a:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
  outline: 3px solid var(--teal);
  outline-offset: 2px;
}


@media (max-width: 768px) {
  .header-inner { flex-wrap: wrap; gap: 10px; }
  .hdr-right-block { display: none; }
  .navbar-inner { flex-wrap: wrap; }
  .navbar ul { justify-content: center; }
  .wizard-wrap { padding: 0 10px; }
  .form-grid.three { grid-template-columns: 1fr 1fr; }
}


@media print {
  .gov-topbar, .navbar, .site-footer, .nav-bar, .btn, .draft-banner { display: none !important; }
  .form-card { box-shadow: none; border: 1px solid #ccc; }
  body { background: #fff; }
  .step-panel { display: block !important; }
}

@media (max-width: 640px) {
  .form-grid, .form-grid.three { grid-template-columns: 1fr; }
  .step-label { display: none; }
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════
     GOVT PORTAL BACKGROUND
══════════════════════════════════════ -->
<div class="page-bg"></div>

<div class="page-wrapper">

<!-- Skip Navigation (GIGW Accessibility) -->
<a href="#main-content" style="position:absolute;left:-9999px;top:0;z-index:9999;background:var(--navy);color:#fff;padding:8px 14px;font-size:13px;font-weight:600;text-decoration:none;" onfocus="this.style.left='0'" onblur="this.style.left='-9999px'">Skip to Main Content</a>

<!-- Gov Topbar -->
<div class="gov-topbar">
  <div class="topbar-left">
    <span>&#127470;&#127475;</span>
    <span aria-label="Government of India, Ministry of Electronics and Information Technology">Government of India &nbsp;&bull;&nbsp; Ministry of Electronics &amp; IT</span>
  </div>
  <div class="topbar-right">
    <a href="#">MeitY</a> <a href="#">NIELIT HQ</a> <a href="#">Grievance</a> <a href="#">RTI</a> <a href="#" aria-label="Screen Reader Access">Screen Reader Access</a>
  </div>
</div>

<!-- Header -->
<div class="header" role="banner">
  <div class="tricolor"></div>
  <div class="header-inner">
    <!-- NIELIT Official Logo -->
    <div style="flex-shrink:0;">
      <img src="data:image/jpeg;base64,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" alt="NIELIT Logo" style="height:64px;width:auto;display:block;">
    </div>
    <div class="hdr-text">
      <div class="ministry-hi">राष्ट्रीय इलेक्ट्रॉनिकी एवं सूचना प्रौद्योगिकी संस्थान, भुवनेश्वर</div>
      <div class="ministry-en">National Institute of Electronics &amp; Information Technology, Bhubaneswar</div>
      <h1></h1>
    </div>
    <!-- Ministry + Ashoka Emblem -->
    <div class="hdr-right-block" style="display:flex;align-items:center;gap:10px;">
      <div style="text-align:right;">
        <div class="min-name">Ministry of Electronics &amp; IT</div>
        <div class="min-sub">Government of India</div>
      </div>
      <img src="data:image/webp;base64,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" alt="Ministry of Electronics & IT, Government of India" style="height:60px;width:auto;display:block;">
    </div>
  </div>
  <div class="tricolor"></div>
</div>

<!-- Navbar -->
<nav class="navbar" role="navigation" aria-label="Main Navigation">
  <div class="navbar-inner">
    <a href="#" class="navbar-brand">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      NIELIT TPMS
    </a>
    <ul>
      <li><a href="#">&#128218; Courses</a></li>
      <li><a href="#">&#128227; Public Notices</a></li>
      <li><a href="#">&#129693; Contact Us</a></li>
      <li><a href="#" class="btn-register active">&#128101; Register Center</a></li>
    </ul>
  </div>
</nav>

<!-- Page Title -->
<div class="page-title">
  <div style="max-width:1200px;margin:0 auto;text-align:left;margin-bottom:8px;">
    <button onclick="history.back()" style="
      display:inline-flex;align-items:center;gap:6px;
      background:#fff;color:var(--navy);
      border:1.5px solid #b0c4d8;border-radius:6px;
      padding:6px 14px;font-size:13px;font-weight:600;
      cursor:pointer;font-family:'Inter',sans-serif;
      transition:all .18s;
    "
    onmouseover="this.style.borderColor='#0a7c8c';this.style.color='#0a7c8c';"
    onmouseout="this.style.borderColor='#b0c4d8';this.style.color='var(--navy)';"
    >&#8592; Back</button>
  </div>
  <div class="bc">Home &rsaquo; Training Partner &rsaquo; <span>New Registration</span></div>
  <h2>Training Partner <em>Registration</em></h2>
  <p>Complete all 5 steps &bull; Save as Draft anytime &bull; Resume anytime from where you left off</p>
</div>

<!-- ══════════════════════════════════════
     WIZARD
══════════════════════════════════════ -->
<div class="wizard-wrap" id="main-content" role="main">

<?php if ($success): ?>
<div class="alert alert-success" style="padding:22px;border-radius:10px;">
  <strong>&#10003; Application Submitted Successfully!</strong><br><br>
  Your TP registration has been received by NIELIT. A confirmation will be sent to your registered email.
  <br><br><a href="tp_login.php" style="color:var(--success);font-weight:700;">&#8594; Login to your account</a>
</div>

<?php else: ?>

<?php if (!empty($draft)): ?>
<div class="draft-banner">
  &#128190; Your saved draft has been restored. Continue from where you left off.
  <button onclick="clearDraft()" style="margin-left:auto;background:none;border:none;color:#b07700;cursor:pointer;font-size:12px;font-weight:700;">&#10005; Clear Draft</button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
  <strong>&#9888; Please correct the following:</strong>
  <ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
</div>
<?php endif; ?>

<!-- Step Indicator -->
<div class="step-indicator">
  <div class="step-bar-wrap">
    <div class="step-item"><div class="step-circle active" id="sc1">1</div><div class="step-label active" id="sl1">Institute<br>Details</div></div>
    <div class="step-item"><div class="step-circle" id="sc2">2</div><div class="step-label" id="sl2">Signatory &amp;<br>Premises</div></div>
    <div class="step-item"><div class="step-circle" id="sc3">3</div><div class="step-label" id="sl3">Legal<br>Status</div></div>
    <div class="step-item"><div class="step-circle" id="sc4">4</div><div class="step-label" id="sl4">Faculty &amp;<br>Financial</div></div>
    <div class="step-item"><div class="step-circle" id="sc5">5</div><div class="step-label" id="sl5">Documents<br>&amp; Submit</div></div>
  </div>
  <div class="progress-row">
    <div class="progress-track"><div class="progress-fill" id="progressFill" style="width:10%"></div></div>
    <div class="progress-pct" id="progressPct">10%</div>
  </div>
</div>

<!-- FORM CARD -->
<div class="form-card">
<form method="POST" enctype="multipart/form-data" id="tpForm" novalidate>
<input type="hidden" name="action" id="formAction" value="final_submit">

<!-- ══ STEP 1: Institute Details ══ -->
<div class="step-panel active" id="panel1">
  <div class="panel-header"><div class="panel-num">1</div><div class="panel-title">Institute / Organization Details</div></div>
  <div class="panel-body">
    <div class="form-grid">
      <div class="form-group col-full">
        <label>Full Name of Institute / Organization <span class="req">*</span></label>
        <input type="text" name="institute_name" value="<?= htmlspecialchars($v['institute_name'] ?? '') ?>" placeholder="As per registration certificate">
      </div>
      <div class="form-group">
        <label>Official Email <span class="req">*</span></label>
        <input type="email" name="email" autocomplete="email" value="<?= htmlspecialchars($v['email'] ?? '') ?>" placeholder="office@institute.in">
      </div>
      <div class="form-group">
        <label>Mobile Number <span class="req">*</span></label>
        <input type="tel" name="mobile" autocomplete="tel" value="<?= htmlspecialchars($v['mobile'] ?? '') ?>" placeholder="10-digit mobile">
      </div>
      <div class="form-group">
        <label>Landline / STD</label>
        <input type="tel" name="landline" value="<?= htmlspecialchars($v['landline'] ?? '') ?>" placeholder="0XXX-XXXXXXX">
      </div>
      <div class="form-group">
        <label>Website</label>
        <input type="text" name="website" value="<?= htmlspecialchars($v['website'] ?? '') ?>" placeholder="https://www.institute.in">
      </div>
      <div class="form-group col-full">
        <label>Full Address of Institute <span class="req">*</span></label>
        <textarea name="institute_address" rows="2" placeholder="Full address with PIN code"><?= htmlspecialchars($v['institute_address'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>State <span class="req">*</span></label>
        <select name="state">
          <option value="">-- Select State --</option>
          <?php foreach(['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Delhi','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal'] as $s): ?>
          <option <?= ($v['state'] ?? '')==$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>District <span class="req">*</span></label>
        <input type="text" name="district" value="<?= htmlspecialchars($v['district'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>PIN Code <span class="req">*</span></label>
        <input type="text" name="pincode" value="<?= htmlspecialchars($v['pincode'] ?? '') ?>" maxlength="6" placeholder="6-digit">
      </div>
      <div class="form-group">
        <label>Year of Establishment</label>
        <input type="number" name="est_year" value="<?= htmlspecialchars($v['est_year'] ?? '') ?>" min="1950" max="2026">
      </div>
      <div class="form-group">
        <label>Create Password <span class="req">*</span></label>
        <input type="password" name="password" autocomplete="new-password" placeholder="Minimum 8 characters">
      </div>
      <div class="form-group">
        <label>Confirm Password <span class="req">*</span></label>
        <input type="password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter password">
      </div>
    </div>
  </div>
  <div class="nav-bar">
    <div></div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm1">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(1)">&#128190; Save Draft</button>
      <button type="button" class="btn btn-next" onclick="goNext(1)">Next &rarr;</button>
    </div>
  </div>
</div>

<!-- ══ STEP 2: Signatory + Premises ══ -->
<div class="step-panel" id="panel2">
  <div class="panel-header"><div class="panel-num">2</div><div class="panel-title">Authorized Signatory &amp; Premises Details</div></div>
  <div class="panel-body">
    <div class="sub-sec">
      <div class="sub-sec-head">Section 3 — Authorized Signatory</div>
      <div class="sub-sec-body">
        <div class="form-grid">
          <div class="form-group"><label>Full Name <span class="req">*</span></label><input type="text" name="s3_name" value="<?= htmlspecialchars($v['s3_name'] ?? '') ?>"></div>
          <div class="form-group"><label>Father's / Husband's Name <span class="req">*</span></label><input type="text" name="s3_father_name" value="<?= htmlspecialchars($v['s3_father_name'] ?? '') ?>"></div>
          <div class="form-group"><label>Designation <span class="req">*</span></label><input type="text" name="s3_designation" value="<?= htmlspecialchars($v['s3_designation'] ?? '') ?>" placeholder="Director / Principal"></div>
          <div class="form-group"><label>Qualification</label><input type="text" name="s3_qualification" value="<?= htmlspecialchars($v['s3_qualification'] ?? '') ?>"></div>
          <div class="form-group"><label>Experience (Years)</label><input type="number" name="s3_experience" value="<?= htmlspecialchars($v['s3_experience'] ?? '') ?>" min="0"></div>
          <div class="form-group">
            <label>ID Proof Type <span class="req">*</span></label>
            <select name="s3_id_type">
              <option value="">-- Select --</option>
              <?php foreach(['Aadhaar','PAN Card','Passport','Voter ID','Driving Licence'] as $id): ?>
              <option <?= ($v['s3_id_type'] ?? '')==$id?'selected':'' ?>><?= $id ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>ID Proof Number <span class="req">*</span></label><input type="text" name="s3_id_number" value="<?= htmlspecialchars($v['s3_id_number'] ?? '') ?>"></div>
          <div class="form-group col-full"><label>Residential Address <span class="req">*</span></label><textarea name="s3_address" rows="2"><?= htmlspecialchars($v['s3_address'] ?? '') ?></textarea></div>
          <div class="upload-box"><label>Upload: ID Proof Document <span class="req">*</span></label><input type="file" name="s3_id_proof" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div></div>
          <div class="upload-box"><label>Upload: Signatory Signature <span class="req">*</span></label><input type="file" name="s3_signature" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">Clear scan &bull; Max 5MB</div></div>
        </div>
      </div>
    </div>
    <div class="sub-sec">
      <div class="sub-sec-head">Section 4 — Premises &amp; Infrastructure</div>
      <div class="sub-sec-body">
        <div class="form-grid">
          <div class="form-group col-full">
            <label>Type of Premises <span class="req">*</span></label>
            <div class="radio-group">
              <?php foreach(['Owned','Rented','Long Term Lease'] as $pt): ?>
              <label><input type="radio" name="s4_premises_type" value="<?= $pt ?>" <?= ($v['s4_premises_type'] ?? '')==$pt?'checked':'' ?>><?= $pt ?></label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="form-group"><label>Total Carpet Area (sq.ft.) <span class="req">*</span></label><input type="number" name="s4_carpet_area" value="<?= htmlspecialchars($v['s4_carpet_area'] ?? '') ?>"></div>
          <div class="form-group"><label>Number of Computers <span class="req">*</span></label><input type="number" name="s4_computers" value="<?= htmlspecialchars($v['s4_computers'] ?? '') ?>" min="1"></div>
          <div class="form-group"><label>Seating Capacity <span class="req">*</span></label><input type="number" name="s4_seating" value="<?= htmlspecialchars($v['s4_seating'] ?? '') ?>" min="1"></div>
          <div class="form-group">
            <label>Internet Connectivity</label>
            <select name="s4_internet">
              <option value="">-- Select --</option>
              <?php foreach(['Broadband','Leased Line','Fiber (FTTH)','VSAT'] as $ic): ?>
              <option <?= ($v['s4_internet'] ?? '')==$ic?'selected':'' ?>><?= $ic ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="upload-box"><label>Upload: Layout Map <span class="req">*</span></label><input type="file" name="s4_layout_map" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
          <div class="upload-box"><label>Upload: Building Photos <span class="req">*</span></label><input type="file" name="s4_building_photo" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
          <div class="upload-box"><label>Upload: Lease / Ownership Agreement <span class="req">*</span></label><input type="file" name="s4_agreement" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
        </div>
      </div>
    </div>
  </div>
  <div class="nav-bar">
    <button type="button" class="btn btn-prev" onclick="goPrev(2)">Back</button>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm2">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(2)">&#128190; Save Draft</button>
      <button type="button" class="btn btn-next" onclick="goNext(2)">Next &rarr;</button>
    </div>
  </div>
</div>

<!-- ══ STEP 3: Legal Status ══ -->
<div class="step-panel" id="panel3">
  <div class="panel-header"><div class="panel-num">3</div><div class="panel-title">Section 9 — Legal Status of Institute</div></div>
  <div class="panel-body">
    <div class="form-group">
      <label>Select Legal Status <span class="req">*</span></label>
      <div class="radio-group">
        <?php $lopts=['1'=>'Proprietorship','2'=>'Partnership Firm','3'=>'Society / Trust','4'=>'Pvt / Public Company','5'=>'Govt / PSU'];
        foreach($lopts as $val=>$lbl): ?>
        <label><input type="radio" name="s9_legal_status" value="<?= $val ?>" <?= ($v['s9_legal_status'] ?? '')==$val?'checked':'' ?> onchange="toggleLegal()"><?= $val ?>. <?= $lbl ?></label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='1'?'visible':'' ?>" id="legal_1">
      <div class="sub-sec"><div class="sub-sec-head">Proprietorship Details</div><div class="sub-sec-body">
        <div class="form-grid">
          <div class="form-group"><label>Proprietor Name</label><input type="text" name="s9_prop_name" value="<?= htmlspecialchars($v['s9_prop_name'] ?? '') ?>"></div>
          <div class="upload-box"><label>Upload: GST / Trade Licence / Govt. Registration <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
        </div>
      </div></div>
    </div>

    <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='2'?'visible':'' ?>" id="legal_2">
      <div class="sub-sec"><div class="sub-sec-head">Partnership Firm Documents</div><div class="sub-sec-body">
        <div class="form-grid">
          <div class="form-group"><label>Deed Date</label><input type="date" name="s9_partnership_date" value="<?= htmlspecialchars($v['s9_partnership_date'] ?? '') ?>"></div>
          <div class="form-group"><label>Registration Number</label><input type="text" name="s9_partnership_reg" value="<?= htmlspecialchars($v['s9_partnership_reg'] ?? '') ?>"></div>
          <div class="upload-box col-full"><label>Upload: Partnership Deed (Registered) <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
        </div>
      </div></div>
    </div>

    <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='3'?'visible':'' ?>" id="legal_3">
      <div class="sub-sec"><div class="sub-sec-head">Society / Trust Documents</div><div class="sub-sec-body">
        <div class="form-grid">
          <div class="form-group"><label>Registration Number</label><input type="text" name="s9_society_reg" value="<?= htmlspecialchars($v['s9_society_reg'] ?? '') ?>"></div>
          <div class="form-group"><label>Registration Date</label><input type="date" name="s9_society_date" value="<?= htmlspecialchars($v['s9_society_date'] ?? '') ?>"></div>
          <div class="upload-box"><label>Upload: Registration Certificate <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
          <div class="upload-box"><label>Upload: Memorandum / Trust Deed <span class="req">*</span></label><input type="file" name="s9_moa_doc" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
        </div>
      </div></div>
    </div>

    <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='4'?'visible':'' ?>" id="legal_4">
      <div class="sub-sec"><div class="sub-sec-head">Company Documents (MCA / ROC)</div><div class="sub-sec-body">
        <div class="form-grid">
          <div class="form-group"><label>CIN Number</label><input type="text" name="s9_cin" value="<?= htmlspecialchars($v['s9_cin'] ?? '') ?>"></div>
          <div class="form-group"><label>Date of Incorporation</label><input type="date" name="s9_incorp_date" value="<?= htmlspecialchars($v['s9_incorp_date'] ?? '') ?>"></div>
          <div class="upload-box"><label>Upload: Certificate of Incorporation <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
          <div class="upload-box"><label>Upload: MOA &amp; AOA <span class="req">*</span></label><input type="file" name="s9_moa_doc" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
        </div>
      </div></div>
    </div>

    <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='5'?'visible':'' ?>" id="legal_5">
      <div class="sub-sec"><div class="sub-sec-head">Government / PSU Documents</div><div class="sub-sec-body">
        <div class="form-grid">
          <div class="form-group"><label>Department / Ministry Name</label><input type="text" name="s9_dept_name" value="<?= htmlspecialchars($v['s9_dept_name'] ?? '') ?>"></div>
          <div class="upload-box"><label>Upload: Govt. Authorization Letter <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div></div>
        </div>
      </div></div>
    </div>
  </div>
  <div class="nav-bar">
    <button type="button" class="btn btn-prev" onclick="goPrev(3)">Back</button>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm3">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(3)">&#128190; Save Draft</button>
      <button type="button" class="btn btn-next" onclick="goNext(3)">Next &rarr;</button>
    </div>
  </div>
</div>

<!-- ══ STEP 4: Faculty + Financial ══ -->
<div class="step-panel" id="panel4">
  <div class="panel-header"><div class="panel-num">4</div><div class="panel-title">Faculty, Experience &amp; Financial Details</div></div>
  <div class="panel-body">

    <div class="sub-sec">
      <div class="sub-sec-head">Section 12 &amp; 13 — Faculty Member 1</div>
      <div class="sub-sec-body">
        <div class="form-grid three">
          <div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="s12_f1_name" value="<?= htmlspecialchars($v['s12_f1_name'] ?? '') ?>"></div>
          <div class="form-group"><label>Qualification <span class="req">*</span></label><input type="text" name="s12_f1_qual" value="<?= htmlspecialchars($v['s12_f1_qual'] ?? '') ?>"></div>
          <div class="form-group"><label>Examination Passed</label><input type="text" name="s12_f1_exam" value="<?= htmlspecialchars($v['s12_f1_exam'] ?? '') ?>"></div>
          <div class="form-group"><label>Year of Passing</label><input type="number" name="s12_f1_year" value="<?= htmlspecialchars($v['s12_f1_year'] ?? '') ?>" min="1970" max="2026"></div>
          <div class="form-group"><label>Board / University</label><input type="text" name="s12_f1_board" value="<?= htmlspecialchars($v['s12_f1_board'] ?? '') ?>"></div>
          <div class="form-group"><label>Designation</label><input type="text" name="s13_f1_desig" value="<?= htmlspecialchars($v['s13_f1_desig'] ?? '') ?>"></div>
          <div class="form-group"><label>Experience From</label><input type="date" name="s13_f1_from" value="<?= htmlspecialchars($v['s13_f1_from'] ?? '') ?>"></div>
          <div class="form-group"><label>Experience To</label><input type="date" name="s13_f1_to" value="<?= htmlspecialchars($v['s13_f1_to'] ?? '') ?>"></div>
          <div class="form-group"><label>Organization</label><input type="text" name="s13_f1_org" value="<?= htmlspecialchars($v['s13_f1_org'] ?? '') ?>"></div>
        </div>
        <div style="margin-top:12px"><div class="upload-box" style="max-width:360px">
          <label>Upload: Faculty 1 Certificate <span class="req">*</span></label>
          <input type="file" name="s12_faculty1_cert" accept=".jpg,.jpeg,.png,.pdf">
          <div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div>
        </div></div>
      </div>
    </div>

    <div class="sub-sec">
      <div class="sub-sec-head">Section 12 &amp; 13 — Faculty Member 2 <span style="font-weight:400;color:var(--muted)">(Optional)</span></div>
      <div class="sub-sec-body">
        <div class="form-grid three">
          <div class="form-group"><label>Name</label><input type="text" name="s12_f2_name" value="<?= htmlspecialchars($v['s12_f2_name'] ?? '') ?>"></div>
          <div class="form-group"><label>Qualification</label><input type="text" name="s12_f2_qual" value="<?= htmlspecialchars($v['s12_f2_qual'] ?? '') ?>"></div>
          <div class="form-group"><label>Examination Passed</label><input type="text" name="s12_f2_exam" value="<?= htmlspecialchars($v['s12_f2_exam'] ?? '') ?>"></div>
          <div class="form-group"><label>Year of Passing</label><input type="number" name="s12_f2_year" value="<?= htmlspecialchars($v['s12_f2_year'] ?? '') ?>" min="1970" max="2026"></div>
          <div class="form-group"><label>Board / University</label><input type="text" name="s12_f2_board" value="<?= htmlspecialchars($v['s12_f2_board'] ?? '') ?>"></div>
          <div class="form-group"><label>Designation</label><input type="text" name="s13_f2_desig" value="<?= htmlspecialchars($v['s13_f2_desig'] ?? '') ?>"></div>
          <div class="form-group"><label>Experience From</label><input type="date" name="s13_f2_from" value="<?= htmlspecialchars($v['s13_f2_from'] ?? '') ?>"></div>
          <div class="form-group"><label>Experience To</label><input type="date" name="s13_f2_to" value="<?= htmlspecialchars($v['s13_f2_to'] ?? '') ?>"></div>
          <div class="form-group"><label>Organization</label><input type="text" name="s13_f2_org" value="<?= htmlspecialchars($v['s13_f2_org'] ?? '') ?>"></div>
        </div>
        <div style="margin-top:12px"><div class="upload-box" style="max-width:360px">
          <label>Upload: Faculty 2 Certificate <span class="opt">(optional)</span></label>
          <input type="file" name="s12_faculty2_cert" accept=".jpg,.jpeg,.png,.pdf">
          <div class="upload-hint">JPG/PNG/PDF &bull; Max 5MB</div>
        </div></div>
      </div>
    </div>

    <div class="sub-sec">
      <div class="sub-sec-head">Section 14 — Financial &amp; Placement Details</div>
      <div class="sub-sec-body">
        <div class="form-grid three">
          <div class="form-group">
            <label>Financial Year <span class="req">*</span></label>
            <select name="s14_fy">
              <option value="">-- Select --</option>
              <?php foreach(['2024-25','2023-24','2022-23'] as $fy): ?>
              <option <?= ($v['s14_fy'] ?? '')==$fy?'selected':'' ?>><?= $fy ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Turnover — IT / Computer (₹)</label><input type="number" name="s14_turnover_it" value="<?= htmlspecialchars($v['s14_turnover_it'] ?? '') ?>" placeholder="INR"></div>
          <div class="form-group"><label>Turnover — Other (₹)</label><input type="number" name="s14_turnover_other" value="<?= htmlspecialchars($v['s14_turnover_other'] ?? '') ?>" placeholder="INR"></div>
          <div class="form-group">
            <label>Income Tax Exempted?</label>
            <div class="radio-group">
              <label><input type="radio" name="s14_tax_exempt" value="Yes" <?= ($v['s14_tax_exempt'] ?? '')=='Yes'?'checked':'' ?>>Yes</label>
              <label><input type="radio" name="s14_tax_exempt" value="No" <?= ($v['s14_tax_exempt'] ?? '')=='No'?'checked':'' ?>>No</label>
            </div>
          </div>
          <div class="form-group"><label>Students Trained (Last FY)</label><input type="number" name="s14_students_trained" value="<?= htmlspecialchars($v['s14_students_trained'] ?? '') ?>" min="0"></div>
          <div class="form-group"><label>Students Placed (Last FY)</label><input type="number" name="s14_students_placed" value="<?= htmlspecialchars($v['s14_students_placed'] ?? '') ?>" min="0"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="nav-bar">
    <button type="button" class="btn btn-prev" onclick="goPrev(4)">Back</button>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm4">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(4)">&#128190; Save Draft</button>
      <button type="button" class="btn btn-next" onclick="goNext(4)">Next &rarr;</button>
    </div>
  </div>
</div>

<!-- ══ STEP 5: Documents + Submit ══ -->
<div class="step-panel" id="panel5">
  <div class="panel-header"><div class="panel-num">5</div><div class="panel-title">Section 17 — Document Uploads &amp; Final Submission</div></div>
  <div class="panel-body">
    <p style="font-size:12.5px;color:var(--muted);margin-bottom:14px">Upload all applicable documents. Formats: JPG, PNG, PDF. Max 5MB each.</p>

    <table class="doc-table">
      <thead><tr><th style="width:36px">#</th><th>Document Name</th><th style="width:130px">Status</th><th style="width:250px">Upload</th></tr></thead>
      <tbody>
      <?php
      $docs=[
        ['s17_id_proof',       'Authorized Signatory — ID Proof',                                       true],
        ['s17_signatory_sig',  'Authorized Signatory — Specimen Signature',                             true],
        ['s17_layout_map',     'Layout Map of Premises',                                               true],
        ['s17_reg_cert',       'Registration Certificate from any Govt. Authority',                    true],
        ['s17_franchise_agmt', 'Franchisee / Licensee Agreement',                                     false],
        ['s17_registrar_reg',  'Registration with Registrar / Sub Registrar',                         false],
        ['s17_tax_reg',        'Registration with Sales Tax / Services Tax / Other Tax Authority',     false],
        ['s17_lease_deed',     'Lease / Rent Agreement / Ownership Deed with NOC',                    true],
        ['s17_other_doc',      'Any Other Relevant Document',                                          false],
        ['s17_building_photos','Photos of Building (Classrooms, Lab, Library, Washrooms, Reception etc.)',true],
      ];
      foreach($docs as $i=>[$fname,$label,$req]):
      ?>
      <tr>
        <td style="font-weight:700;color:var(--navy2);text-align:center"><?= $i+1 ?></td>
        <td><?= htmlspecialchars($label) ?></td>
        <td><?php if($req): ?><span style="color:var(--error);font-weight:700;font-size:12px">&#9679; Required</span><?php else: ?><span style="color:var(--muted);font-size:12px">&#9675; If Applicable</span><?php endif; ?></td>
        <td style="padding:6px 11px">
          <input type="file" name="<?= $fname ?>" accept=".jpg,.jpeg,.png,.pdf" style="font-size:11.5px;width:100%">
          <div style="font-size:10px;color:var(--muted);margin-top:1px">JPG/PNG/PDF &bull; Max 5MB</div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="decl-box">
      <label>
        <input type="checkbox" name="declaration" value="1" <?= !empty($v['declaration'])?'checked':'' ?>>
        <span>I hereby declare that all information provided in this application is true, correct, and complete to the best of my knowledge. I understand that any false information may result in rejection or cancellation of Training Partner empanelment by NIELIT.</span>
      </label>
    </div>
  </div>
  <div class="nav-bar">
    <button type="button" class="btn btn-prev" onclick="goPrev(5)">Back</button>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm5">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(5)">&#128190; Save Draft</button>
      <button type="submit" class="btn btn-submit">&#128196; Submit Application</button>
    </div>
  </div>
</div>

</form>
</div><!-- /.form-card -->

<div style="text-align:center;margin-top:14px;font-size:12px;color:var(--muted);">
  Already registered? <a href="tp_login.php" style="color:var(--teal);font-weight:600">Login here</a> &bull; Help: <a href="mailto:tp@nielit.gov.in" style="color:var(--teal)">tp@nielit.gov.in</a> &bull; 1800-XXX-XXXX (Toll Free)
</div>

<?php endif; ?>
</div><!-- /.wizard-wrap -->

<footer class="site-footer" role="contentinfo">
  <div style="max-width:1200px;margin:auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
    <div>&copy; <?= date('Y') ?> NIELIT, Bhubaneswar &mdash; Ministry of Electronics &amp; IT, Government of India</div>
    <div><a href="#">Privacy Policy</a> &bull; <a href="#">Terms of Use</a> &bull; <a href="#">Accessibility</a> &bull; <a href="#">Sitemap</a></div>
  </div>
</footer>

</div><!-- /.page-wrapper -->

<script>
// ── WIZARD JS ──
var cur=1, tot=5;
var pcts={1:10,2:30,3:50,4:70,5:90};

function updateUI(s){
  document.querySelectorAll('.step-panel').forEach(function(p){p.classList.remove('active')});
  document.getElementById('panel'+s).classList.add('active');
  for(var i=1;i<=tot;i++){
    var c=document.getElementById('sc'+i),l=document.getElementById('sl'+i);
    c.className='step-circle'; l.className='step-label';
    if(i<s){c.classList.add('done');l.classList.add('done');c.innerHTML='&#10003;'}
    else if(i===s){c.classList.add('active');l.classList.add('active');c.innerHTML=i}
    else{c.innerHTML=i}
  }
  document.getElementById('progressFill').style.width=pcts[s]+'%';
  document.getElementById('progressPct').textContent=pcts[s]+'%';
  window.scrollTo({top:0,behavior:'smooth'});
}

function goNext(f){cur=Math.min(f+1,tot);updateUI(cur)}
function goPrev(f){cur=Math.max(f-1,1);updateUI(cur)}

function saveDraft(step){
  var fd=new FormData(document.getElementById('tpForm'));
  fd.set('action','save_draft');
  fetch(window.location.href,{method:'POST',body:fd})
    .then(function(r){return r.json()})
    .then(function(d){
      if(d.status==='ok'){
        var m=document.getElementById('dm'+step);
        if(m){m.style.display='inline';setTimeout(function(){m.style.display='none'},3000)}
      }
    }).catch(function(){alert('Draft save failed. Try again.')});
}

function clearDraft(){
  if(!confirm('Clear saved draft? This cannot be undone.'))return;
  fetch(window.location.href,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=clear_draft'})
    .then(function(){location.reload()});
}

function toggleLegal(){
  document.querySelectorAll('.cond-block').forEach(function(e){e.classList.remove('visible')});
  var s=document.querySelector('input[name="s9_legal_status"]:checked');
  if(s){var b=document.getElementById('legal_'+s.value);if(b)b.classList.add('visible')}
}

document.addEventListener('DOMContentLoaded',function(){
  <?php if(!empty($errors)): ?>updateUI(5);<?php else: ?>updateUI(1);<?php endif; ?>
  var lc=document.querySelector('input[name="s9_legal_status"]:checked');
  if(lc)toggleLegal();
});
</script>
</body>
</html>
