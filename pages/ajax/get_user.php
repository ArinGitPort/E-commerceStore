<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

try {
    // Validate request method
    // 1. Check if parameter exists
    if (!isset($_GET['user_id'])) {
        throw new Exception("User ID required");
    }

    // 2. Validate data type
    $userId = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
    if ($userId === false || $userId < 1) {
        throw new Exception("Invalid User ID format");
    }

    // 3. Check if user exists in the database
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception("User not found");
    }

    // Fetch user data with membership info
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.name,
            u.email,
            u.role_id,
            u.is_active,
            u.created_at,
            m.membership_type_id,
            mt.type_name AS membership_type,
            m.start_date,
            m.expiry_date
        FROM users u
        LEFT JOIN memberships m ON u.user_id = m.user_id
        LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
        WHERE u.user_id = ?
    ");

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        throw new Exception('User not found');
    }

    // Format dates for consistency
    $user['created_at'] = date('c', strtotime($user['created_at']));
    $user['start_date'] = $user['start_date'] ? date('c', strtotime($user['start_date'])) : null;
    $user['expiry_date'] = $user['expiry_date'] ? date('c', strtotime($user['expiry_date'])) : null;

    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
