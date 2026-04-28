<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function checkRole($requiredRole) {
    checkLogin();
    if ($_SESSION['role'] !== $requiredRole) {
        // Redirect to their respective dashboard if they try to cross roles
        $redirect = ($_SESSION['role'] === 'admin') ? '../admin/admin_dashboard.php' : '../tp/tp_dashboard.php';
        header("Location: " . $redirect);
        exit();
    }
}
?>
