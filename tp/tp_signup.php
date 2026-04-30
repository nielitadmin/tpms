<?php
session_start();
require __DIR__ . '/../includes/config.php';

$message     = '';
$messageType = '';

/* ── File upload helper ── */
function upload_doc($key, $dir = 'uploads/tp_docs/') {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$key]['error'] !== UPLOAD_ERR_OK)   return false;
    if ($_FILES[$key]['size'] > 10 * 1024 * 1024)   return false; // 10 MB
    $ext  = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','pdf'])) return false;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = $key . '_' . time() . '_' . uniqid() . '.' . $ext;
    return move_uploaded_file($_FILES[$key]['tmp_name'], $dir . $name) ? $name : false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $center_id       = trim($_POST['center_id']       ?? '');
    $password        = $_POST['password']              ?? '';
    $confirm_password= $_POST['confirm_password']      ?? '';

    /* ── Password match ── */
    if ($password !== $confirm_password) {
        $message     = "Passwords do not match!";
        $messageType = "danger";
    } else {
        /* ── Upload all documents ── */
        $doc_fields = [
            'doc_id_proof','doc_signature','doc_layout_map','doc_govt_reg',
            'doc_franchisee','doc_sub_registrar','doc_sales_tax',
            'doc_lease_noc','doc_other','doc_building_photos',
            /* legal status docs */
            'doc_prop','doc_prop_auth','doc_part_deed','doc_part_reg',
            'doc_soc_cert','doc_soc_moa','doc_trust_deed','doc_trust_cert',
            'doc_co_inc','doc_co_moa',
            /* faculty */
            'doc_fac1','doc_fac2',
        ];
        $uploaded = [];
        $upload_ok = true;
        foreach ($doc_fields as $f) {
            $r = upload_doc($f);
            if ($r === false) { $upload_ok = false; break; }
            $uploaded[$f] = $r;
        }

        if (!$upload_ok) {
            $message     = "File upload error. Only JPG/PNG/PDF allowed, max 10MB each.";
            $messageType = "danger";
        } else {
            /*
            |-------------------------------------------------------------
            | DATABASE INSERT — connect via $pdo / $conn from config.php
            |-------------------------------------------------------------
            | Example (PDO):
            |
            | $hashed = password_hash($password, PASSWORD_DEFAULT);
            |
            | $stmt = $pdo->prepare("
            |   INSERT INTO training_partners
            |     (center_id, password, legal_status,
            |      sig_name, sig_mobile, sig_email,
            |      father_name, designation, qualification, experience,
            |      id_type, id_number,
            |      address1, address2, locality, state, pincode, landline,
            |      premise_type, premises_period, carpet_area, no_computers,
            |      seating_capacity, no_boys, no_girls, has_library,
            |      fac1_name, fac1_qual, fac1_exam, fac1_year, fac1_board,
            |      fac1_exp_period, fac1_org, fac1_doj, fac1_id_type, fac1_id_num,
            |      fac2_name, fac2_qual, fac2_exam, fac2_year, fac2_board,
            |      fac2_exp_period, fac2_org, fac2_doj, fac2_id_type, fac2_id_num,
            |      fin_year, turnover_it, turnover_other, income_tax_exempt,
            |      students_placed, fin_remarks,
            |      doc_id_proof, doc_signature, doc_layout_map, doc_govt_reg,
            |      doc_franchisee, doc_sub_registrar, doc_sales_tax,
            |      doc_lease_noc, doc_other, doc_building_photos,
            |      doc_prop, doc_prop_auth, doc_part_deed, doc_part_reg,
            |      doc_soc_cert, doc_soc_moa, doc_trust_deed, doc_trust_cert,
            |      doc_co_inc, doc_co_moa, doc_fac1, doc_fac2,
            |      status, created_at)
            |   VALUES
            |     (:center_id, :password, :legal_status,
            |      :sig_name, :sig_mobile, :sig_email, ...)
            | ");
            | $stmt->execute([':center_id' => $center_id, ':password' => $hashed, ...]);
            |
            */

            $message     = "Registration request sent successfully! Waiting for Admin approval.";
            $messageType = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Training Partner Registration | NIELIT TPS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@600;700&display=swap" rel="stylesheet">
<style>
/* ── Root Variables ── */
:root {
    --navy:       #00308f;
    --navy-dark:  #002070;
    --navy-mid:   #1a4daf;
    --gold:       #c8922a;
    --gold-lt:    #e8a830;
    --saffron:    #ff6600;
    --ind-green:  #138808;
    --bg:         #eef2f7;
    --white:      #ffffff;
    --border:     #c8d4e4;
    --text:       #1a2535;
    --muted:      #5a6a7e;
    --light:      #f4f7fb;
    --upload-bg:  #edf4fd;
    --upload-bd:  #9bbedd;
    --radius:     6px;
    --shadow:     0 2px 10px rgba(0,48,143,0.09);
}
*, *::before, *::after { box-sizing: border-box; }
body {
    font-family: 'Noto Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    font-size: 13.5px;
    line-height: 1.6;
    margin: 0; padding: 0;
}

/* ── Gov Strip ── */
.gov-strip {
    background: #fff;
    border-bottom: 1px solid #dce4ef;
    padding: 5px 0;
    font-size: 11px;
    color: #555;
}
.gov-strip .inner {
    max-width: 1100px; margin: 0 auto;
    padding: 0 18px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 4px;
}
.gov-strip a { color: var(--navy); text-decoration: none; margin-left: 14px; font-size: 11px; }

/* ── Header ── */
.site-header {
    background: var(--white);
    border-bottom: 4px solid var(--gold);
    box-shadow: 0 2px 6px rgba(0,0,0,0.07);
}
.header-inner {
    max-width: 1100px; margin: 0 auto;
    padding: 10px 18px;
    display: flex; align-items: center; gap: 16px;
}
.h-emblem img { height: 68px; }
.h-sep { width: 2px; height: 58px; background: linear-gradient(to bottom, transparent, var(--gold), transparent); flex-shrink: 0; }
.h-text { flex: 1; }
.h-text .hi   { font-family: 'Noto Serif', serif; font-size: 13px; color: var(--navy); font-weight: 600; }
.h-text .name { font-family: 'Noto Serif', serif; font-size: 24px; font-weight: 700; color: var(--navy); line-height: 1.15; }
.h-text .sub  { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
.h-right { text-align: right; flex-shrink: 0; }
.h-right .min { font-size: 12px; font-weight: 700; color: var(--navy); line-height: 1.5; }
.h-right .gov { font-size: 11px; color: var(--muted); }

/* Tricolour */
.tricolour { height: 5px; display: grid; grid-template-columns: 1fr 1fr 1fr; }
.tc1 { background: var(--saffron); }
.tc2 { background: #f0f0f0; }
.tc3 { background: var(--ind-green); }

/* ── Nav ── */
.main-nav { background: var(--navy-dark); }
.nav-inner { max-width: 1100px; margin: 0 auto; padding: 0 18px; display: flex; flex-wrap: wrap; }
.main-nav a {
    display: inline-block; color: rgba(255,255,255,0.85);
    text-decoration: none; font-size: 12.5px; padding: 9px 15px;
    border-right: 1px solid rgba(255,255,255,0.09);
    transition: background 0.2s;
}
.main-nav a:first-child { border-left: 1px solid rgba(255,255,255,0.09); }
.main-nav a:hover, .main-nav a.cur { background: var(--gold); color: #fff; }

/* ── Breadcrumb ── */
.breadcrumb-bar { background: #f0f4fa; border-bottom: 1px solid var(--border); padding: 7px 0; font-size: 12px; color: var(--muted); }
.breadcrumb-bar .inner { max-width: 1100px; margin: 0 auto; padding: 0 18px; }
.breadcrumb-bar a { color: var(--navy-mid); text-decoration: none; }
.breadcrumb-bar a:hover { text-decoration: underline; }
.breadcrumb-bar .sep { margin: 0 6px; color: #aaa; }

/* ── Page Banner ── */
.page-banner {
    background: linear-gradient(120deg, var(--navy-dark) 0%, var(--navy) 55%, var(--navy-mid) 100%);
    border-bottom: 4px solid var(--gold);
    padding: 22px 0 18px;
    position: relative; overflow: hidden;
}
.page-banner::before {
    content: ''; position: absolute; right: -50px; top: -50px;
    width: 260px; height: 260px; border-radius: 50%;
    background: rgba(255,255,255,0.04); pointer-events: none;
}
.page-banner .inner { max-width: 1100px; margin: 0 auto; padding: 0 18px; }
.page-banner h1 { font-family: 'Noto Serif', serif; font-size: 21px; font-weight: 700; color: #fff; margin-bottom: 5px; }
.page-banner p  { font-size: 13px; color: rgba(255,255,255,0.75); max-width: 620px; }

/* ── Layout ── */
.page-wrap { max-width: 1100px; margin: 24px auto 60px; padding: 0 18px; display: grid; grid-template-columns: 1fr 268px; gap: 22px; align-items: start; }

/* ── Sidebar ── */
.sidebar { position: sticky; top: 16px; }
.s-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 14px; overflow: hidden; }
.s-head { background: var(--navy); color: #fff; padding: 9px 14px; font-size: 12.5px; font-weight: 600; }
.s-body { padding: 12px 14px; }
.s-nav a { display: flex; align-items: center; gap: 7px; padding: 6px 0; border-bottom: 1px solid #eef2f7; color: var(--navy-mid); text-decoration: none; font-size: 12px; transition: color 0.2s; }
.s-nav a:last-child { border-bottom: none; }
.s-nav a:hover { color: var(--gold); }
.s-nav .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); flex-shrink: 0; }
.info-box { background: #fffde7; border: 1px solid #f9a825; border-radius: var(--radius); padding: 10px 13px; font-size: 12px; color: #4e342e; line-height: 1.75; }
.info-box strong { display: block; color: var(--navy); margin-bottom: 4px; font-size: 12.5px; }
.hl-row { display: flex; gap: 8px; margin-bottom: 5px; font-size: 12px; }
.hl-key { color: var(--muted); min-width: 52px; font-size: 11.5px; }
.hl-val { color: var(--navy); font-weight: 600; font-size: 12px; }

/* ── Form Card ── */
.form-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.form-card-head {
    background: var(--navy);
    padding: 13px 22px;
    display: flex; align-items: center; justify-content: space-between;
}
.form-card-head h4 { color: #fff; font-size: 15px; font-weight: 600; margin: 0; font-family: 'Noto Serif', serif; }
.form-card-head .tps-badge { background: var(--gold); color: #fff; font-size: 11px; font-weight: 700; padding: 3px 12px; border-radius: 12px; letter-spacing: 0.3px; }
.form-card-body { padding: 28px 28px 20px; }

/* ── Step Progress ── */
.steps-wrap { position: relative; margin-bottom: 32px; padding: 0 4px; }
.steps-track {
    position: absolute; top: 19px; left: 6%; right: 6%; height: 3px;
    background: var(--border); z-index: 1; border-radius: 2px;
}
.steps-fill {
    height: 100%; background: var(--navy); border-radius: 2px;
    transition: width 0.4s ease; width: 0%;
}
.steps-row { display: flex; justify-content: space-between; position: relative; z-index: 2; }
.step-col { display: flex; flex-direction: column; align-items: center; width: 60px; }
.step-circle {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px;
    background: var(--white); border: 2.5px solid var(--border);
    color: var(--muted); transition: all 0.3s;
}
.step-circle.done   { background: #198754; border-color: #198754; color: #fff; }
.step-circle.active { background: var(--navy); border-color: var(--navy); color: #fff; box-shadow: 0 0 0 4px rgba(0,48,143,0.15); }
.step-lbl { font-size: 10.5px; margin-top: 6px; color: var(--muted); font-weight: 500; text-align: center; }
.step-lbl.active { color: var(--navy); font-weight: 700; }

/* ── Alert ── */
.alert-govt {
    border-left: 5px solid; border-radius: var(--radius); padding: 12px 16px; margin-bottom: 18px;
    font-size: 13px; display: flex; gap: 10px; align-items: flex-start;
}
.alert-success { background: #e8f5e9; border-color: #2e7d32; color: #1b5e20; }
.alert-danger  { background: #fdecea; border-color: #c62828; color: #b71c1c; }
.alert-icon { font-size: 18px; line-height: 1; flex-shrink: 0; }

/* ── Form Steps ── */
.form-step { display: none; animation: fadeIn 0.35s; }
.form-step.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

.step-title {
    font-family: 'Noto Serif', serif;
    font-size: 15px; font-weight: 700; color: var(--navy);
    border-bottom: 2px solid var(--gold-lt); padding-bottom: 8px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}
.step-title .snum {
    background: var(--gold); color: #fff; font-family: 'Noto Sans', sans-serif;
    font-size: 11px; font-weight: 700; width: 24px; height: 24px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
}
.subsec { font-size: 11.5px; font-weight: 700; color: var(--navy-mid); text-transform: uppercase; letter-spacing: 0.5px; margin: 18px 0 10px; }

/* ── Field Styling ── */
.form-label { font-size: 12px; font-weight: 600; color: var(--navy); margin-bottom: 4px; }
.form-label .req { color: #c0392b; }
.form-control, .form-select {
    border: 1px solid var(--border); border-radius: 4px;
    padding: 7px 10px; font-size: 13px;
    font-family: 'Noto Sans', sans-serif; background: var(--light); color: var(--text);
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--navy); background: #fff;
    box-shadow: 0 0 0 3px rgba(0,48,143,0.09); outline: none;
}
.field-note { font-size: 11px; color: var(--muted); margin-top: 3px; }

/* ── Upload boxes ── */
.upload-box {
    border: 1.5px dashed var(--upload-bd); border-radius: 4px;
    padding: 10px 13px; background: var(--upload-bg);
    transition: border-color 0.2s; margin-bottom: 0;
}
.upload-box:hover { border-color: var(--navy); }
.upload-box .form-label { color: var(--navy); display: block; margin-bottom: 5px; }
.upload-box .form-control { background: transparent; border: none; padding: 0; font-size: 12px; }
.upload-box .form-control:focus { box-shadow: none; }
.upload-hint { font-size: 10.5px; color: var(--muted); margin-top: 3px; }

/* ── Doc table (Sec 17) ── */
.doc-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.doc-table th { background: #e4ecf8; color: var(--navy); padding: 8px 10px; border: 1px solid var(--border); font-weight: 700; font-size: 12px; }
.doc-table td { padding: 9px 10px; border: 1px solid var(--border); vertical-align: middle; }
.doc-table tr:nth-child(even) td { background: #f6f9fd; }
.doc-table tr:hover td { background: #e8f0fb; transition: background 0.15s; }
.sno { text-align: center; width: 36px; font-weight: 700; color: var(--navy); }
.badge-req { background: #fde8e8; color: #b71c1c; font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 10px; white-space: nowrap; }
.badge-opt { background: #e3f2fd; color: #0277bd; font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 10px; white-space: nowrap; }
.upload-td { width: 220px; }
.mini-hint { font-size: 10px; color: var(--muted); margin-top: 2px; }

/* ── Legal panels ── */
.legal-panel { display: none; background: #f0f6ff; border: 1px solid #b6cee8; border-radius: 4px; padding: 14px 16px; margin-top: 14px; }
.legal-panel.show { display: block; animation: fadeIn 0.3s; }
.legal-title { font-size: 12px; font-weight: 700; color: var(--navy); margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #c6d9ee; display: flex; align-items: center; gap: 6px; }
.legal-title::before { content: ''; display: inline-block; width: 11px; height: 11px; background: var(--gold); border-radius: 2px; flex-shrink: 0; }
.legal-note { font-size: 11.5px; color: var(--muted); margin-bottom: 12px; line-height: 1.7; background: #fff; border-left: 3px solid var(--gold-lt); padding: 6px 10px; border-radius: 0 4px 4px 0; }

/* ── Radio pills ── */
.radio-group { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.radio-pill { display: flex; align-items: center; gap: 6px; padding: 7px 13px; border: 1.5px solid var(--border); border-radius: 4px; cursor: pointer; font-size: 12.5px; background: var(--light); color: var(--text); transition: all 0.2s; user-select: none; }
.radio-pill input { accent-color: var(--navy); }
.radio-pill:hover { border-color: var(--navy-mid); background: #e8f0fb; }
.radio-pill.on { border-color: var(--navy); background: #ddeaf9; color: var(--navy); font-weight: 600; }

/* ── Faculty block ── */
.fac-block { border: 1px solid var(--border); border-radius: 4px; padding: 14px 16px; background: #f8fafd; margin-bottom: 14px; }
.fac-block:last-child { margin-bottom: 0; }
.fac-title { font-size: 12px; font-weight: 700; color: var(--navy-mid); margin-bottom: 12px; padding-bottom: 7px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }
.fac-badge { background: var(--navy); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 9px; border-radius: 10px; }

/* ── Nav buttons ── */
.nav-btns { display: flex; justify-content: space-between; align-items: center; margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--border); }
.btn-govt-prev { background: transparent; color: var(--muted); border: 1px solid var(--border); padding: 9px 22px; font-size: 13px; font-family: 'Noto Sans', sans-serif; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
.btn-govt-prev:hover { background: var(--bg); }
.btn-govt-next { background: var(--navy); color: #fff; border: none; padding: 10px 28px; font-size: 13.5px; font-weight: 700; font-family: 'Noto Sans', sans-serif; border-radius: 4px; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; gap: 6px; }
.btn-govt-next:hover { background: var(--navy-mid); }
.btn-submit { background: #198754; color: #fff; border: none; padding: 11px 36px; font-size: 14px; font-weight: 700; font-family: 'Noto Sans', sans-serif; border-radius: 4px; cursor: pointer; transition: background 0.2s; width: 100%; margin-top: 10px; letter-spacing: 0.3px; }
.btn-submit:hover { background: #145c38; }

/* ── Review box ── */
.review-box { background: #f8fafd; border: 1px solid var(--border); border-radius: 4px; padding: 16px 18px; margin-bottom: 16px; }
.review-box h6 { font-size: 12px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 10px; border-bottom: 1px solid var(--border); padding-bottom: 5px; }
.review-row { display: flex; gap: 10px; font-size: 12.5px; margin-bottom: 5px; }
.review-key { color: var(--muted); min-width: 120px; font-size: 12px; }
.review-val { color: var(--text); font-weight: 500; }

/* ── Footer ── */
.site-footer { background: var(--navy-dark); color: rgba(255,255,255,0.65); text-align: center; padding: 16px 20px; font-size: 11.5px; border-top: 4px solid var(--gold); line-height: 1.9; }
.site-footer a { color: var(--gold-lt); text-decoration: none; }

/* ── Responsive ── */
@media (max-width: 860px) { .page-wrap { grid-template-columns: 1fr; } .sidebar { position: static; } }
@media (max-width: 600px) { .form-card-body { padding: 18px 14px; } .steps-row .step-lbl { display: none; } }
</style>
</head>
<body>

<!-- ── GOV STRIP ── -->
<div class="gov-strip">
    <div class="inner">
        <span>&#127470;&#127475; &nbsp;Government of India &nbsp;&mdash;&nbsp; Ministry of Electronics &amp; Information Technology</span>
        <span>
            <a href="#">Skip to Content</a>
            <a href="#" onclick="document.body.style.fontSize='16px';return false;">A+</a>
            <a href="#" onclick="document.body.style.fontSize='13.5px';return false;">A</a>
        </span>
    </div>
</div>

<!-- ── HEADER ── -->
<header class="site-header">
    <div class="header-inner">
        <div class="h-emblem">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/68px-Emblem_of_India.svg.png"
                 alt="Emblem of India"
                 onerror="this.style.display='none'">
        </div>
        <div class="h-sep"></div>
        <div class="h-text">
            <div class="hi">राष्ट्रीय इलेक्ट्रॉनिकी एवं सूचना प्रौद्योगिकी संस्थान</div>
            <div class="name">NIELIT TPS</div>
            <div class="sub">National Institute of Electronics &amp; Information Technology — Training Partner System</div>
        </div>
        <div class="h-right">
            <div class="min">Ministry of Electronics &amp;<br>Information Technology</div>
            <div class="gov">Government of India</div>
        </div>
    </div>
    <div class="tricolour"><div class="tc1"></div><div class="tc2"></div><div class="tc3"></div></div>
</header>

<!-- ── NAV ── -->
<nav class="main-nav">
    <div class="nav-inner">
        <a href="#">Home</a>
        <a href="#">About NIELIT</a>
        <a href="#">Courses</a>
        <a href="#" class="cur">Training Partner</a>
        <a href="#">Student Corner</a>
        <a href="#">Downloads</a>
        <a href="#">Contact Us</a>
        <a href="../login.php" style="margin-left:auto;border-left:1px solid rgba(255,255,255,0.1);">&#128274; Login</a>
    </div>
</nav>

<!-- ── BREADCRUMB ── -->
<div class="breadcrumb-bar">
    <div class="inner">
        <a href="#">Home</a><span class="sep">&#9658;</span>
        <a href="#">Training Partner</a><span class="sep">&#9658;</span>
        <span>New Registration</span>
    </div>
</div>

<!-- ── PAGE BANNER ── -->
<div class="page-banner">
    <div class="inner">
        <h1>Training Partner (TP) &mdash; New Registration</h1>
        <p>Apply online to become an authorised NIELIT Training Partner. Fill all details carefully and upload supporting documents.</p>
    </div>
</div>

<!-- ── PAGE WRAP ── -->
<div class="page-wrap">

    <!-- ════ FORM COLUMN ════ -->
    <div>

        <?php if ($message): ?>
        <div class="alert-govt alert-<?= $messageType ?>">
            <span class="alert-icon"><?= $messageType === 'success' ? '&#10004;' : '&#9888;' ?></span>
            <div><?= $message ?></div>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-card-head">
                <h4>NIELIT TPS — Center Registration</h4>
                <span class="tps-badge">&#127968; Official Portal</span>
            </div>
            <div class="form-card-body">

                <!-- ── STEP PROGRESS ── -->
                <div class="steps-wrap">
                    <div class="steps-track"><div class="steps-fill" id="stepsFill"></div></div>
                    <div class="steps-row">
                        <?php
                        $step_labels = ['Account','Faculty','Financials','Documents','Review'];
                        foreach ($step_labels as $i => $sl):
                        ?>
                        <div class="step-col">
                            <div class="step-circle <?= $i===0?'active':'' ?>" id="sc<?=$i?>"><?= $i+1 ?></div>
                            <div class="step-lbl <?= $i===0?'active':'' ?>" id="sl<?=$i?>"><?= $sl ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ════════════════════════════════════════
                     FORM
                ════════════════════════════════════════ -->
                <form id="signupForm" method="POST" action="" enctype="multipart/form-data" novalidate>

                <!-- ══════════════════════════════
                     STEP 1 — ACCOUNT + LEGAL STATUS
                ══════════════════════════════ -->
                <div class="form-step active" id="step-0">
                    <div class="step-title"><span class="snum">1</span>Account &amp; Legal Status</div>

                    <div class="subsec">Login Credentials</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Center ID <span class="req">*</span></label>
                            <input type="text" name="center_id" class="form-control" required placeholder="e.g. OD001">
                            <p class="field-note">Unique identifier assigned to your center</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password <span class="req">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6" placeholder="Min 6 characters">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm Password <span class="req">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter password">
                        </div>
                    </div>

                    <div class="subsec">Institute Details</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Institute Name <span class="req">*</span></label>
                            <input type="text" name="inst_name" class="form-control" required placeholder="Full official name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Institute Code <span style="font-weight:400;color:var(--muted)">(if allotted)</span></label>
                            <input type="text" name="inst_code" class="form-control" placeholder="Leave blank if not allotted">
                        </div>
                    </div>

                    <div class="subsec">Authorized Signatory Details (Sec 3)</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Signatory Name <span class="req">*</span></label>
                            <input type="text" name="sig_name" class="form-control" required placeholder="Full name as per ID">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Father's Name <span class="req">*</span></label>
                            <input type="text" name="father_name" class="form-control" required placeholder="Father's full name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Designation <span class="req">*</span></label>
                            <input type="text" name="designation" class="form-control" required placeholder="e.g. Director / Principal">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Qualification <span class="req">*</span></label>
                            <input type="text" name="qualification" class="form-control" required placeholder="e.g. Graduation">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Experience (years) <span class="req">*</span></label>
                            <input type="number" name="experience" class="form-control" required min="0" placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile <span class="req">*</span></label>
                            <input type="tel" name="sig_mobile" class="form-control" required maxlength="10" placeholder="10-digit mobile">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Email (Login ID) <span class="req">*</span></label>
                            <input type="email" name="sig_email" class="form-control" required placeholder="email@domain.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ID Type <span class="req">*</span></label>
                            <select name="id_type" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="pan_card">Pan Card</option>
                                <option value="aadhar">Aadhar Card</option>
                                <option value="passport">Passport</option>
                                <option value="voter_id">Voter ID</option>
                                <option value="driving_license">Driving Licence</option>
                                <option value="other_govt">Any Other Govt. Card</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ID Proof Number <span class="req">*</span></label>
                            <input type="text" name="id_number" class="form-control" required placeholder="As on ID document">
                        </div>
                    </div>

                    <div class="subsec">Address Details</div>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label">Address Line 1 <span class="req">*</span></label>
                            <input type="text" name="address1" class="form-control" required placeholder="House No., Street, Village">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="address2" class="form-control" placeholder="Landmark / PO / Taluk">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Locality / District <span class="req">*</span></label>
                            <input type="text" name="locality" class="form-control" required placeholder="District">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State <span class="req">*</span></label>
                            <select name="state" class="form-select" required>
                                <option value="">-- Select State --</option>
                                <?php
                                $states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Delhi (NCT)','Jammu & Kashmir','Ladakh','Puducherry','Chandigarh','Andaman & Nicobar Islands'];
                                foreach ($states as $s) echo "<option value=\"$s\">$s</option>";
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pincode <span class="req">*</span></label>
                            <input type="text" name="pincode" class="form-control" required maxlength="6" placeholder="6-digit PIN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">STD Code &amp; Land Line</label>
                            <input type="text" name="landline" class="form-control" placeholder="e.g. 0120-2345678">
                        </div>
                    </div>

                    <div class="subsec">Legal Status (Sec 9)</div>
                    <label class="form-label">Legal Status of Institute <span class="req">*</span></label>
                    <div class="radio-group" id="legal-grp">
                        <?php
                        $legal_opts = ['proprietorship'=>'(1) Proprietorship','partnership'=>'(2) Partnership','society_ngo'=>'(3) Society / NGO','trust'=>'(4) Trust','company'=>'(5) Company'];
                        foreach ($legal_opts as $v => $l):
                        ?>
                        <label class="radio-pill">
                            <input type="radio" name="legal_status" value="<?= $v ?>" required onchange="switchLegal(this.value)">
                            <?= $l ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="legal_status_hidden" id="legalHidden">

                    <!-- Legal sub-panels -->
                    <div class="legal-panel" id="lp_proprietorship">
                        <div class="legal-title">Proprietorship Concern — Required Documents</div>
                        <div class="legal-note">Registration/Certificate from any Govt. authority (Industrial/Business unit or Shop &amp; Establishment Act). Bank certificate accepted to establish ownership. Authority letter from proprietor required.</div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Govt. Registration Certificate <span class="req">*</span></label><input type="file" name="doc_prop" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Authority Letter from Proprietor <span class="req">*</span></label><input type="file" name="doc_prop_auth" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                        </div>
                    </div>

                    <div class="legal-panel" id="lp_partnership">
                        <div class="legal-title">Partnership Firm — Required Documents</div>
                        <div class="legal-note">Registered Partnership Deed + Registration Certificate from Registrar of Firms (with names of partners) + Authority letter signed by all partners.</div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Registered Partnership Deed <span class="req">*</span></label><input type="file" name="doc_part_deed" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Registration Certificate from Registrar of Firms <span class="req">*</span></label><input type="file" name="doc_part_reg" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                        </div>
                    </div>

                    <div class="legal-panel" id="lp_society_ngo">
                        <div class="legal-title">Society / NGO — Required Documents</div>
                        <div class="legal-note">Certificate from Registrar of Society + Rules &amp; Regulations / Memorandum of Association + Resolution nominating the authorised person duly signed by members.</div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Certificate from Registrar of Society <span class="req">*</span></label><input type="file" name="doc_soc_cert" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Rules &amp; Regulations / Memorandum of Association <span class="req">*</span></label><input type="file" name="doc_soc_moa" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                        </div>
                    </div>

                    <div class="legal-panel" id="lp_trust">
                        <div class="legal-title">Trust — Required Documents</div>
                        <div class="legal-note">Trust Deed + Certificate of Registration of Trust + Resolution to nominate authorised person signed by all Trustees.</div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Trust Deed <span class="req">*</span></label><input type="file" name="doc_trust_deed" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Certificate of Registration of Trust <span class="req">*</span></label><input type="file" name="doc_trust_cert" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                        </div>
                    </div>

                    <div class="legal-panel" id="lp_company">
                        <div class="legal-title">Company — Required Documents</div>
                        <div class="legal-note">Certificate of Incorporation + Memorandum of Association + Board Resolution authorizing the authorized person to deal with NIELIT.</div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Certificate of Incorporation <span class="req">*</span></label><input type="file" name="doc_co_inc" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                            <div class="col-md-6"><div class="upload-box"><label class="form-label">Memorandum of Association <span class="req">*</span></label><input type="file" name="doc_co_moa" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p></div></div>
                        </div>
                    </div>
                </div><!-- /step-0 -->

                <!-- ══════════════════════════════
                     STEP 2 — FACULTY + INFRA
                ══════════════════════════════ -->
                <div class="form-step" id="step-1">
                    <div class="step-title"><span class="snum">2</span>Faculty &amp; Infrastructure</div>

                    <div class="subsec">Premises &amp; Infrastructure (Sec 4)</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Premise Type <span class="req">*</span></label>
                            <select name="premise_type" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="Owned">Self Owned</option>
                                <option value="Rented">Rented</option>
                                <option value="Lease">Long Term Lease (min. 11 months)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Premises Period</label>
                            <input type="text" name="premises_period" class="form-control" placeholder="e.g. Apr 2024 – Mar 2027">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Carpet Area (sq.ft) <span class="req">*</span></label>
                            <input type="number" name="infra_area" class="form-control" required min="0" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No. of Computers</label>
                            <input type="number" name="infra_computers" class="form-control" min="0" placeholder="0">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Computer Labs <span class="req">*</span></label>
                            <input type="number" name="infra_labs" class="form-control" required min="0" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Seating Capacity</label>
                            <input type="number" name="seating_capacity" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No. of Boys</label>
                            <input type="number" name="no_boys" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No. of Girls</label>
                            <input type="number" name="no_girls" class="form-control" min="0" placeholder="0">
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Library Available</label>
                            <select name="has_library" class="form-select"><option value="N">No</option><option value="Y">Yes</option></select>
                        </div>
                    </div>

                    <div class="subsec">Faculty Education &amp; Qualification (Sec 12)</div>
                    <?php for ($f = 1; $f <= 2; $f++): ?>
                    <div class="fac-block">
                        <div class="fac-title">
                            <span class="fac-badge">Faculty <?= $f ?></span>
                            Faculty Member <?= $f ?> — Education Details
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Name of Faculty <?= $f === 1 ? '<span class="req">*</span>' : '' ?></label>
                                <input type="text" name="fac_name[]" class="form-control" <?= $f===1?'required':'' ?> placeholder="Full name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Qualification</label>
                                <input type="text" name="fac_qual[]" class="form-control" placeholder="e.g. OSCIT(Computer)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Examination Passed</label>
                                <input type="text" name="fac_exam[]" class="form-control" placeholder="e.g. Others">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Year of Passing</label>
                                <input type="text" name="fac_year[]" class="form-control" maxlength="4" placeholder="YYYY">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Board / University</label>
                                <input type="text" name="fac_board[]" class="form-control" placeholder="e.g. OSOU / OKCL">
                            </div>
                        </div>
                        <div class="upload-box">
                            <label class="form-label">Faculty <?= $f ?> — Qualification Certificate</label>
                            <input type="file" name="doc_fac<?= $f ?>" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <p class="upload-hint">&#128206; JPG/PNG/PDF — Max 10MB</p>
                        </div>
                    </div>
                    <?php endfor; ?>

                    <div class="subsec">Faculty Experience (Sec 13)</div>
                    <?php for ($f = 1; $f <= 2; $f++): ?>
                    <div class="fac-block">
                        <div class="fac-title">
                            <span class="fac-badge">Faculty <?= $f ?></span>
                            Faculty Member <?= $f ?> — Experience
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date From &ndash; Date To</label>
                                <input type="text" name="fac_exp_period[]" class="form-control" placeholder="DD-MM-YYYY to DD-MM-YYYY">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name of Organization</label>
                                <input type="text" name="fac_org[]" class="form-control" placeholder="Organization name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date of Joining</label>
                                <input type="text" name="fac_doj[]" class="form-control" placeholder="DD-MM-YYYY">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ID Type</label>
                                <select name="fac_id_type[]" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option>Pan Card</option><option>Aadhar Card</option><option>Passport</option><option>Any Other Govt. Card</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ID Proof Number</label>
                                <input type="text" name="fac_id_num[]" class="form-control" placeholder="ID number">
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div><!-- /step-1 -->

                <!-- ══════════════════════════════
                     STEP 3 — FINANCIAL DETAILS
                ══════════════════════════════ -->
                <div class="form-step" id="step-2">
                    <div class="step-title"><span class="snum">3</span>Financial Details (Sec 14)</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Financial Ending Year <span class="req">*</span></label>
                            <input type="number" name="fin_year" class="form-control" required placeholder="e.g. 2024">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Annual Turnover — Computer Training (₹)</label>
                            <input type="text" name="fin_turnover" class="form-control" placeholder="Amount in Lakhs">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Annual Turnover — Other Activities (₹)</label>
                            <input type="text" name="fin_turnover_other" class="form-control" placeholder="Amount in Lakhs">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Students Placed <span class="req">*</span></label>
                            <input type="number" name="fin_placed" class="form-control" required min="0" placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Income Tax Exempted</label>
                            <select name="income_tax_exempt" class="form-select">
                                <option value="N">No (N)</option>
                                <option value="Y">Yes (Y)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="fin_remarks" class="form-control" placeholder="Any remarks (optional)">
                        </div>
                    </div>
                </div><!-- /step-2 -->

                <!-- ══════════════════════════════
                     STEP 4 — UPLOAD DOCUMENTS (Sec 17)
                ══════════════════════════════ -->
                <div class="form-step" id="step-3">
                    <div class="step-title"><span class="snum">4</span>Upload Documents (Sec 17)</div>
                    <div class="alert alert-warning small mb-3" style="border-radius:4px;">
                        &#9888; Upload PDF or JPG/PNG formats only. Maximum size: <strong>10 MB</strong> per file.
                    </div>

                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th class="sno">S.No.</th>
                                <th>Certificate / Document Description</th>
                                <th style="text-align:center;width:88px;">Status</th>
                                <th class="upload-td">Upload File</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $doc17 = [
                            ['doc_id_proof',       'Authorized ID Proof',                                                                                                          true ],
                            ['doc_signature',      'Authorized Signatory Signature',                                                                                               true ],
                            ['doc_layout_map',     'Layout Map of the Institute Premises',                                                                                         true ],
                            ['doc_govt_reg',       'Registration Certificate from any Government Authority',                                                                       true ],
                            ['doc_franchisee',     'Franchisee / Licensee Agreement',                                                                                             false],
                            ['doc_sub_registrar',  'Registration with Registrar / Sub-Registrar',                                                                                 false],
                            ['doc_sales_tax',      'Registration with Sales Tax / Services Tax or any other Tax Authority',                                                        false],
                            ['doc_lease_noc',      'Lease / Rent Agreement / Ownership Deed with NOC',                                                                            true ],
                            ['doc_other',          'Any Other Relevant Document',                                                                                                 false],
                            ['doc_building_photos','Photos of Building (Front view with hoarding, Classrooms, Computer Lab, Library, Seating area, Washrooms, Reception, Staffroom etc.)', true],
                        ];
                        foreach ($doc17 as $i => [$fname, $dname, $req]):
                        ?>
                        <tr>
                            <td class="sno"><?= $i+1 ?></td>
                            <td>
                                <?= htmlspecialchars($dname) ?>
                                <?php if ($req): ?><br><small style="color:#b71c1c;font-size:10.5px;font-weight:600;">* Mandatory</small><?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <span class="<?= $req ? 'badge-req' : 'badge-opt' ?>"><?= $req ? 'Required' : 'Optional' ?></span>
                            </td>
                            <td class="upload-td">
                                <input type="file" name="<?= $fname ?>" class="form-control form-control-sm" <?= $req ? 'required' : '' ?> accept=".jpg,.jpeg,.png,.pdf">
                                <div class="mini-hint">JPG/PNG/PDF &nbsp;&bull;&nbsp; Max 10MB</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div><!-- /step-3 -->

                <!-- ══════════════════════════════
                     STEP 5 — REVIEW + SUBMIT
                ══════════════════════════════ -->
                <div class="form-step" id="step-4">
                    <div class="step-title"><span class="snum">5</span>Final Review &amp; Submit</div>

                    <div class="review-box">
                        <h6>&#9432; &nbsp;Application Summary</h6>
                        <p style="font-size:12.5px;color:var(--muted);">Please review all details before submitting. Once submitted, changes require Admin approval.</p>
                        <div class="review-row"><span class="review-key">Center ID</span><span class="review-val" id="rv_center_id">—</span></div>
                        <div class="review-row"><span class="review-key">Institute Name</span><span class="review-val" id="rv_inst_name">—</span></div>
                        <div class="review-row"><span class="review-key">Signatory Name</span><span class="review-val" id="rv_sig_name">—</span></div>
                        <div class="review-row"><span class="review-key">Email</span><span class="review-val" id="rv_email">—</span></div>
                        <div class="review-row"><span class="review-key">Mobile</span><span class="review-val" id="rv_mobile">—</span></div>
                        <div class="review-row"><span class="review-key">Legal Status</span><span class="review-val" id="rv_legal">—</span></div>
                        <div class="review-row"><span class="review-key">State</span><span class="review-val" id="rv_state">—</span></div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="declareCheck" required>
                        <label class="form-check-label fw-bold" for="declareCheck" style="font-size:12.5px;">
                            I hereby declare that all information provided above is true, accurate and complete to the best of my knowledge. I understand that submission of false or misleading information may result in rejection or cancellation of Training Partner status.
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">&#10003; &nbsp;Submit Registration Application</button>
                </div><!-- /step-4 -->

                <!-- Nav Buttons -->
                <div class="nav-btns">
                    <button type="button" class="btn-govt-prev" id="prevBtn" onclick="stepNav(-1)" style="display:none;">&#8592; &nbsp;Back</button>
                    <button type="button" class="btn-govt-next" id="nextBtn" onclick="stepNav(1)">Next Step &nbsp;&#8594;</button>
                </div>

                </form><!-- /signupForm -->

                <div class="text-center mt-4 pt-3 border-top">
                    <a href="../login.php" class="text-decoration-none" style="color:var(--muted);font-size:12.5px;font-weight:600;">
                        &#128274; &nbsp;Already registered? Login here
                    </a>
                </div>

            </div><!-- /form-card-body -->
        </div><!-- /form-card -->
    </div><!-- /form column -->

    <!-- ════ SIDEBAR ════ -->
    <aside class="sidebar">
        <div class="s-card">
            <div class="s-head">&#9776; &nbsp;Form Steps</div>
            <div class="s-body">
                <nav class="s-nav">
                    <a href="#" onclick="jumpTo(0);return false;"><span class="dot"></span>1. Account &amp; Legal Status</a>
                    <a href="#" onclick="jumpTo(1);return false;"><span class="dot"></span>2. Faculty &amp; Infrastructure</a>
                    <a href="#" onclick="jumpTo(2);return false;"><span class="dot"></span>3. Financial Details</a>
                    <a href="#" onclick="jumpTo(3);return false;"><span class="dot"></span>4. Upload Documents</a>
                    <a href="#" onclick="jumpTo(4);return false;"><span class="dot"></span>5. Review &amp; Submit</a>
                </nav>
            </div>
        </div>
        <div class="s-card">
            <div class="s-head">&#9888; &nbsp;Important Instructions</div>
            <div class="s-body">
                <div class="info-box">
                    <strong>Before You Apply</strong>
                    &bull; All documents must be self-attested.<br>
                    &bull; File format: JPG, PNG or PDF only.<br>
                    &bull; Max file size: <strong>10 MB</strong> per document.<br>
                    &bull; Premises: Owned or min. 11-month lease.<br>
                    &bull; Minimum <strong>2 qualified faculty</strong> required.<br>
                    &bull; Keep originals ready for verification.<br>
                    &bull; Incomplete applications will be rejected.
                </div>
            </div>
        </div>
        <div class="s-card">
            <div class="s-head">&#9990; &nbsp;Helpdesk</div>
            <div class="s-body">
                <div class="hl-row"><span class="hl-key">Phone</span><span class="hl-val">1800-111-555</span></div>
                <div class="hl-row"><span class="hl-key">Email</span><span class="hl-val" style="font-size:11px;">tp@nielit.gov.in</span></div>
                <div class="hl-row"><span class="hl-key">Timing</span><span class="hl-val" style="font-size:11px;">10AM–5PM (Mon–Fri)</span></div>
            </div>
        </div>
        <div class="s-card">
            <div class="s-head">&#128196; &nbsp;Downloads</div>
            <div class="s-body">
                <nav class="s-nav">
                    <a href="#"><span class="dot"></span>TP Application Form (PDF)</a>
                    <a href="#"><span class="dot"></span>Document Checklist</a>
                    <a href="#"><span class="dot"></span>Guidelines for TP</a>
                    <a href="#"><span class="dot"></span>Fee Structure</a>
                </nav>
            </div>
        </div>
    </aside>

</div><!-- /page-wrap -->

<!-- ── FOOTER ── -->
<footer class="site-footer">
    <div style="margin-bottom:12px;">
        <strong style="color:rgba(255,255,255,0.9);">NIELIT TPS</strong> &mdash; National Institute of Electronics &amp; Information Technology<br>
        Ministry of Electronics &amp; Information Technology, Government of India<br>
        <small>A-Block, CGO Complex, Lodhi Road, New Delhi &mdash; 110 003</small>
    </div>
    <div>
        <a href="#">Privacy Policy</a> &nbsp;|&nbsp;
        <a href="#">Terms &amp; Conditions</a> &nbsp;|&nbsp;
        <a href="#">Disclaimer</a> &nbsp;|&nbsp;
        <a href="#">Sitemap</a> &nbsp;|&nbsp;
        <a href="#">RTI</a>
    </div>
    <div style="margin-top:8px;font-size:10.5px;color:rgba(255,255,255,0.38);">
        &copy; <?= date('Y') ?> NIELIT. All Rights Reserved. &nbsp;|&nbsp; Last Updated: <?= date('d M Y') ?>
    </div>
</footer>

<script>
/* ═══════════════════════════════════
   MULTI-STEP NAVIGATION
═══════════════════════════════════ */
let cur = 0;
const steps   = document.getElementsByClassName('form-step');
const total   = steps.length;
const labels  = ['Account & Legal','Faculty & Infra','Financials','Documents','Review'];

function showStep(n) {
    for (let i = 0; i < total; i++) steps[i].classList.remove('active');
    steps[n].classList.add('active');
    document.getElementById('prevBtn').style.display = (n === 0) ? 'none' : 'inline-flex';
    document.getElementById('nextBtn').style.display = (n === total - 1) ? 'none' : 'flex';
    updateProgress(n);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (n === total - 1) fillReview();
}

function stepNav(dir) {
    if (dir === 1 && !validateStep()) return;
    cur += dir;
    if (cur < 0) cur = 0;
    if (cur >= total) cur = total - 1;
    showStep(cur);
}

function jumpTo(n) {
    cur = n;
    showStep(cur);
}

function validateStep() {
    let ok = true;
    const inputs = steps[cur].querySelectorAll('input[required], select[required]');
    inputs.forEach(function(el) {
        if (!el.checkValidity()) { el.reportValidity(); ok = false; }
    });
    return ok;
}

function updateProgress(n) {
    const pct = (n / (total - 1)) * 100;
    document.getElementById('stepsFill').style.width = pct + '%';
    for (let i = 0; i < total; i++) {
        const c = document.getElementById('sc' + i);
        const l = document.getElementById('sl' + i);
        c.className = 'step-circle';
        l.className = 'step-lbl';
        if (i < n)  { c.classList.add('done');   c.innerHTML = '&#10003;'; }
        else if (i === n) { c.classList.add('active'); l.classList.add('active'); c.innerHTML = i + 1; }
        else { c.innerHTML = i + 1; }
    }
}

/* ═══════════════════════════════════
   LEGAL PANEL TOGGLE
═══════════════════════════════════ */
function switchLegal(val) {
    document.querySelectorAll('.legal-panel').forEach(function(p) { p.classList.remove('show'); });
    var t = document.getElementById('lp_' + val);
    if (t) t.classList.add('show');
    document.querySelectorAll('.radio-pill').forEach(function(p) { p.classList.remove('on'); });
    var r = document.querySelector('.radio-pill input[value="' + val + '"]');
    if (r) r.closest('.radio-pill').classList.add('on');
    document.getElementById('legalHidden').value = val;
}

document.querySelectorAll('.radio-pill').forEach(function(pill) {
    pill.addEventListener('click', function() {
        var inp = this.querySelector('input[type=radio]');
        if (inp) switchLegal(inp.value);
    });
});

/* ═══════════════════════════════════
   REVIEW FILL
═══════════════════════════════════ */
function fillReview() {
    var f = document.getElementById('signupForm');
    function gv(name) { var el = f.querySelector('[name="' + name + '"]'); return el ? el.value : '—'; }
    document.getElementById('rv_center_id').textContent = gv('center_id')  || '—';
    document.getElementById('rv_inst_name').textContent = gv('inst_name')  || '—';
    document.getElementById('rv_sig_name').textContent  = gv('sig_name')   || '—';
    document.getElementById('rv_email').textContent     = gv('sig_email')  || '—';
    document.getElementById('rv_mobile').textContent    = gv('sig_mobile') || '—';
    document.getElementById('rv_state').textContent     = gv('state')      || '—';
    var legal = document.querySelector('input[name="legal_status"]:checked');
    document.getElementById('rv_legal').textContent = legal ? legal.value.replace('_', ' / ').toUpperCase() : '—';
}

/* Init */
showStep(0);
</script>

</body>
</html>