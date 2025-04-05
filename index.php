<?php
// index.php - Redirects to the shop page with safety checks

// Check if headers have already been sent
if (!headers_sent()) {
    header("Location: ../pages-user/shop.php");
    exit;
} else {
    // Fallback for when headers have been sent
    echo '<script>window.location.href = "../pages-user/shop.php";</script>';
    echo '<meta http-equiv="refresh" content="0;url=../pages-user/shop.php">';
    echo '<p>Redirecting to <a href="../pages-user/shop.php">shop page</a>...</p>';
    exit;
}