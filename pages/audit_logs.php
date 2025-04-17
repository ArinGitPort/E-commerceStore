<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Default filters
$start_date   = date('Y-m-d', strtotime('-1 days'));
$end_date     = date('Y-m-d', strtotime('+30 days'));;
$user_id      = 'all';
$action_type  = 'all';
$table_name   = 'all';

// Get filter parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input_start = $_POST['start_date'] ?? $start_date;
    $user_input_end   = $_POST['end_date']   ?? $end_date;

    // Normalize: make sure $start_date is always the earlier date
    if (strtotime($user_input_start) <= strtotime($user_input_end)) {
        $start_date = $user_input_start;
        $end_date   = $user_input_end;
    } else {
        $start_date = $user_input_end;
        $end_date   = $user_input_start;
    }

    $user_id      = $_POST['user_id']     ?? 'all';
    $action_type  = $_POST['action_type'] ?? 'all';
    $table_name   = $_POST['table_name']  ?? 'all';
}

// Build query
$query = "SELECT DISTINCT al.log_id, al.timestamp, al.action, al.action_type,
                 al.table_name, al.record_id, al.affected_data,
                 u.user_id, u.name AS user_name, r.role_name
          FROM audit_logs al
          LEFT JOIN users u ON al.user_id = u.user_id
          LEFT JOIN roles r ON u.role_id = r.role_id
          WHERE al.timestamp BETWEEN :start_date AND :end_date_plus";

$params = [
    ':start_date'   => "{$start_date} 00:00:00",
    ':end_date_plus' => "{$end_date} 23:59:59"
];

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

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown data
$users = $pdo->query("SELECT user_id, name, email FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$action_types = $pdo->query("SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type")->fetchAll(PDO::FETCH_COLUMN);
$table_names  = $pdo->query("SELECT DISTINCT table_name FROM audit_logs ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);

// Recursive render helper
function renderAffectedData($data) {
    if (is_array($data)) {
        echo '<ul class="ps-3">';
        foreach ($data as $key => $value) {
            echo '<li><strong>' . ucfirst($key) . ':</strong> ';
            echo is_array($value) ? renderAffectedData($value) : htmlspecialchars($value);
            echo '</li>';
        }
        echo '</ul>';
    } elseif ($data) {
        echo '<div class="json-data">' . htmlspecialchars($data) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Audit Logs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    .filter-bar { position: sticky; top: 0; z-index: 10; background: #fff; }
    .timeline { position: relative; padding: 1rem 0; }
    .timeline::before { content: ''; position: absolute; left: 2rem; top: 0; bottom: 0; width: 4px; background: #dee2e6; }
    .timeline-item { position: relative; margin-bottom: 2rem; padding-left: 4rem; }
    .timeline-dot { position: absolute; left: 2rem; width: 1rem; height: 1rem; border-radius: 50%; background: #0d6efd; transform: translateX(-50%); }
    .timeline-item .time { font-size: 0.85rem; color: #6c757d; }
    .action-icon { width: 1.5rem; text-align: center; }
    .json-data { background: #f8f9fa; padding: .5rem; border-radius: .25rem; font-family: monospace; white-space: pre-wrap; }
  </style>
</head>

<?php include '../includes/sidebar.php'; ?>

<body class="bg-light">
  <div class="container-fluid p-4">
    <h2 class="mb-4">Audit Logs</h2>
    <div class="card mb-4 filter-bar shadow-sm">
      <div class="card-body">
        <form method="POST" class="row gy-2 gx-3 align-items-end">
          <div class="col-md-3">
            <label>Date Range</label>
            <div class="d-flex">
              <input type="date" name="start_date" class="form-control me-2" max="<?=htmlspecialchars($end_date)?>" value="<?=htmlspecialchars($start_date)?>">
              <input type="date" name="end_date" class="form-control" min="<?=htmlspecialchars($start_date)?>" value="<?=htmlspecialchars($end_date)?>">
            </div>
          </div>
          <div class="col-md-2">
            <label>User</label>
            <select name="user_id" class="form-select">
              <option value="all">All</option>
              <?php foreach ($users as $u): ?>
                <option value="<?=$u['user_id']?>" <?=$user_id==$u['user_id']?'selected':''?>>
                  <?=htmlspecialchars($u['name'])?> (<?=htmlspecialchars($u['email'])?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label>Action Type</label>
            <select name="action_type" class="form-select">
              <option value="all">All</option>
              <?php foreach ($action_types as $type): ?>
                <option value="<?=$type?>" <?=$action_type==$type?'selected':''?>><?=$type?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label>Table</label>
            <select name="table_name" class="form-select">
              <option value="all">All</option>
              <?php foreach ($table_names as $tbl): ?>
                <option value="<?=$tbl?>" <?=$table_name==$tbl?'selected':''?>><?=$tbl?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Apply</button>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
          </div>
        </form>
      </div>
    </div>

    <div class="timeline">
      <?php if (empty($logs)): ?>
        <div class="alert alert-info">No entries found.</div>
      <?php else: ?>
        <?php foreach ($logs as $log): ?>
          <?php
            // Map action types to icons & colors
            $map = [
              'CREATE' => ['fas fa-plus-circle', 'text-success'],
              'READ'   => ['fas fa-eye', 'text-info'],
              'UPDATE' => ['fas fa-edit', 'text-warning'],
              'DELETE' => ['fas fa-trash-alt', 'text-danger'],
              'LOGIN'  => ['fas fa-sign-in-alt', 'text-primary'],
              'LOGOUT' => ['fas fa-sign-out-alt', 'text-dark'],
              'SYSTEM' => ['fas fa-cogs', 'text-secondary'],
            ];
            [$icon, $color] = $map[$log['action_type']] ?? $map['SYSTEM'];
          ?>
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="d-flex align-items-center">
              <div class="action-icon <?=$color?>"><i class="<?=$icon?>"></i></div>
              <div class="ms-3 flex-grow-1">
                <div class="d-flex justify-content-between">
                  <strong><?=htmlspecialchars($log['action'])?></strong>
                  <span class="time"><?=date('M j, Y H:i', strtotime($log['timestamp']))?></span>
                </div>
                <div class="mt-1 small text-muted">
                  <?=htmlspecialchars($log['user_name'] ?? 'System')?>
                  <?= $log['role_name'] ? "({$log['role_name']})" : '' ?>
                </div>
                <?php if ($log['affected_data']): ?>
                  <?php $data=json_decode($log['affected_data'],true); ?>
                  <div class="card card-body mt-2 py-2">
                    <?php renderAffectedData($data); ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
