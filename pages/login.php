<?php
require_once __DIR__ . '/../includes/session-init.php'; // Ensure this file contains validate_csrf_token()
require_once __DIR__ . '/../config/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate CSRF token
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
    // Select the user with the given email, along with role info
    $stmt = $pdo->prepare("SELECT u.user_id, u.name, u.email, u.password, u.is_active, r.role_name 
                               FROM users u
                               JOIN roles r ON u.role_id = r.role_id 
                               WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists and password matches
    if (!$user || !password_verify($password, $user['password'])) {
      $_SESSION['login_error'] = 'Invalid email or password.';
      header("Location: login.php");
      exit;
    }

    // Check if user is active
    if ($user['is_active'] != 1) {
      $_SESSION['login_error'] = 'Your account is not activated. Please verify your email.';
      header("Location: login.php");
      exit;
    }

    // Regenerate session to avoid session fixation
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id']      = $user['user_id'];
    $_SESSION['name']         = $user['name'];
    $_SESSION['email']        = $user['email'];
    $_SESSION['role']         = $user['role_name'];
    $_SESSION['last_login']   = time();
    $_SESSION['last_activity'] = time();

    try {
      // Update last login timestamp
      $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = :user_id");
      $stmt->execute(['user_id' => $user['user_id']]);

      // Log the login action in the audit logs
      $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                VALUES (:user_id, 'User logged in', 'users', :user_id, 'LOGIN', :ip_address, :user_agent, :affected_data)
            ");
      $stmt->execute([
        'user_id'      => $user['user_id'],
        'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'affected_data' => json_encode([])
      ]);
      error_log("Login action successfully logged for user ID: " . $user['user_id']);
    } catch (PDOException $e) {
      error_log("Failed to insert login audit log: " . $e->getMessage());
    }

    // Redirect based on role
    $redirect = match ($user['role_name']) {
      'Super Admin' => '../pages/dashboard.php',
      'Admin'  => '../pages/dashboard.php',
      'Member' => '../pages-user/homepage.php',
      default  => '../pages-user/homepage.php',
    };

    // If a redirect was set prior to login, use it; otherwise use the default
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

// Grab any login error message stored in session
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
  <title>Bunniwinkle - Login</title>
  <link rel="stylesheet" href="../assets/css/login.css" />
  <link rel="icon" href="../assets/images/icon/logo_bunniwinkleIcon.ico" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <style>
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

    .login-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 15px;
    }

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

    .minimalist-input:focus {
      border-color: #354359;
      box-shadow: 0 0 0 0.25rem rgba(53, 67, 89, 0.25);
    }

    .is-invalid {
      border-color: #dc3545 !important;
    }

    .is-invalid:focus {
      box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }
    
    /* Disabled Google button styles */
    .btn-premium-google.disabled {
      background-color: #e0e0e0 !important;
      color: #a0a0a0 !important;
      cursor: not-allowed;
      pointer-events: none;
      opacity: 0.65;
      border: 1px solid #cccccc;
    }
    
    .btn-premium-google.disabled i {
      color: #a0a0a0 !important;
    }
    
    .btn-premium-google.disabled .google-text-span {
      color: #888888 !important;
    }
  </style>
</head>

<body>


  <a href="../index.php" class="btn btn-success floating-shop-btn">
    <i class="fas fa-store me-2"></i> Visit Shop
  </a>

  <div class="login-wrapper">
    <div class="text-center mb-4">
      <img
        src="../assets/images/company assets/bunniwinkelanotherlogo.jpg"
        alt="Logo"
        style="max-width: 180px;" />
    </div>

    <form action="login.php" method="POST" class="w-100" style="max-width: 400px;" novalidate>
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input
          type="email"
          name="email"
          id="email"
          class="form-control <?= isset($error) ? 'is-invalid' : '' ?>"
          placeholder="you@example.com"
          required
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input
          type="password"
          name="password"
          id="password"
          class="form-control <?= isset($error) ? 'is-invalid' : '' ?>"
          placeholder="your password"
          required>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input type="checkbox" id="showPassword" onclick="togglePassword()" class="form-check-input">
          <label for="showPassword" class="form-check-label">Show Password</label>
        </div>
        <a href="../pages-user/forgot-password.php" class="text-muted" style="font-size: 14px;">Forgot password?</a>
      </div>

      <button type="submit" class="login-btn">
        <span data-text="Login">Login</span>&nbsp;
        <i class="fas fa-arrow-right-to-bracket"></i>
      </button>

      <div class="d-flex align-items-center my-3">
        <hr class="flex-grow-1" />
        <span class="mx-2 text-muted">or</span>
        <hr class="flex-grow-1" />
      </div>
      <div class="goole-btn-wrapper">
        <!-- Added "disabled" class to the Google sign-in button -->
        <a href="#" class="btn-premium-google w-100 disabled">
          <i class="fab fa-google"></i>
          <span class="google-text-span">Continue with Google</span>
        </a>
      </div>
    </form>
  </div>
  <footer class="text-center mt-5 text-muted" style="font-size: 18px;">
    Don't have an account?
    <a href="register.php" style="color: #354359; font-weight: 600; padding: 0 8px;">Register here</a>
  </footer>



  <script>
    function togglePassword() {
      const pw = document.getElementById('password');
      pw.type = pw.type === 'password' ? 'text' : 'password';
    }

    // Auto-close alert after 5 seconds
    window.addEventListener('DOMContentLoaded', () => {
      const alert = document.querySelector('.alert');
      if (alert) {
        setTimeout(() => {
          alert.classList.remove('show');
          alert.classList.add('fade');
        }, 5000);
      }
    });
  </script>
</body>

</html>