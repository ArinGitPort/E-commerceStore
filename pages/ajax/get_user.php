<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

$userId = $_GET['user_id'] ?? 0;

$stmt = $pdo->prepare("SELECT u.*, m.membership_type_id, m.expiry_date 
                      FROM users u
                      LEFT JOIN memberships m ON u.user_id = m.user_id
                      WHERE u.user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($user);

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT user_id, name, email, role_id 
        FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$_GET['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    echo json_encode($user);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}