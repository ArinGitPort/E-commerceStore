<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

$lastCheck = isset($_GET['lastCheck']) ? (int)$_GET['lastCheck'] : 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            o.order_id, 
            u.name AS customer, 
            UNIX_TIMESTAMP(o.created_at) AS timestamp,
            o.order_status,
            o.total_price
        FROM orders o
        JOIN users u ON o.customer_id = u.user_id
        WHERE UNIX_TIMESTAMP(o.created_at) > ?
        ORDER BY o.created_at ASC
        LIMIT 50
    ");
    $stmt->execute([$lastCheck]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $newLastCheck = $lastCheck;
    foreach ($orders as $order) {
        if ($order['timestamp'] > $newLastCheck) {
            $newLastCheck = $order['timestamp'];
        }
    }
    if (empty($orders)) {
        $newLastCheck = time();
    }

    echo json_encode(['orders' => $orders, 'lastCheck' => $newLastCheck]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
