<?php
// Prevent any output before JSON response
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

// Get JSON data from request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate inputs
if (!isset($data['return_id']) || !isset($data['new_status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$returnId = intval($data['return_id']);
$newStatus = $data['new_status'];
$processedBy = isset($data['processed_by']) ? intval($data['processed_by']) : $_SESSION['user_id'];

// Validate status
if (!in_array($newStatus, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // 1. Update the return status
    $updateStmt = $pdo->prepare("
        UPDATE returns 
        SET return_status = :status, 
            processed_by = :processed_by,
            last_status_update = NOW() 
        WHERE return_id = :return_id
    ");
    
    $updateStmt->execute([
        ':status' => $newStatus,
        ':processed_by' => $processedBy,
        ':return_id' => $returnId
    ]);
    
    // 2. Record in status history table
    $historyStmt = $pdo->prepare("
        INSERT INTO return_status_history 
        (return_id, old_status, new_status, changed_by, notes) 
        VALUES (:return_id, 'Pending', :new_status, :changed_by, :notes)
    ");
    
    $historyStmt->execute([
        ':return_id' => $returnId,
        ':new_status' => $newStatus,
        ':changed_by' => $processedBy,
        ':notes' => 'Status updated through admin panel'
    ]);
    
    // Get updated return info for response
    $returnInfoStmt = $pdo->prepare("
        SELECT 
            r.return_id, 
            r.return_date, 
            r.reason, 
            r.order_id,
            r.return_status,
            u.name AS customer_name
        FROM returns r
        JOIN (
            SELECT o.order_id, o.customer_id, FALSE AS is_archived FROM orders o
            UNION ALL
            SELECT ao.order_id, ao.customer_id, TRUE AS is_archived FROM archived_orders ao
        ) AS combined_orders ON r.order_id = combined_orders.order_id
        JOIN users u ON combined_orders.customer_id = u.user_id
        WHERE r.return_id = :return_id
    ");
    
    $returnInfoStmt->execute([':return_id' => $returnId]);
    $returnInfo = $returnInfoStmt->fetch(PDO::FETCH_ASSOC);
    
    // Commit the transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "Return #{$returnId} has been {$newStatus}",
        'return' => $returnInfo
    ]);
    
} catch (PDOException $e) {
    // Roll back the transaction
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>