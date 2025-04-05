<?php
// cart_actions.php

require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

header('Content-Type: application/json'); // Always return JSON

// Make sure we have the right request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['add_to_cart'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// If user is not logged in, redirect them
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['redirect' => '../login.php?redirect=shop']);
    exit;
}

// Check membership
$hasMembershipAccess = false;
$stmt = $pdo->prepare("
    SELECT mt.can_access_exclusive
    FROM users u
    LEFT JOIN memberships m ON u.user_id = m.user_id
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$hasMembershipAccess = $result['can_access_exclusive'] ?? false;

// Get product & quantity
$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Check product in DB
$stmt = $pdo->prepare("SELECT stock, is_exclusive FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product || $product['stock'] < 1) {
    echo json_encode(['error' => 'Product not available']);
    exit;
}

// If exclusive, require membership
if ($product['is_exclusive'] && !$hasMembershipAccess) {
    echo json_encode(['error' => 'You need a membership to purchase exclusive items']);
    exit;
}

// Update session cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id] += $quantity;
} else {
    $_SESSION['cart'][$product_id] = $quantity;
}

// Return success
echo json_encode([
    'success' => true,
    'cart_count' => array_sum($_SESSION['cart']),
    'message' => 'Item added to cart!'
]);
exit;
