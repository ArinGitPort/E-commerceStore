<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

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
        // Fetch the user along with activation status
        $stmt = $pdo->prepare("SELECT u.user_id, u.name, u.email, u.password, u.is_active, r.role_name 
                               FROM users u
                               JOIN roles r ON u.role_id = r.role_id
                               WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // If user not found or password doesn't match, set error.
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: login.php");
            exit;
        }

        // Check if account is activated
        if ($user['is_active'] != 1) {
            $_SESSION['login_error'] = "Your account is not activated. Please verify your email.";
            header("Location: login.php");
            exit;
        }

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session data
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['last_login'] = time();
        $_SESSION['last_activity'] = time();

        // Verify the session was actually saved
        session_write_close();
        session_start();

        if (empty($_SESSION['user_id']) || $_SESSION['user_id'] != $user['user_id']) {
            error_log('Session write verification failed for user: ' . $user['email']);
            throw new Exception('Session storage failed');
        }

        // Role-based redirection
        $redirect = match($user['role_name']) {
            'Admin'  => '../pages/inventory.php',
            'Member' => '../member/home.php',
            default  => '../pages-user/homepage.php'
        };

        // Use any stored redirect URL if present
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
</head>
<body>
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
</body>
</html>
