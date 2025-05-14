<?php
// pages-user/profile.php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../pages/login.php?redirect=profile");
    exit;
}

// Fetch user + membership info
$stmt = $pdo->prepare(
    <<<SQL
    SELECT u.*, r.role_name, mt.type_name AS membership_type, m.start_date, m.expiry_date
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    LEFT JOIN memberships m ON u.user_id = m.user_id
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
    WHERE u.user_id = ?
SQL
);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch() ?: [];

// Check if the user is authenticated via OAuth
$isOAuthUser = !empty($user['oauth_provider']);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['profile_error'] = "Invalid CSRF token.";
        header("Location: profile.php");
        exit;
    }

    $redirectHash = ''; // Initialize redirect hash

    // Profile Update
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        if ($name === '') {
            $_SESSION['profile_error'] = "Name cannot be empty.";
        } else {
            $upd = $pdo->prepare("UPDATE users SET name=?, phone=?, address=? WHERE user_id=?");
            $upd->execute([$name, $phone, $address, $_SESSION['user_id']]);
            $_SESSION['profile_success'] = "Profile updated.";
        }
        $redirectHash = '#profile';
    }

    // Password Change
    if (isset($_POST['change_password'])) {
        // Block OAuth users from changing password
        if ($isOAuthUser) {
            $_SESSION['password_error'] = "Password change is not available for accounts connected via " . htmlspecialchars($user['oauth_provider']) . ".";
        } else {
            $cur = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $conf = $_POST['confirm_password'] ?? '';

            $h = $pdo->prepare("SELECT password FROM users WHERE user_id=?");
            $h->execute([$_SESSION['user_id']]);
            $dbpw = $h->fetchColumn();

            if (!password_verify($cur, $dbpw)) {
                $_SESSION['password_error'] = "Current password incorrect.";
            } elseif (strlen($new) < 8) {
                $_SESSION['password_error'] = "New password must be at least 8 characters.";
            } elseif ($new !== $conf) {
                $_SESSION['password_error'] = "New passwords don't match.";
            } else {
                $hp = password_hash($new, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password=? WHERE user_id=?");
                $upd->execute([$hp, $_SESSION['user_id']]);
                $_SESSION['password_success'] = "Password changed.";
            }
        }
        $redirectHash = '#password';
    }

    // Email Change
    if (isset($_POST['change_email'])) {
        // Block OAuth users from changing email
        if ($isOAuthUser) {
            $_SESSION['email_error'] = "Email change is not available for accounts connected via " . htmlspecialchars($user['oauth_provider']) . ".";
        } else {
            $newEmail = filter_var($_POST['new_email'] ?? '', FILTER_VALIDATE_EMAIL);
            $pw = $_POST['password'] ?? '';

            if (!$newEmail) {
                $_SESSION['email_error'] = "Enter a valid email.";
            } else {
                $h = $pdo->prepare("SELECT password FROM users WHERE user_id=?");
                $h->execute([$_SESSION['user_id']]);
                $dbpw = $h->fetchColumn();

                if (!password_verify($pw, $dbpw)) {
                    $_SESSION['email_error'] = "Password incorrect.";
                } else {
                    $chk = $pdo->prepare("SELECT 1 FROM users WHERE email=? AND user_id<>?");
                    $chk->execute([$newEmail, $_SESSION['user_id']]);
                    if ($chk->fetch()) {
                        $_SESSION['email_error'] = "Email already in use.";
                    } else {
                        // Update email immediately
                        $updateEmail = $pdo->prepare("UPDATE users SET email=? WHERE user_id=?");
                        if ($updateEmail->execute([$newEmail, $_SESSION['user_id']])) {
                            $_SESSION['email_success'] = "Email changed successfully to $newEmail.";
                        } else {
                            $_SESSION['email_error'] = "Failed to update your email. Please try again.";
                        }
                    }
                }
            }
        }
        $redirectHash = '#email';
    }

    // Redirect with proper hash
    header("Location: profile.php$redirectHash");
    exit;
}

// Clear flash messages
$profile_error = $_SESSION['profile_error'] ?? null;
$profile_success = $_SESSION['profile_success'] ?? null;
unset($_SESSION['profile_error'], $_SESSION['profile_success']);

$password_error = $_SESSION['password_error'] ?? null;
$password_success = $_SESSION['password_success'] ?? null;
unset($_SESSION['password_error'], $_SESSION['password_success']);

