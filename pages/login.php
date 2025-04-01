<?php
session_start();
require_once '../config/db_connection.php'; // adjust if needed

function clean_input($data) {
  return htmlspecialchars(trim($data));
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
  $password = $_POST['password'] ?? '';

  if (!$email || !$password) {
    $_SESSION['login_error'] = "Please enter both email and password.";
    header("Location: login.php");
    exit;
  }

  $stmt = $pdo->prepare("SELECT u.user_id, u.name, u.password, r.role_name 
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

  // Set session data
  $_SESSION['user_id'] = $user['user_id'];
  $_SESSION['name'] = $user['name'];
  $_SESSION['role'] = $user['role_name'];

  // Role-based redirection
  switch ($user['role_name']) {
    case 'Admin':
      header("Location: ../admin/dashboard.php"); break; // placeholder
    case 'Member':
      header("Location: ../member/home.php"); break; // placeholder
    case 'Customer':
    default:
      header("Location: ../pages-user/homepage.php"); break; // placeholder
  }
  exit;
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

      <!-- Error message if login fails -->
      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form action="login.php" method="POST" novalidate>
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
            <a href="forgot_password.php">Forgot your password?</a>
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
