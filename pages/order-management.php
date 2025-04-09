<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// FIFO + Filters + Search + Sort Config
$ordersPerPage = 15;
$currentPage = $_GET['page'] ?? 1;
$offset = ($currentPage - 1) * $ordersPerPage;

$search = $_GET['search'] ?? '';
$filterStatus = $_GET['filter'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'order_date';
$sortDir = $_GET['dir'] ?? 'asc';

$where = "WHERE 1";
$params = [];

if ($filterStatus !== 'all') {
    $where .= " AND o.order_status = ?";
    $params[] = $filterStatus;
}
if ($search) {
    $where .= " AND (u.name LIKE ? OR o.order_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON o.customer_id = u.user_id $where");
$totalStmt->execute($params);
$totalOrders = $totalStmt->fetchColumn();

$query = "
    SELECT o.*, u.name AS customer_name, dm.method_name, dm.estimated_days
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    JOIN delivery_methods dm ON o.delivery_method_id = dm.delivery_method_id
    $where
    ORDER BY $sortBy $sortDir
    LIMIT ? OFFSET ?
";
$params[] = (int)$ordersPerPage;
$params[] = (int)$offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark viewed orders.
$unviewed = array_filter($orders, fn($o) => !$o['viewed']);
if ($unviewed) {
    $ids = implode(',', array_column($unviewed, 'order_id'));
    $pdo->exec("UPDATE orders SET viewed = TRUE WHERE order_id IN ($ids)");
}

// We used to pull order_status values from the orders table, but that gives only currently used statuses.
// Instead, define the allowed enum values from your database definition manually.
$allowedStatuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled', 'Returned'];

// Still, we get the counts from the database for display (optional)
$statusCounts = $pdo->query("SELECT order_status, COUNT(*) AS count FROM orders GROUP BY order_status")
                    ->fetchAll(PDO::FETCH_KEY_PAIR);

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=orders_export.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Customer', 'Order Date', 'Total', 'Status', 'Delivery Estimate']);
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['order_id'],
            $order['customer_name'],
            $order['order_date'],
            $order['total_price'],
            $order['order_status'],
            $order['estimated_delivery'] ?? 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

function sort_link($column, $label, $sortBy, $sortDir, $filterStatus, $search) {
    $dir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
    $icon = ($sortBy === $column) ? ($sortDir === 'asc' ? '▲' : '▼') : '';
    return "<a href=\"?sort=$column&dir=$dir&filter=$filterStatus&search=$search\">$label $icon</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management - BunniShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/orders.css">
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar / Filters -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Filters</h5>
                    <div class="list-group">
                        <a href="?filter=all" class="list-group-item list-group-item-action <?= $filterStatus === 'all' ? 'active' : '' ?>">
                            All Orders <span class="badge bg-primary float-end"><?= array_sum($statusCounts) ?></span>
                        </a>
                        <?php foreach ($statusCounts as $status => $count): ?>
                            <a href="?filter=<?= $status ?>" class="list-group-item list-group-item-action <?= $filterStatus === $status ? 'active' : '' ?>">
                                <?= ucfirst($status) ?> <span class="badge bg-secondary float-end"><?= $count ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Live Alerts</h5>
                    <div id="liveAlerts" class="list-group" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                            <input type="hidden" name="filter" value="<?= $filterStatus ?>">
                            <input type="hidden" name="sort" value="<?= $sortBy ?>">
                            <input type="hidden" name="dir" value="<?= $sortDir ?>">
                            <button class="btn btn-primary">Search</button>
                            <a href="order-management.php" class="btn btn-outline-secondary">Reset</a>
                        </form>
                        <a href="?export=csv&filter=<?= $filterStatus ?>&search=<?= $search ?>" class="btn btn-success">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><?= sort_link('order_id', 'Order ID', $sortBy, $sortDir, $filterStatus, $search) ?></th>
                                    <th><?= sort_link('customer_name', 'Customer', $sortBy, $sortDir, $filterStatus, $search) ?></th>
                                    <th><?= sort_link('order_date', 'Order Date', $sortBy, $sortDir, $filterStatus, $search) ?></th>
                                    <th><?= sort_link('total_price', 'Total', $sortBy, $sortDir, $filterStatus, $search) ?></th>
                                    <th>Status</th>
                                    <th>Delivery Estimate</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr class="<?= !$order['viewed'] ? 'table-warning' : '' ?>">
                                    <td>#<?= $order['order_id'] ?></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                    <td>₱<?= number_format($order['total_price'], 2) ?></td>
                                    <td>
                                        <!-- Use allowedStatuses array to populate the dropdown -->
                                        <select class="form-select status-select" data-order-id="<?= $order['order_id'] ?>">
                                            <?php foreach ($allowedStatuses as $status): ?>
                                                <option value="<?= $status ?>" <?= $status === $order['order_status'] ? 'selected' : '' ?>>
                                                    <?= ucfirst($status) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <?php if ($order['estimated_delivery']): ?>
                                            <span class="delivery-date"><?= date('M d, Y', strtotime($order['estimated_delivery'])) ?></span>
                                            <button class="btn btn-sm btn-outline-secondary btn-set-delivery-date" data-order-id="<?= $order['order_id'] ?>" data-delivery-date="<?= $order['estimated_delivery'] ?>">Edit</button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary btn-set-delivery-date" data-order-id="<?= $order['order_id'] ?>">Set Date</button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group mb-1" role="group">
                                            <!-- Order action buttons can be enabled later -->
                                            <button class="btn btn-sm btn-danger order-action" data-action="cancelled" data-order-id="<?= $order['order_id'] ?>">Cancel</button>
                                            <button class="btn btn-sm btn-info order-action" data-action="confirmed" data-order-id="<?= $order['order_id'] ?>">Confirm</button>
                                            <button class="btn btn-sm btn-success order-action" data-action="completed" data-order-id="<?= $order['order_id'] ?>">Complete</button>
                                        </div>
                                        <button class="btn btn-sm btn-primary view-details" data-order-id="<?= $order['order_id'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= ceil($totalOrders / $ordersPerPage); $i++): ?>
                                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&filter=<?= $filterStatus ?>&search=<?= $search ?>&sort=<?= $sortBy ?>&dir=<?= $sortDir ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Set Delivery Date Modal -->
<div class="modal fade" id="deliveryDateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
     <div class="modal-content">
         <div class="modal-header">
             <h5 class="modal-title">Set Delivery Date</h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <div class="mb-3">
              <label for="delivery_date" class="form-label">Delivery Date</label>
              <input type="date" class="form-control" name="delivery_date" id="delivery_date">
            </div>
         </div>
         <div class="modal-footer">
             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
             <button type="button" class="btn btn-primary" id="saveDeliveryDate">Save</button>
         </div>
     </div>
  </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
     <div class="modal-content">
         <div class="modal-header">
             <h5 class="modal-title">Order Details - Order #<span id="modalOrderId"></span></h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <div id="orderDetailsDebug" class="mb-3"></div>
            <!-- Order details loaded via AJAX will appear here -->
         </div>
         <div class="modal-footer">
             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         </div>
     </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Inline Status Update using dropdown
$(document).on('change', '.status-select', function() {
    const orderId = $(this).data('order-id');
    const newStatus = $(this).val();
    $.post('ajax/update_order_status.php', { order_id: orderId, new_status: newStatus }, function(response) {
        if (response.success) {
            console.log('Status updated.');
        }
    }, 'json');
});

// Open Set Delivery Date Modal
$(document).on('click', '.btn-set-delivery-date', function() {
    const orderId = $(this).data('order-id');
    const existingDate = $(this).data('delivery-date') || '';
    $('#deliveryDateModal').data('order-id', orderId);
    $('#deliveryDateModal input[name="delivery_date"]').val(existingDate);
    new bootstrap.Modal(document.getElementById('deliveryDateModal')).show();
});

// Save Delivery Date from Modal
$('#saveDeliveryDate').click(function() {
   const orderId = $('#deliveryDateModal').data('order-id');
   const dateStr = $('#deliveryDateModal input[name="delivery_date"]').val();
   if(dateStr) {
      $.ajax({
          url: 'ajax/set_delivery_date.php',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify({ order_id: orderId, date: dateStr }),
          success: function(response) {
            if(response.success) {
              const cell = $('button.btn-set-delivery-date[data-order-id="'+ orderId +'"]').closest('td');
              cell.html('<span class="delivery-date">'+ response.formatted_date +'</span> <button class="btn btn-sm btn-outline-secondary btn-set-delivery-date" data-order-id="'+ orderId +'" data-delivery-date="'+ dateStr +'">Edit</button>');
              bootstrap.Modal.getInstance(document.getElementById('deliveryDateModal')).hide();
            }
          }
      });
   }
});

// Order Action Buttons (Cancel, Confirm, Complete)
// (Removed for now per request focus on dropdown functionality.)

// View Order Details in Modal when clicking the eye icon
$(document).on('click', '.view-details', function() {
    const orderId = $(this).data('order-id');
    $('#orderDetailsDebug').html('<div class="alert alert-info">Loading details for Order #' + orderId + '...</div>');
    $.get('ajax/get_order_details.php', { order_id: orderId })
        .done(function(data) {
            $('#modalOrderId').text(orderId);
            $('#orderDetailsModal .modal-body').html(data);
            new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            $('#orderDetailsModal .modal-body').html('<div class="alert alert-danger">Error loading order details: ' + textStatus + '</div>');
            new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
        });
});

// Real-time alerts for new orders
let lastCheck = Date.now() / 1000;
function checkNewOrders() {
    $.ajax({
        url: 'ajax/get_new_orders.php',
        data: { lastCheck: lastCheck },
        success: function(orders) {
            if (orders.length > 0) {
                orders.forEach(order => {
                    const alert = `<div class="list-group-item list-group-item-warning">
                        <div class="d-flex justify-content-between">
                            <div>New Order #${order.id} from ${order.customer}</div>
                            <small>${new Date(order.time).toLocaleString()}</small>
                        </div>
                    </div>`;
                    $('#liveAlerts').prepend(alert);
                });
                lastCheck = Date.now() / 1000;
            }
        }
    });
}
setInterval(checkNewOrders, 10000);
checkNewOrders();
</script>
</body>
</html>
