<?php

require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

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

// Define allowed statuses manually
$allowedStatuses = ['Pending', 'Shipped', 'Delivered', 'Received', 'Cancelled', 'Returned'];

$statusCounts = $pdo->query("SELECT order_status, COUNT(*) AS count FROM orders GROUP BY order_status")
    ->fetchAll(PDO::FETCH_KEY_PAIR);

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=orders_export.csv');
    $output = fopen('php://output', 'w');
    // Explicitly add separator, enclosure, and escape parameters
    fputcsv($output, ['Order ID', 'Customer', 'Order Date', 'Total', 'Status', 'Delivery Estimate'], ',', '"', '\\');
    foreach ($orders as $order) {
        // Add the same parameters here
        fputcsv($output, [
            $order['order_id'],
            $order['customer_name'],
            $order['order_date'],
            $order['total_price'],
            $order['order_status'],
            $order['estimated_delivery'] ?? 'N/A'
        ], ',', '"', '\\');
    }
    fclose($output);
    exit;
}

function sort_link($column, $label, $sortBy, $sortDir, $filterStatus, $search)
{
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
</head>

<style>
    /* Live alerts styling */
    #liveAlerts {
        max-height: 300px;
        overflow-y: auto;
    }

    .new-order-alert {
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
        }

        to {
            transform: translateX(0);
        }
    }

    .current-status.Received,
    .badge.bg-Received {
        background-color: #198754;
        color: #fff;
    }
</style>

