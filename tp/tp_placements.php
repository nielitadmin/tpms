<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp');

$tp_id = $_SESSION['user_id'];
$message = '';

// Handle Adding a New Placement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_placement'])) {
    $student_name = $conn->real_escape_string($_POST['student_name']);
    $company_name = $conn->real_escape_string($_POST['company_name']);
    $designation = $conn->real_escape_string($_POST['designation']);
    $package = $conn->real_escape_string($_POST['package']);
    $placement_date = $conn->real_escape_string($_POST['placement_date']);

    $sql = "INSERT INTO placements (tp_id, student_name, company_name, designation, package, placement_date) 
            VALUES ('$tp_id', '$student_name', '$company_name', '$designation', '$package', '$placement_date')";
    
    if ($conn->query($sql)) {
        $message = "<div class='alert alert-success'>Placement record added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}

// Fetch this center's placements
$placements = $conn->query("SELECT * FROM placements WHERE tp_id = $tp_id ORDER BY placement_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Placements - TP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { background: #f8fafc; } </style>
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
            <h2>Student Placements</h2>
            <a href="tp_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <?= $message ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">Log New Placement</div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label>Student Name</label>
                                <input type="text" name="student_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Company / Organization</label>
                                <input type="text" name="company_name" class="form-control" required placeholder="e.g., TCS, Wipro">
                            </div>
                            <div class="mb-3">
                                <label>Job Role / Designation</label>
                                <input type="text" name="designation" class="form-control" required placeholder="e.g., Junior Developer">
                            </div>
                            <div class="mb-3">
                                <label>Salary Package (Optional)</label>
                                <input type="text" name="package" class="form-control" placeholder="e.g., 3.5 LPA">
                            </div>
                            <div class="mb-3">
                                <label>Date of Placement</label>
                                <input type="date" name="placement_date" class="form-control" required>
                            </div>
                            <button type="submit" name="add_placement" class="btn btn-success w-100">Save Record</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Student</th>
                                        <th>Company</th>
                                        <th>Role</th>
                                        <th>Package</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $placements->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['company_name']) ?></td>
                                        <td><?= htmlspecialchars($row['designation']) ?></td>
                                        <td><?= htmlspecialchars($row['package'] ?: 'N/A') ?></td>
                                        <td><?= date('d M Y', strtotime($row['placement_date'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if($placements->num_rows == 0): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No placements logged yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
