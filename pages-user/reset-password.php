<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (!$token || !$email) {
    $_SESSION['error'] = "Invalid password reset link.";
    header("Location: forgot-password.php");
    exit;
}

$valid = false;
$user_id = null;
$current_password_hash = null;

// Check token and get current password hash
$stmt = $pdo->prepare("
    SELECT pr.user_id, u.password 
    FROM password_resets pr
    JOIN users u ON pr.user_id = u.user_id
    WHERE u.email = ? AND pr.token = ?
");
$stmt->execute([$email, $token]);
$row = $stmt->fetch();

if ($row) {
    $valid = true;
    $user_id = $row['user_id'];
    $current_password_hash = $row['password'];
} else {
    $_SESSION['error'] = "Token is invalid.";
    header("Location: forgot-password.php");
    exit;
}

$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Regex pattern for password validation
    // Requires at least 8 characters, 1 uppercase letter, 1 lowercase letter, 1 number, and 1 special character
    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

    // ✅ Backend validations
    if (!preg_match($passwordPattern, $password)) {
        $error = "Password must be at least 8 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (password_verify($password, $current_password_hash)) {
        $error = "New password cannot be the same as your current password.";
    } else {
        // Save password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$hashed, $user_id]);
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user_id]);

        // ✅ Log the password reset action in the audit logs
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, action_type, ip_address, user_agent, affected_data)
                VALUES (:user_id, 'Password reset', 'users', :user_id, 'UPDATE', :ip_address, :user_agent, :affected_data)
            ");
            $stmt->execute([
                'user_id'      => $user_id,
                'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',  // Fallback for IP address
                'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', // Fallback for User Agent
                'affected_data' => json_encode(['action' => 'Password reset']) // Additional details
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log password reset action: " . $e->getMessage());
        }

        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password - Bunniwinkle</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-container">
                <img class="logo-image" src="../assets/images/company assets/bunniwinkelanotherlogo.jpg" alt="Logo">
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success text-center" role="alert">
                    ✅ Password has been reset successfully. Redirecting to login...
                </div>
            <?php else: ?>
                <form action="" method="POST">
                    <h2>Reset Your Password</h2>
                    <p>Enter your new password below.</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" placeholder="New password" class="form-control mb-2" required>

                    <div class="password-requirements small text-muted mb-3">
                        Password must contain at least 8 characters, including uppercase and lowercase letters, numbers, and special characters (@$!%*?&).
                    </div>

                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Confirm password" class="form-control mb-2" required>

                    <!-- ✅ Show Password Checkbox -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="togglePassword" onclick="toggleVisibility()">
                        <label class="form-check-label" for="togglePassword">Show Password</label>
                    </div>

                    <button type="submit" class="login-btn mt-2">Reset Password</button>

                    <div class="text-center mt-3">
                        <a href="../pages/login.php">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleVisibility() {
            const pw = document.getElementById('password');
            const cpw = document.getElementById('confirmPassword');
            const type = pw.type === 'password' ? 'text' : 'password';
            pw.type = type;
            cpw.type = type;
        }

        <?php if ($success): ?>
            setTimeout(() => {
                window.location.href = "../pages/login.php";
            }, 3000);
        <?php endif; ?>
    </script>
</body>

</html>