<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp');

$tp_id = $_SESSION['user_id'];

// Fetch only the students uploaded by THIS logged-in TP
$sql = "SELECT s.student_name, s.email, s.phone, s.enrollment_date, 
               c.course_name 
        FROM students s
        JOIN courses c ON s.course_id = c.id
        WHERE s.tp_id = ?
        ORDER BY s.enrollment_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tp_id);
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Students - TP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="tp_dashboard.php">NIELIT TPS Portal</a>
            <div class="d-flex text-white align-items-center">
                <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>My Uploaded Students</h2>
            <div>
                <a href="tp_upload_students.php" class="btn btn-success me-2">+ Upload More</a>
                <a href="tp_dashboard.php" class="btn btn-secondary">Back</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Enrolled Course</th>
                                <th>Enrollment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $students->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['course_name']) ?></span></td>
                                <td><?= date('d M Y', strtotime($row['enrollment_date'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($students->num_rows == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted mb-2">You haven't uploaded any students yet.</p>
                                        <a href="tp_upload_students.php" class="btn btn-sm btn-outline-primary">Go to Upload Page</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
