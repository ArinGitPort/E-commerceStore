<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// 1. Authentication check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// 2. Pull and trim the new fields
$shippingName    = trim($_POST['shipping_name']    ?? '');
$shippingPhone   = trim($_POST['shipping_phone']   ?? '');
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$deliveryMethod  = (int)($_POST['delivery_method'] ?? 0);
$paymentMethod   = (int)($_POST['payment_method']  ?? 0);

// 3. Validate required fields
if (
    $shippingName    === '' ||
    $shippingPhone   === '' ||
    $shippingAddress === '' ||
    $deliveryMethod  <= 0  ||
    $paymentMethod   <= 0
) {
    echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
    exit;
}

// 4. Get cart details
$cartItems = get_cart_details($pdo);
if (empty($cartItems)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// Set maximum retry attempts for transaction
$maxRetries = 3;
$retryCount = 0;

while ($retryCount < $maxRetries) {
    try {
        // Set transaction isolation level and begin transaction
        $pdo->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
        $pdo->beginTransaction();
        
        // 5. Check stock availability and lock rows for update
        $stockCheck = $pdo->prepare("
            SELECT product_id, stock 
            FROM products 
            WHERE product_id = ? 
            FOR UPDATE
        ");
        
        $insufficientStock = [];
        
        foreach ($cartItems as $item) {
            $stockCheck->execute([$item['product_id']]);
            $product = $stockCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$product || $product['stock'] < $item['quantity']) {
                $insufficientStock[] = $item['product_name'];
            }
        }
        
        // If any product has insufficient stock, abort the transaction
        if (!empty($insufficientStock)) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false, 
                'error' => 'Insufficient stock for: ' . implode(', ', $insufficientStock)
            ]);
            exit;
        }

        // 6. Recalculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $shippingFee = max($subtotal * 0.05, 50);
        $taxAmount   = $subtotal * 0.12;
        $grandTotal  = $subtotal + $shippingFee + $taxAmount;

        // 7. Insert into orders (now including shipping_name & shipping_phone)
        $orderStmt = $pdo->prepare("
            INSERT INTO orders
              (customer_id,
               order_date,
               shipping_name,
               shipping_address,
               shipping_phone,
               total_price,
               delivery_method_id,
               order_status)
            VALUES
              (?, NOW(), ?, ?, ?, ?, ?, 'Pending')
        ");
        $orderStmt->execute([
            $_SESSION['user_id'],
            $shippingName,
            $shippingAddress,
            $shippingPhone,
            $grandTotal,
            $deliveryMethod
        ]);
        $orderId = $pdo->lastInsertId();

        // 8. Insert order items
        $itemStmt = $pdo->prepare("
            INSERT INTO order_details
              (order_id, product_id, quantity, total_price)
            VALUES
              (?, ?, ?, ?)
        ");
        
        // 9. Update product stock
        $updateStock = $pdo->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE product_id = ?
        ");
        
        foreach ($cartItems as $item) {
            $itemStmt->execute([
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price'] * $item['quantity']
            ]);
            
            // Reduce stock for each product
            $updateStock->execute([
                $item['quantity'],
                $item['product_id']
            ]);
        }

        // 10. Record the payment
        $paymentStmt = $pdo->prepare("
            INSERT INTO payments
              (order_id, payment_method_id, payment_status)
            VALUES
              (?, ?, 'Pending')
        ");
        $paymentStmt->execute([$orderId, $paymentMethod]);

        // 11. Clear the cart
        $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")
            ->execute([$_SESSION['user_id']]);
        $_SESSION['cart'] = [];

        // 12. Log the transaction before committing
        $logStmt = $pdo->prepare("
            INSERT INTO transaction_logs 
            (order_id, user_id, amount, status, transaction_date)
            VALUES (?, ?, ?, 'PENDING', NOW())
        ");
        $logStmt->execute([$orderId, $_SESSION['user_id'], $grandTotal]);

        // 13. Commit the transaction
        $pdo->commit();
        
        // 14. Update log status after successful commit
        $pdo->prepare("
            UPDATE transaction_logs 
            SET status = 'COMPLETED' 
            WHERE order_id = ?
        ")->execute([$orderId]);

        // 15. Success response
        echo json_encode(['success' => true, 'order_id' => $orderId]);
        break; // Success, exit the retry loop

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        // Check if it's a deadlock error (MySQL error 1213)
        if ($e->getCode() == 1213 && $retryCount < $maxRetries - 1) {
            $retryCount++;
            usleep(mt_rand(100000, 500000)); // Random delay between 100-500ms
            continue; // Retry the transaction
        }
        
        // Not a deadlock or max retries reached
        error_log('Checkout error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error occurred. Please try again.']);
        exit;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('Checkout error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'An unexpected error occurred. Please try again.']);
        exit;
    }
    
    $retryCount++;
}

// If we've exhausted all retries
if ($retryCount >= $maxRetries) {
    echo json_encode(['success' => false, 'error' => 'Transaction failed after multiple attempts. Please try again later.']);
}