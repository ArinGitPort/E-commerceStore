<?php
// ajax/update_order_status.php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// 1) Auth check
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// 2) Decode payload
$payload = json_decode(file_get_contents('php://input'), true);
$orderId = isset($payload['order_id']) ? (int)$payload['order_id'] : 0;
$newStatus = $payload['new_status'] ?? '';
$cancelReason = $payload['cancel_reason'] ?? '';

// Fetch order details
$stmt = $pdo->prepare("SELECT order_status FROM orders WHERE order_id = ? AND customer_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found or unauthorized']);
    exit;
}

try {
    if ($newStatus === 'Cancelled') {
        // Handle cancellation
        if (!in_array($order['order_status'], ['Pending', 'Processing'])) {
            echo json_encode(['success' => false, 'error' => 'Order cannot be cancelled at this stage']);
            exit;
        }

        // Simplified update - remove the cancel_reason field that doesn't exist
        $update = $pdo->prepare("
            UPDATE orders 
            SET order_status = 'Cancelled',
                modified_at = NOW()
            WHERE order_id = ?
        ");
        $update->execute([$orderId]);
        
        // Check if the audit_logs functionality is properly setup before using it
        try {
            // Skip the audit log if there's any issue
            if (isset($_SESSION['user_id'])) {
                $logData = json_encode(['status' => 'Cancelled', 'reason' => $cancelReason]);
                
                $logCancel = $pdo->prepare("
                    INSERT INTO audit_logs 
                    (user_id, action, table_name, record_id, ip_address, affected_data, action_type)
                    VALUES (?, ?, 'orders', ?, ?, ?, 'UPDATE')
                ");
                $logCancel->execute([
                    $_SESSION['user_id'],
                    'Order cancelled',
                    $orderId,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $logData,
                    'UPDATE'
                ]);
            }
        } catch (Exception $logError) {
            // Just log this error but don't stop the cancellation process
            error_log("Failed to log cancellation: " . $logError->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
    } elseif ($newStatus === 'Received') {
        // Handle archiving
        if ($order['order_status'] !== 'Shipped') {
            echo json_encode(['success' => false, 'error' => "Order must be shipped before it can be received"]);
            exit;
        }

        $pdo->beginTransaction();

        // Get order items for stock reduction
        $itemSel = $pdo->prepare("
            SELECT product_id, quantity, total_price, created_at, modified_at
            FROM order_details
            WHERE order_id = ?
        ");
        $itemSel->execute([$orderId]);
        $orderItems = $itemSel->fetchAll(PDO::FETCH_ASSOC);

        // Update stock levels for each product
        $updateStock = $pdo->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE product_id = ?
        ");

        // Check if we have enough stock before reducing
        $checkStock = $pdo->prepare("
            SELECT product_id, product_name, stock 
            FROM products 
            WHERE product_id = ? AND stock < ?
        ");

        foreach ($orderItems as $item) {
            $checkStock->execute([$item['product_id'], $item['quantity']]);
            $insufficientStock = $checkStock->fetch(PDO::FETCH_ASSOC);
            
            if ($insufficientStock) {
                throw new Exception("Insufficient stock for product ID: " . $insufficientStock['product_id'] . " (" . $insufficientStock['product_name'] . ")");
            }
            
            // Update the stock level
            $updateStock->execute([
                $item['quantity'],
                $item['product_id']
            ]);
        }

        // Archive the order by moving to the archived_orders table
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

        // Archive each line item from the order
        $itemIns = $pdo->prepare("
            INSERT INTO archived_order_details 
                (order_id, product_id, quantity, total_price, created_at, modified_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($orderItems as $row) {
            $itemIns->execute([
                $orderId,
                $row['product_id'],
                $row['quantity'],
                $row['total_price'],
                $row['created_at'],
                $row['modified_at']
            ]);
        }

        // Delete the original order from the orders and order_details tables
        $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")->execute([$orderId]);
        $pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$orderId]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Order archived and inventory updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log the specific error for debugging
    error_log("Order status update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>