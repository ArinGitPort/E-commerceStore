<?php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../includes/session-init.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../pages/login.php");
  exit;
}


// Default date range (last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Get filter parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $start_date = $_POST['start_date'] ?? $start_date;
  $end_date = $_POST['end_date'] ?? $end_date;

  // Validate range
  if ($end_date < $start_date) {
    $end_date = $start_date;
  }
}


// Get archived orders with customer names
$query = "SELECT 
            ao.order_id,
            ao.order_date,
            ao.total_price,
            ao.discount,
            u.name AS customer_name,
            dm.method_name AS delivery_method
          FROM archived_orders ao
          JOIN users u ON ao.customer_id = u.user_id
          JOIN delivery_methods dm ON ao.delivery_method_id = dm.delivery_method_id
          WHERE ao.order_date BETWEEN :start_date AND :end_date
          ORDER BY ao.order_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([
  ':start_date' => $start_date . ' 00:00:00',
  ':end_date' => $end_date . ' 23:59:59'
]);
$archivedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$summary_query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_price) as total_revenue,
                    AVG(total_price) as avg_order_value,
                    COUNT(DISTINCT customer_id) as unique_customers
                  FROM archived_orders
                  WHERE order_date BETWEEN :start_date AND :end_date";
$summary_stmt = $pdo->prepare($summary_query);
$summary_stmt->execute([
  ':start_date' => $start_date . ' 00:00:00',
  ':end_date' => $end_date . ' 23:59:59'
]);
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

// Get daily sales data for chart
$daily_sales_query = "SELECT 
                        DATE(order_date) as sale_date,
                        SUM(total_price) as daily_revenue,
                        COUNT(*) as daily_orders
                      FROM archived_orders
                      WHERE order_date BETWEEN :start_date AND :end_date
                      GROUP BY DATE(order_date)
                      ORDER BY sale_date";
$daily_sales_stmt = $pdo->prepare($daily_sales_query);
$daily_sales_stmt->execute([
  ':start_date' => $start_date . ' 00:00:00',
  ':end_date' => $end_date . ' 23:59:59'
]);
$daily_sales = $daily_sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare chart data
$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];

foreach ($daily_sales as $day) {
  $chart_labels[] = date('M j', strtotime($day['sale_date']));
  $chart_revenue[] = $day['daily_revenue'];
  $chart_orders[] = $day['daily_orders'];
}

// Get top products
$products_query = "SELECT 
                    p.product_id,
                    p.product_name,
                    c.category_name,
                    SUM(od.quantity) as total_quantity,
                    SUM(od.total_price) as total_revenue
                  FROM order_details od
                  JOIN products p ON od.product_id = p.product_id
                  JOIN categories c ON p.category_id = c.category_id
                  JOIN orders o ON od.order_id = o.order_id
                  WHERE o.order_date BETWEEN :start_date AND :end_date
                  GROUP BY p.product_id
                  ORDER BY total_quantity DESC
                  LIMIT 5";

$products_stmt = $pdo->prepare($products_query);
$products_stmt->execute([
  ':start_date' => $start_date . ' 00:00:00',
  ':end_date' => $end_date . ' 23:59:59'
]);
$top_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BunniShop - Completed Orders Report</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="../assets/css/sales_report.css">


</head>

