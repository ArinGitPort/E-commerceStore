<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Re-sync cart data so that the cart count is accurate
if (isset($_SESSION['user_id'])) {
    sync_cart($pdo);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bunniwinkle Navigation</title>
  <link rel="stylesheet" href="../assets/css/navbar.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Logout Confirmation Modal -->
<div class="logout-confirm" id="logoutConfirm">
  <div class="logout-dialog">
    <h3>Are you sure you want to logout?</h3>
    <p>You'll need to sign in again to access your account.</p>
    <div class="logout-actions">
      <button class="logout-btn logout-cancel-btn" id="logoutCancel">Cancel</button>
      <!-- This link calls logout.php which destroys the session and then redirects the user to shop.php -->
      <a href="../pages/logout.php" class="logout-btn logout-confirm-btn">Logout</a>
    </div>
  </div>
</div>

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
      <li class="nav-item"><a href="../pages-user/about.php" class="nav-link">About Us</a></li>
      <li class="nav-item"><a href="../pages-user/contact.php" class="nav-link">Contact Us</a></li>
      <li class="nav-item dropdown">
        <a href="#" class="nav-link">Others <span class="dropdown-icon">▼</span></a>
        <ul class="dropdown-menu">
          <li class="dropdown-item"><a href="../pages-user/faq.php">FAQ</a></li>
          <li class="dropdown-item"><a href="../pages-user/blog.php">Blog</a></li>
          <?php if (isset($_SESSION['user_id'])): ?>
            <li class="dropdown-item"><a href="#" id="navLogout">Logout</a></li>
          <?php else: ?>
            <li class="dropdown-item"><a href="../pages/login.php">Login</a></li>
          <?php endif; ?>
        </ul>
      </li>
    </ul>

    <!-- Icon Section -->
    <div class="nav-icons">
      <a href="../pages-user/search.php" class="icon" title="Search"><i class="fas fa-search"></i></a>
      
      <!-- Dynamic Account Icon: profile if logged in, login if not -->
      <a href="../pages-user/<?= isset($_SESSION['user_id']) ? 'profile.php' : '../pages/login.php' ?>" class="icon" title="<?= isset($_SESSION['user_id']) ? 'My Profile' : 'Login' ?>">
        <i class="fas fa-user"></i>
        <?php if (isset($_SESSION['user_id'])): ?>
          <span class="login-indicator"></span>
        <?php endif; ?>
      </a>
      
      <!-- Logout / Login Icon: show logout icon if logged in, otherwise show login icon -->
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="#" class="icon" title="Logout" id="mobileLogout">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      <?php else: ?>
        <a href="../pages/login.php" class="icon" title="Login" id="mobileLogin">
          <i class="fas fa-sign-in-alt"></i>
        </a>
      <?php endif; ?>

      <!-- Cart Dropdown -->
      <div class="cart-dropdown">
        <a href="cart.php" class="icon cart-icon" title="Cart">
          <i class="fas fa-shopping-bag"></i>
          <span class="cart-count">
            <?= get_cart_count($pdo) ?>
          </span>
        </a>
        <div class="cart-dropdown-content">
          <?php 
          $cart_items = get_cart_details($pdo);
          $total = 0;
          if (!empty($cart_items)):
            foreach ($cart_items as $item):
              $subtotal_item = $item['price'] * $item['quantity'];
              $total += $subtotal_item;
          ?>
            <div class="cart-item">
              <div class="cart-item-info">
                <span class="cart-item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                <span class="cart-item-quantity">
                  <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?>
                </span>
              </div>
              <span class="cart-item-price">₱<?= number_format($subtotal_item, 2) ?></span>
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
        </div>
      </div>
    </div>
  </div>
</nav>

<script>
  // Mobile menu toggle
  document.getElementById('mobileMenuToggle').addEventListener('click', function() {
    this.classList.toggle('active');
    document.getElementById('navMenu').classList.toggle('active');
  });

  document.addEventListener('DOMContentLoaded', function() {
    // Enhanced cart dropdown
    const cartIcon = document.querySelector('.cart-icon');
    const cartDropdown = document.querySelector('.cart-dropdown-content');
    if (cartIcon && cartDropdown) {
      cartIcon.addEventListener('click', function(e) {
        if (window.innerWidth > 768) {
          e.preventDefault();
          cartDropdown.classList.toggle('show');
          // Close other open dropdowns
          document.querySelectorAll('.cart-dropdown-content.show').forEach(dd => {
            if (dd !== cartDropdown) dd.classList.remove('show');
          });
        }
      });
      // Close when clicking outside
      document.addEventListener('click', function(e) {
        if (!e.target.closest('.cart-dropdown')) {
          cartDropdown.classList.remove('show');
        }
      });
    }
    
    // Highlight current page link
    const currentPage = location.pathname.split('/').pop() || 'homepage.php';
    document.querySelectorAll('.nav-link').forEach(link => {
      if (link.getAttribute('href').includes(currentPage)) {
        link.classList.add('active');
      }
    });

    // Logout confirmation
    const logoutConfirm = document.getElementById('logoutConfirm');
    const logoutButtons = [
      document.getElementById('navLogout'),
      document.getElementById('mobileLogout')
    ].filter(btn => btn); // Filter out null if any

    logoutButtons.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        logoutConfirm.style.display = 'flex';
      });
    });

    // Cancel logout
    document.getElementById('logoutCancel').addEventListener('click', function() {
      logoutConfirm.style.display = 'none';
    });

    // Close modal when clicking outside
    logoutConfirm.addEventListener('click', function(e) {
      if (e.target === this) {
        this.style.display = 'none';
      }
    });
  });
</script>
</body>
</html>
