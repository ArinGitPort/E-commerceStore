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
            name = :name,
            email = :email,
            role_id = :role_id,
            modified_at = NOW()
        WHERE user_id = :user_id
    ");

    $stmt->execute([
        ':name' => $input['name'],
        ':email' => $input['email'],
        ':role_id' => $input['role_id'],
        ':user_id' => $input['user_id']
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed: ' . $e->getMessage()]);
}