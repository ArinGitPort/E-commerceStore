<?php
// pages/subscription-management.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Pagination
$page    = isset($_GET['page'])   ? (int)$_GET['page']   : 1;
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Fetch membership tiers + counts
$memberships = $pdo->query("
    SELECT mt.*, COUNT(m.user_id) AS member_count
      FROM membership_types mt
 LEFT JOIN memberships m ON mt.membership_type_id = m.membership_type_id
  GROUP BY mt.membership_type_id
  ORDER BY mt.price ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch audit log with search + pagination
$search    = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';
$auditStmt = $pdo->prepare("
    SELECT SQL_CALC_FOUND_ROWS sa.*, u.name, u.email, mt.type_name
      FROM subscriptions_audit sa
      JOIN users u  ON sa.user_id = u.user_id
      JOIN membership_types mt ON sa.membership_type_id = mt.membership_type_id
     WHERE u.name       LIKE ?
        OR u.email      LIKE ?
        OR mt.type_name LIKE ?
     ORDER BY sa.payment_date DESC
     LIMIT ? OFFSET ?
");
$auditStmt->execute([$search, $search, $search, $perPage, $offset]);
$subscriptionAudits = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

$totalResults = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages   = (int)ceil($totalResults / $perPage);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard – Bunniwinkle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 2rem;
            box-sizing: border-box;
            min-height: 100vh;
            color: #212529;
        }

        .membership-card {
            border: 1px solid rgba(13, 110, 253, .15);
            transition: transform .2s, box-shadow .2s;
        }

        .membership-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1);
        }

        .status-badge {
            position: absolute;
            top: -10px;
            right: -10px;
        }
    </style>
</head>

