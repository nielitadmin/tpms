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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - NIELIT TPS</title>
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #155E75; 
            --primary-light: #0284C7; 
            --bg-body: #F8FAFC;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-body); 
            overflow: hidden; /* Prevents scrollbars from floating background */
        }

        /* Ambient Floating Background */
        .ambient-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; pointer-events: none; background: radial-gradient(circle at 50% 0%, #E0F2FE 0%, #F8FAFC 70%); perspective: 1000px; }
        .shape { position: absolute; background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.3)); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 1); box-shadow: 0 15px 35px rgba(21, 94, 117, 0.05), inset 0 0 20px rgba(255, 255, 255, 0.5); animation: float-3d 25s infinite linear; }
        .cube { width: 180px; height: 180px; border-radius: 35px; top: 15%; left: 10%; animation-duration: 35s; }
        .ring { width: 260px; height: 260px; border-radius: 50%; border: 40px solid rgba(255,255,255,0.5); bottom: 10%; right: 10%; animation-duration: 40s; animation-direction: reverse; background: transparent; }
        @keyframes float-3d { 0% { transform: translateY(0) rotateX(0deg) rotateY(0deg) rotateZ(0deg); } 50% { transform: translateY(-30px) rotateX(180deg) rotateY(90deg) rotateZ(45deg); } 100% { transform: translateY(0) rotateX(360deg) rotateY(180deg) rotateZ(90deg); } }

        /* Glassmorphism Login Card */
        .glass-login-card { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(25px); 
            border: 1px solid rgba(255, 255, 255, 0.8); 
            border-radius: 24px; 
            padding: 40px; 
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1); 
            position: relative; 
            overflow: hidden; 
        }
        .glass-login-card::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
        
        .card-header-icon {
            width: 60px; height: 60px;
            background: #EFF6FF; color: var(--primary-light);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin: 0 auto 20px auto;
            border: 1px solid #BAE6FD;
        }

        h4.auth-title { font-size: 24px; font-weight: 800; color: #0F172A; }
        p.auth-sub { font-size: 14px; color: #475569; font-weight: 500; margin-bottom: 25px; }

        /* Input Styling */
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; }
        .input-group-custom { position: relative; }
        .input-group-custom i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 14px; transition: 0.3s; }
        .form-control { width: 100%; padding: 14px 16px 14px 45px; border: 1px solid #E2E8F0; border-radius: 12px; font-family: inherit; font-size: 14px; background: #F8FAFC; transition: 0.3s; font-weight: 500; }
        .form-control:focus { outline: none; border-color: var(--primary-light); box-shadow: 0 0 0 4px #EFF6FF; background: white; }
        .form-control:focus + i { color: var(--primary-light); }

        /* Button Styling */
        .btn-submit { width: 100%; padding: 16px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 10px; box-shadow: 0 4px 15px rgba(2, 132, 199, 0.25); display: flex; justify-content: center; align-items: center; gap: 8px;}
        .btn-submit:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 8px 25px rgba(2, 132, 199, 0.35); }
        
        .alert-custom { background-color: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; padding: 12px 15px; border-radius: 10px; font-size: 13.5px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

        .form-footer { text-align: center; margin-top: 25px; font-size: 14px; font-weight: 600; color: #475569; }
        .form-footer a { color: var(--primary-light); text-decoration: none; transition: 0.2s; }
        .form-footer a:hover { color: var(--primary); text-decoration: underline; }

        .back-link { position: absolute; top: 30px; left: 30px; color: #64748B; font-weight: 700; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; z-index: 10;}
        .back-link:hover { color: var(--primary); }

        @media (max-width: 768px) {
            .glass-login-card { padding: 30px 20px; }
            .back-link { top: 15px; left: 15px; }
        }
    </style>
</head>
<body class="d-flex align-items-center vh-100 relative">
    
    <!-- Ambient Background Elements -->
    <div class="ambient-bg">
        <div class="shape cube"></div>
        <div class="shape ring"></div>
    </div>

    <!-- Back to Portal Link -->
    <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>

    <div class="container relative z-10">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                
                <div class="glass-login-card">
                    <div class="card-header-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="text-center auth-title">Portal Access</h4>
                    <p class="text-center auth-sub">Secure login for registered partners & staff</p>
                    
                    <?php if($error): ?>
                        <div class="alert-custom">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-group-custom">
                                <input type="email" name="email" class="form-control" placeholder="name@nielit.gov.in" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="d-flex justify-content-between">
                                Password
                                <a href="#" style="font-size: 12px; color: var(--primary-light); text-decoration: none; font-weight: 600;">Forgot?</a>
                            </label>
                            <div class="input-group-custom">
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Secure Sign In <i class="fas fa-arrow-right"></i></button>
                    </form>
                    
                    <div class="form-footer border-top pt-3 mt-4">
                        New Training Center? <a href="tp/tp_signup.php">Apply Here</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>