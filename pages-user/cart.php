<?php
session_start();
require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=cart");
    exit;
}

// Get user's cart items
$cartItems = [];
$cartTotal = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cartProductIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($cartProductIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name, 
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.product_id IN ($placeholders)
    ");
    $stmt->execute($cartProductIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['product_id']];
        $subtotal = $product['price'] * $quantity;
        $cartTotal += $subtotal;
        
        $cartItems[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $productId => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = $quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        }
        $_SESSION['message'] = "Cart updated successfully!";
        header("Location: cart.php");
        exit;
    }
    
    if (isset($_POST['remove_item'])) {
        $productId = (int)$_POST['product_id'];
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['message'] = "Item removed from cart!";
        }
        header("Location: cart.php");
        exit;
    }
    
    if (isset($_POST['checkout'])) {
        header("Location: checkout.php");
        exit;
    }
}

// Display messages
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - BunniShop</title>
    <link rel="stylesheet" href="../assets/css/cart.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
</head>
<body>
    <?php include '../includes/user-navbar.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1>Your Shopping Cart</h1>
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Browse our products and add some items to your cart</p>
                <a href="../shop.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <form action="cart.php" method="post">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td class="product-info">
                                        <div class="product-image">
                                            <?php if ($item['product']['primary_image']): ?>
                                                <img src="../assets/images/products/<?= htmlspecialchars($item['product']['primary_image']) ?>" 
                                                     alt="<?= htmlspecialchars($item['product']['product_name']) ?>">
                                            <?php else: ?>
                                                <div class="no-image">No Image</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-details">
                                            <h3><?= htmlspecialchars($item['product']['product_name']) ?></h3>
                                            <p class="category"><?= htmlspecialchars($item['product']['category_name']) ?></p>
                                            <?php if ($item['product']['is_exclusive']): ?>
                                                <span class="badge bg-info">Exclusive</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="price">₱<?= number_format($item['product']['price'], 2) ?></td>
                                    <td class="quantity">
                                        <input type="number" name="quantities[<?= $item['product']['product_id'] ?>]" 
                                               value="<?= $item['quantity'] ?>" min="1" class="form-control">
                                    </td>
                                    <td class="subtotal">₱<?= number_format($item['subtotal'], 2) ?></td>
                                    <td class="action">
                                        <button type="submit" name="remove_item" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Remove this item from your cart?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <input type="hidden" name="product_id" value="<?= $item['product']['product_id'] ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₱<?= number_format($cartTotal, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <div class="summary-row total">
                            <span>Estimated Total</span>
                            <span>₱<?= number_format($cartTotal, 2) ?></span>
                        </div>
                        <div class="cart-actions">
                            <button type="submit" name="update_cart" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Update Cart
                            </button>
                            <button type="submit" name="checkout" class="btn btn-primary">
                                Proceed to Checkout <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>