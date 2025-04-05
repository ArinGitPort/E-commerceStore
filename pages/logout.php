<?php
require_once __DIR__ . '/../includes/session-init.php';

// Clear all session variables
$_SESSION = [];

// Delete session cookie if sessions use cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../pages/login.php");
exit;
?>
