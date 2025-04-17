<?php
// ../pages/ajax/complete_order.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Invalid request method']));
}

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid JSON input']));
}

$order_id = $input['order_id'] ?? null;
if (!$order_id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Missing order ID']));
}

try {
    $pdo->beginTransaction();

    // 1. Verify order exists
    $checkStmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $checkStmt->execute([$order_id]);
    $order = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        throw new Exception('Order not found');
    }

    // 2. Deduct stock for each order line
    $detailStmt = $pdo->prepare("
        SELECT product_id, quantity 
        FROM order_details 
        WHERE order_id = ?
    ");
    $detailStmt->execute([$order_id]);
    $orderItems = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orderItems as $item) {
        $updateStock = $pdo->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE product_id = ?
        ");
        $updateStock->execute([
            $item['quantity'],
            $item['product_id']
        ]);
        // Optionally, you could check $updateStock->rowCount() or 
        // throw if stock would go negative.
    }

    // 3. Archive the order
    $archiveStmt = $pdo->prepare("
        INSERT INTO archived_orders (
            order_id, customer_id, order_date, shipping_address,
            order_status, total_price, delivery_method_id,
            discount, created_at, modified_at, viewed, estimated_delivery
        )
        SELECT 
            order_id, customer_id, order_date, shipping_address,
            'Completed', total_price, delivery_method_id,
            discount, created_at, NOW(), viewed, estimated_delivery
        FROM orders 
        WHERE order_id = ?
    ");
    $archiveStmt->execute([$order_id]);

    // 4. Delete from orders table
    $deleteStmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
    $deleteStmt->execute([$order_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
