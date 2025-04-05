<?php
// Prevent direct access
defined('ROOT_PATH') || define('ROOT_PATH', realpath(dirname(__DIR__)));

// Only start session if headers not already sent
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
    
    // Initialize cart if not exists or if it's not an array
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Set default currency if not exists
    if (!isset($_SESSION['currency'])) {
        $_SESSION['currency'] = '₱';
    }
}