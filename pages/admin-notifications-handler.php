<?php
// admin-notifications-handler.php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_notification') {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $start_date = $_POST['start_date'] ?: null;
        $expiry_date = $_POST['expiry_date'] ?: null;
        $target_memberships = $_POST['membership_types'] ?? [];
        
        if ($title && $message) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO notifications (title, message, created_by, start_date, expiry_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $message, $_SESSION['user_id'], $start_date, $expiry_date]);
            $notification_id = $pdo->lastInsertId();
            
            // Insert membership target data if provided
            if (!empty($target_memberships)) {
                $targetStmt = $pdo->prepare("INSERT INTO notification_membership_targets (notification_id, membership_type_id) VALUES (?, ?)");
                foreach ($target_memberships as $type_id) {
                    $targetStmt->execute([$notification_id, $type_id]);
                }
            }
            $pdo->commit();
            $response = [
                'success' => true,
                'message' => 'Notification sent successfully!',
                'notification_id' => $notification_id,
                'redirect' => !$is_ajax
            ];
        } else {
            $response['message'] = 'Title and message are required.';
        }
    } elseif ($action === 'save_template') {
        $title = trim($_POST['template_title']);
        $message = trim($_POST['template_message']);
        if ($title && $message) {
            $stmt = $pdo->prepare("INSERT INTO notification_templates (title, message, created_by) VALUES (?, ?, ?)");
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
        $notification_id = (int) $_POST['notification_id'];
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ?");
        $stmt->execute([$notification_id]);
        $response = [
            'success' => true,
            'message' => 'Notification deleted successfully!',
            'notification_id' => $notification_id,
            'redirect' => !$is_ajax
        ];
    } elseif ($action === 'update_notification') {
        $notification_id = (int) $_POST['notification_id'];
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $start_date = $_POST['start_date'] ?: null;
        $expiry_date = $_POST['expiry_date'] ?: null;
        $stmt = $pdo->prepare("UPDATE notifications SET title = ?, message = ?, start_date = ?, expiry_date = ? WHERE notification_id = ?");
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
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
}

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    $_SESSION[$response['success'] ? 'success' : 'error'] = $response['message'];
    header("Location: admin-notifications.php");
    exit;
}
