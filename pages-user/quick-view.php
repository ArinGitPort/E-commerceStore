<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

if (!isset($_GET['product_id'])) {
    die(json_encode(['error' => 'Product ID not specified']));
}

$product_id = (int)$_GET['product_id'];

try {
    // Get product details
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.product_id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Product not found");
    }

    // Get product images
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching product: " . $e->getMessage());
}
?>

<div class="modal-header">
    <h5 class="modal-title"><?= htmlspecialchars($product['product_name']) ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6">
            <?php if (!empty($images)): ?>
                <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="../assets/images/products/<?= htmlspecialchars($image['image_url']) ?>"
                                    class="d-block w-100" alt="<?= htmlspecialchars($product['product_name']) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            <?php else: ?>
                <div class="no-image-placeholder bg-light d-flex align-items-center justify-content-center" style="height:300px;">
                    <i class="fas fa-image fa-5x text-muted"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <h3><?= htmlspecialchars($product['product_name']) ?></h3>
            <div class="price fs-4 mb-3">₱<?= number_format($product['price'], 2) ?></div>

            <div class="d-flex justify-content-between mb-3">
                <div>
                    <span class="badge bg-info"><?= htmlspecialchars($product['category_name']) ?></span>
                    <?php if ($product['is_exclusive']): ?>
                        <span class="badge bg-warning">Exclusive</span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success">In Stock (<?= $product['stock'] ?>)</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Out of Stock</span>
                    <?php endif; ?>
                </div>
            </div>

            <p><?= htmlspecialchars($product['description']) ?></p>

            <!-- In the modal form (around line 60) -->
            <form method="POST" class="add-to-cart-form mt-4">
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                <div class="quantity-selector d-flex align-items-center mb-3">
                    <button type="button" class="modal-quantity-btn modal-minus btn btn-outline-secondary">-</button>
                    <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>"
                        class="modal-quantity-input form-control mx-2" style="width:80px;">
                    <button type="button" class="modal-quantity-btn modal-plus btn btn-outline-secondary">+</button>
                </div>
                <button type="submit" name="add_to_cart" class="btn btn-primary w-100 py-2"
                    <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-shopping-cart"></i>
                    <?= $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                </button>
            </form>
        </div>
    </div>
</div>