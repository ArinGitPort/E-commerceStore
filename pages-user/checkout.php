<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../pages/login.php?redirect=checkout");
  exit;
}

$stmt = $pdo->prepare("SELECT name, phone, address FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userRow = $stmt->fetch();

$userFullName = $userRow['name'] ?? '';
$userPhone = $userRow['phone'] ?? '';
$userAddress = $userRow['address'] ?? '';

$cartItems = get_cart_details($pdo);
if (empty($cartItems)) {
  header("Location: ../pages-user/shop.php");
  exit;
}

$subtotal = 0;
$itemDetails = [];

foreach ($cartItems as $item) {
  $price = (float)($item['price'] ?? 0);
  $quantity = (int)($item['quantity'] ?? 0);
  $itemTotal = $price * $quantity;
  $subtotal += $itemTotal;

  $imageUrl = '../assets/images/default-product.jpg';
  if (!empty($item['primary_image']['image_url'])) {
    $imageUrl = '/assets/images/products/' . $item['primary_image']['image_url'];
  }

  $itemDetails[] = [
    'name' => $item['product_name'],
    'image' => $imageUrl,
    'price' => $price,
    'quantity' => $quantity,
    'total' => $itemTotal,
    'category' => $item['category_name']
  ];
}

$checkpointSubtotal = $subtotal;
$shippingFee = max($checkpointSubtotal * 0.05, 50);
$taxRate = 0.12;
$taxAmount = $checkpointSubtotal * $taxRate;
$grandTotal = $checkpointSubtotal + $shippingFee + $taxAmount;

$deliveryMethods = $pdo->query("SELECT * FROM delivery_methods")->fetchAll(PDO::FETCH_ASSOC);
$paymentMethods = $pdo->query("SELECT * FROM payment_methods")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Checkout - BunniShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/checkout.css">
  <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
  <!-- SweetAlert2 for beautiful popups -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  <?php include '../includes/user-navbar.php'; ?>

  <!-- ✅ Start of Form -->
  <form id="checkoutForm" method="POST" action="checkout_process.php">
    <div class="container checkout-container my-5">
      <h2 class="mb-4 text-center">Checkout</h2>
      <div class="row">
        <div class="col-lg-7">
          <h4 class="section-title">Your Order</h4>
          <?php foreach ($itemDetails as $item): ?>
            <div class="cart-item d-flex align-items-center mb-3">
              <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:80px; object-fit:contain;">
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

        <div class="col-lg-5">
          <div class="summary-card">
            <h4 class="mb-4">Order Summary</h4>
            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <span>Subtotal</span>
                <span>₱<?= number_format($checkpointSubtotal, 2) ?></span>
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
              <span id="orderTotal" class="text-primary">
                ₱<?= number_format($grandTotal, 2) ?>
              </span>
            </div>
            <!-- ✅ Submit button inside the form -->
            <button type="submit" class="btn btn-primary w-100 mt-4 py-3">
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

      <div class="row mt-5">
        <div class="col">
          <h4 class="section-title">Shipping Information</h4>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="shippingName" class="form-label">Full Name</label>
              <input
                type="text"
                class="form-control"
                id="shippingName"
                name="shipping_name"
                required
                value="<?= htmlspecialchars($userFullName) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label for="shippingPhone" class="form-label">Phone Number</label>
              <input
                type="text"
                class="form-control"
                id="shippingPhone"
                name="shipping_phone"
                required
                value="<?= htmlspecialchars($userPhone) ?>"
                placeholder="+63 912 345 6789">
            </div>
          </div>
          <div class="mb-3">
            <label for="shippingAddress" class="form-label">Shipping Address</label>
            <textarea
              class="form-control"
              id="shippingAddress"
              name="shipping_address"
              rows="3"
              required
              placeholder="House #, Street, Barangay, City, Province"><?= htmlspecialchars($userAddress) ?></textarea>
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
              <label class="payment-method p-2 border rounded mb-2 d-block" style="cursor: pointer;">
                <input class="form-check-input" type="radio" name="payment_method" value="<?= $pm['payment_method_id'] ?>" <?= $pm['method_name'] === 'Cash on Delivery' ? 'checked' : '' ?> style="position: absolute; opacity: 0;">
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
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </form>
  <!-- ✅ End of Form -->

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // highlight selected payment method
    $('input[name="payment_method"]').change(function() {
      $('.payment-method').removeClass('active');
      $(this).closest('.payment-method').addClass('active');
    }).filter(':checked').closest('.payment-method').addClass('active');

    // confirmation dialog
    $('#checkoutForm').on('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Confirm Your Order',
        html: `
        <div class="text-start">
          <p>You are about to place an order for <strong>₱<?= number_format($grandTotal, 2) ?></strong>.</p>
          <p>Please review your shipping information:</p>
          <ul class="text-muted">
            <li>Name: ${$('#shippingName').val()}</li>
            <li>Address: ${$('#shippingAddress').val()}</li>
            <li>Phone: ${$('#shippingPhone').val()}</li>
          </ul>
        </div>
      `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, place order!',
        cancelButtonText: 'Review order',
        backdrop: `rgba(0,0,0,0.7) url("/assets/images/loading.gif") center top no-repeat`
      }).then(result => {
        if (result.isConfirmed) {
          const form = $(this),
            btn = form.find('button[type="submit"]');
          const txt = btn.html();
          btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
          $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success(resp) {
              if (resp.success) window.location = 'order-confirmation.php?order_id=' + resp.order_id;
              else Swal.fire('Error', resp.error || 'Please try again', 'error');
            },
            error() {
              Swal.fire('Error', 'Server error, check connection', 'error');
            },
            complete() {
              btn.prop('disabled', false).html(txt);
            }
          });
        }
      });
    });
  </script>
</body>

</html>