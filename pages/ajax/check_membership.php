<?php
require_once __DIR__ . '/../../config/db_connection.php';

$userId = $_GET['user_id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            m.expiry_date < NOW() AS is_expired,
            mt.type_name
        FROM memberships m
        JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
        WHERE m.user_id = ?
        ORDER BY m.start_date DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();

    echo json_encode([
        'expired' => $result ? (bool)$result['is_expired'] : false,
        'type' => $result['type_name'] ?? 'Free'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Membership check failed: ' . $e->getMessage()
    ]);
}