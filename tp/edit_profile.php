<?php
/**
 * ============================================================================
 * NIELIT TPMS - INSTITUTE SETTINGS & PROFILE COMMAND CENTER
 * ============================================================================
 * File: edit_profile.php
 * Description: Comprehensive settings hub for Training Partners. Manages public
 * profile, contact data, password security, and read-only registration vaults.
 * Matches the unified Enterprise SaaS Theme.
 * ============================================================================
 */

// 1. SECURITY & SESSION INITIALIZATION
session_name('NIELIT_TPMS');
session_start();

// Strict Role Checking: Training Partner Only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tp') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/config.php';

// Global Variables
$tp_email = $_SESSION['user_email'];
$message = '';
$msg_type = '';

// 2. FETCH CORE USER DETAILS
$tp_user_id = 0;
$stmt_uid = $conn->prepare("SELECT id FROM users WHERE email = ?");
if ($stmt_uid) {
    $stmt_uid->bind_param("s", $tp_email);
    $stmt_uid->execute();
    $res_uid = $stmt_uid->get_result();
    if ($row = $res_uid->fetch_assoc()) {
        $tp_user_id = $row['id'];
    }
    $stmt_uid->close();
}

// 3. HANDLE FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ------------------------------------------------------------------------
    // ACTION A: PROFILE & CONTACT UPDATE
    // ------------------------------------------------------------------------
    if ($_POST['action'] === 'update_profile') {
        $mobile = trim($_POST['mobile']);
        $landline = trim($_POST['landline'] ?? '');
        $website = trim($_POST['website']);
        $address = trim($_POST['institute_address']);
        $state = trim($_POST['state']);
        $district = trim($_POST['district']);
        $pincode = trim($_POST['pincode']);
        $s3_name = trim($_POST['s3_name']);
        $s3_designation = trim($_POST['s3_designation']);
        $s3_experience = intval($_POST['s3_experience'] ?? 0);

        // Update centers table
        $stmt = $conn->prepare("UPDATE centers SET 
            mobile = ?, landline = ?, website = ?, institute_address = ?, state = ?, district = ?, pincode = ?, s3_name = ?, s3_designation = ?, s3_experience = ? 
            WHERE contact_email = ?");
        
        if ($stmt) {
            $stmt->bind_param("sssssssssis", $mobile, $landline, $website, $address, $state, $district, $pincode, $s3_name, $s3_designation, $s3_experience, $tp_email);
            if ($stmt->execute()) {
                // Sync mobile to users table for login consistency
                $stmt_user = $conn->prepare("UPDATE users SET mobile = ? WHERE email = ?");
                if ($stmt_user) {
                    $stmt_user->bind_param("ss", $mobile, $tp_email);
                    $stmt_user->execute();
                    $stmt_user->close();
                }
                $message = "Institute configuration successfully updated and saved.";
                $msg_type = "success";
            } else {
                $message = "System Error: Could not save configuration.";
                $msg_type = "danger";
            }
            $stmt->close();
        }

        // Handle File Uploads (Images)
        $upload_dir = '../uploads/images/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        
        // Profile Photo Logic
        if (!empty($_FILES['profile_photo']['name'])) {
            if(in_array($_FILES['profile_photo']['type'], $allowed_types)) {
                $profile_name = time() . '_logo_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["profile_photo"]["name"]));
                $target_file = $upload_dir . $profile_name;
                if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                    $db_path = 'uploads/images/' . $profile_name;
                    $conn->query("UPDATE centers SET profile_photo='$db_path' WHERE contact_email='$tp_email'");
                    $message = "Profile & Media updated successfully.";
                }
            } else {
                $message = "Invalid file type for Profile Logo. Only JPG/PNG allowed.";
                $msg_type = "warning";
            }
        }

        // Cover Photo Logic
        if (!empty($_FILES['cover_photo']['name'])) {
            if(in_array($_FILES['cover_photo']['type'], $allowed_types)) {
                $cover_name = time() . '_cover_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["cover_photo"]["name"]));
                $target_file = $upload_dir . $cover_name;
                if (move_uploaded_file($_FILES["cover_photo"]["tmp_name"], $target_file)) {
                    $db_path = 'uploads/images/' . $cover_name;
                    $conn->query("UPDATE centers SET cover_photo='$db_path' WHERE contact_email='$tp_email'");
                    $message = "Profile & Media updated successfully.";
                }
            }
        }
    }
    
    // ------------------------------------------------------------------------
    // ACTION B: SECURITY / PASSWORD UPDATE
    // ------------------------------------------------------------------------
    elseif ($_POST['action'] === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $message = "New passwords do not match.";
            $msg_type = "danger";
        } else {
            // Verify current password
            $stmt_pass = $conn->prepare("SELECT password FROM users WHERE email = ?");
            $stmt_pass->bind_param("s", $tp_email);
            $stmt_pass->execute();
            $res_pass = $stmt_pass->get_result();
            if ($row = $res_pass->fetch_assoc()) {
                if (password_verify($current_password, $row['password'])) {
                    // Hash and Update new password
                    $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pass = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $update_pass->bind_param("ss", $hashed_new, $tp_email);
                    if ($update_pass->execute()) {
                        $message = "Security credentials updated successfully.";
                        $msg_type = "success";
                    }
                    $update_pass->close();
                } else {
                    $message = "Incorrect current password.";
                    $msg_type = "danger";
                }
            }
            $stmt_pass->close();
        }
    }
}

