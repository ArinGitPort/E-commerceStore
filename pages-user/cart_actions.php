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

// Helper function to validate if user can access exclusive product
function user_can_access_product(PDO $pdo, int $userId, int $productId): bool {
    $stmt = $pdo->prepare("
        SELECT p.is_exclusive, p.min_membership_level, mt.membership_type_id, mt.can_access_exclusive
        FROM products p
        LEFT JOIN memberships m ON m.user_id = :user_id
        LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
        WHERE p.product_id = :product_id
    ");
    $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    $row = $stmt->fetch();

    if (!$row) return false; // Invalid product
    if (!$row['is_exclusive']) return true; // Anyone can access

    return $row['can_access_exclusive'] &&
           ($row['membership_type_id'] >= $row['min_membership_level']);
}

// Handle update cart item action (AJAX quantity update)
if (isset($_POST['action']) && $_POST['action'] === 'update_cart_item') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'login_required' => true,
            'message' => 'You must be logged in to update items in the cart.'
        ]);
        exit;
    }

    // Check access to exclusive products
    if (!user_can_access_product($pdo, $_SESSION['user_id'], $productId)) {
        echo json_encode([
            'error' => 'This product is exclusive. Upgrade your membership to modify it.'
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $_SESSION['user_id'], $productId]);

        $_SESSION['cart'][$productId] = $quantity;

        // Recalculate cart total
        $cartItems = get_cart_details($pdo);
        $newTotal = 0;
        foreach ($cartItems as $item) {
            $newTotal += $item['price'] * $item['quantity'];
        }

        echo json_encode([
            'success'  => true,
            'newTotal' => $newTotal
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle add to cart action
if (isset($_POST['add_to_cart'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'login_required' => true,
            'message' => 'You must be logged in to add items to the cart.'
        ]);
        exit;
    }

    // Check access to exclusive products
    if (!user_can_access_product($pdo, $_SESSION['user_id'], $productId)) {
        echo json_encode([
            'error' => 'You are not allowed to add this exclusive item. Please upgrade your membership.'
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->execute([$_SESSION['user_id'], $productId, $quantity]);

        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 0;
        }
        $_SESSION['cart'][$productId] += $quantity;

        echo json_encode([
            'success'    => true,
            'message'    => 'Item added to cart successfully!',
            'cart_count' => count($_SESSION['cart'])
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Invalid action fallback
echo json_encode(['error' => 'Invalid request data.']);
exit;
