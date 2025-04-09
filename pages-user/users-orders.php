<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../includes/session-init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];

$ordersStmt = $pdo->prepare("
    SELECT o.*, dm.method_name 
    FROM orders o
    JOIN delivery_methods dm ON o.delivery_method_id = dm.delivery_method_id
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC
");
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .order-card.Pending { border-left-color: #ffc107; }
        .order-card.Shipped { border-left-color: #0dcaf0; }
        .order-card.Delivered { border-left-color: #198754; }
        .order-card.Cancelled { border-left-color: #dc3545; }
        .order-card.Returned { border-left-color: #6c757d; }
        
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
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-6">My Orders</h1>
                <p class="text-muted">View your order history and track current orders</p>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <h4 class="alert-heading">No orders yet!</h4>
                <p>You haven't placed any orders with us yet. Start shopping to see your orders here.</p>
                <hr>
                <a href="../products.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col">
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): 
                                    $itemsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_details WHERE order_id = ?");
                                    $itemsStmt->execute([$order['order_id']]);
                                    $itemCount = $itemsStmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                                <tr class="order-card <?= htmlspecialchars($order['order_status']) ?>">
                                    <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                    <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                    <td><?= $itemCount ?></td>
                                    <td>₱<?= number_format($order['total_price'], 2) ?></td>
                                    <td>
                                        <span class="badge status-badge bg-<?=
                                            match($order['order_status']) {
                                                'Pending' => 'warning',
                                                'Shipped' => 'info',
                                                'Delivered' => 'success',
                                                'Cancelled' => 'danger',
                                                'Returned' => 'secondary',
                                                default => 'primary'
                                            }
                                        ?>">
                                            <?= ucfirst(htmlspecialchars($order['order_status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($order['method_name'] ?? 'Standard') ?></td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-outline-primary view-order-details" 
                                            data-order-id="<?= $order['order_id'] ?>">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
                data: { order_id: orderId },
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
</body>
</html>
