<?php
require_once __DIR__ . '/../includes/session-init.php';session_unset();
session_destroy();
header("Location: ../pages/login.php");
exit;
?>
