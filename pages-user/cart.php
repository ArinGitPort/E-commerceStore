<?php
require_once __DIR__ . '/../includes/session-init.php';
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>
  <?php include '../includes/user-navbar.php'; ?>

  <div class="cart-container">
    <?php if ($message): ?>
      <div class="alert alert-success mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($hasExclusiveItems): ?>
      <div class="alert alert-info mb-4">
        <i class="fas fa-crown"></i> You have exclusive items in your cart!
      </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
      <div class="empty-cart text-center py-5">
        <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
        <h2 class="mb-3">Your cart is empty</h2>
        <p class="text-muted mb-4">Browse our products and add some items to your cart</p>
        <a href="../shop.php" class="btn btn-primary px-4 py-2">
          <i class="fas fa-arrow-left me-2"></i> Continue Shopping
        </a>
      </div>
    <?php else: ?>
      <form action="cart.php" method="post">
        <div class="cart-content">
          <div class="products-column">
            <?php foreach ($cartItems as $item): ?>
              <div class="cart-item">
                <div class="cart-item-image">
                  <?php if ($item['product']['primary_image']): ?>
                    <img src="../assets/images/products/<?= htmlspecialchars($item['product']['primary_image']) ?>" alt="<?= htmlspecialchars($item['product']['product_name']) ?>">
                  <?php else: ?>
                    <div class="no-image text-muted"><i class="fas fa-image fa-2x"></i></div>
                  <?php endif; ?>
                </div>

                <div class="cart-item-details">
                  <?php if ($item['product']['is_exclusive']): ?>
                    <span class="badge bg-warning mb-2">Exclusive</span>
                  <?php endif; ?>
                  <h4 class="mb-2"><?= htmlspecialchars($item['product']['product_name']) ?></h4>
                  <p class="text-muted mb-1"><?= htmlspecialchars($item['product']['category_name']) ?></p>
                  <p class="text-success mb-2"><small>Available: <?= $item['product']['stock'] ?></small></p>
                  <p class="price fw-bold h5 mb-0">₱<?= number_format($item['product']['price'], 2) ?></p>
                </div>

                <div class="cart-item-actions">
                  <div class="quantity-control mb-3">
                    <input type="number" name="quantities[<?= $item['product']['product_id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['product']['stock'] ?>" class="form-control">
                  </div>
                  <button type="submit" name="remove_item" class="btn btn-danger btn-remove" onclick="return confirm('Remove this item from your cart?')">
                    <i class="fas fa-trash-alt me-1"></i> Remove
                  </button>
                  <input type="hidden" name="product_id" value="<?= $item['product']['product_id'] ?>">
                </div>
              </div>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
              <button type="submit" name="update_cart" class="btn btn-outline-primary px-4">
                <i class="fas fa-sync-alt me-2"></i> Update Cart
              </button>
              <a href="shop.php" class="btn btn-outline-secondary px-4">
                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
              </a>
            </div>
          </div>

          <div class="summary-column">
            <div class="summary-card mb-3">
              <h3>Order Summary</h3>
              <div class="summary-row mb-2">
                <span class="text-muted">Subtotal</span>
                <span class="fw-bold">₱<?= number_format($cartTotal, 2) ?></span>
              </div>
              <div class="summary-row mb-2">
                <span class="text-muted">Shipping</span>
                <span class="text-muted">Calculated at checkout</span>
              </div>
              <?php if ($hasExclusiveItems): ?>
                <div class="summary-row mb-3 pb-2 border-bottom">
                  <span class="text-muted">Membership Discount</span>
                  <span class="text-success">-₱0.00</span>
                </div>
              <?php endif; ?>
              <div class="summary-row total pt-2">
                <span class="h5">Estimated Total</span>
                <span class="h5">₱<?= number_format($cartTotal, 2) ?></span>
              </div>
            </div>
            <button type="submit" name="checkout" class="btn btn-dark btn-checkout w-100">
              Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
            </button>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
