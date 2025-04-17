<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Turn off direct error display; log everything
ini_set('display_errors', 0);
ini_set('log_errors',    1);
ini_set('error_log',     __DIR__ . '/../logs/php-errors.log');

// Only proceed if there’s an active session with a user ID
if (!empty($_SESSION['user_id'])) {
    $user_id    = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR']   ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    try {
        error_log("=== Starting logout process for user {$user_id} ===");

        // 1. Update users table: record logout timestamp
        $stmt = $pdo->prepare("UPDATE users SET last_logout_at = NOW() WHERE user_id = ?");
        $stmt->execute([$user_id]);
        error_log("Updated users.last_logout_at; rows affected: " . $stmt->rowCount());

        // 2. Insert into audit_logs: record the logout action
        $auditSql = "
            INSERT INTO audit_logs 
              (user_id, action, table_name, record_id, action_type, ip_address, user_agent)
            VALUES (?, 'User logged out', 'users', ?, 'LOGOUT', ?, ?)
        ";
        $stmt = $pdo->prepare($auditSql);
        $stmt->execute([
            $user_id,
            $user_id,
            $ip_address,
            $user_agent
        ]);
        error_log("Inserted logout into audit_logs; insert ID: " . $pdo->lastInsertId());

    } catch (PDOException $e) {
        error_log("Database error during logout: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("General error during logout: " . $e->getMessage());
    }
} else {
    error_log("No user_id in session; skipping logout audit.");
}

// 3. Destroy session completely
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
error_log("Session destroyed successfully");

// 4. Redirect to login with a cache-busting timestamp
header("Location: login.php?logout=1&t=" . time());
exit;
