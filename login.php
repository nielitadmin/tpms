<?php
// 1. CRITICAL FIX: Must match the session name used in the dashboards!
session_name('NIELIT_TPMS');
session_start();

require_once 'includes/config.php';

$error = '';

// If already logged in, redirect them immediately
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
        exit();
    } else {
        header("Location: tp/tp_dashboard.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // 2. SECURITY FIX: Use Prepared Statements to prevent SQL Injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Safe check: If you added a 'status' column to users table, enforce it
                if (isset($user['status']) && $user['status'] !== 'active') {
                    $error = "Account is pending or inactive. Contact Admin.";
                } 
                // Verify the hashed password
                elseif (password_verify($password, $user['password'])) {
                    
                    // 3. SESSION FIX: Use the exact variable names the dashboards expect
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role']; 
                    
                    // Optional: Store name if it exists in your table
                    if(isset($user['name'])) {
                        $_SESSION['name'] = $user['name'];
                    }

                    // Route based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin/admin_dashboard.php");
                    } else {
                        header("Location: tp/tp_dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            error_log("Login Query Error: " . $conn->error);
            $error = "System error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - NIELIT TPS</title>
    <!-- Google Fonts for a slightly cleaner look -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
        .card { border-radius: 12px; border: 1px solid #E2E8F0; }
        .btn-primary { background-color: #0284C7; border: none; padding: 12px; font-weight: 600; border-radius: 8px; }
        .btn-primary:hover { background-color: #0369A1; }
        .form-control { padding: 12px; border-radius: 8px; }
    </style>
</head>
<body class="d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                
                <!-- Back to Home Link -->
                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none text-muted fw-bold">&larr; Back to Portal</a>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4 fw-bold text-dark">TPS Portal Login</h4>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger" style="font-size: 14px; font-weight: 500;">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small">Email address</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Secure Login</button>
                        </form>
                        
                        <div class="text-center mt-4 pt-3 border-top">
                            <a href="tp/tp_signup.php" class="text-decoration-none fw-bold" style="color: #0D9488; font-size: 14px;">New Center? Sign Up Here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>