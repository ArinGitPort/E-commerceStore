<?php
// pages/ajax/get_order_details.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

$orderId = (int)($_GET['order_id'] ?? 0);
$isReturn = isset($_GET['is_return']); // Flag to indicate if this is for a return
if (!$orderId) {
    die('<div class="alert alert-danger">Invalid order ID</div>');
}

// 1. Try live order first
// Change the orderStmt preparation to:
$orderStmt = $pdo->prepare("
    (SELECT 
        o.order_id,
        o.customer_id,
        o.order_date,
        o.order_status,
        o.total_price,
        o.shipping_address,
        o.shipping_phone,
        u.name,
        u.email,
        dm.method_name,
        o.delivery_method_id,
        o.estimated_delivery,
        FALSE AS is_archived
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    JOIN delivery_methods dm ON o.delivery_method_id = dm.delivery_method_id
    WHERE o.order_id = ?)
    
    UNION
    
    (SELECT 
        ao.order_id,
        ao.customer_id,
        ao.order_date,
        ao.order_status,
        ao.total_price,
        ao.shipping_address,
        ao.shipping_phone,
        u.name,
        u.email,
        dm.method_name,
        ao.delivery_method_id,
        ao.estimated_delivery,
        TRUE AS is_archived
    FROM archived_orders ao
    JOIN users u ON ao.customer_id = u.user_id
    JOIN delivery_methods dm ON ao.delivery_method_id = dm.delivery_method_id
    WHERE ao.order_id = ?)
    
    LIMIT 1
");
$orderStmt->execute([$orderId, $orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('<div class="alert alert-warning">Order not found</div>');
}

// Determine which details table to use
$detailsTable = $order['is_archived'] ? 'archived_order_details' : 'order_details';

// 2. Fetch line items
$itemStmt = $pdo->prepare("
    SELECT 
        d.*, 
        p.product_name,
        p.product_id,
        IFNULL((
            SELECT SUM(ri.quantity) 
            FROM return_items ri 
            JOIN returns r ON ri.return_id = r.return_id
            WHERE ri.product_id = p.product_id 
            AND r.order_id = ?
            AND r.return_status IN ('Approved', 'Processed')
        ), 0) AS returned_qty
    FROM {$detailsTable} d
    JOIN products p ON d.product_id = p.product_id
    WHERE d.order_id = ?
");
$itemStmt->execute([$orderId, $orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. If this is for a return, get return-specific info
$returnInfo = null;
if ($isReturn) {
    $returnStmt = $pdo->prepare("
        SELECT 
            r.return_id,
            r.return_status,
            r.reason,
            r.return_date,
            r.last_status_update,
            u.name AS processed_by_name
        FROM returns r
        LEFT JOIN users u ON r.processed_by = u.user_id
        WHERE r.order_id = ?
        ORDER BY r.return_date DESC
        LIMIT 1
    ");
    $returnStmt->execute([$orderId]);
    $returnInfo = $returnStmt->fetch(PDO::FETCH_ASSOC);
}

// Helper to escape
function safe($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES);
}
?>

<div class="container mt-2">
    <?php if ($isReturn && $returnInfo): ?>
        <div class="alert alert-<?= $returnInfo['return_status'] === 'Approved' ? 'success' : 'danger' ?> mb-4">
            <h5 class="alert-heading">
                Return <?= strtoupper($returnInfo['return_status']) ?>
                <?php if ($returnInfo['processed_by_name']): ?>
                    <small class="text-muted">by <?= safe($returnInfo['processed_by_name']) ?></small>
                <?php endif; ?>
            </h5>
            <p class="mb-1"><strong>Reason:</strong> <?= safe($returnInfo['reason']) ?></p>
            <p class="mb-1"><strong>Date Requested:</strong> <?= date('M d, Y H:i', strtotime($returnInfo['return_date'])) ?></p>
            <?php if ($returnInfo['last_status_update']): ?>
                <p class="mb-0"><strong>Last Updated:</strong> <?= date('M d, Y H:i', strtotime($returnInfo['last_status_update'])) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Customer Info -->
        <div class="col-md-6">
            <h6>Customer Information</h6>
            <ul class="list-unstyled">
                <li><strong>Name:</strong> <?= safe($order['name']) ?></li>
                <li><strong>Email:</strong> <?= safe($order['email']) ?></li>
            </ul>

            <h6>Shipping Information</h6>
            <ul class="list-unstyled">
                <li><strong>Phone:</strong> <?= safe($order['shipping_phone']) ?></li>
                <li><strong>Address:</strong><br><?= nl2br(safe($order['shipping_address'])) ?></li>
            </ul>
        </div>

        <!-- Order Info -->
        <div class="col-md-6">
            <h6>Order Information</h6>
            <ul class="list-unstyled">
                <li><strong>Order Date:</strong> <?= date('M d, Y H:i', strtotime($order['order_date'])) ?></li>
                <li>
                    <strong>Status:</strong> 
                    <span class="badge bg-<?= $order['order_status'] === 'Completed' ? 'success' : 
                                          ($order['order_status'] === 'Returned' ? 'warning' : 'secondary') ?>">
                        <?= safe(ucfirst($order['order_status'])) ?>
                    </span>
                </li>
                <li><strong>Delivery:</strong> <?= safe($order['method_name']) ?></li>
                <li>
                    <strong>Estimated Delivery:</strong>
                    <?= !empty($order['estimated_delivery'])
                        ? date('M d, Y', strtotime($order['estimated_delivery']))
                        : '—' ?>
                </li>
                <?php if ($order['is_archived']): ?>
                    <li><strong>Archived:</strong> Yes</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Line Items -->
    <table class="table mt-4">
        <thead>
            <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <?php if ($isReturn): ?>
                    <th>Returned</th>
                    <th>Net Qty</th>
                <?php endif; ?>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sumTotal = 0;
            $sumQty = 0;
            $sumReturned = 0;
            
            foreach ($items as $it):
                $unit = $it['quantity'] > 0 ? $it['total_price'] / $it['quantity'] : 0;
                $sumTotal += $it['total_price'];
                $sumQty += $it['quantity'];
                $sumReturned += $it['returned_qty'];
                $netQty = $it['quantity'] - $it['returned_qty'];
            ?>
                <tr>
                    <td><?= safe($it['product_name']) ?></td>
                    <td>₱<?= number_format($unit, 2) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <?php if ($isReturn): ?>
                        <td class="<?= $it['returned_qty'] > 0 ? 'text-danger' : '' ?>">
                            <?= (int)$it['returned_qty'] ?>
                        </td>
                        <td class="<?= $netQty < $it['quantity'] ? 'text-success' : '' ?>">
                            <?= (int)$netQty ?>
                        </td>
                    <?php endif; ?>
                    <td>₱<?= number_format($it['total_price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    // Calculate shipping, tax, and grand total
    $shippingFee = max($sumTotal * 0.05, 50);
    $taxAmount = $sumTotal * 0.12;
    $grandTotal = $sumTotal + $shippingFee + $taxAmount;
    
    // For returns, calculate potential refund
    $potentialRefund = 0;
    if ($isReturn) {
        $potentialRefund = $sumTotal * 0.8; // Example: 80% refund policy
    }
    ?>

    <!-- Full Breakdown -->
    <div class="mt-3">
        <h5>Order Breakdown</h5>
        <ul class="list-unstyled">
            <li><strong>Total Items:</strong> <?= $sumQty ?></li>
            <?php if ($isReturn): ?>
                <li><strong>Items Returned:</strong> <?= $sumReturned ?></li>
                <li><strong>Net Items:</strong> <?= $sumQty - $sumReturned ?></li>
            <?php endif; ?>
            <li><strong>Items Total (₱):</strong> <?= number_format($sumTotal, 2) ?></li>
            <li><strong>Shipping Fee (₱):</strong> <?= number_format($shippingFee, 2) ?></li>
            <li><strong>Tax (12%) (₱):</strong> <?= number_format($taxAmount, 2) ?></li>
            <li><strong>Grand Total (₱):</strong> <?= number_format($grandTotal, 2) ?></li>
            <?php if ($isReturn && $sumReturned > 0): ?>
                <li class="fw-bold mt-2">
                    <strong>Potential Refund (₱):</strong> 
                    <?= number_format($potentialRefund, 2) ?>
                    <small class="text-muted">(80% of item value)</small>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>