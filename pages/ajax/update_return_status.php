<?php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

header('Content-Type: application/json');

http_response_code(200);

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Basic validations
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        throw new Exception('Unauthorized');
    }

    if (empty($data['return_id']) || empty($data['new_status'])) {
        http_response_code(400);
        throw new Exception('Missing required fields');
    }

    // Validate allowed status transitions
    $allowedStatuses = ['Approved', 'Rejected'];
    if (!in_array($data['new_status'], $allowedStatuses)) {
        http_response_code(400);
        throw new Exception('Invalid return status');
    }

    $returnId = (int) $data['return_id'];
    $userId   = (int) $_SESSION['user_id'];
    $newStatus = $data['new_status'];

    $pdo->beginTransaction();

    // Fetch existing return
    $stmt = $pdo->prepare("SELECT return_status, status_history FROM returns WHERE return_id = ?");
    $stmt->execute([$returnId]);
    $return = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$return) {
        http_response_code(404);
        throw new Exception('Return not found');
    }

    // Update return
    $updateStmt = $pdo->prepare("
        UPDATE returns 
        SET return_status = ?,
            last_status_update = NOW(),
            status_history = JSON_ARRAY_APPEND(
                COALESCE(status_history, '[]'), 
                '$', 
                JSON_OBJECT(
                    'old_status', ?, 
                    'new_status', ?, 
                    'changed_by', ?, 
                    'changed_at', NOW()
                )
            )
        WHERE return_id = ?
    ");
    $updateStmt->execute([
        $newStatus,
        $return['return_status'],
        $newStatus,
        $userId,
        $returnId
    ]);

    // Fetch updated return info
    $stmt = $pdo->prepare("
        SELECT r.*, ao.order_id, u.name AS customer_name 
        FROM returns r
        JOIN archived_orders ao ON r.order_id = ao.order_id
        JOIN users u ON ao.customer_id = u.user_id
        WHERE r.return_id = ?
    ");
    $stmt->execute([$returnId]);
    $updatedReturn = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'return' => $updatedReturn
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(http_response_code() === 200 ? 500 : http_response_code());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
