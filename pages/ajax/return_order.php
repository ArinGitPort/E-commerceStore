<?php
// ../pages/ajax/return_order.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Invalid request method']));
}

// Decode JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid JSON']));
}

$order_id = $input['order_id'] ?? null;
$items    = $input['items']    ?? [];
$reason   = trim($input['reason'] ?? '');

if (!$order_id || !is_array($items) || empty($items)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Missing order ID or items']));
}

try {
    $pdo->beginTransaction();

    // 1. Verify this order exists in archived_orders
    $archived = $pdo->prepare("SELECT 1 FROM archived_orders WHERE order_id = ?");
    $archived->execute([$order_id]);
    if (! $archived->fetch()) {
        throw new Exception("Order not found or not completed");
    }

    // 2. Insert into returns table
    $insReturn = $pdo->prepare("
        INSERT INTO returns (order_id, processed_by, reason)
        VALUES (?, ?, ?)
    ");
    $insReturn->execute([
        $order_id,
        $_SESSION['user_id'],
        $reason
    ]);
    $returnId = $pdo->lastInsertId();

    // 3. Prepare restock and detail‑insert statements
    $restockSt = $pdo->prepare("
        UPDATE products
        SET stock = stock + ?
        WHERE product_id = ?
    ");
    $detailSt  = $pdo->prepare("
        INSERT INTO return_details (return_id, product_id, quantity)
        VALUES (?, ?, ?)
    ");

    // 4. Process each returned item
    foreach ($items as $it) {
        $prodId = (int)($it['product_id'] ?? 0);
        $qty    = (int)($it['quantity']   ?? 0);
        if ($prodId <= 0 || $qty <= 0) {
            throw new Exception("Invalid product or quantity");
        }
        // Restock
        $restockSt->execute([$qty, $prodId]);
        // Record return detail
        $detailSt->execute([$returnId, $prodId, $qty]);
    }

    // 5. (Optional) Audit log entry
    $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type)
        VALUES (?, ?, ?, ?, 'UPDATE')
    ")->execute([
        $_SESSION['user_id'],
        "Processed return for order $order_id",
        'returns',
        $returnId
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'return_id' => $returnId]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
