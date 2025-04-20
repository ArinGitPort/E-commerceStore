<?php
// pages/ajax/update_membership_tier.php
session_start();
require_once __DIR__ . '/../../includes/session-init.php';
require_once __DIR__ . '/../../config/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../subscription-management.php');
  exit;
}

$id          = (int)($_POST['membership_type_id'] ?? 0);
$name        = trim($_POST['type_name'] ?? '');
$price       = floatval($_POST['price'] ?? 0);
$description = $_POST['description'] ?? '';
$exclusive   = isset($_POST['can_access_exclusive']) ? 1 : 0;

if ($id < 1 || $name === '') {
  $_SESSION['error'] = 'Invalid input.';
  header('Location: ../subscription-management.php');
  exit;
}

try {
  $stmt = $pdo->prepare("
    UPDATE membership_types
       SET type_name            = ?,
           price                = ?,
           description          = ?,
           can_access_exclusive = ?,
           modified_at          = NOW()
     WHERE membership_type_id   = ?
  ");
  $stmt->execute([$name, $price, $description, $exclusive, $id]);
  $_SESSION['message'] = 'Tier updated successfully.';
} catch (PDOException $e) {
  $_SESSION['error'] = 'Update failed: ' . $e->getMessage();
}

header('Location: ../subscription-management.php');
exit;
