<?php
// CERT-In Compliant Security Headers
session_name('NIELIT_TPMS'); // Unified session name for the entire portal
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
        foreach ($upload_fields as $uf) {
            $uploads[$uf] = handle_upload($uf);
        }

        // --- 1. INSERT INTO USERS TABLE ---
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = 'tp';
        $status = 'pending';
        
        $stmt_user = $conn->prepare("INSERT INTO users (name, email, mobile, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_user) {
            $stmt_user->bind_param("ssssss", $_POST['institute_name'], $_POST['email'], $_POST['mobile'], $hashed_password, $role, $status);
            if (!$stmt_user->execute()) {
                $errors[] = "Registration Failed: Email may already be registered (" . $stmt_user->error . ")";
            }
            $stmt_user->close();
        } else {
            $errors[] = "Database Error: Cannot initialize user creation.";
        }

        // --- 2. INSERT INTO CENTERS TABLE (Only if User creation succeeded) ---
        if (empty($errors)) {
            $sql = "INSERT INTO centers (
                institute_name, contact_email, mobile, landline, pan_number, website, institute_address, state, district, pincode, est_year, gender, category,
                s3_name, s3_father_name, s3_designation, s3_qualification, s3_experience, s3_id_type, s3_id_number, s3_address,
                s4_premises_type, s4_carpet_area, s4_computers, s4_seating, s4_internet,
                s9_legal_status, s9_prop_name, s9_partnership_date, s9_partnership_reg, s9_society_reg, s9_society_date, s9_cin, s9_incorp_date, s9_dept_name,
                s12_f1_name, s12_f1_qual, s12_f1_exam, s12_f1_year, s12_f1_board, s13_f1_desig, s13_f1_from, s13_f1_to, s13_f1_org,
                s12_f2_name, s12_f2_qual, s12_f2_exam, s12_f2_year, s12_f2_board, s13_f2_desig, s13_f2_from, s13_f2_to, s13_f2_org,
                s14_fy, s14_turnover_it, s14_turnover_other, s14_tax_exempt, s14_students_trained, s14_students_placed,
                doc_s3_id_proof, doc_s3_signature, doc_s4_layout_map, doc_s4_building_photo, doc_s4_agreement, doc_s9_legal_doc, doc_s9_moa_doc, 
                doc_s12_faculty1_cert, doc_s12_faculty2_cert, doc_s17_id_proof, doc_s17_signatory_sig, doc_s17_layout_map, doc_s17_reg_cert, 
                doc_s17_franchise_agmt, doc_s17_registrar_reg, doc_s17_tax_reg, doc_s17_lease_deed, doc_s17_other_doc, doc_s17_building_photos
            ) VALUES (" . str_repeat('?,', 77) . "?)";

            $stmt_center = $conn->prepare($sql);
            if ($stmt_center) {
                // Helpers
                $date_or_null = function($val) { return !empty($val) ? $val : null; };
                $int_or_null = function($val) { return ($val !== '' && $val !== null) ? (int)$val : null; };

                // Map exactly 78 variables to match the columns
                $v1 = $_POST['institute_name'] ?? null;
                $v2 = $_POST['email'] ?? null;
                $v3 = $_POST['mobile'] ?? null;
                $v4 = $_POST['landline'] ?? null;
                $v5 = $_POST['pan_number'] ?? null;
                $v6 = $_POST['website'] ?? null;
                $v7 = $_POST['institute_address'] ?? null;
                $v8 = $_POST['state'] ?? null;
                $v9 = $_POST['district'] ?? null;
                $v10 = $_POST['pincode'] ?? null;
                $v11 = $int_or_null($_POST['est_year'] ?? null);
                $v12 = $_POST['gender'] ?? null;
                $v13 = $_POST['category'] ?? null;
                
                $v14 = $_POST['s3_name'] ?? null;
                $v15 = $_POST['s3_father_name'] ?? null;
                $v16 = $_POST['s3_designation'] ?? null;
                $v17 = $_POST['s3_qualification'] ?? null;
                $v18 = $int_or_null($_POST['s3_experience'] ?? null);
                $v19 = $_POST['s3_id_type'] ?? null;
                $v20 = $_POST['s3_id_number'] ?? null;
                $v21 = $_POST['s3_address'] ?? null;
                
                $v22 = $_POST['s4_premises_type'] ?? null;
                $v23 = $int_or_null($_POST['s4_carpet_area'] ?? null);
                $v24 = $int_or_null($_POST['s4_computers'] ?? null);
                $v25 = $int_or_null($_POST['s4_seating'] ?? null);
                $v26 = $_POST['s4_internet'] ?? null;
                
                $v27 = $int_or_null($_POST['s9_legal_status'] ?? null);
                $v28 = $_POST['s9_prop_name'] ?? null;
                $v29 = $date_or_null($_POST['s9_partnership_date'] ?? null);
                $v30 = $_POST['s9_partnership_reg'] ?? null;
                $v31 = $_POST['s9_society_reg'] ?? null;
                $v32 = $date_or_null($_POST['s9_society_date'] ?? null);
                $v33 = $_POST['s9_cin'] ?? null;
                $v34 = $date_or_null($_POST['s9_incorp_date'] ?? null);
                $v35 = $_POST['s9_dept_name'] ?? null;
                
                $v36 = $_POST['s12_f1_name'] ?? null;
                $v37 = $_POST['s12_f1_qual'] ?? null;
                $v38 = $_POST['s12_f1_exam'] ?? null;
                $v39 = $int_or_null($_POST['s12_f1_year'] ?? null);
                $v40 = $_POST['s12_f1_board'] ?? null;
                $v41 = $_POST['s13_f1_desig'] ?? null;
                $v42 = $date_or_null($_POST['s13_f1_from'] ?? null);
                $v43 = $date_or_null($_POST['s13_f1_to'] ?? null);
                $v44 = $_POST['s13_f1_org'] ?? null;
                
                $v45 = $_POST['s12_f2_name'] ?? null;
                $v46 = $_POST['s12_f2_qual'] ?? null;
                $v47 = $_POST['s12_f2_exam'] ?? null;
                $v48 = $int_or_null($_POST['s12_f2_year'] ?? null);
                $v49 = $_POST['s12_f2_board'] ?? null;
                $v50 = $_POST['s13_f2_desig'] ?? null;
                $v51 = $date_or_null($_POST['s13_f2_from'] ?? null);
                $v52 = $date_or_null($_POST['s13_f2_to'] ?? null);
                $v53 = $_POST['s13_f2_org'] ?? null;
                
                $v54 = $_POST['s14_fy'] ?? null;
                $v55 = (!empty($_POST['s14_turnover_it']) ? (float)$_POST['s14_turnover_it'] : null);
                $v56 = (!empty($_POST['s14_turnover_other']) ? (float)$_POST['s14_turnover_other'] : null);
                $v57 = $_POST['s14_tax_exempt'] ?? null;
                $v58 = $int_or_null($_POST['s14_students_trained'] ?? null);
                $v59 = $int_or_null($_POST['s14_students_placed'] ?? null);
                
                $v60 = $uploads['s3_id_proof'] ?? null;
                $v61 = $uploads['s3_signature'] ?? null;
                $v62 = $uploads['s4_layout_map'] ?? null;
                $v63 = $uploads['s4_building_photo'] ?? null;
                $v64 = $uploads['s4_agreement'] ?? null;
                $v65 = $uploads['s9_legal_doc'] ?? null;
                $v66 = $uploads['s9_moa_doc'] ?? null;
                
                $v67 = $uploads['s12_faculty1_cert'] ?? null;
                $v68 = $uploads['s12_faculty2_cert'] ?? null;
                $v69 = $uploads['s17_id_proof'] ?? null;
                $v70 = $uploads['s17_signatory_sig'] ?? null;
                $v71 = $uploads['s17_layout_map'] ?? null;
                $v72 = $uploads['s17_reg_cert'] ?? null;
                
                $v73 = $uploads['s17_franchise_agmt'] ?? null;
                $v74 = $uploads['s17_registrar_reg'] ?? null;
                $v75 = $uploads['s17_tax_reg'] ?? null;
                $v76 = $uploads['s17_lease_deed'] ?? null;
                $v77 = $uploads['s17_other_doc'] ?? null;
                $v78 = $uploads['s17_building_photos'] ?? null;

                // Bind parameter types exactly 78 characters long
                $types = "ssssssssssiss" . "ssssisss" . "siiis" . "issssssss" . "sssisssss" . "sssisssss" . "sddsii" . "sssssss" . "ssssss" . "ssssss";

                $stmt_center->bind_param(
                    $types,
                    $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10, $v11, $v12, $v13,
                    $v14, $v15, $v16, $v17, $v18, $v19, $v20, $v21,
                    $v22, $v23, $v24, $v25, $v26,
                    $v27, $v28, $v29, $v30, $v31, $v32, $v33, $v34, $v35,
                    $v36, $v37, $v38, $v39, $v40, $v41, $v42, $v43, $v44,
                    $v45, $v46, $v47, $v48, $v49, $v50, $v51, $v52, $v53,
                    $v54, $v55, $v56, $v57, $v58, $v59,
                    $v60, $v61, $v62, $v63, $v64, $v65, $v66,
                    $v67, $v68, $v69, $v70, $v71, $v72,
                    $v73, $v74, $v75, $v76, $v77, $v78
                );

                if (!$stmt_center->execute()) {
                    $errors[] = "Application Save Error: " . $stmt_center->error;
                } else {
                    unset($_SESSION['tp_draft']);
                    $success = true;
                }
                $stmt_center->close();
            } else {
                $errors[] = "Database Error: Cannot initialize center application saving.";
            }
        }
    }
}

