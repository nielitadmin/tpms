<?php
// tp_signup.php - NIELIT Training Partner Registration
session_start();

// Database connection logic
require __DIR__ . '/../includes/config.php'; 

$errors = [];
$success = false;

function handle_upload($file_key, $subfolder = 'tp_documents') {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $file = $_FILES[$file_key];
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','application/pdf'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = $subfolder . '/' . $file_key . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dir = 'uploads/' . $subfolder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], 'uploads/' . $name)) return $name;
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn; // Using your mysqli connection

    // Basic Validation
    if (empty($_POST['institute_name'])) $errors[] = "Institute name is required.";
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($_POST['password']) || strlen($_POST['password']) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($_POST['password'] !== $_POST['confirm_password']) $errors[] = "Passwords do not match.";
    if (empty($_POST['mobile']) || !preg_match('/^[6-9]\d{9}$/', $_POST['mobile'])) $errors[] = "Valid 10-digit mobile number required.";

    if (empty($errors)) {
        // Checking if email already exists
        $email = $conn->real_escape_string($_POST['email']);
        $check_sql = "SELECT id FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $errors[] = "Email is already registered. Please login.";
        } else {
            // Process file uploads
            $uploads = [
                's3_id_proof'       => handle_upload('s3_id_proof'),
                's3_signature'      => handle_upload('s3_signature'),
                's4_layout_map'     => handle_upload('s4_layout_map'),
                's4_building_photo' => handle_upload('s4_building_photo'),
                's4_agreement'      => handle_upload('s4_agreement'),
                's9_legal_doc'      => handle_upload('s9_legal_doc'),
                's12_faculty1_cert' => handle_upload('s12_faculty1_cert'),
                's12_faculty2_cert' => handle_upload('s12_faculty2_cert'),
                's17_id_proof'          => handle_upload('s17_id_proof'),
                's17_signatory_sig'     => handle_upload('s17_signatory_sig'),
                's17_layout_map'        => handle_upload('s17_layout_map'),
                's17_reg_cert'          => handle_upload('s17_reg_cert'),
                's17_franchise_agmt'    => handle_upload('s17_franchise_agmt'),
                's17_registrar_reg'     => handle_upload('s17_registrar_reg'),
                's17_tax_reg'           => handle_upload('s17_tax_reg'),
                's17_lease_deed'        => handle_upload('s17_lease_deed'),
                's17_other_doc'         => handle_upload('s17_other_doc'),
                's17_building_photos'   => handle_upload('s17_building_photos'),
            ];

            // Proper Database Insertion
            $center_id = "OD" . rand(1000, 9999); // Generates temporary Center ID
            $name = $conn->real_escape_string($_POST['institute_name']);
            $phone = $conn->real_escape_string($_POST['mobile']);
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (center_id, name, email, phone, password, role, status) 
                    VALUES ('$center_id', '$name', '$email', '$phone', '$hashed_password', 'tp', 'pending')";
            
            if ($conn->query($sql) === TRUE) {
                $success = true;
            } else {
                $errors[] = "Database Error: " . $conn->error;
            }
        }
    }
}

$v = $_POST; // preserve form values
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Training Partner Registration | NIELIT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="gov-topbar">
    Government of India &nbsp;|&nbsp;
    <a href="#">MeitY</a> |
    <a href="#">NIELIT HQ</a> |
    <a href="#">Grievance Portal</a> |
    <a href="#">RTI</a> |
    <a href="#">Screen Reader Access</a>
</div>

<div class="header">
    <div class="tricolor-bar"></div>
    <div class="header-inner">
        <div class="header-logos">
            <div class="logo-circle">NIELIT<br>राष्ट्रीय</div>
        </div>
        <div class="header-text">
            <div class="ministry">Ministry of Electronics &amp; Information Technology, Government of India</div>
            <h1>NIELIT &ndash; <span>National Institute of Electronics &amp; Information Technology</span></h1>
            <div class="tagline">&#x1f1ee;&#x1f1f3; An Autonomous Scientific Society under MeitY</div>
        </div>
        <div class="flag-icon"></div>
    </div>
    <div class="tricolor-bar"></div>
</div>

