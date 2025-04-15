<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Only proceed if there's an active session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    
    try {
        // Update user's last logout time
        $pdo->prepare("UPDATE users SET last_logout_at = NOW() WHERE user_id = ?")
           ->execute([$user_id]);
        
        // Update session record with logout time if exists
        $pdo->prepare("UPDATE user_sessions SET logout_time = NOW() WHERE session_id = ?")
           ->execute([$session_id]);
        
        // Log the logout action
        $pdo->prepare("
            INSERT INTO audit_logs 
            (user_id, action, table_name, record_id, action_type, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $user_id,
            'User logged out',
            'users',
            $user_id,
            'LOGOUT',
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
    } catch (Exception $e) {
        error_log("Logout tracking error: " . $e->getMessage());
        // Continue with logout even if tracking fails
    }
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear session cookie
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

// Prevent caching with multiple headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Redirect to login with cache-busting parameter
header("Location: login.php?t=" . time() . "&logout=1");
exit;
?>