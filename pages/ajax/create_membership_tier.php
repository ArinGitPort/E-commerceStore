<?php
// pages/ajax/create_membership_tier.php
session_start();
require_once __DIR__ . '/../../includes/session-init.php';
require_once __DIR__ . '/../../config/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../subscription-management.php');
    exit;
}

$name = trim($_POST['type_name'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$description = $_POST['description'] ?? '';
$exclusive = isset($_POST['can_access_exclusive']) ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        INSERT INTO membership_types 
        (type_name, price, description, can_access_exclusive)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$name, $price, $description, $exclusive]);
    $_SESSION['message'] = 'Tier created successfully';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Create failed: ' . $e->getMessage();
}

header('Location: ../subscription-management.php');
exit;
?>