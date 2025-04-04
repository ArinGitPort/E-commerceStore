<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

header('Content-Type: application/json'); // Ensure JSON header for AJAX responses

// Check if user has membership access
$hasMembershipAccess = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT mt.can_access_exclusive FROM users u LEFT JOIN memberships m ON u.user_id = m.user_id LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id WHERE u.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasMembershipAccess = $result['can_access_exclusive'] ?? false;
}

// Handle AJAX add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['redirect' => '../login.php?redirect=shop']);
        exit;
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    $stmt = $pdo->prepare("SELECT stock, is_exclusive FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || $product['stock'] < 1) {
        echo json_encode(['error' => 'Product not available']);
        exit;
    }

    if ($product['is_exclusive'] && !$hasMembershipAccess) {
        echo json_encode(['error' => 'You need a membership to purchase exclusive items']);
        exit;
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }

    echo json_encode([
        'success' => true,
        'cart_count' => array_sum($_SESSION['cart']),
        'message' => 'Item added to cart!'
    ]);
    exit;
}

// Reset header for HTML output
header_remove('Content-Type');

// Fetch products
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? null;
$showExclusive = $hasMembershipAccess;

$query = "
    SELECT p.*, c.category_name, 
           (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    WHERE p.stock > 0
";

$params = [];
if ($search) {
    $query .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}
if (!$showExclusive) {
    $query .= " AND p.is_exclusive = FALSE";
}
$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - BunniShop</title>
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
</head>
<body>
<?php include '../includes/user-navbar.php'; ?>
<div class="shop-container container my-4">
    <div class="shop-header text-center mb-4">
        <h1>Our Products</h1>
        <?php if ($showExclusive): ?>
            <div class="alert alert-info">You have access to exclusive products!</div>
        <?php endif; ?>
        <form method="GET" class="search-filter d-flex justify-content-center align-items-center gap-3 mt-3">
            <div class="search-box">
                <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-dropdown">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>" <?= ($category_id == $category['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($search || $category_id): ?>
                <a href="shop.php" class="btn btn-outline-secondary">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="product-grid row g-4">
        <?php foreach ($products as $product): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="product-card border p-3 h-100">
                    <?php if ($product['is_exclusive']): ?>
                        <div class="exclusive-badge">Exclusive</div>
                    <?php endif; ?>
                    <div class="product-image position-relative">
                        <?php if ($product['primary_image']): ?>
                            <img src="../assets/images/products/<?= htmlspecialchars($product['primary_image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="img-fluid">
                        <?php else: ?>
                            <div class="no-image-placeholder bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="quick-view position-absolute top-50 start-50 translate-middle bg-light p-2 rounded" data-product-id="<?= $product['product_id'] ?>" style="cursor:pointer;">
                            <i class="fas fa-eye"></i> Quick View
                        </div>
                    </div>
                    <div class="product-info mt-2">
                        <h3 class="h6"><?= htmlspecialchars($product['product_name']) ?></h3>
                        <div class="price fs-5">₱<?= number_format($product['price'], 2) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content"></div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).on('click', '.quick-view', function () {
    const productId = $(this).data('product-id');
    $.get('quick-view.php', { product_id: productId }, function (response) {
        $('#quickViewModal .modal-content').html(response);
        $('#quickViewModal').modal('show');
    });
});

$(document).on('submit', '.add-to-cart-form', function (e) {
    e.preventDefault();
    const form = $(this);
    const btn = form.find('button[type="submit"]');
    const originalText = btn.html();

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Adding...');

    $.ajax({
        url: 'shop.php',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function (response) {
            if (response.redirect) {
                window.location.href = response.redirect;
            } else if (response.success) {
                alert(response.message);
                $('#quickViewModal').modal('hide');
                window.location.reload();
            } else {
                alert(response.error || 'Something went wrong.');
            }
        },
        error: function (xhr, status, error) {
            console.error('Add to Cart Error:', error);
            alert('Failed to add to cart. Please try again.');
        },
        complete: function () {
            btn.prop('disabled', false).html(originalText);
        }
    });
});
</script>
</body>
</html>
