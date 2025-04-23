<?php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../includes/session-init.php';

// Get request parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// Check membership access
$hasMembershipAccess = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT mt.can_access_exclusive
        FROM users u
        LEFT JOIN memberships m ON u.user_id = m.user_id
        LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasMembershipAccess = $result['can_access_exclusive'] ?? false;
}

// Build base query
$query = "
    SELECT p.*,
           c.category_name,
           (SELECT image_url
            FROM product_images
            WHERE product_id = p.product_id
              AND is_primary = 1
            LIMIT 1) AS primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    WHERE p.stock > 0
";

$params = [];

// Add exclusive product filter
if (!$hasMembershipAccess) {
    $query .= " AND p.is_exclusive = FALSE";
}

// Add search filters
if (!empty($search)) {
    $query .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add category filter
if ($category_id > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$query .= " ORDER BY p.created_at DESC";

// Prepare and execute query using PDO
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML response
if (empty($products)): ?>
    <div class="col-12 text-center py-5">
        <h3>No products found</h3>
        <p>Try adjusting your search or filter criteria</p>
        <a href="shop.php" class="btn btn-primary">View All Products</a>
    </div>
<?php else: ?>
    <?php foreach ($products as $product): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="product-card border p-3 h-100">
                <div class="product-image position-relative">
                    <?php if (!empty($product['primary_image'])): ?>
                        <img src="../assets/images/products/<?= htmlspecialchars($product['primary_image']) ?>" 
                             alt="<?= htmlspecialchars($product['product_name']) ?>" 
                             class="img-fluid">
                    <?php else: ?>
                        <div class="no-image-placeholder bg-light d-flex align-items-center justify-content-center" 
                             style="height:200px;">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="quick-view position-absolute top-50 start-50 translate-middle p-2 rounded" 
                         data-product-id="<?= $product['product_id'] ?>" 
                         style="cursor:pointer; background-color: transparent !important;">
                        <i class="fas fa-eye"></i> Quick View
                    </div>
                </div>

                <div class="product-info mt-2">
                    <h3 class="h6"><?= htmlspecialchars($product['product_name']) ?></h3>
                    <div class="price fs-5">₱<?= number_format($product['price'], 2) ?></div>
                    <?php if ($product['is_exclusive']): ?>
                        <div class="mt-1">
                            <span class="badge bg-warning text-dark px-3 py-1" style="font-size: 0.85rem;">
                                <i class="fas fa-star me-1"></i> Subscriber Exclusive
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>