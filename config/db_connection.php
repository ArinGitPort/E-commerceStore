<?php
// Database connection settings
$host = 'localhost';
$db = 'bunnishop';
$user = 'root';
$pass = '1234';
$charset = 'utf8mb4';

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Set up DSN (Data Source Name)
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    
    // Set PDO options for better error handling and performance
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // Create PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // For backward compatibility with code that might expect mysqli
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        error_log("MySQLi connection failed: " . $conn->connect_error);
        // Don't die here, since we have PDO working
    }
    
} catch (PDOException $e) {
    // Log the error
    error_log("PDO Connection Error: " . $e->getMessage());
    
    // For production, don't display sensitive error details
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        die("Database connection failed. Please try again later.");
    } else {
        // For development, show detailed error
        die("Database connection failed: " . $e->getMessage());
    }
}
?>