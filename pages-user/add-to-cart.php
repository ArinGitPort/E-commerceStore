<?php
// add-to-cart.php
require_once __DIR__ . '/../includes/session-init.php';

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages-user/shop.php");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    die("Invalid CSRF token");
}

// Check if add_to_cart is set
if (isset($_POST['add_to_cart'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    // You can add additional checks here (e.g., check if the product exists and has enough stock)

    // Here you would insert or update the cart_items table for logged in users,
    // or update the session cart for guests.

    if (isset($_SESSION['user_id'])) {
        // Update the database for logged-in users
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $stmt->execute([$_SESSION['user_id'], $productId, $quantity, $quantity]);
        // Optionally sync the session cart with the database
    } else {
        // For guest users, update the session cart
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
    }
    
    $_SESSION['message'] = "Item added to cart successfully!";
}

// Redirect back (or to the cart page)
header("Location: ../pages-user/cart.php");
exit;
?>