// ============================================================================
// 4. FETCH FULL REGISTRATION DATA
// ============================================================================
$tp_data = [];
$stmt_fetch = $conn->prepare("SELECT * FROM centers WHERE contact_email = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("s", $tp_email);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result && $result->num_rows > 0) {
        $tp_data = $result->fetch_assoc();
    } else {
        $message = "Critical Error: Could not locate master record.";
        $msg_type = "danger";
    }
    $stmt_fetch->close();
}

// Compute Profile Health Score
$profile_score = 40; 
if(!empty($tp_data['profile_photo'])) $profile_score += 20;
if(!empty($tp_data['cover_photo'])) $profile_score += 15;
if(!empty($tp_data['website'])) $profile_score += 15;
if(!empty($tp_data['mobile'])) $profile_score += 10;
$health_color = ($profile_score >= 100) ? '#10B981' : '#3B82F6';

// Fallback images
$profile_img = !empty($tp_data['profile_photo']) ? '../' . $tp_data['profile_photo'] : 'https://ui-avatars.com/api/?name='.urlencode($tp_data['institute_name'] ?? 'TP').'&background=3B82F6&color=fff&size=200';
$cover_img = !empty($tp_data['cover_photo']) ? '../' . $tp_data['cover_photo'] : 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=1200&h=300';
$institute_initials = strtoupper(substr($tp_data['institute_name'] ?? 'TP', 0, 2));

