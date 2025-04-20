<?php
// ajax/update_order_status.php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';
header('Content-Type: application/json');

// 1) Auth check
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// 2) Decode payload
$payload   = json_decode(file_get_contents('php://input'), true);
$orderId   = isset($payload['order_id'])   ? (int)$payload['order_id']   : 0;
$newStatus = $payload['new_status'] ?? '';

// 3) Only archive on Received
if ($newStatus !== 'Received') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// 4) Fetch order and verify ownership & state
$stmt = $pdo->prepare("
    SELECT customer_id, order_date, total_price, shipping_address,
           shipping_phone, delivery_method_id, discount, created_at,
           modified_at, viewed, estimated_delivery
      FROM orders
     WHERE order_id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || $order['customer_id'] !== $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// 5) Ensure it’s been shipped
$statusChk = $pdo->prepare("SELECT order_status FROM orders WHERE order_id = ?");
$statusChk->execute([$orderId]);
if ($statusChk->fetchColumn() !== 'Shipped') {
    echo json_encode([
        'success' => false,
        'error'   => "Only shipped orders can be archived"
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 6) Archive the order
    $insOrder = $pdo->prepare("
      INSERT INTO archived_orders
        (order_id, customer_id, order_date, order_status, total_price,
         shipping_address, shipping_phone, delivery_method_id, discount,
         created_at, modified_at, viewed, estimated_delivery)
      VALUES (?, ?, ?, 'Completed', ?, ?, ?, ?, ?, ?, ?, 0, ?)
    ");
    $insOrder->execute([
        $orderId,
        $order['customer_id'],
        $order['order_date'],
        $order['total_price'],
        $order['shipping_address'],
        $order['shipping_phone'],
        $order['delivery_method_id'],
        $order['discount'] ?? 0,
        $order['created_at'],
        $order['modified_at'],
        $order['estimated_delivery']
    ]);

    // 7) Archive each line item
    $itemSel = $pdo->prepare("
      SELECT product_id, quantity, total_price, created_at, modified_at
        FROM order_details
       WHERE order_id = ?
    ");
    $itemSel->execute([$orderId]);

    $itemIns = $pdo->prepare("
      INSERT INTO archived_order_details
        (order_id, product_id, quantity, total_price, created_at, modified_at)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    while ($row = $itemSel->fetch(PDO::FETCH_ASSOC)) {
        $itemIns->execute([
            $orderId,
            $row['product_id'],
            $row['quantity'],
            $row['total_price'],
            $row['created_at'],
            $row['modified_at']
        ]);
    }

    // 8) Delete originals
    $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")
        ->execute([$orderId]);
    $pdo->prepare("DELETE FROM orders WHERE order_id = ?")
        ->execute([$orderId]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage()
    ]);
}
