<?php
session_start();
require_once '../config/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=cart");
    exit;
}

// Check membership access
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

// Get user's cart items with membership checks
$cartItems = [];
$cartTotal = 0;
$hasExclusiveItems = false;

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
        
        // Check if user can access exclusive products
        if ($product['is_exclusive'] && !$hasMembershipAccess) {
            $_SESSION['cart_message'] = "Some items were removed - membership required for exclusive products";
            unset($_SESSION['cart'][$product['product_id']]);
            continue;
        }
        
        // Check stock availability
        if ($product['stock'] < $quantity) {
            $quantity = min($quantity, $product['stock']);
            $_SESSION['cart'][$product['product_id']] = $quantity;
            if ($quantity == 0) {
                unset($_SESSION['cart'][$product['product_id']]);
                continue;
            }
        }
        
        $subtotal = $product['price'] * $quantity;
        $cartTotal += $subtotal;
        
        $cartItems[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
        
        if ($product['is_exclusive']) {
            $hasExclusiveItems = true;
        }
    }
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $productId => $quantity) {
            $productId = (int)$productId;
            $quantity = (int)$quantity;
            
            // Verify product exists and is available
            $stmt = $pdo->prepare("SELECT stock, is_exclusive FROM products WHERE product_id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product || $product['stock'] < 1) {
                unset($_SESSION['cart'][$productId]);
                continue;
            }
            
            // Check exclusive product access
            if ($product['is_exclusive'] && !$hasMembershipAccess) {
                unset($_SESSION['cart'][$productId]);
                continue;
            }
            
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = min($quantity, $product['stock']);
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
        if (empty($_SESSION['cart'])) {
            $_SESSION['message'] = "Your cart is empty!";
            header("Location: cart.php");
            exit;
        }
        
        // Additional checkout validation can go here
        
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
    <style>
        /* Split layout styles */
        .cart-content {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }
        
        .products-column {
            flex: 2;
        }
        
        .summary-column {
            flex: 1;
            position: sticky;
            top: 20px;
        }
        
        @media (max-width: 992px) {
            .cart-content {
                flex-direction: column;
            }
            
            .products-column,
            .summary-column {
                width: 100%;
            }
        }
        
        /* Enhanced cart item styling */
        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            display: flex;
            gap: 1.5rem;
        }
        
        .cart-item-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .cart-item-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        /* Quantity controls */
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-control input {
            width: 60px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include '../includes/user-navbar.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1>Your Shopping Cart</h1>
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($hasExclusiveItems): ?>
                <div class="alert alert-info">
                    <i class="fas fa-crown"></i> You have exclusive items in your cart!
                </div>
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
                <div class="cart-content">
                    <div class="products-column">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <?php if ($item['product']['primary_image']): ?>
                                        <img src="../assets/images/products/<?= htmlspecialchars($item['product']['primary_image']) ?>" 
                                             alt="<?= htmlspecialchars($item['product']['product_name']) ?>">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="cart-item-details">
                                    <?php if ($item['product']['is_exclusive']): ?>
                                        <span class="badge bg-warning mb-2">Exclusive</span>
                                    <?php endif; ?>
                                    
                                    <h3><?= htmlspecialchars($item['product']['product_name']) ?></h3>
                                    <p class="text-muted"><?= htmlspecialchars($item['product']['category_name']) ?></p>
                                    <p>Available: <?= $item['product']['stock'] ?></p>
                                    <p class="price fw-bold">₱<?= number_format($item['product']['price'], 2) ?></p>
                                </div>
                                
                                <div class="cart-item-actions">
                                    <div class="quantity-control">
                                        <input type="number" name="quantities[<?= $item['product']['product_id'] ?>]" 
                                               value="<?= $item['quantity'] ?>" min="1" max="<?= $item['product']['stock'] ?>" 
                                               class="form-control">
                                    </div>
                                    
                                    <button type="submit" name="remove_item" class="btn btn-sm btn-danger mt-3"
                                            onclick="return confirm('Remove this item from your cart?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                    <input type="hidden" name="product_id" value="<?= $item['product']['product_id'] ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <button type="submit" name="update_cart" class="btn btn-outline-primary">
                                <i class="fas fa-sync-alt"></i> Update Cart
                            </button>
                            <a href="shop.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                    
                    <div class="summary-column">
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
                            <?php if ($hasExclusiveItems): ?>
                                <div class="summary-row">
                                    <span>Membership Discount</span>
                                    <span>-₱0.00</span>
                                </div>
                            <?php endif; ?>
                            <div class="summary-row total">
                                <span>Estimated Total</span>
                                <span>₱<?= number_format($cartTotal, 2) ?></span>
                            </div>
                            <button type="submit" name="checkout" class="btn btn-primary w-100 mt-3 py-2">
                                Proceed to Checkout <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>