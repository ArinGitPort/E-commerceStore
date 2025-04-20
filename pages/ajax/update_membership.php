<?php
// ajax/membership_update.php
require_once __DIR__ . '/../../includes/session-init.php';
require_once __DIR__ . '/../../config/db_connection.php';

header('Content-Type: application/json');

try {
    // 1) Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method Not Allowed');
    }

    // 2) Required fields
    foreach (['user_id','membership_type_id'] as $f) {
        if (!isset($_POST[$f])) {
            http_response_code(400);
            throw new Exception("Missing required field: $f");
        }
    }

    // 3) Sanitize
    $userId   = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    $typeId   = filter_var($_POST['membership_type_id'], FILTER_VALIDATE_INT);
    $startRaw = $_POST['start_date']  ?? null;
    $endRaw   = $_POST['expiry_date'] ?? null;

    // 4) Verify user
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        throw new Exception('User not found');
    }

    // 5) Get membership type name
    $stmt = $pdo->prepare("SELECT type_name FROM membership_types WHERE membership_type_id = ?");
    $stmt->execute([$typeId]);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$type) {
        http_response_code(400);
        throw new Exception('Invalid membership type');
    }

    // 6) Handle Free
    if ($type['type_name'] === 'Free') {
        if ($startRaw || $endRaw) {
            throw new Exception('Free membership cannot have dates');
        }
        // Remove any existing membership
        $pdo->prepare("DELETE FROM memberships WHERE user_id = ?")
            ->execute([$userId]);

        // Downgrade role to Customer
        $pdo->prepare("
            UPDATE users
               SET role_id = (
                   SELECT role_id FROM roles WHERE role_name = 'Customer'
               )
             WHERE user_id = ?
        ")->execute([$userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Membership reset to Free and role set to Customer'
        ]);
        exit;
    }

    // 7) Paid tiers require dates
    if (!$startRaw || !$endRaw) {
        throw new Exception('Start and end dates are required for premium memberships');
    }
    $start = new DateTime($startRaw);
    $end   = new DateTime($endRaw);
    if ($start > $end) {
        throw new Exception('End date cannot be before start date');
    }

    // 8) Upsert membership
    $sql = "
      INSERT INTO memberships
        (user_id, membership_type_id, start_date, expiry_date)
      VALUES (:u, :t, :s, :e)
      ON DUPLICATE KEY UPDATE
        membership_type_id = VALUES(membership_type_id),
        start_date         = VALUES(start_date),
        expiry_date        = VALUES(expiry_date),
        modified_at        = NOW()
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':u' => $userId,
        ':t' => $typeId,
        ':s' => $start->format('Y-m-d'),
        ':e' => $end->format('Y-m-d'),
    ]);

    // 9) Promote role to Member
    $pdo->prepare("
        UPDATE users
           SET role_id = (
               SELECT role_id FROM roles WHERE role_name = 'Member'
           )
         WHERE user_id = ?
    ")->execute([$userId]);

    // 10) Success
    echo json_encode([
        'success' => true,
        'message' => 'Membership updated; role set to Member',
        'details' => [
            'start_date'    => $start->format('Y-m-d'),
            'expiry_date'   => $end->format('Y-m-d'),
            'duration_days' => $end->diff($start)->days
        ]
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
