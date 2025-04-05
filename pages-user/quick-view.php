<?php
// quick-view.php

require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Get product_id from GET
if (!isset($_GET['product_id'])) {
    echo "<div class='modal-body'><p>Invalid product request.</p></div>";
    exit;
}

$product_id = (int)$_GET['product_id'];

// Fetch product details
$stmt = $pdo->prepare("
    SELECT p.*, c.category_name
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<div class='modal-body'><p>Product not found.</p></div>";
    exit;
}

// Fetch images
$imageStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
$imageStmt->execute([$product_id]);
$images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
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
                                     class="d-block w-100"
                                     alt="<?= htmlspecialchars($product['product_name']) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            <?php else: ?>
                <div class="no-image-placeholder bg-light d-flex align-items-center justify-content-center"
                     style="height:300px;">
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

            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

            <!-- Add to Cart Form (handled via AJAX) -->
            <form method="POST" class="add-to-cart-form mt-4">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

                <div class="quantity-selector d-flex align-items-center mb-3">
                    <button type="button" class="modal-quantity-btn modal-minus btn btn-outline-secondary">-</button>
                    <input type="number" name="quantity"
                           value="1"
                           min="1"
                           max="<?= $product['stock'] ?>"
                           class="modal-quantity-input form-control mx-2"
                           style="width:80px;">
                    <button type="button" class="modal-quantity-btn modal-plus btn btn-outline-secondary">+</button>
                </div>

                <button type="submit"
                        class="btn btn-primary w-100 py-2"
                        <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-shopping-cart"></i>
                    <?= $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                </button>
            </form>
            <!--
              Note: The form submission is caught by the global AJAX handler (from shop.php)
              which processes the request via cart_actions.php. The response from that handler
              updates the navbar cart icon (using get_cart_count()) to reflect the number of distinct items.
            -->
        </div>
    </div>
</div>
