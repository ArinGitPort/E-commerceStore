<?php
// admin-notifications-view.php

$pendingCount = $pdo->query("
    SELECT COUNT(*)
    FROM notifications
    WHERE start_date IS NOT NULL
      AND start_date > CURDATE()
")->fetchColumn();

$expiredCount = $pdo->query("
    SELECT COUNT(*)
    FROM notifications
    WHERE expiry_date IS NOT NULL
      AND expiry_date < CURDATE()
")->fetchColumn();
?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">Notifications</h1>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Notifications</h5>
                    <form class="d-flex" method="get" action="admin-notifications.php">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search notifications..." value="<?= htmlspecialchars($search) ?>">
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
                                $now = new DateTime();
                                foreach ($notifications as $notification):
                                    $status = 'Active';
                                    $statusClass = 'status-active';
                                    $startDate = $notification['start_date'] ? new DateTime($notification['start_date']) : null;
                                    $expiryDate = $notification['expiry_date'] ? new DateTime($notification['expiry_date']) : null;
                                    if ($startDate && $now < $startDate) {
                                        $status = 'Pending';
                                        $statusClass = 'status-pending';
                                    } elseif ($expiryDate && $now > $expiryDate) {
                                        $status = 'Expired';
                                        $statusClass = 'status-expired';
                                    }
                                ?>
                                    <tr data-notification-id="<?= $notification['notification_id'] ?>">
                                        <td><?= $notification['notification_id'] ?></td>
                                        <td><?= htmlspecialchars($notification['title']) ?></td>
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
                                                <?= $startDate ? $startDate->format('M j, Y') : 'Immediate' ?> -
                                                <?= $expiryDate ? $expiryDate->format('M j, Y') : 'No expiry' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <!-- Edit button: data attributes to populate the edit modal -->
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
                                                <form method="post" action="admin-notifications-handler.php" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                                    <input type="hidden" name="action" value="delete_notification">
                                                    <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger action-btn" type="submit">
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
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
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
        <div class="col-md-6 mb-4">
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
                                <div class="template-item" onclick="loadTemplate(<?= htmlspecialchars(json_encode($template), ENT_QUOTES) ?>)">
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
                                <h3 class="mb-0"><?= $pendingCount ?></h3>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="mb-0"><?= $expiredCount ?></h3>
                                <small class="text-muted">Expired</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>