<?php
// session-init.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

defined('ROOT_PATH') || define('ROOT_PATH', realpath(dirname(__DIR__)));

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_name('BUNNISHOP_SESS');
    session_start();
}

// Initialize core session variables
$_SESSION['user_id'] = $_SESSION['user_id'] ?? null;
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=bunnishop;charset=utf8mb4",
        "root",
        "1234",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

/**
 * Sync database cart to session — used for logged-in users only
 */
function sync_cart(PDO $pdo): void
{
    if (!isset($_SESSION['user_id'])) return;

    try {
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['cart'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Cart sync error: " . $e->getMessage());
    }
}

/**
 * Get detailed cart items for checkout, cart display, etc.
 */ function get_cart_details(PDO $pdo): array
{
    $items = [];

    try {
        if (isset($_SESSION['user_id'])) {
            // Logged-in: use DB cart
            $stmt = $pdo->prepare("
                SELECT p.product_id, p.product_name, p.price, p.stock, p.is_exclusive,
                       ci.quantity, c.category_name
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.product_id
                JOIN categories c ON p.category_id = c.category_id
                WHERE ci.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Guest: use session cart
            if (!empty($_SESSION['cart'])) {
                $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
                $stmt = $pdo->prepare("
                    SELECT p.product_id, p.product_name, p.price, p.stock, p.is_exclusive,
                           c.category_name
                    FROM products p
                    JOIN categories c ON p.category_id = c.category_id
                    WHERE p.product_id IN ($placeholders)
                ");
                $stmt->execute(array_keys($_SESSION['cart']));
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Attach quantities from session cart data
                foreach ($items as &$item) {
                    $item['quantity'] = $_SESSION['cart'][$item['product_id']];
                }
            }
        }

        // Now, retrieve all images for the products in the cart.
        $productIds = array_column($items, 'product_id');
        if ($productIds) {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $stmt = $pdo->prepare("
                SELECT product_id, image_url, is_primary, alt_text, sort_order
                FROM product_images
                WHERE product_id IN ($placeholders)
                ORDER BY product_id, sort_order ASC
            ");
            $stmt->execute($productIds);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group images by product ID
            $imagesByProduct = [];
            foreach ($images as $img) {
                $imagesByProduct[$img['product_id']][] = $img;
            }

            // Attach images to each item and optionally set a primary image
            foreach ($items as &$item) {
                $item['images'] = $imagesByProduct[$item['product_id']] ?? [];

                // Look for a primary image
                foreach ($item['images'] as $img) {
                    if ($img['is_primary']) {
                        $item['primary_image'] = $img;
                        break;
                    }
                }
                // If no image is flagged as primary, just pick the first available image if there is any.
                if (!isset($item['primary_image']) && !empty($item['images'])) {
                    $item['primary_image'] = $item['images'][0];
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Cart details error: " . $e->getMessage());
    }

    return $items;
}




/**
 * CSRF token generator
 */
function generate_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Count total number of product types in the cart
 * You can modify this to return total quantity instead if preferred
 */
function get_cart_count(PDO $pdo): int
{
    if (!isset($_SESSION['user_id'])) {
        return count($_SESSION['cart'] ?? []);
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Cart count error: " . $e->getMessage());
        return 0;
    }
}
