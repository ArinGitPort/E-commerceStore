<?php
/**
 * Auth Check - Role-based Access Control Utility
 * 
 * Include this file at the beginning of any protected page to verify if the user
 * has appropriate permissions to access the page.
 * 
 * Usage: 
 *   require_once 'path/to/auth_check.php';
 *   // At the top of your admin page
 *   authCheck(['Admin', 'Super Admin']);
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if user is logged in and has proper role permissions
 *
 * @param array $allowedRoles Array of role names that are permitted to access the page
 * @param string $redirectUrl URL to redirect unauthorized users (default: login page)
 * @return void Redirects to login or access denied page if authentication fails
 */
function authCheck($allowedRoles = [], $redirectUrl = 'login.php') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Store the requested URL to redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // If no specific roles required, just being logged in is enough
    if (empty($allowedRoles)) {
        return;
    }
    
    // Verify user role
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        // Redirect to access denied page
        header('Location: access_denied.php');
        exit;
    }
}

/**
 * Checks if a user has a specific permission without redirecting
 * Useful for conditional display of UI elements
 *
 * @param array $allowedRoles Array of role names that have permission
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($allowedRoles = []) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    if (empty($allowedRoles)) {
        return true; // If no specific roles required, just being logged in is enough
    }
    
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $allowedRoles);
}

/**
 * Checks if user is logged in
 *
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Gets current user's role
 *
 * @return string|null User's role or null if not logged in
 */
function getCurrentUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Gets current user's ID
 *
 * @return int|null User's ID or null if not logged in
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Helper function to create access_denied.php if it doesn't exist
function createAccessDeniedPage() {
    $accessDeniedFile = dirname(__FILE__) . '/access_denied.php';
    
    if (!file_exists($accessDeniedFile)) {
        $content = <<<EOT
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            text-align: center;
            background-color: #fff;
            border-radius: 5px;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        p {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Access Denied</h1>
        <p>Sorry, you don't have permission to access this page. Please contact your administrator if you believe this is an error.</p>
        <a href="index.php" class="btn">Return to Home</a>
    </div>
</body>
</html>

EOT;
        
        file_put_contents($accessDeniedFile, $content);
    }
}

// Auto-create the access denied page
createAccessDeniedPage();