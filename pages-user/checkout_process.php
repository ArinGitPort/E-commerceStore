<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Validate input
$fullName = trim($_POST['fullName'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$deliveryMethodId = $_POST['delivery_method'] ?? null;
$paymentMethodId = $_POST['payment_method'] ?? null;

if (!$fullName || !$phone || !$address || !$deliveryMethodId || !$paymentMethodId) {
    echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
    exit;
}

// Get cart items
$cartItems = get_cart_details($pdo);
if (empty($cartItems)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Calculate totals
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    $shippingFee = max($subtotal * 0.05, 50);
    $taxAmount = $subtotal * 0.12;
    $grandTotal = $subtotal + $shippingFee + $taxAmount;

    // Insert into orders
    $stmt = $pdo->prepare("
        INSERT INTO orders (customer_id, order_date, shipping_address, total_price, delivery_method_id, order_status)
        VALUES (?, NOW(), ?, ?, ?, 'Pending')
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $address,
        $grandTotal,
        $deliveryMethodId
    ]);
    $orderId = $pdo->lastInsertId();

    // Insert each order item
    $itemStmt = $pdo->prepare("
    INSERT INTO order_details (order_id, product_id, quantity, total_price)
    VALUES (?, ?, ?, ?)
");

    foreach ($cartItems as $item) {
        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['price'] * $item['quantity'] // total_price
        ]);
    }

    // Insert payment record
    $paymentStmt = $pdo->prepare("
        INSERT INTO payments (order_id, payment_method_id, payment_status)
        VALUES (?, ?, 'Pending')
    ");
    $paymentStmt->execute([$orderId, $paymentMethodId]);

    // Clear user's cart (DB + session)
    if (isset($_SESSION['user_id'])) {
        $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    }
    $_SESSION['cart'] = [];

    $pdo->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage()
    ]);
}

//PLEASE REMOVE ERROR CODE HERE
