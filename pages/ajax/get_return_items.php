<?php
// pages/ajax/get_return_items.php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get order ID from query string
$orderId = isset($_GET['order_id']) ? filter_var($_GET['order_id'], FILTER_VALIDATE_INT) : 0;
$userId = $_SESSION['user_id'];

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    // First check if this order already has a return
    $returnCheckSql = "SELECT return_id FROM returns WHERE archived_order_id = ?";
    $returnCheckStmt = $pdo->prepare($returnCheckSql);
    $returnCheckStmt->execute([$orderId]);
    
    if ($returnCheckStmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'type' => 'warning',
            'message' => 'A return request already exists for this order'
        ]);
        exit();
    }
    
    // Check if this is the user's order
    $orderSql = "SELECT ao.order_id 
                FROM archived_orders ao 
                WHERE ao.order_id = ? AND ao.customer_id = ?";
    $orderStmt = $pdo->prepare($orderSql);
    $orderStmt->execute([$orderId, $userId]);
    
    if ($orderStmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'type' => 'warning',
            'message' => 'Order not found or does not belong to you'
        ]);
        exit();
    }
    
    // Get items from the order
    $itemsSql = "SELECT 
                    aod.product_id,
                    p.product_name,
                    aod.quantity as ordered_quantity,
                    aod.quantity as max_returnable
                FROM archived_order_details aod
                JOIN products p ON aod.product_id = p.product_id
                WHERE aod.order_id = ?";
    $itemsStmt = $pdo->prepare($itemsSql);
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo json_encode([
            'success' => false,
            'type' => 'warning',
            'message' => 'No items found for this order'
        ]);
        exit();
    }
    
    // Return the items
    echo json_encode([
        'success' => true,
        'type' => 'data',
        'data' => $items
    ]);
    
} catch (PDOException $e) {
    // Log error instead of exposing details
    error_log("Return items fetch error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'type' => 'error',
        'message' => 'Database error occurred. Please try again.'
    ]);
}