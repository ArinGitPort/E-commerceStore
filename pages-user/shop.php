<?php
session_start();
require_once '../config/db_connection.php';

// Fetch products from database
try {
    $stmt = $pdo->prepare("SELECT * FROM products"); // Removed 'active' column filter
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

// Handle add to cart (simplified example)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=shop");
        exit;
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Add to cart logic would go here
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
    <title>Shop - Bunniwinkle</title>
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
        <div class="search-filter d-flex justify-content-center align-items-center gap-3 mt-3">
            <div class="search-box">
                <input type="text" class="form-control" placeholder="Search products...">
                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
            <div class="filter-dropdown">
                <select class="form-select">
                    <option selected>All Categories</option>
                    <option>Category 1</option>
                    <option>Category 2</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Updated Product Grid using Bootstrap row and columns -->
    <div class="product-grid row g-4">
        <?php foreach ($products as $product): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="product-card border p-3 h-100">
                    <div class="product-image position-relative">
                        <img src="../assets/images/products/<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="img-fluid">
                        <div class="quick-view position-absolute top-50 start-50 translate-middle bg-light p-2 rounded" data-product-id="<?= $product['product_id'] ?>" style="cursor:pointer;">
                            <i class="fas fa-eye"></i> Quick View
                        </div>
                    </div>
                    <div class="product-info mt-2">
                        <h3 class="h6"><?= htmlspecialchars($product['product_name']) ?></h3>
                        <div class="price fs-5">₱<?= number_format($product['price'], 2) ?></div>
                        <div class="rating text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="fs-6 text-muted">(<?= rand(10, 100) ?>)</span>
                        </div>
                        <form method="POST" class="add-to-cart-form mt-2">
                            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                            <div class="quantity-selector d-flex align-items-center mb-2">
                                <button type="button" class="quantity-btn btn btn-outline-secondary btn-sm minus">-</button>
                                <input type="number" name="quantity" value="1" min="1" class="quantity-input form-control form-control-sm mx-1" style="width:60px;">
                                <button type="button" class="quantity-btn btn btn-outline-secondary btn-sm plus">+</button>
                            </div>
                            <button type="submit" name="add_to_cart" class="add-to-cart-btn btn btn-primary w-100">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination (static example) -->
    <div class="pagination d-flex justify-content-center mt-4">
        <a href="#">&laquo;</a>
        <a href="#" class="active">1</a>
        <a href="#">2</a>
        <a href="#">3</a>
        <a href="#">&raquo;</a>
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Quantity buttons
    $(document).on('click', '.quantity-btn', function () {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val());
        if ($(this).hasClass('plus')) {
            input.val(value + 1);
        } else if (value > 1) {
            input.val(value - 1);
        }
    });

    // Quick view modal via AJAX
    $(document).on('click', '.quick-view', function () {
        const productId = $(this).data('product-id');
        $.ajax({
            url: 'quick-view.php',
            type: 'GET',
            data: {product_id: productId},
            success: function (response) {
                $('#quickViewModal .modal-content').html(response);
                $('#quickViewModal').modal('show');
            }
        });
    });
</script>
</body>
</html>