$email_error = $_SESSION['email_error'] ?? null;
$email_success = $_SESSION['email_success'] ?? null;
unset($_SESSION['email_error'], $_SESSION['email_success']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bunniwinkle - My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>

<body>
    <?php include '../includes/user-navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <h2><?= htmlspecialchars($user['name'] ?? 'User') ?></h2>
                <p class="mb-0"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                <small class="text-white-50">Member since <?= date('F Y', strtotime($user['created_at'])) ?></small>
                <?php if ($isOAuthUser): ?>
                    <div class="mt-2">
                        <span class="badge bg-primary">
                            <i class="fab fa-<?= strtolower($user['oauth_provider']) ?>"></i>
                            Connected with <?= htmlspecialchars(ucfirst($user['oauth_provider'])) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                        <i class="fas fa-user me-2"></i> Profile
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                        <i class="fas fa-lock me-2"></i> Password
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false">
                        <i class="fas fa-envelope me-2"></i> Email
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="profileTabsContent">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <?php if ($profile_error): ?><div class="alert alert-danger"><?= $profile_error ?></div><?php endif; ?>
                    <?php if ($profile_success): ?><div class="alert alert-success"><?= $profile_success ?></div><?php endif; ?>

                    <form method="POST" action="profile.php">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="profile_email" class="form-label">Email (cannot be changed here)</label>
                                <input type="email" class="form-control" id="profile_email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="number" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Type</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['role_name'] ?? 'Customer') ?>" disabled>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Membership Tier</label><br>
                                <span class="badge bg-info text-dark p-2">
                                    <?= htmlspecialchars($user['membership_type'] ?? 'None') ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Membership Validity</label>
                                <input type="text" class="form-control"
                                    value="<?= htmlspecialchars(
                                                isset($user['start_date'], $user['expiry_date'])
                                                    ? 'From ' . date('F j, Y', strtotime($user['start_date'])) .
                                                    ' to ' . date('F j, Y', strtotime($user['expiry_date']))
                                                    : 'None'
                                            ) ?>"
                                    disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
                <!-- Password Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                    <?php if ($password_error): ?><div class="alert alert-danger"><?= $password_error ?></div><?php endif; ?>
                    <?php if ($password_success): ?><div class="alert alert-success"><?= $password_success ?></div><?php endif; ?>

                    <?php if ($isOAuthUser): ?>
                        <!-- OAuth users can't change password -->
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <strong>Connected account notice:</strong>
                                Password management is handled by <?= htmlspecialchars(ucfirst($user['oauth_provider'])) ?> for connected accounts.
                            </div>
                        </div>
                        <div class="text-center py-4">
                            <i class="fab fa-<?= strtolower($user['oauth_provider']) ?> fa-4x mb-3 text-muted"></i>
                            <p>Your account is connected with <?= htmlspecialchars(ucfirst($user['oauth_provider'])) ?>.
                                Password changes must be made through your <?= htmlspecialchars(ucfirst($user['oauth_provider'])) ?> account settings.</p>
                        </div>
                    <?php else: ?>
                        <!-- Password tab banner -->
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="fas fa-shield-alt me-2"></i>
                            <div>
                                <strong>Password security reminder:</strong>
                                For your safety, never share your password with anyone.
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">At least 8 characters</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>

                        <!-- Password strength checker -->
                        <div class="password-strength mt-4">
                            <h5>Password Strength</h5>
                            <div class="progress mb-2">
                                <div class="progress-bar" id="password-strength-meter" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div id="password-feedback" class="small text-muted"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Email Tab -->
                <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                    <?php if ($email_error): ?><div class="alert alert-danger"><?= $email_error ?></div><?php endif; ?>
                    <?php if ($email_success): ?><div class="alert alert-success"><?= $email_success ?></div><?php endif; ?>

                    <?php if ($isOAuthUser): ?>
                        <!-- OAuth users can't change email -->
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <strong>Connected account notice:</strong>
                                Email management is handled by <?= htmlspecialchars(ucfirst($user['oauth_provider'])) ?> for connected accounts.
                            </div>
                        </div>
                        <div class="text-center py-4">
                            <i class="fab fa-<?= strtolower($user['oauth_provider']) ?> fa-4x mb-3 text-muted"></i>
                            <p>Your account is connected with <?= htmlspecialchars(ucfirst($user['oauth_provider'])) ?>.
                                Email changes must be made through your <?= htmlspecialchars(ucfirst($user['oauth_provider'])) ?> account settings.</p>
                        </div>
                    <?php else: ?>
                        <!-- Email tab banner -->
                        <div class="alert alert-warning d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Email change warning:</strong>
                                Changing your email will affect your login credentials.
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="mb-3">
                                <label class="form-label">Current Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="new_email" class="form-label">New Email</label>
                                <input type="email" class="form-control" id="new_email" name="new_email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Verify your identity</div>
                            </div>
                            <button type="submit" name="change_email" class="btn btn-primary">Change Email</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="logout-confirm" id="logoutConfirm" style="display:none;">
        <div class="logout-dialog">
            <h3>Logout Confirmation</h3>
            <p>You'll need to sign in again to access your account.</p>
            <div class="logout-actions">
                <button class="btn btn-secondary" id="logoutCancel">Cancel</button>
                <button class="btn btn-danger" id="logoutConfirmBtn">Logout</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="redirectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <p id="redirectMessage">Redirecting...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toggle password visibility
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');

                    // Toggle input type
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // Logout handling
            const logoutConfirm = document.getElementById('logoutConfirm');
            const confirmBtn = document.getElementById('logoutConfirmBtn');
            const cancelBtn = document.getElementById('logoutCancel');

            // Show logout modal
            ['navLogout', 'mobileLogout'].forEach(id => {
                document.getElementById(id)?.addEventListener('click', e => {
                    e.preventDefault();
                    logoutConfirm.style.display = 'flex';
                });
            });

            // Hide logout modal
            cancelBtn?.addEventListener('click', () => logoutConfirm.style.display = 'none');
            logoutConfirm?.addEventListener('click', e => {
                if (e.target === logoutConfirm) logoutConfirm.style.display = 'none';
            });

            // Handle logout
            confirmBtn?.addEventListener('click', () => {
                new bootstrap.Modal('#redirectModal').show();
                setTimeout(() => window.location.href = '/pages/logout.php', 1500);
            });

            // Tab handling
            const hash = window.location.hash;
            if (hash) {
                const tabTrigger = document.querySelector(`[data-bs-target="${hash}"]`);
                if (tabTrigger) bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
            }

            // Update URL hash when tabs change
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('click', () => {
                    window.location.hash = tab.getAttribute('data-bs-target');
                });
            });

            // Password strength meter
            const newPasswordInput = document.getElementById('new_password');
            const strengthMeter = document.getElementById('password-strength-meter');
            const feedback = document.getElementById('password-feedback');

            if (newPasswordInput && strengthMeter) {
                newPasswordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = calculatePasswordStrength(password);

                    // Update the strength meter
                    strengthMeter.style.width = strength.score * 25 + '%';

                    // Set the color based on strength
                    if (strength.score === 0) {
                        strengthMeter.className = 'progress-bar bg-danger';
                    } else if (strength.score < 2) {
                        strengthMeter.className = 'progress-bar bg-warning';
                    } else if (strength.score < 4) {
                        strengthMeter.className = 'progress-bar bg-info';
                    } else {
                        strengthMeter.className = 'progress-bar bg-success';
                    }

                    // Update feedback text
                    feedback.textContent = strength.message;
                });
            }

            // Simple password strength calculator
            function calculatePasswordStrength(password) {
                let score = 0;
                let message = '';

                if (password.length < 1) {
                    return {
                        score: 0,
                        message: ''
                    };
                }

                // Length check
                if (password.length < 8) {
                    return {
                        score: 0,
                        message: 'Password is too short'
                    };
                } else {
                    score += 1;
                }

                // Check for mixed case
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
                    score += 1;
                }

                // Check for numbers
                if (password.match(/\d/)) {
                    score += 1;
                }

                // Check for special characters
                if (password.match(/[^a-zA-Z\d]/)) {
                    score += 1;
                }

                // Set feedback message based on score
                if (score === 1) {
                    message = 'Password is weak';
                } else if (score === 2) {
                    message = 'Password is fair';
                } else if (score === 3) {
                    message = 'Password is good';
                } else if (score === 4) {
                    message = 'Password is strong';
                }

                return {
                    score,
                    message
                };
            }
        });
    </script>

</body>

</html>