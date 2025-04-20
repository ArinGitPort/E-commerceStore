<?php
// ajax/export_subscription_audit.php
session_start();
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/session-init.php';

$search = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';

$stmt = $pdo->prepare("
    SELECT 
        sa.payment_date, 
        u.name, 
        u.email, 
        mt.type_name, 
        sa.payment_amount, 
        sa.transaction_id
      FROM subscriptions_audit sa
      JOIN users u ON sa.user_id = u.user_id
      JOIN membership_types mt ON sa.membership_type_id = mt.membership_type_id
     WHERE u.name LIKE ?
        OR u.email LIKE ?
        OR mt.type_name LIKE ?
     ORDER BY sa.payment_date DESC
");
$stmt->execute([$search, $search, $search]);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="subscription_audit.csv"');

$out = fopen('php://output', 'w');
// Column headers
fputcsv($out, ['Date','Name','Email','Tier','Amount','Transaction ID']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        date('Y-m-d H:i:s', strtotime($row['payment_date'])),
        $row['name'],
        $row['email'],
        $row['type_name'],
        number_format($row['payment_amount'], 2),
        $row['transaction_id'],
    ]);
}

fclose($out);
exit;
