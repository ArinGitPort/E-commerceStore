<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Only proceed if user is logged in
if (!empty($_SESSION['user_id'])) {
    $user_id    = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    try {
        // 1. Record logout timestamp (no longer relying on trigger)
        $pdo->prepare("UPDATE users SET last_logout_at = NOW() WHERE user_id = :user_id")
            ->execute(['user_id' => $user_id]);
            
        // 2. Add direct audit log entry for logout
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
            VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
        ");
        $stmt->execute([
            'user_id'       => $user_id,
            'action'        => 'User logged out',
            'table_name'    => 'users',
            'record_id'     => $user_id,
            'action_type'   => 'LOGOUT',
            'ip_address'    => $ip_address,
            'user_agent'    => $user_agent,
            'affected_data' => json_encode([])
        ]);
        
        error_log("Logout action successfully logged for user ID: " . $user_id);
    } catch (Exception $e) {
        error_log("Failed to log logout activity: " . $e->getMessage());
        // Continue with logout process despite the logging error
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
