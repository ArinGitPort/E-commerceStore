<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
// pages/users-orders.php
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
      (SELECT COUNT(*) FROM returns r WHERE r.archived_order_id = ao.order_id) AS return_count,
      ao.order_status 
    FROM archived_orders ao
    JOIN delivery_methods dm 
      ON ao.delivery_method_id = dm.delivery_method_id
    WHERE ao.customer_id = ?
    ORDER BY ao.order_date DESC
";

$compStmt = $pdo->prepare($completedSql);
$compStmt->execute([$userId]);
$completedOrders = $compStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch returns
$returnsSql = "
    SELECT 
        r.return_id,
        r.return_date,
        r.reason,
        r.return_status,
        r.last_status_update,
        ao.order_id,
        ao.order_date,
        ao.total_price,
        dm.method_name,
        COUNT(ri.return_item_id) AS item_count
    FROM returns r
    JOIN archived_orders ao ON r.archived_order_id = ao.order_id
    JOIN delivery_methods dm ON ao.delivery_method_id = dm.delivery_method_id
    LEFT JOIN return_items ri ON r.return_id = ri.return_id
    WHERE ao.customer_id = ?
    GROUP BY r.return_id
    ORDER BY r.return_date DESC
";
$returnsStmt = $pdo->prepare($returnsSql);
$returnsStmt->execute([$userId]);
$returns = $returnsStmt->fetchAll(PDO::FETCH_ASSOC);
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
            /* you can map it to "success" */
        }


        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }

        #orderDetailsModal .modal-dialog {
            max-width: 800px;
        }

        /* Add return status colors */
        .return-status.Pending {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }

        .return-status.Approved {
            border-left-color: #198754;
            background-color: #d1e7dd;
        }

        .return-status.Rejected {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }

        .return-status.Processed {
            border-left-color: #0d6efd;
            background-color: #cfe2ff;
        }

        .refund-status {
            font-size: 0.9rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        .refund-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .refund-completed {
            background-color: #d4edda;
            color: #155724;
        }

        /* Disabled state styling */
        .order-action[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Disabled checkbox styling */
        .return-checkbox:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Disabled quantity input styling */
        .return-qty:disabled {
            background-color: #f8f9fa;
            opacity: 0.7;
        }
    </style>
</head>

<body>
    <?php include '../includes/user-navbar.php'; ?>

    <div class="container py-5">

        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-4" id="ordersTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-orders" type="button" role="tab">
                    Active Orders <span class="badge bg-warning"><?= count($activeOrders) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-orders" type="button" role="tab">
                    Completed Orders <span class="badge bg-secondary"><?= count($completedOrders) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns" type="button" role="tab">
                    Returns & Refunds <span class="badge bg-info"><?= count($returns) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="ordersTabContent">
            <div class="tab-pane fade show active" id="active-orders" role="tabpanel" aria-labelledby="active-tab">
                <!-- Active Orders -->
                <h2 class="mb-4">Active Orders</h2>
                <?php if (empty($activeOrders)): ?>
                    <div class="alert alert-info">No active orders at the moment.</div>
                <?php else: ?>
                    <div class="table-responsive mb-5">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
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
                                                                                    'Pending'   => 'warning',  // Pending orders get a yellow (warning) badge
                                                                                    'Shipped'   => 'info',     // Shipped orders get a blue (info) badge
                                                                                    'Delivered' => 'success',  // Delivered orders get a green (success) badge
                                                                                    'Cancelled' => 'danger',   // Cancelled orders get a red (danger) badge
                                                                                    'Returned'  => 'secondary', // Returned orders get a gray (secondary) badge
                                                                                    'Rejected'  => 'danger',   // Rejected orders get a red (danger) badge
                                                                                    default     => 'secondary' // Default for other statuses
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
                                                <button class="btn btn-sm btn-outline-success order-action"
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
            </div>

            <div class="tab-pane fade" id="completed-orders" role="tabpanel" aria-labelledby="completed-tab">
                <!-- Completed Orders -->
                <h2 class="mb-4">Completed Orders</h2>
                <?php if (empty($completedOrders)): ?>
                    <div class="alert alert-info">No completed orders yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
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


            <!-- Returns Tab -->
            <div class="tab-pane fade" id="returns" role="tabpanel" aria-labelledby="returns-tab">
                <h2 class="mb-4">Returns & Refunds</h2>
                <?php if (empty($returns)): ?>
                    <div class="alert alert-info">No return requests found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Return #</th>
                                    <th>Order #</th>
                                    <th>Date Requested</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Last Update</th>
                                    <th>Refund</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($returns as $return): ?>
                                    <tr class="return-status <?= htmlspecialchars($return['return_status']) ?>">
                                        <td>#<?= $return['return_id'] ?></td>
                                        <td>#<?= $return['order_id'] ?></td>
                                        <td><?= date('M d, Y', strtotime($return['return_date'])) ?></td>
                                        <td><?= $return['item_count'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= match ($return['return_status']) {
                                                                        'Pending' => 'warning',
                                                                        'Approved' => 'success',
                                                                        'Rejected' => 'danger',
                                                                        'Processed' => 'primary',
                                                                        default => 'secondary'
                                                                    } ?>">
                                                <?= htmlspecialchars($return['return_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($return['last_status_update'])) ?></td>
                                        <td>
                                            <?php if ($return['return_status'] === 'Processed'): ?>
                                                <span class="refund-status refund-completed">
                                                    <i class="bi bi-check-circle-fill"></i> Completed
                                                </span>
                                            <?php elseif ($return['return_status'] === 'Approved'): ?>
                                                <span class="refund-status refund-pending">
                                                    <i class="bi bi-clock-history"></i> Processing
                                                </span>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary view-return-details"
                                                data-return-id="<?= $return['return_id'] ?>">
                                                Details
                                            </button>
                                            <?php if ($return['return_status'] === 'Pending'): ?>
                                                <button class="btn btn-sm btn-outline-danger cancel-return"
                                                    data-return-id="<?= $return['return_id'] ?>">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
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

    <!-- Mark as Received Confirmation Modal -->
    <div class="modal fade" id="markReceivedModal" tabindex="-1" aria-labelledby="markReceivedModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="markReceivedModalLabel">Confirm Mark Order as Received</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to mark this order as **Received**? This action will update the order status.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmMarkReceivedBtn" class="btn btn-success">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Order Confirmation Modal -->
    <div class="modal fade" id="returnOrderModal" tabindex="-1" aria-labelledby="returnOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnOrderModalLabel">Confirm Order Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to return this order? You can select the items to return in the next step.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmReturnOrderBtn" class="btn btn-warning">Confirm Return</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Mark Received Success Modal -->
    <div class="modal fade" id="receivedSuccessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Success!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-3 text-success me-3"></i>
                        <span>Order marked as received successfully!</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Return Status Modal (Reusable for success/error) -->
    <div class="modal fade" id="returnStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Return Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="returnStatusContent" class="d-flex align-items-center">
                        <i class="bi fs-3 me-3"></i>
                        <span></span>
                    </div>
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
        // Return Modal Handling
        let currentReturnOrderId = null;

        // Return button handler
        $(document).on('click', '.order-action[data-action="return"]:not(:disabled)', async function() {
            currentReturnOrderId = $(this).data('order-id'); // Add this line
            const orderId = currentReturnOrderId; // Now use this variable
            $('#returnItemsBody').empty().html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');

            try {
                const response = await fetch(`/pages/ajax/get_return_items.php?order_id=${orderId}`);

                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                const result = await response.json();

                if (!result.success) throw new Error(result.message);

                // Handle different response types
                switch (result.type) {
                    case 'warning':
                        showReturnStatus('warning', result.message);
                        return;

                    case 'data':
                        populateReturnModal(result.data);
                        new bootstrap.Modal('#returnModal').show();
                        break;

                    default:
                        throw new Error('Unexpected response format');
                }

            } catch (error) {
                console.error('Return error:', error);
                showReturnStatus('error', error.message || 'Failed to load return details');
            }
        });

        // Modal population function
        function populateReturnModal(items) {
            const $body = $('#returnItemsBody').empty();

            items.forEach(item => {
                $body.append(`
            <tr>
                <td>
                    <input type="checkbox" 
                           class="return-checkbox"
                           data-product-id="${item.product_id}"
                           ${item.max_returnable > 0 ? 'checked' : 'disabled'}>
                </td>
                <td>${item.product_name}</td>
                <td>${item.ordered_quantity}</td>
                <td>
                    <input type="number"
                           class="return-qty form-control"
                           data-product-id="${item.product_id}"
                           value="${item.max_returnable}"
                           min="1"
                           max="${item.max_returnable}"
                           ${item.max_returnable > 0 ? '' : 'disabled'}>
                </td>
            </tr>
        `);
            });

            // Select-all handler
            $('#selectAllReturn').off('change').on('change', function() {
                $('.return-checkbox:not(:disabled)').prop('checked', this.checked);
            });
        }

        // Status display handler
        function showReturnStatus(type, message) {
            const icons = {
                error: 'bi-x-circle-fill text-danger',
                warning: 'bi-exclamation-triangle-fill text-warning',
                success: 'bi-check-circle-fill text-success'
            };

            $('#returnStatusContent').html(`
        <i class="bi ${icons[type]} fs-3 me-2"></i>
        <span>${message}</span>
    `);
            new bootstrap.Modal('#returnStatusModal').show();
        }

        // Handle return submission
        // The current code in your confirmReturnBtn click handler may be problematic
        $('#confirmReturnBtn').off('click').on('click', async function() {
            const $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

            try {
                // Client-side validation
                const reason = $('#returnReason').val().trim();
                const items = [];

                $('.return-checkbox:checked:not(:disabled)').each(function() {
                    const pid = +$(this).data('product-id');
                    const qtyInput = $(`.return-qty[data-product-id="${pid}"]`);
                    const qty = Math.min(Math.max(1, +qtyInput.val()), +qtyInput.attr('max'));

                    items.push({
                        product_id: pid,
                        quantity: qty
                    });
                });

                if (items.length === 0) {
                    throw new Error('Please select at least one item to return');
                }

                if (reason.length < 20) {
                    throw new Error('Reason must be at least 20 characters');
                }

                // Add this for debugging purposes
                console.log('Sending return request:', {
                    order_id: currentReturnOrderId,
                    reason,
                    items
                });

                // Server request
                const response = await fetch('/pages/ajax/return_order.php', {
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

                const data = await response.json();
                console.log('Server response:', data);

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Request failed');
                }

                // Success handling
                $(`.order-action[data-action="return"][data-order-id="${currentReturnOrderId}"]`)
                    .prop('disabled', true)
                    .text('Return Requested');

                $('#returnModal').modal('hide');
                showReturnStatus('success', data.message);

            } catch (error) {
                console.error('Return error:', error);
                showReturnStatus('error', error.message);
            } finally {
                $btn.prop('disabled', false).html('Process Return');
            }
        });

        // Enhanced status display
        function showReturnStatus(type, message) {
            const iconMap = {
                error: 'bi-x-circle-fill text-danger',
                warning: 'bi-exclamation-triangle-fill text-warning',
                success: 'bi-check-circle-fill text-success'
            };

            $('#returnStatusContent').html(`
        <i class="bi ${iconMap[type]} fs-3 me-2"></i>
        <span class="align-middle">${message}</span>
    `);
            new bootstrap.Modal('#returnStatusModal').show();
        }

        // Mark order as received
        $(document).on('click', '.order-action[data-action="received"]', function() {
            const orderId = $(this).data('order-id');
            if (!confirm(`Confirm you've received order #${orderId}?`)) return;

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
                        // Reload the page to show updated status
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error('Error updating order status:', err);
                    alert('An error occurred while updating the order status. Please try again.');
                });
        });
    </script>

    <script>
        // Mark as Received with Modal Confirmation
        $(document).on('click', '.order-action[data-action="received"]', function() {
            const orderId = $(this).data('order-id');
            const $btn = $(this);
            const receivedModal = new bootstrap.Modal('#markReceivedModal');

            // Show confirmation modal
            receivedModal.show();

            // Handle confirmation
            $('#confirmMarkReceivedBtn').off().on('click', async function() {
                receivedModal.hide();

                // Show loading state
                $btn.html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing...');

                try {
                    const response = await fetch('/pages/ajax/update_order_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            new_status: 'Received'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Show success modal
                        const successModal = new bootstrap.Modal('#receivedSuccessModal');
                        successModal.show();

                        // Refresh the page after 2 seconds
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showReturnStatus('error', data.error || 'Failed to mark order as received');
                    }
                } catch (error) {
                    showReturnStatus('error', 'An error occurred. Please try again.');
                } finally {
                    $btn.html('Mark Received');
                }
            });
        });

        // Process Return with Modal Feedback
        $('#confirmReturnBtn').on('click', async () => {
            const $btn = $('#confirmReturnBtn');
            const originalText = $btn.html();

            // Show loading state
            $btn.html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing...');

            try {
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
                    showReturnStatus('warning', 'Please select at least one item to return');
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
                    // Hide return modal
                    $('#returnModal').modal('hide');

                    // Disable return button
                    $(`.order-action[data-action="return"][data-order-id="${currentReturnOrderId}"]`)
                        .prop('disabled', true);

                    // Show success modal
                    showReturnStatus('success', 'Return processed successfully!');
                } else {
                    showReturnStatus('error', result.error || 'Failed to process return');
                }
            } catch (error) {
                showReturnStatus('error', 'An error occurred. Please try again.');
            } finally {
                $btn.html(originalText);
            }
        });

        // Helper function to show status modals
        function showReturnStatus(type, message) {
            const modal = new bootstrap.Modal('#returnStatusModal');
            const $content = $('#returnStatusContent');

            $content.find('i').removeClass('bi-check-circle-fill bi-exclamation-triangle-fill text-success text-danger')
                .addClass(
                    type === 'success' ? 'bi-check-circle-fill text-success' :
                    type === 'error' ? 'bi-exclamation-triangle-fill text-danger' :
                    'bi-info-circle-fill text-warning'
                );

            $content.find('span').text(message);
            modal.show();
        }

        // Return Details & Cancel Functionality
        $(document).ready(function() {
            // Return Details Handler
            // Return Details Handler
            $(document).on('click', '.view-return-details', async function() {
                const returnId = $(this).data('return-id');

                // Show loading in the modal
                $('#orderDetailsModalLabel').text('Return #' + returnId + ' Details');
                $('#orderDetailsContent').html(`
        <div class="text-center my-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `);

                // Show the modal while loading
                const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                modal.show();

                try {
                    // Fetch return details
                    const response = await fetch(`/pages/ajax/get_return_details.php?return_id=${returnId}`);
                    const data = await response.json(); // Parse as JSON instead of text

                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load return details');
                    }

                    // Format the JSON data as HTML
                    const returnInfo = data.return;
                    const items = data.items;
                    const history = data.history;
                    const totals = data.totals;

                    // Create HTML content from JSON data
                    let htmlContent = `
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Return Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Return ID:</strong> #${returnInfo.return_id}</p>
                            <p><strong>Order ID:</strong> #${returnInfo.order_id}</p>
                            <p><strong>Return Date:</strong> ${new Date(returnInfo.return_date).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> <span class="badge bg-${getStatusBadgeClass(returnInfo.return_status)}">${returnInfo.return_status}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Customer:</strong> ${returnInfo.customer_name}</p>
                            <p><strong>Email:</strong> ${returnInfo.customer_email}</p>
                            <p><strong>Original Order Date:</strong> ${new Date(returnInfo.original_order_date).toLocaleDateString()}</p>
                            <p><strong>Last Update:</strong> ${new Date(returnInfo.last_status_update).toLocaleDateString()}</p>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Return Reason:</h6>
                        <p class="border p-2 rounded bg-light">${returnInfo.reason}</p>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Returned Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Refund</th>
                                </tr>
                            </thead>
                            <tbody>`;

                    // Add rows for each item
                    items.forEach(item => {
                        htmlContent += `
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.product_sku}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td>₱${parseFloat(item.total_refund).toFixed(2)}</td>
                </tr>`;
                    });

                    htmlContent += `
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total Refund:</strong></td>
                                    <td><strong>₱${parseFloat(totals.total_refund).toFixed(2)}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Status History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Changed By</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>`;

                    // Add rows for status history
                    history.forEach(entry => {
                        htmlContent += `
                <tr>
                    <td>${new Date(entry.change_date).toLocaleString()}</td>
                    <td><span class="badge bg-${getStatusBadgeClass(entry.new_status)}">${entry.new_status}</span></td>
                    <td>${entry.changed_by_name || 'System'}</td>
                    <td>${entry.notes || '-'}</td>
                </tr>`;
                    });

                    htmlContent += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;

                    // Update modal with formatted HTML content
                    $('#orderDetailsContent').html(htmlContent);
                } catch (error) {
                    console.error('Error fetching return details:', error);
                    $('#orderDetailsContent').html(`
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Failed to load return details. Please try again later.
            </div>
        `);
                }
            });

            // Helper function to get appropriate badge class based on status
            function getStatusBadgeClass(status) {
                switch (status) {
                    case 'Pending':
                        return 'warning';
                    case 'Approved':
                        return 'success';
                    case 'Rejected':
                        return 'danger';
                    case 'Processed':
                        return 'primary';
                    default:
                        return 'secondary';
                }
            }

            // Cancel Return Handler
            $(document).on('click', '.cancel-return', function() {
                const returnId = $(this).data('return-id');

                // Show confirmation dialog
                if (confirm(`Are you sure you want to cancel return #${returnId}? This action cannot be undone.`)) {
                    // Show loading state on button
                    const $btn = $(this);
                    const originalText = $btn.html();
                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');

                    // Send cancel request
                    fetch('/pages/ajax/cancel_return.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                return_id: returnId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message using the modal
                                showCancelReturnStatus('success', 'Return request cancelled successfully.');

                                // Remove the return row or update status after a short delay
                                setTimeout(() => {
                                    location.reload(); // Reload to show updated status
                                }, 1500);
                            } else {
                                // Show error message
                                showCancelReturnStatus('error', data.message || 'Failed to cancel return request.');
                                $btn.prop('disabled', false).html(originalText);
                            }
                        })
                        .catch(error => {
                            console.error('Error cancelling return:', error);
                            showCancelReturnStatus('error', 'An error occurred. Please try again.');
                            $btn.prop('disabled', false).html(originalText);
                        });
                }
            });
        });
    </script>

</body>

</html>