<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;
$date = $data['date'] ?? null;

if (!$orderId || !$date) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing parameters']));
}

try {
    $stmt = $pdo->prepare("UPDATE orders SET estimated_delivery = ? WHERE order_id = ?");
    $stmt->execute([$date, $orderId]);
    
    echo json_encode(['success' => true, 'formatted_date' => date('M d, Y', strtotime($date))]);
} catch (PDOException $e) {
    http_response_code(500);
    // Log the full error info for debugging
    error_log(print_r($stmt->errorInfo(), true));
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
