<?php
// pages/ajax/get_order_details.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) {
    die('<div class="alert alert-danger">Invalid order ID</div>');
}

// 1. Try live order
$liveStmt = $pdo->prepare("
    SELECT 
        o.*, 
        u.name, u.email, u.phone, u.address, 
        dm.method_name 
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    JOIN delivery_methods dm ON o.delivery_method_id = dm.delivery_method_id
    WHERE o.order_id = ?
");
$liveStmt->execute([$orderId]);
$order = $liveStmt->fetch(PDO::FETCH_ASSOC);

if ($order) {
    $detailsTable = 'order_details';
} else {
    // 2. Try archived
    $archStmt = $pdo->prepare("
        SELECT 
            ao.*, 
            u.name, u.email, u.phone, u.address, 
            dm.method_name 
        FROM archived_orders ao
        JOIN users u ON ao.customer_id = u.user_id
        JOIN delivery_methods dm ON ao.delivery_method_id = dm.delivery_method_id
        WHERE ao.order_id = ?
    ");
    $archStmt->execute([$orderId]);
    $order = $archStmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        die('<div class="alert alert-warning">Order not found</div>');
    }
    $detailsTable = 'archived_order_details';
}

// 3. Fetch line items
$itemStmt = $pdo->prepare("
    SELECT d.*, p.product_name 
    FROM {$detailsTable} d
    JOIN products p ON d.product_id = p.product_id
    WHERE d.order_id = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Helper function to safely escape
function safe($val)
{
    return htmlspecialchars($val ?? '');
}
?>

<div class="container mt-2">
    <div class="row">
        <div class="col-md-6">
            <h6>Customer Information</h6>
            <ul class="list-unstyled">
                <li><strong>Name:</strong> <?= safe($order['name']) ?></li>
                <li><strong>Email:</strong> <?= safe($order['email']) ?></li>
                <li><strong>Phone:</strong> <?= safe($order['phone']) ?></li>
                <li><strong>Address:</strong> <?= nl2br(safe($order['address'])) ?></li>
            </ul>
        </div>
        <div class="col-md-6">
            <h6>Order Information</h6>
            <ul class="list-unstyled">
                <li><strong>Order Date:</strong> <?= date('M d, Y H:i', strtotime($order['order_date'])) ?></li>
                <li><strong>Status:</strong> <?= ucfirst(safe($order['order_status'])) ?></li>
                <li><strong>Delivery:</strong> <?= safe($order['method_name']) ?></li>
                <li>
                    <strong>Estimated Delivery:</strong>
                    <?= !empty($order['estimated_delivery'])
                        ? date('M d, Y', strtotime($order['estimated_delivery']))
                        : '—' ?>
                </li>
            </ul>
        </div>
    </div>

    <table class="table mt-4">
        <thead>
            <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sumTotal = 0;
            $sumQty   = 0;
            foreach ($items as $it):
                $unit = $it['total_price'] / $it['quantity'];
                $sumTotal += $it['total_price'];
                $sumQty   += $it['quantity'];
            ?>
                <tr>
                    <td><?= safe($it['product_name']) ?></td>
                    <td>₱<?= number_format($unit, 2) ?></td>
                    <td><?= $it['quantity'] ?></td>
                    <td>₱<?= number_format($it['total_price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-3">
        <h5>Total Summary</h5>
        <ul class="list-unstyled">
            <li><strong>Total Items:</strong> <?= $sumQty ?></li>
            <li><strong>Grand Total:</strong> ₱<?= number_format($sumTotal, 2) ?></li>
        </ul>
    </div>
</div>



</body>

</html>