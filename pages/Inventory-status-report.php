<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Default filter values
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 'all';
$stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : 'all';
$is_exclusive = isset($_GET['is_exclusive']) ? $_GET['is_exclusive'] : 'all';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'stock_asc';

// Build query
$query = "SELECT 
            p.product_id,
            p.product_name,
            p.sku,
            p.price,
            p.stock,
            p.is_exclusive,
            c.category_name,
            mt.type_name as membership_level,
            (SELECT COALESCE(SUM(od.quantity), 0) FROM order_details od 
             JOIN orders o ON od.order_id = o.order_id 
             WHERE od.product_id = p.product_id 
             AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             AND o.order_status != 'Cancelled') as monthly_sales
          FROM products p
          JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN membership_types mt ON p.min_membership_level = mt.membership_type_id
          WHERE 1=1";

$params = [];

// Apply filters
if ($category_id != 'all') {
  $query .= " AND p.category_id = :category_id";
  $params[':category_id'] = $category_id;
}

if ($stock_status == 'low') {
  $query .= " AND p.stock <= 10";
} elseif ($stock_status == 'out') {
  $query .= " AND p.stock = 0";
} elseif ($stock_status == 'healthy') {
  $query .= " AND p.stock > 10";
}

if ($is_exclusive != 'all') {
  $query .= " AND p.is_exclusive = :is_exclusive";
  $params[':is_exclusive'] = ($is_exclusive === 'yes') ? 1 : 0;
}

// Apply sorting
switch ($sort_by) {
  case 'stock_asc':
    $query .= " ORDER BY p.stock ASC";
    break;
  case 'stock_desc':
    $query .= " ORDER BY p.stock DESC";
    break;
  case 'sales_desc':
    $query .= " ORDER BY monthly_sales DESC";
    break;
  case 'name_asc':
    $query .= " ORDER BY p.product_name ASC";
    break;
  default:
    $query .= " ORDER BY p.stock ASC";
}

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$total_products = count($products);
$out_of_stock = 0;
$low_stock = 0;
$total_value = 0;

foreach ($products as $product) {
  if ($product['stock'] == 0) {
    $out_of_stock++;
  } elseif ($product['stock'] <= 10) {
    $low_stock++;
  }
  $total_value += $product['price'] * $product['stock'];
}

// Report generation functionality
function generateCSV($products)
{
  $filename = 'inventory_report_' . date('Y-m-d_H-i-s') . '.csv';
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="' . $filename . '"');

  $output = fopen('php://output', 'w');

  // Add CSV headers
  fputcsv($output, ['Product ID', 'Product Name', 'SKU', 'Category', 'Membership Required', 'Price', 'Stock', 'Monthly Sales', 'Is Exclusive']);

  // Add product data
  foreach ($products as $product) {
    fputcsv($output, [
      $product['product_id'],
      $product['product_name'],
      $product['sku'],
      $product['category_name'],
      $product['membership_level'] ?? 'N/A',
      $product['price'],
      $product['stock'],
      $product['monthly_sales'] ?? 0,
      $product['is_exclusive'] ? 'Yes' : 'No'
    ]);
  }

  fclose($output);
  exit;
}

// Generate report if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  generateCSV($products);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bunniwinkle - Inventory Status Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    @media print {
      .no-print {
        display: none;
      }

      body {
        padding: 20px;
      }
    }

    .stock-status-out {
      color: #dc3545;
      font-weight: bold;
    }

    .stock-status-low {
      color: #ffc107;
      font-weight: bold;
    }

    .stock-status-ok {
      color: #198754;
    }

    .table th {
      position: sticky;
      top: 0;
      background-color: #f8f9fa;
    }

    .card-header {
      font-weight: bold;
    }

    .report-timestamp {
      text-align: right;
      font-style: italic;
      margin-bottom: 20px;
    }
  </style>
</head>

<?php include '../includes/sidebar.php'; ?>

<body class="bg-light">
  <div class="container-fluid p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="report-timestamp">
        Generated: <?= date('F j, Y, g:i a') ?>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-header">Total Products</div>
          <div class="card-body">
            <h2><?= $total_products ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-danger text-white">
          <div class="card-header">Out of Stock</div>
          <div class="card-body">
            <h2><?= $out_of_stock ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-header">Low Stock</div>
          <div class="card-body">
            <h2><?= $low_stock ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-header">Inventory Value</div>
          <div class="card-body">
            <h2>₱<?= number_format($total_value, 2) ?></h2>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 no-print">
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category_id">
              <option value="all">All Categories</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?= $category['category_id'] ?>" <?= $category_id == $category['category_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($category['category_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stock Status</label>
            <select class="form-select" name="stock_status">
              <option value="all" <?= $stock_status == 'all' ? 'selected' : '' ?>>All</option>
              <option value="out" <?= $stock_status == 'out' ? 'selected' : '' ?>>Out of Stock</option>
              <option value="low" <?= $stock_status == 'low' ? 'selected' : '' ?>>Low Stock (≤10)</option>
              <option value="healthy" <?= $stock_status == 'healthy' ? 'selected' : '' ?>>Healthy Stock (>10)</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Exclusive Items</label>
            <select class="form-select" name="is_exclusive">
              <option value="all" <?= $is_exclusive == 'all' ? 'selected' : '' ?>>All</option>
              <option value="yes" <?= $is_exclusive == 'yes' ? 'selected' : '' ?>>Yes</option>
              <option value="no" <?= $is_exclusive == 'no' ? 'selected' : '' ?>>No</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Sort By</label>
            <select class="form-select" name="sort_by">
              <option value="stock_asc" <?= $sort_by == 'stock_asc' ? 'selected' : '' ?>>Stock (Low to High)</option>
              <option value="stock_desc" <?= $sort_by == 'stock_desc' ? 'selected' : '' ?>>Stock (High to Low)</option>
              <option value="sales_desc" <?= $sort_by == 'sales_desc' ? 'selected' : '' ?>>Best Selling</option>
              <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
            </select>
          </div>

          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Apply</button>
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary me-2">
              <i class="fas fa-undo"></i> Reset
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
              <i class="fas fa-print"></i> Print
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Inventory Table -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Inventory Items</h5>

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Membership Required</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Monthly Sales</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($products)): ?>
                <tr>
                  <td colspan="8" class="text-center">No products found matching your criteria</td>
                </tr>
              <?php else: ?>
                <?php foreach ($products as $index => $product): ?>
                  <?php
                  $stockClass = 'stock-status-ok';
                  if ($product['stock'] == 0) {
                    $stockClass = 'stock-status-out';
                  } elseif ($product['stock'] <= 10) {
                    $stockClass = 'stock-status-low';
                  }
                  ?>
                  <tr>
                    <td><?= $index + 1 ?></td>
                    <td>
                      <?= htmlspecialchars($product['product_name']) ?>
                      <?php if ($product['is_exclusive']): ?>
                        <span class="badge bg-info ms-1">Exclusive</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($product['sku']) ?></td>
                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                    <td><?= $product['membership_level'] ? htmlspecialchars($product['membership_level']) : 'N/A' ?></td>
                    <td>₱<?= number_format($product['price'], 2) ?></td>
                    <td class="<?= $stockClass ?>"><?= $product['stock'] ?></td>
                    <td><?= $product['monthly_sales'] ?: 0 ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="text-end mt-3 no-print">
      <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-secondary">
        <i class="fas fa-file-export"></i> Generate Full Report
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>