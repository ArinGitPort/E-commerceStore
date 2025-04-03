<?php
session_start();
require_once '../config/db_connection.php';

// Check if user has membership access
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

// Fetch products with filtering
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? null;
$showExclusive = $hasMembershipAccess;

try {
    $query = "
        SELECT p.*, c.category_name, 
               (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.stock > 0
    ";

    $params = [];

    // Apply search filter
    if ($search) {
        $query .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Apply category filter
    if ($category_id) {
        $query .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    // Apply exclusive products filter
    if (!$showExclusive) {
        $query .= " AND p.is_exclusive = FALSE";
    }

    $query .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch categories for dropdown
    $stmt = $pdo->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php?redirect=shop");
        exit;
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Check if product exists and is available
    $stmt = $pdo->prepare("SELECT stock, is_exclusive FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product || $product['stock'] < 1) {
        $_SESSION['cart_message'] = "Product not available!";
        header("Location: shop.php");
        exit;
    }

    // Check for exclusive product access
    if ($product['is_exclusive'] && !$hasMembershipAccess) {
        $_SESSION['cart_message'] = "You need a membership to purchase exclusive items!";
        header("Location: shop.php");
        exit;
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add item to cart or update quantity
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }

    $_SESSION['cart_message'] = "Item added to cart!";
    header("Location: shop.php");
    exit;
}
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
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                <?= htmlspecialchars($_SESSION['cart_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['cart_message']); ?>
        <?php endif; ?>

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
            <?php if (empty($products)): ?>
                <div class="col-12 text-center py-5">
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                    <a href="shop.php" class="btn btn-primary">View All Products</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="product-card border p-3 h-100">
                            <?php if ($product['is_exclusive']): ?>
                                <div class="exclusive-badge">Exclusive</div>
                            <?php endif; ?>

                            <div class="product-image position-relative">
                                <?php if ($product['primary_image']): ?>
                                    <img src="../assets/images/products/<?= htmlspecialchars($product['primary_image']) ?>"
                                        alt="<?= htmlspecialchars($product['product_name']) ?>" class="img-fluid">
                                <?php else: ?>
                                    <div class="no-image-placeholder bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="quick-view position-absolute top-50 start-50 translate-middle bg-light p-2 rounded"
                                    data-product-id="<?= $product['product_id'] ?>" style="cursor:pointer;">
                                    <i class="fas fa-eye"></i> Quick View
                                </div>
                            </div>
                            <div class="product-info mt-2">
                                <h3 class="h6"><?= htmlspecialchars($product['product_name']) ?></h3>
                                <div class="price fs-5">₱<?= number_format($product['price'], 2) ?></div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="rating text-warning">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Quick View Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fixed quantity buttons - prevent event bubbling
        $(document).on('click', '.quantity-btn', function(e) {
            e.stopPropagation(); // Prevent event from bubbling up
            const input = $(this).siblings('.quantity-input');
            let value = parseInt(input.val());
            const max = parseInt(input.attr('max')) || 999;

            if ($(this).hasClass('plus') && value < max) {
                input.val(value + 1);
            } else if ($(this).hasClass('minus') && value > 1) {
                input.val(value - 1);
            }
        });

        // Show quick view when clicking Add to Cart
        function showQuickView(productId, form) {
            $.ajax({
                url: 'quick-view.php',
                type: 'GET',
                data: {
                    product_id: productId
                },
                success: function(response) {
                    $('#quickViewModal .modal-content').html(response);
                    $('#quickViewModal').modal('show');

                    // Set up the form submission in the modal
                    $('#quickViewModal').off('submit', '.add-to-cart-form').on('submit', '.add-to-cart-form', function(e) {
                        e.preventDefault();
                        const formData = $(this).serialize();

                        $.ajax({
                            url: 'shop.php',
                            type: 'POST',
                            data: formData,
                            success: function() {
                                $('#quickViewModal').modal('hide');
                                // Reload to show updated cart
                                window.location.reload();
                            }
                        });
                    });

                    // Initialize quantity buttons in modal
                    $('#quickViewModal').off('click', '.modal-quantity-btn').on('click', '.modal-quantity-btn', function(e) {
                        e.stopPropagation();
                        const modalInput = $(this).siblings('.modal-quantity-input');
                        let value = parseInt(modalInput.val());
                        const max = parseInt(modalInput.attr('max')) || 999;

                        if ($(this).hasClass('modal-plus') && value < max) {
                            modalInput.val(value + 1);
                        } else if ($(this).hasClass('modal-minus') && value > 1) {
                            modalInput.val(value - 1);
                        }
                    });
                },
                error: function() {
                    $('#quickViewModal .modal-content').html('<div class="modal-body"><p>Error loading product details</p></div>');
                    $('#quickViewModal').modal('show');
                }
            });
        }

        // Original quick view click handler
        $(document).on('click', '.quick-view', function() {
            const productId = $(this).data('product-id');
            showQuickView(productId);
        });
    </script>
</body>

</html>