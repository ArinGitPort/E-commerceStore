<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/admin_session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

$orderId = $_POST['order_id'] ?? null;
$newStatus = $_POST['new_status'] ?? null;

if (!$orderId || !$newStatus) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing parameters']));
}

try {
    // The update query changes the order_status for the given order.
    $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->execute([$newStatus, $orderId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    // This log will help you debug if the update fails:
    error_log(print_r($stmt->errorInfo(), true));
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