<body?>
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
                        <h5 class="card-title">Recent Orders</h5>
                        <div id="liveAlerts" class="list-group" style="max-height: 300px; overflow-y: auto;"></div>
                    </div>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title">Recent Returns</h5>
                        <div id="liveReturnAlerts" class="list-group" style="max-height: 300px; overflow-y: auto;"></div>
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
                                <tbody id="ordersTableBody">
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="<?= !$order['viewed'] ? 'table-warning' : '' ?>">
                                            <td>#<?= $order['order_id'] ?></td>
                                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                                            <td>₱<?= number_format($order['total_price'], 2) ?></td>
                                            <td>
                                                <span class="current-status" id="status-<?= $order['order_id'] ?>">
                                                    <?= ucfirst($order['order_status']) ?>
                                                </span>
                                                <button class="btn btn-sm btn-primary btn-update-status" data-order-id="<?= $order['order_id'] ?>">
                                                    Update Status
                                                </button>
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
                                                <div class="d-flex gap-2">
                                                    <?php if ($order['order_status'] === 'Pending'): ?>
                                                        <button
                                                            class="btn btn-sm btn-info order-action"
                                                            data-action="ship"
                                                            data-order-id="<?= $order['order_id'] ?>">
                                                            Ship
                                                        </button>
                                                    <?php elseif ($order['order_status'] === 'Shipped'): ?>
                                                        <button
                                                            class="btn btn-sm btn-success order-action"
                                                            data-action="completed"
                                                            data-order-id="<?= $order['order_id'] ?>">
                                                            Complete
                                                        </button>
                                                    <?php endif; ?>

                                                    <button
                                                        class="btn btn-sm btn-primary view-details"
                                                        data-order-id="<?= $order['order_id'] ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
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

    <!-- Update Status Modal with Debug Area -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Debug Message Area -->
                    <div id="updateStatusDebug" class="alert alert-info d-none"></div>
                    <input type="hidden" id="modalOrderId" value="">
                    <div class="mb-3">
                        <label class="form-label">Select New Status</label>
                        <div>
                            <?php foreach ($allowedStatuses as $status): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="new_status" value="<?= $status ?>" id="status_<?= $status ?>">
                                    <label class="form-check-label" for="status_<?= $status ?>">
                                        <?= ucfirst($status) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStatus">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details - Order #<span id="modalOrderIdDisplay"></span></h5>
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


    <!-- Confirmation Modal for Completing an Order -->
    <div class="modal fade" id="confirmCompleteModal" tabindex="-1" aria-labelledby="confirmCompleteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmCompleteLabel">Confirm Order Completion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to mark this order as completed?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmCompleteBtn" class="btn btn-success">Yes, Complete Order</button>
                </div>
            </div>
        </div>
    </div>






    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple toast notification function (adjust styling as needed)
        function showToast(message, type = 'info') {
            // Create a simple toast element if not using a prebuilt one.
            // Here’s a very basic implementation:
            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-white bg-${type} border-0 position-fixed`;
            toastEl.style.right = '20px';
            toastEl.style.top = '20px';
            toastEl.style.zIndex = 1050;
            toastEl.innerHTML = `<div class="d-flex">
                            <div class="toast-body">${message}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                          </div>`;
            document.body.appendChild(toastEl);
            let toast = new bootstrap.Toast(toastEl, {
                delay: 3000
            });
            toast.show();
            // Remove the toast element after it hides.
            toastEl.addEventListener('hidden.bs.toast', () => {
                toastEl.remove();
            });
        }



        $(document).ready(function() {
            // Open Update Status Modal when clicking the Update Status button
            $(document).on('click', '.btn-update-status', function() {
                const orderId = $(this).data('order-id');
                $('#modalOrderId').val(orderId);
                // Clear any previous debug messages
                $("#updateStatusDebug").addClass('d-none').html('');

                // Set the current status in the modal if needed
                const currentStatus = $('#status-' + orderId).text().trim().toLowerCase();
                $('input[name="new_status"]').prop('checked', false);
                $('input[name="new_status"][value="' + currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1) + '"]').prop('checked', true);

                new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
            });

            // Save the new status from the modal with visual debug messages
            $('#saveStatus').click(function() {
                const orderId = $('#modalOrderId').val();
                const newStatus = $('input[name="new_status"]:checked').val();

                if (!newStatus) {
                    return $("#updateStatusDebug")
                        .removeClass('d-none')
                        .text('Please select a status.');
                }

                $.ajax({
                    url: 'ajax/update_order_status.php',
                    method: 'POST',
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    data: JSON.stringify({
                        order_id: orderId,
                        new_status: newStatus
                    }),
                    success(data) {
                        if (data.success) {
                            $('#status-' + orderId).text(newStatus);
                            bootstrap.Modal.getInstance(
                                document.getElementById('updateStatusModal')
                            ).hide();
                        } else {
                            $("#updateStatusDebug")
                                .removeClass('d-none')
                                .text(data.error);
                        }
                    },
                    error(xhr, status, err) {
                        console.error(err);
                        $("#updateStatusDebug")
                            .removeClass('d-none')
                            .text('AJAX error: ' + err);
                    }
                });
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
                if (dateStr) {
                    $.ajax({
                        url: '/pages/ajax/set_delivery_date.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            order_id: orderId,
                            date: dateStr
                        }),
                        success: function(response) {
                            if (response.success) {
                                const cell = $('button.btn-set-delivery-date[data-order-id="' + orderId + '"]').closest('td');
                                cell.html('<span class="delivery-date">' + response.formatted_date + '</span> <button class="btn btn-sm btn-outline-secondary btn-set-delivery-date" data-order-id="' + orderId + '" data-delivery-date="' + dateStr + '">Edit</button>');
                                let modalEl = document.getElementById('deliveryDateModal');
                                let modalInstance = bootstrap.Modal.getInstance(modalEl);
                                if (modalInstance) {
                                    modalInstance.hide();
                                } else {
                                    new bootstrap.Modal(modalEl).hide();
                                }
                            }
                        }
                    });
                }
            });

            // Global variable to store the order ID pending completion
            let currentCompleteOrderId = null;

            $(document).ready(function() {

                // Listen for clicks on the "Complete" button
                $(document).on('click', '.order-action[data-action="completed"]', function() {
                    currentCompleteOrderId = $(this).data('order-id');
                    let confirmModal = new bootstrap.Modal(document.getElementById('confirmCompleteModal'));
                    confirmModal.show();
                });

                $('#confirmCompleteBtn').on('click', async function() {
                    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmCompleteModal'));
                    confirmModal.hide();

                    if (!currentCompleteOrderId) {
                        showToast("No order selected", "danger");
                        return;
                    }

                    try {
                        const response = await fetch('ajax/complete_order.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                order_id: currentCompleteOrderId
                            })
                        });

                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            const text = await response.text();
                            throw new Error(`Expected JSON but got: ${text.substring(0, 100)}...`);
                        }

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.error || 'Failed to complete order');
                        }

                        showToast("Order completed and archived", "success");

                        // === Option A: Refresh the page (recommended for summaries or counts)
                        setTimeout(() => {
                            location.reload();
                        }, 1000); // Add delay for toast visibility

                        // === Option B: Remove just the row (uncomment below if preferred)
                        // $(`tr:has(button[data-order-id="${currentCompleteOrderId}"])`).remove();

                    } catch (error) {
                        console.error("Error:", error);
                        showToast(error.message, "danger");
                    }

                    currentCompleteOrderId = null;
                });

            });




            // View Order Details in Modal when clicking the eye icon
            $(document).on('click', '.view-details', function() {
                const orderId = $(this).data('order-id');
                $('#orderDetailsDebug').html('<div class="alert alert-info">Loading details for Order #' + orderId + '...</div>');
                $.get('ajax/get_order_details.php', {
                        order_id: orderId
                    })
                    .done(function(data) {
                        $('#modalOrderIdDisplay').text(orderId);
                        $('#orderDetailsModal .modal-body').html(data);
                        new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        $('#orderDetailsModal .modal-body').html('<div class="alert alert-danger">Error loading order details: ' + textStatus + '</div>');
                        new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                    });
            });

            // Real-time alerts for new orders
            let lastCheck = Math.floor(Date.now() / 1000); // current time in seconds

            async function checkNewOrders() {
                try {
                    // Build the URL using a template literal.
                    const response = await fetch(`ajax/get_new_orders.php?lastCheck=${lastCheck}`);
                    if (!response.ok) {
                        const text = await response.text();
                        console.error("Server response:", text);
                        throw new Error("Network response was not ok");
                    }
                    const data = await response.json();
                    // If there are new orders returned from the server, display them.
                    if (Array.isArray(data.orders) && data.orders.length > 0) {
                        data.orders.forEach(order => {
                            const date = new Date(order.timestamp * 1000);
                            const alertHTML = `
          <div class="list-group-item list-group-item-warning">
              <div class="d-flex justify-content-between align-items-center">
                  <div>
                      <strong>Order #${order.order_id}</strong><br>
                      <small>${order.customer}</small>
                  </div>
                  <small class="text-nowrap">
                      ${date.toLocaleDateString()}<br>
                      ${date.toLocaleTimeString()}
                  </small>
              </div>
          </div>
                        `;
                            document.getElementById('liveAlerts').insertAdjacentHTML('afterbegin', alertHTML);
                        });
                    }
                    // Update the lastCheck timestamp provided by the server or use the current time.
                    lastCheck = data.lastCheck ? data.lastCheck : Math.floor(Date.now() / 1000);
                } catch (error) {
                    console.error('Error fetching orders:', error);
                    const errorAlert = `
          <div class="list-group-item list-group-item-danger">
              Connection Error: ${error.message}
          </div>
                `;
                    document.getElementById('liveAlerts').insertAdjacentHTML('afterbegin', errorAlert);
                }
            }

            // Call checkNewOrders every 3 seconds.
            setInterval(checkNewOrders, 3000);
            checkNewOrders();

        });

        // Ship button
        $(document).on('click', '.order-action[data-action="ship"]', async function() {
            const btn = $(this);
            const orderId = btn.data('order-id');

            try {
                const res = await fetch('ajax/ship_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                });
                const result = await res.json();
                if (!result.success) throw new Error(result.error);

                // Update UI
                $('#status-' + orderId).text('Shipped');
                btn.replaceWith(`
  <button
    class="btn btn-sm btn-success order-action"
    data-action="completed"
    data-order-id="${orderId}">
    Complete
  </button>
`);
                showToast(`Order #${orderId} marked as Shipped.`, 'info');
            } catch (err) {
                showToast(err.message, 'danger');
            }
        });
    </script>




    </body>

</html>