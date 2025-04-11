<?php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Google OAuth Configuration
$client = new Google\Client();
$client->setClientId('132279815834-hc8gb34u5igiisrtggp0rrd1aliih3qm.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost:3000/pages/login-google.php');
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        $service = new Google\Service\Oauth2($client);
        $userInfo = $service->userinfo->get();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$userInfo->email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Register new user
            $defaultRole = 1; // Customer role
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role_id, is_active, created_at, oauth_provider, oauth_id)
                VALUES (?, ?, ?, ?, 1, NOW(), 'google', ?)
            ");
            $randomPassword = bin2hex(random_bytes(16));
            $stmt->execute([
                $userInfo->name,
                $userInfo->email,
                password_hash($randomPassword, PASSWORD_DEFAULT),
                $defaultRole,
                $userInfo->id
            ]);
            $userId = $pdo->lastInsertId();
        } else {
            $userId = $user['user_id'];
        }

        // Secure session
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $userInfo->email;
        $_SESSION['name'] = $userInfo->name;
        $_SESSION['oauth_provider'] = 'google';
        
        // Get role name for consistent handling
        $stmt = $pdo->prepare("SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?");
        $stmt->execute([$userId]);
        $_SESSION['role'] = $stmt->fetchColumn();

        // Redirect based on role
        $redirect = match ($_SESSION['role']) {
            'Admin' => '../pages/inventory.php',
            'Member' => '../pages-user/homepage.php',
            default => '../pages-user/homepage.php'
        };

        header("Location: $redirect");
        exit;
    } catch (Exception $e) {
        error_log('Google OAuth error: ' . $e->getMessage());
        $_SESSION['login_error'] = "Google login failed. Please try again.";
        header("Location: login.php");
        exit;
    }
}

// Initial auth redirect
$authUrl = $client->createAuthUrl();
header("Location: $authUrl");
exit;