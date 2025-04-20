<?php
// pages/ajax/create_user.php
require_once __DIR__ . '/../../includes/session-init.php';
require_once __DIR__ . '/../../config/db_connection.php';

header('Content-Type: application/json');

// 1) Permission check: only Admin or Super Admin
if (
    empty($_SESSION['role_id'])
    || ! in_array((int)$_SESSION['role_id'], [4, 5], true)
) {
    http_response_code(403);
    exit(json_encode([
        'success' => false,
        'error' => 'You do not have permission to create staff accounts.'
    ]));
}

// 2) Collect & trim inputs
$data = [
    'name'     => trim($_POST['name']     ?? ''),
    'email'    => trim($_POST['email']    ?? ''),
    'password' => $_POST['password']      ?? '',
    'role_id'  => (int) ($_POST['role_id'] ?? 0),
    'phone'    => trim($_POST['phone']    ?? ''),
    'address'  => trim($_POST['address']  ?? ''),
];

// 3) Validate required fields
if ($data['name'] === '' || $data['email'] === '' || $data['password'] === '' || $data['role_id'] <= 0) {
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'error'   => 'Name, email, password and role are required.'
    ]));
}

// 4) Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'error'   => 'Invalid email address.'
    ]));
}

// 5) Hash the provided password
$hash = password_hash($data['password'], PASSWORD_DEFAULT);
if ($hash === false) {
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error'   => 'Failed to hash password.'
    ]));
}

// 6) Insert into users
try {
    $stmt = $pdo->prepare("
        INSERT INTO users
          (name, email, password, role_id, phone, address, is_active, created_at)
        VALUES
          (?,    ?,     ?,        ?,       ?,     ?,       1,         NOW())
    ");
    $stmt->execute([
        $data['name'],
        $data['email'],
        $hash,
        $data['role_id'],
        $data['phone']    ?: null,
        $data['address']  ?: null,
    ]);

    echo json_encode([
        'success' => true,
        'user_id' => $pdo->lastInsertId()
    ]);
    exit;
} catch (PDOException $e) {
    // Detect duplicate‐email error
    if ($e->getCode() === '23000') {
        http_response_code(409);
        exit(json_encode([
            'success' => false,
            'error'   => 'That email is already in use.'
        ]));
    }
    // Log unexpected error and return generic message
    error_log("ajax/create_user.php error: {$e->getMessage()}");
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error'   => 'An unexpected database error occurred.'
    ]));
}
