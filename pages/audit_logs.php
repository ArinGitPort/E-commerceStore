<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Default filters
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$user_id = 'all';
$action_type = 'all';
$table_name = 'all';

// Get filter parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date;
    $user_id = $_POST['user_id'] ?? 'all';
    $action_type = $_POST['action_type'] ?? 'all';
    $table_name = $_POST['table_name'] ?? 'all';
}

// Base query
$query = "SELECT 
            al.log_id,
            al.timestamp,
            al.action,
            al.action_type,
            al.table_name,
            al.record_id,
            al.affected_data,
            u.user_id,
            u.name as user_name,
            u.email as user_email,
            r.role_name
          FROM audit_logs al
          LEFT JOIN users u ON al.user_id = u.user_id
          LEFT JOIN roles r ON u.role_id = r.role_id
          WHERE al.timestamp BETWEEN :start_date AND :end_date";

$params = [
    ':start_date' => $start_date . ' 00:00:00',
    ':end_date' => $end_date . ' 23:59:59'
];

// Add filters
if ($user_id !== 'all') {
    $query .= " AND al.user_id = :user_id";
    $params[':user_id'] = $user_id;
}

if ($action_type !== 'all') {
    $query .= " AND al.action_type = :action_type";
    $params[':action_type'] = $action_type;
}

if ($table_name !== 'all') {
    $query .= " AND al.table_name = :table_name";
    $params[':table_name'] = $table_name;
}

$query .= " ORDER BY al.timestamp DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get users for filter dropdown
$users_query = "SELECT user_id, name, email FROM users ORDER BY name";
$users = $pdo->query($users_query)->fetchAll(PDO::FETCH_ASSOC);

// Get distinct action types
$action_types = $pdo->query("SELECT DISTINCT action_type FROM audit_logs")->fetchAll(PDO::FETCH_COLUMN);

// Get distinct table names
$table_names = $pdo->query("SELECT DISTINCT table_name FROM audit_logs")->fetchAll(PDO::FETCH_COLUMN);

function renderAffectedData($data) {
    if (is_array($data)) {
        echo '<ul>';
        foreach ($data as $key => $value) {
            echo '<li>';
            echo ucfirst($key) . ': ';
            if (is_array($value)) {
                // Recursively render nested arrays
                renderAffectedData($value);
            } else {
                // Render scalar values
                echo htmlspecialchars($value);
            }
            echo '</li>';
        }
        echo '</ul>';
    } else {
        // Render scalar values directly
        echo htmlspecialchars($data);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BunniShop - Audit Logs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .log-entry {
            border-left: 4px solid;
            margin-bottom: 10px;
        }
        .log-create { border-color: #28a745; }
        .log-read { border-color: #17a2b8; }
        .log-update { border-color: #ffc107; }
        .log-delete { border-color: #dc3545; }
        .log-login { border-color: #007bff; }
        .log-system { border-color: #6c757d; }
        .json-data {
            background-color: #f8f9fa;
            padding: 5px;
            border-radius: 3px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="pageWrapper">
        <?php include '../includes/sidebar.php'; ?>

        <div class="contentWrapper">
            <div class="container-fluid">

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Filter Logs</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Date Range</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                                        <span class="input-group-text">to</span>
                                        <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">User</label>
                                    <select class="form-select" name="user_id">
                                        <option value="all">All Users</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['user_id'] ?>" <?= $user_id == $user['user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Table Affected</label>
                                    <select class="form-select" name="table_name">
                                        <option value="all">All Tables</option>
                                        <?php foreach ($table_names as $table): ?>
                                            <option value="<?= $table ?>" <?= $table_name == $table ? 'selected' : '' ?>>
                                                <?= ucfirst($table) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                    <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Log Entries -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5>Activity Log</h5>
                        <span><?= count($logs) ?> entries found</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                            <div class="alert alert-info">No log entries found for the selected filters</div>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <div class="log-entry p-3 log-<?= strtolower($log['action_type']) ?>">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?= htmlspecialchars($log['action']) ?></strong>
                                            <?php if ($log['table_name']): ?>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= date('M j, Y H:i:s', strtotime($log['timestamp'])) ?></small>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <?php if ($log['user_name']): ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($log['user_name']) ?> (<?= htmlspecialchars($log['role_name']) ?>)</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark">System</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($log['affected_data']): ?>
                                            <?php 
                                            // Decode the JSON data
                                            $affectedData = json_decode($log['affected_data'], true); 
                                            ?>
                                            <div class="mt-2">
                                                <strong>Details:</strong>
                                                <?php renderAffectedData($affectedData); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        flatpickr("input[type=date]", {
            dateFormat: "Y-m-d",
            allowInput: true
        });
    </script>
</body>
</html>