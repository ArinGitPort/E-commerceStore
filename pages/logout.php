<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Only proceed if user is logged in
if (!empty($_SESSION['user_id'])) {
    $user_id    = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    try {
        // 1. Record logout timestamp (trigger will log the audit)
        $pdo->prepare("UPDATE users SET last_logout_at = NOW() WHERE user_id = ?")
            ->execute([$user_id]);

    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// 2. Destroy session completely
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 3. Redirect to login
header("Location: login.php?logout=1");
exit;
