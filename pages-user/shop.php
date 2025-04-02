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

<div class="shop-container">
    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            <?= htmlspecialchars($_SESSION['cart_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['cart_message']); ?>
    <?php endif; ?>

    <div class="shop-header">
        <h1>Our Products</h1>
        <div class="search-filter">
            <div class="search-box">
                <input type="text" placeholder="Search products...">
                <button><i class="fas fa-search"></i></button>
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

    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="../assets/images/products/<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                    <div class="quick-view" data-product-id="<?= $product['product_id'] ?>">
                        <i class="fas fa-eye"></i> Quick View
                    </div>
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                    <div class="price">$<?= number_format($product['price'], 2) ?></div>
                    <div class="rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span>(<?= rand(10, 100) ?>)</span>
                    </div>
                    <form method="POST" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn minus">-</button>
                            <input type="number" name="quantity" value="1" min="1" class="quantity-input">
                            <button type="button" class="quantity-btn plus">+</button>
                        </div>
                        <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="pagination">
        <a href="#">&laquo;</a>
        <a href="#" class="active">1</a>
        <a href="#">2</a>
        <a href="#">3</a>
        <a href="#">&raquo;</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

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
    $(document).on('click', '.quantity-btn', function () {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val());

        if ($(this).hasClass('plus')) {
            input.val(value + 1);
        } else {
            if (value > 1) input.val(value - 1);
        }
    });

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
