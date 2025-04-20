<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Fetch membership tiers excluding Free
$stmt = $pdo->query("SELECT * FROM membership_types WHERE membership_type_id > 1");
$tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bunniwinkle Memberships</title>
    <link rel="stylesheet" href="../assets/css/homepage.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/user-navbar.php'; ?>

    <section class="container py-5 membership-section">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold">Choose Your Membership</h2>
            <p class="lead">Unlock exclusive content and perks</p>
        </div>

        <div class="row g-4">
            <?php foreach ($tiers as $tier): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card tier-card h-100">
                    <div class="card-header bg-pink text-white py-4">
                        <h3 class="mb-0"><?= htmlspecialchars($tier['type_name']) ?></h3>
                        <div class="price display-4 fw-bold mt-3">
                            ₱<?= number_format($tier['price'], 2) ?>
                            <small class="text-muted fs-6">/month</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-pink me-2"></i>
                                Exclusive product access
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-pink me-2"></i>
                                Member-only discounts
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-pink me-2"></i>
                                Early access to new collections
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 py-4">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="process_subscription.php" method="POST">
                                <input type="hidden" name="tier_id" value="<?= $tier['membership_type_id'] ?>">
                                <button type="submit" class="shop-btn w-100">
                                    Subscribe Now
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="shop-btn w-100">
                                Login to Subscribe
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</body>
</html>