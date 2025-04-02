<?php
session_start(); // Start the session

// Check if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login page
    exit();
}

// Disable caching of this page to prevent access after logout
header("Cache-Control: no-cache, must-revalidate, no-store, max-age=0, post-check=0, pre-check=0");
header("Pragma: no-cache");
header("Expires: 0");
?>