<?php
/**
 * Synchronizes the cart between database and session
 * 
 * @param PDO $pdo Database connection
 * @return void
 */
function sync_cart(PDO $pdo): void
{
    // Only for logged-in users
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    try {
        // Initialize session cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Get cart items from database
        $stmt = $pdo->prepare("
            SELECT product_id, quantity 
            FROM cart_items 
            WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $dbCart = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (empty($dbCart) && empty($_SESSION['cart'])) {
            // No cart items in either place
            return;
        }
        
        // Situations to handle:
        // 1. Items in DB but not in session
        // 2. Items in session but not in DB
        // 3. Items in both but with different quantities
        
        $pdo->beginTransaction();
        
        // Add/update items from session to DB
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            if (!isset($dbCart[$productId])) {
                // Item in session but not in DB - add it
                $stmt = $pdo->prepare("
                    INSERT INTO cart_items (user_id, product_id, quantity)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $productId, $quantity]);
            } else if ($dbCart[$productId] != $quantity) {
                // Item in both but quantities differ - update DB
                $stmt = $pdo->prepare("
                    UPDATE cart_items 
                    SET quantity = ?
                    WHERE user_id = ? AND product_id = ?
                ");
                $stmt->execute([$quantity, $_SESSION['user_id'], $productId]);
            }
        }
        
        // Add items from DB to session
        foreach ($dbCart as $productId => $quantity) {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Sync cart error: " . $e->getMessage());
    }
}