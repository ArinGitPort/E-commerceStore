<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

// Add your filtering and sorting logic as needed for reports.
$query = "SELECT * FROM archived_orders ORDER BY order_date DESC";
$stmt = $pdo->query($query);
$archivedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- In your HTML for report-generation.php -->
<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Order Date</th>
            <th>Total Price</th>
            <!-- Other columns -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($archivedOrders as $order): ?>
            <tr>
                <td>#<?= $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['customer_id']) ?></td>
                <td><?= $order['order_date'] ?></td>
                <td>₱<?= number_format($order['total_price'], 2) ?></td>
                <!-- Other columns -->
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
