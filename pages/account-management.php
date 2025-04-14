<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

// Pagination
$usersPerPage = 15;
$currentPage = $_GET['page'] ?? 1;
$offset = ($currentPage - 1) * $usersPerPage;

// Filters
$filterRole = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base query
$query = "SELECT u.*, r.role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.role_id";

$where = [];
$params = [];

if ($filterRole !== 'all') {
    $where[] = "u.role_id = ?";
    $params[] = $filterRole;
}

if ($search) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Total count
$countQuery = "SELECT COUNT(*) FROM ($query) AS total";
$totalStmt = $pdo->prepare($countQuery);
$totalStmt->execute($params);
$totalUsers = $totalStmt->fetchColumn();

// Pagination and sorting
$query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$params[] = $usersPerPage;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get roles for filter
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Management - BunniShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <form method="GET" class="d-flex gap-2">
                                <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                                <select name="role" class="form-select">
                                    <option value="all" <?= $filterRole === 'all' ? 'selected' : '' ?>>All Roles</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['role_id'] ?>" <?= $filterRole == $role['role_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary">Filter</button>
                                <a href="user-management.php" class="btn btn-outline-secondary">Reset</a>
                            </form>

                            <a href="admin-notifications.php" class="btn btn-success">
                                <i class="bi bi-megaphone"></i> Send Notification
                            </a>

                        </div>



                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['user_id'] ?></td>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($user['role_name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-user"
                                                    data-user-id="<?= $user['user_id'] ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-sm <?= $user['is_active'] ? 'btn-danger' : 'btn-success' ?> toggle-status"
                                                    data-user-id="<?= $user['user_id'] ?>"
                                                    data-new-status="<?= $user['is_active'] ? 0 : 1 ?>">
                                                    <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
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
                                <?php for ($i = 1; $i <= ceil($totalUsers / $usersPerPage); $i++): ?>
                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&role=<?= $filterRole ?>&search=<?= $search ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['role_id'] ?>"><?= $role['role_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Automatic search implementation
            let searchTimeout;
            const searchInput = $('input[name="search"]');

            // Search debounce function
            searchInput.on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    $(this.form).submit();
                }, 300); // 300ms delay after typing stops
            });

            // Original code below remains the same
            // Edit User Modal
            $('.edit-user').click(function() {
                const userId = $(this).data('user-id');
                $.get('ajax/get_user.php', {
                        user_id: userId
                    })
                    .done(function(data) {
                        $('#editUserId').val(data.user_id);
                        $('input[name="name"]').val(data.name);
                        $('input[name="email"]').val(data.email);
                        $('select[name="role_id"]').val(data.role_id);
                    })
                    .fail(function() {
                        alert('Error loading user data');
                    });
            });

            // Save User Changes
            $('#editUserForm').submit(function(e) {
                e.preventDefault();
                $.post('ajax/update_user.php', $(this).serialize())
                    .done(function() {
                        location.reload();
                    })
                    .fail(function() {
                        alert('Error saving changes');
                    });
            });

            // Toggle User Status
            $('.toggle-status').click(function() {
                const userId = $(this).data('user-id');
                const newStatus = $(this).data('new-status');

                $.post('ajax/toggle_user_status.php', {
                    user_id: userId,
                    new_status: newStatus
                }).done(function() {
                    location.reload();
                }).fail(function() {
                    alert('Error updating status');
                });
            });
        });
    </script>
</body>

</html>