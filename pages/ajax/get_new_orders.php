<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: text/plain');

// Get last check time from request
$lastCheck = isset($_GET['lastCheck']) ? (int)$_GET['lastCheck'] : 0;

try {
    $stmt = $pdo->prepare("
        SELECT o.order_id, u.name AS customer, o.order_date 
        FROM orders o
        JOIN users u ON o.customer_id = u.user_id
        WHERE u.role_id = 1 AND UNIX_TIMESTAMP(o.order_date) > ?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$lastCheck]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        echo sprintf(
            "ORDER|%s|%s|%s\n",
            $order['order_id'],
            htmlspecialchars($order['customer']),
            $order['order_date']
        );
    }

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo "ERROR|Database error";
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo "ERROR|System error";
}