<?php
// inventory_actions.php
ob_start(); // Start output buffering
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Check admin authentication. If not authenticated as Admin, redirect to login.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../pages/login.php");
    exit;
}


//ADD MEMBERSHIP LEVEL PRODUCT ACCESS
// Handle setting primary image via AJAX
if ($_GET['action'] === 'set_primary_image' && isset($_POST['product_id'], $_POST['image_id'])) {
    header('Content-Type: application/json');
    try {
        $pdo->beginTransaction();

        // First reset all primary flags for this product
        $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
        $stmt->execute([$_POST['product_id']]);

        // Then set the selected image as primary
        $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE image_id = ? AND product_id = ?");
        $stmt->execute([$_POST['image_id'], $_POST['product_id']]);

        $pdo->commit();

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
}


// Handle AJAX requests for modals (edit or view product)
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_product':
            // Retrieve product details (with category name) based on provided product id.
            $stmt = $pdo->prepare("SELECT p.*, c.category_name, p.min_membership_level FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ?");
            $stmt->execute([$_GET['id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                // Retrieve product images for the product.
                $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
                $stmt->execute([$product['product_id']]);
                $productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Retrieve all categories (for the category dropdown in the modal).
                $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
?>
                <!-- Modal header for editing product -->
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <!-- Form for updating product details -->
                <form action="inventory_actions.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Product details form fields -->
                                <div class="mb-3">
                                    <label class="form-label">Product Name*</label>
                                    <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">SKU*</label>
                                    <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($product['sku']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category*</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['category_id'] ?>" <?= $product['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['category_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Price*</label>
                                        <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Stock*</label>
                                        <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" name="is_exclusive" class="form-check-input" id="isExclusive" <?= $product['is_exclusive'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isExclusive">Exclusive Product</label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Minimum Membership Access</label>
                                    <select name="min_membership_level" class="form-select" id="minMembershipSelect" <?= !$product['is_exclusive'] ? 'disabled' : '' ?>>
                                        <option value="">All Users</option>
                                        <?php
                                        $membershipTypes = $pdo->query("SELECT * FROM membership_types")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($membershipTypes as $type):
                                        ?>
                                            <option value="<?= $type['membership_type_id'] ?>" <?= $product['min_membership_level'] == $type['membership_type_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($type['type_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <script>
                                    $('#isExclusive').on('change', function() {
                                        const isChecked = $(this).is(':checked');
                                        $('#minMembershipSelect').prop('disabled', !isChecked);
                                    });
                                </script>


                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Current Images</label>
                                    <div class="current-images">
                                        <?php foreach ($productImages as $img): ?>
                                            <div class="image-thumbnail">
                                                <img src="../assets/images/products/<?= htmlspecialchars($img['image_url']) ?>" alt="<?= htmlspecialchars($img['alt_text']) ?>">
                                                <div class="image-actions">
                                                    <input type="radio" name="primary_image" value="<?= $img['image_id'] ?>" <?= $img['is_primary'] ? 'checked' : '' ?>>
                                                    <label>Primary</label>
                                                    <button type="button" class="btn btn-sm btn-danger delete-image-btn" data-image-id="<?= $img['image_id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Add More Images</label>
                                    <input type="file" name="product_images[]" class="form-control" multiple accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
                <script>
                    // Handle image deletion in the modal via AJAX
                    $('.delete-image-btn').click(function() {
                        if (confirm('Are you sure you want to delete this image?')) {
                            const imageId = $(this).data('image-id');
                            $.post('inventory_actions.php', {
                                delete_image: 1,
                                image_id: imageId
                            }, function() {
                                location.reload();
                            });
                        }
                    });
                </script>
            <?php
            }
            exit;

        case 'view_product':
            // Retrieve product details for view modal
            $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ?");
            $stmt->execute([$_GET['id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                // Retrieve product images for the product
                $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
                $stmt->execute([$product['product_id']]);
                $productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // If product is exclusive, get membership types
                $membershipTypes = $product['is_exclusive'] ? $pdo->query("SELECT * FROM membership_types")->fetchAll(PDO::FETCH_ASSOC) : [];
            ?>
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4><?= htmlspecialchars($product['product_name']) ?></h4>
                            <p class="text-muted">SKU: <?= htmlspecialchars($product['sku']) ?></p>
                            <table class="table table-sm">
                                <tr>
                                    <th>Category:</th>
                                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Price:</th>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                </tr>
                                <tr>
                                    <th>Stock:</th>
                                    <td><?= $product['stock'] ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><?= $product['is_exclusive'] ? '<span class="badge bg-info">Exclusive</span>' : '<span class="badge bg-secondary">Regular</span>' ?></td>
                                </tr>
                                <tr>
                                    <th>Created:</th>
                                    <td><?= date('M d, Y h:i A', strtotime($product['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Updated:</th>
                                    <td><?= date('M d, Y h:i A', strtotime($product['modified_at'])) ?></td>
                                </tr>
                            </table>
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Product Images</h6>
                            <?php if (empty($productImages)): ?>
                                <p class="text-muted">No images available</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($productImages as $img): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <img src="../assets/images/products/<?= htmlspecialchars($img['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($img['alt_text']) ?>">
                                                <div class="card-body text-center">
                                                    <?= $img['is_primary'] ? '<span class="badge bg-primary">Primary</span>' : '' ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($product['is_exclusive'] && !empty($membershipTypes)): ?>
                                <div class="mt-4">
                                    <h6>Available To:</h6>
                                    <ul>
                                        <?php foreach ($membershipTypes as $m): ?>
                                            <li><?= htmlspecialchars($m['type_name']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
<?php
            }
            exit;
    }
}

// Handle form submissions for add/update/import/export, image deletion, etc.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        if (isset($_POST['add_product'])) {
            // This code block adds a new product.
            $stmt = $pdo->prepare("INSERT INTO products (product_name, sku, description, price, stock, is_exclusive, category_id) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                $_POST['product_name'],
                $_POST['sku'],
                $_POST['description'],
                $_POST['price'],
                $_POST['stock'],
                isset($_POST['is_exclusive']) ? 1 : 0,
                $_POST['category_id']
            ]);
            $productId = $pdo->lastInsertId();
            // Handle image uploads for the new product.
            if (!empty($_FILES['product_images']['name'][0])) {
                $uploadDir = '../assets/images/products/';
                foreach ($_FILES['product_images']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = uniqid() . '_' . basename($_FILES['product_images']['name'][$key]);
                        if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                            $isPrimary = ($key === 0) ? 1 : 0;
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, alt_text) VALUES (?,?,?,?)");
                            $stmt->execute([$productId, $fileName, $isPrimary, $_POST['product_name']]);
                        }
                    }
                }
            }
            // Log the add product action.
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?,?,?,?)");
            $stmt->execute([$_SESSION['user_id'], "Added product: " . $_POST['product_name'], 'products', $productId]);
            $_SESSION['message'] = "Product added successfully!";
            $pdo->commit();
        } elseif (isset($_POST['update_product'])) {
            // This code block updates an existing product.
            $stmt = $pdo->prepare("UPDATE products SET product_name=?, sku=?, description=?, price=?, stock=?, is_exclusive=?, category_id=? WHERE product_id=?");
            $stmt->execute([
                $_POST['product_name'],
                $_POST['sku'],
                $_POST['description'],
                $_POST['price'],
                $_POST['stock'],
                isset($_POST['is_exclusive']) ? 1 : 0,
                $_POST['category_id'],
                $_POST['product_id']
            ]);
            // Update the primary image if selected.
            if (isset($_POST['primary_image'])) {
                $stmt = $pdo->prepare("UPDATE product_images SET is_primary=0 WHERE product_id=?");
                $stmt->execute([$_POST['product_id']]);
                $stmt = $pdo->prepare("UPDATE product_images SET is_primary=1 WHERE image_id=?");
                $stmt->execute([$_POST['primary_image']]);
            }
            // Handle new image uploads for the updated product.
            if (!empty($_FILES['product_images']['name'][0])) {
                $uploadDir = '../assets/images/products/';
                foreach ($_FILES['product_images']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = uniqid() . '_' . basename($_FILES['product_images']['name'][$key]);
                        if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, alt_text) VALUES (?,?,?,?)");
                            $stmt->execute([$_POST['product_id'], $fileName, 0, $_POST['product_name']]);
                        }
                    }
                }
            }
            // Log the update product action.
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?,?,?,?)");
            $stmt->execute([$_SESSION['user_id'], "Updated product: " . $_POST['product_name'], 'products', $_POST['product_id']]);
            $_SESSION['message'] = "Product updated successfully!";
            $pdo->commit();
        } elseif (isset($_POST['delete_image'])) {
            // This code block deletes a product image.
            $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE image_id=?");
            $stmt->execute([$_POST['image_id']]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($image) {
                $stmt = $pdo->prepare("DELETE FROM product_images WHERE image_id=?");
                $stmt->execute([$_POST['image_id']]);
                $filePath = '../assets/images/products/' . $image['image_url'];
                if (file_exists($filePath)) unlink($filePath);
                // Log the deletion of the image.
                $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?,?,?,?)");
                $stmt->execute([$_SESSION['user_id'], "Deleted product image", 'product_images', $_POST['image_id']]);
            }
            $pdo->commit();
            exit;
        } elseif (isset($_POST['import_products'])) {
            // This code block handles CSV product import.
            if ($_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
                $handle = fopen($_FILES['import_file']['tmp_name'], "r");
                $header = fgetcsv($handle);
                $imported = $updated = $skipped = 0;
                while (($data = fgetcsv($handle)) !== false) {
                    $product = array_combine($header, $data);
                    if (empty($product['product_name']) || empty($product['sku']) || empty($product['price'])) {
                        $skipped++;
                        continue;
                    }
                    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name=?");
                    $stmt->execute([$product['category_name']]);
                    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$cat) {
                        $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
                        $stmt->execute([$product['category_name']]);
                        $categoryId = $pdo->lastInsertId();
                    } else {
                        $categoryId = $cat['category_id'];
                    }
                    $stmt = $pdo->prepare("SELECT product_id FROM products WHERE sku=?");
                    $stmt->execute([$product['sku']]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($existing && isset($_POST['overwrite'])) {
                        // Update existing product if overwrite is checked.
                        $stmt = $pdo->prepare("UPDATE products SET product_name=?, description=?, price=?, stock=?, is_exclusive=?, category_id=? WHERE product_id=?");
                        $stmt->execute([$product['product_name'], $product['description'], $product['price'], $product['stock'] ?? 0, $product['is_exclusive'] ?? 0, $categoryId, $existing['product_id']]);
                        $updated++;
                        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?,?,?,?)");
                        $stmt->execute([$_SESSION['user_id'], "Updated product via import: " . $product['product_name'], 'products', $existing['product_id']]);
                    } elseif (!$existing) {
                        // Add new product if it does not exist.
                        $stmt = $pdo->prepare("INSERT INTO products (product_name, sku, description, price, stock, is_exclusive, category_id) VALUES (?,?,?,?,?,?,?)");
                        $stmt->execute([$product['product_name'], $product['sku'], $product['description'], $product['price'], $product['stock'] ?? 0, $product['is_exclusive'] ?? 0, $categoryId]);
                        $imported++;
                        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?,?,?,?)");
                        $stmt->execute([$_SESSION['user_id'], "Added product via import: " . $product['product_name'], 'products', $pdo->lastInsertId()]);
                    } else {
                        $skipped++;
                    }
                }
                fclose($handle);
                $_SESSION['message'] = "Import completed: $imported new products added, $updated products updated, $skipped skipped.";
            }
            $pdo->commit();
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    header("Location: inventory.php");
    exit;
}

// Export functionality - output products as a CSV file.
if (isset($_GET['export'])) {
    // Clear output buffer if needed
    if (ob_get_contents()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Write CSV header row
    fputcsv($output, ['product_name', 'sku', 'description', 'price', 'stock', 'is_exclusive', 'category_name']);

    // Query to fetch products with their category names
    $stmt = $pdo->query("SELECT p.*, c.category_name 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.category_id 
                       ORDER BY p.product_name");
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $product['product_name'],
            $product['sku'],
            $product['description'],
            $product['price'],
            $product['stock'],
            $product['is_exclusive'],
            $product['category_name']
        ]);
    }
    fclose($output);
    ob_end_flush(); // Flush the output buffer
    exit;
}
?>