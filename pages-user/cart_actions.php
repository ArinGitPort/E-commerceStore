<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit;
}

// Handle update cart item action (for dynamic quantity update)
if (isset($_POST['action']) && $_POST['action'] === 'update_cart_item') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'login_required' => true,
            'message' => 'You must be logged in to update items in the cart.'
        ]);
        exit;
    }
    
    try {
        // Update the cart item in the database
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $_SESSION['user_id'], $productId]);
        
        // Update session cart
        $_SESSION['cart'][$productId] = $quantity;
        
        // Recalculate new order total using the helper function
        $cartItems = get_cart_details($pdo);
        $newTotal = 0;
        foreach ($cartItems as $item) {
            $newTotal += $item['price'] * $item['quantity'];
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'newTotal' => $newTotal
        ]);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle add to cart action
if (isset($_POST['add_to_cart'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'login_required' => true,
            'message' => 'You must be logged in to add items to the cart.'
        ]);
        exit;
    }

    try {
        // Insert or update the cart item in the database
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->execute([$_SESSION['user_id'], $productId, $quantity]);

        // Update session cart
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 0;
        }
        $_SESSION['cart'][$productId] += $quantity;
        
        // Compute distinct cart count (number of keys)
        $cartCount = count($_SESSION['cart']);

        header('Content-Type: application/json');
        echo json_encode([
            'success'    => true,
            'message'    => 'Item added to cart successfully!',
            'cart_count' => $cartCount
        ]);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// If no valid action is found, return an error
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request data.']);
exit;
?>
