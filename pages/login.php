<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

function merge_session_cart_to_db(PDO $pdo): void
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
        return;
    }

    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        INSERT INTO cart_items (user_id, product_id, quantity)
        VALUES (:user_id, :product_id, :quantity)
        ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
    ");

    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $stmt->execute([
            'user_id'    => $userId,
            'product_id' => $productId,
            'quantity'   => $quantity
        ]);
    }

    // Clear the session cart once merged to DB.
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token.
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['login_error'] = 'Invalid security token. Please try again.';
        header("Location: login.php");
        exit;
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['login_error'] = 'Please enter both email and password.';
        header("Location: login.php");
        exit;
    }

    try {
        // Select the user with the given email, along with role info.
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.name, u.email, u.password, u.is_active, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists and password matches.
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['login_error'] = 'Invalid email or password.';
            header("Location: login.php");
            exit;
        }

        // Check if user is active.
        if ($user['is_active'] != 1) {
            $_SESSION['login_error'] = 'Your account is not activated. Please verify your email.';
            header("Location: login.php");
            exit;
        }

        // Regenerate session to avoid fixation.
        session_regenerate_id(true);

        // Set session variables.
        $_SESSION['user_id']      = $user['user_id'];
        $_SESSION['name']         = $user['name'];
        $_SESSION['email']        = $user['email'];
        $_SESSION['role']         = $user['role_name'];
        $_SESSION['last_login']   = time();
        $_SESSION['last_activity'] = time();

        // Merge cart from session to DB.
        merge_session_cart_to_db($pdo);

        // If you have a sync_cart function, you can call it here (as shown in your snippet).
        if (function_exists('sync_cart')) {
            sync_cart($pdo);
        }

        // Decide where to redirect based on role.
        $redirect = match ($user['role_name']) {
            'Admin'  => '../pages/inventory.php',
            'Member' => '../pages-user/homepage.php',
            default  => '../pages-user/homepage.php',
        };

        // If a redirect was set prior to login, use it; otherwise use the default.
        $redirect_url = $_SESSION['redirect_url'] ?? $redirect;
        unset($_SESSION['redirect_url']);

        header("Location: $redirect_url");
        exit;
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        $_SESSION['login_error'] = 'An error occurred. Please try again.';
        header("Location: login.php");
        exit;
    }
}

// Grab any login error message stored in session.
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <title>Login - Bunniwinkle</title>
  <link rel="stylesheet" href="../assets/css/login.css" />
  <link rel="icon" href="../assets/images/icon/logo_bunniwinkleIcon.ico" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    body,
    html {
      margin: 0;
      padding: 0;
      background: linear-gradient(0deg, rgba(255, 219, 254, 1) 3%, rgba(200, 226, 246, 1) 48%);
    }
    .floating-shop-btn {
      position: fixed;
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
    /* Login wrapper */
    .login-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 15px;
    }
    /* Login card */
    .login-card {
      background: rgba(255, 255, 255, 0.95);
      border: 2px solid #354359;
      border-radius: 10px;
      padding: 30px 40px;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 450px;
      margin-top: 70px;
    }
    /* Button styling */
    .login-btn {
      background-color: #354359;
      color: white;
      padding: 12px;
      font-size: 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .login-btn:hover {
      background-color: #2b374b;
    }
    /* Responsive adjustments for small screens */
    @media (max-width: 576px) {
      .login-card {
        padding: 20px;
        margin: 0 10px;
        margin-top: 40px;
      }
      .floating-shop-btn {
        padding: 6px 16px;
        top: 10px;
      }
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
      <div class="logo-container text-center">
        <img
          class="logo-image"
          src="../assets/images/company assets/bunniwinkelanotherlogo.jpg"
          alt="Logo"
          style="max-width: 100%; height: auto;"
        />
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="login.php" method="POST" novalidate autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <div class="mb-3">
          <label for="email" class="form-label" style="color: #333; font-weight: bold;">Email</label>
          <input
            type="email"
            name="email"
            id="email"
            required
            placeholder="Enter your email"
            class="form-control"
            autocomplete="off"
            value=""
          />
        </div>

        <div class="mb-3">
          <label for="password" class="form-label" style="color: #333; font-weight: bold;">Password</label>
          <input
            type="password"
            name="password"
            id="password"
            required
            placeholder="Enter your password"
            class="form-control"
            autocomplete="off"
            value=""
          />
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input
              type="checkbox"
              id="showPassword"
              onclick="togglePassword()"
              class="form-check-input"
            />
            <label for="showPassword" class="form-check-label">Show Password</label>
          </div>
          <div class="forgot-link">
            <a href="../pages-user/forgot-password.php" style="color: #354359; font-size: 14px;">
              Forgot your password
            </a>
          </div>
        </div>

        <button type="submit" class="login-btn w-100">Login</button>

        <div class="register-link text-center mt-3" style="font-size: 16px;">
          <p>
            Don't have an account?
            <a href="register.php" style="color: #354359; font-weight: bold;">Register here</a>
          </p>

          <div class="d-flex align-items-center mb-3">
            <hr class="flex-grow-1" />
            <span class="mx-2 text-muted">or</span>
            <hr class="flex-grow-1" />
          </div>

          <div class="mb-3">
            <a href="login-google.php" class="btn-premium-google w-100">
              <i class="fab fa-google"></i>
              <span class="google-text-span">Continue with Google</span>
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordField = document.getElementById('password');
      passwordField.type = (passwordField.type === 'password') ? 'text' : 'password';
    }

    document.addEventListener('DOMContentLoaded', function() {
      const shopBtn = document.querySelector('.floating-shop-btn');

      // Fallback for older browsers
      if (window.CSS && CSS.supports('position', 'sticky')) {
        return;
      }

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
