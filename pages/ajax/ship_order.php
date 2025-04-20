<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';
header('Content-Type: application/json');

// 1. Auth check (only staff/admin should ship)
if (empty($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Not authenticated']);
  exit;
}

// 2. Decode JSON
$data = json_decode(file_get_contents('php://input'), true);
$orderId = (int)($data['order_id'] ?? 0);

// 3. Verify current status
$stmt = $pdo->prepare("SELECT order_status FROM orders WHERE order_id = ?");
$stmt->execute([$orderId]);
$current = $stmt->fetchColumn();
if (!$current || $current !== 'Pending') {
  echo json_encode([
    'success' => false,
    'error'   => "Cannot ship an order in “{$current}” status"
  ]);
  exit;
}

// 4. Update to Shipped
$upd = $pdo->prepare("
  UPDATE orders 
     SET order_status = 'Shipped', modified_at = NOW() 
   WHERE order_id = ?
");
$upd->execute([$orderId]);

echo json_encode(['success' => true]);
