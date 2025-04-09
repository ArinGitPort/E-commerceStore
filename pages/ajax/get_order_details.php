<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

// Get order ID from GET parameters
$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    die('Invalid request');
}

// Fetch order information along with customer details and delivery method
$orderStmt = $pdo->prepare("
    SELECT o.*, u.*, dm.method_name 
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    JOIN delivery_methods dm ON o.delivery_method_id = dm.delivery_method_id
    WHERE o.order_id = ?
");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found');
}

// Fetch order items and associated product names
$itemsStmt = $pdo->prepare("
    SELECT od.*, p.product_name 
    FROM order_details od
    JOIN products p ON od.product_id = p.product_id
    WHERE od.order_id = ?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total summary values
$totalSum = 0;
$totalQuantity = 0;
foreach ($items as $item) {
    $totalSum += $item['total_price'];
    $totalQuantity += $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <h6>Customer Information</h6>
                <ul class="list-unstyled">
                    <li><strong>Name:</strong> <?= htmlspecialchars($order['name'] ?? '') ?></li>
                    <li><strong>Email:</strong> <?= htmlspecialchars($order['email'] ?? '') ?></li>
                    <li><strong>Phone:</strong> <?= htmlspecialchars($order['phone'] ?? '') ?></li>
                    <li><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['address'] ?? '')) ?></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Order Information</h6>
                <ul class="list-unstyled">
                    <li><strong>Order Date:</strong> <?= date('M d, Y H:i', strtotime($order['order_date'])) ?></li>
                    <li><strong>Status:</strong> <?= ucfirst($order['order_status']) ?></li>
                    <li><strong>Delivery Method:</strong> <?= htmlspecialchars($order['method_name'] ?? '') ?></li>
                    <li>
                        <strong>Estimated Delivery:</strong> 
                        <?= $order['estimated_delivery'] ? date('M d, Y', strtotime($order['estimated_delivery'])) : 'Not set' ?>
                    </li>
                </ul>
            </div>
        </div>

        <table class="table mt-4">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
                    <td>₱<?= number_format($item['total_price'] / $item['quantity'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₱<?= number_format($item['total_price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Total Summary Section -->
        <div class="mt-4">
            <h5>Total Summary</h5>
            <ul class="list-unstyled">
                <li><strong>Total Items:</strong> <?= $totalQuantity ?></li>
                <li><strong>Grand Total:</strong> ₱<?= number_format($totalSum, 2) ?></li>
            </ul>
        </div>
    </div>
</body>
</html>