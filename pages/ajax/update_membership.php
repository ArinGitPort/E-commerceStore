<?php
require_once __DIR__ . '/../../config/db_connection.php';

header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method Not Allowed');
    }

    // Validate input parameters
    $required = ['user_id', 'membership_type_id'];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            http_response_code(400);
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize input
    $data = [
        'user_id' => filter_var($_POST['user_id'], FILTER_VALIDATE_INT),
        'membership_type_id' => filter_var($_POST['membership_type_id'], FILTER_VALIDATE_INT),
        'start_date' => $_POST['start_date'] ?? null,
        'expiry_date' => $_POST['expiry_date'] ?? null
    ];

    // Validate user exists
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = ?");
    $stmt->execute([$data['user_id']]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        throw new Exception('User not found');
    }

    // Get membership type details
    $stmt = $pdo->prepare("SELECT type_name FROM membership_types WHERE membership_type_id = ?");
    $stmt->execute([$data['membership_type_id']]);
    $type = $stmt->fetch();

    if (!$type) {
        http_response_code(400);
        throw new Exception('Invalid membership type');
    }

    // Handle Free membership
    if ($type['type_name'] === 'Free') {
        if (!empty($data['start_date']) || !empty($data['expiry_date'])) {
            throw new Exception('Free membership cannot have dates');
        }
        
        // Delete existing membership
        $stmt = $pdo->prepare("DELETE FROM memberships WHERE user_id = ?");
        $stmt->execute([$data['user_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Membership reset to Free'
        ]);
        exit;
    }

    // Validate paid membership dates
    if (empty($data['start_date']) || empty($data['expiry_date'])) {
        throw new Exception('Start and end dates are required for premium memberships');
    }

    // Validate date format and logic
    $startDate = new DateTime($data['start_date']);
    $endDate = new DateTime($data['expiry_date']);
    
    if ($startDate > $endDate) {
        throw new Exception('End date cannot be before start date');
    }

    // Upsert membership
    $stmt = $pdo->prepare("
        INSERT INTO memberships 
        (user_id, membership_type_id, start_date, expiry_date)
        VALUES (:user_id, :type_id, :start, :end)
        ON DUPLICATE KEY UPDATE
            membership_type_id = VALUES(membership_type_id),
            start_date = VALUES(start_date),
            expiry_date = VALUES(expiry_date),
            modified_at = NOW()
    ");

    $stmt->execute([
        ':user_id' => $data['user_id'],
        ':type_id' => $data['membership_type_id'],
        ':start' => $startDate->format('Y-m-d'),
        ':end' => $endDate->format('Y-m-d')
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Membership updated successfully',
        'details' => [
            'start_date' => $startDate->format('Y-m-d'),
            'expiry_date' => $endDate->format('Y-m-d'),
            'duration_days' => $endDate->diff($startDate)->days
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>