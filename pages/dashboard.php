<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../pages/login.php");
  exit;
}

// Default date range (last 30 days)
$start_date      = date('Y-m-d', strtotime('-2 days'));
$end_date        = date('Y-m-d', strtotime('+5 days'));
$membership_type = 'all';
$order_status    = 'all';
$category_id     = 'all';

// Get filter parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $start_date      = $_POST['start_date']      ?? $start_date;
  $end_date        = $_POST['end_date']        ?? $end_date;
  $membership_type = $_POST['membership_type'] ?? 'all';
  $order_status    = $_POST['order_status']    ?? 'all';
  $category_id     = $_POST['category_id']     ?? 'all';
}

// CTE combining live + archived orders
$cte = <<<SQL
WITH all_orders AS (
  SELECT order_id, customer_id, order_date, total_price, order_status, discount, delivery_method_id
    FROM orders
  UNION ALL
  SELECT order_id, customer_id, order_date, total_price, order_status, discount, delivery_method_id
    FROM archived_orders
)
SQL;

$params = [
  ':start_date' => $start_date . ' 00:00:00',
  ':end_date'   => $end_date   . ' 23:59:59'
];

// --- 1) Main orders listing ---
$mainQuery = $cte . "
SELECT 
    o.order_id,
    o.order_date,
    o.total_price,
    o.order_status,
    o.discount,
    u.name            AS customer_name,
    mt.type_name      AS membership_type,
    dm.method_name    AS delivery_method,
    pm.method_name    AS payment_method
FROM all_orders o
JOIN users u               ON o.customer_id = u.user_id
JOIN memberships m         ON u.user_id = m.user_id
JOIN membership_types mt   ON m.membership_type_id = mt.membership_type_id
JOIN delivery_methods dm   ON o.delivery_method_id = dm.delivery_method_id
LEFT JOIN payments p       ON o.order_id = p.order_id
LEFT JOIN payment_methods pm ON p.payment_method_id = pm.payment_method_id
WHERE o.order_date BETWEEN :start_date AND :end_date
";

// membership filter
if ($membership_type !== 'all') {
  $mainQuery .= " AND m.membership_type_id = :membership_type";
  $params[':membership_type'] = $membership_type;
}
// status filter
if ($order_status !== 'all') {
  $mainQuery .= " AND o.order_status = :order_status";
  $params[':order_status'] = $order_status;
}
// **category** filter
if ($category_id !== 'all') {
  $mainQuery .= "
    AND EXISTS (
      SELECT 1
      FROM order_details odf
      JOIN products pf ON odf.product_id = pf.product_id
      WHERE odf.order_id = o.order_id
        AND pf.category_id = :category_id
    )
  ";
  $params[':category_id'] = $category_id;
}

$mainQuery .= " ORDER BY o.order_date DESC";
$stmt   = $pdo->prepare($mainQuery);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 2) Summary KPIs ---
$summaryQuery = $cte . "
SELECT
    COUNT(*)                   AS total_orders,
    SUM(total_price)           AS total_revenue,
    AVG(total_price)           AS avg_order_value,
    COUNT(DISTINCT customer_id) AS unique_customers
FROM all_orders
WHERE order_date BETWEEN :start_date AND :end_date
";
// NOTE: usually you don’t filter summary by category, but you could copy the same EXISTS() clause if desired
$summaryStmt = $pdo->prepare($summaryQuery);
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

// --- 3) Top products (already using CTE) ---
$productsQuery = $cte . "
SELECT 
    p.product_id,
    p.product_name,
    c.category_name,
    SUM(od.quantity)    AS total_quantity,
    SUM(od.total_price) AS total_revenue
FROM order_details od
JOIN products p           ON od.product_id = p.product_id
JOIN categories c         ON p.category_id = c.category_id
JOIN all_orders o         ON od.order_id = o.order_id
WHERE o.order_date BETWEEN :start_date AND :end_date
GROUP BY p.product_id
ORDER BY total_quantity DESC
LIMIT 5
";
$productsStmt = $pdo->prepare($productsQuery);
$productsStmt->execute($params);
$top_products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Top products from ARCHIVED ORDERS only
$archivedProductsQuery = "
  SELECT 
    p.product_id,
    p.product_name,
    c.category_name,
    SUM(aod.quantity)     AS total_quantity,
    SUM(aod.total_price)  AS total_revenue
  FROM archived_order_details aod
  JOIN archived_orders ao   ON aod.order_id   = ao.order_id
  JOIN products p           ON aod.product_id = p.product_id
  JOIN categories c         ON p.category_id  = c.category_id
  WHERE ao.order_date BETWEEN :start_date AND :end_date
  GROUP BY p.product_id
  ORDER BY total_quantity DESC
  LIMIT 5
";
$archivedStmt = $pdo->prepare($archivedProductsQuery);
$archivedStmt->execute([
  ':start_date' => $start_date . ' 00:00:00',
  ':end_date'   => $end_date   . ' 23:59:59',
]);
$archived_top_products = $archivedStmt->fetchAll(PDO::FETCH_ASSOC);



