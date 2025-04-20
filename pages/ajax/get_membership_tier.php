<?php
// pages/ajax/get_membership_tier.php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id < 1) {
        throw new Exception('Invalid tier ID');
    }

    $stmt = $pdo->prepare("
        SELECT membership_type_id, type_name, price, description, can_access_exclusive
        FROM membership_types
        WHERE membership_type_id = ?
    ");
    $stmt->execute([$id]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tier) {
        throw new Exception('Tier not found');
    }

    echo json_encode($tier);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTrace() // Remove in production
    ]);
}
exit;