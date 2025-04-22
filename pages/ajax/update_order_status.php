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

// 3) Ensure the status is "Received" before archiving
if ($newStatus !== 'Received') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// 4) Fetch the order and verify ownership & state
$stmt = $pdo->prepare("
    SELECT order_status
    FROM orders
    WHERE order_id = ?
    AND customer_id = ?  /* Fixed: Removed CAST to handle type conversion properly */
");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found or unauthorized']);
    exit;
}

// 5) Ensure the order is marked as "Shipped"
if ($order['order_status'] !== 'Shipped') {
    echo json_encode(['success' => false, 'error' => "Order must be marked as shipped before it can be received"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 6) Archive the order by moving to the archived_orders table
    $insArchivedOrder = $pdo->prepare("
        INSERT INTO archived_orders
            (order_id, customer_id, order_date, order_status, total_price, 
             shipping_address, shipping_phone, delivery_method_id, discount, 
             created_at, modified_at, viewed, estimated_delivery)
        SELECT order_id, customer_id, order_date, 'Completed', total_price, 
               shipping_address, shipping_phone, delivery_method_id, discount, 
               created_at, modified_at, viewed, estimated_delivery
        FROM orders WHERE order_id = ?
    ");
    $insArchivedOrder->execute([$orderId]);

    // 7) Archive each line item from the order
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

    // 8) Delete the original order from the orders and order_details tables
    $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")->execute([$orderId]);
    $pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$orderId]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Order archived successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}