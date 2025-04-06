<?php
// Initialize session and DB connection
require_once __DIR__ . '/../includes/session-init.php';
require_once '../config/db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../pages/login.php?redirect=profile");
    exit;
}

// Get user data
$user = [];
try {
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name 
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error loading profile: " . $e->getMessage();
}

// Handle form submissions (profile update, password change, email change)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: profile.php");
        exit;
    }

    // Profile update
    if (isset($_POST['update_profile'])) {
        $name = htmlspecialchars(trim($_POST['name']));
        $phone = htmlspecialchars(trim($_POST['phone']));
        $address = htmlspecialchars(trim($_POST['address']));

        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, phone = ?, address = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$name, $phone, $address, $_SESSION['user_id']]);
            $_SESSION['success'] = "Profile updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
        }
    }

    // Password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $db_password = $stmt->fetchColumn();

            if (!password_verify($current_password, $db_password)) {
                $_SESSION['error'] = "Current password is incorrect";
            } elseif ($new_password !== $confirm_password) {
                $_SESSION['error'] = "New passwords don't match";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $_SESSION['success'] = "Password changed successfully!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error changing password: " . $e->getMessage();
        }
    }

    // Email change
    if (isset($_POST['change_email'])) {
        $new_email = filter_var($_POST['new_email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];

        if (!$new_email) {
            $_SESSION['error'] = "Please enter a valid email address";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $db_password = $stmt->fetchColumn();

                if (!password_verify($password, $db_password)) {
                    $_SESSION['error'] = "Password is incorrect";
                } else {
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                    $stmt->execute([$new_email]);
                    if ($stmt->fetch()) {
                        $_SESSION['error'] = "Email already in use";
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                        $stmt->execute([$new_email, $_SESSION['user_id']]);
                        $_SESSION['success'] = "Email changed successfully!";
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error changing email: " . $e->getMessage();
            }
        }
    }

    header("Location: profile.php");
    exit;
}

// Flash messages
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BunniShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body>
    <!-- Include the user navbar; ensure its logout triggers are updated -->
    <?php include '../includes/user-navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <img src="../assets/images/default-avatar.jpg" alt="Profile" class="profile-avatar">
                <h2><?= htmlspecialchars($user['name'] ?? 'User') ?></h2>
                <p class="mb-0"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                <small class="text-white-50">Member since <?= date('F Y', strtotime($user['created_at'])) ?></small>
            </div>

            <!-- Flash Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger m-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success m-3"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Tab navigation -->
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                        <i class="fas fa-user me-2"></i>Profile
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                        <i class="fas fa-lock me-2"></i>Password
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                        <i class="fas fa-envelope me-2"></i>Email
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="profileTabsContent">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <form method="POST" action="profile.php">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email (cannot be changed here)</label>
                                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Type</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['role_name'] ?? 'Customer') ?>" disabled>
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
                <div class="tab-pane fade" id="password" role="tabpanel">
                    <div class="security-alert">
                        <i class="fas fa-shield-alt me-2"></i>
                        For security, please don't share your password with anyone.
                    </div>
                    <form method="POST" action="profile.php">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Minimum 8 characters</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>

                <!-- Email Tab -->
                <div class="tab-pane fade" id="email" role="tabpanel">
                    <div class="security-alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Changing your email will affect your login credentials.
                    </div>
                    <form method="POST" action="profile.php">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="mb-3">
                            <label for="current_email" class="form-label">Current Email</label>
                            <input type="email" class="form-control" id="current_email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="new_email" class="form-label">New Email</label>
                            <input type="email" class="form-control" id="new_email" name="new_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Enter your password to confirm this change</div>
                        </div>
                        <button type="submit" name="change_email" class="btn btn-primary">Change Email</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal (direct child of body) -->
    <div class="logout-confirm" id="logoutConfirm" style="display:none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
        <div class="logout-dialog" style="background: white; padding: 2rem; border-radius: 8px; max-width: 400px; width: 90%; text-align: center;">
            <h3>Are you sure you want to logout?</h3>
            <p>You'll need to sign in again to access your account.</p>
            <div class="logout-actions" style="display: flex; justify-content: center; gap: 1rem; margin-top: 1.5rem;">
                <button class="logout-btn logout-cancel-btn" id="logoutCancel" style="padding: 0.5rem 1.5rem; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <a href="../pages/logout.php" class="logout-btn logout-confirm-btn" style="padding: 0.5rem 1.5rem; border: none; border-radius: 4px; background: #e74c3c; color: white; text-decoration: none;">Logout</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Enable Bootstrap tabs -->
    <script>
        const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(tabEl => {
            tabEl.addEventListener('click', function(event) {
                event.preventDefault();
                const tab = new bootstrap.Tab(this);
                tab.show();
            });
        });
    </script>
    <!-- Logout Modal Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutConfirm = document.getElementById('logoutConfirm');
            // Get logout triggers - ensure these are buttons or anchors with href="javascript:void(0)" in user-navbar.php
            const logoutButtons = [
                document.getElementById('navLogout'),
                document.getElementById('mobileLogout')
            ].filter(btn => btn);

            logoutButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    logoutConfirm.style.display = 'flex';
                });
            });

            document.getElementById('logoutCancel').addEventListener('click', function() {
                logoutConfirm.style.display = 'none';
            });

            logoutConfirm.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