// --- 4) Sales by category ---
$categoriesQuery = $cte . "
SELECT 
    c.category_name,
    SUM(od.total_price) AS total_revenue
FROM order_details od
JOIN products p           ON od.product_id = p.product_id
JOIN categories c         ON p.category_id = c.category_id
JOIN all_orders o         ON od.order_id = o.order_id
WHERE o.order_date BETWEEN :start_date AND :end_date
GROUP BY c.category_id
ORDER BY total_revenue DESC
";
$categoriesStmt = $pdo->prepare($categoriesQuery);
$categoriesStmt->execute($params);
$sales_by_category = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// --- 5) Sales by membership type ---
$membershipQuery = $cte . "
SELECT 
    mt.type_name,
    COUNT(o.order_id)    AS order_count,
    SUM(o.total_price)   AS total_revenue
FROM all_orders o
JOIN users u            ON o.customer_id = u.user_id
JOIN memberships m      ON u.user_id     = m.user_id
JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
WHERE o.order_date BETWEEN :start_date AND :end_date
GROUP BY mt.type_name
ORDER BY total_revenue DESC
";
$membershipStmt = $pdo->prepare($membershipQuery);
$membershipStmt->execute($params);
$sales_by_membership = $membershipStmt->fetchAll(PDO::FETCH_ASSOC);

// --- 6) Pending orders (live-only, with category filter too) ---
$pendingParams = [
  ':start_date' => $start_date . ' 00:00:00',
  ':end_date'   => $end_date   . ' 23:59:59'
];
$pendingQuery = "
SELECT 
    o.order_id,
    o.order_date,
    u.name          AS customer_name,
    mt.type_name    AS membership_type,
    dm.method_name  AS delivery_method,
    pm.method_name  AS payment_method,
    o.total_price,
    o.discount
FROM orders o
JOIN users u             ON o.customer_id = u.user_id
JOIN memberships m       ON u.user_id = m.user_id
JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
JOIN delivery_methods dm ON o.delivery_method_id = dm.delivery_method_id
LEFT JOIN payments p     ON o.order_id = p.order_id
LEFT JOIN payment_methods pm ON p.payment_method_id = pm.payment_method_id
WHERE o.order_status = 'Pending'
  AND o.order_date BETWEEN :start_date AND :end_date
";
// apply category filter to pending as well
if ($category_id !== 'all') {
  $pendingQuery .= "
    AND EXISTS (
      SELECT 1
      FROM order_details odf
      JOIN products pf ON odf.product_id = pf.product_id
      WHERE odf.order_id = o.order_id
        AND pf.category_id = :category_id
    )
  ";
  $pendingParams[':category_id'] = $category_id;
}

$pendingQuery .= " ORDER BY o.order_date DESC";
$pendingStmt = $pdo->prepare($pendingQuery);
$pendingStmt->execute($pendingParams);
$pendingOrders = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BunniShop - Sales Reports</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">

</head>

