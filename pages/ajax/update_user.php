<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate input
    $input = filter_input_array(INPUT_POST, [
        'user_id' => FILTER_VALIDATE_INT,
        'name' => FILTER_SANITIZE_STRING,
        'email' => FILTER_VALIDATE_EMAIL,
        'role_id' => FILTER_VALIDATE_INT
    ]);

    if (!$input['user_id'] || $input['user_id'] < 1) {
        throw new Exception('Invalid user ID');
    }

    if (empty($input['name'])) {
        throw new Exception('Name is required');
    }

    if (!$input['email']) {
        throw new Exception('Valid email is required');
    }

    if (!$input['role_id'] || $input['role_id'] < 1) {
        throw new Exception('Valid role is required');
    }

    // Update user in database
    $stmt = $pdo->prepare("
        UPDATE users 
        SET 
            name = :name,
            email = :email,
            role_id = :role_id,
            modified_at = NOW()
        WHERE user_id = :user_id
    ");

    $stmt->execute([
        ':name' => $input['name'],
        ':email' => $input['email'],
        ':role_id' => $input['role_id'],
        ':user_id' => $input['user_id']
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No changes made or user not found');
    }

    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>