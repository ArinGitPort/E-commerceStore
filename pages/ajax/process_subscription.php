<?php
// pages/ajax/process_subscription.php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'success' => false, 
        'error' => 'You must be logged in to subscribe',
        'redirect' => '/login.php'
    ]);
    exit;
}

// Validate and sanitize input
$membership_type_id = filter_input(INPUT_POST, 'membership_type_id', FILTER_VALIDATE_INT);
$payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING) ?? 'demo';
$reference_number = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING) ?? 'DEMO-' . strtoupper(bin2hex(random_bytes(4)));

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // 1. Validate membership tier
    $stmt = $pdo->prepare("SELECT * FROM membership_types WHERE membership_type_id = ?");
    $stmt->execute([$membership_type_id]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tier) {
        // Fallback to the first paid tier if invalid ID provided
        $stmt = $pdo->prepare("
            SELECT * FROM membership_types 
            WHERE price > 0 
            ORDER BY price ASC 
            LIMIT 1
        ");
        $stmt->execute();
        $tier = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tier) {
            throw new Exception("No valid membership tiers available");
        }
        $membership_type_id = $tier['membership_type_id'];
    }
    
    // 2. Calculate dates
    $startDate = date('Y-m-d');
    $expiryDate = date('Y-m-d', strtotime('+1 month'));
    
    // 3. Update/create membership
    $stmt = $pdo->prepare("
        INSERT INTO memberships 
            (user_id, membership_type_id, start_date, expiry_date)
        VALUES 
            (:user_id, :type_id, :start_date, :expiry_date)
        ON DUPLICATE KEY UPDATE
            membership_type_id = VALUES(membership_type_id),
            start_date = VALUES(start_date),
            expiry_date = VALUES(expiry_date),
            modified_at = NOW()
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':type_id' => $membership_type_id,
        ':start_date' => $startDate,
        ':expiry_date' => $expiryDate
    ]);
    
    // 4. Record payment audit
    $auditStmt = $pdo->prepare("
        INSERT INTO subscriptions_audit 
            (user_id, membership_type_id, payment_amount, payment_method, 
             payment_status, reference_number, payment_date)
        VALUES 
            (:user_id, :type_id, :amount, :method, :status, :ref, NOW())
    ");
    $auditStmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':type_id' => $membership_type_id,
        ':amount' => $tier['price'],
        ':method' => $payment_method,
        ':status' => 'completed',
        ':ref' => $reference_number
    ]);
    
    // 5. Update user role if upgrading from Free
    if ($tier['price'] > 0) {
        $roleStmt = $pdo->prepare("
            UPDATE users 
            SET role_id = (SELECT role_id FROM roles WHERE role_name = 'Member'),
                modified_at = NOW()
            WHERE user_id = :user_id
        ");
        $roleStmt->execute([':user_id' => $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['user_role'] = 'Member';
    }
    
    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'tier' => [
                'id' => $tier['membership_type_id'],
                'name' => $tier['type_name'],
                'price' => $tier['price'],
                'is_exclusive' => (bool)$tier['can_access_exclusive']
            ],
            'dates' => [
                'start' => $startDate,
                'expiry' => $expiryDate
            ],
            'payment' => [
                'method' => $payment_method,
                'reference' => $reference_number
            ]
        ],
        'html' => "
            <div class='membership-success' data-membership-type='{$tier['type_name']}'>
                <div class='alert alert-success'>
                    <h4><i class='bi bi-check-circle-fill'></i> Welcome to {$tier['type_name']}!</h4>
                    <p>Your subscription is now active.</p>
                    <hr>
                    <ul class='mb-0'>
                        <li>Plan: <strong>{$tier['type_name']}</strong></li>
                        <li>Price: <strong>₱" . number_format($tier['price'], 2) . "/month</strong></li>
                        <li>Start Date: <strong>{$startDate}</strong></li>
                        <li>Expiry Date: <strong>{$expiryDate}</strong></li>
                        <li>Payment Method: <strong>" . ucfirst($payment_method) . "</strong></li>
                        <li>Reference: <code>{$reference_number}</code></li>
                    </ul>
                </div>
            </div>
        "
    ]);
    
} catch (Exception $e) {
    // Roll back transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'html' => "
            <div class='alert alert-danger'>
                <h4><i class='bi bi-exclamation-triangle-fill'></i> Subscription Error</h4>
                <p>We couldn't process your subscription.</p>
                <p class='mb-0'><small>Error: " . htmlspecialchars($e->getMessage()) . "</small></p>
            </div>
        "
    ]);
}