<body>
  <div class="pageWrapper">
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="contentWrapper">


      <!-- Main Content -->
      <div class="container-fluid">

        <!-- Filter Form -->
        <div class="report-filter mb-4">
          <form method="POST">
            <div class="row g-3">
              <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
              </div>
              <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
              </div>
              <div class="col-md-2">
                <label for="membership_type" class="form-label">Membership</label>
                <select class="form-select" id="membership_type" name="membership_type">
                  <option value="all" <?= $membership_type === 'all' ? 'selected' : '' ?>>All Memberships</option>
                  <option value="1" <?= $membership_type === '1' ? 'selected' : '' ?>>Free</option>
                  <option value="2" <?= $membership_type === '2' ? 'selected' : '' ?>>Premium</option>
                  <option value="3" <?= $membership_type === '3' ? 'selected' : '' ?>>VIP</option>
                </select>
              </div>
              <div class="col-md-2">
                <label for="order_status" class="form-label">Order Status</label>
                <select class="form-select" id="order_status" name="order_status">
                  <option value="all" <?= $order_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                  <option value="Pending" <?= $order_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Shipped" <?= $order_status === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                  <option value="Delivered" <?= $order_status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                  <option value="Cancelled" <?= $order_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                  <option value="Returned" <?= $order_status === 'Returned' ? 'selected' : '' ?>>Returned</option>
                </select>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
              </div>
            </div>
          </form>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="card-counter primary">
              <div class="count-numbers"><?= number_format($summary['total_orders']) ?></div>
              <div class="count-name">Total Orders</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card-counter success">
              <div class="count-numbers">₱<?= number_format($summary['total_revenue'], 2) ?></div>
              <div class="count-name">Total Revenue</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card-counter warning">
              <div class="count-numbers">₱<?= number_format($summary['avg_order_value'], 2) ?></div>
              <div class="count-name">Avg. Order Value</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card-counter danger">
              <div class="count-numbers"><?= number_format($summary['unique_customers']) ?></div>
              <div class="count-name">Customers</div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
          <!-- Sales by Category Chart -->
          <div class="col-md-6">
            <div class="card chart-card">
              <div class="card-header">
                <h5 class="card-title">Revenue by Product Category</h5>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="categoryChart"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Sales by Membership Type Chart -->
          <div class="col-md-6">
            <div class="card chart-card">
              <div class="card-header">
                <h5 class="card-title">Revenue by Membership Type</h5>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="membershipChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Products -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title">Top Selling Products</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Quantity Sold</th>
                    <th>Total Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($archived_top_products)): ?>
                    <tr>
                      <td colspan="4" class="text-center">No archived sales in this period.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($archived_top_products as $prod): ?>
                      <tr>
                        <td><?= htmlspecialchars($prod['product_name']) ?></td>
                        <td><?= htmlspecialchars($prod['category_name']) ?></td>
                        <td><?= number_format($prod['total_quantity']) ?></td>
                        <td>₱<?= number_format($prod['total_revenue'], 2) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>


        <!-- Order Details: Pending Orders -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Pending Orders</h5>
            <button class="btn btn-sm btn-primary" onclick="exportToExcel('pendingTable', 'Pending_Orders_<?= $start_date ?>_to_<?= $end_date ?>')">
              Export to Excel
            </button>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="pendingTable">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Membership</th>
                    <th>Delivery Method</th>
                    <th>Payment Method</th>
                    <th>Total</th>
                    <th>Discount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($pendingOrders)): ?>
                    <?php foreach ($pendingOrders as $order): ?>
                      <tr>
                        <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                        <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= htmlspecialchars($order['membership_type']) ?></td>
                        <td><?= htmlspecialchars($order['delivery_method']) ?></td>
                        <td><?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></td>
                        <td>₱<?= number_format($order['total_price'], 2) ?></td>
                        <td>₱<?= number_format($order['discount'], 2) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="8" class="text-center">No pending orders in this period.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <script>
    // Initialize date pickers
    flatpickr("#start_date", {
      dateFormat: "Y-m-d",
      defaultDate: "<?= $start_date ?>"
    });

    flatpickr("#end_date", {
      dateFormat: "Y-m-d",
      defaultDate: "<?= $end_date ?>"
    });

    // Prepare chart data
    const categoryData = {
      labels: <?= json_encode(array_column($sales_by_category, 'category_name')) ?>,
      datasets: [{
        data: <?= json_encode(array_column($sales_by_category, 'total_revenue')) ?>,
        backgroundColor: [
          '#4e73df',
          '#1cc88a',
          '#36b9cc',
          '#f6c23e',
          '#e74a3b'
        ],
        hoverBackgroundColor: [
          '#2e59d9',
          '#17a673',
          '#2c9faf',
          '#dda20a',
          '#be2617'
        ],
        hoverBorderColor: "rgba(234, 236, 244, 1)",
      }]
    };

    const membershipData = {
      labels: <?= json_encode(array_column($sales_by_membership, 'type_name')) ?>,
      datasets: [{
        data: <?= json_encode(array_column($sales_by_membership, 'total_revenue')) ?>,
        backgroundColor: [
          '#4e73df',
          '#1cc88a',
          '#36b9cc'
        ],
        hoverBackgroundColor: [
          '#2e59d9',
          '#17a673',
          '#2c9faf'
        ],
        hoverBorderColor: "rgba(234, 236, 244, 1)",
      }]
    };

    // Initialize charts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      // Category Chart
      const categoryCtx = document.getElementById('categoryChart').getContext('2d');
      new Chart(categoryCtx, {
        type: 'doughnut',
        data: categoryData,
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  // Chart.js v3+: use parsed
                  const label = context.label || '';
                  const value = context.parsed;
                  // format with two decimals and thousands separators
                  const formatted = value.toLocaleString(undefined, {
                    minimumFractionDigits: 2
                  });
                  return label ? `${label}: ₱${formatted}` : `₱${formatted}`;
                }
              }
            }
          }
        }
      });

      // Membership Chart
      const membershipCtx = document.getElementById('membershipChart').getContext('2d');
      new Chart(membershipCtx, {
        type: 'pie',
        data: membershipData,
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  // Chart.js v3+: use parsed
                  const label = context.label || '';
                  const value = context.parsed;
                  // format with two decimals and thousands separators
                  const formatted = value.toLocaleString(undefined, {
                    minimumFractionDigits: 2
                  });
                  return label ? `${label}: ₱${formatted}` : `₱${formatted}`;
                }
              }
            }
          }
        }
      });
    });

    // Export to Excel function
    function exportToExcel() {
      const table = document.getElementById('orderTable');
      const ws = XLSX.utils.table_to_sheet(table);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "SalesReport");

      // Generate a filename with the date range
      const start = "<?= $start_date ?>";
      const end = "<?= $end_date ?>";
      const filename = `BunniShop_Sales_Report_${start}_to_${end}.xlsx`;

      XLSX.writeFile(wb, filename);
    }
  </script>
</body>

</html>