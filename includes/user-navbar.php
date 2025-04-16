<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Re-sync cart data so that the cart count is accurate
$user_id = $_SESSION['user_id'] ?? null;
$membership_type_id = 0;
$can_access_exclusive = false;

if ($user_id) {
  $stmt = $pdo->prepare("
    SELECT mt.membership_type_id, mt.can_access_exclusive
    FROM memberships m
    JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
    WHERE m.user_id = ?
  ");
  $stmt->execute([$user_id]);
  $membership = $stmt->fetch();

  if ($membership) {
    $membership_type_id = (int) $membership['membership_type_id'];
    $can_access_exclusive = (bool) $membership['can_access_exclusive'];
  }
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
                $has_restricted_exclusive = false;

                foreach ($cart_items as $item) {
                  if ($item['is_exclusive']) {
                    $min_level = (int) $item['min_membership_level'];
                    if (!$can_access_exclusive || $membership_type_id < $min_level) {
                      $has_restricted_exclusive = true;
                      break;
                    }
                  }
                }


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
                <?php if ($has_restricted_exclusive): ?>
                  <button class="btn-checkout disabled" style="opacity: 0.6; cursor: not-allowed;" title="Upgrade to checkout exclusive items" disabled>
                    Checkout
                  </button>
                <?php else: ?>
                  <a href="checkout.php" class="btn-checkout">Checkout</a>
                <?php endif; ?>
              </div>

            <?php else: ?>
              <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="shop.php" class="btn-shop">Continue Shopping</a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Notification Bell Icon -->
        <div class="notif-dropdown position-relative" style="margin-left: 15px;">
          <a href="#" class="icon" id="notifBell" title="Notifications" style="font-size: 1.1rem; position: relative;">
            <i class="fas fa-bell"></i>
            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle p-1 rounded-circle" id="notifCount" style="font-size: 0.65rem; transform: translate(-30%, -30%);">0</span>
          </a>
          <div class="notif-dropdown-content shadow-sm border" id="notifDropdown" style="display: none; position: absolute; right: 0; background: #fff; width: 300px; max-height: 350px; overflow-y: auto; z-index: 999; border-radius: 8px;">
            <div class="p-3">
              <h6 class="fw-bold mb-2">Notifications</h6>
              <div id="notifItems">
                <p class="text-muted small mb-0">No notifications yet.</p>
              </div>
            </div>
          </div>
        </div>





      </div>
    </div>
  </nav>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // Mobile menu toggle
      document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        this.classList.toggle('active');
        document.getElementById('navMenu').classList.toggle('active');
      });

      // Cart dropdown
      const cartIcon = document.querySelector('.cart-icon');
      const cartDropdown = document.querySelector('.cart-dropdown-content');
      if (cartIcon && cartDropdown) {
        cartIcon.addEventListener('click', function(e) {
          if (window.innerWidth > 768) {
            e.preventDefault();
            cartDropdown.classList.toggle('show');
            document.querySelectorAll('.cart-dropdown-content.show').forEach(dd => {
              if (dd !== cartDropdown) dd.classList.remove('show');
            });
          }
        });
        document.addEventListener('click', function(e) {
          if (!e.target.closest('.cart-dropdown')) {
            cartDropdown.classList.remove('show');
          }
        });
      }

      // Highlight current page
      const currentPage = location.pathname.split('/').pop() || 'homepage.php';
      document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href').includes(currentPage)) {
          link.classList.add('active');
        }
      });

      // Logout confirmation
      const logoutConfirm = document.getElementById('logoutConfirm');
      const logoutButtons = [document.getElementById('navLogout'), document.getElementById('mobileLogout')].filter(Boolean);
      logoutButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          logoutConfirm.style.display = 'flex';
        });
      });

      document.getElementById('logoutCancel').addEventListener('click', function() {
        logoutConfirm.style.display = 'none';
      });

      logoutConfirm.addEventListener('click', function(e) {
        if (e.target === this) {
          logoutConfirm.style.display = 'none';
        }
      });

      // Login confirmation
      const mobileLogin = document.getElementById('mobileLogin');
      const loginConfirm = document.getElementById('loginConfirm');
      if (mobileLogin && loginConfirm) {
        mobileLogin.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          loginConfirm.style.display = 'flex';
        });
      }

      document.querySelectorAll('.login-trigger').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          loginConfirm.style.display = 'flex';
        });
      });

      document.getElementById('loginCancel').addEventListener('click', function() {
        loginConfirm.style.display = 'none';
      });

      loginConfirm.addEventListener('click', function(e) {
        if (e.target === this) {
          loginConfirm.style.display = 'none';
        }
      });

      document.getElementById('logoutConfirmBtn').addEventListener('click', function(e) {
        e.preventDefault();
        logoutConfirm.style.display = 'none';
        document.getElementById('redirectMessage').innerText = "Redirecting to Logout Page...";
        let redirectModal = new bootstrap.Modal(document.getElementById('redirectModal'));
        redirectModal.show();
        setTimeout(() => window.location.href = "../pages/logout.php", 2000);
      });

      document.getElementById('loginConfirmBtn').addEventListener('click', function(e) {
        e.preventDefault();
        loginConfirm.style.display = 'none';
        document.getElementById('redirectMessage').innerText = "Redirecting to Login Page...";
        let redirectModal = new bootstrap.Modal(document.getElementById('redirectModal'));
        redirectModal.show();
        setTimeout(() => window.location.href = "../pages/login.php", 2000);
      });

      document.getElementById('redirectModal').addEventListener('click', function(e) {
        if (e.target === this) {
          let modalInstance = bootstrap.Modal.getInstance(this);
          modalInstance.hide();
        }
      });

      const notifBell = document.getElementById('notifBell');
      const notifCount = document.getElementById('notifCount');
      const notifDropdown = document.getElementById('notifDropdown');
      const notifItems = document.getElementById('notifItems');

      // Fetch notifications every 30 seconds
      function fetchNotifications() {
        fetch('../pages/get_notifications.php')
          .then(response => response.json()) // Add this line
          .then(data => {
            notifCount.textContent = data.count || '0';
            updateNotificationList(data.notifications);
          })
          .catch(error => console.error('Error:', error));
      }

      function updateNotificationList(notifications) {
        notifItems.innerHTML = '';

        if (notifications.length === 0) {
          notifItems.innerHTML = '<p class="text-muted small mb-0">No notifications yet.</p>';
          return;
        }

        notifications.forEach(notification => {
          const item = document.createElement('a');
          item.href = '#';
          item.className = `d-block p-2 notification-item ${!notification.is_read ? 'bg-light' : ''}`;
          item.innerHTML = `
  <div class="d-flex justify-content-between">
    <h6 class="mb-1">${notification.title}</h6>
    <small class="text-muted">
      ${new Date(notification.created_at).toLocaleString('en-US', {
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })}
    </small>
  </div>
  <p class="small mb-0">${notification.message}</p>
`;

          if (!notification.is_read) {
            item.addEventListener('click', () => markAsRead(notification.notification_id));
          }

          notifItems.appendChild(item);
        });
      }

      function markAsRead(notificationId) {
        fetch('/api/mark_notification_read.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              notificationId
            })
          })
          .then(() => fetchNotifications())
          .catch(error => console.error('Error:', error));
      }

      // Toggle dropdown
      notifBell.addEventListener('click', function(e) {
        e.preventDefault();
        notifDropdown.style.display = notifDropdown.style.display === 'none' ? 'block' : 'none';
        fetchNotifications();
      });

      // Initial fetch
      fetchNotifications();
      setInterval(fetchNotifications, 30000); // Refresh every 30 seconds
    });
  </script>

</body>

</html>