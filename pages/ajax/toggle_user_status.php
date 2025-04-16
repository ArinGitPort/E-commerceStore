<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method - expected POST');
    }

    // Check required parameters exist
    if (!isset($_POST['user_id'], $_POST['new_status'])) {
        throw new Exception('Missing required parameters');
    }

    // Validate parameters
    $userId = (int)$_POST['user_id'];
    $newStatus = (int)$_POST['new_status'];

    if ($userId < 1) {
        throw new Exception('Invalid user ID: Must be positive integer');
    }

    if (!in_array($newStatus, [0, 1], true)) {
        throw new Exception('Invalid status value: Must be 0 or 1');
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE users SET is_active = ?, modified_at = NOW() WHERE user_id = ?");
    $stmt->execute([$newStatus, $userId]);

    // Check if update was successful
    if ($stmt->rowCount() === 0) {
        throw new Exception('No changes made - user might not exist');
    }

    // Return success response
    echo json_encode(['success' => true, 'message' => 'Status updated']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}