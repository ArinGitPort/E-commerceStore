<?php
// pages/ajax/create_membership_tier.php

require_once __DIR__ . '/../../includes/session-init.php';
require_once __DIR__ . '/../../config/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Validate input
    $name = trim($_POST['type_name'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = $_POST['description'] ?? '';
    $exclusive = isset($_POST['can_access_exclusive']) ? 1 : 0;

    if (empty($name) || $price === false || $price < 0) {
        throw new InvalidArgumentException('Invalid input parameters');
    }

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO membership_types 
        (type_name, price, description, can_access_exclusive)
        VALUES (:name, :price, :desc, :exclusive)
    ");
    
    $stmt->execute([
        ':name' => $name,
        ':price' => $price,
        ':desc' => $description,
        ':exclusive' => $exclusive
    ]);

    $_SESSION['message'] = 'Tier created successfully';
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Create failed: ' . $e->getMessage()
    ]);
}
exit;