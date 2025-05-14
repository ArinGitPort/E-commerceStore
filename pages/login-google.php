<?php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google\Client();
$client->setClientId('132279815834-hc8gb34u5igiisrtggp0rrd1aliih3qm.apps.googleusercontent.com');
$client->setClientSecret('');
$client->setRedirectUri('http://localhost:3000/pages/login-google.php');
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    try {
        // 1. Exchange code for token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // 2. Fetch Google user info
        $service = new Google\Service\Oauth2($client);
        $userInfo = $service->userinfo->get();

        // 3. Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$userInfo->email]);
        $user = $stmt->fetch();

        // 4. Register if new
        $isNewUser = false;
        if (!$user) {
            $isNewUser = true;
            $defaultRole = 1;  // Member
            $stmt = $pdo->prepare("
                INSERT INTO users
                  (name, email, password, role_id, is_active, created_at, oauth_provider, oauth_id)
                VALUES (?, ?, ?, ?, 1, NOW(), 'google', ?)
            ");
            $dummyPwd = bin2hex(random_bytes(16));
            $stmt->execute([
                $userInfo->name,
                $userInfo->email,
                password_hash($dummyPwd, PASSWORD_DEFAULT),
                $defaultRole,
                $userInfo->id
            ]);
            $userId = $pdo->lastInsertId();
            
            // Get the full user record after creation
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } else {
            $userId = $user['user_id'];
        }

        // 5. Initialize session
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $userInfo->email;
        $_SESSION['name'] = $userInfo->name;
        $_SESSION['oauth_provider'] = 'google';
        $_SESSION['last_login'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['is_new_user'] = $isNewUser;

        // 6. Update last_login_at (trigger logs audit)
        $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = ?")
            ->execute([$userId]);

        // 7. Get current role information (works for both new and existing users)
        $stmt = $pdo->prepare("SELECT r.role_name FROM roles r JOIN users u ON r.role_id = u.role_id WHERE u.user_id = ?");
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();

        // 8. Determine redirect based on role
        $redirect = '../pages-user/homepage.php';
        if ($role) {
            $redirect = match ($role) {
                'Super Admin', 'Admin' => '../pages/dashboard.php',
                'Brand Partners' => '../pages/sales_report.php',
                'Staff' => '../pages/order-management.php',
                default => $isNewUser ? '../pages-user/homepage.php' : '../pages-user/homepage.php',
            };
        }

        // 9. Set a session flag for first-time login
        if ($isNewUser) {
            $_SESSION['first_login'] = true;
        }

        header("Location: $redirect");
        exit;

    } catch (Exception $e) {
        error_log('Google OAuth error: ' . $e->getMessage());
        $_SESSION['login_error'] = "Google login failed. Please try again.";
        header("Location: login.php");
        exit;
    }
}

// 10. No code? Redirect to Google
$authUrl = $client->createAuthUrl();
header("Location: $authUrl");
exit;