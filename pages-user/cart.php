<?php
// cart.php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Initialize variables
$message = '';
$cart_message = '';
$hasExclusiveItems = false;
$cartItems = [];
$cartTotal = 0;

// Check login status
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=cart");
    exit;
}

sync_cart($pdo);


// Handle form submissions (fallback for update/remove via full page submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_update'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    try {
        $pdo->beginTransaction();

        // 1) Update cart quantities
        if (isset($_POST['update_cart']) && !empty($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $productId => $quantity) {
                $productId = (int)$productId;
                $quantity = max(1, (int)$quantity);

                // Update cart_items table
                $stmt = $pdo->prepare("
                    UPDATE cart_items 
                    SET quantity = ?
                    WHERE user_id = ? AND product_id = ?
                ");
                $stmt->execute([$quantity, $_SESSION['user_id'], $productId]);

                // Also update session
                $_SESSION['cart'][$productId] = $quantity;
            }
            $_SESSION['message'] = "Cart updated successfully!";
        }

        // 2) Remove an item from cart
        if (isset($_POST['remove_item'])) {
            $productId = (int)$_POST['remove_item'];

            // Remove from DB
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $productId]);

            // Remove from session
            unset($_SESSION['cart'][$productId]);

            $_SESSION['message'] = "Item removed from cart!";
        }

        if (isset($_POST['checkout'])) {
            // Check if user still has access to all exclusive items in the cart
            $stmt = $pdo->prepare("
                SELECT p.product_name
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.product_id
                LEFT JOIN memberships m ON ci.user_id = m.user_id
                LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
                WHERE ci.user_id = ? 
                  AND p.is_exclusive = TRUE
                  AND (mt.can_access_exclusive IS NULL OR mt.can_access_exclusive = FALSE)
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $restrictedItems = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
            if (!empty($restrictedItems)) {
                $_SESSION['message'] = "You no longer have access to these exclusive item(s): " . implode(', ', $restrictedItems);
                header("Location: cart.php");
                exit;
            }
        
            // Commit and proceed to checkout
            $pdo->commit();
            header("Location: ../pages-user/checkout.php");
            exit;
        }
        

        // 3) Proceed to Checkout
        if (isset($_POST['checkout'])) {
            // Commit the transaction so any updates/removals are finalized
            $pdo->commit();

            // Redirect to the checkout page
            header("Location: ../pages-user/checkout.php");
            exit;
        }

        // If we didn't checkout, commit changes and reload the cart page
        $pdo->commit();
        header("Location: cart.php");
        exit;
    } catch (PDOException $e) {
        // Roll back if anything fails
        $pdo->rollBack();
        $_SESSION['message'] = "Error updating cart: " . $e->getMessage();
        header("Location: cart.php");
        exit;
    }
}

// 4) Retrieve cart items (outside the POST block, for normal page load)
$cartItems = get_cart_details($pdo);

// Calculate totals
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
    if ($item['is_exclusive']) {
        $hasExclusiveItems = true;
    }
}

// Handle any messages (e.g. “Cart updated”, “Item removed”, etc.)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_SESSION['cart_message'])) {
    $cart_message = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
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
    <style>
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 5px;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
        }

        .cart-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>

<body>
    <?php include '../includes/user-navbar.php'; ?>

    <div class="container py-5 cart-container">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($hasExclusiveItems): ?>
            <div class="alert alert-info">
                <i class="fas fa-crown"></i> You have exclusive items in your cart!
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <!-- Empty cart display -->
            <div class="container d-flex justify-content-center align-items-center" style="min-height: 50vh;">
                <div class="text-center">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h2 class="fw-bold mb-2">Your cart is empty</h2>
                    <p class="text-muted mb-4">
                        Looks like you haven't added anything to your cart yet.
                    </p>
                    <a href="../pages-user/shop.php" class="btn btn-primary btn-lg px-4 py-2">
                        <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Cart form for updates/removals/checkout -->
            <form action="cart.php" method="post" id="cartForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="row">
                    <!-- Left column: Items -->
                    <div class="col-lg-8">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item row align-items-center" data-product-id="<?= $item['product_id'] ?>">
                                <div class="col-md-2">
                                    <img
                                        src="<?= isset($item['primary_image'])
                                                    ? '/assets/images/products/' . htmlspecialchars($item['primary_image']['image_url'])
                                                    : '../assets/images/default-product.jpg' ?>"
                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                        class="img-fluid cart-item-image">

                                </div>
                                <div class="col-md-4">
                                    <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                    <p class="text-muted mb-1"><?= htmlspecialchars($item['category_name']) ?></p>
                                    <?php if ($item['is_exclusive']): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-crown"></i> Exclusive
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <input
                                        type="number"
                                        name="quantities[<?= $item['product_id'] ?>]"
                                        value="<?= $item['quantity'] ?>"
                                        min="1"
                                        max="<?= $item['stock'] ?>"
                                        class="form-control quantity-input">
                                </div>
                                <div class="col-md-2 text-center">
                                    <p class="mb-0">₱<?= number_format($item['price'], 2) ?></p>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button
                                        type="submit"
                                        name="remove_item"
                                        class="btn btn-outline-danger btn-sm"
                                        value="<?= $item['product_id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Update / Continue Buttons -->
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            <button
                                type="submit"
                                name="update_cart"
                                class="btn btn-outline-primary px-4">
                                <i class="fas fa-sync-alt me-2"></i> Update Cart
                            </button>
                            <a href="../pages-user/shop.php" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                            </a>
                        </div>
                    </div>

                    <!-- Right column: Summary & Checkout -->
                    <div class="col-lg-4">
                        <div class="summary-card mt-4 mt-lg-0">
                            <h4 class="mb-4">Order Summary</h4>
                            <div class="d-flex justify-content-between fw-bold mb-4">
                                <span>Total:</span>
                                <span id="orderTotal">₱<?= number_format($cartTotal, 2) ?></span>
                            </div>
                            <!-- 'Proceed to Checkout' triggers if (isset($_POST['checkout'])) in the code above -->
                            <button
                                type="submit"
                                name="checkout"
                                class="btn btn-primary w-100 py-2">
                                Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- jQuery & Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dynamic Quantity Update via AJAX
        $(document).ready(function() {
            $('.quantity-input').on('change', function() {
                const input = $(this);
                const productId = input.closest('.cart-item').data('product-id');
                const newQuantity = input.val();
                const csrfToken = $('input[name=\"csrf_token\"]').val();

                $.ajax({
                    url: 'cart_actions.php', // a file that handles 'update_cart_item'
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        csrf_token: csrfToken,
                        action: 'update_cart_item',
                        product_id: productId,
                        quantity: newQuantity,
                        ajax_update: true
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update the order summary total on the page
                            $('#orderTotal').text(
                                '₱' + Number(response.newTotal).toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })
                            );
                        } else if (response.error) {
                            alert(response.error);
                        }
                    },
                    error: function() {
                        alert('Failed to update cart. Please try again.');
                    }
                });
            });
        });
    </script>
</body>

</html>