<?php
// pages/my-orders.php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../includes/session-init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
$userId = $_SESSION['user_id'];

// 1. Fetch active (live) orders, including shipping info
$activeSql = "
    SELECT
      o.order_id,
      o.order_date,
      o.total_price,
      o.order_status,
      o.shipping_address,
      o.shipping_phone,
      dm.method_name
    FROM orders o
    JOIN delivery_methods dm
      ON o.delivery_method_id = dm.delivery_method_id
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC
";
$activeStmt = $pdo->prepare($activeSql);
$activeStmt->execute([$userId]);
$activeOrders = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch completed (archived) orders, including shipping info
$completedSql = "
    SELECT 
      ao.order_id,
      ao.order_date,
      ao.total_price,
      ao.shipping_address,
      ao.shipping_phone,
      dm.method_name,
      (SELECT COUNT(*) FROM returns r WHERE r.order_id = ao.order_id) AS return_count
    FROM archived_orders ao
    JOIN delivery_methods dm 
      ON ao.delivery_method_id = dm.delivery_method_id
    WHERE ao.customer_id = ?
    ORDER BY ao.order_date DESC
";
$compStmt = $pdo->prepare($completedSql);
$compStmt->execute([$userId]);
$completedOrders = $compStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Bunnishop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/my-orders.css">
    <style>
        .order-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .order-card.Pending {
            border-left-color: #ffc107;
        }

        .order-card.Shipped {
            border-left-color: #0dcaf0;
        }

        .order-card.Delivered {
            border-left-color: #198754;
        }

        .order-card.Cancelled {
            border-left-color: #dc3545;
        }

        .order-card.Returned {
            border-left-color: #6c757d;
        }

        .order-card.Received {
            border-left-color: #198754;
        }

        .status-badge.bg-Received {
            /* you can map it to “success” */
        }


        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }

        #orderDetailsModal .modal-dialog {
            max-width: 800px;
        }
    </style>
</head>

