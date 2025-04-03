<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../../config/db_connection.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Pagination
$rowsPerPage = isset($_GET['rows']) ? (int)$_GET['rows'] : 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $rowsPerPage;

// Filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "SELECT SQL_CALC_FOUND_ROWS o.*, u.name as customer_name, 
          d.method_name as delivery_method, p.method_name as payment_method
          FROM orders o
          JOIN users u ON o.customer_id = u.user_id
          JOIN delivery_methods d ON o.delivery_method_id = d.delivery_method_id
          JOIN payments py ON o.order_id = py.order_id
          JOIN payment_methods p ON py.payment_method_id = p.payment_method_id
          WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $query .= " AND o.order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchTerm)) {
    $query .= " AND (o.order_id = ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = $searchTerm;
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$query .= " ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
$params[] = $rowsPerPage;
$params[] = $offset;

// Get orders
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$totalRows = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($totalRows / $rowsPerPage);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->execute([$newStatus, $orderId]);

        // Update payment status if order is completed
        if ($newStatus === 'Delivered') {
            $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'Paid' WHERE order_id = ?");
            $stmt->execute([$orderId]);
        }

        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            "Updated order #$orderId status to $newStatus",
            'orders',
            $orderId
        ]);

        $_SESSION['message'] = "Order status updated successfully!";
        header("Location: orders.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
        header("Location: orders.php");
        exit;
    }
}

// Display messages
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Management - BunniShop</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../../assets/images/iconlogo/bunniwinkleIcon.ico">
</head>

<body>
    <?php include '../includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h2>Order Management</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <div class="filters mb-4">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Shipped" <?= $statusFilter === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search by order ID or customer" value="<?= htmlspecialchars($searchTerm) ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Delivery</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_details WHERE order_id = ?");
                                            $stmt->execute([$order['order_id']]);
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </td>
                                        <td>₱<?= number_format($order['total_price'], 2) ?></td>
                                        <td>
                                            <span class="badge 
                                                <?= $order['order_status'] === 'Pending' ? 'bg-warning' : ($order['order_status'] === 'Shipped' ? 'bg-info' : ($order['order_status'] === 'Delivered' ? 'bg-success' : 'bg-danger')) ?>">
                                                <?= $order['order_status'] ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                                        <td><?= htmlspecialchars($order['delivery_method']) ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="order_details.php?id=<?= $order['order_id'] ?>">
                                                            <i class="fas fa-eye me-2"></i> View Details
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal"
                                                            data-bs-target="#statusModal<?= $order['order_id'] ?>">
                                                            <i class="fas fa-pencil-alt me-2"></i> Update Status
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="invoice.php?id=<?= $order['order_id'] ?>" target="_blank">
                                                            <i class="fas fa-file-invoice me-2"></i> Generate Invoice
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Status Update Modal -->
                                            <div class="modal fade" id="statusModal<?= $order['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="post">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Order Status</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Current Status</label>
                                                                    <input type="text" class="form-control" value="<?= $order['order_status'] ?>" readonly>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">New Status</label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="Pending" <?= $order['order_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                        <option value="Shipped" <?= $order['order_status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                                        <option value="Delivered" <?= $order['order_status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                                        <option value="Cancelled" <?= $order['order_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="pagination-info">
                                Showing <?= count($orders) ?> of <?= $totalRows ?> orders
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav class="float-end">
                                <ul class="pagination">
                                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>&rows=<?= $rowsPerPage ?>&status=<?= $statusFilter ?>&search=<?= urlencode($searchTerm) ?>">Previous</a>
                                    </li>

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&rows=<?= $rowsPerPage ?>&status=<?= $statusFilter ?>&search=<?= urlencode($searchTerm) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>&rows=<?= $rowsPerPage ?>&status=<?= $statusFilter ?>&search=<?= urlencode($searchTerm) ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>