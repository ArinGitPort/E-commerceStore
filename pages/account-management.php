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
$filterMembership = $_GET['membership'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base query with membership data
$query = "SELECT u.*, r.role_name, m.membership_type_id, mt.type_name AS membership_type, m.expiry_date
          FROM users u 
          JOIN roles r ON u.role_id = r.role_id
          LEFT JOIN memberships m ON u.user_id = m.user_id
          LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id";

$where = [];
$params = [];

if (isset($_GET['clear_filters'])) {
    header("Location: account-management.php");
    exit;
}

if ($filterRole !== 'all') {
    $where[] = "u.role_id = ?";
    $params[] = $filterRole;
}

if ($filterMembership !== 'all') {
    $where[] = "m.membership_type_id = ?";
    $params[] = $filterMembership;
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
// Sorting
$allowedSortColumns = ['user_id', 'name', 'email', 'role_name', 'membership_type', 'expiry_date', 'created_at'];
$defaultSort  = 'user_id';
$defaultDir   = 'ASC';
$sort      = $_GET['sort']      ?? $defaultSort;
$direction = $_GET['direction'] ?? $defaultDir;


// Validate sort parameters
if (!in_array($sort, $allowedSortColumns)) {
    $sort = $defaultSort;
}
$direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

// Pagination and sorting
$query .= " ORDER BY $sort $direction LIMIT ? OFFSET ?";
$params[] = $usersPerPage;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get roles and membership types for filters
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
$membershipTypes = $pdo->query("SELECT * FROM membership_types")->fetchAll(PDO::FETCH_ASSOC);

function getMembershipStats($pdo)
{
    return [
        'active_members' => $pdo->query("SELECT COUNT(*) FROM memberships WHERE expiry_date > NOW()")->fetchColumn(),
        'expiring_soon' => $pdo->query("SELECT COUNT(*) FROM memberships WHERE expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
        'inactive_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn()
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Management - BunniShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/account-management.css">
    <style>
        .membership-badge {
            position: relative;
            cursor: pointer;
        }

        .membership-badge:hover .membership-tooltip {
            display: block;
        }

        .membership-tooltip {
            display: none;
            position: absolute;
            background: #fff;
            border: 1px solid #ddd;
            padding: 5px 10px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <p class="card-text display-6"><?= $totalUsers ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Active Members</h5>
                                <p class="card-text display-6"><?= getMembershipStats($pdo)['active_members'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Expiring Soon</h5>
                                <p class="card-text display-6"><?= getMembershipStats($pdo)['expiring_soon'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <h5 class="card-title">Inactive Users</h5>
                                <p class="card-text display-6"><?= getMembershipStats($pdo)['inactive_users'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <form method="GET" class="d-flex gap-2 flex-wrap" autocomplete="off">
                                <input type="text" name="search" class="form-control" placeholder="Search users..."
                                    value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
                                <select name="role" class="form-select" style="width: 180px;">
                                    <option value="all" <?= $filterRole === 'all' ? 'selected' : '' ?>>All Roles</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['role_id'] ?>" <?= $filterRole == $role['role_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="membership" class="form-select" style="width: 180px;">
                                    <option value="all" <?= $filterMembership === 'all' ? 'selected' : '' ?>>All Memberships</option>
                                    <?php foreach ($membershipTypes as $type): ?>
                                        <option value="<?= $type['membership_type_id'] ?>" <?= $filterMembership == $type['membership_type_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary">Filter</button>
                                <a href="?clear_filters=1" class="btn btn-outline-secondary">Reset</a>

                            </form>

                            <div class="d-flex gap-2">

                                <button id="btnAddStaff" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Add Staff
                                </button>

                                <a href="admin-notifications.php" class="btn btn-success">
                                    <i class="bi bi-megaphone"></i> Notify
                                </a>
                                <button class="btn btn-info" id="exportUsers">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <?php
                                        function getSortLink($column, $label)
                                        {
                                            global $sort, $direction, $filterRole, $filterMembership, $search, $currentPage;
                                            $newDirection = ($sort === $column && $direction === 'DESC') ? 'ASC' : 'DESC';
                                            $queryParams = [
                                                'sort' => $column,
                                                'direction' => ($sort === $column) ? $newDirection : 'DESC',
                                                'role' => $filterRole,
                                                'membership' => $filterMembership,
                                                'search' => $search,
                                                'page' => $currentPage
                                            ];
                                            $arrow = ($sort === $column)
                                                ? ($direction === 'ASC' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>')
                                                : '';
                                            return '<a href="?' . http_build_query($queryParams) . '" class="text-decoration-none text-dark">'
                                                . htmlspecialchars($label) . $arrow . '</a>';
                                        }
                                        ?>
                                        <th><?= getSortLink('user_id', 'ID') ?></th>
                                        <th><?= getSortLink('name', 'Name') ?></th>
                                        <th><?= getSortLink('email', 'Email') ?></th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th><?= getSortLink('role_name', 'Role') ?></th>
                                        <th><?= getSortLink('membership_type', 'Membership') ?></th>
                                        <th><?= getSortLink('expiry_date', 'Expiry') ?></th>
                                        <th><?= getSortLink('created_at', 'Registered') ?></th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($users as $user):
                                        $isFree = $user['membership_type_id'] == 1; // Check by ID instead of name
                                        $isExpired = false;
                                        $expiresSoon = false;
                                        $daysLeft = null;

                                        if (!$isFree && $user['expiry_date']) {
                                            $expiryDate = new DateTime($user['expiry_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($expiryDate);
                                            $daysLeft = $interval->days * ($interval->invert ? -1 : 1);
                                            $isExpired = $daysLeft < 0;
                                            $expiresSoon = !$isExpired && $daysLeft <= 7;
                                        }
                                    ?>
                                        <tr class="<?= $expiresSoon ? 'table-warning' : '' ?>">
                                            <td><?= $user['user_id'] ?></td>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['phone'] ?? '—') ?></td>
                                            <td><?= nl2br(htmlspecialchars($user['address'] ?? '—')) ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($user['role_name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $isFree = $user['membership_type'] === 'Free';
                                                $expiresSoon = false;
                                                $daysLeft = null;

                                                if (!$isFree && $user['expiry_date']) {
                                                    $expiryDate = new DateTime($user['expiry_date']);
                                                    $today = new DateTime();
                                                    $interval = $today->diff($expiryDate);
                                                    $daysLeft = $interval->days * ($interval->invert ? -1 : 1);

                                                    $isExpired = $daysLeft < 0;
                                                    $expiresSoon = !$isExpired && $daysLeft <= 7;
                                                } else {
                                                    $isExpired = false;
                                                }

                                                ?>

                                                <?php if ($user['membership_type'] || $isExpired): ?>
                                                    <div class="membership-badge position-relative">
                                                        <span class="badge <?= $isExpired ? 'bg-secondary' : ($expiresSoon ? 'bg-warning' : 'bg-primary') ?>">
                                                            <?= $isExpired ? 'Free (Expired)' : $user['membership_type'] ?>
                                                            <?php if ($expiresSoon): ?>
                                                                <i class="bi bi-exclamation-triangle-fill ms-1"></i>
                                                            <?php elseif ($isExpired): ?>
                                                                <i class="bi bi-arrow-counterclockwise ms-1"></i>
                                                            <?php endif; ?>
                                                        </span>
                                                        <div class="membership-tooltip">
                                                            <?php if ($isExpired): ?>
                                                                <div class="text-muted">
                                                                    Auto-downgraded on <?= date('M d, Y', strtotime($user['expiry_date'])) ?>
                                                                </div>
                                                            <?php elseif ($expiresSoon): ?>
                                                                <div class="text-warning fw-bold mb-1">
                                                                    ⚠️ Expires in <?= $daysLeft ?> days!
                                                                </div>
                                                                Expires: <?= date('M d, Y', strtotime($user['expiry_date'])) ?>
                                                            <?php else: ?>
                                                                <?= $user['expiry_date'] ? 'Expires: ' . date('M d, Y', strtotime($user['expiry_date'])) : 'Permanent' ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-primary edit-user"
                                                        data-user-id="<?= $user['user_id'] ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editUserModal">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning manage-membership"
                                                        data-user-id="<?= $user['user_id'] ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#membershipModal">
                                                        <i class="bi bi-credit-card"></i>
                                                    </button>
                                                    <button class="btn btn-sm <?= $user['is_active'] ? 'btn-danger' : 'btn-success' ?> toggle-status"
                                                        data-user-id="<?= $user['user_id'] ?>"
                                                        data-new-status="<?= $user['is_active'] ? 0 : 1 ?>">
                                                        <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Add Staff / Admin Registration Modal -->
                        <div class="modal fade" id="addStaffModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form id="addStaffForm">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Create Staff Account</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Name -->
                                            <div class="mb-3">
                                                <label class="form-label">Full Name<span class="text-danger">*</span></label>
                                                <input name="name" class="form-control" required>
                                            </div>

                                            <!-- Email -->
                                            <div class="mb-3">
                                                <label class="form-label">Email<span class="text-danger">*</span></label>
                                                <input name="email" type="email" class="form-control" required>
                                            </div>

                                            <!-- Password -->
                                            <!-- inside your Add Staff modal form -->
                                            <div class="mb-3">
                                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input
                                                        id="staffPassword"
                                                        name="password"
                                                        type="password"
                                                        class="form-control"
                                                        required>
                                                    <button
                                                        class="btn btn-outline-secondary toggle-password"
                                                        type="button"
                                                        tabindex="-1">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </div>


                                            <!-- Role -->
                                            <div class="mb-3">
                                                <label class="form-label">Role<span class="text-danger">*</span></label>
                                                <select name="role_id" class="form-select" required>
                                                    <?php foreach ($roles as $r): ?>
                                                        <?php if (in_array($r['role_name'], ['Staff', 'Admin', 'Super Admin'])): ?>
                                                            <option value="<?= $r['role_id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Optional: Phone -->
                                            <div class="mb-3">
                                                <label class="form-label">Contact Number</label>
                                                <input name="phone" type="text" class="form-control" placeholder="e.g. +63 912 345 6789">
                                            </div>

                                            <!-- Optional: Address -->
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea name="address" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Create Account</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>



                        <!-- Deactivation Modal -->
                        <div class="modal fade" id="deactivateModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirm Deactivation</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to deactivate this account? The user will lose access to the system.
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-danger" id="confirmDeactivate">Deactivate</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activation Modal -->
                        <div class="modal fade" id="activateModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirm Activation</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to activate this account? The user will regain access to the system.
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-success" id="confirmActivate">Activate</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= ceil($totalUsers / $usersPerPage); $i++): ?>
                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
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

    <!-- Modals -->
    <!-- Membership Management Modal -->
    <div class="modal fade" id="membershipModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Membership</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="membershipForm">
                    <div class="modal-body">
                        <input type="hidden" id="membershipUserId" name="user_id">
                        <div class="mb-3">
                            <label class="form-label">Membership Type</label>
                            <select class="form-select" name="membership_type_id" required>
                                <option value="">Select Membership Type</option>
                                <?php foreach ($membershipTypes as $type): ?>
                                    <option value="<?= $type['membership_type_id'] ?>">
                                        <?= htmlspecialchars($type['type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Membership</button>
                    </div>
                </form>
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
    <script>
        $(document).ready(function() {
            // Initialize modals once
            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            const membershipModal = new bootstrap.Modal(document.getElementById('membershipModal'));

            // Handle modal hidden events
            $('#editUserModal').on('hidden.bs.modal', function() {
                $('#editUserForm')[0].reset();
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            });

            $('#membershipModal').on('hidden.bs.modal', function() {
                $('#membershipForm')[0].reset();
                $('#membershipUserId').val('');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            });

            // Edit User Modal Handler
            $('.edit-user').click(function(e) {
                e.preventDefault();
                const userId = $(this).data('user-id');

                $.ajax({
                        url: 'ajax/get_user.php',
                        method: 'GET',
                        data: {
                            user_id: userId
                        },
                        dataType: 'json'
                    })
                    .done(function(response) {
                        if (response.success) {
                            $('#editUserId').val(response.data.user_id);
                            $('input[name="name"]').val(response.data.name);
                            $('input[name="email"]').val(response.data.email);
                            $('select[name="role_id"]').val(response.data.role_id);
                            editModal.show();
                        } else {
                            showAlert('Error: ' + (response.error || 'Unknown error'), 'danger');
                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        showAlert('Failed to load user data', 'danger');
                    });
            });

            // Save User Changes
            $('#editUserForm').submit(function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalHtml = submitBtn.html();

                submitBtn.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm" role="status"></span> Saving...
            `);

                $.post('ajax/update_user.php', form.serialize())
                    .done(function(response) {
                        if (response.success) {
                            submitBtn.html(`
                            <i class="bi bi-check-circle"></i> Saved!
                        `).removeClass('btn-primary').addClass('btn-success');

                            setTimeout(() => {
                                editModal.hide();
                                location.reload();
                            }, 1500);
                        } else {
                            submitBtn.html(originalHtml).prop('disabled', false);
                            showAlert('Error: ' + (response.error || 'Unknown error'), 'danger');
                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Update Error:', status, error);
                        submitBtn.html(originalHtml).prop('disabled', false);
                        showAlert('Failed to save changes', 'danger');
                    });
            });

            // Membership Management
            $('.manage-membership').click(function() {
                const userId = $(this).data('user-id');

                // Reset form and set user ID
                $('#membershipForm')[0].reset();
                $('#membershipUserId').val(userId);

                $.ajax({
                        url: 'ajax/get_membership.php',
                        method: 'GET',
                        data: {
                            user_id: userId
                        },
                        dataType: 'json'
                    })
                    .done(function(response) {
                        if (response.success) {
                            const isFree = response.data?.membership_type_id == 1;

                            // Set membership type (default to Free if no data)
                            $('select[name="membership_type_id"]').val(
                                response.data?.membership_type_id || 1
                            );

                            // Handle date fields
                            if (response.data && !isFree) {
                                $('input[name="start_date"]').val(response.data.start_date.split('T')[0]);
                                $('input[name="expiry_date"]').val(response.data.expiry_date.split('T')[0]);
                            } else {
                                // Clear dates for Free membership
                                $('input[name="start_date"], input[name="expiry_date"]').val('');
                            }

                            // Disable date fields for Free membership
                            $('input[name="start_date"], input[name="expiry_date"]').prop('disabled', isFree);

                            // Set up membership type change handler
                            $('select[name="membership_type_id"]').off('change').on('change', function() {
                                const isNowFree = $(this).val() == 1;
                                $('input[name="start_date"], input[name="expiry_date"]')
                                    .prop('disabled', isNowFree)
                                    .val(isNowFree ? '' : new Date().toISOString().split('T')[0]);

                                if (!isNowFree) {
                                    const inThirty = new Date();
                                    inThirty.setDate(inThirty.getDate() + 30);
                                    $('input[name="expiry_date"]').val(
                                        inThirty.toISOString().split('T')[0]
                                    );
                                }

                            });

                            membershipModal.show();
                        }
                    })
                    .fail(function(error) {
                        console.error('Error:', error);
                        showAlert('Failed to load membership data', 'danger');
                    });
            });

            // Save Membership
            $('#membershipForm').submit(function(e) {
                e.preventDefault();
                const form = $(this);
                const userId = $('#membershipUserId').val();
                const membershipTypeId = $('select[name="membership_type_id"]').val();

                // Get the user's current role before making changes
                $.ajax({
                        url: 'ajax/get_user.php',
                        method: 'GET',
                        data: {
                            user_id: userId
                        },
                        dataType: 'json',
                        async: false // Make this synchronous to get role before proceeding
                    })
                    .done(function(response) {
                        if (response.success) {
                            const userRoleId = parseInt(response.data.role_id);

                            // If membership is not free (id != 1) and user has a role higher than Member (2)
                            // then we need to preserve their role instead of auto-changing to Member
                            if (membershipTypeId != 1 && userRoleId > 2) {
                                // Add a hidden field to the form to indicate we should preserve the role
                                form.append('<input type="hidden" name="preserve_role" value="1">');
                            }

                            // Now proceed with the regular form submission
                            submitMembershipForm(form);
                        } else {
                            showAlert('Error: ' + (response.error || 'Unknown error'), 'danger');
                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        showAlert('Failed to load user data', 'danger');
                    });
            });

            // Extract the existing submission logic to a separate function
            function submitMembershipForm(form) {
                const submitBtn = form.find('button[type="submit"]');
                const originalHtml = submitBtn.html();

                submitBtn.prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm" role="status"></span> Saving...
    `);

                $.post('ajax/update_membership.php', form.serialize())
                    .done(function(response) {
                        if (response.success) {
                            submitBtn.html(`
                    <i class="bi bi-check-circle"></i> Saved!
                `).removeClass('btn-primary').addClass('btn-success');

                            setTimeout(() => {
                                membershipModal.hide();
                                location.reload();
                            }, 1500);
                        } else {
                            submitBtn.html(originalHtml).prop('disabled', false);
                            showAlert('Error: ' + (response.error || 'Unknown error'), 'danger');
                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Error:', error);
                        submitBtn.html(originalHtml).prop('disabled', false);
                        showAlert('Error saving membership', 'danger');
                    });
            }

            // Toggle User Status
            $('.toggle-status').click(function() {
                const btn = $(this);
                const userId = btn.data('user-id');
                const newStatus = btn.data('new-status');
                const originalHtml = btn.html();

                if (newStatus === 0) {
                    $('#deactivateModal').modal('show');
                    $('#confirmDeactivate').off('click').on('click', function() {
                        $('#deactivateModal').modal('hide');
                        performStatusUpdate(userId, newStatus, btn, originalHtml);
                    });
                } else {
                    $('#activateModal').modal('show');
                    $('#confirmActivate').off('click').on('click', function() {
                        $('#activateModal').modal('hide');
                        performStatusUpdate(userId, newStatus, btn, originalHtml);
                    });
                }
            });

            function performStatusUpdate(userId, newStatus, btn, originalHtml) {
                btn.prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm" role="status"></span>
    `);

                $.ajax({
                        url: 'ajax/toggle_user_status.php',
                        method: 'POST',
                        data: {
                            user_id: userId,
                            new_status: newStatus
                        },
                        dataType: 'json'
                    })
                    .done(function(response) {
                        if (response.success) {
                            showAlert('Status updated successfully', 'success');
                            // Refresh page after 1 second to show updated status
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showAlert('Error: ' + (response.error || 'Failed to update status'), 'danger');
                            btn.html(originalHtml).data('new-status', newStatus);
                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Toggle Status Error:', status, error);
                        showAlert('Error: Failed to update status', 'danger');
                        btn.html(originalHtml).data('new-status', newStatus);
                    })
                    .always(() => btn.prop('disabled', false));
            }

            // Alert system
            function showAlert(message, type = 'success') {
                const alert = $(`
                <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);

                $('body').append(alert);
                setTimeout(() => alert.alert('close'), 3000);
            }

            // Export Users
            $('#exportUsers').click(() => window.location = 'ajax/export_users.php?' + $('form').serialize());
        });

        const addStaffModal = new bootstrap.Modal($('#addStaffModal')[0]);

        // Open the modal
        $('#btnAddStaff').click(() => {
            $('#addStaffForm')[0].reset();
            addStaffModal.show();
        });

        // Handle form submission
        $('#addStaffForm').submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            const origText = btn.html();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Creating…');

            $.post('ajax/create_user.php', form.serialize(), function(resp) {
                if (resp.success) {
                    addStaffModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + resp.error);
                    btn.prop('disabled', false).html(origText);
                }
            }, 'json').fail(() => {
                alert('Server error');
                btn.prop('disabled', false).html(origText);
            });
        });

        // toggle show/hide password
        $('#addStaffModal').on('click', '.toggle-password', function() {
            const $btn = $(this);
            const $input = $btn.siblings('input[name="password"]');
            const showing = $input.attr('type') === 'password';
            $input.attr('type', showing ? 'text' : 'password');
            $btn.find('i').toggleClass('bi-eye bi-eye-slash');
        });

        const currentUserRole = <?= json_encode($_SESSION['role_id'] ?? 0) ?>;

        // Only show Add Staff button for Admin and Super Admin (role_id 4 and 5)
        if (currentUserRole < 4) {
            $('#btnAddStaff').hide();
        }



        // Restrict role selection in edit form
        $('select[name="role_id"]').on('change', function() {
            const selectedRole = parseInt($(this).val());

            // If current user is Admin (role_id 4), they can't set roles equal or higher than Admin
            if (currentUserRole === 4 && selectedRole >= 4) {
                alert("You cannot assign Admin or higher roles.");
                // Reset to Staff role (role_id 3)
                $(this).val(3);
            }
        });

        // Apply same restriction when populating the edit modal
        $(document).on('show.bs.modal', '#editUserModal', function() {
            if (currentUserRole === 4) {
                // Admin can only assign roles up to Staff (role_id 3)
                $('select[name="role_id"] option').each(function() {
                    if (parseInt($(this).val()) >= 4) {
                        $(this).prop('disabled', true);
                    }
                });
            }
        });

        $('.table tbody tr').each(function() {
            const row = $(this);
            const editButton = row.find('.edit-user');
            const toggleStatusButton = row.find('.toggle-status');

            // Get user's role from the badge text in this row
            const userRoleBadge = row.find('td:nth-child(6) .badge');
            const userRole = userRoleBadge.text().trim();

            // Role hierarchy check
            if (currentUserRole === 4) { // Admin
                // Admin can't edit or deactivate Super Admin accounts
                if (userRole === 'Super Admin') {
                    editButton.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
                    toggleStatusButton.prop('disabled', true).addClass('btn-secondary').removeClass('btn-danger btn-success');
                }

                // Admin can't deactivate other Admin accounts (but can edit)
                if (userRole === 'Admin' && toggleStatusButton.text().trim() === 'Deactivate') {
                    toggleStatusButton.prop('disabled', true).addClass('btn-secondary').removeClass('btn-danger');
                }
            } else if (currentUserRole === 3) { // Staff
                // Staff can only edit/deactivate customers and members
                if (!['Customer', 'Member'].includes(userRole)) {
                    editButton.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
                    toggleStatusButton.prop('disabled', true).addClass('btn-secondary').removeClass('btn-danger btn-success');
                }
            }
        });

        // Restrict role selection in edit form
        $('select[name="role_id"]').on('change', function() {
            const selectedRole = parseInt($(this).val());

            // If current user is Admin (role_id 4), they can't set roles equal or higher than Admin
            if (currentUserRole === 4 && selectedRole >= 4) {
                alert("You cannot assign Admin or higher roles.");
                // Reset to Staff role (role_id 3)
                $(this).val(3);
            }

            // If current user is Staff (role_id 3), they can't set roles higher than Member (role_id 2)
            if (currentUserRole === 3 && selectedRole > 2) {
                alert("Staff cannot assign Staff or higher roles.");
                // Reset to Member role (role_id 2)
                $(this).val(2);
            }
        });

        // Apply same restriction when opening the edit modal
        $(document).on('show.bs.modal', '#editUserModal', function() {
            if (currentUserRole === 4) {
                // Admin can only assign roles up to Staff (role_id 3)
                $('select[name="role_id"] option').each(function() {
                    if (parseInt($(this).val()) >= 4) {
                        $(this).prop('disabled', true);
                    }
                });
            } else if (currentUserRole === 3) {
                // Staff can only assign roles up to Member (role_id 2)
                $('select[name="role_id"] option').each(function() {
                    if (parseInt($(this).val()) > 2) {
                        $(this).prop('disabled', true);
                    }
                });
            }
        });
    </script>
</body>

</html>