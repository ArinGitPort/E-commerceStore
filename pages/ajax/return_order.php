<?php
// pages/ajax/return_order.php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['order_id']) || !isset($data['reason']) || !isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit();
}

$orderId = filter_var($data['order_id'], FILTER_VALIDATE_INT);
$reason = trim($data['reason']);
$items = $data['items'];
$userId = $_SESSION['user_id'];

if (!$orderId || strlen($reason) < 20) {
    echo json_encode(['success' => false, 'error' => 'Reason too short']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify this is a completed order in archived_orders
    $orderSql = "SELECT ao.order_id, ao.customer_id, ao.total_price 
                FROM archived_orders ao 
                WHERE ao.order_id = ? AND ao.customer_id = ?";
    $orderStmt = $pdo->prepare($orderSql);
    $orderStmt->execute([$orderId, $userId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Order not found or doesn't belong to you");
    }
    
    // Check if return already exists for this order
    $checkSql = "SELECT return_id FROM returns WHERE archived_order_id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$orderId]);
    
    if ($checkStmt->rowCount() > 0) {
        throw new Exception("Return requested for this order");
    }
    
    // Insert return
    $returnSql = "INSERT INTO returns (archived_order_id, is_archived, order_id, reason, return_status) 
                  VALUES (?, TRUE, ?, ?, 'Pending')";
    $returnStmt = $pdo->prepare($returnSql);
    $returnStmt->execute([$orderId, $orderId, $reason]);
    $returnId = $pdo->lastInsertId();
    
    // Insert return items
    $itemSql = "INSERT INTO return_items (return_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
    $itemStmt = $pdo->prepare($itemSql);
    
    // Get products and their prices
    foreach ($items as $item) {
        // Verify product exists in the order
        $checkProdSql = "SELECT aod.total_price / aod.quantity as unit_price
                        FROM archived_order_details aod 
                        WHERE aod.order_id = ? AND aod.product_id = ?";
        $checkProdStmt = $pdo->prepare($checkProdSql);
        $checkProdStmt->execute([$orderId, $item['product_id']]);
        $prodInfo = $checkProdStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prodInfo) {
            throw new Exception("Product ID {$item['product_id']} not found in order");
        }
        
        // Insert the return item
        $itemStmt->execute([
            $returnId, 
            $item['product_id'], 
            $item['quantity'], 
            $prodInfo['unit_price']
        ]);
    }
    
    // Add status history entry
    $historySql = "INSERT INTO return_status_history (return_id, old_status, new_status, notes) 
                  VALUES (?, NULL, 'Pending', 'Return request created by customer')";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([$returnId]);
    
    // Commit transaction
    $pdo->commit();
    
    // Return success
    echo json_encode([
        'success' => true, 
        'message' => 'Return request submitted successfully', 
        'return_id' => $returnId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    
} catch (PDOException $e) {
    // Rollback transaction on database error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error instead of showing raw DB error to user
    error_log("Return processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred. Please try again.']);
}