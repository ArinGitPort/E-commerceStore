<?php
//This is unused fr fr
// mark_notification_read.php
session_start();
require_once '../config/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = filter_var($data['notificationId'], FILTER_VALIDATE_INT);
$userId = $_SESSION['user_id'];

if (!$notificationId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid notification ID']));
}

try {
    // Check if recipient record exists
    $stmt = $pdo->prepare("
        INSERT INTO notification_recipients (notification_id, user_id, is_read, read_at)
        VALUES (?, ?, TRUE, NOW())
        ON DUPLICATE KEY UPDATE
        is_read = VALUES(is_read),
        read_at = VALUES(read_at)
    ");
    $stmt->execute([$notificationId, $userId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>