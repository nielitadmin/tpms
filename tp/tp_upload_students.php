<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('tp');

$tp_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Fetch active courses for the dropdown
$courses = $conn->query("SELECT id, course_name FROM courses WHERE status = 'active'");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['student_file'])) {
    $course_id = (int)$_POST['course_id'];
    $file = $_FILES['student_file']['tmp_name'];
    $filename = $_FILES['student_file']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    if (strtolower($ext) !== 'csv') {
        $message = "Invalid file format. Please upload a .csv file.";
        $messageType = "danger";
    } elseif ($_FILES["student_file"]["size"] > 2000000) { // 2MB limit
        $message = "File is too large. Maximum size is 2MB.";
        $messageType = "danger";
    } else {
        // Open the CSV file
        $handle = fopen($file, "r");
        if ($handle !== FALSE) {
            // Skip the first row (headers)
            fgetcsv($handle, 1000, ",");
            
            $successCount = 0;
            $stmt = $conn->prepare("INSERT INTO students (tp_id, course_id, student_name, email, phone, enrollment_date) VALUES (?, ?, ?, ?, ?, ?)");
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Assuming CSV Format: [Name, Email, Phone, Enrollment Date (YYYY-MM-DD)]
                if (count($data) >= 4 && !empty($data[0])) {
                    $student_name = $data[0];
                    $email = $data[1];
                    $phone = $data[2];
                    $enrollment_date = $data[3];

                    $stmt->bind_param("iissss", $tp_id, $course_id, $student_name, $email, $phone, $enrollment_date);
                    if ($stmt->execute()) {
                        $successCount++;
                    }
                }
            }
            fclose($handle);
            $stmt->close();
            
            $message = "Successfully uploaded $successCount students!";
            $messageType = "success";
        } else {
            $message = "Error reading the file.";
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Students - TP Portal</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Bulk Upload Students</h2>
                    <a href="tp_dashboard.php" class="btn btn-secondary">Back</a>
                </div>

                <?php if($message): ?>
                    <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Note:</strong> Please upload data using a <code>.csv</code> file. 
                            <hr>
                            <strong>Required Column Order:</strong><br>
                            1. Student Name | 2. Email Address | 3. Phone Number | 4. Enrollment Date (YYYY-MM-DD)
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select Course</label>
                                <select name="course_id" class="form-select" required>
                                    <option value="">-- Choose a Course --</option>
                                    <?php while($c = $courses->fetch_assoc()): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload CSV File</label>
                                <input class="form-control" type="file" name="student_file" accept=".csv" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Start Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
