<?php
// File: /pages/get_notifications.php


session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db_connection.php';

// 1) Authentication
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$userId = (int)$_SESSION['user_id'];

try {
    // 2) Determine user’s membership_type_id (default to Free = 1)
    $stmt = $pdo->prepare("
        SELECT membership_type_id
        FROM memberships
        WHERE user_id = ?
        ORDER BY expiry_date DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $membershipType = (int)$stmt->fetchColumn() ?: 1;

    // 3) Build SQL with DISTINCT (no GROUP BY)
    $sql = "
        SELECT DISTINCT
            n.notification_id,
            n.title,
            n.message,
            n.created_at,
            COALESCE(nr.is_read, 0) AS is_read
        FROM notifications n
        LEFT JOIN notification_membership_targets nt
          ON n.notification_id = nt.notification_id
        LEFT JOIN notification_recipients nr
          ON nr.notification_id = n.notification_id
         AND nr.user_id = :uid
        WHERE
            n.is_active = 1
          AND (n.start_date  IS NULL OR DATE(n.start_date)  <= CURDATE())
          AND (n.expiry_date IS NULL OR DATE(n.expiry_date) >= CURDATE())
          AND (
               nt.membership_type_id IS NULL
            OR nt.membership_type_id = :mt
          )
        ORDER BY n.created_at DESC
        LIMIT 20
    ";
    $params = [
        ':uid' => $userId,
        ':mt'  => $membershipType,
    ];

    // 4) Execute
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':uid', $userId,        PDO::PARAM_INT);
    $stmt->bindValue(':mt',  $membershipType, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5) Count unread
    $unread = 0;
    foreach ($notifications as $n) {
        if ((int)$n['is_read'] === 0) {
            $unread++;
        }
    }

    // 6) JSON response (with debug info)
    echo json_encode([
        'count'         => $unread,
        'notifications' => $notifications,
        'debug'         => [
            'membershipType' => $membershipType,
            'sql'            => $sql,
            'params'         => $params,
            'rowCount'       => count($notifications),
        ]
    ]);
    exit;

} catch (PDOException $e) {
    error_log("get_notifications.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error'   => 'Database error',
        'message' => $e->getMessage(),
        'debug'   => [
            'sql'    => $sql ?? null,
            'params' => $params ?? null,
        ]
    ]);
    exit;
}
