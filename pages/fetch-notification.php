<?php
// filepath: c:\Users\Allen\VS Code Project\onlineshowebsiteVer2-4\pages-user\fetch-notifications.php

require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'notifications' => [],
    'error' => null
];

try {


    $user_id = $_SESSION['user_id'];

    // Get user's current membership (if exists)
    $stmt = $pdo->prepare("
        SELECT m.membership_type_id, mt.type_name
        FROM memberships m
        JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
        WHERE m.user_id = ?
        AND m.expiry_date > CURDATE()
        ORDER BY m.expiry_date DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    $membership_type_id = $membership['membership_type_id'] ?? null;

    // Get relevant notifications
    $stmt = $pdo->prepare("
        SELECT 
            n.notification_id,
            n.title,
            n.message,
            n.start_date,
            n.expiry_date,
            n.created_at,
            u.name as author_name,
            GROUP_CONCAT(mt.type_name) as target_groups
        FROM notifications n
        LEFT JOIN notification_membership_targets nt 
            ON n.notification_id = nt.notification_id
        LEFT JOIN membership_types mt 
            ON nt.membership_type_id = mt.membership_type_id
        LEFT JOIN users u 
            ON n.created_by = u.user_id
        WHERE 
            (n.start_date <= CURDATE() OR n.start_date IS NULL)
            AND (n.expiry_date >= CURDATE() OR n.expiry_date IS NULL)
            AND (
                nt.membership_type_id IS NULL  -- Global notifications
                OR nt.membership_type_id = ?   -- Matching membership
            )
        GROUP BY n.notification_id
        ORDER BY n.created_at DESC
    ");
    
    $stmt->execute([$membership_type_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates and targets
    foreach ($notifications as &$notif) {
        $notif['start_date'] = $notif['start_date'] ? date('M j, Y', strtotime($notif['start_date'])) : 'Immediate';
        $notif['expiry_date'] = $notif['expiry_date'] ? date('M j, Y', strtotime($notif['expiry_date'])) : 'No expiry';
        $notif['target_groups'] = $notif['target_groups'] ?: 'All Members';
    }

    $response['success'] = true;
    $response['notifications'] = $notifications;

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);