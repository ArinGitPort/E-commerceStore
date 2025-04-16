<?php
// Filepath: /pages/admin-notifications.php

require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$notifications = [];
$totalNotifications = 0;
$totalPages = 1;
$templates = [];
$membershipTypes = [];
$success = '';
$error = '';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 15;
$offset = ($page - 1) * $limit;

try {
    // Fetch notification templates
    $templates = $pdo->query("SELECT * FROM notification_templates ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $membershipTypes = $pdo->query("SELECT * FROM membership_types ORDER BY type_name")->fetchAll(PDO::FETCH_ASSOC);

    // Main notifications query
    $baseQuery = "
        SELECT 
            n.notification_id,
            n.title,
            n.message,
            n.start_date,
            n.expiry_date,
            n.created_at,
            n.created_by,
            GROUP_CONCAT(DISTINCT mt.type_name ORDER BY mt.type_name) AS target_groups
        FROM notifications n
        LEFT JOIN notification_membership_targets nt ON n.notification_id = nt.notification_id
        LEFT JOIN membership_types mt ON nt.membership_type_id = mt.membership_type_id
    ";

    // Initialize search conditions
    $searchConditions = [];
    $searchParams = [];
    
    if (!empty($search)) {
        // Sanitize and prepare search term
        $searchTerm = trim($search);
        $searchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm); // Escape wildcards
        $searchTerm = "%$searchTerm%"; // Add wildcards for partial matching
        
        // Search across multiple fields
        $searchConditions[] = "(n.title LIKE :search OR n.message LIKE :search)";
        $searchParams[':search'] = $searchTerm;
        
        // Optional: Add search by target groups if needed
        // $searchConditions[] = "mt.type_name LIKE :search";
    }

    // Build final query
    $query = $baseQuery;
    if (!empty($searchConditions)) {
        $query .= " WHERE " . implode(' OR ', $searchConditions);
    }
    $query .= " GROUP BY n.notification_id ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";

    // Prepare and execute main query
    $stmt = $pdo->prepare($query);
    
    // Bind search parameters if they exist
    foreach ($searchParams as $param => $value) {
        $stmt->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count query for pagination
    $countQuery = "SELECT COUNT(DISTINCT n.notification_id) FROM notifications n";
    if (!empty($searchConditions)) {
        $countQuery .= " WHERE " . implode(' OR ', $searchConditions);
    }
    
    $countStmt = $pdo->prepare($countQuery);
    foreach ($searchParams as $param => $value) {
        $countStmt->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    $countStmt->execute();
    $totalNotifications = $countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalNotifications / $limit));

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    error_log("Query: " . $query ?? '');
    error_log("Params: " . json_encode($searchParams));
    $error = "Failed to load notifications. Please try again.";
    
    // Fallback to non-searched results if search fails
    try {
        $notifications = $pdo->query("
            SELECT n.*, GROUP_CONCAT(DISTINCT mt.type_name) AS target_groups
            FROM notifications n
            LEFT JOIN notification_membership_targets nt ON n.notification_id = nt.notification_id
            LEFT JOIN membership_types mt ON nt.membership_type_id = mt.membership_type_id
            GROUP BY n.notification_id
            ORDER BY n.created_at DESC
            LIMIT $limit OFFSET $offset
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $totalNotifications = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
        $totalPages = max(1, ceil($totalNotifications / $limit));
    } catch (PDOException $e) {
        error_log("Fallback query also failed: " . $e->getMessage());
    }
}

// Flash messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Notifications</title>
  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="../assets/css/admin-notifications.css">
  <style>
    .notification-row:hover {
      background-color: #f8f9fa;
      cursor: pointer;
    }

    .status-active {
      color: #198754;
    }

    .status-expired {
      color: #dc3545;
    }

    .status-pending {
      color: #fd7e14;
    }

    .badge-target {
      background-color: #6c757d;
    }

    .action-btn {
      width: 32px;
      height: 32px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    #notificationTable th {
      white-space: nowrap;
    }

    .template-item {
      border-left: 3px solid #0d6efd;
      padding-left: 10px;
      margin-bottom: 10px;
    }

    .template-item:hover {
      background-color: #f8f9fa;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <!-- Include Sidebar -->
  <?php include '../includes/sidebar.php'; ?>

  <div class="main-content container-fluid py-4">
    <!-- Header Row -->
    <div class="row mb-4">
      <div class="col">
        <div class="d-flex justify-content-between align-items-center">
          <h2><i class="bi bi-megaphone"></i> Notifications Management</h2>
          <!-- Button triggers the New Notification modal -->
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newNotificationModal">
            <i class="bi bi-plus-lg"></i> New Notification
          </button>
        </div>
      </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Notifications List -->
    <div class="row mb-4">
      <div class="col">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Notifications</h5>
            <form class="d-flex" method="get" action="">
              <div class="input-group">
                <input type="text" class="form-control" name="search"
                  placeholder="Search notifications..."
                  value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-secondary" type="submit">
                  <i class="bi bi-search"></i>
                </button>
                <?php if (!empty($search)): ?>
                  <a href="admin-notifications.php" class="btn btn-outline-danger">
                    <i class="bi bi-x-lg"></i>
                  </a>
                <?php endif; ?>
              </div>
            </form>
          </div>


          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0" id="notificationTable">
                <thead class="table-light">
                  <tr>
                    <th width="5%">ID</th>
                    <th width="20%">Title</th>
                    <th width="30%">Message Preview</th>
                    <th width="10%">Target</th>
                    <th width="10%">Status</th>
                    <th width="15%">Dates</th>
                    <th width="10%">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                  foreach ($notifications as $notification):
                    $status = 'Active';
                    $statusClass = 'status-active';

                    $startDate = $notification['start_date']
                      ? new DateTime($notification['start_date'], new DateTimeZone('Asia/Manila'))
                      : null;
                    $expiryDate = $notification['expiry_date']
                      ? new DateTime($notification['expiry_date'], new DateTimeZone('Asia/Manila'))
                      : null;

                    if ($startDate && $now < $startDate) {
                      $status      = 'Pending';
                      $statusClass = 'status-pending';
                    } elseif ($expiryDate && $now > $expiryDate) {
                      $status      = 'Expired';
                      $statusClass = 'status-expired';
                    }
                  ?>
                    <tr class="notification-row">
                      <td><?= $notification['notification_id'] ?></td>
                      <td><?= htmlspecialchars($notification['title'], ENT_QUOTES) ?></td>
                      <td>
                        <div class="text-truncate" style="max-width: 300px;">
                          <?= htmlspecialchars($notification['message']) ?>
                        </div>
                      </td>
                      <td>
                        <?php
                        // If 'target_groups' is empty, it implies no membership targets → "All Members"
                        if (!empty($notification['target_groups'])):
                          // e.g. "Free, Premium, VIP"
                          $groups = explode(',', $notification['target_groups']);
                          foreach ($groups as $grp) {
                            echo '<span class="badge badge-target me-1">' . htmlspecialchars($grp) . '</span>';
                          }
                        else:
                        ?>
                          <span class="badge bg-primary">All Members</span>
                        <?php endif; ?>
                      </td>
                      <td><span class="<?= $statusClass ?>"><?= $status ?></span></td>
                      <td>
                        <small class="text-muted">
                          <?php if ($startDate): ?>
                            <?= $startDate->format('M j, Y') ?>
                          <?php else: ?>
                            Immediate
                          <?php endif; ?>
                          -
                          <?php if ($expiryDate): ?>
                            <?= $expiryDate->format('M j, Y') ?>
                          <?php else: ?>
                            No expiry
                          <?php endif; ?>
                        </small>
                      </td>
                      <td>
                        <div class="d-flex gap-2">
                          <!-- Edit button triggers modal -->
                          <button class="btn btn-sm btn-outline-primary action-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editNotificationModal"
                            data-id="<?= $notification['notification_id'] ?>"
                            data-title="<?= htmlspecialchars($notification['title']) ?>"
                            data-message="<?= htmlspecialchars($notification['message']) ?>"
                            data-start-date="<?= $notification['start_date'] ?>"
                            data-expiry-date="<?= $notification['expiry_date'] ?>">
                            <i class="bi bi-pencil"></i>
                          </button>

                          <!-- Delete form -->
                          <form method="post" action="admin-notifications-handler.php">
                            <input type="hidden" name="action" value="delete_notification">
                            <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                            <button type="submit"
                              class="btn btn-sm btn-outline-danger action-btn"
                              onclick="return confirm('Are you sure you want to delete this notification?')">
                              <i class="bi bi-trash"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Fallback if no notifications -->
            <?php if (empty($notifications)): ?>
              <div class="text-center py-4">
                <i class="bi bi-megaphone" style="font-size: 3rem; opacity: 0.2;"></i>
                <p class="text-muted mt-2">No notifications found</p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Pagination -->
          <div class="card-footer">
            <nav aria-label="Notifications pagination">
              <ul class="pagination justify-content-center mb-0">
                <?php if ($page > 1): ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                  </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div><!-- /row -->

    <!-- Templates & Stats Row -->
    <div class="row">
      <!-- Quick Templates -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Quick Templates</h5>
            <!-- This button triggers the "New Template" modal -->
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newTemplateModal">
              <i class="bi bi-plus-lg"></i> New Template
            </button>
          </div>
          <div class="card-body">
            <?php if (empty($templates)): ?>
              <div class="text-center py-4">
                <i class="bi bi-file-earmark-text" style="font-size: 3rem; opacity: 0.2;"></i>
                <p class="text-muted mt-2">No templates available</p>
              </div>
            <?php else: ?>
              <div class="list-group list-group-flush">
                <?php foreach ($templates as $template): ?>
                  <div class="template-item"
                    onclick="loadTemplate(<?= htmlspecialchars(json_encode($template)) ?>)">
                    <div class="d-flex justify-content-between">
                      <strong><?= htmlspecialchars($template['title']) ?></strong>
                      <small class="text-muted">
                        <?= date('M j, Y', strtotime($template['created_at'])) ?>
                      </small>
                    </div>
                    <p class="small text-muted mb-0 text-truncate">
                      <?= htmlspecialchars($template['message']) ?>
                    </p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Notification Stats -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Notification Stats</h5>
          </div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-4">
                <div class="p-3 border rounded bg-light">
                  <h3 class="mb-0"><?= $totalNotifications ?></h3>
                  <small class="text-muted">Total</small>
                </div>
              </div>
              <div class="col-4">
                <div class="p-3 border rounded bg-light">
                  <h3 class="mb-0">
                    <?php
                    // Count "Pending": notifications with start_date > NOW()
                    $pendingCount = $pdo->query("
                        SELECT COUNT(*)
                        FROM notifications
                        WHERE start_date IS NOT NULL AND start_date > NOW()
                      ")->fetchColumn();
                    echo (int)$pendingCount;
                    ?>
                  </h3>
                  <small class="text-muted">Pending</small>
                </div>
              </div>
              <div class="col-4">
                <div class="p-3 border rounded bg-light">
                  <h3 class="mb-0">
                    <?php
                    // Count "Expired": notifications whose expiry_date < NOW()
                    $expiredCount = $pdo->query("
                        SELECT COUNT(*)
                        FROM notifications
                        WHERE expiry_date IS NOT NULL
                          AND expiry_date < NOW()
                      ")->fetchColumn();
                    echo (int)$expiredCount;
                    ?>
                  </h3>
                  <small class="text-muted">Expired</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /row -->
  </div><!-- /main-content -->

  <!-- Include separate file containing all the modals -->
  <?php include __DIR__ . '/admin-notifications-modals.php'; ?>

  <!-- JS Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script>
    $(document).ready(function() {
      $('#notificationTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: false
      });
    });
    // Load a template into the "New Notification" form
    function loadTemplate(template) {
      const modalEl = document.getElementById('newNotificationModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

      const form = modalEl.querySelector('form');
      form.querySelector('input[name="title"]').value = template.title;
      form.querySelector('textarea[name="message"]').value = template.message;

      // Show the modal
      modal.show();
    }

    // Edit notification modal logic
    const editModal = document.getElementById('editNotificationModal');
    editModal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      editModal.querySelector('#editNotificationId').value = button.getAttribute('data-id');
      editModal.querySelector('#editTitle').value = button.getAttribute('data-title');
      editModal.querySelector('#editMessage').value = button.getAttribute('data-message');

      const startDate = button.getAttribute('data-start-date');
      const expiryDate = button.getAttribute('data-expiry-date');
      editModal.querySelector('#editStartDate').value = startDate ? startDate.split(' ')[0] : '';
      editModal.querySelector('#editExpiryDate').value = expiryDate ? expiryDate.split(' ')[0] : '';
    });
  </script>
</body>

</html>