<?php
// admin-notifications.php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Fetch templates and membership types (used in modals)
$templates = $pdo->query("SELECT * FROM notification_templates ORDER BY created_at DESC")
                 ->fetchAll(PDO::FETCH_ASSOC);
$membershipTypes = $pdo->query("SELECT * FROM membership_types")
                     ->fetchAll(PDO::FETCH_ASSOC);

// Handle pagination and search
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit  = 15;
$offset = ($page - 1) * $limit;

// Build query for notifications (joining users and membership_types)
$notificationsQuery = "
    SELECT n.*, u.name AS creator_name, mt.type_name AS target_type 
    FROM notifications n
    LEFT JOIN users u ON n.created_by = u.user_id
    LEFT JOIN membership_types mt ON n.membership_type_id = mt.membership_type_id
";
$whereClauses = [];
$params = [];
if (!empty($search)) {
    $whereClauses[] = "(n.title LIKE :search OR n.message LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($whereClauses) {
    $notificationsQuery .= " WHERE " . implode(' AND ', $whereClauses);
}
$notificationsQuery .= " ORDER BY n.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($notificationsQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Uncomment to inspect fetched notifications
// echo '<pre>'; print_r($notifications); echo '</pre>';

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM notifications";
if (!empty($search)) {
    $countQuery .= " WHERE title LIKE :search OR message LIKE :search";
}
$countStmt = $pdo->prepare($countQuery);
if (!empty($search)) {
    $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$countStmt->execute();
$totalNotifications = $countStmt->fetchColumn();
$totalPages = ceil($totalNotifications / $limit);

// Flash messages from previous actions
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Notifications</title>
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../assets/css/admin-notifications.css">
  <style>
    .notification-row:hover { background-color: #f8f9fa; cursor: pointer; }
    .status-active { color: #198754; }
    .status-expired { color: #dc3545; }
    .status-pending { color: #fd7e14; }
    .badge-target { background-color: #6c757d; }
    .action-btn { width: 32px; height: 32px;
                  display: inline-flex; align-items: center; justify-content: center; }
    #notificationTable th { white-space: nowrap; }
    .template-item { border-left: 3px solid #0d6efd;
                     padding-left: 10px; margin-bottom: 10px; }
    .template-item:hover { background-color: #f8f9fa; cursor: pointer; }
  </style>
</head>
<body>
  <!-- Include Sidebar -->
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content container-fluid py-4">
    <!-- Header -->
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

    <!-- Include the view for notifications -->
    <?php include __DIR__ . '/admin-notifications-view.php'; ?>

    <!-- Include the modals -->
    <?php include __DIR__ . '/admin-notifications-modals.php'; ?>
  </div>

  <!-- Load JS Libraries and your custom JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="../assets/js/admin-notifications.js"></script>
</body>
</html>
