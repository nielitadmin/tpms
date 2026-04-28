<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('admin');

// Optional: Filter by Course
$course_filter = isset($_GET['course_id']) ? (int)$_GET['course_id'] : '';
$where_clause = $course_filter ? "WHERE s.course_id = $course_filter" : "";

// Fetch students with related TP and Course info
$sql = "SELECT s.student_name, s.email, s.phone, s.enrollment_date, 
               u.center_id, u.name as tp_name, 
               c.course_name 
        FROM students s
        JOIN users u ON s.tp_id = u.id
        JOIN courses c ON s.course_id = c.id
        $where_clause
        ORDER BY s.enrollment_date DESC";

$students = $conn->query($sql);
$courses = $conn->query("SELECT id, course_name FROM courses WHERE status = 'active'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reports - Admin Command</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; min-height: 100vh; }
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .navbar-brand { font-weight: 800; background: linear-gradient(90deg, #1e293b, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s; overflow: hidden; }
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }
        .filter-panel { background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(241,245,249,0.9)); border-left: 5px solid #3b82f6; }
        .form-select-3d { border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05); }
        .table-glass { --bs-table-bg: transparent; --bs-table-hover-bg: rgba(241, 245, 249, 0.6); margin-bottom: 0; }
        .table-glass thead th { background: #1e293b; color: white; border: none; padding: 15px; font-weight: 600; letter-spacing: 0.5px; }
        .table-glass tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .table-glass tbody tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4" href="admin_dashboard.php">
                <i class="fas fa-shield-alt text-dark me-2"></i> NIELIT Admin
            </a>
            <div class="d-flex align-items-center">
                <div class="me-4 text-secondary fw-semibold d-none d-md-block">
                    <i class="fas fa-user-astronaut text-dark me-1"></i> <?= htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?>
                </div>
                <a href="../logout.php" class="btn btn-3d btn-dark text-white"><i class="fas fa-power-off me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-users-cog text-primary me-2"></i> Master Student Reports</h2>
                <p class="text-muted mb-0">View and filter all student records uploaded by Training Partners across the state.</p>
            </div>
            <div>
                <a href="admin_dashboard.php" class="btn btn-3d btn-light text-primary px-4 py-2 border">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="card-3d filter-panel mb-4 p-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-bold text-secondary mb-2"><i class="fas fa-filter me-1"></i> Filter by Active Course</label>
                    <select name="course_id" class="form-select form-select-3d py-2">
                        <option value="">-- View All Courses --</option>
                        <?php while($c = $courses->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= ($course_filter == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-3d px-4 py-2 flex-grow-1"><i class="fas fa-search me-1"></i> Apply Filter</button>
                    <?php if($course_filter): ?>
                        <a href="admin_student_reports.php" class="btn btn-light btn-3d px-4 py-2 border text-danger"><i class="fas fa-times me-1"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card-3d p-2 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover table-glass align-middle">
                    <thead>
                        <tr>
                            <th style="border-top-left-radius: 12px;"><i class="fas fa-user me-2"></i>Student Name</th>
                            <th><i class="fas fa-address-book me-2"></i>Contact Info</th>
                            <th><i class="fas fa-book-open me-2"></i>Enrolled Course</th>
                            <th><i class="fas fa-university me-2"></i>Training Center</th>
                            <th style="border-top-right-radius: 12px;"><i class="fas fa-calendar-alt me-2"></i>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td><div class="fw-bold text-dark fs-6"><?= htmlspecialchars($row['student_name']) ?></div></td>
                            <td>
                                <div class="text-primary fw-medium"><i class="fas fa-envelope me-1 opacity-75"></i> <?= htmlspecialchars($row['email']) ?></div>
                                <div class="text-muted small mt-1"><i class="fas fa-phone-alt me-1 opacity-75"></i> <?= htmlspecialchars($row['phone']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info p-2 rounded px-3">
                                    <i class="fas fa-graduation-cap me-1"></i> <?= htmlspecialchars($row['course_name']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['tp_name']) ?></div>
                                <div class="badge bg-dark bg-opacity-10 text-dark mt-1">ID: <?= htmlspecialchars($row['center_id']) ?></div>
                            </td>
                            <td class="text-muted fw-medium"><?= date('d M Y', strtotime($row['enrollment_date'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($students->num_rows == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-users-slash fa-3x text-muted opacity-50 mb-3"></i>
                                    <h5 class="text-muted fw-bold">No students found.</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