<nav class="navbar">
    <ul>
        <li><a href="#">Home</a></li>
        <li><a href="#">About NIELIT</a></li>
        <li><a href="#">Courses</a></li>
        <li><a href="#" class="active">Training Partner</a></li>
        <li><a href="#">Examination</a></li>
        <li><a href="#">Certification</a></li>
        <li><a href="#">Downloads</a></li>
        <li><a href="#">Contact</a></li>
    </ul>
</nav>

<div class="page-title-bar">
    <div class="breadcrumb">Home &raquo; Training Partner &raquo; <span>New Registration</span></div>
    <h2>Training Partner (TP) Registration Portal</h2>
    <p>Online application for empanelment as Training Partner under NIELIT</p>
</div>

<div class="page-wrap">

    <aside class="sidebar">
        <div class="sidebar-title">&#128196; Registration Guide</div>
        <ul>
            <li><a href="#sec1">1. Institute Details</a></li>
            <li><a href="#sec3">3. Authorized Signatory</a></li>
            <li><a href="#sec4">4. Premises &amp; Infrastructure</a></li>
            <li><a href="#sec9">9. Legal Status</a></li>
            <li><a href="#sec12">12. Faculty Details</a></li>
            <li><a href="#sec13">13. Experience Details</a></li>
            <li><a href="#sec14">14. Financial &amp; Placement</a></li>
            <li><a href="#sec17">17. Document Uploads</a></li>
        </ul>
        <div class="sidebar-note">
            <strong>&#9888; Note:</strong> Fields marked <span style="color:red">*</span> are mandatory.<br><br>
            Documents: JPG/PNG/PDF only, Max 5MB each.<br><br>
            Save your Application ID after submission.
        </div>
    </aside>

    <main class="form-card">

        <?php if ($success): ?>
        <div class="alert alert-success" style="margin:20px;">
            <strong>&#10003; Application Submitted Successfully!</strong><br>
            Your Training Partner registration application has been received. You will receive a confirmation email shortly.
        </div>
        <?php elseif (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>&#9888; Please correct the following errors:</strong>
            <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>

        <div class="form-section" id="sec1">
            <div class="section-header">
                <div class="section-num">1</div>
                <div class="section-title">Institute / Organization Details</div>
            </div>
            <div class="section-body">
                <div class="form-grid">
                    <div class="form-group col-full">
                        <label>Name of Institute / Organization <span class="req">*</span></label>
                        <input type="text" name="institute_name" value="<?= htmlspecialchars($v['institute_name'] ?? '') ?>" placeholder="Full legal name as per registration">
                    </div>
                    <div class="form-group">
                        <label>Email Address <span class="req">*</span></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($v['email'] ?? '') ?>" placeholder="official@institute.in">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number <span class="req">*</span></label>
                        <input type="tel" name="mobile" value="<?= htmlspecialchars($v['mobile'] ?? '') ?>" placeholder="10-digit mobile">
                    </div>
                    <div class="form-group">
                        <label>Landline / STD Number</label>
                        <input type="tel" name="landline" value="<?= htmlspecialchars($v['landline'] ?? '') ?>" placeholder="0XXX-XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Website URL</label>
                        <input type="text" name="website" value="<?= htmlspecialchars($v['website'] ?? '') ?>" placeholder="https://www.institute.in">
                    </div>
                    <div class="form-group">
                        <label>Password <span class="req">*</span></label>
                        <input type="password" name="password" placeholder="Minimum 8 characters">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span class="req">*</span></label>
                        <input type="password" name="confirm_password" placeholder="Re-enter password">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section" id="sec3">
            <div class="section-header">
                <div class="section-num">3</div>
                <div class="section-title">Authorized Signatory Details</div>
            </div>
            <div class="section-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="s3_name" value="<?= htmlspecialchars($v['s3_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Father's / Husband's Name <span class="req">*</span></label>
                        <input type="text" name="s3_father_name" value="<?= htmlspecialchars($v['s3_father_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Designation <span class="req">*</span></label>
                        <input type="text" name="s3_designation" value="<?= htmlspecialchars($v['s3_designation'] ?? '') ?>" placeholder="e.g. Director, Principal">
                    </div>
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="s3_qualification" value="<?= htmlspecialchars($v['s3_qualification'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Experience (Years)</label>
                        <input type="number" name="s3_experience" value="<?= htmlspecialchars($v['s3_experience'] ?? '') ?>" min="0" max="60">
                    </div>
                    <div class="form-group">
                        <label>ID Proof Type <span class="req">*</span></label>
                        <select name="s3_id_type">
                            <option value="">-- Select --</option>
                            <option <?= ($v['s3_id_type'] ?? '') == 'Aadhaar' ? 'selected' : '' ?>>Aadhaar</option>
                            <option <?= ($v['s3_id_type'] ?? '') == 'PAN' ? 'selected' : '' ?>>PAN Card</option>
                            <option <?= ($v['s3_id_type'] ?? '') == 'Passport' ? 'selected' : '' ?>>Passport</option>
                            <option <?= ($v['s3_id_type'] ?? '') == 'Voter ID' ? 'selected' : '' ?>>Voter ID</option>
                            <option <?= ($v['s3_id_type'] ?? '') == 'Driving Licence' ? 'selected' : '' ?>>Driving Licence</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ID Proof Number <span class="req">*</span></label>
                        <input type="text" name="s3_id_number" value="<?= htmlspecialchars($v['s3_id_number'] ?? '') ?>">
                    </div>
                    <div class="form-group col-full">
                        <label>Full Address (Residential) <span class="req">*</span></label>
                        <textarea name="s3_address" rows="3"><?= htmlspecialchars($v['s3_address'] ?? '') ?></textarea>
                    </div>
                    <div class="upload-group">
                        <label>Upload: ID Proof Document <span class="req">*</span></label>
                        <input type="file" name="s3_id_proof" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                    </div>
                    <div class="upload-group">
                        <label>Upload: Authorized Signatory Signature <span class="req">*</span></label>
                        <input type="file" name="s3_signature" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="upload-hint">JPG / PNG &bull; Max 5MB (clear scan)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section" id="sec4">
            <div class="section-header">
                <div class="section-num">4</div>
                <div class="section-title">Premises &amp; Infrastructure Details</div>
            </div>
            <div class="section-body">
                <div class="form-grid">
                    <div class="form-group col-full">
                        <label>Type of Premises <span class="req">*</span></label>
                        <div class="radio-group">
                            <label><input type="radio" name="s4_premises_type" value="Owned" <?= ($v['s4_premises_type'] ?? '') == 'Owned' ? 'checked' : '' ?>> Owned</label>
                            <label><input type="radio" name="s4_premises_type" value="Rented" <?= ($v['s4_premises_type'] ?? '') == 'Rented' ? 'checked' : '' ?>> Rented</label>
                            <label><input type="radio" name="s4_premises_type" value="Long Term Lease" <?= ($v['s4_premises_type'] ?? '') == 'Long Term Lease' ? 'checked' : '' ?>> Long Term Lease</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Total Carpet Area (sq.ft.) <span class="req">*</span></label>
                        <input type="number" name="s4_carpet_area" value="<?= htmlspecialchars($v['s4_carpet_area'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Number of Computers <span class="req">*</span></label>
                        <input type="number" name="s4_computers" value="<?= htmlspecialchars($v['s4_computers'] ?? '') ?>" min="1">
                    </div>
                    <div class="form-group">
                        <label>Total Seating Capacity <span class="req">*</span></label>
                        <input type="number" name="s4_seating" value="<?= htmlspecialchars($v['s4_seating'] ?? '') ?>" min="1">
                    </div>
                    <div class="form-group">
                        <label>Internet Connectivity</label>
                        <select name="s4_internet">
                            <option value="">-- Select --</option>
                            <option <?= ($v['s4_internet'] ?? '') == 'Broadband' ? 'selected' : '' ?>>Broadband</option>
                            <option <?= ($v['s4_internet'] ?? '') == 'Leased Line' ? 'selected' : '' ?>>Leased Line</option>
                            <option <?= ($v['s4_internet'] ?? '') == 'Fiber' ? 'selected' : '' ?>>Fiber (FTTH)</option>
                            <option <?= ($v['s4_internet'] ?? '') == 'VSAT' ? 'selected' : '' ?>>VSAT</option>
                        </select>
                    </div>
                    <div class="upload-group">
                        <label>Upload: Layout Map <span class="req">*</span></label>
                        <input type="file" name="s4_layout_map" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                    </div>
                    <div class="upload-group">
                        <label>Upload: Building / Premises Photos <span class="req">*</span></label>
                        <input type="file" name="s4_building_photo" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                    </div>
                    <div class="upload-group">
                        <label>Upload: Lease / Ownership Agreement <span class="req">*</span></label>
                        <input type="file" name="s4_agreement" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section" id="sec9">
            <div class="section-header">
                <div class="section-num">9</div>
                <div class="section-title">Legal Status of Institute</div>
            </div>
            <div class="section-body">
                <div class="form-group">
                    <label>Select Legal Status <span class="req">*</span></label>
                    <div class="radio-group" id="legalStatusGroup">
                        <label><input type="radio" name="s9_legal_status" value="1" <?= ($v['s9_legal_status'] ?? '') == '1' ? 'checked' : '' ?> onchange="toggleLegal()"> 1. Proprietorship</label>
                        <label><input type="radio" name="s9_legal_status" value="2" <?= ($v['s9_legal_status'] ?? '') == '2' ? 'checked' : '' ?> onchange="toggleLegal()"> 2. Partnership Firm</label>
                        <label><input type="radio" name="s9_legal_status" value="3" <?= ($v['s9_legal_status'] ?? '') == '3' ? 'checked' : '' ?> onchange="toggleLegal()"> 3. Society / Trust</label>
                        <label><input type="radio" name="s9_legal_status" value="4" <?= ($v['s9_legal_status'] ?? '') == '4' ? 'checked' : '' ?> onchange="toggleLegal()"> 4. Private / Public Company</label>
                        <label><input type="radio" name="s9_legal_status" value="5" <?= ($v['s9_legal_status'] ?? '') == '5' ? 'checked' : '' ?> onchange="toggleLegal()"> 5. Government / PSU</label>
                    </div>
                </div>

                <div class="conditional-block sub-section <?= ($v['s9_legal_status'] ?? '') == '1' ? 'visible' : '' ?>" id="legal_1">
                    <div class="sub-section-head">Proprietorship Documents</div>
                    <div class="sub-section-body">
                        <div class="form-grid">
                            <div class="form-group"><label>Proprietor Name</label><input type="text" name="s9_prop_name" value="<?= htmlspecialchars($v['s9_prop_name'] ?? '') ?>"></div>
                            <div class="upload-group">
                                <label>Upload: GST / Trade Licence / Any Govt. Registration <span class="req">*</span></label>
                                <input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="conditional-block sub-section <?= ($v['s9_legal_status'] ?? '') == '2' ? 'visible' : '' ?>" id="legal_2">
                    <div class="sub-section-head">Partnership Firm Documents</div>
                    <div class="sub-section-body">
                        <div class="form-grid">
                            <div class="form-group"><label>Partnership Deed Date</label><input type="date" name="s9_partnership_date" value="<?= htmlspecialchars($v['s9_partnership_date'] ?? '') ?>"></div>
                            <div class="form-group"><label>Registration Number</label><input type="text" name="s9_partnership_reg" value="<?= htmlspecialchars($v['s9_partnership_reg'] ?? '') ?>"></div>
                            <div class="upload-group col-full">
                                <label>Upload: Partnership Deed (Registered) <span class="req">*</span></label>
                                <input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="conditional-block sub-section <?= ($v['s9_legal_status'] ?? '') == '3' ? 'visible' : '' ?>" id="legal_3">
                    <div class="sub-section-head">Society / Trust Documents</div>
                    <div class="sub-section-body">
                        <div class="form-grid">
                            <div class="form-group"><label>Registration Number</label><input type="text" name="s9_society_reg" value="<?= htmlspecialchars($v['s9_society_reg'] ?? '') ?>"></div>
                            <div class="form-group"><label>Registration Date</label><input type="date" name="s9_society_date" value="<?= htmlspecialchars($v['s9_society_date'] ?? '') ?>"></div>
                            <div class="upload-group">
                                <label>Upload: Registration Certificate <span class="req">*</span></label>
                                <input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                            <div class="upload-group">
                                <label>Upload: Memorandum of Association / Trust Deed <span class="req">*</span></label>
                                <input type="file" name="s9_moa_doc" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="conditional-block sub-section <?= ($v['s9_legal_status'] ?? '') == '4' ? 'visible' : '' ?>" id="legal_4">
                    <div class="sub-section-head">Company Documents (MCA / ROC)</div>
                    <div class="sub-section-body">
                        <div class="form-grid">
                            <div class="form-group"><label>CIN Number</label><input type="text" name="s9_cin" value="<?= htmlspecialchars($v['s9_cin'] ?? '') ?>"></div>
                            <div class="form-group"><label>Date of Incorporation</label><input type="date" name="s9_incorp_date" value="<?= htmlspecialchars($v['s9_incorp_date'] ?? '') ?>"></div>
                            <div class="upload-group">
                                <label>Upload: Certificate of Incorporation <span class="req">*</span></label>
                                <input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                            <div class="upload-group">
                                <label>Upload: Memorandum &amp; Articles of Association <span class="req">*</span></label>
                                <input type="file" name="s9_moa_doc" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="conditional-block sub-section <?= ($v['s9_legal_status'] ?? '') == '5' ? 'visible' : '' ?>" id="legal_5">
                    <div class="sub-section-head">Government / PSU Documents</div>
                    <div class="sub-section-body">
                        <div class="form-grid">
                            <div class="form-group"><label>Department / Ministry Name</label><input type="text" name="s9_dept_name" value="<?= htmlspecialchars($v['s9_dept_name'] ?? '') ?>"></div>
                            <div class="upload-group">
                                <label>Upload: Government Authorization Letter <span class="req">*</span></label>
                                <input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section" id="sec12">
            <div class="section-header">
                <div class="section-num">12</div>
                <div class="section-title">Faculty Details</div>
            </div>
            <div class="section-body">

                <div class="sub-section">
                    <div class="sub-section-head">Faculty Member 1</div>
                    <div class="sub-section-body">
                        <div class="form-grid three">
                            <div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="s12_f1_name" value="<?= htmlspecialchars($v['s12_f1_name'] ?? '') ?>"></div>
                            <div class="form-group"><label>Qualification <span class="req">*</span></label><input type="text" name="s12_f1_qual" value="<?= htmlspecialchars($v['s12_f1_qual'] ?? '') ?>"></div>
                            <div class="form-group"><label>Examination Passed</label><input type="text" name="s12_f1_exam" value="<?= htmlspecialchars($v['s12_f1_exam'] ?? '') ?>"></div>
                            <div class="form-group"><label>Year of Passing</label><input type="number" name="s12_f1_year" value="<?= htmlspecialchars($v['s12_f1_year'] ?? '') ?>" min="1970" max="2026"></div>
                            <div class="form-group"><label>Board / University</label><input type="text" name="s12_f1_board" value="<?= htmlspecialchars($v['s12_f1_board'] ?? '') ?>"></div>
                            <div class="upload-group">
                                <label>Upload: Certificate <span class="req">*</span></label>
                                <input type="file" name="s12_faculty1_cert" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sub-section" style="margin-top:12px">
                    <div class="sub-section-head">Faculty Member 2</div>
                    <div class="sub-section-body">
                        <div class="form-grid three">
                            <div class="form-group"><label>Name</label><input type="text" name="s12_f2_name" value="<?= htmlspecialchars($v['s12_f2_name'] ?? '') ?>"></div>
                            <div class="form-group"><label>Qualification</label><input type="text" name="s12_f2_qual" value="<?= htmlspecialchars($v['s12_f2_qual'] ?? '') ?>"></div>
                            <div class="form-group"><label>Examination Passed</label><input type="text" name="s12_f2_exam" value="<?= htmlspecialchars($v['s12_f2_exam'] ?? '') ?>"></div>
                            <div class="form-group"><label>Year of Passing</label><input type="number" name="s12_f2_year" value="<?= htmlspecialchars($v['s12_f2_year'] ?? '') ?>" min="1970" max="2026"></div>
                            <div class="form-group"><label>Board / University</label><input type="text" name="s12_f2_board" value="<?= htmlspecialchars($v['s12_f2_board'] ?? '') ?>"></div>
                            <div class="upload-group">
                                <label>Upload: Certificate <span class="opt">(optional)</span></label>
                                <input type="file" name="s12_faculty2_cert" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section" id="sec13">
            <div class="section-header">
                <div class="section-num">13</div>
                <div class="section-title">Faculty Experience Details</div>
            </div>
            <div class="section-body">
                <div class="sub-section">
                    <div class="sub-section-head">Faculty 1 Experience</div>
                    <div class="sub-section-body">
                        <div class="form-grid three">
                            <div class="form-group"><label>From Date</label><input type="date" name="s13_f1_from" value="<?= htmlspecialchars($v['s13_f1_from'] ?? '') ?>"></div>
                            <div class="form-group"><label>To Date</label><input type="date" name="s13_f1_to" value="<?= htmlspecialchars($v['s13_f1_to'] ?? '') ?>"></div>
                            <div class="form-group"><label>Organization Name</label><input type="text" name="s13_f1_org" value="<?= htmlspecialchars($v['s13_f1_org'] ?? '') ?>"></div>
                            <div class="form-group"><label>Designation</label><input type="text" name="s13_f1_desig" value="<?= htmlspecialchars($v['s13_f1_desig'] ?? '') ?>"></div>
                            <div class="form-group"><label>ID Proof Number</label><input type="text" name="s13_f1_id" value="<?= htmlspecialchars($v['s13_f1_id'] ?? '') ?>"></div>
                        </div>
                    </div>
                </div>
                <div class="sub-section" style="margin-top:12px">
                    <div class="sub-section-head">Faculty 2 Experience</div>
                    <div class="sub-section-body">
                        <div class="form-grid three">
                            <div class="form-group"><label>From Date</label><input type="date" name="s13_f2_from" value="<?= htmlspecialchars($v['s13_f2_from'] ?? '') ?>"></div>
                            <div class="form-group"><label>To Date</label><input type="date" name="s13_f2_to" value="<?= htmlspecialchars($v['s13_f2_to'] ?? '') ?>"></div>
                            <div class="form-group"><label>Organization Name</label><input type="text" name="s13_f2_org" value="<?= htmlspecialchars($v['s13_f2_org'] ?? '') ?>"></div>
                            <div class="form-group"><label>Designation</label><input type="text" name="s13_f2_desig" value="<?= htmlspecialchars($v['s13_f2_desig'] ?? '') ?>"></div>
                            <div class="form-group"><label>ID Proof Number</label><input type="text" name="s13_f2_id" value="<?= htmlspecialchars($v['s13_f2_id'] ?? '') ?>"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section" id="sec14">
            <div class="section-header">
                <div class="section-num">14</div>
                <div class="section-title">Financial &amp; Placement Details</div>
            </div>
            <div class="section-body">
                <div class="form-grid three">
                    <div class="form-group">
                        <label>Financial Year <span class="req">*</span></label>
                        <select name="s14_fy">
                            <option value="">-- Select --</option>
                            <option <?= ($v['s14_fy'] ?? '') == '2024-25' ? 'selected' : '' ?>>2024-25</option>
                            <option <?= ($v['s14_fy'] ?? '') == '2023-24' ? 'selected' : '' ?>>2023-24</option>
                            <option <?= ($v['s14_fy'] ?? '') == '2022-23' ? 'selected' : '' ?>>2022-23</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Turnover (IT / Computer) ₹</label>
                        <input type="number" name="s14_turnover_it" value="<?= htmlspecialchars($v['s14_turnover_it'] ?? '') ?>" placeholder="Amount in INR">
                    </div>
                    <div class="form-group">
                        <label>Turnover (Other) ₹</label>
                        <input type="number" name="s14_turnover_other" value="<?= htmlspecialchars($v['s14_turnover_other'] ?? '') ?>" placeholder="Amount in INR">
                    </div>
                    <div class="form-group">
                        <label>Income Tax Exempted?</label>
                        <div class="radio-group">
                            <label><input type="radio" name="s14_tax_exempt" value="Yes" <?= ($v['s14_tax_exempt'] ?? '') == 'Yes' ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="s14_tax_exempt" value="No" <?= ($v['s14_tax_exempt'] ?? '') == 'No' ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Students Trained (Last FY)</label>
                        <input type="number" name="s14_students_trained" value="<?= htmlspecialchars($v['s14_students_trained'] ?? '') ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Students Placed (Last FY)</label>
                        <input type="number" name="s14_students_placed" value="<?= htmlspecialchars($v['s14_students_placed'] ?? '') ?>" min="0">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section" id="sec17">
            <div class="section-header">
                <div class="section-num">17</div>
                <div class="section-title">Required Documents Upload</div>
            </div>
            <div class="section-body">
                <p style="font-size:12px;color:var(--muted);margin-bottom:14px;">
                    Upload all applicable documents. Accepted formats: JPG, PNG, PDF. Maximum size: 5MB per file.
                </p>

                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:var(--navy);color:white;">
                            <th style="padding:9px 12px;text-align:left;width:40px;">#</th>
                            <th style="padding:9px 12px;text-align:left;">Document Name</th>
                            <th style="padding:9px 12px;text-align:left;width:200px;">Required</th>
                            <th style="padding:9px 12px;text-align:left;width:260px;">Upload</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $docs = [
                            ['s17_id_proof',        'Authorized Signatory ID Proof',                    true],
                            ['s17_signatory_sig',   'Authorized Signatory Signature',                   true],
                            ['s17_layout_map',      'Layout Map of Premises',                           true],
                            ['s17_reg_cert',        'Registration Certificate from any Govt. Authority',true],
                            ['s17_franchise_agmt',  'Franchisee / Licensee Agreement',                  false],
                            ['s17_registrar_reg',   'Registration with Registrar / Sub Registrar',      false],
                            ['s17_tax_reg',         'Registration with Sales Tax / Services Tax or any other Tax Authority', false],
                            ['s17_lease_deed',      'Lease / Rent Agreement / Ownership Deed with NOC', true],
                            ['s17_other_doc',       'Any Other Relevant Document',                      false],
                            ['s17_building_photos', 'Photos of Building (Classrooms, Computer Lab, Library, Seating, Washrooms, Reception, Staff Room)', true],
                        ];
                        foreach ($docs as $i => [$fname, $label, $required]):
                            $row_bg = ($i % 2 === 0) ? '#f8fafc' : 'white';
                        ?>
                        <tr style="background:<?= $row_bg ?>;border-bottom:1px solid #e0e8f0;">
                            <td style="padding:10px 12px;font-weight:700;color:var(--navy2);"><?= $i + 1 ?></td>
                            <td style="padding:10px 12px;"><?= htmlspecialchars($label) ?></td>
                            <td style="padding:10px 12px;">
                                <?php if ($required): ?>
                                    <span style="color:var(--error);font-weight:700;">&#9679; Required</span>
                                <?php else: ?>
                                    <span style="color:var(--muted);">&#9675; If Applicable</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:8px 12px;">
                                <input type="file" name="<?= $fname ?>" accept=".jpg,.jpeg,.png,.pdf"
                                    style="font-size:11.5px;width:100%;">
                                <div style="font-size:10px;color:var(--muted);margin-top:2px;">JPG/PNG/PDF &bull; Max 5MB</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="padding:16px 20px;background:#fff8e8;border-top:2px solid var(--gold);">
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:13px;">
                <input type="checkbox" name="declaration" required style="margin-top:3px;accent-color:var(--navy2);flex-shrink:0;">
                <span>
                    I hereby declare that all the information provided in this application is true and correct to the best of my knowledge and belief.
                    I understand that any false information may lead to rejection of the application or cancellation of the Training Partner empanelment.
                </span>
            </label>
        </div>

        <div class="form-footer">
            <div>
                <button type="submit" class="btn-submit">&#128196; Submit Application</button>
                &nbsp;
                <button type="reset" class="btn-reset">Reset Form</button>
            </div>
            <div class="footer-note">
                Already registered? <a href="../login.php">Login here</a><br>
                For help: <a href="mailto:tp@nielit.gov.in">tp@nielit.gov.in</a> | 1800-XXX-XXXX (Toll Free)
            </div>
        </div>

        </form>
    </main>
</div>

<footer class="site-footer">
    &copy; <?= date('Y') ?> NIELIT &ndash; National Institute of Electronics &amp; Information Technology<br>
    Ministry of Electronics &amp; Information Technology, Government of India<br>
    <a href="#">Privacy Policy</a> &bull; <a href="#">Terms of Use</a> &bull; <a href="#">Accessibility Statement</a> &bull; <a href="#">Sitemap</a>
</footer>

<script>
function toggleLegal() {
    var selected = document.querySelector('input[name="s9_legal_status"]:checked');
    document.querySelectorAll('.conditional-block').forEach(function(el) {
        el.classList.remove('visible');
    });
    if (selected) {
        var block = document.getElementById('legal_' + selected.value);
        if (block) block.classList.add('visible');
    }
}

// Re-trigger on page load if value preserved
document.addEventListener('DOMContentLoaded', function() {
    var checked = document.querySelector('input[name="s9_legal_status"]:checked');
    if (checked) toggleLegal();
});
</script>

</body>
</html>