<body>
  <div class="pageWrapper">
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="contentWrapper">

      <!-- Main Content -->



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
              <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-success w-100" onclick="exportToExcel()">Export to Excel</button>
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-info w-100" onclick="window.print()">Print Report</button>
              </div>
            </div>
          </form>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="card-counter primary">
              <div class="count-numbers"><?= number_format($summary['total_orders']) ?></div>
              <div class="count-name">Completed Orders</div>
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
            <div class="card-counter info">
              <div class="count-numbers"><?= number_format($summary['unique_customers']) ?></div>
              <div class="count-name">Unique Customers</div>
            </div>
          </div>
        </div>

        <!-- Sales Trend Chart -->
        <!-- Charts Row - Sales Trend + Top Products -->
        <div class="row mb-4">
          <!-- Sales Trend Chart -->
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header">
                <h5 class="card-title">Sales Trend (<?= date('M j', strtotime($start_date)) ?> - <?= date('M j', strtotime($end_date)) ?>)</h5>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="salesTrendChart"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Products Chart -->
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header">
                <h5 class="card-title">Top Selling Products</h5>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="topProductsChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Details -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title">Completed Order Details</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="orderTable">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Delivery Method</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Discount</th>
                    <th>Net Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($archivedOrders as $order): ?>
                    <tr>
                      <td>#<?= $order['order_id'] ?></td>
                      <td><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></td>
                      <td><?= htmlspecialchars($order['customer_name']) ?></td>
                      <td>
                        <span class="badge-delivery"><?= htmlspecialchars($order['delivery_method']) ?></span>
                      </td>
                      <td>
                        <span class="badge-status status-completed">Completed</span>
                      </td>
                      <td>₱<?= number_format($order['total_price'], 2) ?></td>
                      <td>₱<?= number_format($order['discount'], 2) ?></td>
                      <td>₱<?= number_format($order['total_price'] - $order['discount'], 2) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (empty($archivedOrders)): ?>
                    <tr>
                      <td colspan="8" class="text-center">No completed orders found for the selected period</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
                <?php if (!empty($archivedOrders)): ?>
                  <tfoot>
                    <tr>
                      <th colspan="5">Totals</th>
                      <th>₱<?= number_format(array_sum(array_column($archivedOrders, 'total_price')), 2) ?></th>
                      <th>₱<?= number_format(array_sum(array_column($archivedOrders, 'discount')), 2) ?></th>
                      <th>₱<?= number_format(array_sum(array_column($archivedOrders, 'total_price')) - array_sum(array_column($archivedOrders, 'discount')), 2) ?></th>
                    </tr>
                  </tfoot>
                <?php endif; ?>
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
    const chartData = {
      labels: <?= json_encode($chart_labels) ?>,
      datasets: [{
          label: 'Daily Revenue',
          data: <?= json_encode($chart_revenue) ?>,
          backgroundColor: 'rgba(78, 115, 223, 0.5)',
          borderColor: 'rgba(78, 115, 223, 1)',
          borderWidth: 2,
          yAxisID: 'y'
        },
        {
          label: 'Number of Orders',
          data: <?= json_encode($chart_orders) ?>,
          backgroundColor: 'rgba(28, 200, 138, 0.5)',
          borderColor: 'rgba(28, 200, 138, 1)',
          borderWidth: 3, // Increased from 2 to 3
          pointBorderWidth: 2, // Added point border width
          pointRadius: 5, // Increased point size
          pointHoverRadius: 7, // Larger on hover
          pointBackgroundColor: 'rgba(28, 200, 138, 1)', // Solid point color
          type: 'line',
          yAxisID: 'y1',
          tension: 0.1, // Slightly curved line
          fill: false // Don't fill under line
        }
      ]
    };

    // Initialize chart when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      const ctx = document.getElementById('salesTrendChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              type: 'linear',
              display: true,
              position: 'left',
              title: {
                display: true,
                text: 'Revenue (₱)'
              },
              ticks: {
                callback: function(value) {
                  return '₱' + value.toLocaleString();
                }
              }
            },
            y1: {
              type: 'linear',
              display: true,
              position: 'right',
              title: {
                display: true,
                text: 'Number of Orders'
              },
              grid: {
                drawOnChartArea: false
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || '';
                  if (label.includes('Revenue')) {
                    return label + ': ₱' + context.raw.toLocaleString();
                  } else {
                    return label + ': ' + context.raw;
                  }
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
      XLSX.utils.book_append_sheet(wb, ws, "CompletedOrders");

      // Generate a filename with the date range
      const start = "<?= $start_date ?>";
      const end = "<?= $end_date ?>";
      const filename = `BunniShop_Completed_Orders_${start}_to_${end}.xlsx`;

      XLSX.writeFile(wb, filename);
    }
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const ctx = document.getElementById('topProductsChart').getContext('2d');

      // Prepare data for chart
      const productNames = <?= json_encode(array_column($top_products, 'product_name')) ?>;
      const quantities = <?= json_encode(array_column($top_products, 'total_quantity')) ?>;
      const revenues = <?= json_encode(array_column($top_products, 'total_revenue')) ?>;

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: productNames,
          datasets: [{
              label: 'Quantity Sold',
              data: quantities,
              backgroundColor: '#4e73df',
              borderColor: '#2e59d9',
              borderWidth: 1,
              yAxisID: 'y'
            },

          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              grid: {
                display: false
              }
            },
            y: {
              type: 'linear',
              display: true,
              position: 'left',
              title: {
                display: true,
                text: 'Quantity Sold'
              },
              beginAtZero: true
            },
            y1: {
              type: 'linear',
              display: true,
              position: 'right',
              title: {
                display: true,
                text: 'Revenue (₱)'
              },
              beginAtZero: true,
              grid: {
                drawOnChartArea: false
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || '';
                  if (label) {
                    label += ': ';
                  }
                  if (context.datasetIndex === 1) {
                    label += '₱' + context.raw.toFixed(2);
                  } else {
                    label += context.raw;
                  }
                  return label;
                }
              }
            }
          }
        }
      });
    });
  </script>
</body>

</html>