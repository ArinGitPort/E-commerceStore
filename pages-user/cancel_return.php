<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get the POST data (JSON)
$data = json_decode(file_get_contents('php://input'), true);
$return_id = isset($data['return_id']) ? intval($data['return_id']) : 0;

// Validate input
if ($return_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid return ID']);
    exit;
}

// Get current user ID
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get return information to verify ownership or admin access
    $stmt = $conn->prepare("
        SELECT r.*, u.role_id FROM returns r 
        JOIN archived_orders ao ON r.archived_order_id = ao.order_id
        JOIN users u ON ao.customer_id = u.user_id
        WHERE r.return_id = ? AND (ao.customer_id = ? OR ? IN (SELECT user_id FROM users WHERE role_id >= 3))
    ");
    
    $stmt->bind_param("iii", $return_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Return not found or user not authorized
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Return not found or you are not authorized to cancel it']);
        exit;
    }
    
    $return_data = $result->fetch_assoc();
    
    // Check if return can be cancelled (only Pending returns can be cancelled)
    if ($return_data['return_status'] !== 'Pending') {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Only pending returns can be cancelled']);
        exit;
    }
    
    // Update return status to 'Rejected'
    $update_stmt = $conn->prepare("UPDATE returns SET return_status = 'Rejected', last_status_update = NOW() WHERE return_id = ?");
    $update_stmt->bind_param("i", $return_id);
    $update_stmt->execute();
    
    if ($update_stmt->affected_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update return status']);
        exit;
    }
    
    // Add entry to the return status history
    $notes = "Return request cancelled by user";
    $status_stmt = $conn->prepare("
        INSERT INTO return_status_history 
        (return_id, old_status, new_status, changed_by, notes) 
        VALUES (?, 'Pending', 'Rejected', ?, ?)
    ");
    $status_stmt->bind_param("iis", $return_id, $user_id, $notes);
    $status_stmt->execute();
    
    // Get the items that were to be returned
    $items_stmt = $conn->prepare("
        SELECT ri.product_id, ri.quantity 
        FROM return_items ri
        WHERE ri.return_id = ?
    ");
    $items_stmt->bind_param("i", $return_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    // No need to adjust inventory since the return is cancelled (items stay with customer)
    
    // Create an audit log entry
    $action = "Return #$return_id cancelled";
    $table_name = "returns";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $affected_data = json_encode([
        'return_id' => $return_id,
        'old_status' => 'Pending',
        'new_status' => 'Rejected'
    ]);
    
    $audit_stmt = $conn->prepare("
        INSERT INTO audit_logs 
        (user_id, action, table_name, record_id, ip_address, user_agent, affected_data, action_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'UPDATE')
    ");
    $audit_stmt->bind_param("ississs", $user_id, $action, $table_name, $return_id, $ip_address, $user_agent, $affected_data);
    $audit_stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    echo json_encode(['success' => true, 'message' => 'Return successfully cancelled']);
    
} catch (Exception $e) {
    // Roll back the transaction in case of error
    $conn->rollback();
    
    // Log the error (you should have an error logging system)
    error_log("Error cancelling return: " . $e->getMessage());
    
    // Return error response
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}

// Close the connection
$conn->close();
?>