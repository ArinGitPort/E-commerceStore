<?php
// pages/ajax/update_return_status.php
// Ensure we're outputting JSON only, not HTML error messages
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Function to return error responses
function sendErrorResponse($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit();
}

// Log errors to file instead of displaying them
function logError($message) {
    error_log("Return status update error: " . $message);
}

// Check if user is logged in as admin/staff
if (!isset($_SESSION['user_id'])) {
    sendErrorResponse('User not logged in', 401);
}

// Check user role if you have role-based restrictions
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [3, 4, 5])) { // Staff, Admin, Super Admin
    sendErrorResponse('Unauthorized access. Staff or admin privileges required.', 403);
}

try {
    // Get the JSON input
    $jsonInput = file_get_contents('php://input');
    if (empty($jsonInput)) {
        sendErrorResponse('No data received');
    }
    
    $data = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON data: ' . json_last_error_msg());
    }
    
    // Validate input
    if (!isset($data['return_id']) || !isset($data['new_status'])) {
        sendErrorResponse('Missing required fields');
    }
    
    $returnId = filter_var($data['return_id'], FILTER_VALIDATE_INT);
    $newStatus = $data['new_status'];
    // Use the current user's ID if processed_by not provided
    $processedBy = isset($data['processed_by']) ? 
                  filter_var($data['processed_by'], FILTER_VALIDATE_INT) : 
                  $_SESSION['user_id'];
    
    if (!$returnId || !in_array($newStatus, ['Approved', 'Rejected'])) {
        sendErrorResponse('Invalid return ID or status');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get current status and return details
    $returnSql = "SELECT r.return_status, r.order_id, r.is_archived 
                 FROM returns r 
                 WHERE r.return_id = ?";
    $returnStmt = $pdo->prepare($returnSql);
    $returnStmt->execute([$returnId]);
    $return = $returnStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$return) {
        throw new Exception("Return #$returnId not found");
    }
    
    if ($return['return_status'] !== 'Pending') {
        throw new Exception("This return has already been processed and is currently '{$return['return_status']}'");
    }
    
    // Update return status
    $updateSql = "UPDATE returns 
                 SET return_status = ?, processed_by = ?, last_status_update = NOW() 
                 WHERE return_id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$newStatus, $processedBy, $returnId]);
    
    // Add status history entry - fixed column name from updated_by to changed_by to match the table structure
    $historySql = "INSERT INTO return_status_history (return_id, old_status, new_status, changed_by, notes) 
                  VALUES (?, 'Pending', ?, ?, ?)";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([
        $returnId, 
        $newStatus, 
        $processedBy,
        $newStatus === 'Approved' ? 'Return request approved' : 'Return request rejected'
    ]);
    
    // If approved, update inventory
    if ($newStatus === 'Approved') {
        // Get return items
        $itemsSql = "SELECT product_id, quantity FROM return_items WHERE return_id = ?";
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([$returnId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            // If no items in return_items, try return_details table
            $itemsSql = "SELECT product_id, quantity FROM return_details WHERE return_id = ?";
            $itemsStmt = $pdo->prepare($itemsSql);
            $itemsStmt->execute([$returnId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (!empty($items)) {
            // Update product inventory for each item
            $updateInventorySql = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
            $inventoryStmt = $pdo->prepare($updateInventorySql);
            
            foreach ($items as $item) {
                $inventoryStmt->execute([$item['quantity'], $item['product_id']]);
                
                // Try to log inventory change if the table exists
                try {
                    // First check if the inventory_log table exists
                    $checkTableSql = "SELECT 1 FROM information_schema.tables 
                                     WHERE table_schema = DATABASE() 
                                     AND table_name = 'inventory_log'";
                    $tableExists = $pdo->query($checkTableSql)->fetchColumn();
                    
                    if ($tableExists) {
                        $logSql = "INSERT INTO inventory_log (product_id, quantity_change, change_type, reference_id, reference_type, created_by) 
                                  VALUES (?, ?, 'Return', ?, 'Return', ?)";
                        $logStmt = $pdo->prepare($logSql);
                        $logStmt->execute([
                            $item['product_id'], 
                            $item['quantity'], 
                            $returnId,
                            $processedBy
                        ]);
                    }
                } catch (PDOException $e) {
                    // Just log the error but continue processing
                    logError("Could not log inventory change: " . $e->getMessage());
                }
            }
        } else {
            logError("No items found for return #$returnId");
        }
    }
    
    // Get customer information for response
    $customerSql = "SELECT u.name as customer_name, r.return_date, r.reason, r.order_id, r.return_status
                   FROM returns r
                   JOIN users u ON u.user_id = (
                       SELECT customer_id FROM " . ($return['is_archived'] ? "archived_orders" : "orders") . "
                       WHERE order_id = ?
                   )
                   WHERE r.return_id = ?";
    $customerStmt = $pdo->prepare($customerSql);
    $customerStmt->execute([$return['order_id'], $returnId]);
    $returnInfo = $customerStmt->fetch(PDO::FETCH_ASSOC);
    
    // If we couldn't get the customer info, still return basic info
    if (!$returnInfo) {
        $returnInfo = [
            'return_id' => $returnId,
            'order_id' => $return['order_id'],
            'return_status' => $newStatus,
            'customer_name' => 'Customer', // Default value
            'return_date' => date('Y-m-d H:i:s'),
            'reason' => 'Return reason unavailable'
        ];
    }
    
    // Add entry to audit_logs if possible
    try {
        $auditSql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, action_type, affected_data) 
                    VALUES (?, ?, 'returns', ?, ?, 'UPDATE', ?)";
        $auditStmt = $pdo->prepare($auditSql);
        $auditStmt->execute([
            $processedBy,
            "Updated return status to $newStatus",
            $returnId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            json_encode(['old_status' => 'Pending', 'new_status' => $newStatus])
        ]);
    } catch (PDOException $e) {
        // Just log the error but continue processing
        logError("Could not create audit log: " . $e->getMessage());
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Return success with return info
    echo json_encode([
        'success' => true, 
        'message' => "Return #{$returnId} marked as {$newStatus} successfully", 
        'return' => $returnInfo
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    logError($e->getMessage());
    sendErrorResponse($e->getMessage());
    
} catch (PDOException $e) {
    // Rollback transaction on database error
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error instead of showing raw DB error to user
    logError($e->getMessage());
    sendErrorResponse('Database error occurred. Please try again.');
}