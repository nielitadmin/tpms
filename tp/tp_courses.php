<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp');

$tp_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// 1. Handle Adding a New Batch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_batch'])) {
    $course_id = (int)$_POST['course_id'];
    $batch_number = $conn->real_escape_string($_POST['batch_number']);
    $batch_timing = $conn->real_escape_string($_POST['batch_timing']);
    $batch_capacity = (int)$_POST['batch_capacity'];
    $deadline_date = $conn->real_escape_string($_POST['deadline_date']);

    $sql = "INSERT INTO tp_batches (tp_id, course_id, batch_number, batch_timing, batch_capacity, deadline_date, status) 
            VALUES ('$tp_id', '$course_id', '$batch_number', '$batch_timing', '$batch_capacity', '$deadline_date', 'active')";
            
    if ($conn->query($sql)) {
        $message = "New batch successfully registered!";
        $messageType = "success";
    } else {
        $message = "Error registering batch: " . $conn->error;
        $messageType = "danger";
    }
}

// 2. Handle Marking Batch as Completed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_batch'])) {
    $batch_id = (int)$_POST['batch_id'];
    $conn->query("UPDATE tp_batches SET status = 'completed' WHERE id = $batch_id AND tp_id = $tp_id");
    header("Location: tp_courses.php");
    exit();
}

// Fetch master courses for the dropdown modal
$master_courses = $conn->query("SELECT id, course_name FROM courses WHERE status = 'active'");

// Fetch this TP's specific batches, joined with course names and a count of enrolled students
$batch_sql = "SELECT b.*, c.course_name, 
              (SELECT COUNT(id) FROM students WHERE batch_id = b.id) as enrolled_students 
              FROM tp_batches b 
              JOIN courses c ON b.course_id = c.id 
              WHERE b.tp_id = $tp_id 
              ORDER BY b.created_at DESC";
$my_batches = $conn->query($batch_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Batches & Courses - TP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* styles copied from original tp_courses.php */
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; min-height: 100vh; }
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .navbar-brand { font-weight: 800; background: linear-gradient(90deg, #2563eb, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s; display: flex; flex-direction: column; }
        .batch-card:hover { transform: translateY(-5px); box-shadow: 15px 15px 25px rgba(166, 180, 200, 0.5), -15px -15px 25px rgba(255, 255, 255, 1); }
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; transition: 0.2s; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }
        .form-control-3d { border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05); padding: 10px 15px; }
        .progress-3d { height: 12px; border-radius: 10px; background: rgba(0,0,0,0.05); box-shadow: inset 2px 2px 5px rgba(0,0,0,0.1); overflow: visible; }
        .progress-bar-3d { border-radius: 10px; position: relative; }
    </style>
    </head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4" href="tp_dashboard.php">
                <i class="fas fa-layer-group text-primary me-2"></i> NIELIT TPS
            </a>
            <div class="d-flex align-items-center">
                <a href="tp_dashboard.php" class="btn btn-3d btn-light text-primary me-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
                <a href="../logout.php" class="btn btn-3d btn-light text-danger"><i class="fas fa-power-off"></i></a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <!-- Content unchanged -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
