<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';



// Fetch data for templates & membership types
$templates = $pdo->query("SELECT * FROM notification_templates ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$membershipTypes = $pdo->query("SELECT * FROM membership_types")->fetchAll(PDO::FETCH_ASSOC);

// Handle pagination/search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 15;
$offset = ($page - 1) * $limit;

$notificationsQuery = "
    SELECT 
        n.*, 
        u.name AS creator_name,
        GROUP_CONCAT(mt.type_name) AS target_groups
    FROM notifications n
    LEFT JOIN users u ON n.created_by = u.user_id
    LEFT JOIN notification_membership_targets nt ON n.notification_id = nt.notification_id
    LEFT JOIN membership_types mt ON nt.membership_type_id = mt.membership_type_id
";

// If searching
$whereClauses = [];
if (!empty($search)) {
  $whereClauses[] = "(n.title LIKE :search OR n.message LIKE :search)";
}

if ($whereClauses) {
  $notificationsQuery .= " WHERE " . implode(' AND ', $whereClauses);
}

$notificationsQuery .= " ORDER BY n.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($notificationsQuery);
if (!empty($search)) {
  $searchTerm = "%$search%";
  $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total notifications
$countQuery = "SELECT COUNT(*) FROM notifications";
if (!empty($search)) {
  $countQuery .= " WHERE title LIKE :search OR message LIKE :search";
}
$stmt = $pdo->prepare($countQuery);
if (!empty($search)) {
  $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
}
$stmt->execute();
$totalNotifications = $stmt->fetchColumn();
$totalPages = ceil($totalNotifications / $limit);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

  $action = $_POST['action'] ?? '';
  $response = ['success' => false, 'message' => ''];

  try {
    if ($action === 'create_notification') {
      // Create new notification
      $title = trim($_POST['title']);
      $message = trim($_POST['message']);
      $start_date = $_POST['start_date'] ?: null;
      $expiry_date = $_POST['expiry_date'] ?: null;
      $target_memberships = $_POST['membership_types'] ?? [];

      if ($title && $message) {
        $pdo->beginTransaction();

        // Insert into notifications
        $stmt = $pdo->prepare("
                  INSERT INTO notifications (title, message, created_by, start_date, expiry_date) 
                  VALUES (?, ?, ?, ?, ?)
              ");
        $stmt->execute([
          $title,
          $message,
          $_SESSION['user_id'],
          $start_date,
          $expiry_date
        ]);

        $notification_id = $pdo->lastInsertId();

        // Insert into notification_membership_targets if multiple membership types
        if (!empty($target_memberships)) {
          $targetStmt = $pdo->prepare("
                      INSERT INTO notification_membership_targets (notification_id, membership_type_id) 
                      VALUES (?, ?)
                  ");
          foreach ($target_memberships as $type_id) {
            $targetStmt->execute([$notification_id, $type_id]);
          }
        }

        $pdo->commit();
        $response = [
          'success' => true,
          'message' => 'Notification sent successfully!',
          'notification_id' => $notification_id,
          'redirect' => !$is_ajax // Only redirect for non-AJAX
        ];
      } else {
        $response['message'] = 'Title and message are required.';
      }
    } elseif ($action === 'save_template') {
      // Save new template
      $title = trim($_POST['template_title']);
      $message = trim($_POST['template_message']);

      if ($title && $message) {
        $stmt = $pdo->prepare("
                  INSERT INTO notification_templates (title, message, created_by)
                  VALUES (?, ?, ?)
              ");
        $stmt->execute([$title, $message, $_SESSION['user_id']]);
        $template_id = $pdo->lastInsertId();

        $response = [
          'success' => true,
          'message' => 'Template saved successfully!',
          'template' => [
            'id' => $template_id,
            'title' => $title,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
          ],
          'redirect' => !$is_ajax
        ];
      } else {
        $response['message'] = 'Template title and message are required.';
      }
    } elseif ($action === 'delete_notification') {
      // Delete notification
      $notification_id = (int)$_POST['notification_id'];
      $stmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ?");
      $stmt->execute([$notification_id]);

      $response = [
        'success' => true,
        'message' => 'Notification deleted successfully!',
        'notification_id' => $notification_id,
        'redirect' => !$is_ajax
      ];
    } elseif ($action === 'update_notification') {
      // Update existing notification
      $notification_id = (int)$_POST['notification_id'];
      $title = trim($_POST['title']);
      $message = trim($_POST['message']);
      $start_date = $_POST['start_date'] ?: null;
      $expiry_date = $_POST['expiry_date'] ?: null;

      $stmt = $pdo->prepare("
              UPDATE notifications
              SET title = ?, message = ?, start_date = ?, expiry_date = ?
              WHERE notification_id = ?
          ");
      $stmt->execute([$title, $message, $start_date, $expiry_date, $notification_id]);

      $response = [
        'success' => true,
        'message' => 'Notification updated successfully!',
        'notification_id' => $notification_id,
        'redirect' => !$is_ajax
      ];
    } else {
      $response['message'] = 'Invalid action specified.';
    }
  } catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
  }

  if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  } else {
    // For regular form submissions
    $_SESSION['success'] = $response['success'] ? $response['message'] : '';
    $_SESSION['error'] = !$response['success'] ? $response['message'] : '';
    header("Location: admin-notifications.php");
    exit;
  }
}

// Flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
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

    <!-- Notifications Card -->
    <div class="row mb-4">
      <div class="col">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Notifications</h5>
            <form class="d-flex" method="get" action="fetch-notifications.php">
              <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search notifications..."
                  value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-secondary" type="submit">
                  <i class="bi bi-search"></i>
                </button>
                <?php if (!empty($search)): ?>
                  <a href="fetch-notifications.php" class="btn btn-outline-danger">
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
                  $now = new DateTime();
                  foreach ($notifications as $notification):
                    $status = 'Active';
                    $statusClass = 'status-active';

                    date_default_timezone_set('Asia/Manila'); // Set appropriate timezone

                    $startDate = $notification['start_date'] ?
                      new DateTime($notification['start_date'], new DateTimeZone('Asia/Manila')) :
                      null;
                    $expiryDate = $notification['expiry_date'] ? new DateTime($notification['expiry_date']) : null;

                    if ($startDate && $now < $startDate) {
                      $status = 'Pending';
                      $statusClass = 'status-pending';
                    } elseif ($expiryDate && $now > $expiryDate) {
                      $status = 'Expired';
                      $statusClass = 'status-expired';
                    }
                  ?>
                    <tr class="notification-row">
                      <td><?= $notification['notification_id'] ?></td>
                      <td><?= htmlspecialchars($data, ENT_QUOTES) ?></td>

                      <td>
                        <div class="text-truncate" style="max-width: 300px;">
                          <?= htmlspecialchars($notification['message']) ?>
                        </div>
                      </td>
                      <td>
                        <?php if (!empty($notification['target_type'])): ?>
                          <span class="badge badge-target"><?= htmlspecialchars($notification['target_type']) ?></span>
                        <?php else: ?>
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
                            data-bs-toggle="modal" data-bs-target="#editNotificationModal"
                            data-id="<?= $notification['notification_id'] ?>"
                            data-title="<?= htmlspecialchars($notification['title']) ?>"
                            data-message="<?= htmlspecialchars($notification['message']) ?>"
                            data-start-date="<?= $notification['start_date'] ?>"
                            data-expiry-date="<?= $notification['expiry_date'] ?>">
                            <i class="bi bi-pencil"></i>
                          </button>

                          <!-- Delete form -->
                          <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                            <input type="hidden" name="action" value="delete_notification">
                            <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger action-btn"
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
    </div>

    <!-- Templates & Stats Row -->
    <div class="row">
      <!-- Quick Templates -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Quick Templates</h5>
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
                    // Example: count "Pending"
                    // (start_date > now) or else if start_date is null, fallback logic
                    echo $pdo->query("
                      SELECT COUNT(*)
                      FROM notifications
                      WHERE (start_date IS NOT NULL AND start_date > NOW())
                    ")->fetchColumn();
                    ?>
                  </h3>
                  <small class="text-muted">Pending</small>
                </div>
              </div>
              <div class="col-4">
                <div class="p-3 border rounded bg-light">
                  <h3 class="mb-0">
                    <?php
                    // Example: count "Expired"
                    echo $pdo->query("
                      SELECT COUNT(*)
                      FROM notifications
                      WHERE expiry_date IS NOT NULL
                        AND expiry_date < NOW()
                    ")->fetchColumn();
                    ?>
                  </h3>
                  <small class="text-muted">Expired</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- /col-md-6 -->
    </div> <!-- /row -->

  </div> <!-- /main-content -->
  <!-- Modals -->

  <!-- New Notification Modal -->
  <div class="modal fade" id="newNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
          <input type="hidden" name="action" value="create_notification">

          <div class="modal-header">
            <h5 class="modal-title">Create New Notification</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="row">
              <div class="col-md-8">
                <div class="mb-3">
                  <label class="form-label">Title <span class="text-danger">*</span></label>
                  <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Message <span class="text-danger">*</span></label>
                  <textarea name="message" class="form-control" rows="5" required></textarea>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <label class="form-label">Schedule</label>
                  <div class="mb-3">
                    <label class="form-label small">Start Date (optional)</label>
                    <input type="date" name="start_date" class="form-control form-control-sm">
                  </div>
                  <div class="mb-3">
                    <label class="form-label small">Expiry Date (optional)</label>
                    <input type="date" name="expiry_date" class="form-control form-control-sm">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Target Audience</label>
                  <div class="form-text small mb-2">Select specific membership types</div>
                  <div class="list-group list-group-flush">
                    <?php foreach ($membershipTypes as $type): ?>
                      <label class="list-group-item d-flex gap-2">
                        <input class="form-check-input flex-shrink-0" type="checkbox"
                          name="membership_types[]" value="<?= $type['membership_type_id'] ?>">
                        <span>
                          <?= htmlspecialchars($type['type_name']) ?>
                          <?php if ($type['can_access_exclusive']): ?>
                            <span class="badge bg-info ms-1">Exclusive</span>
                          <?php endif; ?>
                        </span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div> <!-- /row -->
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Send Notification</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- New Template Modal -->
  <div class="modal fade" id="newTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
          <input type="hidden" name="action" value="save_template">
          <div class="modal-header">
            <h5 class="modal-title">Save New Template</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Template Title <span class="text-danger">*</span></label>
              <input type="text" name="template_title" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Template Message <span class="text-danger">*</span></label>
              <textarea name="template_message" class="form-control" rows="8" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Template</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Notification Modal -->
  <div class="modal fade" id="editNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
          <input type="hidden" name="action" value="update_notification">
          <input type="hidden" name="notification_id" id="editNotificationId">

          <div class="modal-header">
            <h5 class="modal-title">Edit Notification</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="row">
              <div class="col-md-8">
                <div class="mb-3">
                  <label class="form-label">Title <span class="text-danger">*</span></label>
                  <input type="text" name="title" id="editTitle" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Message <span class="text-danger">*</span></label>
                  <textarea name="message" id="editMessage" class="form-control" rows="5" required></textarea>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3">
                  <label class="form-label">Schedule</label>
                  <div class="mb-3">
                    <label class="form-label small">Start Date</label>
                    <input type="date" name="start_date" id="editStartDate" class="form-control form-control-sm">
                  </div>
                  <div class="mb-3">
                    <label class="form-label small">Expiry Date</label>
                    <input type="date" name="expiry_date" id="editExpiryDate" class="form-control form-control-sm">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Target Audience</label>
                  <div class="form-text small">Targets cannot be modified after creation</div>
                </div>
              </div>
            </div> <!-- /row -->
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Notification</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Debug Panel -->
  <div id="debug-panel" style="
  position: fixed;
  top: 20px;
  right: 20px;
  width: 400px;
  max-height: 400px;
  overflow-y: auto;
  z-index: 9999;
  background: rgba(0,0,0,0.85);
  color: #fff;
  font-size: 12px;
  font-family: monospace;
  border-radius: 5px;
  box-shadow: 0 0 10px rgba(0,0,0,0.3);
  display: none;
  padding: 10px;
">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <strong>🛠️ Debug Log</strong>
      <button onclick="document.getElementById('debug-panel').style.display='none'" style="
      background: red; border: none; color: white; font-size: 12px; padding: 2px 8px; cursor: pointer;
    ">X</button>
    </div>
    <div id="debug-log"></div>
  </div>

  <!-- Toggle Debug Button -->
  <button onclick="toggleDebug()" style="
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
  background: #343a40;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
">
    🔧 Toggle Debug
  </button>

  <script>
    function toggleDebug() {
      const panel = document.getElementById('debug-panel');
      panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }

    function debugLog(...args) {
      const logDiv = document.getElementById('debug-log');
      const entry = document.createElement('div');
      entry.textContent = `[${new Date().toLocaleTimeString()}] ` + args.map(a =>
        typeof a === 'object' ? JSON.stringify(a) : a
      ).join(' | ');
      logDiv.prepend(entry);
    }

    // Patch fetch to log all AJAX requests
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
      debugLog('📤 Request:', args[0], args[1]);
      const response = await originalFetch(...args);
      try {
        const clone = response.clone();
        const data = await clone.json();
        debugLog('📥 Response:', data);
      } catch (e) {
        debugLog('⚠️ Non-JSON Response');
      }
      return response;
    };
  </script>


  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script>
    // Load template into the "New Notification" form
    function loadTemplate(template) {
      const modalEl = document.getElementById('newNotificationModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

      const form = modalEl.querySelector('form');
      form.querySelector('input[name="title"]').value = template.title;
      form.querySelector('textarea[name="message"]').value = template.message;

      // Show the modal
      modal.show();
    }

    // Edit modal logic
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

    // Auto-focus first input in modals
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('shown.bs.modal', () => {
        const input = modal.querySelector('input[type="text"], textarea');
        if (input) input.focus();
      });
    });

    // Initialize DataTable (no paging to avoid conflict with custom paging)
    $(document).ready(function() {
      $('#notificationTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: false
      });
    });
  </script>

  <script>
    // Handle all form submissions via AJAX
    document.addEventListener('DOMContentLoaded', function() {
      // Intercept form submissions
      document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', async function(e) {
          e.preventDefault();

          const formData = new FormData(form);
          const submitBtn = form.querySelector('button[type="submit"]');

          try {
            // Disable submit button to prevent duplicate submissions
            if (submitBtn) {
              submitBtn.disabled = true;
              submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            }

            const response = await fetch(form.action, {
              method: 'POST',
              body: formData, // ✅ correct
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            });


            const data = await response.json();

            if (data.success) {
              const newRow = createNotificationRow(data.notification);
              document.querySelector('#notificationTable tbody').prepend(newRow);
              showAlert('success', data.message);

              // Close modal if this was a modal form
              const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
              if (modal) {
                modal.hide();
              }

              // Refresh page if needed (for non-AJAX fallback)
              if (data.redirect) {
                window.location.reload();
              } else {
                // Update UI dynamically
                if (data.notification_id) {
                  refreshNotification(data.notification_id);
                }
                if (data.template) {
                  addNewTemplate(data.template);
                }
              }
            } else {
              showAlert('danger', data.message);
            }
          } catch (error) {
            showAlert('danger', 'Request failed: ' + error.message);
          } finally {
            // Re-enable submit button
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = form.id.includes('Template') ? 'Save Template' : 'Submit';
            }
          }
        });
      });

      // Show alert message
      function showAlert(type, message) {
        // Remove any existing alerts
        document.querySelectorAll('.alert-dismissible').forEach(alert => {
          bootstrap.Alert.getInstance(alert)?.close();
        });

        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Add to page
        const container = document.querySelector('.main-content') || document.body;
        container.prepend(alertDiv);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
          bootstrap.Alert.getOrCreateInstance(alertDiv).close();
        }, 5000);
      }

      // Refresh notification in UI
      function refreshNotification(id) {
        // You can implement more specific UI updates here
        window.location.reload(); // Simple reload for now
      }

      // Add new template to UI
      function addNewTemplate(template) {
        const templateItem = document.createElement('div');
        templateItem.className = 'template-item';
        templateItem.onclick = () => loadTemplate(template);
        templateItem.innerHTML = `
            <div class="d-flex justify-content-between">
                <strong>${template.title}</strong>
                <small class="text-muted">${new Date(template.created_at).toLocaleDateString()}</small>
            </div>
            <p class="small text-muted mb-0 text-truncate">${template.message}</p>
        `;

        const templatesContainer = document.querySelector('.list-group-flush');
        if (templatesContainer) {
          templatesContainer.prepend(templateItem);
        }
      }
    });
  </script>
</body>

</html>