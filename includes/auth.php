<?php
// includes/auth.php

// Role levels
define('ROLE_CUSTOMER',   1);
define('ROLE_MEMBER',     2);
define('ROLE_STAFF',      3);
define('ROLE_ADMIN',      4);
define('ROLE_SUPER_ADMIN',5);

/**
 * Abort with a 403 if the current user’s role is below the given level.
 */
function require_role(int $minLevel): void {
  if (empty($_SESSION['role_id']) || $_SESSION['role_id'] < $minLevel) {
    header('HTTP/1.1 403 Forbidden');
    exit('You do not have permission to view this page.');
  }
}

/**
 * Convenient check for rendering links or UI.
 */
function allow_if(int $minLevel): bool {
  return !empty($_SESSION['role_id']) && $_SESSION['role_id'] >= $minLevel;
}
