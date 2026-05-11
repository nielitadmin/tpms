<?php
/**
 * ============================================================================
 * NIELIT TPMS - ACADEMIC HUB & BATCH MANAGEMENT
 * ============================================================================
 * File: tp_courses.php
 * Description: Strict linear workflow for Training Partners: 
 * 1. Request course accreditation from catalog.
 * 2. Wait for Admin approval.
 * 3. Create a training batch under the approved course.
 * 4. Bulk upload students via CSV directly into that batch.
 * ============================================================================
 */

// 1. SECURITY & SESSION INITIALIZATION
session_name('NIELIT_TPMS');
session_start();

// Strict Role Checking: TP Only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'tp') {
    header("Location: ../index.php");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';

$tp_email = $_SESSION['user_email'];
$message = '';
$msg_type = '';
$db_errors = [];
$timestamp_now = date('Y-m-d H:i:s');

// ============================================================================
// 2. CSV TEMPLATE GENERATOR
// ============================================================================
if (isset($_GET['download_template']) && $_GET['download_template'] == '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=NIELIT_Batch_Student_Upload.csv');
    $output = fopen('php://output', 'w');
    // Ensure this matches the 3 columns your DB now expects
    fputcsv($output, array('Full Name', 'Mobile Number', 'Email ID'));
    fputcsv($output, array('Amit Kumar', '9876543210', 'amit@example.com'));
    fputcsv($output, array('Priya Sharma', '9123456789', 'priya@example.com'));
    fclose($output);
    exit();
}

// ============================================================================
// 3. FETCH CORE USER DETAILS
// ============================================================================
$tp_user_id = 0;
$tp_details = ['institute_name' => 'Training Partner', 'status' => 'Pending'];

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

$stmt_center = $conn->prepare("SELECT institute_name, profile_photo, status, s3_name FROM centers WHERE contact_email = ?");
if ($stmt_center) {
    $stmt_center->bind_param("s", $tp_email);
    $stmt_center->execute();
    $res_center = $stmt_center->get_result();
    if ($res_center && $res_center->num_rows > 0) {
        $tp_details = $res_center->fetch_assoc();
    }
    $stmt_center->close();
}

// ============================================================================
// 4. POST REQUEST HANDLERS (The Linear Workflow Engine)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ------------------------------------------------------------------------
    // STEP 1: REQUEST COURSE ACCREDITATION (From Catalog)
    // ------------------------------------------------------------------------
    if ($_POST['action'] === 'request_accreditation') {
        if(strtolower($tp_details['status']) !== 'approved') {
            $message = "Action Denied: Your institute profile must be verified by Admin before requesting courses.";
            $msg_type = "danger";
        } else {
            $course_id = intval($_POST['course_id']);
            
            // Prevent duplicate requests
            $check_stmt = $conn->prepare("SELECT id FROM tp_batches WHERE tp_id = ? AND course_id = ? AND status IN ('active', 'pending')");
            $check_stmt->bind_param("ii", $tp_user_id, $course_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $message = "Conflict: You already hold an active or pending accreditation for this program.";
                $msg_type = "warning";
            } else {
                // Insert as a master accreditation record (batch_capacity = 0 denotes it's an accreditation, not a batch)
                $accr_no = 'REQ-' . strtoupper(substr(md5(uniqid()), 0, 6));
                $insert_stmt = $conn->prepare("INSERT INTO tp_batches (tp_id, course_id, batch_number, batch_capacity, status, created_at) VALUES (?, ?, ?, 0, 'pending', ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("iiss", $tp_user_id, $course_id, $accr_no, $timestamp_now);
                    if ($insert_stmt->execute()) {
                        $message = "Accreditation request submitted! Awaiting Admin approval.";
                        $msg_type = "success";
                    } else {
                        $message = "System Error: Could not process request.";
                        $msg_type = "danger";
                    }
                    $insert_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }

    // ------------------------------------------------------------------------
    // STEP 2: CREATE A NEW ONGOING BATCH (Inside an Approved Course)
    // ------------------------------------------------------------------------
    if ($_POST['action'] === 'create_batch') {
        $course_id = intval($_POST['course_id']);
        $batch_name = trim($_POST['batch_name']);
        $batch_capacity = intval($_POST['batch_capacity']);
        $batch_timing = trim($_POST['batch_timing']);
        
        if ($course_id > 0 && $batch_capacity > 0 && !empty($batch_name)) {
            // Verify they actually hold an APPROVED accreditation for this course
            $accr_check = $conn->prepare("SELECT id FROM tp_batches WHERE tp_id = ? AND course_id = ? AND status = 'active' AND batch_capacity = 0");
            $accr_check->bind_param("ii", $tp_user_id, $course_id);
            $accr_check->execute();
            
            if($accr_check->get_result()->num_rows > 0) {
                // Insert the actual ongoing batch
                $deadline = date('Y-m-d', strtotime('+6 months')); 
                $insert_batch = $conn->prepare("INSERT INTO tp_batches (tp_id, course_id, batch_number, batch_timing, batch_capacity, deadline_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active_batch', ?)");
                
                if ($insert_batch) {
                    $insert_batch->bind_param("iississ", $tp_user_id, $course_id, $batch_name, $batch_timing, $batch_capacity, $deadline, $timestamp_now);
                    if ($insert_batch->execute()) {
                        $message = "Batch '$batch_name' created successfully! You can now upload students into it.";
                        $msg_type = "success";
                    } else {
                        $message = "Failed to create batch.";
                        $msg_type = "danger";
                    }
                    $insert_batch->close();
                }
            } else {
                $message = "Security Error: You are not authorized to create batches for this course.";
                $msg_type = "danger";
            }
            $accr_check->close();
        }
    }

    // ------------------------------------------------------------------------
    // STEP 3: BULK UPLOAD STUDENTS VIA CSV (Auto-Calculate & Link to Batch)
    // ------------------------------------------------------------------------
    if ($_POST['action'] === 'upload_students_csv') {
        $target_batch_id = intval($_POST['target_batch_id']);
        $target_course_name = trim($_POST['target_course_name']);
        
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0 && $target_batch_id > 0) {
            $filename = $_FILES['csv_file']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
            
            if ($ext === 'csv') {
                $file = fopen($filename, "r");
                $header = fgetcsv($file); // Skip header row
                
                $success_count = 0;
                $failed_count = 0;
                
                // Using the updated table structure featuring 'batch_id' and 'email'
                $stmt_ins = $conn->prepare("INSERT INTO students (tp_email, full_name, mobile, email, course_name, batch_id, status, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, 'Active', ?)");
                
                if ($stmt_ins) {
                    while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
                        if(isset($data[0]) && isset($data[1])) {
                            $name = trim($data[0]);
                            $mobile = trim($data[1]);
                            $email = isset($data[2]) ? trim($data[2]) : null;
                            
                            // Validate 10-digit mobile and non-empty name
                            if (!empty($name) && preg_match('/^[0-9]{10}$/', $mobile)) {
                                $stmt_ins->bind_param("sssssis", $tp_email, $name, $mobile, $email, $target_course_name, $target_batch_id, $timestamp_now);
                                if($stmt_ins->execute()) {
                                    $success_count++;
                                } else {
                                    $failed_count++;
                                }
                            } else {
                                $failed_count++; // Invalid row format
                            }
                        }
                    }
                    $stmt_ins->close();
                }
                fclose($file);
                
                if ($success_count > 0) {
                    $message = "Upload Success: $success_count students enrolled and linked to Batch #$target_batch_id. ($failed_count invalid rows skipped).";
                    $msg_type = "success";
                } else {
                    $message = "Upload Failed: No valid data found. Ensure mobile numbers are 10 digits.";
                    $msg_type = "danger";
                }
            } else {
                $message = "Invalid format: Please upload a valid .csv Excel file.";
                $msg_type = "warning";
            }
        }
    }
}