<body>

    <!-- sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 text-primary">
                <i class="fas fa-users-cog me-2"></i>Membership Management
            </h1>
            <div class="dropdown">
                <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    Quick Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#newTierModal" data-bs-toggle="modal">
                            <i class="fas fa-plus-circle me-2"></i>New Tier
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#exportModal" data-bs-toggle="modal">
                            <i class="fas fa-file-export me-2"></i>Export CSV
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#membershipTiers">
                    <i class="fas fa-layer-group me-2"></i>Tiers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#auditLog">
                    <i class="fas fa-clipboard-list me-2"></i>Audit Log
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tiers -->
            <div class="tab-pane fade show active" id="membershipTiers">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($memberships as $tier): ?>
                        <div class="col">
                            <div class="card membership-card h-100 position-relative">
                                <div class="card-header bg-primary text-white d-flex justify-content-between">
                                    <h5 class="mb-0"><?= htmlspecialchars($tier['type_name']) ?></h5>
                                    <div class="dropdown">
                                        <button class="btn btn-link text-white p-0" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item edit-tier" data-id="<?= $tier['membership_type_id'] ?>">
                                                    <i class="fas fa-edit me-2"></i>Edit
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <span class="status-badge badge bg-white text-primary shadow-sm">
                                    <?= $tier['member_count'] ?> members
                                </span>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div class="h4 mb-0">₱<?= number_format($tier['price'], 2) ?></div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            <?= $tier['can_access_exclusive'] ? 'Exclusive' : 'Basic' ?>
                                        </span>
                                    </div>
                                    <div class="tier-description mb-3"><?= htmlspecialchars_decode($tier['description'] ?? '') ?></div>
                                    <div class="text-end small text-muted">
                                        Updated <?= date('M j, Y', strtotime($tier['modified_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Audit Log -->
            <div class="tab-pane fade" id="auditLog">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-4">
                            <form class="w-50" method="GET">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search…"
                                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="fas fa-file-export me-2"></i>Export CSV
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Tier</th>
                                        <th>Amount</th>
                                        <th>Txn ID</th>
                                        <th>Info</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptionAudits as $audit): ?>
                                        <tr>
                                            <td><?= date('M j, Y H:i', strtotime($audit['payment_date'])) ?></td>
                                            <td>
                                                <?= htmlspecialchars($audit['name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($audit['email']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($audit['type_name']) ?></td>
                                            <td class="fw-bold">₱<?= number_format($audit['payment_amount'], 2) ?></td>
                                            <td><code><?= htmlspecialchars($audit['transaction_id']) ?></code></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#transactionDetailsModal"
                                                    data-details="<?= htmlspecialchars(json_encode($audit)) ?>">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($_GET['search'] ?? '') ?>">
                                            <?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- modals -->
    <!-- Export CSV Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export Subscription Audit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Click “Download CSV” below to export the current subscription audit list.</p>
                </div>
                <div class="modal-footer">
                    <form method="GET" action="/pages/ajax/export_subscription_audit.php">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-csv me-1"></i> Download CSV
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- New Tier Modal -->
    <div class="modal fade" id="newTierModal" tabindex="-1" aria-labelledby="newTierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="newTierForm" action="/pages/ajax/create_membership_tier.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newTierModalLabel">Create New Tier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newTierName" class="form-label">Tier Name</label>
                        <input type="text" class="form-control" name="type_name" id="newTierName" required>
                    </div>
                    <div class="mb-3">
                        <label for="newTierPrice" class="form-label">Price (₱)</label>
                        <input type="number" step="0.01" class="form-control" name="price" id="newTierPrice" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="can_access_exclusive" id="newTierExclusive">
                        <label class="form-check-label" for="newTierExclusive">Can Access Exclusive</label>
                    </div>
                    <div class="mb-3">
                        <label for="newMembershipDescEditor" class="form-label">Description</label>
                        <textarea id="newMembershipDescEditor" name="description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create Tier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Tier Modal -->
    <div class="modal fade" id="editTierModal" tabindex="-1" aria-labelledby="editTierModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editTierForm" action="/pages/ajax/update_membership_tier.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTierModalLabel">Edit Membership Tier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="membership_type_id" id="editTierId">
                    <div class="mb-3">
                        <label for="editTierName" class="form-label">Tier Name</label>
                        <input type="text" class="form-control" name="type_name" id="editTierName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTierPrice" class="form-label">Price (₱)</label>
                        <input type="number" step="0.01" class="form-control" name="price" id="editTierPrice" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="can_access_exclusive" id="editTierExclusive">
                        <label class="form-check-label" for="editTierExclusive">Can Access Exclusive</label>
                    </div>
                    <div class="mb-3">
                        <label for="membershipDescEditor" class="form-label">Description</label>
                        <textarea id="membershipDescEditor" name="description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionDetailsModal" tabindex="-1" aria-labelledby="transactionDetailsLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transactionDetailsLabel">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Transaction ID:</strong> <span class="transaction-id"></span></p>
                    <p><strong>Amount:</strong> <span class="transaction-amount"></span></p>
                    <p><strong>Date:</strong> <span class="transaction-date"></span></p>
                    <p><strong>User:</strong><br>
                        <span class="user-details"></span>
                    </p>
                    <p><strong>Tier:</strong> <span class="tier-details"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        // Initialize both editors
        $('#membershipDescEditor, #newMembershipDescEditor').summernote({
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol']],
                ['view', ['codeview']]
            ],
            height: 200 // Set same height for both
        });
        $('#newMembershipDescEditor').summernote({
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol']],
                ['view', ['codeview']]
            ],
            height: 200
        });

        // Reset new tier form when modal closes
        $('#newTierModal').on('hidden.bs.modal', function() {
            $('#newTierForm')[0].reset();
            $('#newMembershipDescEditor').summernote('code', '');
        });

        // EDIT TIER button → fetch & show
        document.querySelectorAll('.edit-tier').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    const response = await fetch(`ajax/get_membership_tier.php?id=${btn.dataset.id}`);
                    const text = await response.text();

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}\nResponse: ${text}`);
                    }

                    const tier = JSON.parse(text);

                    document.getElementById('editTierId').value = tier.membership_type_id;
                    document.getElementById('editTierName').value = tier.type_name;
                    document.getElementById('editTierPrice').value = tier.price;
                    document.getElementById('editTierExclusive').checked = tier.can_access_exclusive;
                    $('#membershipDescEditor').summernote('code', tier.description || '');
                    new bootstrap.Modal(document.getElementById('editTierModal')).show();
                } catch (err) {
                    console.error('Error loading tier:', err);
                    alert(`Error loading tier data: ${err.message}`);
                }
            });
        });

        // TRANSACTION DETAILS modal population
        document.getElementById('transactionDetailsModal')
            .addEventListener('show.bs.modal', e => {
                const data = JSON.parse(e.relatedTarget.dataset.details);
                e.target.querySelector('.transaction-id').textContent = data.transaction_id;
                e.target.querySelector('.transaction-amount').textContent = `₱${parseFloat(data.payment_amount).toFixed(2)}`;
                e.target.querySelector('.transaction-date').textContent = new Date(data.payment_date).toLocaleString();
                e.target.querySelector('.user-details').innerHTML = `<div>${data.name}</div><small>${data.email}</small>`;
                e.target.querySelector('.tier-details').textContent = data.type_name;
            });
    </script>
</body>

</html>