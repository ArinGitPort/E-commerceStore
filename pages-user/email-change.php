<?php
// pages-user/verify-email-change.php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$success = false;
$error = '';

if (empty($token) || empty($email)) {
    $error = 'Invalid verification link. Missing required parameters.';
} else {
    // Verify token and update email
    $stmt = $pdo->prepare("
    SELECT ecr.user_id, ecr.new_email, u.email as old_email, u.name 
    FROM email_change_requests ecr 
    JOIN users u ON ecr.user_id = u.user_id
    WHERE ecr.verification_token = ? AND ecr.new_email = ? AND ecr.expires_at > NOW()
");
    $stmt->execute([$token, $email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        $error = 'Invalid or expired verification link. Please request a new email change.';
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Update user's email
            $updateStmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $updateStmt->execute([$result['new_email'], $result['user_id']]);

            // Delete the request
            $deleteStmt = $pdo->prepare("DELETE FROM email_change_requests WHERE user_id = ?");
            $deleteStmt->execute([$result['user_id']]);

            // Log the email change
            $logStmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, affected_data, action_type)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $logData = json_encode([
                'old_email' => $result['old_email'],
                'new_email' => $result['new_email']
            ]);
            $logStmt->execute([
                $result['user_id'],
                'Email changed',
                'users',
                $result['user_id'],
                $_SERVER['REMOTE_ADDR'],
                $logData,
                'UPDATE'
            ]);

            // Commit transaction
            $pdo->commit();
            $success = true;

            // Update session if the user is currently logged in
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $result['user_id']) {
                $_SESSION['user_email'] = $result['new_email'];
            }
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = 'Database error occurred. Please try again later.';
            // Log the error for administrators
            error_log('Email verification error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Verification | BunniShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/email-validation.css">
  <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
  <style>
    .verification-container {
      text-align: center;
      padding: 20px;
    }
    .verification-icon {
      font-size: 72px;
      margin-bottom: 20px;
    }
    .success-icon {
      color: #4CAF50;
    }
    .error-icon {
      color: #e74c3c;
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
          <div class="verification-icon success-icon">✓</div>
          <h2>Email Successfully Changed!</h2>
          <p class="verification-text">Your email address has been updated to <strong><?= htmlspecialchars($email) ?></strong>.</p>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="login-btn action-link">Back to Profile</a>
          <?php else: ?>
            <a href="../pages/login.php" class="login-btn action-link">Continue to Login</a>
          <?php endif; ?>
        <?php else: ?>
          <div class="verification-icon error-icon">✉️</div>
          <h2>Verification Failed</h2>
          <p class="verification-text"><?= htmlspecialchars($error) ?></p>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="login-btn action-link">Back to Profile</a>
          <?php else: ?>
            <a href="../pages/login.php" class="login-btn action-link">Back to Login</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($success && isset($_SESSION['user_id'])): ?>
  <script>
    // Redirect to profile.php after 3 seconds if verification was successful and user is logged in
    setTimeout(function(){
      window.location.href = "profile.php";
    }, 3000);
  </script>
  <?php elseif ($success): ?>
  <script>
    // Redirect to login.php after 3 seconds if verification was successful but user is not logged in
    setTimeout(function(){
      window.location.href = "../pages/login.php";
    }, 3000);
  </script>
  <?php endif; ?>
</body>
</html>