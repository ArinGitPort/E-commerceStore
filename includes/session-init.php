<?php
// Prevent direct access
defined('ROOT_PATH') || define('ROOT_PATH', realpath(__DIR__ . '/..'));

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'read_and_close'  => false
    ]);
}

// Initialize common session variables
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
$_SESSION['currency'] = $_SESSION['currency'] ?? '₱';