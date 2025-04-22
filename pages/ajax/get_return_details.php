<?php
// pages/ajax/get_return_details.php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

$returnId = (int)($_GET['return_id'] ?? 0);

try {
    if (!$returnId) {
        throw new Exception("Invalid return ID");
    }

    // Get return basic info
    $returnStmt = $pdo->prepare("
        SELECT 
            r.*,
            u.name AS customer_name,
            u.email AS customer_email,
            ao.order_date AS original_order_date,
            dm.method_name AS delivery_method,
            ao.shipping_address,
            ao.shipping_phone
        FROM returns r
        JOIN archived_orders ao ON r.archived_order_id = ao.order_id
        JOIN users u ON ao.customer_id = u.user_id
        JOIN delivery_methods dm ON ao.delivery_method_id = dm.delivery_method_id
        WHERE r.return_id = ?
    ");
    $returnStmt->execute([$returnId]);
    $return = $returnStmt->fetch(PDO::FETCH_ASSOC);

    if (!$return) {
        throw new Exception("Return not found");
    }

    // Get return items
    $itemsStmt = $pdo->prepare("
        SELECT 
            ri.*,
            p.product_name,
            p.sku AS product_sku,
            (ri.quantity * ri.unit_price) AS total_refund
        FROM return_items ri
        JOIN products p ON ri.product_id = p.product_id
        WHERE ri.return_id = ?
    ");
    $itemsStmt->execute([$returnId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get status history
    $historyStmt = $pdo->prepare("
        SELECT 
            rsh.*,
            u.name AS changed_by_name
        FROM return_status_history rsh
        LEFT JOIN users u ON rsh.changed_by = u.user_id
        WHERE rsh.return_id = ?
        ORDER BY rsh.change_date DESC
    ");
    $historyStmt->execute([$returnId]);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totals = [
        'total_items' => count($items),
        'total_quantity' => array_sum(array_column($items, 'quantity')),
        'total_refund' => array_sum(array_column($items, 'total_refund')),
        'restocking_fee' => array_sum(array_column($items, 'restocking_fee') ?? [0])
    ];
    $totals['net_refund'] = $totals['total_refund'] - $totals['restocking_fee'];

    echo json_encode([
        'success' => true,
        'return' => $return,
        'items' => $items,
        'history' => $history,
        'totals' => $totals
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}