<?php
// Assuming session-init.php handles session_start()
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Check if the user is logged in and is admin (by role_id)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: /pages/login.php?unauthorized=1");
    exit;
}

// Session timeout (here 24 hours; adjust if needed)
$timeoutSeconds = 86400;

// Auto-logout on inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
    session_unset();
    session_destroy();
    header("Location: /pages/login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Optional: update last activity timestamp in DB
try {
    $stmt = $pdo->prepare("UPDATE users SET last_activity_at = NOW() WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
} catch (PDOException $e) {
    error_log("Failed to update admin last activity: " . $e->getMessage());
}
?>
