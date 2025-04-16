<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

try {
    // Validate and sanitize input
    $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    
    if (!$userId || $userId < 1) {
        http_response_code(400);
        throw new Exception('Invalid User ID format');
    }

    // Get complete membership data
    $stmt = $pdo->prepare("
        SELECT 
            m.membership_type_id,
            mt.type_name,
            m.start_date,
            m.expiry_date,
            DATEDIFF(m.expiry_date, CURDATE()) AS days_remaining,
            m.modified_at
        FROM memberships m
        JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
        WHERE m.user_id = ?
        ORDER BY m.start_date DESC
        LIMIT 1
    ");

    $stmt->execute([$userId]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'success' => true,
        'data' => $membership ?: null
    ];

    if (!$membership) {
        $response['message'] = 'No active membership found';
    }

    echo json_encode($response);

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