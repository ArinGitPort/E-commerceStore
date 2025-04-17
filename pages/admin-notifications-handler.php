<?php
// Filepath: /pages/admin-notifications-handler.php

require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$response = [
    'success' => false,
    'message' => '',
    'data'    => null,
    'errors'  => []
];

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_notification':
            // Process "New Notification"
            $title               = trim($_POST['title'] ?? '');
            $message             = trim($_POST['message'] ?? '');
            $start_date          = !empty($_POST['start_date'])  ? $_POST['start_date']  : null;
            $expiry_date         = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            $target_memberships  = $_POST['membership_types']    ?? [];

            // Basic validation
            if (empty($title)) {
                $response['errors']['title'] = 'Title is required.';
            }
            if (empty($message)) {
                $response['errors']['message'] = 'Message is required.';
            }

            // New Date validation logic
            if ($start_date && $expiry_date && ($start_date > $expiry_date)) {
                // Instead of returning validation failed, mark this as an error for date
                $response['errors']['date'] = 'Expiry date must be after start date.';
                $response['message'] = 'Invalid date selection';
            }

            if (!empty($response['errors'])) {
                break;
            }

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

            // Insert into notification_membership_targets
            if (!empty($target_memberships)) {
                $insertTargets = $pdo->prepare("
                    INSERT INTO notification_membership_targets (notification_id, membership_type_id)
                    VALUES (?, ?)
                ");
                foreach ($target_memberships as $typeId) {
                    if (!is_numeric($typeId)) continue;
                    $insertTargets->execute([$notification_id, (int)$typeId]);
                }
            }

            $pdo->commit();

            // Fetch the newly created notification data
            $fetchStmt = $pdo->prepare("
                SELECT n.*, GROUP_CONCAT(mt.type_name) AS target_groups
                FROM notifications n
                LEFT JOIN notification_membership_targets nmt
                       ON n.notification_id = nmt.notification_id
                LEFT JOIN membership_types mt
                       ON nmt.membership_type_id = mt.membership_type_id
                WHERE n.notification_id = ?
                GROUP BY n.notification_id
            ");
            $fetchStmt->execute([$notification_id]);
            $notification = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['message'] = 'Notification created successfully';
            $response['data']    = ['notification' => $notification];
            break;

        case 'delete_notification':
            // Process "Delete Notification"
            $notification_id = (int)($_POST['notification_id'] ?? 0);

            if ($notification_id <= 0) {
                $response['message'] = 'Invalid notification ID';
                break;
            }

            $pdo->beginTransaction();

            // First, delete related entries from notification_membership_targets
            $deleteTargetsStmt = $pdo->prepare("DELETE FROM notification_membership_targets WHERE notification_id = ?");
            $deleteTargetsStmt->execute([$notification_id]);

            // Then, delete the notification itself
            $deleteNotificationStmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ?");
            $deleteNotificationStmt->execute([$notification_id]);

            $pdo->commit();

            $response['success'] = true;
            $response['message'] = 'Notification deleted successfully';
            break;

        case 'update_notification':
            // Process "Edit Notification"
            $notification_id = (int)($_POST['notification_id'] ?? 0);
            $title           = trim($_POST['title'] ?? '');
            $message         = trim($_POST['message'] ?? '');
            $start_date      = !empty($_POST['start_date'])  ? $_POST['start_date']  : null;
            $expiry_date     = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

            if ($notification_id <= 0) {
                $response['message'] = 'Invalid notification ID';
                break;
            }
            if (empty($title)) {
                $response['errors']['title'] = 'Title is required.';
            }
            if (empty($message)) {
                $response['errors']['message'] = 'Message is required.';
            }

            // New Date validation logic for update
            if ($start_date && $expiry_date && ($start_date > $expiry_date)) {
                $response['errors']['date'] = 'Expiry date must be after start date.';
                $response['message'] = 'Invalid date selection';
            }

            if (!empty($response['errors'])) {
                break;
            }

            $stmt = $pdo->prepare("
                UPDATE notifications
                SET title = ?, message = ?, start_date = ?, expiry_date = ?
                WHERE notification_id = ?
            ");
            $stmt->execute([
                $title,
                $message,
                $start_date,
                $expiry_date,
                $notification_id
            ]);

            // Fetch updated notification
            $fetchStmt = $pdo->prepare("
                SELECT n.*, GROUP_CONCAT(mt.type_name) AS target_groups
                FROM notifications n
                LEFT JOIN notification_membership_targets nmt
                       ON n.notification_id = nmt.notification_id
                LEFT JOIN membership_types mt
                       ON nmt.membership_type_id = mt.membership_type_id
                WHERE n.notification_id = ?
                GROUP BY n.notification_id
            ");
            $fetchStmt->execute([$notification_id]);
            $notification = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['message'] = 'Notification updated successfully';
            $response['data']    = ['notification' => $notification];
            break;

        case 'create_template':
            $title   = trim($_POST['title']   ?? '');
            $message = trim($_POST['message'] ?? '');
            $errors  = [];

            if ($title === '') {
                $errors['title'] = 'Template title is required.';
            }
            if ($message === '') {
                $errors['message'] = 'Template message is required.';
            }

            if (!empty($errors)) {
                $response['errors']  = $errors;
                $response['message'] = 'Please fix the errors below.';
                break;
            }

            $stmt = $pdo->prepare("
                    INSERT INTO notification_templates (title, message, created_by)
                    VALUES (?, ?, ?)
                ");
            $stmt->execute([
                $title,
                $message,
                $_SESSION['user_id']
            ]);

            $response['success'] = true;
            $response['message'] = 'Template saved!';
            break;

        case 'delete_template':
            $tpl_id = (int)($_POST['template_id'] ?? 0);
            if ($tpl_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM notification_templates WHERE template_id = ?");
                $stmt->execute([$tpl_id]);
                $response['success'] = true;
                $response['message'] = 'Template deleted';
            } else {
                $response['message'] = 'Invalid template ID';
            }
            break;

        default:
            $response['message'] = 'Unknown action';
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'General error: ' . $e->getMessage();
    error_log('General error: ' . $e->getMessage());
}

// Output response
output($response, $is_ajax);

/**
 * Helper function to output JSON for AJAX or redirect for non-AJAX
 */
function output($response, $isAjax)
{
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        session_start();
        if ($response['success']) {
            $_SESSION['success'] = $response['message'];
        } else {
            $_SESSION['error'] = $response['message'];
            if (!empty($response['errors'])) {
                $_SESSION['form_errors'] = $response['errors'];
            }
        }
        // Redirect back to admin-notifications or referrer
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'admin-notifications.php';
        header("Location: $redirect_url");
        exit;
    }
}
