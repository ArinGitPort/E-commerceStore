<?php
// get_notifications.php
session_start();
require_once '../config/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];

try {
    // Get user's membership type
    $stmt = $pdo->prepare("
        SELECT m.membership_type_id 
        FROM memberships m
        WHERE m.user_id = ? AND m.expiry_date > CURDATE()
    ");
    $stmt->execute([$userId]);
    $membershipType = $stmt->fetchColumn();

    // Get notifications targeting user's membership type
    $query = "
        SELECT n.notification_id, n.title, n.message, n.created_at,
               nr.is_read, nr.read_at
        FROM notifications n
        INNER JOIN notification_membership_targets nmt ON n.notification_id = nmt.notification_id
        LEFT JOIN notification_recipients nr ON n.notification_id = nr.notification_id AND nr.user_id = ?
        WHERE nmt.membership_type_id = ?
          AND n.is_active = TRUE
          AND CURDATE() BETWEEN n.start_date AND n.expiry_date
        ORDER BY n.created_at DESC
        LIMIT 15
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $membershipType]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate unread count
    $unreadCount = 0;
    foreach ($notifications as $notification) {
        if (!$notification['is_read']) $unreadCount++;
    }

    echo json_encode([
        'count' => $unreadCount,
        'notifications' => $notifications
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>