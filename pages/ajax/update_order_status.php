<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';



// Validate input
$orderId = $_POST['order_id'] ?? null;
$newStatus = $_POST['new_status'] ?? null;

if (!$orderId || !$newStatus) {
    echo 'Missing parameters';
    exit;
}

// Validate status against allowed values
$allowedStatuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled', 'Returned'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo 'Invalid status';
    exit;
}

try {
    // Update the order status
    $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->execute([$newStatus, $orderId]);

    if ($stmt->rowCount() > 0) {
        // Log this change
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([
            $_SESSION['user_id'],
            "Updated order status to $newStatus",
            'orders',
            $orderId
        ]);

        echo 'success';
    } else {
        echo 'No changes made';
    }
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}