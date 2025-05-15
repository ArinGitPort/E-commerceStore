<?php
session_start();
require_once '../config/db_connection.php';

date_default_timezone_set('Asia/Manila'); // Adjust to your timezone

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's an email verification step
    if (isset($_POST['email'])) {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $_SESSION['error'] = "Please enter a valid email address.";
            header("Location: forgot-password.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate a temporary token
                $token = bin2hex(random_bytes(16));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Delete any existing tokens for this user
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['user_id']]);
                
                // Insert new token
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['user_id'], $token, $expires]);

                // Store verified email in session
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_token'] = $token;
                $_SESSION['reset_name'] = $user['name'];
                
                // Log the password reset request in audit_logs
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                        VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                    ");
                    $stmt->execute([
                        'user_id'       => $user['user_id'],
                        'action'        => 'Password reset requested',
                        'table_name'    => 'password_resets',
                        'record_id'     => $user['user_id'],
                        'action_type'   => 'SYSTEM',
                        'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                        'affected_data' => json_encode(['email' => $email])
                    ]);
                    error_log("Password reset request logged for user ID: " . $user['user_id']);
                } catch (Exception $e) {
                    error_log("Failed to log password reset request: " . $e->getMessage());
                    // Continue with the password reset process despite logging error
                }
                
                // Show reset form
                $_SESSION['show_reset_form'] = true;
                header("Location: forgot-password.php");
                exit;
            } else {
                // Don't reveal if email exists or not (security measure)
                $_SESSION['error'] = "If this email exists in our system, you'll see a password reset form.";
                header("Location: forgot-password.php");
                exit;
            }

        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            $_SESSION['error'] = "Something went wrong. Please try again.";
            header("Location: forgot-password.php");
            exit;
        }
    }
    
    // Check if it's a password reset step
    if (isset($_POST['password']) && isset($_POST['confirm_password']) && isset($_SESSION['reset_token'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $token = $_SESSION['reset_token'];
        $email = $_SESSION['reset_email'];
        
        // Validate password
        if (strlen($password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters long.";
            header("Location: forgot-password.php");
            exit;
        }
        
        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Passwords do not match.";
            header("Location: forgot-password.php");
            exit;
        }
        
        try {
            // Get user_id from email
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $_SESSION['error'] = "Invalid request.";
                unset($_SESSION['show_reset_form']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_name']);
                header("Location: forgot-password.php");
                exit;
            }
            
            // Check if token is valid
            $stmt = $pdo->prepare("SELECT * FROM password_resets 
                                  WHERE user_id = ? AND token = ? AND expires_at > NOW()");
            $stmt->execute([$user['user_id'], $token]);
            $reset = $stmt->fetch();
            
            if ($reset) {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $user['user_id']]);
                
                // Log the password reset completion in audit_logs
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                        VALUES (:user_id, :action, :table_name, :record_id, :action_type, :ip_address, :user_agent, :affected_data)
                    ");
                    $stmt->execute([
                        'user_id'       => $user['user_id'],
                        'action'        => 'Password reset completed',
                        'table_name'    => 'users',
                        'record_id'     => $user['user_id'],
                        'action_type'   => 'UPDATE',
                        'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                        'affected_data' => json_encode(['email' => $email])
                    ]);
                    error_log("Password reset completion logged for user ID: " . $user['user_id']);
                } catch (Exception $e) {
                    error_log("Failed to log password reset completion: " . $e->getMessage());
                    // Continue with the password reset process despite logging error
                }
                
                // Delete used token
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['user_id']]);
                
                // Clear session variables
                unset($_SESSION['show_reset_form']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_name']);
                
                $_SESSION['success'] = "Your password has been successfully reset. You can now login with your new password.";
                header("Location: ../pages/login.php");
                exit;
            } else {
                $_SESSION['error'] = "Invalid or expired token.";
                unset($_SESSION['show_reset_form']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_name']);
                header("Location: forgot-password.php");
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            $_SESSION['error'] = "Something went wrong. Please try again.";
            header("Location: forgot-password.php");
            exit;
        }
    }
}

// Display session messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bunniwinkle - Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/forgot-password.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-container">
                <img class="logo-image" src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Logo">
            </div>

            <!-- Flash Messages -->
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['show_reset_form'])): ?>
                <!-- Password Reset Form -->
                <form action="forgot-password.php" method="POST">
                    <h2>Reset Password</h2>
                    <p>Hello <?= htmlspecialchars($_SESSION['reset_name'] ?? '') ?>, create your new password below.</p>

                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" required 
                        placeholder="Minimum 8 characters" class="form-control mb-3">

                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required 
                        placeholder="Confirm your password" class="form-control mb-3">

                    <button type="submit" class="login-btn">Reset Password</button>
                </form>
            <?php else: ?>
                <!-- Email Verification Form -->
                <form action="forgot-password.php" method="POST">
                    <h2>Forgot Password</h2>
                    <p>Enter your email to reset your password.</p>

                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required 
                        placeholder="Your email" class="form-control mb-3">

                    <button type="submit" class="login-btn">Continue</button>

                    <div class="text-center mt-3">
                        <a href="../pages/login.php">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>