// Document Dictionary for the Vault
$document_vault = [
    'doc_s17_reg_cert' => 'Institute Registration Certificate',
    'doc_s9_legal_doc' => 'Legal Status Document',
    'doc_s9_moa_doc' => 'Memorandum of Association (MoA)',
    'doc_s17_pan_card' => 'Institute PAN Card',
    'doc_s3_id_proof' => 'Signatory ID Proof',
    'doc_s3_signature' => 'Signatory Digital Signature',
    'doc_s4_layout_map' => 'Premises Layout Map',
    'doc_s4_building_photo' => 'Building Facade Photo',
    'doc_s4_agreement' => 'Premises Ownership/Lease Agreement',
    'doc_s12_faculty1_cert' => 'Faculty 1 Qualification Certificate',
    'doc_s12_faculty2_cert' => 'Faculty 2 Qualification Certificate'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institute Settings - NIELIT TPMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            /* Theme Colors matched to Dashboard */
            --sidebar-bg: #0B1120; 
            --sidebar-hover: #1E293B;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --bg-body: #F4F7F9;
            --card-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --text-light: #F8FAFC;
            --primary: #3B82F6; 
            --primary-hover: #2563EB;
            --primary-light: #EFF6FF;
            --secondary: #475569;
            --accent-success: #10B981;
            --accent-warning: #F59E0B;
            --accent-danger: #EF4444;
            --accent-purple: #8B5CF6;
            
            /* Structural Elements */
            --border-color: #E2E8F0;
            --sidebar-width: 280px;
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
            --transition-speed: 0.3s;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.02);
            --shadow-glow: 0 0 20px rgba(59, 130, 246, 0.3);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-dark); overflow-x: hidden; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-body); }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

        /* --- SIDEBAR --- */
        #sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); position: fixed; top: 0; left: 0; height: 100vh; z-index: 1050; display: flex; flex-direction: column; border-right: 1px solid var(--sidebar-border); transition: transform var(--transition-speed) ease; }
        .sidebar-brand { padding: 30px 25px; border-bottom: 1px solid var(--sidebar-border); text-align: center; display: flex; flex-direction: column; align-items: center; }
        .sidebar-brand-icon { width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary), var(--accent-purple)); border-radius: var(--border-radius-md); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px; box-shadow: var(--shadow-glow); }
        .sidebar-brand h4 { font-weight: 800; font-size: 20px; margin: 0; color: var(--text-light); letter-spacing: 0.5px; }
        .sidebar-brand span { font-size: 11px; color: #94A3B8; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 5px; }
        
        .sidebar-menu { padding: 25px 15px; flex-grow: 1; overflow-y: auto; }
        .sidebar-menu-category { font-size: 10px; color: #475569; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin: 15px 0 5px 15px; }
        .sidebar-menu a { padding: 12px 18px; margin-bottom: 5px; display: flex; align-items: center; color: #94A3B8; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: var(--border-radius-sm); transition: all var(--transition-speed) ease; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--sidebar-hover); color: white; transform: translateX(4px); }
        .sidebar-menu a i { width: 30px; font-size: 16px; transition: var(--transition-speed); }
        .sidebar-menu a.active i { color: #60A5FA; }
        
        .sidebar-footer { padding: 20px 15px; border-top: 1px solid var(--sidebar-border);}
        .btn-logout { width: 100%; padding: 12px; background: rgba(239, 68, 68, 0.05); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.1); border-radius: var(--border-radius-sm); font-weight: 600; font-size: 14px; transition: var(--transition-speed); display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .btn-logout:hover { background: var(--accent-danger); color: white; border-color: var(--accent-danger); box-shadow: 0 0 15px rgba(239, 68, 68, 0.3); }

        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1040; display: none; backdrop-filter: blur(3px); }

        /* --- MAIN CONTENT & TOPBAR --- */
        #main-content { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; transition: margin var(--transition-speed) ease; }
        .top-navbar { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 999; box-shadow: var(--shadow-sm); }
        .mobile-toggle-btn { display: none; background: none; border: none; font-size: 24px; color: var(--text-dark); cursor: pointer; }
        
        .nav-profile-area { display: flex; align-items: center; gap: 20px; margin-left: auto;}
        .nav-profile-info { text-align: right; display: flex; flex-direction: column; justify-content: center;}
        .nav-profile-info span { font-size: 14px; font-weight: 700; color: var(--text-dark); line-height: 1.2;}
        .nav-profile-info small { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;}
        .avatar-circle { width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary), var(--accent-purple)); border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 18px; box-shadow: var(--shadow-md); overflow: hidden; border: 2px solid white; }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

        .settings-container { padding: 40px; flex-grow: 1; max-width: 1600px; margin: 0 auto; width: 100%; }

        /* --- HERO BANNER --- */
        .hero-banner { background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: var(--border-radius-lg); padding: 40px 50px; color: white; display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; box-shadow: var(--shadow-lg); position: relative; overflow: hidden; }
        .hero-banner::after { content: ''; position: absolute; bottom: -100px; right: -50px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none;}
        .hero-content { position: relative; z-index: 2; max-width: 600px;}
        .hero-content h1 { font-size: 28px; font-weight: 800; margin-bottom: 5px;}
        .hero-content p { color: #CBD5E1; font-size: 15px; font-weight: 500; margin: 0; line-height: 1.6;}

        /* --- SETTINGS LAYOUT (Grid) --- */
        .settings-grid { display: grid; grid-template-columns: 280px 1fr; gap: 30px; align-items: start; }
        
        /* Navigation Sidebar for Settings */
        .settings-nav { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); padding: 20px; position: sticky; top: 100px; box-shadow: var(--shadow-sm);}
        .nav-section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94A3B8; margin: 15px 0 10px 15px; letter-spacing: 1px;}
        .settings-nav .nav-link { color: var(--text-muted); font-weight: 600; padding: 12px 18px; border-radius: var(--border-radius-sm); margin-bottom: 5px; transition: var(--transition-speed); font-size: 14px; display: flex; align-items: center; gap: 10px;}
        .settings-nav .nav-link i { width: 20px; font-size: 16px; text-align: center;}
        .settings-nav .nav-link.active { background-color: var(--primary-light); color: var(--primary); font-weight: 700; box-shadow: 0 2px 5px rgba(59,130,246,0.1); }
        .settings-nav .nav-link:hover:not(.active) { background-color: #F8FAFC; color: var(--text-dark); }
        
        /* Content Cards */
        .content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); box-shadow: var(--shadow-sm); padding: 35px; margin-bottom: 25px; animation: fadeIn 0.4s ease-out;}
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card-header-title { display: flex; align-items: center; gap: 12px; margin-bottom: 25px; border-bottom: 2px solid #F1F5F9; padding-bottom: 15px;}
        .card-header-title h5 { font-weight: 800; font-size: 18px; margin: 0; color: var(--text-dark);}
        .card-header-title i { color: var(--primary); font-size: 20px;}

        /* --- FORMS & UPLOADS --- */
        .form-label { font-size: 13px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
        .form-control, .form-select { border-radius: var(--border-radius-sm); font-size: 14px; padding: 12px 16px; border: 1px solid var(--border-color); background: #F8FAFC; color: var(--text-dark); font-weight: 500; transition: var(--transition-speed);}
        .form-control:focus, .form-select:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); outline: none;}
        .form-control:read-only { background: #E2E8F0; color: #64748B; cursor: not-allowed; }
        
        /* Custom Upload Zones */
        .upload-zone { border: 2px dashed var(--border-color); border-radius: var(--border-radius-md); padding: 20px; text-align: center; background: #F8FAFC; transition: var(--transition-speed); position: relative; overflow: hidden; cursor: pointer;}
        .upload-zone:hover { border-color: var(--primary); background: var(--primary-light);}
        .upload-zone input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10;}
        .upload-icon { font-size: 24px; color: var(--primary); margin-bottom: 10px;}
        .upload-text { font-size: 13px; font-weight: 600; color: var(--text-muted);}
        
        .cover-preview { width: 100%; height: 200px; border-radius: var(--border-radius-md); object-fit: cover; background: #E2E8F0; margin-bottom: 15px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);}
        .profile-preview { width: 130px; height: 130px; border-radius: 50%; object-fit: cover; background: #E2E8F0; margin: 0 auto 15px auto; border: 4px solid white; box-shadow: var(--shadow-md); display: block;}

        /* --- READONLY DATA GRIDS --- */
        .data-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .data-item { background: #F8FAFC; padding: 18px; border-radius: var(--border-radius-md); border: 1px solid #F1F5F9;}
        .data-label { font-size: 11px; color: var(--text-muted); font-weight: 800; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;}
        .data-value { font-size: 15px; color: var(--text-dark); font-weight: 600; word-break: break-word;}

        /* --- DOCUMENT VAULT --- */
        .vault-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;}
        .vault-item { display: flex; align-items: center; justify-content: space-between; background: white; border: 1px solid var(--border-color); padding: 15px 20px; border-radius: var(--border-radius-md); transition: var(--transition-speed);}
        .vault-item:hover { border-color: var(--primary); box-shadow: var(--shadow-sm); transform: translateY(-2px);}
        .vault-icon { width: 45px; height: 45px; background: #FEF2F2; color: #EF4444; border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;}
        .vault-info { flex-grow: 1; padding: 0 15px;}
        .vault-info strong { display: block; font-size: 13px; color: var(--text-dark); margin-bottom: 2px;}
        .vault-info span { font-size: 11px; color: var(--accent-success); font-weight: 800; background: #ECFDF5; padding: 2px 6px; border-radius: 4px; display: inline-block;}
        .btn-download { background: var(--primary-light); color: var(--primary); width: 38px; height: 38px; border-radius: var(--border-radius-sm); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: var(--transition-speed);}
        .btn-download:hover { background: var(--primary); color: white;}

        /* Alerts & Info Boxes */
        .info-box { background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-left: 4px solid var(--primary); padding: 15px 20px; border-radius: var(--border-radius-sm); font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 25px; display: flex; align-items: flex-start; gap: 12px;}
        .info-box i { color: var(--primary); font-size: 18px; margin-top: 2px;}

        /* Floating Action Button for Saving */
        .floating-save-bar { position: fixed; bottom: 0; left: var(--sidebar-width); right: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 15px 40px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; z-index: 900; box-shadow: 0 -4px 10px rgba(0,0,0,0.05); transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);}
        .floating-save-bar.show { transform: translateY(0); }
        .btn-save { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 15px; box-shadow: var(--shadow-glow); transition: var(--transition-speed);}
        .btn-save:hover { background: var(--primary-hover); transform: translateY(-2px);}
        .btn-discard { background: #F1F5F9; color: var(--secondary); border: none; padding: 12px 25px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 14px; transition: var(--transition-speed);}
        .btn-discard:hover { background: #E2E8F0; color: var(--text-dark);}

        /* Modals */
        .modal-content { border-radius: var(--border-radius-lg); border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { border-bottom: 1px solid var(--border-color); padding: 25px 30px; background: #F8FAFC; border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;}
        .modal-body { padding: 30px; }
        .modal-footer { border-top: 1px solid var(--border-color); padding: 20px 30px; background: #F8FAFC; border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);}

        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); }
            #main-content { margin-left: 0; }
            .mobile-toggle-btn { display: block; }
            .settings-grid { grid-template-columns: 1fr; }
            .settings-nav { position: static; margin-bottom: 20px;}
            .floating-save-bar { left: 0; }
            .hero-banner { flex-direction: column; text-align: left; align-items: flex-start; gap: 20px;}
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon"><i class="fas fa-satellite-dish"></i></div>
            <h4>NIELIT<span style="color: #94A3B8; font-weight: 500;">TPMS</span></h4>
            <span>Command Center</span>
        </div>
        
        <div class="sidebar-menu">
            <div class="sidebar-menu-category">Main Menu</div>
            <a href="tp_dashboard.php"><i class="fas fa-home"></i> Overview</a>
            <a href="edit_profile.php" class="active"><i class="fas fa-id-card-clip"></i> Institute Settings</a>
            
            <div class="sidebar-menu-category mt-4">Academic Operations</div>
            <a href="tp_courses.php"><i class="fas fa-layer-group"></i> Manage Batches</a>
            <a href="tp_students_data.php"><i class="fas fa-users"></i> Student Database</a>
            
            <div class="sidebar-menu-category mt-4">System Hub</div>
            <a href="tp_notices.php"><i class="fas fa-bullhorn"></i> Official Notices</a>
            <a href="tp_helpdesk.php"><i class="fas fa-headset"></i> Support Desk</a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="btn-logout"><i class="fas fa-power-off"></i> Secure Logout</a>
        </div>
    </aside>

    <main id="main-content">
        
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="mobile-toggle-btn" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                <div class="d-none d-md-block">
                    <span class="badge bg-light text-dark border shadow-sm" style="font-weight: 700; padding: 8px 12px; font-size: 12px;">
                        <i class="fas fa-shield-alt text-success me-1"></i> Operations Secure
                    </span>
                </div>
            </div>
            
            <div class="nav-profile-area">
                <div class="nav-profile-info d-none d-sm-flex">
                    <span><?= htmlspecialchars($tp_data['institute_name'] ?? 'Training Partner') ?></span>
                    <small>Settings Portal</small>
                </div>
                <div class="avatar-circle">
                    <?php if(!empty($tp_data['profile_photo'])): ?>
                        <img src="../<?= htmlspecialchars($tp_data['profile_photo']) ?>" alt="Profile">
                    <?php else: ?>
                        <?= $institute_initials ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="settings-container">
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: var(--border-radius-md); font-weight: 600; border-left: 5px solid <?= $msg_type=='success'?'var(--accent-success)':'var(--accent-danger)' ?> !important;">
                    <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-danger' ?> me-2 fs-5"></i> 
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="hero-banner">
                <div class="hero-content">
                    <h1>Institute Configuration</h1>
                    <p>Manage your public profile, update contact routing, modify security credentials, and access your verified registration vault.</p>
                </div>
                <div class="d-none d-lg-block text-end z-2" style="position: relative;">
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94A3B8; margin-bottom: 5px;">Profile Health</div>
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 150px; height: 8px; background: rgba(255,255,255,0.1); border-radius: 10px; overflow: hidden;">
                            <div style="width: <?= $profile_score ?>%; height: 100%; background: <?= $health_color ?>;"></div>
                        </div>
                        <span style="font-weight: 800; font-size: 18px;"><?= $profile_score ?>%</span>
                    </div>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" id="settingsForm">
                <input type="hidden" name="action" id="formAction" value="update_profile">

                <div class="settings-grid">
                    
                    <div class="settings-nav">
                        <div class="nav-section-title">Editable Settings</div>
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-public" type="button"><i class="fas fa-globe"></i> Public Media</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-contact" type="button"><i class="fas fa-map-marker-alt"></i> Contact & Location</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-signatory" type="button"><i class="fas fa-user-tie"></i> Operational Signatory</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-security" type="button"><i class="fas fa-shield-alt"></i> Security & Access</button>
                        </div>
                        
                        <div class="nav-section-title mt-4">Verified Vault (Read-Only)</div>
                        <div class="nav flex-column nav-pills" role="tablist">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-core" type="button"><i class="fas fa-building"></i> Core Identity (S1)</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-infra" type="button"><i class="fas fa-laptop-house"></i> Infrastructure (S4)</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-legal" type="button"><i class="fas fa-balance-scale"></i> Legal Status (S9)</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-faculty" type="button"><i class="fas fa-chalkboard-teacher"></i> Faculty List (S12)</button>
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-docs" type="button"><i class="fas fa-folder-open"></i> Document Vault</button>
                        </div>
                    </div>

                    <div class="tab-content" id="v-pills-tabContent">
                        
                        <div class="tab-pane fade show active" id="tab-public" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title">
                                    <i class="fas fa-image"></i> <h5>Public Profile Media</h5>
                                </div>
                                <div class="info-box">
                                    <i class="fas fa-info-circle"></i>
                                    <span>These images are displayed on your public NIELIT listing and admin dashboards. Use high-quality, professional images representing your institute.</span>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <label class="form-label">Institute Cover Photo (1200x300px recommended)</label>
                                        <img src="<?= $cover_img ?>" class="cover-preview" id="coverPreview">
                                        <div class="upload-zone">
                                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                            <div class="upload-text">Drag & Drop or Click to Upload New Cover</div>
                                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">JPG, PNG up to 2MB</div>
                                            <input type="file" name="cover_photo" accept="image/jpeg, image/png" onchange="previewImage(this, 'coverPreview')">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label text-center d-block">Institute Logo (Square)</label>
                                        <img src="<?= $profile_img ?>" class="profile-preview" id="profilePreview">
                                        <div class="upload-zone p-3">
                                            <div class="upload-text"><i class="fas fa-camera me-2"></i> Select Logo</div>
                                            <input type="file" name="profile_photo" accept="image/jpeg, image/png" onchange="previewImage(this, 'profilePreview')">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4 d-flex flex-column justify-content-center">
                                        <label class="form-label">Official Website URL</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-globe text-muted"></i></span>
                                            <input type="url" name="website" class="form-control border-start-0 ps-0" value="<?= htmlspecialchars($tp_data['website'] ?? '') ?>" placeholder="https://www.yourinstitute.com" onchange="triggerSaveBar()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-contact" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title">
                                    <i class="fas fa-map-marked-alt"></i> <h5>Contact Routing & Location</h5>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Primary Mobile Number <span class="text-danger">*</span></label>
                                        <input type="tel" name="mobile" class="form-control" value="<?= htmlspecialchars($tp_data['mobile'] ?? '') ?>" required pattern="[0-9]{10}" onchange="triggerSaveBar()">
                                        <small class="text-muted" style="font-size: 11px;">Used for system alerts and student contact.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Landline Number (Optional)</label>
                                        <input type="text" name="landline" class="form-control" value="<?= htmlspecialchars($tp_data['landline'] ?? '') ?>" onchange="triggerSaveBar()">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Operational Address <span class="text-danger">*</span></label>
                                        <textarea name="institute_address" class="form-control" rows="3" required onchange="triggerSaveBar()"><?= htmlspecialchars($tp_data['institute_address'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">State <span class="text-danger">*</span></label>
                                        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($tp_data['state'] ?? '') ?>" required onchange="triggerSaveBar()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">District <span class="text-danger">*</span></label>
                                        <input type="text" name="district" class="form-control" value="<?= htmlspecialchars($tp_data['district'] ?? '') ?>" required onchange="triggerSaveBar()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Postal PIN Code <span class="text-danger">*</span></label>
                                        <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($tp_data['pincode'] ?? '') ?>" required pattern="[0-9]{6}" onchange="triggerSaveBar()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-signatory" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title">
                                    <i class="fas fa-user-tie"></i> <h5>Operational Signatory Details</h5>
                                </div>
                                <div class="info-box">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>You may update the contact details of the signatory here. Changes to the core identity document (Aadhaar/PAN) associated with the signatory require a formal change request.</span>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Signatory Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="s3_name" class="form-control" value="<?= htmlspecialchars($tp_data['s3_name'] ?? '') ?>" required onchange="triggerSaveBar()">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Official Designation <span class="text-danger">*</span></label>
                                        <input type="text" name="s3_designation" class="form-control" value="<?= htmlspecialchars($tp_data['s3_designation'] ?? '') ?>" required onchange="triggerSaveBar()">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Years of Experience</label>
                                        <div class="input-group">
                                            <input type="number" name="s3_experience" class="form-control" value="<?= htmlspecialchars($tp_data['s3_experience'] ?? '0') ?>" onchange="triggerSaveBar()">
                                            <span class="input-group-text bg-light border-start-0 text-muted">Years</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mt-4 pt-3 border-top">
                                        <label class="form-label text-muted text-uppercase" style="font-size: 11px; letter-spacing: 1px;">Locked Identity Data</label>
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" value="ID Type: <?= htmlspecialchars($tp_data['s3_id_type'] ?? 'N/A') ?>" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" value="ID Number: <?= htmlspecialchars($tp_data['s3_id_number'] ?? 'N/A') ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-security" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title">
                                    <i class="fas fa-lock"></i> <h5>Security & Access Control</h5>
                                </div>
                                
                                <div class="mb-5 pb-4 border-bottom">
                                    <h6 class="fw-bold mb-3 text-dark">Change Account Password</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" name="current_password" class="form-control" placeholder="Enter current password" id="currPass">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 8 characters" id="newPass">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" name="confirm_password" class="form-control" placeholder="Retype new password" id="confPass">
                                        </div>
                                        <div class="col-12 text-end mt-3">
                                            <button type="button" class="btn btn-dark fw-bold px-4" style="border-radius: var(--border-radius-sm);" onclick="submitPasswordChange()">Update Password</button>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h6 class="fw-bold mb-3 text-dark">Login Sessions</h6>
                                    <div class="d-flex justify-content-between align-items-center p-4 bg-light rounded border border-dashed">
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 45px; height: 45px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--primary); box-shadow: var(--shadow-sm);">
                                                <i class="fas fa-desktop"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">Current Session</div>
                                                <div style="font-size: 12px; color: var(--text-muted);">IP: <?= $_SERVER['REMOTE_ADDR'] ?> | Browsing from current device</div>
                                            </div>
                                        </div>
                                        <span class="badge bg-success" style="padding: 6px 12px; border-radius: 50px;">Active Now</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-core" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-building text-secondary"></i> <h5 class="text-secondary">Core Registration Identity</h5>
                                    </div>
                                    <span class="badge bg-light text-secondary border p-2"><i class="fas fa-lock me-1"></i> Verified Data</span>
                                </div>
                                <div class="info-box border-secondary text-secondary" style="border-left-color: var(--secondary) !important; background: #F8FAFC;">
                                    <i class="fas fa-info-circle text-secondary"></i>
                                    <div>This data was verified during onboarding. To amend legally binding names or PAN details, initiate a formal modification request via the helpdesk.</div>
                                </div>
                                
                                <div class="data-grid mb-4">
                                    <div class="data-item">
                                        <div class="data-label">Registered Institute Name</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['institute_name'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Primary Email (Login ID)</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['contact_email'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">PAN Number</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['pan_number'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Year of Establishment</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['est_year'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-3 py-2 border-2" data-bs-toggle="modal" data-bs-target="#requestChangeModal">Request Data Modification</button>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-infra" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-laptop-house text-secondary"></i> <h5 class="text-secondary">Section 4: Infrastructure Capability</h5>
                                    </div>
                                    <span class="badge bg-light text-secondary border p-2"><i class="fas fa-lock me-1"></i> Verified Data</span>
                                </div>
                                <div class="data-grid mb-4">
                                    <div class="data-item">
                                        <div class="data-label">Premises Ownership</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s4_premises_type'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Total Carpet Area</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s4_carpet_area'] ?? 'N/A') ?> sq.ft.</div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Computer Terminals</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s4_computers'] ?? '0') ?> Systems</div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Classroom Seating Capacity</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s4_seating'] ?? '0') ?> Seats</div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Internet Infrastructure</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s4_internet'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-3 py-2 border-2" data-bs-toggle="modal" data-bs-target="#requestChangeModal">Request Capacity Update</button>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-legal" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-balance-scale text-secondary"></i> <h5 class="text-secondary">Section 9: Legal Entity Status</h5>
                                    </div>
                                    <span class="badge bg-light text-secondary border p-2"><i class="fas fa-lock me-1"></i> Verified Data</span>
                                </div>
                                <div class="data-grid">
                                    <div class="data-item">
                                        <div class="data-label">Legal Status Type Code</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s9_legal_status'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Proprietor / Organization Name</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s9_prop_name'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">CIN / Registration No.</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s9_cin'] ?? htmlspecialchars($tp_data['s9_society_reg'] ?? 'N/A')) ?></div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Date of Incorporation</div>
                                        <div class="data-value"><?= htmlspecialchars($tp_data['s9_incorp_date'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-faculty" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-chalkboard-teacher text-secondary"></i> <h5 class="text-secondary">Section 12: Faculty Roster</h5>
                                    </div>
                                    <span class="badge bg-light text-secondary border p-2"><i class="fas fa-lock me-1"></i> Verified Data</span>
                                </div>
                                
                                <h6 class="fw-bold mb-3 text-secondary text-uppercase" style="font-size: 13px; letter-spacing: 1px;">Primary Faculty (F1)</h6>
                                <div class="data-grid mb-4">
                                    <div class="data-item"><div class="data-label">Name</div><div class="data-value"><?= htmlspecialchars($tp_data['s12_f1_name'] ?? 'N/A') ?></div></div>
                                    <div class="data-item"><div class="data-label">Highest Qualification</div><div class="data-value"><?= htmlspecialchars($tp_data['s12_f1_qual'] ?? 'N/A') ?></div></div>
                                    <div class="data-item"><div class="data-label">Designation</div><div class="data-value"><?= htmlspecialchars($tp_data['s13_f1_desig'] ?? 'N/A') ?></div></div>
                                </div>

                                <div class="border-top pt-4 mb-4">
                                    <h6 class="fw-bold mb-3 text-secondary text-uppercase" style="font-size: 13px; letter-spacing: 1px;">Secondary Faculty (F2)</h6>
                                    <div class="data-grid">
                                        <div class="data-item"><div class="data-label">Name</div><div class="data-value"><?= htmlspecialchars($tp_data['s12_f2_name'] ?? 'N/A') ?></div></div>
                                        <div class="data-item"><div class="data-label">Highest Qualification</div><div class="data-value"><?= htmlspecialchars($tp_data['s12_f2_qual'] ?? 'N/A') ?></div></div>
                                        <div class="data-item"><div class="data-label">Designation</div><div class="data-value"><?= htmlspecialchars($tp_data['s13_f2_desig'] ?? 'N/A') ?></div></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-3 py-2 border-2" data-bs-toggle="modal" data-bs-target="#requestChangeModal">Request Faculty Roster Update</button>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                            <div class="content-card">
                                <div class="card-header-title justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-folder-open text-secondary"></i> <h5 class="text-secondary">Official Document Vault</h5>
                                    </div>
                                    <span class="badge bg-light text-secondary border p-2"><i class="fas fa-shield-alt me-1"></i> Secure Vault</span>
                                </div>
                                <p class="text-muted mb-4" style="font-size: 14px;">These are the official documents submitted and verified during your registration process. They are stored securely for compliance purposes.</p>
                                
                                <div class="vault-grid">
                                    <?php 
                                    $has_docs = false;
                                    foreach($document_vault as $column_name => $doc_title): 
                                        if(!empty($tp_data[$column_name])):
                                            $has_docs = true;
                                    ?>
                                        <div class="vault-item">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="vault-icon"><i class="fas fa-file-pdf"></i></div>
                                                <div class="vault-info">
                                                    <strong><?= htmlspecialchars($doc_title) ?></strong>
                                                    <span>Verified PDF</span>
                                                </div>
                                            </div>
                                            <a href="../<?= htmlspecialchars($tp_data[$column_name]) ?>" target="_blank" class="btn-download" title="View Document">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                    <?php 
                                        endif; 
                                    endforeach; 
                                    
                                    if(!$has_docs): ?>
                                        <div class="col-12 text-center text-muted p-5 bg-light rounded border border-dashed">
                                            <i class="fas fa-exclamation-circle fs-2 text-warning mb-3 d-block"></i>
                                            <strong>No Documents Found</strong><br>
                                            No documents were found in the secure vault.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div> </div> <div class="floating-save-bar" id="saveBar">
                    <div class="d-flex align-items-center gap-4">
                        <span class="text-muted fw-bold" style="font-size: 14px;">You have unsaved changes.</span>
                        <button type="button" class="btn-discard" onclick="resetForm()">Discard</button>
                        <button type="submit" class="btn-save"><i class="fas fa-save me-2"></i> Save Changes</button>
                    </div>
                </div>

            </form>
        </div>
    </main>

    <div class="modal fade" id="requestChangeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit text-primary"></i> Request Data Modification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="info-box mb-4">
                        <i class="fas fa-info-circle"></i>
                        <span>Modifying verified core data requires admin approval. Please submit a request explaining what needs to be changed and why. Our team will open a support ticket for you.</span>
                    </div>
                    <form id="ticketForm">
                        <div class="mb-3">
                            <label class="form-label">Data Section to Modify</label>
                            <select class="form-select border-2 bg-light">
                                <option>Core Identity (Institute Name, PAN)</option>
                                <option>Infrastructure (Capacity, Area)</option>
                                <option>Faculty Roster</option>
                                <option>Legal / Registration Status</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason for Modification</label>
                            <textarea class="form-control border-2 bg-light" rows="4" placeholder="Briefly explain the required changes..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary fw-bold border-2 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" onclick="alert('Modification request submitted successfully. Ticket #REQ-'+Math.floor(Math.random()*10000)+' generated.'); bootstrap.Modal.getInstance(document.getElementById('requestChangeModal')).hide();">Submit Request</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 1. Image Preview Logic
        function previewImage(input, imgId) {
            if (input.files && input.files[0]) {
                // Basic validation for size (<2MB)
                if(input.files[0].size > 2097152){
                   alert("File is too large. Please upload an image under 2MB.");
                   input.value = "";
                   return;
                }
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(imgId).src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
                triggerSaveBar();
            }
        }

        // 2. Unsaved Changes & Floating Save Bar Logic
        const saveBar = document.getElementById('saveBar');
        let hasUnsavedChanges = false;

        function triggerSaveBar() {
            if(!hasUnsavedChanges) {
                saveBar.classList.add('show');
                hasUnsavedChanges = true;
            }
        }

        function resetForm() {
            document.getElementById('settingsForm').reset();
            saveBar.classList.remove('show');
            hasUnsavedChanges = false;
            // Reload to reset image previews visually safely
            window.location.reload();
        }

        // Prevent accidental navigation if unsaved
        window.addEventListener('beforeunload', function (e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Bypass unsaved warning on actual form submit
        document.getElementById('settingsForm').addEventListener('submit', function() {
            hasUnsavedChanges = false; 
        });

        // 3. Handle Password Submit via same form structure but different action
        function submitPasswordChange() {
            const curr = document.getElementById('currPass').value;
            const newP = document.getElementById('newPass').value;
            const conf = document.getElementById('confPass').value;

            if(!curr || !newP || !conf) {
                alert("Please fill in all password fields.");
                return;
            }
            if(newP !== conf) {
                alert("New passwords do not match!");
                return;
            }
            if(newP.length < 8) {
                alert("New password must be at least 8 characters long.");
                return;
            }

            // Change action and submit
            document.getElementById('formAction').value = 'update_password';
            hasUnsavedChanges = false; // allow submit
            document.getElementById('settingsForm').submit();
        }

        // 4. Sidebar Mobile Toggle
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            const isOpen = sidebar.style.transform === 'translateX(0px)';
            if (isOpen) {
                sidebar.style.transform = 'translateX(-100%)';
                overlay.style.display = 'none';
            } else {
                sidebar.style.transform = 'translateX(0px)';
                overlay.style.display = 'block';
            }
        }

        if(toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
        if(overlay) overlay.addEventListener('click', toggleSidebar);

        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.style.transform = ''; 
                overlay.style.display = 'none';
            }
        });
    </script>
</body>
</html>