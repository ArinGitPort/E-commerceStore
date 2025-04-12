<?php
require_once __DIR__ . '/../includes/session-init.php';

// Start session securely (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_name('BUNNISHOP_SESS');
    session_start();
}

// Security headers to prevent back button access
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// CSRF token validation (optional but recommended if logout is triggered via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
}

// Clear session data
$_SESSION = [];
session_unset();
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Redirect to login page with logout flag
header("Location: /pages/login.php?logged_out=1");
exit;

