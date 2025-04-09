<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/admin_session.php';

header('Content-Type: application/json');

$lastCheck = $_GET['lastCheck'] ?? 0;

$stmt = $pdo->prepare("
    SELECT o.order_id as id, u.name as customer, o.order_date as time 
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_date > FROM_UNIXTIME(?)
    ORDER BY o.order_date DESC
");
$stmt->execute([$lastCheck]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($orders);