<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');



$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE users SET 
            is_active = :new_status,
            modified_at = NOW()
        WHERE user_id = :user_id
    ");

    $stmt->execute([
        ':new_status' => $input['new_status'],
        ':user_id' => $input['user_id']
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Status update failed: ' . $e->getMessage()]);
}