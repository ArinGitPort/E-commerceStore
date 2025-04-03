

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bunniwinkle Navigation</title>
  <link rel="stylesheet" href="../assets/css/navbar.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">

    <!-- Logo -->
    <a href="../pages-user/homepage.php">
      <img src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Bunniwinkle Logo" class="nav-logo" />
    </a>

    <!-- Hamburger (mobile) -->
    <div class="mobile-menu-toggle" id="mobileMenuToggle">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <!-- Navigation Links -->
    <ul class="nav-menu" id="navMenu">
      <li class="nav-item"><a href="../pages-user/shop.php" class="nav-link">Shop</a></li>
      <li class="nav-item"><a href="#" class="nav-link">About Us</a></li>
      <li class="nav-item"><a href="#" class="nav-link">Contact Us</a></li>
      <li class="nav-item dropdown">
        <a href="#" class="nav-link">Others <span class="dropdown-icon">▼</span></a>
        <ul class="dropdown-menu">
          <li class="dropdown-item"><a href="#">FAQ</a></li>
          <li class="dropdown-item"><a href="#">Blog</a></li>
          <li class="dropdown-item"><a href="#">Resources</a></li>
        </ul>
      </li>
    </ul>

    <!-- Icon Section -->
    <div class="nav-icons">
      <a href="#" class="icon"><i class="fas fa-search"></i></a>
      <a href="#" class="icon"><i class="fas fa-user"></i></a>
      <div class="cart-dropdown">
        <a href="../pages-user/cart.php" class="icon cart-icon">
          <i class="fas fa-shopping-bag"></i>
          <span class="cart-count"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?></span>
        </a>
        <div class="cart-dropdown-content">
          <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <div class="cart-items">
              <?php 
              $total = 0;
              foreach ($_SESSION['cart'] as $product_id => $quantity):
                // Get product details from database
                $stmt = $pdo->prepare("SELECT product_name, price FROM products WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                if ($product):
                  $subtotal = $product['price'] * $quantity;
                  $total += $subtotal;
              ?>
                <div class="cart-item">
                  <div class="cart-item-info">
                    <span class="cart-item-name"><?= htmlspecialchars($product['product_name']) ?></span>
                    <span class="cart-item-quantity"><?= $quantity ?> × ₱<?= number_format($product['price'], 2) ?></span>
                  </div>
                  <span class="cart-item-price">₱<?= number_format($subtotal, 2) ?></span>
                </div>
              <?php endif; endforeach; ?>
            </div>
            <div class="cart-total">
              <span>Total:</span>
              <span>₱<?= number_format($total, 2) ?></span>
            </div>
            <div class="cart-actions">
              <a href="../pages-user/cart.php" class="btn-view-cart">View Cart</a>
              <a href="../pages-user/checkout.php" class="btn-checkout">Checkout</a>
            </div>
          <?php else: ?>
            <div class="empty-cart">
              <p>Your cart is empty</p>
              <a href="../pages-user/shop.php" class="btn-shop">Continue Shopping</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</nav>

<script>
  // Mobile menu toggle
  document.getElementById('mobileMenuToggle').addEventListener('click', function () {
    this.classList.toggle('active');
    document.getElementById('navMenu').classList.toggle('active');
  });

  // Cart dropdown functionality
  document.addEventListener('DOMContentLoaded', function() {
    const cartIcon = document.querySelector('.cart-icon');
    const cartDropdown = document.querySelector('.cart-dropdown-content');
    
    cartIcon.addEventListener('click', function(e) {
      if (window.innerWidth > 768) { // Only prevent default on desktop
        e.preventDefault();
        cartDropdown.classList.toggle('show');
      }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.cart-dropdown')) {
        cartDropdown.classList.remove('show');
      }
    });
  });
</script>

</body>
</html>