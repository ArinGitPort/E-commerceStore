<?php
// pages/ajax/get_return_items.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db_connection.php';
$orderId = (int)($_GET['order_id'] ?? 0);
$stmt = $pdo->prepare("
  SELECT d.product_id, p.product_name, d.quantity
  FROM archived_order_details d
  JOIN products p ON d.product_id = p.product_id
  WHERE d.order_id = ?
");
$stmt->execute([$orderId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
