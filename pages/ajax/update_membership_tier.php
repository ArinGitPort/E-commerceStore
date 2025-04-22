<?php
// pages/ajax/update_membership_tier.php

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
    $id = filter_input(INPUT_POST, 'membership_type_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['type_name'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = $_POST['description'] ?? '';
    $exclusive = isset($_POST['can_access_exclusive']) ? 1 : 0;

    if (!$id || empty($name) || $price === false || $price < 0) {
        throw new InvalidArgumentException('Invalid input parameters');
    }

    // Update database
    $stmt = $pdo->prepare("
        UPDATE membership_types
        SET type_name = :name,
            price = :price,
            description = :desc,
            can_access_exclusive = :exclusive,
            modified_at = NOW()
        WHERE membership_type_id = :id
    ");
    
    $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':price' => $price,
        ':desc' => $description,
        ':exclusive' => $exclusive
    ]);

    $_SESSION['message'] = 'Tier updated successfully';
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Update failed: ' . $e->getMessage()
    ]);
}
exit;