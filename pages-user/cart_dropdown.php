<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

$cart_items = get_cart_details($pdo);
$total = 0;
?>
<?php if (!empty($cart_items)): ?>
  <?php foreach ($cart_items as $item): 
      $subtotal = $item['price'] * $item['quantity'];
      $total += $subtotal;
  ?>
    <div class="cart-item">
      <div class="cart-item-info">
        <span class="cart-item-name"><?= htmlspecialchars($item['product_name']) ?></span>
        <span class="cart-item-quantity">
          <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?>
        </span>
      </div>
      <span class="cart-item-price">₱<?= number_format($subtotal, 2) ?></span>
    </div>
  <?php endforeach; ?>
    <div class="cart-total">
      <span>Total:</span>
      <span>₱<?= number_format($total, 2) ?></span>
    </div>
    <div class="cart-actions">
      <a href="cart.php" class="btn-view-cart">View Cart</a>
      <a href="checkout.php" class="btn-checkout">Checkout</a>
    </div>
<?php else: ?>
  <div class="empty-cart">
    <p>Your cart is empty</p>
    <a href="shop.php" class="btn-shop">Continue Shopping</a>
  </div>
<?php endif; ?>
