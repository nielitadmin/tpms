<?php
session_start();
require __DIR__ . '/../includes/config.php';

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $center_id = $conn->real_escape_string($_POST['center_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "danger"; 
    } else {
        // Check if email or center_id already exists
        $check_sql = "SELECT id FROM users WHERE email = '$email' OR center_id = '$center_id'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $message = "Email or Center ID already registered.";
            $messageType = "danger";
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (center_id, name, email, phone, password, role, status) 
                    VALUES ('$center_id', '$name', '$email', '$phone', '$hashed_password', 'tp', 'pending')";
            
            if ($conn->query($sql) === TRUE) {
                $message = "Registration successful! Please wait for Admin approval before logging in.";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TP Sign Up - NIELIt TPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Register New TPS Center</h4>
                        
                        <?php if($message): ?>
                            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label>Unique Center ID (e.g., OD001)</label>
                                <incput type="text" name="center_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Center / Institute Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Email address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control" required pattern="[0-9]{10}" title="Enter a valid 10-digit phone number">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Register Center</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="../login.php">Already registered?? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
