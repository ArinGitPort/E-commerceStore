<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

$error = "";
$success = "";

// Check for activation token and email in GET parameters
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Look for a user with this email and token who is not active
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND activation_token = ? AND is_active = 0");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();

        if ($user) {
            // Activate the account and clear the token
            $updateStmt = $pdo->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE user_id = ?");
            if ($updateStmt->execute([$user['user_id']])) {
                $success = "Email verified successfully! Your account is now activated.";
            } else {
                $error = "Unable to activate your account. Please try again later.";
            }
        } else {
            $error = "Invalid or expired activation link.";
        }
    }
} else {
    $error = "Activation token and email are required.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Verification | Bunniwinkle</title>
  <link rel="stylesheet" href="../assets/css/login.css">
  <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .verification-container {
      text-align: center;
      padding: 20px;
    }
    .verification-icon {
      font-size: 72px;
      color: #4CAF50;
      margin-bottom: 20px;
    }
    .verification-text {
      margin-bottom: 30px;
    }
    .action-link {
      margin-top: 20px;
      font-size: 1rem;
    }
  </style>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="logo-container">
        <img class="logo-image" src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Logo">
      </div>

      <div class="verification-container">
        <?php if ($success): ?>
          <div class="verification-icon">✓</div>
          <h2>Email Verified Successfully!</h2>
          <p class="verification-text"><?= htmlspecialchars($success) ?></p>
          <a href="../pages/login.php" class="login-btn action-link">Continue to Login</a>
        <?php else: ?>
          <div class="verification-icon">✉️</div>
          <h2>Verification Failed</h2>
          <p class="verification-text"><?= htmlspecialchars($error) ?></p>
          <a href="../pages/login.php" class="login-btn action-link">Back to Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($success): ?>
  <script>
    // Redirect to login.php after 3 seconds if verification was successful
    setTimeout(function(){
      window.location.href = "../pages/login.php";
    }, 3000);
  </script>
  <?php endif; ?>
</body>
</html>
