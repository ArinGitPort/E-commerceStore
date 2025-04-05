<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php?redirect=checkout");
    exit;
}

// Get cart items; if empty, redirect to shop
$cartItems = get_cart_details($pdo);
if (empty($cartItems)) {
    header("Location: ../pages-user/shop.php");
    exit;
}

// Debug: Output cart items for inspection (remove in production)
// echo '<div style="background: #f1f1f1; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
// echo '<strong>DEBUG - Cart Items:</strong><br>';
// echo '<pre>';
// print_r($cartItems);
// echo '</pre>';
// echo '</div>';

// Calculate order total and subtotals
$subtotal = 0;
$itemDetails = [];
foreach ($cartItems as $item) {
    // Explicitly cast price and quantity to ensure proper calculation
    $price = isset($item['price']) ? (float)$item['price'] : 0;
    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
    $itemTotal = $price * $quantity;
    $subtotal += $itemTotal;
    $itemDetails[] = [
        'name'     => $item['product_name'],
        'image'    => $item['image'] ?? '../assets/images/default-product.jpg',
        'price'    => $price,
        'quantity' => $quantity,
        'total'    => $itemTotal,
        'category' => $item['category_name']
    ];
}

// Calculate shipping (example: 5% of subtotal or minimum ₱50)
$shippingFee = max($subtotal * 0.05, 50);
// Calculate tax (example: 12% tax)
$taxRate   = 0.12;
$taxAmount = $subtotal * $taxRate;
$grandTotal = $subtotal + $shippingFee + $taxAmount;

