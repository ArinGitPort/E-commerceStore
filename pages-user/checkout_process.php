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

try {
    $pdo->beginTransaction();
    
    // 5. CRITICAL FIX: Check inventory before proceeding
    $inventoryCheckFailed = false;
    $outOfStockItems = [];
    
    // Prepare statement for inventory check
    $stockStmt = $pdo->prepare("SELECT product_id, stock_quantity FROM products WHERE product_id = ? FOR UPDATE");
    
    // Check each product's inventory
    foreach ($cartItems as $item) {
        $stockStmt->execute([$item['product_id']]);
        $product = $stockStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $inventoryCheckFailed = true;
            $outOfStockItems[] = "Product not found: {$item['product_name']}";
            continue;
        }
        
        if ($product['stock_quantity'] < $item['quantity']) {
            $inventoryCheckFailed = true;
            $outOfStockItems[] = "{$item['product_name']} (Available: {$product['stock_quantity']}, Requested: {$item['quantity']})";
        }
    }
    
    // If any product doesn't have enough stock, abort transaction
    if ($inventoryCheckFailed) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'error' => 'Some items are no longer in stock or have insufficient quantity',
            'items' => $outOfStockItems
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

    // 7. Insert into orders
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

    // 8. Insert order items and update inventory
    $itemStmt = $pdo->prepare("
        INSERT INTO order_details
          (order_id, product_id, quantity, total_price)
        VALUES
          (?, ?, ?, ?)
    ");
    
    // Prepare statement for inventory update
    $updateStockStmt = $pdo->prepare("
        UPDATE products 
        SET stock_quantity = stock_quantity - ? 
        WHERE product_id = ?
    ");
    
    foreach ($cartItems as $item) {
        // Add item to order
        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['price'] * $item['quantity']
        ]);
        
        // Update inventory
        $updateStockStmt->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }

    // 9. Record the payment
    $paymentStmt = $pdo->prepare("
        INSERT INTO payments
          (order_id, payment_method_id, payment_status)
        VALUES
          (?, ?, 'Pending')
    ");
    $paymentStmt->execute([$orderId, $paymentMethod]);

    // 10. Clear the cart
    $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")
        ->execute([$_SESSION['user_id']]);
    $_SESSION['cart'] = [];

    $pdo->commit();

    // 11. Success response
    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (\Throwable $e) {
    $pdo->rollBack();
    // Log the error for debugging but don't expose details to users
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred. Please try again.']);
}