$v = array_merge($draft, $_POST ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Partner Registration | NIELIT TPMS</title>
    
    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Devanagari:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #155E75; 
            --primary-light: #0284C7; 
            --primary-bg: #EFF6FF; 
            --text-dark: #0F172A; 
            --text-muted: #475569; 
            --bg-body: #F8FAFC;
            --border: #E2E8F0;
            --error: #DC2626;
            --success: #059669;
            --warning: #D97706;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-body); 
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Ambient Background */
        .ambient-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; pointer-events: none; background: radial-gradient(circle at 50% 0%, #E0F2FE 0%, #F8FAFC 70%); perspective: 1000px; }
        .shape { position: absolute; background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.3)); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 15px 35px rgba(21, 94, 117, 0.05), inset 0 0 20px rgba(255, 255, 255, 0.5); animation: float-3d 25s infinite linear; }
        .cube { width: 180px; height: 180px; border-radius: 35px; top: 15%; left: 5%; animation-duration: 35s; }
        .ring { width: 260px; height: 260px; border-radius: 50%; border: 40px solid rgba(255,255,255,0.5); top: 50%; right: 2%; animation-duration: 40s; animation-direction: reverse; background: transparent; }
        @keyframes float-3d { 0% { transform: translateY(0) rotateX(0deg) rotateY(0deg) rotateZ(0deg); } 50% { transform: translateY(-30px) rotateX(180deg) rotateY(90deg) rotateZ(45deg); } 100% { transform: translateY(0) rotateX(360deg) rotateY(180deg) rotateZ(90deg); } }

        /* Header */
        .top-header { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); z-index: 100; position: relative; width: 100%; }
        .header-container { display: flex; justify-content: space-between; align-items: center; max-width: 1440px; margin: 0 auto; padding: 12px 40px; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .nielit-logo { height: 55px; width: auto; object-fit: contain; }
        .header-titles { display: flex; flex-direction: column; justify-content: center; }
        .hindi-title { font-family: 'Noto Sans Devanagari', sans-serif; font-size: 15px; color: var(--primary); font-weight: 700; line-height: 1.2; }
        .eng-title { font-size: 13px; font-weight: 600; color: var(--text-dark); margin-top: 2px;}

        /* Wizard Wrapper */
        .wizard-wrap { max-width: 950px; margin: 40px auto 60px; padding: 0 20px; position: relative; z-index: 10; }
        
        .page-title { text-align: center; margin-bottom: 30px; }
        .page-title h2 { font-size: 32px; font-weight: 800; color: var(--primary); margin-bottom: 8px; }
        .page-title p { font-size: 15px; color: var(--text-muted); font-weight: 500; }

        /* Step Indicator */
        .step-indicator { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.8); border-radius: 20px; padding: 25px 30px; margin-bottom: 25px; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05); }
        .step-bar-wrap { display: flex; justify-content: space-between; position: relative; margin-bottom: 10px; }
        .step-bar-wrap::before { content: ''; position: absolute; top: 18px; left: 0; right: 0; height: 4px; background: #E2E8F0; z-index: 0; border-radius: 2px; }
        .step-item { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 1; }
        .step-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; border: 3px solid #E2E8F0; background: #F8FAFC; color: #94A3B8; transition: 0.3s; }
        
        .step-circle.done { background: var(--success); border-color: var(--success); color: #fff; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }
        .step-circle.active { background: var(--primary-light); border-color: var(--primary-light); color: #fff; box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4); transform: scale(1.1); }
        
        .step-label { font-size: 12px; color: var(--text-muted); margin-top: 10px; text-align: center; font-weight: 600; line-height: 1.3; }
        .step-label.active { color: var(--primary-light); font-weight: 800; }
        .step-label.done { color: var(--success); }

        /* Form Card */
        .form-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.8); border-radius: 20px; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08); overflow: hidden; }
        .step-panel { display: none; padding: 30px; }
        .step-panel.active { display: block; animation: fadeUp 0.4s ease forwards; }
        
        @keyframes fadeUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

        .panel-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border-bottom: 2px solid var(--border); padding-bottom: 15px; }
        .panel-num { background: var(--primary-bg); color: var(--primary-light); width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; border: 1px solid #BAE6FD;}
        .panel-title { font-size: 18px; font-weight: 800; color: var(--text-dark); }

        /* Form Fields */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-grid.three { grid-template-columns: 1fr 1fr 1fr; }
        .col-full { grid-column: 1 / -1; }

        .form-group label { display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; }
        .form-group label .req { color: var(--error); }
        .form-group label .opt { color: #94A3B8; font-weight: 500; font-size: 11px; }
        
        .form-control, .form-select, textarea { width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 10px; font-family: inherit; font-size: 14px; background: #F8FAFC; transition: 0.3s; font-weight: 500; }
        .form-control:focus, .form-select:focus, textarea:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 4px var(--primary-bg); background: white; }
        textarea { resize: vertical; min-height: 80px; }

        /* Radio & Checkboxes */
        .radio-group { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 5px;}
        .radio-inline { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer; color: var(--text-dark); background: #F8FAFC; padding: 10px 15px; border: 1px solid var(--border); border-radius: 8px; transition: 0.2s;}
        .radio-inline:hover { background: white; border-color: var(--primary-light); }
        .radio-inline input[type="radio"] { width: 16px; height: 16px; accent-color: var(--primary); }

        /* Upload Box */
        .upload-box { border: 2px dashed #CBD5E1; border-radius: 12px; padding: 15px; background: #F8FAFC; transition: 0.3s; text-align: center;}
        .upload-box:hover { border-color: var(--primary-light); background: var(--primary-bg); }
        .upload-box label { font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px; }
        .upload-box input[type=file] { font-size: 12px; color: var(--text-muted); width: 100%; }
        .upload-hint { font-size: 11px; color: var(--text-muted); margin-top: 5px; font-weight: 600; }

        /* Sub Sections */
        .sub-sec { background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; margin-top: 25px; overflow: hidden; }
        .sub-sec-head { background: var(--primary-bg); padding: 14px 20px; font-size: 15px; font-weight: 800; color: var(--primary-light); border-bottom: 1px solid #BAE6FD; }
        .sub-sec-body { padding: 20px; }

        .cond-block { display: none; }
        .cond-block.visible { display: block; animation: fadeUp 0.3s ease; }

        /* Table */
        .doc-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .doc-table th { background: var(--primary-bg); color: var(--primary-light); padding: 14px; text-align: left; font-size: 13px; font-weight: 800; border-bottom: 2px solid #BAE6FD; }
        .doc-table td { padding: 14px; border-bottom: 1px solid var(--border); font-size: 14px; font-weight: 500; vertical-align: middle; }

        /* Alerts & Banners */
        .alert-custom { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: flex-start; gap: 12px;}
        .alert-error { background: #FEF2F2; border: 1px solid #FECACA; color: var(--error); }
        .alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; color: var(--success); }
        .draft-banner { background: #FFFBEB; border: 1px solid #FDE68A; color: var(--warning); padding: 12px 20px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}

        /* Form Navigation Bar */
        .form-nav-bar { display: flex; justify-content: space-between; align-items: center; padding: 20px 30px; background: #F8FAFC; border-top: 1px solid var(--border); border-radius: 0 0 20px 20px; }
        .btn { padding: 12px 24px; font-size: 14px; font-weight: 700; border-radius: 10px; cursor: pointer; border: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;}
        .btn-prev { background: white; color: var(--text-muted); border: 1px solid var(--border); }
        .btn-prev:hover { background: #F1F5F9; color: var(--text-dark); }
        .btn-next { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(21, 94, 117, 0.2); }
        .btn-next:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(2, 132, 199, 0.3); }
        .btn-draft { background: #FFFBEB; color: var(--warning); border: 1px solid #FDE68A; }
        .btn-draft:hover { background: #FEF3C7; }
        .btn-submit { background: var(--success); color: white; }
        .btn-submit:hover { background: #047857; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.3); }

        @media (max-width: 768px) {
            .form-grid, .form-grid.three { grid-template-columns: 1fr; }
            .header-container { flex-direction: column; text-align: center; }
            .form-nav-bar { flex-direction: column; gap: 15px; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <!-- Ambient Background -->
    <div class="ambient-bg">
        <div class="shape cube"></div>
        <div class="shape ring"></div>
    </div>

    <!-- Header -->
    <header class="top-header">
        <div class="header-container">
            <div class="header-left">
                <img src="../RR.png" alt="NIELIT Logo" class="nielit-logo">
                <div class="header-titles">
                    <span class="hindi-title">राष्ट्रीय इलेक्ट्रॉनिकी एवं सूचना प्रौद्योगिकी संस्थान, भुवनेश्वर</span>
                    <span class="eng-title">National Institute of Electronics & Information Technology, Bhubaneswar</span>
                </div>
            </div>
            <a href="../index.php" style="color: var(--primary-light); font-weight: 700; text-decoration: none; font-size: 14px;"><i class="fas fa-sign-in-alt"></i> Existing Partner Login</a>
        </div>
    </header>

    <div class="wizard-wrap">
        
        <div class="page-title">
            <h2>New Center Registration</h2>
            <p>Complete all 5 steps &bull; Save as Draft anytime &bull; Resume when ready</p>
        </div>

        <?php if ($success): ?>
            <div class="alert-custom alert-success">
                <i class="fas fa-check-circle fs-3 mt-1"></i>
                <div>
                    <strong>Application Submitted Successfully!</strong><br>
                    Your TP registration has been received. A confirmation will be sent to your email.<br>
                    <a href="../index.php" style="color: var(--success); text-decoration: underline; margin-top: 8px; display: inline-block;">Proceed to Login</a>
                </div>
            </div>
        <?php else: ?>

        <?php if (!empty($draft)): ?>
            <div class="draft-banner">
                <span><i class="fas fa-save"></i> Your saved draft has been restored.</span>
                <button onclick="clearDraft()" style="background:none; border:none; color:#B45309; cursor:pointer; font-weight:700;"><i class="fas fa-times"></i> Clear Draft</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert-custom alert-error">
                <i class="fas fa-exclamation-triangle fs-3 mt-1"></i>
                <div>
                    <strong>Please correct the following:</strong>
                    <ul style="margin: 5px 0 0 20px; padding: 0;">
                        <?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step-bar-wrap">
                <div class="step-item"><div class="step-circle active" id="sc1">1</div><div class="step-label active" id="sl1">Institute Details</div></div>
                <div class="step-item"><div class="step-circle" id="sc2">2</div><div class="step-label" id="sl2">Signatory & Premises</div></div>
                <div class="step-item"><div class="step-circle" id="sc3">3</div><div class="step-label" id="sl3">Legal Status</div></div>
                <div class="step-item"><div class="step-circle" id="sc4">4</div><div class="step-label" id="sl4">Faculty & Financial</div></div>
                <div class="step-item"><div class="step-circle" id="sc5">5</div><div class="step-label" id="sl5">Uploads & Submit</div></div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data" id="tpForm" novalidate>
            <input type="hidden" name="action" id="formAction" value="final_submit">

            <!-- ══ STEP 1: Institute Details ══ -->
            <div class="step-panel active" id="panel1">
                <div class="panel-header"><div class="panel-num">1</div><div class="panel-title">Institute / Organization Details</div></div>
                <div class="form-grid">
                    <div class="form-group col-full">
                        <label>Full Name of Institute / Organization <span class="req">*</span></label>
                        <input type="text" name="institute_name" class="form-control" required value="<?= htmlspecialchars($v['institute_name'] ?? '') ?>" placeholder="As per registration certificate">
                    </div>
                    <div class="form-group">
                        <label>Official Email <span class="req">*</span></label>
                        <input type="email" name="email" class="form-control" autocomplete="email" required value="<?= htmlspecialchars($v['email'] ?? '') ?>" placeholder="office@institute.in">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number <span class="req">*</span></label>
                        <input type="tel" name="mobile" class="form-control" autocomplete="tel" required value="<?= htmlspecialchars($v['mobile'] ?? '') ?>" placeholder="10-digit mobile">
                    </div>
                    <div class="form-group">
                        <label>Landline / STD</label>
                        <input type="tel" name="landline" class="form-control" value="<?= htmlspecialchars($v['landline'] ?? '') ?>" placeholder="0XXX-XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>PAN Number <span class="req">*</span></label>
                        <input type="text" name="pan_number" class="form-control" value="<?= htmlspecialchars($v['pan_number'] ?? '') ?>" maxlength="10" placeholder="ABCDE1234F" style="text-transform:uppercase">
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($v['website'] ?? '') ?>" placeholder="https://www.institute.in">
                    </div>
                    <div class="form-group col-full">
                        <label>Full Address of Institute <span class="req">*</span></label>
                        <textarea name="institute_address" class="form-control" placeholder="Full address with PIN code"><?= htmlspecialchars($v['institute_address'] ?? '') ?></textarea>
                    </div>

                    <!-- Dynamic State/District Dropdowns -->
                    <div class="form-group">
                        <label>State <span class="req">*</span></label>
                        <select name="state" id="stateDropdown" class="form-select" required onchange="populateDistricts()">
                            <option value="">-- Select State --</option>
                            <option value="Odisha" <?= ($v['state'] ?? '')=='Odisha'?'selected':'' ?>>Odisha</option>
                            <option value="Chhattisgarh" <?= ($v['state'] ?? '')=='Chhattisgarh'?'selected':'' ?>>Chhattisgarh</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>District <span class="req">*</span></label>
                        <select name="district" id="districtDropdown" class="form-select" required>
                            <option value="">-- Select District --</option>
                        </select>
                        <input type="hidden" id="savedDistrict" value="<?= htmlspecialchars($v['district'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>PIN Code <span class="req">*</span></label>
                        <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($v['pincode'] ?? '') ?>" maxlength="6" placeholder="6-digit">
                    </div>
                    <div class="form-group">
                        <label>Year of Establishment</label>
                        <input type="number" name="est_year" class="form-control" value="<?= htmlspecialchars($v['est_year'] ?? '') ?>" min="1950" max="2026">
                    </div>
                    <div class="form-group">
                        <label>Gender <span class="req">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option <?= ($v['gender']??'')=='Male'?'selected':'' ?>>Male</option>
                            <option <?= ($v['gender']??'')=='Female'?'selected':'' ?>>Female</option>
                            <option <?= ($v['gender']??'')=='Other'?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category <span class="req">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option <?= ($v['category']??'')=='General'?'selected':'' ?>>General</option>
                            <option <?= ($v['category']??'')=='OBC'?'selected':'' ?>>OBC</option>
                            <option <?= ($v['category']??'')=='SC'?'selected':'' ?>>SC</option>
                            <option <?= ($v['category']??'')=='ST'?'selected':'' ?>>ST</option>
                            <option <?= ($v['category']??'')=='EWS'?'selected':'' ?>>EWS</option>
                            <option <?= ($v['category']??'')=='PwD'?'selected':'' ?>>PwD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Create Password <span class="req">*</span></label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="Minimum 8 characters">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span class="req">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" autocomplete="new-password" placeholder="Re-enter password">
                    </div>
                </div>
            </div>

            <!-- ══ STEP 2: Signatory + Premises ══ -->
            <div class="step-panel" id="panel2">
                <div class="panel-header"><div class="panel-num">2</div><div class="panel-title">Authorized Signatory &amp; Premises Details</div></div>
                
                <div class="sub-sec" style="margin-top:0;">
                    <div class="sub-sec-head">Section 3 — Authorized Signatory</div>
                    <div class="sub-sec-body form-grid">
                        <div class="form-group"><label>Full Name <span class="req">*</span></label><input type="text" name="s3_name" class="form-control" value="<?= htmlspecialchars($v['s3_name'] ?? '') ?>"></div>
                        <div class="form-group"><label>Father's / Husband's Name <span class="req">*</span></label><input type="text" name="s3_father_name" class="form-control" value="<?= htmlspecialchars($v['s3_father_name'] ?? '') ?>"></div>
                        <div class="form-group"><label>Designation <span class="req">*</span></label><input type="text" name="s3_designation" class="form-control" value="<?= htmlspecialchars($v['s3_designation'] ?? '') ?>" placeholder="Director / Principal"></div>
                        <div class="form-group"><label>Qualification</label><input type="text" name="s3_qualification" class="form-control" value="<?= htmlspecialchars($v['s3_qualification'] ?? '') ?>"></div>
                        <div class="form-group"><label>Experience (Years)</label><input type="number" name="s3_experience" class="form-control" value="<?= htmlspecialchars($v['s3_experience'] ?? '') ?>" min="0"></div>
                        <div class="form-group">
                            <label>ID Proof Type <span class="req">*</span></label>
                            <select name="s3_id_type" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach(['Aadhaar','PAN Card','Passport','Voter ID','Driving Licence'] as $id): ?>
                                <option <?= ($v['s3_id_type'] ?? '')==$id?'selected':'' ?>><?= $id ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>ID Proof Number <span class="req">*</span></label><input type="text" name="s3_id_number" class="form-control" value="<?= htmlspecialchars($v['s3_id_number'] ?? '') ?>"></div>
                        <div class="form-group col-full"><label>Residential Address <span class="req">*</span></label><textarea name="s3_address" class="form-control"><?= htmlspecialchars($v['s3_address'] ?? '') ?></textarea></div>
                        <div class="upload-box"><label>Upload: ID Proof Document <span class="req">*</span></label><input type="file" name="s3_id_proof" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">JPG / PNG / PDF &bull; Max 5MB</div></div>
                        <div class="upload-box"><label>Upload: Signatory Signature <span class="req">*</span></label><input type="file" name="s3_signature" accept=".jpg,.jpeg,.png,.pdf"><div class="upload-hint">Clear scan &bull; Max 5MB</div></div>
                    </div>
                </div>

                <div class="sub-sec">
                    <div class="sub-sec-head">Section 4 — Premises &amp; Infrastructure</div>
                    <div class="sub-sec-body form-grid">
                        <div class="form-group col-full">
                            <label>Type of Premises <span class="req">*</span></label>
                            <div class="radio-group">
                                <?php foreach(['Owned','Rented','Long Term Lease'] as $pt): ?>
                                <label class="radio-inline"><input type="radio" name="s4_premises_type" value="<?= $pt ?>" <?= ($v['s4_premises_type'] ?? '')==$pt?'checked':'' ?>><?= $pt ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-group"><label>Total Carpet Area (sq.ft.) <span class="req">*</span></label><input type="number" name="s4_carpet_area" class="form-control" value="<?= htmlspecialchars($v['s4_carpet_area'] ?? '') ?>"></div>
                        <div class="form-group"><label>Number of Computers <span class="req">*</span></label><input type="number" name="s4_computers" class="form-control" value="<?= htmlspecialchars($v['s4_computers'] ?? '') ?>" min="1"></div>
                        <div class="form-group"><label>Seating Capacity <span class="req">*</span></label><input type="number" name="s4_seating" class="form-control" value="<?= htmlspecialchars($v['s4_seating'] ?? '') ?>" min="1"></div>
                        <div class="form-group">
                            <label>Internet Connectivity</label>
                            <select name="s4_internet" class="form-select">
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

            <!-- ══ STEP 3: Legal Status ══ -->
            <div class="step-panel" id="panel3">
                <div class="panel-header"><div class="panel-num">3</div><div class="panel-title">Section 9 — Legal Status of Institute</div></div>
                <div class="form-group">
                    <label>Select Legal Status <span class="req">*</span></label>
                    <div class="radio-group">
                        <?php $lopts=['1'=>'Proprietorship','2'=>'Partnership Firm','3'=>'Society / Trust','4'=>'Pvt / Public Company','5'=>'Govt / PSU'];
                        foreach($lopts as $val=>$lbl): ?>
                        <label class="radio-inline"><input type="radio" name="s9_legal_status" value="<?= $val ?>" <?= ($v['s9_legal_status'] ?? '')==$val?'checked':'' ?> onchange="toggleLegal()"> <?= $lbl ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='1'?'visible':'' ?>" id="legal_1">
                    <div class="sub-sec"><div class="sub-sec-head">Proprietorship Details</div><div class="sub-sec-body form-grid">
                        <div class="form-group"><label>Proprietor Name</label><input type="text" name="s9_prop_name" class="form-control" value="<?= htmlspecialchars($v['s9_prop_name'] ?? '') ?>"></div>
                        <div class="upload-box"><label>Upload: GST / Trade Licence <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"></div>
                    </div></div>
                </div>

                <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='2'?'visible':'' ?>" id="legal_2">
                    <div class="sub-sec"><div class="sub-sec-head">Partnership Firm Documents</div><div class="sub-sec-body form-grid">
                        <div class="form-group"><label>Deed Date</label><input type="date" name="s9_partnership_date" class="form-control" value="<?= htmlspecialchars($v['s9_partnership_date'] ?? '') ?>"></div>
                        <div class="form-group"><label>Registration Number</label><input type="text" name="s9_partnership_reg" class="form-control" value="<?= htmlspecialchars($v['s9_partnership_reg'] ?? '') ?>"></div>
                        <div class="upload-box col-full"><label>Upload: Partnership Deed (Registered) <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"></div>
                    </div></div>
                </div>

                <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='3'?'visible':'' ?>" id="legal_3">
                    <div class="sub-sec"><div class="sub-sec-head">Society / Trust Documents</div><div class="sub-sec-body form-grid">
                        <div class="form-group"><label>Registration Number</label><input type="text" name="s9_society_reg" class="form-control" value="<?= htmlspecialchars($v['s9_society_reg'] ?? '') ?>"></div>
                        <div class="form-group"><label>Registration Date</label><input type="date" name="s9_society_date" class="form-control" value="<?= htmlspecialchars($v['s9_society_date'] ?? '') ?>"></div>
                        <div class="upload-box"><label>Upload: Registration Certificate <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"></div>
                        <div class="upload-box"><label>Upload: Memorandum / Trust Deed <span class="req">*</span></label><input type="file" name="s9_moa_doc" accept=".jpg,.jpeg,.png,.pdf"></div>
                    </div></div>
                </div>

                <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='4'?'visible':'' ?>" id="legal_4">
                    <div class="sub-sec"><div class="sub-sec-head">Company Documents (MCA / ROC)</div><div class="sub-sec-body form-grid">
                        <div class="form-group"><label>CIN Number</label><input type="text" name="s9_cin" class="form-control" value="<?= htmlspecialchars($v['s9_cin'] ?? '') ?>"></div>
                        <div class="form-group"><label>Date of Incorporation</label><input type="date" name="s9_incorp_date" class="form-control" value="<?= htmlspecialchars($v['s9_incorp_date'] ?? '') ?>"></div>
                        <div class="upload-box"><label>Upload: Certificate of Incorporation <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"></div>
                        <div class="upload-box"><label>Upload: MOA &amp; AOA <span class="req">*</span></label><input type="file" name="s9_moa_doc" accept=".jpg,.jpeg,.png,.pdf"></div>
                    </div></div>
                </div>

                <div class="cond-block <?= ($v['s9_legal_status'] ?? '')=='5'?'visible':'' ?>" id="legal_5">
                    <div class="sub-sec"><div class="sub-sec-head">Government / PSU Documents</div><div class="sub-sec-body form-grid">
                        <div class="form-group"><label>Department / Ministry Name</label><input type="text" name="s9_dept_name" class="form-control" value="<?= htmlspecialchars($v['s9_dept_name'] ?? '') ?>"></div>
                        <div class="upload-box"><label>Upload: Govt. Authorization Letter <span class="req">*</span></label><input type="file" name="s9_legal_doc" accept=".jpg,.jpeg,.png,.pdf"></div>
                    </div></div>
                </div>
            </div>

            <!-- ══ STEP 4: Faculty + Financial ══ -->
            <div class="step-panel" id="panel4">
                <div class="panel-header"><div class="panel-num">4</div><div class="panel-title">Faculty, Experience &amp; Financial Details</div></div>
                
                <div class="sub-sec" style="margin-top:0;">
                    <div class="sub-sec-head">Section 12 &amp; 13 — Faculty Member 1</div>
                    <div class="sub-sec-body form-grid three">
                        <div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="s12_f1_name" class="form-control" value="<?= htmlspecialchars($v['s12_f1_name'] ?? '') ?>"></div>
                        <div class="form-group"><label>Qualification <span class="req">*</span></label><input type="text" name="s12_f1_qual" class="form-control" value="<?= htmlspecialchars($v['s12_f1_qual'] ?? '') ?>"></div>
                        <div class="form-group"><label>Examination Passed</label><input type="text" name="s12_f1_exam" class="form-control" value="<?= htmlspecialchars($v['s12_f1_exam'] ?? '') ?>"></div>
                        <div class="form-group"><label>Year of Passing</label><input type="number" name="s12_f1_year" class="form-control" value="<?= htmlspecialchars($v['s12_f1_year'] ?? '') ?>" min="1970" max="2026"></div>
                        <div class="form-group"><label>Board / University</label><input type="text" name="s12_f1_board" class="form-control" value="<?= htmlspecialchars($v['s12_f1_board'] ?? '') ?>"></div>
                        <div class="form-group"><label>Designation</label><input type="text" name="s13_f1_desig" class="form-control" value="<?= htmlspecialchars($v['s13_f1_desig'] ?? '') ?>"></div>
                        <div class="form-group"><label>Experience From</label><input type="date" name="s13_f1_from" class="form-control" value="<?= htmlspecialchars($v['s13_f1_from'] ?? '') ?>"></div>
                        <div class="form-group"><label>Experience To</label><input type="date" name="s13_f1_to" class="form-control" value="<?= htmlspecialchars($v['s13_f1_to'] ?? '') ?>"></div>
                        <div class="form-group"><label>Organization</label><input type="text" name="s13_f1_org" class="form-control" value="<?= htmlspecialchars($v['s13_f1_org'] ?? '') ?>"></div>
                        <div class="upload-box col-full"><label>Upload: Faculty 1 Certificate <span class="req">*</span></label><input type="file" name="s12_faculty1_cert" accept=".jpg,.jpeg,.png,.pdf"></div>
                    </div>
                </div>

                <div class="sub-sec">
                    <div class="sub-sec-head">Section 12 &amp; 13 — Faculty Member 2 <span style="font-weight:400;color:var(--text-muted)">(Optional)</span></div>
                    <div class="sub-sec-body form-grid three">
                        <div class="form-group"><label>Name</label><input type="text" name="s12_f2_name" class="form-control" value="<?= htmlspecialchars($v['s12_f2_name'] ?? '') ?>"></div>
                        <div class="form-group"><label>Qualification</label><input type="text" name="s12_f2_qual" class="form-control" value="<?= htmlspecialchars($v['s12_f2_qual'] ?? '') ?>"></div>
                        <div class="form-group"><label>Examination Passed</label><input type="text" name="s12_f2_exam" class="form-control" value="<?= htmlspecialchars($v['s12_f2_exam'] ?? '') ?>"></div>
                        <div class="form-group"><label>Year of Passing</label><input type="number" name="s12_f2_year" class="form-control" value="<?= htmlspecialchars($v['s12_f2_year'] ?? '') ?>" min="1970" max="2026"></div>
                        <div class="form-group"><label>Board / University</label><input type="text" name="s12_f2_board" class="form-control" value="<?= htmlspecialchars($v['s12_f2_board'] ?? '') ?>"></div>
                        <div class="form-group"><label>Designation</label><input type="text" name="s13_f2_desig" class="form-control" value="<?= htmlspecialchars($v['s13_f2_desig'] ?? '') ?>"></div>
                        <div class="form-group"><label>Experience From</label><input type="date" name="s13_f2_from" class="form-control" value="<?= htmlspecialchars($v['s13_f2_from'] ?? '') ?>"></div>
                        <div class="form-group"><label>Experience To</label><input type="date" name="s13_f2_to" class="form-control" value="<?= htmlspecialchars($v['s13_f2_to'] ?? '') ?>"></div>
                        <div class="form-group"><label>Organization</label><input type="text" name="s13_f2_org" class="form-control" value="<?= htmlspecialchars($v['s13_f2_org'] ?? '') ?>"></div>
                        <div class="upload-box col-full"><label>Upload: Faculty 2 Certificate <span class="opt">(optional)</span></label><input type="file" name="s12_faculty2_cert" accept=".jpg,.jpeg,.png,.pdf"></div>
                    </div>
                </div>

                <div class="sub-sec">
                    <div class="sub-sec-head">Section 14 — Financial &amp; Placement Details</div>
                    <div class="sub-sec-body form-grid three">
                        <div class="form-group">
                            <label>Financial Year <span class="req">*</span></label>
                            <select name="s14_fy" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach(['2024-25','2023-24','2022-23'] as $fy): ?>
                                <option <?= ($v['s14_fy'] ?? '')==$fy?'selected':'' ?>><?= $fy ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Turnover — IT/Comp (₹)</label><input type="number" name="s14_turnover_it" class="form-control" value="<?= htmlspecialchars($v['s14_turnover_it'] ?? '') ?>" placeholder="INR"></div>
                        <div class="form-group"><label>Turnover — Other (₹)</label><input type="number" name="s14_turnover_other" class="form-control" value="<?= htmlspecialchars($v['s14_turnover_other'] ?? '') ?>" placeholder="INR"></div>
                        <div class="form-group">
                            <label>Income Tax Exempted?</label>
                            <div class="radio-group">
                                <label class="radio-inline"><input type="radio" name="s14_tax_exempt" value="Yes" <?= ($v['s14_tax_exempt'] ?? '')=='Yes'?'checked':'' ?>>Yes</label>
                                <label class="radio-inline"><input type="radio" name="s14_tax_exempt" value="No" <?= ($v['s14_tax_exempt'] ?? '')=='No'?'checked':'' ?>>No</label>
                            </div>
                        </div>
                        <div class="form-group"><label>Students Trained (Last FY)</label><input type="number" name="s14_students_trained" class="form-control" value="<?= htmlspecialchars($v['s14_students_trained'] ?? '') ?>" min="0"></div>
                        <div class="form-group"><label>Students Placed (Last FY)</label><input type="number" name="s14_students_placed" class="form-control" value="<?= htmlspecialchars($v['s14_students_placed'] ?? '') ?>" min="0"></div>
                    </div>
                </div>
            </div>

            <!-- ══ STEP 5: Documents + Submit ══ -->
            <div class="step-panel" id="panel5">
                <div class="panel-header"><div class="panel-num">5</div><div class="panel-title">Section 17 — Document Uploads &amp; Final Submission</div></div>
                <p style="font-size:13px; color:var(--text-muted); margin-bottom:15px">Upload all applicable documents. Formats: JPG, PNG, PDF. Max 5MB each.</p>

                <div style="overflow-x:auto;">
                    <table class="doc-table">
                        <thead><tr><th style="width:36px">#</th><th>Document Name</th><th style="width:130px">Status</th><th style="width:250px">Upload</th></tr></thead>
                        <tbody>
                        <?php
                        $docs=[
                            ['s17_id_proof',       'Authorized Signatory — ID Proof',                                       true],
                            ['s17_signatory_sig',  'Authorized Signatory — Specimen Signature',                             true],
                            ['s17_layout_map',     'Layout Map of Premises',                                                true],
                            ['s17_reg_cert',       'Registration Certificate from any Govt. Authority',                     true],
                            ['s17_franchise_agmt', 'Franchisee / Licensee Agreement',                                       false],
                            ['s17_registrar_reg',  'Registration with Registrar / Sub Registrar',                           false],
                            ['s17_tax_reg',        'Registration with Sales Tax / Services Tax / Other Tax Authority',      false],
                            ['s17_lease_deed',     'Lease / Rent Agreement / Ownership Deed with NOC',                      true],
                            ['s17_other_doc',      'Any Other Relevant Document',                                           false],
                            ['s17_building_photos','Photos of Building (Classrooms, Lab, Library, Washrooms, Reception)',   true],
                        ];
                        foreach($docs as $i=>[$fname,$label,$req]):
                        ?>
                        <tr>
                            <td style="font-weight:800;color:var(--primary);text-align:center"><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($label) ?></td>
                            <td><?php if($req): ?><span style="color:var(--error);font-weight:700;font-size:12px">&#9679; Required</span><?php else: ?><span style="color:var(--text-muted);font-size:12px">&#9675; If Applicable</span><?php endif; ?></td>
                            <td>
                                <input type="file" name="<?= $fname ?>" accept=".jpg,.jpeg,.png,.pdf" style="font-size:12px;width:100%">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="background: var(--primary-bg); border: 1px solid #BAE6FD; padding: 20px; border-radius: 12px; margin-top: 25px;">
                    <label style="display: flex; gap: 15px; cursor: pointer; font-size: 14px; font-weight: 600; color: var(--text-dark);">
                        <input type="checkbox" name="declaration" value="1" <?= !empty($v['declaration'])?'checked':'' ?> style="width: 20px; height: 20px; margin-top: 2px;">
                        <span>I hereby declare that all information provided in this application is true, correct, and complete to the best of my knowledge. I understand that any false information may result in rejection or cancellation of Training Partner empanelment by NIELIT.</span>
                    </label>
                </div>
            </div>

            <!-- Form Navigation -->
            <div class="form-nav-bar">
                <button type="button" class="btn btn-prev" id="btnPrev" onclick="goPrev()" style="display:none;"><i class="fas fa-arrow-left"></i> Previous</button>
                <div style="margin-left: auto;"></div> <!-- Spacer -->
                
                <div style="display:flex; gap:15px; align-items: center; flex-wrap: wrap;">
                    <span id="draftMsg" style="display:none; color: var(--success); font-size: 13px; font-weight: 700;"><i class="fas fa-check"></i> Draft Saved</span>
                    <button type="button" class="btn btn-draft" onclick="saveDraft()"><i class="fas fa-save"></i> Save Draft</button>
                    <button type="button" class="btn btn-next" id="btnNext" onclick="goNext()">Next <i class="fas fa-arrow-right"></i></button>
                    <button type="submit" class="btn btn-submit" id="btnSubmit" style="display:none;"><i class="fas fa-paper-plane"></i> Submit Application</button>
                </div>
            </div>

            </form>
        </div>
        <?php endif; ?>
    </div>

<script>
// --- Cascading Dropdown Logic ---
const stateDistrictMap = {
    "Odisha": [
        "Angul", "Balangir", "Balasore", "Bargarh", "Bhadrak", "Boudh", "Cuttack", 
        "Deogarh", "Dhenkanal", "Gajapati", "Ganjam", "Jagatsinghapur", "Jajpur", 
        "Jharsuguda", "Kalahandi", "Kandhamal", "Kendrapara", "Kendujhar (Keonjhar)", 
        "Khordha", "Koraput", "Malkangiri", "Mayurbhanj", "Nabarangpur", "Nayagarh", 
        "Nuapada", "Puri", "Rayagada", "Sambalpur", "Subarnapur (Sonepur)", "Sundargarh"
    ],
    "Chhattisgarh": [
        "Balod", "Baloda Bazar", "Balrampur", "Bastar", "Bemetara", "Bijapur", "Bilaspur", 
        "Dantewada", "Dhamtari", "Durg", "Gariaband", "Gaurela Pendra Marwahi", "Janjgir-Champa", 
        "Jashpur", "Kabirdham", "Kanker", "Kondagaon", "Korba", "Koriya", "Mahasamund", 
        "Mungeli", "Narayanpur", "Raigarh", "Raipur", "Rajnandgaon", "Sukma", "Surajpur", "Surguja"
    ]
};

function populateDistricts() {
    const state = document.getElementById("stateDropdown").value;
    const districtDropdown = document.getElementById("districtDropdown");
    const savedDistrict = document.getElementById("savedDistrict").value;

    districtDropdown.innerHTML = '<option value="">-- Select District --</option>';

    if (state && stateDistrictMap[state]) {
        stateDistrictMap[state].forEach(district => {
            const option = document.createElement("option");
            option.value = district;
            option.text = district;
            if (district === savedDistrict) option.selected = true;
            districtDropdown.appendChild(option);
        });
    }
}

// --- Wizard Navigation Logic ---
let cur = 1;
const tot = 5;

function updateUI() {
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel' + cur).classList.add('active');
    
    for(let i=1; i<=tot; i++){
        let c = document.getElementById('sc'+i);
        let l = document.getElementById('sl'+i);
        c.className = 'step-circle'; 
        l.className = 'step-label';
        
        if(i < cur) {
            c.classList.add('done'); l.classList.add('done'); c.innerHTML = '<i class="fas fa-check"></i>';
        } else if(i === cur) {
            c.classList.add('active'); l.classList.add('active'); c.innerHTML = i;
        } else {
            c.innerHTML = i;
        }
    }

    document.getElementById('btnPrev').style.display = (cur > 1) ? 'inline-flex' : 'none';
    if(cur === tot) {
        document.getElementById('btnNext').style.display = 'none';
        document.getElementById('btnSubmit').style.display = 'inline-flex';
    } else {
        document.getElementById('btnNext').style.display = 'inline-flex';
        document.getElementById('btnSubmit').style.display = 'none';
    }
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function goNext() { if(cur < tot) { cur++; updateUI(); } }
function goPrev() { if(cur > 1) { cur--; updateUI(); } }

function saveDraft(){
    let fd = new FormData(document.getElementById('tpForm'));
    fd.set('action', 'save_draft');
    fetch(window.location.href, {method: 'POST', body: fd})
    .then(r => r.json())
    .then(d => {
        if(d.status === 'ok'){
            let m = document.getElementById('draftMsg');
            m.style.display = 'inline';
            setTimeout(() => m.style.display = 'none', 3000);
        }
    }).catch(() => alert('Draft save failed. Try again.'));
}

function clearDraft(){
    if(!confirm('Clear saved draft? This cannot be undone.')) return;
    fetch(window.location.href, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=clear_draft'})
    .then(() => location.reload());
}

function toggleLegal(){
    document.querySelectorAll('.cond-block').forEach(e => e.classList.remove('visible'));
    let s = document.querySelector('input[name="s9_legal_status"]:checked');
    if(s){
        let b = document.getElementById('legal_'+s.value);
        if(b) b.classList.add('visible');
    }
}

document.addEventListener('DOMContentLoaded', function(){
    <?php if(!empty($errors)): ?>cur = 5;<?php endif; ?>
    if (document.getElementById("stateDropdown").value !== "") populateDistricts();
    updateUI();
    toggleLegal();

    // ── Enter key → move to next field, never submit ──
    var form = document.getElementById('tpForm');
    if(form){
        form.addEventListener('keydown', function(e){
            if(e.key !== 'Enter') return;
            var focusable = Array.from(
                form.querySelectorAll('input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])')
            ).filter(function(el){
                return el.offsetParent !== null; 
            });
            var idx = focusable.indexOf(document.activeElement);
            if(idx === -1 || idx === focusable.length - 1){
                e.preventDefault();
                return;
            }
            e.preventDefault();
            focusable[idx + 1].focus();
        });
    }
});
</script>
</body>
</html>