// Retrieve available delivery and payment methods
$deliveryMethods = $pdo->query("SELECT * FROM delivery_methods")->fetchAll(PDO::FETCH_ASSOC);
$paymentMethods  = $pdo->query("SELECT * FROM payment_methods")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkout - BunniShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/checkout.css">
  <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
  <style>
    body {
      background: #f9f9f9;
    }
    .checkout-container {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    .section-title {
      border-bottom: 2px solid #ddd;
      padding-bottom: 5px;
      margin-bottom: 15px;
      color: #333;
    }
    .cart-item {
      display: flex;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #eee;
    }
    .cart-item img {
      border-radius: 5px;
    }
    .summary-card {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      /* Removed sticky-top so it scrolls with the page */
    }
    .payment-method {
      cursor: pointer;
      transition: background-color 0.2s;
    }
    .payment-method.active {
      background-color: #e9ecef;
    }
  </style>
</head>
<body>
  <?php include '../includes/user-navbar.php'; ?>

  <div class="container checkout-container my-5">
    <h2 class="mb-4 text-center">Checkout</h2>
    <div class="row">
      <!-- Left Column: Cart Items -->
      <div class="col-lg-7">
        <h4 class="section-title">Your Order</h4>
        <?php foreach ($itemDetails as $item): ?>
        <div class="cart-item">
          <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:80px;">
          <div class="ms-3 flex-grow-1">
            <h5><?= htmlspecialchars($item['name']) ?></h5>
            <p class="text-muted mb-1"><?= htmlspecialchars($item['category']) ?></p>
            <div>
              <span>₱<?= number_format($item['price'], 2) ?></span>
              <span class="mx-2">×</span>
              <span><?= $item['quantity'] ?></span>
            </div>
          </div>
          <div class="fw-bold">
            ₱<?= number_format($item['total'], 2) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Right Column: Order Summary -->
      <div class="col-lg-5">
        <div class="summary-card">
          <h4 class="mb-4">Order Summary</h4>
          <div class="mb-3">
            <div class="d-flex justify-content-between">
              <span>Subtotal</span>
              <span>₱<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Shipping Fee</span>
              <span>₱<?= number_format($shippingFee, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Tax (12%)</span>
              <span>₱<?= number_format($taxAmount, 2) ?></span>
            </div>
          </div>
          <hr>
          <div class="d-flex justify-content-between fw-bold">
            <span>Total</span>
            <span id="orderTotal" class="text-primary">₱<?= number_format($grandTotal, 2) ?></span>
          </div>
          <button type="submit" form="checkoutForm" class="btn btn-primary w-100 mt-4 py-3">
            <i class="fas fa-lock me-2"></i> Complete Order
          </button>
          <div class="text-center mt-3">
            <small class="text-muted">
              <i class="fas fa-shield-alt me-1"></i> Your payment is secure and encrypted
            </small>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Shipping, Delivery & Payment Form -->
    <div class="row mt-5">
      <div class="col">
        <form id="checkoutForm" method="POST" action="checkout_process.php">
          <h4 class="section-title">Shipping Information</h4>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="fullName" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="fullName" name="fullName" required value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="text" class="form-control" id="phone" name="phone" required placeholder="+63 912 345 6789">
            </div>
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Shipping Address</label>
            <textarea class="form-control" id="address" name="address" rows="3" required placeholder="House #, Street, Barangay, City, Province"></textarea>
          </div>
          <div class="mb-4">
            <label for="delivery_method" class="form-label">Delivery Method</label>
            <select name="delivery_method" class="form-select" required>
              <?php foreach ($deliveryMethods as $method): ?>
              <option value="<?= $method['delivery_method_id'] ?>">
                <?= htmlspecialchars($method['method_name']) ?>
                <?php if (isset($method['estimated_days'])): ?>
                  (<?= $method['estimated_days'] ?> days)
                <?php endif; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <h4 class="section-title">Payment Method</h4>
          <div class="mb-4 payment-options">
            <?php foreach ($paymentMethods as $pm): ?>
            <div class="payment-method p-2 border rounded mb-2" onclick="document.getElementById('pm<?= $pm['payment_method_id'] ?>').checked = true;">
              <input class="form-check-input" type="radio" name="payment_method" id="pm<?= $pm['payment_method_id'] ?>" value="<?= $pm['payment_method_id'] ?>" <?= $pm['method_name'] === 'Cash on Delivery' ? 'checked' : '' ?> style="position: absolute; opacity: 0;">
              <div class="d-flex align-items-center">
                <div class="me-2">
                  <?php 
                    if (stripos($pm['method_name'], 'Credit') !== false) {
                      echo '<i class="far fa-credit-card fa-2x"></i>';
                    } elseif (stripos($pm['method_name'], 'PayPal') !== false) {
                      echo '<i class="fab fa-paypal fa-2x"></i>';
                    } elseif (stripos($pm['method_name'], 'GCash') !== false) {
                      echo '<i class="fas fa-mobile-alt fa-2x"></i>';
                    } else {
                      echo '<i class="fas fa-money-bill-wave fa-2x"></i>';
                    }
                  ?>
                </div>
                <div>
                  <div class="fw-bold"><?= htmlspecialchars($pm['method_name']) ?></div>
                  <small class="text-muted"><?= $pm['description'] ?? 'Standard payment method' ?></small>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- JS Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Highlight selected payment method
    $('input[name="payment_method"]').change(function() {
      $('.payment-method').removeClass('active');
      $(this).closest('.payment-method').addClass('active');
    });
    // Trigger initial highlight for checked method
    $('input[name="payment_method"]:checked').closest('.payment-method').addClass('active');

    // AJAX Checkout Submission
    $('#checkoutForm').on('submit', function(e) {
      e.preventDefault();
      const form = $(this);
      const btn = form.find('button[type="submit"]');
      const originalText = btn.html();
      btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Processing Order...');
      
      $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            window.location.href = 'order-confirmation.php?order_id=' + response.order_id;
          } else {
            alert(response.error || 'An error occurred. Please try again.');
          }
        },
        error: function() {
          alert('Failed to connect to server. Please check your connection.');
        },
        complete: function() {
          btn.prop('disabled', false).html(originalText);
        }
      });
    });
  </script>
</body>
</html>
