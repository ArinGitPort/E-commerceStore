<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
  header("Location: ../pages/login.php");
  exit;
}

// Pagination
$rowsPerPage = isset($_GET['rows']) ? (int)$_GET['rows'] : 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Filters
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "SELECT SQL_CALC_FOUND_ROWS p.*, c.category_name, 
          (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) AS primary_image
          FROM products p
          JOIN categories c ON p.category_id = c.category_id
          WHERE 1=1";
$params = [];

if ($categoryFilter > 0) {
  $query .= " AND p.category_id = ?";
  $params[] = $categoryFilter;
}

if ($stockFilter === 'low_stock') {
  $query .= " AND p.stock < 10";
} elseif ($stockFilter === 'out_of_stock') {
  $query .= " AND p.stock <= 0";
}

if (!empty($searchTerm)) {
  $query .= " AND (p.product_name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
  $params[] = "%$searchTerm%";
  $params[] = "%$searchTerm%";
  $params[] = "%$searchTerm%";
}

$query .= " ORDER BY p.product_name LIMIT ? OFFSET ?";
$params[] = $rowsPerPage;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count and calculate pages
$totalRows = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($totalRows / $rowsPerPage);

// Get categories for filter dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);

// Handle bulk deletion (this form posts to the same file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_products'])) {
  $selectedIds = $_POST['selected_products'] ?? [];
  if (!empty($selectedIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $pdo->beginTransaction();
    try {
      // Delete images then products
      $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id IN ($placeholders)");
      $stmt->execute($selectedIds);
      $stmt = $pdo->prepare("DELETE FROM products WHERE product_id IN ($placeholders)");
      $stmt->execute($selectedIds);
      $pdo->commit();
      $_SESSION['message'] = count($selectedIds) . " product(s) deleted successfully.";
      // Log the action
      $logMessage = "Deleted products: " . implode(', ', $selectedIds);
      $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
      $stmt->execute([$_SESSION['user_id'], $logMessage, 'products', 0]);
    } catch (PDOException $e) {
      $pdo->rollBack();
      $_SESSION['error'] = "Error deleting products: " . $e->getMessage();
    }
    header("Location: inventory.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Inventory Management - BunniShop</title>
  <link rel="stylesheet" href="/assets/css/inventory.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="../assets/images/iconlogo/bunniwinkleIcon.ico">
</head>

<body>
  <div class="pageWrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="mainContent">
      <div class="inventory-card">
        <!-- Message Display -->
        <?php if (isset($_SESSION['message'])): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="inventory-header">
          <button class="md-btn md-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus"></i> ADD PRODUCT
          </button>
          <div class="btn-group">
            <button class="md-btn" data-bs-toggle="modal" data-bs-target="#importModal">
              <i class="fas fa-file-import"></i> IMPORT
            </button>
            <button class="md-btn" id="exportBtn">
              <i class="fas fa-file-export"></i> EXPORT
            </button>
            <button class="md-btn md-danger" id="deleteSelectedBtn">
              <i class="fas fa-trash"></i> DELETE
            </button>
          </div>
          <div class="filters">
            <form method="get" class="filter-form">
              <select name="category" class="md-select" onchange="this.form.submit()">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= $category['category_id'] ?>" <?= $categoryFilter == $category['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <select name="stock" class="md-select" onchange="this.form.submit()">
                <option value="">All Items</option>
                <option value="low_stock" <?= $stockFilter === 'low_stock' ? 'selected' : '' ?>>Low Stock (<10)< /option>
                <option value="out_of_stock" <?= $stockFilter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
              </select>
              <div class="search-box" style="margin-top: 10px;">
                <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
              </div>
            </form>
          </div>
        </div>

        <form id="inventoryForm" method="post">
          <table class="md-table">
            <thead>
              <tr>
                <th><input type="checkbox" onclick="toggleAll(this)"></th>
                <th>Image</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product): ?>
                <tr class="<?= $product['stock'] <= 0 ? 'out-of-stock' : ($product['stock'] < 10 ? 'low-stock' : '') ?>">
                  <td><input type="checkbox" name="selected_products[]" value="<?= $product['product_id'] ?>"></td>
                  <td class="product-image-cell">
                    <?php if ($product['primary_image']): ?>
                      <img src="../assets/images/products/<?= htmlspecialchars($product['primary_image'] ?? '') ?>"
                        alt="<?= htmlspecialchars($product['product_name'] ?? '') ?>"
                        class="product-thumbnail">
                    <?php else: ?>
                      <div class="no-image">No Image</div>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($product['product_name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($product['sku'] ?? '') ?></td>
                  <td><?= htmlspecialchars($product['category_name'] ?? '') ?></td>
                  <td>₱<?= number_format($product['price'], 2) ?></td>
                  <td><?= $product['stock'] ?></td>
                  <td>
                    <?php if ($product['stock'] <= 0): ?>
                      <span class="badge bg-danger">Out of Stock</span>
                    <?php elseif ($product['stock'] < 10): ?>
                      <span class="badge bg-warning">Low Stock</span>
                    <?php else: ?>
                      <span class="badge bg-success">In Stock</span>
                    <?php endif; ?>
                    <?php if ($product['is_exclusive']): ?>
                      <span class="badge bg-info mt-1">Exclusive</span>
                    <?php endif; ?>
                  </td>
                  <td class="actions">
                    <button type="button" class="md-icon-btn edit-btn"
                      data-id="<?= $product['product_id'] ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#editProductModal">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="md-icon-btn view-btn"
                      data-id="<?= $product['product_id'] ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#viewProductModal">
                      <i class="fas fa-eye"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <input type="hidden" name="delete_products" value="1">
        </form>

        <div class="pagination-row">
          <div class="pagination-info">
            Showing <?= count($products) ?> of <?= $totalRows ?> products
          </div>
          <div class="pagination-controls">
            <label for="rowsPerPage">Rows per page:</label>
            <select id="rowsPerPage" class="md-select" onchange="updateRowsPerPage(this)">
              <option value="5" <?= $rowsPerPage == 5 ? 'selected' : '' ?>>5</option>
              <option value="10" <?= $rowsPerPage == 10 ? 'selected' : '' ?>>10</option>
              <option value="25" <?= $rowsPerPage == 25 ? 'selected' : '' ?>>25</option>
              <option value="50" <?= $rowsPerPage == 50 ? 'selected' : '' ?>>50</option>
            </select>
            <button class="md-icon-btn" <?= $currentPage == 1 ? 'disabled' : '' ?>
              onclick="goToPage(<?= $currentPage - 1 ?>)">
              &lt;
            </button>
            <span>Page <?= $currentPage ?> of <?= $totalPages ?></span>
            <button class="md-icon-btn" <?= $currentPage >= $totalPages ? 'disabled' : '' ?>
              onclick="goToPage(<?= $currentPage + 1 ?>)">
              &gt;
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <!-- Add Product Modal -->
  <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="inventory_actions.php" method="post" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title">Add New Product</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Product Name*</label>
                  <input type="text" name="product_name" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">SKU*</label>
                  <input type="text" name="sku" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Category*</label>
                  <select name="category_id" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                      <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Price*</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Stock Quantity*</label>
                    <input type="number" name="stock" class="form-control" required>
                  </div>
                </div>
                <div class="mb-3 form-check">
                  <input type="checkbox" name="is_exclusive" class="form-check-input" id="isExclusive">
                  <label class="form-check-label" for="isExclusive">Exclusive Product (Members Only)</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label">Product Images</label>
                  <input type="file" name="product_images[]" class="form-control" multiple accept="image/*">
                  <small class="text-muted">First image will be set as primary</small>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_product" class="btn btn-primary">Save Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Product Modal (loaded via AJAX) -->
  <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <!-- AJAX loaded content -->
      </div>
    </div>
  </div>

  <!-- View Product Modal (loaded via AJAX) -->
  <div class="modal fade" id="viewProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <!-- AJAX loaded content -->
      </div>
    </div>
  </div>

  <!-- Import Modal -->
  <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="inventory_actions.php" method="post" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title">Import Products</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Select CSV File</label>
              <input type="file" name="import_file" class="form-control" accept=".csv" required>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="overwrite" id="overwriteCheck">
              <label class="form-check-label" for="overwriteCheck">
                Overwrite existing products with matching SKU
              </label>
            </div>
            <div class="mt-3">
              <a href="/assets/templates/products_template.csv" download class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-download"></i> Download Template
              </a>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="import_products" class="btn btn-primary">Import</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle all checkboxes in the table
    function toggleAll(source) {
      const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
      checkboxes.forEach(checkbox => checkbox.checked = source.checked);
    }
    // Update rows per page and reset to the first page
    function updateRowsPerPage(select) {
      const url = new URL(window.location.href);
      url.searchParams.set('rows', select.value);
      url.searchParams.set('page', 1);
      window.location.href = url.toString();
    }
    // Pagination navigation
    function goToPage(page) {
      const url = new URL(window.location.href);
      url.searchParams.set('page', page);
      window.location.href = url.toString();
    }
    // Delete selected products (bulk deletion)
    document.getElementById('deleteSelectedBtn').addEventListener('click', function() {
      const selectedCount = document.querySelectorAll('tbody input[type="checkbox"]:checked').length;
      if (selectedCount === 0) {
        alert('Please select at least one product to delete');
        return;
      }
      if (confirm(`Are you sure you want to delete ${selectedCount} selected product(s)?`)) {
        document.getElementById('inventoryForm').submit();
      }
    });
    // Trigger export by setting the 'export' parameter in the URL
    document.getElementById('exportBtn').addEventListener('click', function() {
      const url = new URL(window.location.href);
      url.searchParams.set('export', '1');
      window.location.href = 'inventory_actions.php?export=1';

    });
    // Load edit modal content via AJAX
    $('.edit-btn').click(function() {
      const productId = $(this).data('id');
      $.get('inventory_actions.php?action=get_product&id=' + productId, function(data) {
        $('#editProductModal .modal-content').html(data);
      });
    });
    // Load view modal content via AJAX
    $('.view-btn').click(function() {
      const productId = $(this).data('id');
      $.get('inventory_actions.php?action=view_product&id=' + productId, function(data) {
        $('#viewProductModal .modal-content').html(data);
      });
    });
  </script>
</body>

</html>