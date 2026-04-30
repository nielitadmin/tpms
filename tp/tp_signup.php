<?php
session_start();
require_once 'db.php';

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
<title>TP Registration | NIELIT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<style>
:root {
  --navy:    #002855;
  --navy2:   #00438a;
  --saffron: #d95c04;
  --gold:    #b8860b;
  --green:   #0a7c3e;
  --white:   #ffffff;
  --border:  #c0cfdf;
  --text:    #1a2533;
  --muted:   #556070;
  --error:   #b72b2b;
  --success: #0a7c3e;
  --glass:   rgba(255,255,255,0.82);
  --glass-border: rgba(255,255,255,0.55);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  color: var(--text);
  font-size: 14px;
  line-height: 1.65;
  min-height: 100vh;
  overflow-x: hidden;
  background: #020e1f;
}

/* ══════════════════════════════════════
   3D ANIMATED BACKGROUND CANVAS
══════════════════════════════════════ */
#bgCanvas {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  z-index: 0;
  pointer-events: none;
}

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
  background: rgba(0,20,50,0.92);
  backdrop-filter: blur(8px);
  color: #90b8e0;
  font-size: 11.5px;
  text-align: center;
  padding: 5px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.gov-topbar a { color: #6aaae0; text-decoration: none; margin: 0 6px; }
.gov-topbar a:hover { color: #fff; }

/* ══════════════════════════════════════
   HEADER (glass)
══════════════════════════════════════ */
.header {
  background: rgba(255,255,255,0.94);
  backdrop-filter: blur(18px);
  border-bottom: 3px solid var(--saffron);
  box-shadow: 0 4px 32px rgba(0,30,80,0.18);
}
.tricolor { height: 5px; background: linear-gradient(to right, #FF9933 33.3%, #fff 33.3% 66.6%, #138808 66.6%); }
.header-inner {
  max-width: 1080px; margin: auto;
  display: flex; align-items: center; gap: 16px;
  padding: 10px 20px;
}
.logo-circle {
  width: 64px; height: 64px; border-radius: 50%;
  background: linear-gradient(135deg, var(--navy), var(--navy2));
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 9px; font-weight: 700;
  text-align: center; line-height: 1.3;
  border: 3px solid var(--gold); flex-shrink: 0;
  box-shadow: 0 4px 20px rgba(0,30,80,0.3);
}
.hdr-text { flex: 1; text-align: center; }
.hdr-text .ministry { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
.hdr-text h1 { font-family: 'Playfair Display', serif; font-size: 19px; color: var(--navy); line-height: 1.2; }
.hdr-text h1 span { color: var(--saffron); }
.hdr-text .tagline { font-size: 11px; color: var(--green); font-weight: 600; margin-top: 2px; }

/* ══════════════════════════════════════
   NAVBAR
══════════════════════════════════════ */
.navbar {
  background: linear-gradient(to right, var(--navy), var(--navy2));
  box-shadow: 0 2px 16px rgba(0,20,60,0.35);
}
.navbar ul { max-width: 1080px; margin: auto; list-style: none; display: flex; flex-wrap: wrap; }
.navbar ul li a {
  display: block; padding: 10px 14px; color: #b8d4f0;
  text-decoration: none; font-size: 12.5px; font-weight: 500;
  border-bottom: 3px solid transparent; transition: .18s;
}
.navbar ul li a:hover, .navbar ul li a.active {
  background: rgba(255,255,255,0.1); color: #fff;
  border-bottom-color: var(--saffron);
}

/* ══════════════════════════════════════
   PAGE TITLE BAR (glass)
══════════════════════════════════════ */
.page-title {
  background: linear-gradient(135deg, rgba(0,30,70,0.88), rgba(0,60,140,0.82));
  backdrop-filter: blur(14px);
  color: #fff; text-align: center; padding: 20px 20px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.12);
}
.page-title .bc { font-size: 11px; color: #80b8e8; margin-bottom: 4px; }
.page-title .bc span { color: #ffa060; }
.page-title h2 { font-family: 'Playfair Display', serif; font-size: 20px; text-shadow: 0 2px 12px rgba(0,0,0,0.4); }
.page-title p { font-size: 12px; color: #80b8e8; margin-top: 3px; }

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
  background: var(--glass);
  backdrop-filter: blur(20px);
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  padding: 18px 22px 14px;
  margin-bottom: 18px;
  box-shadow: 0 8px 40px rgba(0,20,80,0.18);
}
.step-bar-wrap {
  display: flex; align-items: flex-start; justify-content: space-between;
  position: relative; margin-bottom: 12px;
}
.step-bar-wrap::before {
  content: ''; position: absolute;
  top: 16px; left: 0; right: 0; height: 3px;
  background: rgba(0,30,80,0.12); z-index: 0;
  border-radius: 2px;
}
.step-item { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 1; }
.step-circle {
  width: 34px; height: 34px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 13px;
  border: 3px solid #b0c4dc; background: rgba(255,255,255,0.7); color: #8090a0;
  transition: all .35s cubic-bezier(.34,1.56,.64,1);
}
.step-circle.done {
  background: linear-gradient(135deg, var(--green), #14a05a);
  border-color: var(--green); color: #fff;
  box-shadow: 0 4px 16px rgba(10,124,62,0.4);
}
.step-circle.active {
  background: linear-gradient(135deg, var(--navy2), #0066cc);
  border-color: var(--navy2); color: #fff;
  box-shadow: 0 4px 20px rgba(0,68,138,0.45);
  transform: scale(1.12);
}
.step-label { font-size: 10.5px; color: var(--muted); margin-top: 6px; text-align: center; font-weight: 500; line-height: 1.3; max-width: 86px; }
.step-label.active { color: var(--navy2); font-weight: 700; }
.step-label.done { color: var(--green); }

.progress-row { display: flex; align-items: center; gap: 10px; }
.progress-track { flex: 1; height: 9px; background: rgba(0,30,80,0.1); border-radius: 6px; overflow: hidden; }
.progress-fill {
  height: 100%;
  background: linear-gradient(to right, var(--navy2), var(--saffron));
  border-radius: 6px; transition: width .5s cubic-bezier(.4,0,.2,1);
  box-shadow: 0 0 12px rgba(217,92,4,0.4);
}
.progress-pct { font-size: 12px; font-weight: 700; color: var(--navy2); min-width: 36px; text-align: right; }

/* ══════════════════════════════════════
   FORM CARD (glass)
══════════════════════════════════════ */
.form-card {
  background: var(--glass);
  backdrop-filter: blur(22px);
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 12px 60px rgba(0,20,80,0.22);
}
.step-panel { display: none; }
.step-panel.active { display: block; animation: panelIn .35s ease; }
@keyframes panelIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Panel header */
.panel-header {
  background: linear-gradient(to right, rgba(0,40,85,0.08), rgba(0,80,158,0.05));
  border-left: 5px solid var(--navy2);
  padding: 13px 20px; display: flex; align-items: center; gap: 12px;
  border-bottom: 1px solid rgba(0,30,80,0.08);
}
.panel-num {
  background: linear-gradient(135deg, var(--navy2), #0066cc);
  color: #fff; width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; flex-shrink: 0;
  box-shadow: 0 3px 12px rgba(0,68,138,0.4);
}
.panel-title { font-size: 14px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: .5px; }
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
  font-family: 'DM Sans', sans-serif;
  color: var(--text);
  background: rgba(255,255,255,0.85);
  width: 100%;
  transition: all .18s;
  box-shadow: inset 0 1px 3px rgba(0,20,60,0.06);
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: var(--navy2);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(0,68,138,0.12), inset 0 1px 3px rgba(0,20,60,0.04);
}
.form-group textarea { resize: vertical; min-height: 68px; }

/* Upload box */
.upload-box {
  border: 2px dashed #b8cce0;
  border-radius: 8px;
  padding: 11px 13px;
  background: rgba(240,248,255,0.7);
  transition: all .18s;
}
.upload-box:hover { border-color: var(--navy2); background: rgba(225,240,255,0.85); }
.upload-box label { font-size: 12.5px; font-weight: 600; color: var(--navy); display: block; margin-bottom: 6px; }
.upload-box label .req { color: var(--error); }
.upload-box label .opt { color: var(--muted); font-weight: 400; font-size: 11px; }
.upload-box input[type=file] { width: 100%; font-size: 12px; color: var(--muted); cursor: pointer; }
.upload-hint { font-size: 10.5px; color: var(--muted); margin-top: 3px; }

/* Radio group */
.radio-group { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
.radio-group label {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 12px;
  border: 1.5px solid #c8d8e8; border-radius: 6px;
  cursor: pointer; font-size: 13px; font-weight: 500;
  background: rgba(255,255,255,0.7);
  transition: all .15s;
}
.radio-group label:hover { border-color: var(--navy2); background: rgba(225,240,255,0.9); }
.radio-group input[type=radio] { accent-color: var(--navy2); }

/* Sub section */
.sub-sec {
  background: rgba(235,244,255,0.6);
  border: 1px solid rgba(0,60,140,0.1);
  border-radius: 8px; margin-top: 14px; overflow: hidden;
}
.sub-sec-head {
  background: rgba(0,60,140,0.07);
  padding: 8px 14px; font-size: 12.5px; font-weight: 700;
  color: var(--navy); border-left: 3px solid var(--saffron);
}
.sub-sec-body { padding: 14px; }

/* Conditional blocks */
.cond-block { display: none; }
.cond-block.visible { display: block; animation: panelIn .3s ease; }

/* Document table */
.doc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.doc-table thead tr { background: linear-gradient(to right, var(--navy), var(--navy2)); color: #fff; }
.doc-table thead th { padding: 10px 12px; text-align: left; font-weight: 600; font-size: 12.5px; }
.doc-table tbody tr:nth-child(even) { background: rgba(230,240,255,0.5); }
.doc-table tbody tr { border-bottom: 1px solid rgba(0,40,100,0.08); }
.doc-table tbody td { padding: 9px 12px; vertical-align: middle; }

/* Alerts */
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 14px; font-size: 13px; }
.alert-error { background: rgba(183,43,43,0.08); border-left: 4px solid var(--error); color: var(--error); }
.alert-success { background: rgba(10,124,62,0.08); border-left: 4px solid var(--success); color: var(--success); font-size: 14px; }
.alert ul { padding-left: 16px; margin-top: 4px; }

/* Draft banner */
.draft-banner {
  background: rgba(255,248,220,0.9);
  border: 1px solid var(--gold); border-radius: 8px;
  padding: 10px 14px; font-size: 12.5px; color: #7a5a00;
  margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
  backdrop-filter: blur(8px);
}

/* Nav bar */
.nav-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 20px;
  background: rgba(0,30,80,0.04);
  border-top: 1px solid rgba(0,30,80,0.08);
  flex-wrap: wrap; gap: 10px;
}
.btn {
  padding: 9px 22px; font-size: 13px; font-weight: 700;
  font-family: 'DM Sans', sans-serif; border-radius: 6px;
  cursor: pointer; border: none; transition: all .2s;
  text-transform: uppercase; letter-spacing: .5px;
}
.btn-prev { background: rgba(255,255,255,0.8); color: var(--muted); border: 1.5px solid #c8d8e8; }
.btn-prev:hover { background: #e8f0f8; color: var(--navy); }
.btn-next {
  background: linear-gradient(135deg, var(--navy2), #0066cc);
  color: #fff;
  box-shadow: 0 4px 16px rgba(0,68,138,0.35);
}
.btn-next:hover { background: linear-gradient(135deg, var(--navy), var(--navy2)); box-shadow: 0 6px 24px rgba(0,68,138,0.45); transform: translateY(-1px); }
.btn-draft {
  background: rgba(255,248,220,0.9); color: #7a5a00;
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
  background: rgba(255,248,220,0.85); border: 2px solid var(--gold);
  border-radius: 8px; padding: 14px; margin-top: 18px;
}
.decl-box label { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13px; }
.decl-box input[type=checkbox] { margin-top: 3px; accent-color: var(--navy2); flex-shrink: 0; width: 16px; height: 16px; }

/* Footer */
.site-footer {
  background: rgba(0,20,50,0.95);
  backdrop-filter: blur(10px);
  color: #7090b8; text-align: center; padding: 16px; font-size: 12px;
}
.site-footer a { color: #7090b8; }

@media (max-width: 640px) {
  .form-grid, .form-grid.three { grid-template-columns: 1fr; }
  .step-label { display: none; }
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════
     3D ANIMATED BACKGROUND
══════════════════════════════════════ -->
<canvas id="bgCanvas"></canvas>

<div class="page-wrapper">

<!-- Gov Topbar -->
<div class="gov-topbar">
  Government of India &nbsp;|&nbsp;
  <a href="#">MeitY</a> | <a href="#">NIELIT HQ</a> | <a href="#">Grievance</a> | <a href="#">RTI</a> | <a href="#">Screen Reader Access</a>
</div>

<!-- Header -->
<div class="header">
  <div class="tricolor"></div>
  <div class="header-inner">
    <div class="logo-circle">NIELIT<br>राष्ट्रीय<br>इलेक्ट्रॉनिकी</div>
    <div class="hdr-text">
      <div class="ministry">Ministry of Electronics &amp; Information Technology, Govt. of India</div>
      <h1>NIELIT — <span>National Institute of Electronics &amp; Information Technology</span></h1>
      <div class="tagline">&#x1F1EE;&#x1F1F3; An Autonomous Scientific Society under MeitY</div>
    </div>
  </div>
  <div class="tricolor"></div>
</div>

<!-- Navbar -->
<nav class="navbar">
  <ul>
    <li><a href="#">Home</a></li>
    <li><a href="#">About NIELIT</a></li>
    <li><a href="#">Courses</a></li>
    <li><a href="#" class="active">Training Partner</a></li>
    <li><a href="#">Examination</a></li>
    <li><a href="#">Certification</a></li>
    <li><a href="#">Downloads</a></li>
    <li><a href="#">Contact Us</a></li>
  </ul>
</nav>

<!-- Page Title -->
<div class="page-title">
  <div class="bc">Home &raquo; Training Partner &raquo; <span>New Registration</span></div>
  <h2>Training Partner (TP) — Online Registration</h2>
  <p>Complete all 5 steps &bull; Save as Draft anytime &bull; Resume later</p>
</div>

<!-- ══════════════════════════════════════
     WIZARD
══════════════════════════════════════ -->
<div class="wizard-wrap">

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
        <input type="email" name="email" value="<?= htmlspecialchars($v['email'] ?? '') ?>" placeholder="office@institute.in">
      </div>
      <div class="form-group">
        <label>Mobile Number <span class="req">*</span></label>
        <input type="tel" name="mobile" value="<?= htmlspecialchars($v['mobile'] ?? '') ?>" placeholder="10-digit mobile">
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
        <input type="password" name="password" placeholder="Minimum 8 characters">
      </div>
      <div class="form-group">
        <label>Confirm Password <span class="req">*</span></label>
        <input type="password" name="confirm_password" placeholder="Re-enter password">
      </div>
    </div>
  </div>
  <div class="nav-bar">
    <div></div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm1">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(1)">&#128190; Save Draft</button>
      <button type="button" class="btn btn-next" onclick="goNext(1)">Next &#8594;</button>
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
    <button type="button" class="btn btn-prev" onclick="goPrev(2)">&#8592; Back</button>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm2">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(2)">&#128190; Save Draft</button>
      <button type="button" class="btn btn-next" onclick="goNext(2)">Next &#8594;</button>
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
    <button type="button" class="btn btn-prev" onclick="goPrev(3)">&#8592; Back</button>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm3">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(3)">&#128190; Save Draft</button>
      <button type="button" class="btn btn-next" onclick="goNext(3)">Next &#8594;</button>
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
    <button type="button" class="btn btn-prev" onclick="goPrev(4)">&#8592; Back</button>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm4">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(4)">&#128190; Save Draft</button>
      <button type="button" class="btn btn-next" onclick="goNext(4)">Next &#8594;</button>
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
    <button type="button" class="btn btn-prev" onclick="goPrev(5)">&#8592; Back</button>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span class="draft-saved-msg" id="dm5">&#10003; Draft Saved!</span>
      <button type="button" class="btn btn-draft" onclick="saveDraft(5)">&#128190; Save Draft</button>
      <button type="submit" class="btn btn-submit">&#128196; Submit Application</button>
    </div>
  </div>
</div>

</form>
</div><!-- /.form-card -->

<div style="text-align:center;margin-top:14px;font-size:12px;color:rgba(255,255,255,0.65);text-shadow:0 1px 4px rgba(0,0,0,0.4)">
  Already registered? <a href="tp_login.php" style="color:#80c4ff;font-weight:600">Login here</a> &bull;
  Help: <a href="mailto:tp@nielit.gov.in" style="color:#80c4ff">tp@nielit.gov.in</a> &bull; 1800-XXX-XXXX (Toll Free)
</div>

<?php endif; ?>
</div><!-- /.wizard-wrap -->

<footer class="site-footer">
  &copy; <?= date('Y') ?> NIELIT — National Institute of Electronics &amp; Information Technology<br>
  Ministry of Electronics &amp; Information Technology, Government of India<br>
  <a href="#">Privacy Policy</a> &bull; <a href="#">Terms of Use</a> &bull; <a href="#">Accessibility</a> &bull; <a href="#">Sitemap</a>
</footer>

</div><!-- /.page-wrapper -->

<!-- ══════════════════════════════════════
     3D BACKGROUND SCRIPT (WebGL/Canvas)
══════════════════════════════════════ -->
<script>
(function(){
  var canvas = document.getElementById('bgCanvas');
  var ctx = canvas.getContext('2d');
  var W, H, nodes = [], lines = [], particles = [];
  var mouse = {x: -9999, y: -9999};

  function resize(){
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  window.addEventListener('resize', resize);
  window.addEventListener('mousemove', function(e){ mouse.x = e.clientX; mouse.y = e.clientY; });
  resize();

  // ─ Nodes (floating 3D-looking spheres / dots)
  var NODE_COUNT = 55;
  for(var i=0;i<NODE_COUNT;i++){
    var depth = 0.2 + Math.random() * 0.8; // simulate Z
    nodes.push({
      x: Math.random()*W,
      y: Math.random()*H,
      vx: (Math.random()-0.5)*0.4,
      vy: (Math.random()-0.5)*0.4,
      depth: depth,
      r: depth * 4 + 1,
      hue: 200 + Math.random()*40,
      alpha: depth * 0.7 + 0.1,
      pulse: Math.random()*Math.PI*2,
      pulseSpeed: 0.008 + Math.random()*0.012,
    });
  }

  // ─ Floating particles (small sparkles)
  for(var j=0;j<80;j++){
    particles.push({
      x: Math.random()*W,
      y: Math.random()*H,
      size: Math.random()*1.8 + 0.4,
      speed: 0.2 + Math.random()*0.6,
      alpha: Math.random()*0.5 + 0.1,
      drift: (Math.random()-0.5)*0.3,
    });
  }

  // ─ 3D grid lines (perspective floor)
  var GRID_LINES = 16;
  var gridAngle = 0;

  function drawGrid(){
    var cx = W/2, horizon = H*0.45;
    var vanishY = horizon;
    ctx.save();
    ctx.strokeStyle = 'rgba(0,100,200,0.06)';
    ctx.lineWidth = 1;

    // Vertical lines (converge to vanishing point)
    for(var i=0;i<=GRID_LINES;i++){
      var t = i/GRID_LINES;
      var bx = W * t;
      ctx.beginPath();
      ctx.moveTo(bx, H);
      ctx.lineTo(cx + (bx - cx)*0.02, vanishY);
      ctx.stroke();
    }
    // Horizontal lines (spaced by perspective)
    for(var j=0;j<12;j++){
      var p = Math.pow((j+1)/12, 1.8);
      var y = vanishY + (H - vanishY) * p;
      var spread = 1 - (y - vanishY)/(H - vanishY);
      var lx = cx - (cx * (1-spread*0.98));
      var rx = cx + (cx * (1-spread*0.98));
      ctx.beginPath();
      ctx.moveTo(lx, y);
      ctx.lineTo(rx, y);
      ctx.globalAlpha = 0.04 + p*0.06;
      ctx.stroke();
      ctx.globalAlpha = 1;
    }
    ctx.restore();
  }

  function drawNodes(){
    // Draw connections
    for(var a=0;a<nodes.length;a++){
      for(var b=a+1;b<nodes.length;b++){
        var na=nodes[a], nb=nodes[b];
        var dx=na.x-nb.x, dy=na.y-nb.y;
        var dist=Math.sqrt(dx*dx+dy*dy);
        var maxDist = 140;
        if(dist<maxDist){
          var alpha = (1-dist/maxDist)*0.22*((na.depth+nb.depth)/2);
          ctx.save();
          ctx.globalAlpha=alpha;
          var grad=ctx.createLinearGradient(na.x,na.y,nb.x,nb.y);
          grad.addColorStop(0,'hsl('+na.hue+',80%,60%)');
          grad.addColorStop(1,'hsl('+nb.hue+',80%,60%)');
          ctx.strokeStyle=grad;
          ctx.lineWidth=na.depth*1.2;
          ctx.beginPath();
          ctx.moveTo(na.x,na.y);
          ctx.lineTo(nb.x,nb.y);
          ctx.stroke();
          ctx.restore();
        }
      }
    }

    // Draw nodes
    for(var i=0;i<nodes.length;i++){
      var n=nodes[i];
      n.pulse+=n.pulseSpeed;
      var pulseR = n.r + Math.sin(n.pulse)*1.2;

      // Mouse repulsion
      var mdx=n.x-mouse.x, mdy=n.y-mouse.y;
      var mdist=Math.sqrt(mdx*mdx+mdy*mdy);
      if(mdist<120){
        n.vx += mdx/mdist*0.3;
        n.vy += mdy/mdist*0.3;
      }

      n.x += n.vx; n.y += n.vy;
      n.vx *= 0.99; n.vy *= 0.99;
      if(n.x<-20) n.x=W+20;
      if(n.x>W+20) n.x=-20;
      if(n.y<-20) n.y=H+20;
      if(n.y>H+20) n.y=-20;

      // Glow
      ctx.save();
      var grd=ctx.createRadialGradient(n.x,n.y,0,n.x,n.y,pulseR*3.5);
      grd.addColorStop(0,'hsla('+n.hue+',90%,70%,'+n.alpha+')');
      grd.addColorStop(0.5,'hsla('+n.hue+',80%,50%,'+(n.alpha*0.3)+')');
      grd.addColorStop(1,'hsla('+n.hue+',70%,40%,0)');
      ctx.fillStyle=grd;
      ctx.beginPath();
      ctx.arc(n.x,n.y,pulseR*3.5,0,Math.PI*2);
      ctx.fill();

      // Core
      ctx.beginPath();
      ctx.arc(n.x,n.y,pulseR,0,Math.PI*2);
      var core=ctx.createRadialGradient(n.x-pulseR*0.3,n.y-pulseR*0.3,0,n.x,n.y,pulseR);
      core.addColorStop(0,'hsla('+n.hue+',100%,90%,'+(n.alpha+0.3)+')');
      core.addColorStop(1,'hsla('+n.hue+',80%,55%,'+n.alpha+')');
      ctx.fillStyle=core;
      ctx.fill();
      ctx.restore();
    }
  }

  function drawParticles(){
    for(var i=0;i<particles.length;i++){
      var p=particles[i];
      p.y -= p.speed;
      p.x += p.drift;
      if(p.y < -5){ p.y=H+5; p.x=Math.random()*W; }
      ctx.save();
      ctx.globalAlpha=p.alpha*(0.5+0.5*Math.sin(Date.now()*0.001+i));
      ctx.fillStyle='rgba(140,200,255,1)';
      ctx.beginPath();
      ctx.arc(p.x,p.y,p.size,0,Math.PI*2);
      ctx.fill();
      ctx.restore();
    }
  }

  // Nebula / atmosphere blobs
  function drawAtmosphere(){
    var blobs=[
      {x:W*0.15,y:H*0.25,r:W*0.28,h:210,s:0.06},
      {x:W*0.85,y:H*0.55,r:W*0.32,h:190,s:0.05},
      {x:W*0.5, y:H*0.8, r:W*0.25,h:170,s:0.04},
    ];
    for(var i=0;i<blobs.length;i++){
      var b=blobs[i];
      var g=ctx.createRadialGradient(b.x,b.y,0,b.x,b.y,b.r);
      g.addColorStop(0,'hsla('+b.h+',70%,25%,'+b.s+')');
      g.addColorStop(1,'hsla('+b.h+',50%,10%,0)');
      ctx.save();
      ctx.fillStyle=g;
      ctx.beginPath();
      ctx.arc(b.x,b.y,b.r,0,Math.PI*2);
      ctx.fill();
      ctx.restore();
    }
  }

  function draw(){
    // Deep space background
    ctx.fillStyle='#010e1e';
    ctx.fillRect(0,0,W,H);

    drawAtmosphere();
    drawGrid();
    drawParticles();
    drawNodes();

    // Vignette
    var vig=ctx.createRadialGradient(W/2,H/2,H*0.3,W/2,H/2,H*0.9);
    vig.addColorStop(0,'rgba(0,0,0,0)');
    vig.addColorStop(1,'rgba(0,5,20,0.55)');
    ctx.fillStyle=vig;
    ctx.fillRect(0,0,W,H);

    requestAnimationFrame(draw);
  }
  draw();
})();

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