<body>
    <?php include '../includes/user-navbar.php'; ?>

    <div class="container py-5">
        <!-- Active Orders -->
        <h2 class="mb-4">Active Orders</h2>
        <?php if (empty($activeOrders)): ?>
            <div class="alert alert-info">No active orders at the moment.</div>
        <?php else: ?>
            <div class="table-responsive mb-5">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Delivery</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeOrders as $order):
                            // count items for this order
                            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM order_details WHERE order_id = ?");
                            $countStmt->execute([$order['order_id']]);
                            $itemCount = $countStmt->fetchColumn();
                        ?>
                            <tr class="order-card <?= htmlspecialchars($order['order_status']) ?>">
                                <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                <td><?= $itemCount ?></td>
                                <td>₱<?= number_format($order['total_price'], 2) ?></td>
                                <td>
                                    <span class="badge status-badge bg-<?= match ($order['order_status']) {
                                                                            'Pending'   => 'warning',
                                                                            'Shipped'   => 'info',
                                                                            'Delivered' => 'success',
                                                                            'Cancelled' => 'danger',
                                                                            default     => 'secondary'
                                                                        } ?>">
                                        <?= htmlspecialchars($order['order_status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($order['method_name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></td>
                                <td><?= htmlspecialchars($order['shipping_phone']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary view-order-details"
                                        data-order-id="<?= $order['order_id'] ?>">
                                        View Details
                                    </button>
                                    <?php if ($order['order_status'] === 'Shipped'): ?>
                                        <button
                                            class="btn btn-sm btn-outline-success order-action"
                                            data-action="received"
                                            data-order-id="<?= $order['order_id'] ?>">
                                            Mark Received
                                        </button>
                                    <?php endif; ?>

                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Completed Orders -->
        <h2 class="mb-4">Completed Orders</h2>
        <?php if (empty($completedOrders)): ?>
            <div class="alert alert-info">No completed orders yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Delivery</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedOrders as $order):
                            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM archived_order_details WHERE order_id = ?");
                            $countStmt->execute([$order['order_id']]);
                            $itemCount = $countStmt->fetchColumn();
                        ?>
                            <tr class="order-card Returned">
                                <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                <td><?= $itemCount ?></td>
                                <td>₱<?= number_format($order['total_price'], 2) ?></td>
                                <td><span class="badge status-badge bg-secondary">Completed</span></td>
                                <td><?= htmlspecialchars($order['method_name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></td>
                                <td><?= htmlspecialchars($order['shipping_phone']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary view-order-details"
                                        data-order-id="<?= htmlspecialchars($order['order_id']) ?>">
                                        View Details
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning order-action"
                                        data-action="return"
                                        data-order-id="<?= htmlspecialchars($order['order_id']) ?>"
                                        <?= $order['return_count'] ? 'disabled' : '' ?>>
                                        Return
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>



    </div>

    <!-- Return Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Return Order #<span id="returnOrderIdLabel"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="returnForm">
                        <div class="mb-3">
                            <label for="returnReason" class="form-label">Reason for Return</label>
                            <textarea id="returnReason" class="form-control" rows="2" placeholder="e.g. Wrong size…"></textarea>
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllReturn"></th>
                                    <th>Product</th>
                                    <th>Ordered Qty</th>
                                    <th>Return Qty</th>
                                </tr>
                            </thead>
                            <tbody id="returnItemsBody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmReturnBtn" class="btn btn-warning">Process Return</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- AJAX content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            const orderDetailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));

            $('.view-order-details').on('click', function() {
                const orderId = $(this).data('order-id');
                $('#orderDetailsModalLabel').text('Order #' + orderId + ' Details');

                // Clear modal content before loading new content
                $('#orderDetailsContent').html(`
                <div class="text-center my-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

                $.ajax({
                    // Changed to absolute path so it correctly finds get_order_details.php.
                    // Adjust the path below if your project is hosted in a subdirectory.
                    url: '/pages/ajax/get_order_details.php',
                    method: 'GET',
                    data: {
                        order_id: orderId
                    },
                    success: function(response) {
                        $('#orderDetailsContent').html(response);
                        orderDetailsModal.show(); // Show modal after content is loaded
                    },
                    error: function(xhr) {
                        $('#orderDetailsContent').html(`
                        <div class="alert alert-danger">
                            Failed to load order details. Please try again later.
                        </div>
                    `);
                        console.error(xhr.responseText);
                    }
                });
            });
        });
    </script>

    <script>
        let currentReturnOrderId = null;

        // Open and populate modal
        $(document).on('click', '.order-action[data-action="return"]', async function() {
            currentReturnOrderId = $(this).data('order-id');
            $('#returnOrderIdLabel').text(currentReturnOrderId);
            $('#returnReason').val('');

            // Fetch line items as JSON
            const resp = await fetch(`/pages/ajax/get_return_items.php?order_id=${currentReturnOrderId}`);
            const items = await resp.json(); // expects [{ product_id, product_name, quantity }, …]

            const $body = $('#returnItemsBody').empty();
            items.forEach(it => {
                $body.append(`
      <tr>
        <td>
          <input type="checkbox" class="return-checkbox" data-product-id="${it.product_id}" checked>
        </td>
        <td>${it.product_name}</td>
        <td>${it.quantity}</td>
        <td>
          <input type="number" class="return-qty form-control"
                 data-product-id="${it.product_id}"
                 value="${it.quantity}" min="1" max="${it.quantity}">
        </td>
      </tr>
    `);
            });

            // select-all support
            $('#selectAllReturn').off().on('change', function() {
                $('.return-checkbox').prop('checked', this.checked);
            }).prop('checked', true);

            new bootstrap.Modal($('#returnModal')).show();
        });

        // Submit return
        $('#confirmReturnBtn').on('click', async () => {
            const reason = $('#returnReason').val().trim();
            const items = [];

            $('.return-checkbox:checked').each(function() {
                const pid = +$(this).data('product-id');
                const qty = +$(`.return-qty[data-product-id="${pid}"]`).val();
                items.push({
                    product_id: pid,
                    quantity: qty
                });
            });

            if (!items.length) {
                alert('Select at least one item to return.');
                return;
            }

            const res = await fetch('/pages/ajax/return_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: currentReturnOrderId,
                    reason,
                    items
                })
            });
            const result = await res.json();

            if (result.success) {
                // disable the button to prevent duplicates
                $(`.order-action[data-action="return"][data-order-id="${currentReturnOrderId}"]`)
                    .prop('disabled', true);
                alert('Return processed successfully.');
                $('#returnModal').modal('hide');
            } else {
                alert('Error: ' + result.error);
            }
        });

        $(document).on('click', '.order-action[data-action="received"]', function() {
            const orderId = $(this).data('order-id');
            if (!confirm(`Confirm you’ve received order #${orderId}?`)) return;

            fetch('/pages/ajax/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        new_status: 'Received'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // you could simply reload, or update the row in-place
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    </script>

</body>

</html>