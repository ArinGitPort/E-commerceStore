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
  <link rel="stylesheet" href="../assets/css/about-us.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

  <!-- Logout Confirmation Modal -->
  <div class="logout-confirm" id="logoutConfirm">
    <div class="logout-dialog">
      <h3>Are you sure you want to logout?</h3>
      <p>You'll need to sign in again to access your account.</p>
      <div class="logout-actions">
        <button class="logout-btn logout-cancel-btn" id="logoutCancel">Cancel</button>
        <!-- Changed to button with no href to intercept click -->
        <a href="#" id="logoutConfirmBtn" class="logout-btn logout-confirm-btn">Logout</a>
      </div>
    </div>
  </div>

  <!-- Login Confirmation Modal -->
  <div class="login-confirm" id="loginConfirm">
    <div class="login-dialog">
      <h3>Do you want to login?</h3>
      <p>You need to sign in to access your account/cart.</p>
      <div class="login-actions">
        <button class="login-btn login-cancel-btn" id="loginCancel">Cancel</button>
        <!-- Changed to button with no href to intercept click -->
        <a href="#" id="loginConfirmBtn" class="login-btn login-confirm-btn">Login</a>
      </div>
    </div>
  </div>

  <!-- Redirecting Modal (Bootstrap) -->
  <div class="modal fade" id="redirectModal" tabindex="-1" aria-labelledby="redirectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <p id="redirectMessage">Redirecting...</p>
        </div>
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
        <li class="nav-item"><a href="../pages-user/about-us.php" class="nav-link">About Us</a></li>
        <li class="nav-item"><a href="../pages-user/contact.php" class="nav-link">Contact Us</a></li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link">Others <span class="dropdown-icon">▼</span></a>
          <ul class="dropdown-menu">
            <li class="dropdown-item"><a href="../pages-user/faq.php">FAQ</a></li>
            <li class="dropdown-item"><a href="../pages-user/blog.php">Blog</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
              <li class="dropdown-item"><a href="#" id="navLogout">Logout</a></li>
            <?php else: ?>
              <!-- Added common class "login-trigger" so this dropdown login also triggers the modal -->
              <li class="dropdown-item"><a href="#" id="dropdownLogin" class="login-trigger">Login</a></li>
            <?php endif; ?>
          </ul>
        </li>
      </ul>

      <!-- Icon Section -->
      <div class="nav-icons">
        <a href="../pages-user/search.php" class="icon" title="Search"><i class="fas fa-search"></i></a>

        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="../pages-user/users-orders.php" class="icon" title="My Orders">
            <i class="fas fa-clipboard-list"></i>
          </a>
        <?php endif; ?>

        <!-- Dynamic Account Icon: profile if logged in, login if not -->
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="../pages-user/profile.php" class="icon" title="My Profile">
            <i class="fas fa-user"></i>
            <span class="login-indicator"></span>
          </a>
        <?php else: ?>
          <!-- Added common class "login-trigger" -->
          <a href="#" id="loginIcon" class="icon login-trigger" title="Login">
            <i class="fas fa-user"></i>
          </a>
        <?php endif; ?>

        <!-- Logout / Login Icon: show logout icon if logged in, otherwise show login icon -->
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="#" class="icon" title="Logout" id="mobileLogout">
            <i class="fas fa-sign-out-alt"></i>
          </a>
        <?php else: ?>
          <!-- Mobile login icon now triggers the login modal -->
          <a href="#" class="icon" title="Login" id="mobileLogin">
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

  <!-- Bootstrap 5 Bundle JS (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
      ].filter(btn => btn);

      logoutButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          logoutConfirm.style.display = 'flex';
        });
      });

      // Cancel logout
      document.getElementById('logoutCancel').addEventListener('click', function() {
        logoutConfirm.style.display = 'none';
      });

      // Close logout modal when clicking outside
      logoutConfirm.addEventListener('click', function(e) {
        if (e.target === this) {
          logoutConfirm.style.display = 'none';
        }
      });

      // Login modal trigger for mobile login
      const mobileLogin = document.getElementById('mobileLogin');
      const loginConfirm = document.getElementById('loginConfirm');
      if (mobileLogin && loginConfirm) {
        mobileLogin.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          loginConfirm.style.display = 'flex';
        });
      }

      // Login modal trigger for any element with class 'login-trigger'
      const loginTriggers = document.querySelectorAll('.login-trigger');
      loginTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          loginConfirm.style.display = 'flex';
        });
      });

      // Cancel login action
      document.getElementById('loginCancel').addEventListener('click', function() {
        loginConfirm.style.display = 'none';
      });

      // Close login modal when clicking outside
      loginConfirm.addEventListener('click', function(e) {
        if (e.target === this) {
          loginConfirm.style.display = 'none';
        }
      });

      // Redirection logic for Logout Confirm button
      document.getElementById('logoutConfirmBtn').addEventListener('click', function(e) {
        e.preventDefault();
        logoutConfirm.style.display = 'none';
        document.getElementById('redirectMessage').innerText = "Redirecting to Logout Page...";
        let redirectModal = new bootstrap.Modal(document.getElementById('redirectModal'));
        redirectModal.show();
        setTimeout(function() {
          window.location.href = "../pages/logout.php";
        }, 2000);
      });

      // Redirection logic for Login Confirm button
      document.getElementById('loginConfirmBtn').addEventListener('click', function(e) {
        e.preventDefault();
        loginConfirm.style.display = 'none';
        document.getElementById('redirectMessage').innerText = "Redirecting to Login Page...";
        let redirectModal = new bootstrap.Modal(document.getElementById('redirectModal'));
        redirectModal.show();
        setTimeout(function() {
          window.location.href = "../pages/login.php";
        }, 2000);
      });

      // Close redirect modal when clicking outside (optional, Bootstrap handles this by default)
      document.getElementById('redirectModal').addEventListener('click', function(e) {
        if (e.target === this) {
          let modalInstance = bootstrap.Modal.getInstance(document.getElementById('redirectModal'));
          modalInstance.hide();
        }
      });
    });
  </script>

</body>

</html>