// ============================================================================
// 5. DATA AGGREGATION & FETCHING FOR DASHBOARD
// ============================================================================

// A. Fetch Master Catalog
$master_catalog = [];
$res_cat = $conn->query("SELECT * FROM courses WHERE status = 'active' ORDER BY course_name ASC");
if ($res_cat) {
    while ($c = $res_cat->fetch_assoc()) {
        $master_catalog[] = $c;
    }
}

// B. Fetch TP's Accreditations (Requested Courses)
// Status 'active' means Admin approved it. 'pending' means awaiting admin.
$my_accreditations = [];
if ($tp_user_id > 0) {
    $acc_query = "
        SELECT c.id as course_id, c.course_name, c.duration, MAX(b.status) as accr_status, b.created_at
        FROM tp_batches b
        JOIN courses c ON b.course_id = c.id
        WHERE b.tp_id = ? AND b.batch_capacity = 0
        GROUP BY c.id, c.course_name, c.duration, b.created_at
        ORDER BY b.created_at DESC
    ";
    $stmt_acc = $conn->prepare($acc_query);
    if($stmt_acc) {
        $stmt_acc->bind_param("i", $tp_user_id);
        $stmt_acc->execute();
        $res_acc = $stmt_acc->get_result();
        while($row = $res_acc->fetch_assoc()) {
            $my_accreditations[$row['course_id']] = $row;
        }
        $stmt_acc->close();
    }
}

// C. Fetch ONGOING BATCHES & Auto-Calculate Students inside them
$ongoing_batches = [];
$total_students_across_batches = 0;

if ($tp_user_id > 0) {
    // We count students specifically linked to the batch_id
    $batch_query = "
        SELECT 
            b.id as batch_id, b.course_id, b.batch_number, b.batch_timing, b.batch_capacity, b.created_at,
            c.course_name,
            (SELECT COUNT(*) FROM students s WHERE s.tp_email = ? AND s.batch_id = b.id AND s.status != 'Dropped') as auto_student_count
        FROM tp_batches b
        JOIN courses c ON b.course_id = c.id
        WHERE b.tp_id = ? AND b.status = 'active_batch'
        ORDER BY b.created_at DESC
    ";
    
    $stmt_b = $conn->prepare($batch_query);
    if($stmt_b) {
        $stmt_b->bind_param("si", $tp_email, $tp_user_id);
        $stmt_b->execute();
        $res_b = $stmt_b->get_result();
        
        while ($row = $res_b->fetch_assoc()) {
            $ongoing_batches[] = $row;
            $total_students_across_batches += intval($row['auto_student_count']);
        }
        $stmt_b->close();
    }
}

