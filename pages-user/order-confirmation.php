<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: ../pages-user/shop.php");
    exit;
}

$orderId = $_GET['order_id'];

// Fetch order information
$stmt = $pdo->prepare("
    SELECT o.*, u.name AS customer_name, u.address, pm.method_name AS payment_method, dm.method_name AS delivery_method
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    JOIN payments p ON p.order_id = o.order_id
    JOIN payment_methods pm ON p.payment_method_id = pm.payment_method_id
    JOIN delivery_methods dm ON o.delivery_method_id = dm.delivery_method_id
    WHERE o.order_id = ? AND o.customer_id = ?
");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo "<h2>Order not found.</h2>";
    exit;
}

// Fetch items
$itemStmt = $pdo->prepare("
    SELECT p.product_name, od.quantity, od.total_price, pi.image_url
    FROM order_details od
    JOIN products p ON od.product_id = p.product_id
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    WHERE od.order_id = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - BunniShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
    <style>
        .order-summary img {
            width: 80px;
            height: auto;
            object-fit: contain;
        }
        .order-box {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 12px;
            background-color: #fdfdfd;
        }
    </style>
</head>
<body>
<?php include '../includes/user-navbar.php'; ?>

<div class="container my-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-success">Thank you for your order!</h2>
        <p>Your order has been placed successfully. Below are the details.</p>
    </div>

    <div class="order-box">
        <h4 class="mb-3">Order Information</h4>
        <ul class="list-group mb-4">
            <li class="list-group-item d-flex justify-content-between"><strong>Order ID:</strong> #<?= $order['order_id'] ?></li>
            <li class="list-group-item d-flex justify-content-between"><strong>Order Date:</strong> <?= date('F d, Y h:i A', strtotime($order['order_date'])) ?></li>
            <li class="list-group-item d-flex justify-content-between"><strong>Delivery Method:</strong> <?= $order['delivery_method'] ?></li>
            <li class="list-group-item d-flex justify-content-between"><strong>Payment Method:</strong> <?= $order['payment_method'] ?></li>
            <li class="list-group-item d-flex justify-content-between"><strong>Status:</strong> <?= $order['order_status'] ?></li>
            <?php if ($order['estimated_delivery']): ?>
                <li class="list-group-item d-flex justify-content-between"><strong>Estimated Delivery:</strong> <?= date('F d, Y', strtotime($order['estimated_delivery'])) ?></li>
            <?php endif; ?>
        </ul>

        <h5>Shipping Address</h5>
        <p><?= htmlspecialchars($order['address']) ?></p>
    </div>

    <div class="order-summary mt-5">
        <h4 class="mb-3">Items Ordered</h4>
        <?php foreach ($items as $item): ?>
        <div class="d-flex align-items-center mb-3 border-bottom pb-2">
            <img src="/assets/images/products/<?= $item['image_url'] ?? 'default-product.jpg' ?>" alt="<?= $item['product_name'] ?>" class="me-3">
            <div class="flex-grow-1">
                <h6 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                <small>Quantity: <?= $item['quantity'] ?></small>
            </div>
            <div><strong>₱<?= number_format($item['total_price'], 2) ?></strong></div>
        </div>
        <?php endforeach; ?>

        <div class="text-end mt-3">
            <h5 class="fw-bold">Total: ₱<?= number_format($order['total_price'], 2) ?></h5>
        </div>
    </div>

    <div class="text-center mt-5">
        <a href="../pages-user/shop.php" class="btn btn-outline-primary">Continue Shopping</a>
        <a href="../pages-user/my-orders.php" class="btn btn-success">View My Orders</a>
    </div>
</div>

</body>
</html>
