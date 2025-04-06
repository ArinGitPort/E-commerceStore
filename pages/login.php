<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

/**
 * Merge session cart into the database once, immediately after login.
 * This prevents session cart data loss without multiplying quantities.
 */
function merge_session_cart_to_db(PDO $pdo): void
{
  if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) return;

  $userId = $_SESSION['user_id'];

  $stmt = $pdo->prepare("
        INSERT INTO cart_items (user_id, product_id, quantity)
        VALUES (:user_id, :product_id, :quantity)
        ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
    ");

  foreach ($_SESSION['cart'] as $productId => $quantity) {
    $stmt->execute([
      ':user_id' => $userId,
      ':product_id' => $productId,
      ':quantity' => $quantity
    ]);
  }

  // Clear session cart so it doesn’t get merged again later
  $_SESSION['cart'] = [];
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verify CSRF token
  if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['login_error'] = "Invalid security token. Please try again.";
    header("Location: login.php");
    exit;
  }

  $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
  $password = $_POST['password'] ?? '';

  if (!$email || !$password) {
    $_SESSION['login_error'] = "Please enter both email and password.";
    header("Location: login.php");
    exit;
  }

  try {
    // Fetch user and their role
    $stmt = $pdo->prepare("SELECT u.user_id, u.name, u.email, u.password, u.is_active, r.role_name 
                               FROM users u
                               JOIN roles r ON u.role_id = r.role_id
                               WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
      $_SESSION['login_error'] = "Invalid email or password.";
      header("Location: login.php");
      exit;
    }

    if ($user['is_active'] != 1) {
      $_SESSION['login_error'] = "Your account is not activated. Please verify your email.";
      header("Location: login.php");
      exit;
    }

    // Secure session
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role_name'];
    $_SESSION['last_login'] = time();
    $_SESSION['last_activity'] = time();

    session_write_close();
    session_start();

    if (empty($_SESSION['user_id']) || $_SESSION['user_id'] != $user['user_id']) {
      error_log('Session write verification failed for user: ' . $user['email']);
      throw new Exception('Session storage failed');
    }

    // Merge guest session cart into user's DB cart only once
    merge_session_cart_to_db($pdo);

    // Sync DB cart back into session so pages work as expected
    sync_cart($pdo);

    // Redirect user based on role
    $redirect = match ($user['role_name']) {
      'Admin'  => '../pages/inventory.php',
      'Member' => '../member/home.php',
      default  => '../pages-user/homepage.php'
    };

    $redirect_url = $_SESSION['redirect_url'] ?? $redirect;
    unset($_SESSION['redirect_url']);

    header("Location: $redirect_url");
    exit;
  } catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    $_SESSION['login_error'] = "An error occurred. Please try again.";
    header("Location: login.php");
    exit;
  }
}

// Display error from previous login attempt (if any)
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Login - Bunniwinkle</title>
  <link rel="stylesheet" href="../assets/css/login.css" />
  <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    /* Floating shop button styles */
    .floating-shop-btn {
      position: absolute;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 100;
      animation: floatUp 1s ease-out;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      border-radius: 50px;
      padding: 8px 20px;
      background: linear-gradient(135deg, #ffaee7, #83a6d4);
      color: white;
      border: none;
      transition: all 0.3s ease;
    }



    @keyframes floatUp {
      from {
        opacity: 0;
        transform: translateX(-50%) translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
      }
    }

    /* Adjust login card position to make space for floating button */
    .login-card {
      margin-top: 70px;
    }
  </style>
</head>

<body>
  <!-- Floating Visit Shop Button -->
  <a href="../index.php" class="btn btn-success floating-shop-btn">
    <i class="fas fa-store me-2"></i> Visit Shop
  </a>

  <div class="login-wrapper">
    <div class="login-card">
      <div class="logo-container">
        <img class="logo-image" src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Logo" />
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="login.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <label for="email">Email</label>
        <input type="email" name="email" id="email" required placeholder="Enter your email" />

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required placeholder="Enter your password" />

        <div class="options-row">
          <div class="checkbox-wrapper">
            <input type="checkbox" id="showPassword" onclick="togglePassword()" />
            <label for="showPassword">Show Password</label>
          </div>

          <div class="forgot-link">
            <a href="../pages-user/forgot-password.php">Forgot your password?</a>
          </div>
        </div>

        <button type="submit" class="login-btn">Login</button>

        <div class="register-link">
          <p>
            Don't have an account?
            <a href="register.php">Register here</a>
          </p>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordField = document.getElementById("password");
      passwordField.type = passwordField.type === "password" ? "text" : "password";
    }
  </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const shopBtn = document.querySelector('.floating-shop-btn');
  
  // For browsers that support the pseudo-element approach
  if (window.CSS && CSS.supports('position', 'sticky')) {
    return; // Let CSS handle it
  }
  
  // Fallback for older browsers
  shopBtn.addEventListener('mouseenter', function() {
    this.style.transition = 'background 0.6s ease-in-out';
    this.style.background = 'linear-gradient(135deg, #6da3d6, #e091cc)';
  });
  
  shopBtn.addEventListener('mouseleave', function() {
    this.style.transition = 'background 0.6s ease-in-out';
    this.style.background = 'linear-gradient(135deg, #ffaee7, #83a6d4)';
  });
});
</script>
</body>

</html>