// D. Fetch all students for the Roster Modal (Grouped by JS later)
$all_my_students = [];
$stmt_stu = $conn->prepare("SELECT id, batch_id, full_name, mobile, email, enrollment_date FROM students WHERE tp_email = ? AND status != 'Dropped' ORDER BY id DESC");
if($stmt_stu) {
    $stmt_stu->bind_param("s", $tp_email);
    $stmt_stu->execute();
    $res_stu = $stmt_stu->get_result();
    while($row = $res_stu->fetch_assoc()) {
        $all_my_students[] = $row;
    }
    $stmt_stu->close();
}

$institute_initials = strtoupper(substr($tp_details['institute_name'] ?? 'TP', 0, 2));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Batches & Students - NIELIT TPMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ====================================================================
           GLOBAL VARIABLES & RESET
           ==================================================================== */
        :root {
            --sidebar-bg: #0B1120; 
            --sidebar-hover: #1E293B;
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --bg-body: #F4F7F9;
            --card-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --text-light: #F8FAFC;
            --primary: #2563EB; 
            --primary-hover: #1D4ED8;
            --primary-light: #EFF6FF;
            --secondary: #475569;
            --accent-success: #10B981;
            --accent-warning: #F59E0B;
            --accent-danger: #EF4444;
            --accent-purple: #8B5CF6;
            --border-color: #E2E8F0;
            --sidebar-width: 280px;
            --transition-speed: 0.3s;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05);
            --shadow-glow: 0 0 20px rgba(37, 99, 235, 0.3);
            --shadow-glow-success: 0 0 20px rgba(16, 185, 129, 0.3);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-dark); overflow-x: hidden; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }

        /* ====================================================================
           SIDEBAR & TOPBAR
           ==================================================================== */
        #sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); position: fixed; top: 0; left: 0; height: 100vh; z-index: 1050; display: flex; flex-direction: column; border-right: 1px solid var(--sidebar-border); transition: transform var(--transition-speed); }
        .sidebar-brand { padding: 30px 25px; border-bottom: 1px solid var(--sidebar-border); text-align: center; display: flex; flex-direction: column; align-items: center; }
        .sidebar-brand-icon { width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary), var(--accent-purple)); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px; box-shadow: var(--shadow-glow); }
        .sidebar-brand h4 { font-weight: 800; font-size: 20px; margin: 0; color: var(--text-light); }
        .sidebar-brand span { font-size: 11px; color: #94A3B8; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 5px; }
        
        .sidebar-menu { padding: 25px 15px; flex-grow: 1; overflow-y: auto; }
        .sidebar-menu-category { font-size: 10px; color: #475569; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin: 15px 0 5px 15px; }
        .sidebar-menu a { padding: 12px 18px; margin-bottom: 5px; display: flex; align-items: center; color: #94A3B8; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 8px; transition: var(--transition-speed); }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--sidebar-hover); color: white; transform: translateX(4px); }
        .sidebar-menu a i { width: 30px; font-size: 16px; }
        .sidebar-menu a.active i { color: #60A5FA; }
        
        .sidebar-footer { padding: 20px 15px; border-top: 1px solid var(--sidebar-border);}
        .btn-logout { width: 100%; padding: 12px; background: rgba(239, 68, 68, 0.05); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.1); border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: var(--transition-speed);}
        .btn-logout:hover { background: var(--accent-danger); color: white; }

        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1040; display: none; backdrop-filter: blur(3px); }

        #main-content { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; transition: margin var(--transition-speed); }
        .top-navbar { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 999; box-shadow: var(--shadow-sm); }
        .mobile-toggle-btn { display: none; background: none; border: none; font-size: 24px; color: var(--text-dark); cursor: pointer; }
        
        .nav-profile-area { display: flex; align-items: center; gap: 20px; margin-left: auto;}
        .nav-profile-info { text-align: right; display: flex; flex-direction: column; justify-content: center;}
        .nav-profile-info span { font-size: 14px; font-weight: 700; color: var(--text-dark); line-height: 1.2;}
        .nav-profile-info small { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;}
        .avatar-circle { width: 45px; height: 45px; background: linear-gradient(135deg, var(--primary), var(--accent-purple)); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 18px; overflow: hidden; border: 2px solid white; box-shadow: var(--shadow-md);}
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

        .page-container { padding: 40px; flex-grow: 1; max-width: 1600px; margin: 0 auto; width: 100%; }

        /* ====================================================================
           HERO BANNER & PIPELINE
           ==================================================================== */
        .hero-banner { background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: 16px; padding: 40px 50px; color: white; margin-bottom: 35px; box-shadow: var(--shadow-lg); position: relative; overflow: hidden; }
        .hero-banner::after { content: ''; position: absolute; bottom: -100px; right: -50px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(37, 99, 235, 0.2) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; }
        .hero-content { position: relative; z-index: 2; max-width: 800px;}
        .hero-content h1 { font-size: 28px; font-weight: 800; margin-bottom: 10px;}
        .hero-content p { color: #CBD5E1; font-size: 15px; font-weight: 500; margin-bottom: 25px;}

        /* Visual Pipeline */
        .pipeline-container { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; position: relative; z-index: 2; background: rgba(255,255,255,0.05); padding: 15px 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); width: max-content;}
        .pipe-step { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 700;}
        .pipe-icon { width: 30px; height: 30px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;}
        .pipe-arrow { color: #64748B; font-size: 12px;}
        
        /* ====================================================================
           TABS & BATCH CARDS
           ==================================================================== */
        .content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-sm); margin-bottom: 30px; overflow: hidden;}
        
        .custom-nav-pills { display: flex; gap: 10px; border-bottom: 1px solid #E2E8F0; padding: 20px 30px; background: #F8FAFC;}
        .custom-nav-pills .nav-link { color: var(--text-muted); font-weight: 700; padding: 10px 20px; border-radius: 50px; font-size: 14px; border: 1px solid transparent; transition: var(--transition-speed);}
        .custom-nav-pills .nav-link.active { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(37,99,235,0.2);}
        
        /* Batch Grid */
        .batch-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 25px; padding: 30px;}
        .batch-card { border: 1px solid #E2E8F0; border-radius: 12px; background: white; transition: all 0.3s; display: flex; flex-direction: column; position: relative;}
        .batch-card:hover { border-color: var(--primary); box-shadow: var(--shadow-md); transform: translateY(-3px);}
        
        .batch-header { padding: 20px; border-bottom: 1px dashed #E2E8F0; background: #FAFAFA; border-radius: 12px 12px 0 0;}
        .batch-title { font-size: 16px; font-weight: 800; color: var(--text-dark); margin-bottom: 5px; line-height: 1.3;}
        .batch-id { font-size: 11px; background: #E0E7FF; color: #4338CA; padding: 3px 8px; border-radius: 4px; font-weight: 800; display: inline-block; border: 1px solid #C7D2FE;}
        
        .batch-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column;}
        
        /* Auto Calculate Highlight */
        .auto-calc-box { background: #F8FAFC; border: 1px solid #E2E8F0; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px;}
        .auto-calc-number { font-size: 32px; font-weight: 900; color: var(--primary); line-height: 1;}
        .auto-calc-text { font-size: 11px; color: var(--secondary); font-weight: 700; text-transform: uppercase; margin-top: 5px; display: flex; align-items: center; justify-content: center; gap: 5px;}
        
        .progress-bar-bg { height: 8px; background: #E2E8F0; border-radius: 10px; overflow: hidden; margin-bottom: 8px;}
        .progress-bar-fill { height: 100%; border-radius: 10px; transition: width 1s ease;}
        
        /* Card Action Buttons */
        .card-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: auto; padding-top: 15px;}
        .btn-action-card { background: white; color: var(--primary); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; font-weight: 700; font-size: 13px; text-align: center; display: block; width: 100%; transition: all 0.3s; cursor: pointer; text-decoration: none;}
        .btn-action-card:hover { background: #F8FAFC; border-color: #CBD5E1; color: var(--primary-hover);}
        .btn-action-upload { background: var(--accent-success); color: white; border: none; box-shadow: var(--shadow-sm);}
        .btn-action-upload:hover { background: #059669; color: white;}

        /* Lists & Tables */
        .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; text-transform: uppercase; letter-spacing: 0.5px;}
        .table-responsive { padding: 0 30px 30px 30px; }
        .table th { font-size: 12px; text-transform: uppercase; color: var(--secondary); font-weight: 800; border-bottom: 2px solid var(--border-color); padding: 15px;}
        .table td { padding: 15px; font-size: 14px; font-weight: 600; color: var(--text-dark); vertical-align: middle; border-bottom: 1px solid #F1F5F9;}

        /* ====================================================================
           MODALS & DRAG DROP ZONE
           ==================================================================== */
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { border-bottom: 1px solid var(--border-color); padding: 25px 30px; background: #F8FAFC; border-radius: 16px 16px 0 0;}
        .modal-title { font-weight: 800; font-size: 20px; color: var(--text-dark); display: flex; align-items: center; gap: 10px;}
        .modal-body { padding: 30px; }
        .modal-footer { border-top: 1px solid var(--border-color); padding: 20px 30px; background: #F8FAFC; border-radius: 0 0 16px 16px;}
        
        .form-label { font-size: 13px; font-weight: 800; color: var(--secondary); margin-bottom: 8px; text-transform: uppercase;}
        .form-control, .form-select { border-radius: 8px; font-weight: 600; padding: 12px 16px; border: 1px solid var(--border-color); font-size: 15px;}
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); outline: none;}

        .upload-drop-zone { border: 2px dashed #CBD5E1; border-radius: 12px; padding: 40px 20px; text-align: center; background: #F8FAFC; cursor: pointer; transition: all 0.3s; position: relative;}
        .upload-drop-zone:hover, .upload-drop-zone.dragover { border-color: var(--accent-success); background: #ECFDF5; }
        .upload-drop-zone i { font-size: 40px; color: var(--accent-success); margin-bottom: 15px;}
        .upload-drop-zone input[type="file"] { position: absolute; top:0; left:0; width:100%; height:100%; opacity: 0; cursor: pointer; z-index: 10;}
        
        /* Roster List inside Modal */
        .roster-list { max-height: 400px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px;}
        .roster-item { display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #F1F5F9;}
        .roster-item:last-child { border-bottom: none;}
        .roster-avatar { width: 35px; height: 35px; background: #E0E7FF; color: #4338CA; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; flex-shrink: 0;}
        
        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); }
            #main-content { margin-left: 0; }
            .mobile-toggle-btn { display: block; }
            .page-container { padding: 20px; }
            .hero-banner { padding: 30px; }
            .pipeline-container { display: none; } /* Hide pipeline on small screens */
            .custom-nav-pills { overflow-x: auto; white-space: nowrap;}
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
            <a href="edit_profile.php"><i class="fas fa-id-card-clip"></i> Institute Settings</a>
            
            <div class="sidebar-menu-category mt-4">Academic Operations</div>
            <a href="tp_courses.php" class="active"><i class="fas fa-layer-group"></i> Manage Batches</a>
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
                    <span class="badge bg-light text-dark border" style="font-weight: 700; padding: 8px 12px; font-size: 12px;">
                        <i class="fas fa-shield-alt text-success me-1"></i> Workflow Secure
                    </span>
                </div>
            </div>
            <div class="nav-profile-area">
                <div class="nav-profile-info">
                    <span><?= htmlspecialchars($tp_details['institute_name'] ?? 'Training Partner') ?></span>
                    <small>Batch Management</small>
                </div>
                <div class="avatar-circle">
                    <?php if(!empty($tp_details['profile_photo'])): ?>
                        <img src="../<?= htmlspecialchars($tp_details['profile_photo']) ?>" alt="Profile">
                    <?php else: ?>
                        <?= $institute_initials ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="page-container">
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm mb-4" role="alert" style="border-radius: 12px; font-weight: 600; border: none; border-left: 5px solid <?= $msg_type == 'success' ? 'var(--accent-success)' : 'var(--accent-warning)' ?>;">
                    <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-warning' ?> me-2 fs-5"></i> 
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="hero-banner">
                <div class="hero-content">
                    <h1>Academic Pipeline & Batches</h1>
                    <p>Follow the strict operational workflow: Request accreditation, wait for administrative approval, create your batches, and finally bulk upload your enrolled students.</p>
                    
                    <div class="pipeline-container mt-4">
                        <div class="pipe-step">
                            <div class="pipe-icon"><i class="fas fa-search"></i></div> Request Course
                        </div>
                        <i class="fas fa-chevron-right pipe-arrow"></i>
                        <div class="pipe-step">
                            <div class="pipe-icon"><i class="fas fa-user-shield"></i></div> Admin Approves
                        </div>
                        <i class="fas fa-chevron-right pipe-arrow"></i>
                        <div class="pipe-step">
                            <div class="pipe-icon"><i class="fas fa-layer-group"></i></div> Create Batch
                        </div>
                        <i class="fas fa-chevron-right pipe-arrow"></i>
                        <div class="pipe-step" style="color: var(--accent-success);">
                            <div class="pipe-icon" style="background: var(--accent-success);"><i class="fas fa-file-csv text-white"></i></div> Upload Students
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card p-0">
                <div class="custom-nav-pills" id="courseTabs" role="tablist">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-catalog" type="button">
                        Step 1: Master Catalog
                    </button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-accreditations" type="button">
                        Step 2: My Accreditations
                    </button>
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-batches" type="button">
                        Step 3 & 4: Manage Batches
                    </button>
                </div>

                <div class="tab-content" id="courseTabsContent">

                    <div class="tab-pane fade show active" id="tab-catalog" role="tabpanel">
                        <div class="px-4 py-2 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                            <div>
                                <h5 class="fw-bold m-0" style="font-size: 18px;">NIELIT Master Catalog</h5>
                                <p class="text-muted m-0" style="font-size: 13px;">Browse available courses and submit an accreditation request.</p>
                            </div>
                            <div class="position-relative" style="width: 300px;">
                                <i class="fas fa-search position-absolute text-muted" style="top: 50%; left: 15px; transform: translateY(-50%);"></i>
                                <input type="text" id="catalogSearch" class="form-control" style="padding-left: 40px; background: #F8FAFC; border-radius: 50px;" placeholder="Search catalog...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table" id="catalogTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;">Official Program Name</th>
                                        <th style="width: 15%;">Duration</th>
                                        <th style="width: 30%;">Candidate Eligibility</th>
                                        <th style="width: 15%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($master_catalog)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No courses available.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($master_catalog as $mc): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark" style="font-size: 15px;"><?= htmlspecialchars($mc['course_name']) ?></div>
                                                    <div style="font-size: 11px; color: var(--primary); font-weight: 700;">Code: #<?= str_pad($mc['id'], 4, '0', STR_PAD_LEFT) ?></div>
                                                </td>
                                                <td><i class="far fa-clock text-muted me-1"></i> <?= htmlspecialchars($mc['duration']) ?></td>
                                                <td style="font-size: 13px; color: var(--secondary);"><?= htmlspecialchars($mc['eligibility']) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary fw-bold w-100" data-bs-toggle="modal" data-bs-target="#requestAccreditationModal" onclick="document.getElementById('accrCourseSelect').value='<?= $mc['id'] ?>';">Request Access</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-accreditations" role="tabpanel">
                        <div class="p-4 pt-2">
                            <h5 class="fw-bold m-0" style="font-size: 18px;">My Accreditation Status</h5>
                            <p class="text-muted mb-4" style="font-size: 13px;">View the status of your requests. Only 'Approved' programs allow batch creation.</p>
                            
                            <?php if(empty($my_accreditations)): ?>
                                <div class="text-center py-5 border border-dashed rounded bg-light">
                                    <i class="fas fa-certificate fs-1 text-muted mb-3 d-block opacity-50"></i>
                                    <div class="fw-bold text-dark">No Accreditations Requested</div>
                                </div>
                            <?php else: ?>
                                <div class="row g-4">
                                    <?php foreach($my_accreditations as $acc): 
                                        $is_approved = ($acc['accr_status'] == 'active' || $acc['accr_status'] == 'active_batch');
                                    ?>
                                        <div class="col-md-6 col-xl-4">
                                            <div class="p-4 border rounded" style="background: <?= $is_approved ? 'white' : '#F8FAFC' ?>; box-shadow: <?= $is_approved ? 'var(--shadow-sm)' : 'none' ?>;">
                                                <div class="d-flex align-items-center gap-3 mb-3">
                                                    <div style="width: 45px; height: 45px; background: <?= $is_approved ? 'var(--primary-light)' : '#E2E8F0' ?>; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: <?= $is_approved ? 'var(--primary)' : 'var(--secondary)' ?>;">
                                                        <i class="fas fa-award"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 15px; line-height: 1.2;"><?= htmlspecialchars($acc['course_name']) ?></div>
                                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;"><i class="far fa-clock"></i> <?= htmlspecialchars($acc['duration']) ?></div>
                                                    </div>
                                                </div>
                                                
                                                <?php if(!$is_approved): ?>
                                                    <div class="text-center p-2 rounded" style="background: #FFFBEB; border: 1px solid #FDE68A; color: #D97706; font-size: 12px; font-weight: 700;">
                                                        <i class="fas fa-hourglass-half me-1"></i> Pending Admin Approval
                                                    </div>
                                                <?php else: ?>
                                                    <div class="d-flex gap-2">
                                                        <div class="w-100 text-center p-2 rounded" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #059669; font-size: 12px; font-weight: 700;">
                                                            <i class="fas fa-check-circle me-1"></i> Approved
                                                        </div>
                                                        <button class="btn btn-primary btn-sm fw-bold w-100" data-bs-toggle="modal" data-bs-target="#createBatchModal" onclick="document.getElementById('createBatchCourseSelect').value='<?= $acc['course_id'] ?>';">Create Batch</button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-batches" role="tabpanel">
                        <?php if (empty($ongoing_batches)): ?>
                            <div class="text-center py-5">
                                <div style="width: 80px; height: 80px; background: #F1F5F9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                                    <i class="fas fa-layer-group text-muted fs-3"></i>
                                </div>
                                <h6 class="fw-bold text-dark">No Ongoing Batches Found</h6>
                                <p class="text-muted small mb-4">You have not created any active batches yet. Once an accreditation is approved, create a batch here.</p>
                                <button type="button" class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#createBatchModal">Create Batch</button>
                            </div>
                        <?php else: ?>
                            <div class="p-4 pt-0 d-flex justify-content-between align-items-center border-bottom mb-4">
                                <h5 class="fw-bold m-0" style="font-size: 18px;">Active Training Batches</h5>
                                <button type="button" class="btn btn-dark btn-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#createBatchModal"><i class="fas fa-plus"></i> New Batch</button>
                            </div>

                            <div class="batch-grid pt-0">
                                <?php foreach ($ongoing_batches as $batch): 
                                    $capacity = intval($batch['batch_capacity']);
                                    $enrolled = intval($batch['auto_student_count']);
                                    $percent = ($capacity > 0) ? round(($enrolled / $capacity) * 100) : 0;
                                    $p_color = ($percent >= 100) ? 'var(--accent-danger)' : (($percent >= 80) ? 'var(--accent-warning)' : 'var(--primary)');
                                ?>
                                    <div class="batch-card">
                                        <div class="batch-header">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="batch-id">Batch: <?= htmlspecialchars($batch['batch_number']) ?></div>
                                                <span class="badge bg-white text-dark border"><i class="far fa-clock"></i> <?= htmlspecialchars($batch['batch_timing']) ?></span>
                                            </div>
                                            <div class="batch-title"><?= htmlspecialchars($batch['course_name']) ?></div>
                                        </div>
                                        <div class="batch-body">
                                            
                                            <div class="auto-calc-box">
                                                <div class="auto-calc-number" style="color: <?= $p_color ?>;"><?= number_format($enrolled) ?></div>
                                                <div class="auto-calc-text">
                                                    <i class="fas fa-users"></i> Registered Students
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between font-weight-bold" style="font-size: 11px; color: var(--secondary); text-transform: uppercase;">
                                                <span>Capacity Limit</span>
                                                <span style="color: <?= $p_color ?>; font-weight: 800;"><?= $percent ?>% Filled</span>
                                            </div>
                                            <div class="prog-container mb-4">
                                                <div class="prog-bar" style="width: <?= min(100, $percent) ?>%; background: <?= $p_color ?>;"></div>
                                            </div>

                                            <div class="card-actions-grid">
                                                <button class="btn-action-card" onclick="viewRoster(<?= $batch['batch_id'] ?>, '<?= htmlspecialchars(addslashes($batch['course_name'])) ?>', '<?= htmlspecialchars($batch['batch_number']) ?>')">
                                                    <i class="fas fa-list-ol mb-1 fs-5"></i><br>View Roster
                                                </button>

                                                <?php if($percent >= 100): ?>
                                                    <div class="btn-action-card" style="background: #FEF2F2; border-color: #FECACA; color: #DC2626; cursor: not-allowed;">
                                                        <i class="fas fa-lock mb-1 fs-5"></i><br>Batch Full
                                                    </div>
                                                <?php else: ?>
                                                    <button class="btn-action-card btn-action-upload" onclick="openUploadModal(<?= $batch['batch_id'] ?>, '<?= htmlspecialchars(addslashes($batch['course_name'])) ?>')">
                                                        <i class="fas fa-file-csv mb-1 fs-5"></i><br>Upload Students
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="requestAccreditationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-certificate text-primary"></i> Request Accreditation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="accreditationForm">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="action" value="request_accreditation">
                        <p class="text-muted mb-4" style="font-size: 13px;">Select a course from the catalog. Once approved by NIELIT admin, you can create batches for it.</p>
                        
                        <label class="form-label">Select NIELIT Program</label>
                        <select name="course_id" id="accrCourseSelect" class="form-select bg-white mb-3 shadow-sm border-0" required>
                            <option value="" selected disabled>-- Browse Catalog --</option>
                            <?php foreach($master_catalog as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer bg-white">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4" id="submitAccreditationBtn">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createBatchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-layer-group text-primary"></i> Create Training Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="createBatchForm">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="action" value="create_batch">
                        
                        <div class="mb-4">
                            <label class="form-label">Select Approved Course <span class="text-danger">*</span></label>
                            <select name="course_id" id="createBatchCourseSelect" class="form-select bg-white border-0 shadow-sm" required>
                                <option value="" selected disabled>-- Your Approved Courses --</option>
                                <?php 
                                foreach($my_accreditations as $acc): 
                                    if($acc['accr_status'] == 'pending') continue;
                                ?>
                                    <option value="<?= $acc['course_id'] ?>"><?= htmlspecialchars($acc['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Batch Identifier Name <span class="text-danger">*</span></label>
                            <input type="text" name="batch_name" class="form-control bg-white border-0 shadow-sm" placeholder="e.g. Morning Batch 2026" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Daily Timing</label>
                                <input type="text" name="batch_timing" class="form-control bg-white border-0 shadow-sm" placeholder="10:00 AM - 1:00 PM" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Seat Capacity Limit</label>
                                <input type="number" name="batch_capacity" class="form-control bg-white border-0 shadow-sm fw-bold text-primary" min="10" max="500" value="50" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="submitBatchBtn">Create Batch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0" style="box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25);">
                <div class="modal-header border-bottom-0 pb-0 bg-white">
                    <h5 class="modal-title text-success">
                        <i class="fas fa-file-csv me-2"></i> Upload Students to Batch
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" id="csvUploadForm">
                    <div class="modal-body bg-white pt-2">
                        <input type="hidden" name="action" value="upload_students_csv">
                        <input type="hidden" name="target_batch_id" id="uploadBatchId">
                        <input type="hidden" name="target_course_name" id="uploadCourseName">
                        
                        <div class="mb-4 p-3 rounded" style="background: #F0FDF4; border: 1px solid #A7F3D0;">
                            <div style="font-size: 11px; color: #047857; font-weight: 800; text-transform: uppercase; margin-bottom: 5px;">Uploading to Batch</div>
                            <div class="fw-bold text-dark fs-5" id="displayUploadBatchName">Course Name Here</div>
                        </div>

                        <div class="mb-4 d-flex justify-content-between align-items-center border-bottom pb-3">
                            <span class="text-muted fw-bold" style="font-size: 13px;">Format required: Name, Mobile, Email</span>
                            <a href="?download_template=1" class="btn btn-dark btn-sm fw-bold">
                                <i class="fas fa-download me-1"></i> Get CSV Template
                            </a>
                        </div>

                        <div class="upload-drop-zone" id="csvDropZone">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <div class="upload-drop-text">Drag & Drop your .CSV file here</div>
                            <div class="upload-drop-subtext">or click to browse from your computer</div>
                            <input type="file" name="csv_file" accept=".csv" required id="csvFileInput">
                            
                            <div id="filePreview" style="display: none; margin-top: 15px; padding: 10px; background: white; border-radius: 8px; border: 1px solid var(--accent-success); color: var(--accent-success); font-weight: 700;">
                                <i class="fas fa-check-circle fs-4 mb-2"></i><br>
                                <span id="fileNameDisplay">filename.csv</span> ready for processing.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light mt-2 border-top-0">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm" id="submitCsvBtn">Process File & Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rosterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title text-dark"><i class="fas fa-list-ol text-primary me-2"></i> Batch Roster</h5>
                        <div id="rosterBatchName" class="text-muted" style="font-size: 12px; font-weight: 600; margin-top: 5px;"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="roster-list" id="rosterContainer">
                        </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-dark fw-bold px-4" data-bs-dismiss="modal">Close Roster</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allStudentsData = <?= json_encode($all_my_students) ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // 1. Sidebar Toggle
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

            // 2. Button Loading States
            const forms = [
                {id: 'accreditationForm', btn: 'submitAccreditationBtn'},
                {id: 'createBatchForm', btn: 'submitBatchBtn'},
                {id: 'csvUploadForm', btn: 'submitCsvBtn'}
            ];
            
            forms.forEach(f => {
                const formEl = document.getElementById(f.id);
                if(formEl) {
                    formEl.addEventListener('submit', function() {
                        const btn = document.getElementById(f.btn);
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
                        btn.classList.add('disabled');
                    });
                }
            });

            // 3. Catalog Search
            const searchInput = document.getElementById('catalogSearch');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#catalogTable tbody tr');
                    rows.forEach(row => {
                        if(row.cells.length === 1) return; 
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }

            // 4. CSV Drag & Drop UI
            const fileInput = document.getElementById('csvFileInput');
            const dropZone = document.getElementById('csvDropZone');
            const filePreview = document.getElementById('filePreview');
            const fileNameDisplay = document.getElementById('fileNameDisplay');

            if(fileInput && dropZone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); dropZone.classList.add('dragover'); }, false);
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); dropZone.classList.remove('dragover'); }, false);
                });

                fileInput.addEventListener('change', function() {
                    if(this.files && this.files.length > 0) {
                        const file = this.files[0];
                        if(!file.name.toLowerCase().endsWith('.csv')) {
                            alert('Please upload a valid .csv format.');
                            this.value = ''; 
                            return;
                        }
                        fileNameDisplay.textContent = file.name;
                        filePreview.style.display = 'block';
                        dropZone.querySelector('i').style.display = 'none';
                        dropZone.querySelector('.upload-drop-text').style.display = 'none';
                        dropZone.querySelector('.upload-drop-subtext').style.display = 'none';
                    }
                });
            }
        });

        // Open Upload Modal
        function openUploadModal(batchId, courseName) {
            document.getElementById('uploadBatchId').value = batchId;
            document.getElementById('uploadCourseName').value = courseName;
            document.getElementById('displayUploadBatchName').textContent = courseName + " (ID: " + batchId + ")";
            var myModal = new bootstrap.Modal(document.getElementById('bulkUploadModal'));
            myModal.show();
        }

        // View Student Roster Logic
        function viewRoster(batchId, courseName, batchNumber) {
            document.getElementById('rosterBatchName').textContent = courseName + ' | Batch: ' + batchNumber;
            const container = document.getElementById('rosterContainer');
            container.innerHTML = ''; // clear

            // Filter global student array by batch_id
            const studentsInBatch = allStudentsData.filter(s => parseInt(s.batch_id) === parseInt(batchId));

            if(studentsInBatch.length === 0) {
                container.innerHTML = '<div class="text-center p-5 text-muted"><i class="fas fa-user-slash fs-1 mb-3 opacity-50"></i><br>No students found in this batch.</div>';
            } else {
                studentsInBatch.forEach(s => {
                    const initials = s.full_name.substring(0, 2).toUpperCase();
                    const emailTxt = s.email ? s.email : 'No email provided';
                    
                    const item = document.createElement('div');
                    item.className = 'roster-item';
                    item.innerHTML = `
                        <div class="roster-avatar">${initials}</div>
                        <div style="flex-grow: 1;">
                            <div class="fw-bold text-dark" style="font-size: 14px;">${s.full_name}</div>
                            <div style="font-size: 12px; color: var(--text-muted);"><i class="fas fa-phone-alt me-1"></i> ${s.mobile} | <i class="fas fa-envelope mx-1"></i> ${emailTxt}</div>
                        </div>
                        <span class="badge bg-success" style="font-size:10px;">Active</span>
                    `;
                    container.appendChild(item);
                });
            }

            var rosterModal = new bootstrap.Modal(document.getElementById('rosterModal'));
            rosterModal.show();
        }
    </script>
</body>
</html>