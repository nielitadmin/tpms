<?php
// 1. CRITICAL: Use the exact same session name used across the application
session_name('NIELIT_TPMS');
session_start();

// 2. Unset all active session variables in the array
$_SESSION = array();

// 3. SECURE LOGOUT: Completely destroy the session cookie in the user's browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy the session file on the server
session_destroy();

// 5. Redirect back to the homepage (which now contains our login form)
header("Location: index.php");
exit();
?>