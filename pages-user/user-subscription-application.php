<?php
// pages/subscription.php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../includes/session-init.php';

// Fetch available membership tiers
$tiers = $pdo->query("
    SELECT * FROM membership_types
    ORDER BY price ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get current user's membership
$currentMembership = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT m.*, mt.type_name, mt.price 
        FROM memberships m
        JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
        WHERE m.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $currentMembership = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Choose Your Bunniwinkle Membership</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/user-subscription.css">
    <style>
        :root {
            --primary-color: rgb(144, 139, 153);
            --secondary-color: #8D72E1;
            --accent-color: #B9E0FF;
        }

        .membership-slider {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 2rem 0;
        }

        .tier-card {
            background: white;
            border-radius: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .tier-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .tier-card.active {
            border: 3px solid var(--primary-color);
            transform: scale(1.05);
        }

        .price-badge {
            font-size: 2.5rem;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem 2rem;
            border-radius: 0 0 1rem 1rem;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
        }

        .benefits-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .benefits-list li:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <?php include '../includes/user-navbar.php'; ?>

    <div class="membership-slider">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold mb-3">Join the Bunniwinkle Club</h1>
                <p class="lead text-muted">Unlock exclusive content and benefits</p>

                <?php if ($currentMembership): ?>
                    <div class="alert alert-info d-inline-flex align-items-center">
                        <i class="bi bi-stars me-2"></i>
                        Current Plan: <?= htmlspecialchars($currentMembership['type_name']) ?>
                        (₱<?= number_format($currentMembership['price'], 2) ?>/mo)
                    </div>
                <?php endif; ?>
            </div>

            <div class="row g-4 justify-content-center">
                <?php foreach ($tiers as $tier): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="tier-card card h-100 <?= $currentMembership && $currentMembership['membership_type_id'] == $tier['membership_type_id'] ? 'active' : '' ?>"
                            style="border-top: 5px solid <?= $tier['can_access_exclusive'] ? '#6C4AB6' : '#8D72E1' ?>">
                            <div class="price-badge text-center">
                                ₱<?= number_format($tier['price'], 2) ?>
                                <div class="fs-6">per month</div>
                            </div>
                            <div class="card-body p-4">
                                <h3 class="card-title mb-4">
                                    <?= htmlspecialchars($tier['type_name']) ?>
                                    <?php if ($tier['can_access_exclusive']): ?>
                                        <i class="bi bi-patch-check-fill text-primary"></i>
                                    <?php endif; ?>
                                </h3>

                                <ul class="benefits-list">
                                    <?php $benefits = explode("\n", $tier['description']); ?>
                                    <?php foreach ($benefits as $benefit): ?>
                                        <li>
                                            <i class="bi bi-check2-circle me-2 text-success"></i>
                                            <?= strip_tags(trim($benefit), '<strong><em><span><b><i>') ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <button class="btn btn-lg btn-primary w-100 mt-4 subscribe-btn"
                                    data-tier-id="<?= $tier['membership_type_id'] ?>"
                                    data-tier-name="<?= htmlspecialchars($tier['type_name']) ?>"
                                    data-tier-price="<?= $tier['price'] ?>">
                                    <?= $currentMembership && $currentMembership['membership_type_id'] == $tier['membership_type_id'] ?
                                        'Current Plan' : 'Choose Plan' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Subscription</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="subscriptionForm" method="POST" action="/pages/ajax/process_subscription.php">
                    <div class="modal-body">
                        <div class="alert alert-primary">
                            You're subscribing to: <strong><span id="selectedTierName"></span></strong><br>
                            Price: <strong>₱<span id="selectedTierPrice"></span>/month</strong>
                        </div>

                        <input type="hidden" name="membership_type_id" id="selectedTierId">

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="gcash">GCash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>

                        <div id="paymentDetails">
                            <!-- Dynamic payment fields will be inserted here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.subscribe-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.textContent === 'Current Plan') return;

                const modal = new bootstrap.Modal('#paymentModal');
                document.getElementById('selectedTierName').textContent = btn.dataset.tierName;
                document.getElementById('selectedTierPrice').textContent = btn.dataset.tierPrice;
                document.getElementById('selectedTierId').value = btn.dataset.tierId;
                modal.show();
            });
        });

        // Payment method dynamic form
        document.querySelector('[name="payment_method"]').addEventListener('change', function() {
            const paymentDetails = {
                'gcash': `
                    <div class="mb-3">
                        <label class="form-label">GCash Number</label>
                        <input type="tel" class="form-control" name="gcash_number" 
                            pattern="09[0-9]{9}" placeholder="09123456789" required>
                    </div>`,
                'credit_card': `
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" name="card_number" 
                                pattern="[0-9]{16}" placeholder="4242 4242 4242 4242" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="month" class="form-control" name="expiry" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CVC</label>
                            <input type="text" class="form-control" name="cvc" 
                                pattern="[0-9]{3}" placeholder="123" required>
                        </div>
                    </div>`,
                'paypal': `
                    <div class="alert alert-info">
                        You will be redirected to PayPal after confirmation
                    </div>`
            };

            document.getElementById('paymentDetails').innerHTML =
                paymentDetails[this.value] || '';
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize modal
            const paymentModal = new bootstrap.Modal('#paymentModal');

            // Handle subscription button clicks
            document.querySelectorAll('.subscribe-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.textContent === 'Current Plan') return;

                    document.getElementById('selectedTierName').textContent = btn.dataset.tierName;
                    document.getElementById('selectedTierPrice').textContent = btn.dataset.tierPrice;
                    document.getElementById('selectedTierId').value = btn.dataset.tierId;
                    paymentModal.show();
                });
            });

            // Payment method dynamic form
            document.querySelector('[name="payment_method"]').addEventListener('change', function() {
                const paymentDetails = {
                    'gcash': `
                <div class="mb-3">
                    <label class="form-label">GCash Number</label>
                    <input type="tel" class="form-control" name="gcash_number" 
                        placeholder="09123456789">
                </div>`,
                    'credit_card': `
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Card Number</label>
                        <input type="text" class="form-control" name="card_number" 
                            placeholder="4242 4242 4242 4242">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expiry Date</label>
                        <input type="month" class="form-control" name="expiry">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CVC</label>
                        <input type="text" class="form-control" name="cvc" 
                            placeholder="123">
                    </div>
                </div>`,
                    'paypal': `
                <div class="alert alert-info">
                    You will be redirected to PayPal after confirmation
                </div>`
                };

                document.getElementById('paymentDetails').innerHTML =
                    paymentDetails[this.value] || '';
            });

            // Handle subscription form submission
            const subscriptionForm = document.getElementById('subscriptionForm');
            subscriptionForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Create loading overlay
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center';
                loadingOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                loadingOverlay.style.zIndex = '9999';
                loadingOverlay.innerHTML = `
        <div class="card p-4 shadow">
            <div class="text-center mb-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <h5 class="text-center">Processing your subscription...</h5>
        </div>
    `;
                document.body.appendChild(loadingOverlay);

                // Hide the payment modal
                paymentModal.hide();

                // Get form data
                const formData = new FormData(subscriptionForm);

                // Send AJAX request
                fetch('/pages/ajax/process_subscription.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json()) // Parse as JSON instead of text
                    .then(data => {
                        // Remove loading overlay after a minimum of 1.5 seconds (for realism)
                        setTimeout(() => {
                            document.body.removeChild(loadingOverlay);

                            // Create success modal
                            const successModal = document.createElement('div');
                            successModal.className = 'modal fade';
                            successModal.id = 'successModal';
                            successModal.setAttribute('tabindex', '-1');
                            successModal.setAttribute('aria-hidden', 'true');

                            if (data.success) {
                                // Success case
                                const tierName = data.data.tier.name;
                                const tierPrice = data.data.tier.price;

                                successModal.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background: linear-gradient(to right,rgb(127, 167, 197),rgb(202, 162, 201));">
                                <h5 class="modal-title text-white">Subscription Successful</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class='membership-success' data-membership-type='${tierName}'>
                                    <div class='alert alert-success'>
                                        <h4><i class='bi bi-check-circle-fill'></i> Welcome to ${tierName}!</h4>
                                        <p>Your subscription is now active.</p>
                                        <hr>
                                        <ul class='mb-0'>
                                            <li>Plan: <strong>${tierName}</strong></li>
                                            <li>Price: <strong>₱${tierPrice}/month</strong></li>
                                            <li>Start Date: <strong>${data.data.dates.start}</strong></li>
                                            <li>Expiry Date: <strong>${data.data.dates.expiry}</strong></li>
                                            <li>Payment Method: <strong>${data.data.payment.method.charAt(0).toUpperCase() + data.data.payment.method.slice(1)}</strong></li>
                                            <li>Reference: <code>${data.data.payment.reference}</code></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="window.location.href='/pages-user/profile.php'">
                                    Go to Profile
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                            } else {
                                // Error case
                                successModal.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">Subscription Failed</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class='alert alert-danger'>
                                    <h4><i class='bi bi-exclamation-triangle-fill'></i> Subscription Error</h4>
                                    <p>We couldn't process your subscription.</p>
                                    <p class='mb-0'><small>Error: ${data.error || 'Unknown error'}</small></p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="window.location.reload()">Try Again</button>
                            </div>
                        </div>
                    </div>
                `;
                            }

                            document.body.appendChild(successModal);
                            const bsSuccessModal = new bootstrap.Modal('#successModal');
                            bsSuccessModal.show();

                            // Remove the modal from DOM when hidden
                            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                                document.body.removeChild(successModal);
                                if (data.success) {
                                    window.location.href = '/pages-user/profile.php';
                                }
                            });

                            // Refresh the current membership display if success
                            if (data.success) {
                                const tierName = data.data.tier.name;
                                const tierPrice = data.data.tier.price;

                                const membershipAlert = document.querySelector('.alert-info');
                                if (membershipAlert) {
                                    membershipAlert.innerHTML = `
                        <i class="bi bi-stars me-2"></i>
                        Current Plan: ${tierName} 
                        (₱${tierPrice}/month)
                    `;
                                }

                                // Update the active class on membership cards
                                document.querySelectorAll('.tier-card').forEach(card => {
                                    card.classList.remove('active');
                                    const cardTitle = card.querySelector('.card-title').textContent.trim();
                                    if (cardTitle.includes(tierName)) {
                                        card.classList.add('active');
                                        card.querySelector('.subscribe-btn').textContent = 'Current Plan';
                                    }
                                });
                            }
                        }, 1500);
                    })
                    .catch(error => {
                        // Remove loading overlay
                        document.body.removeChild(loadingOverlay);

                        // Show error alert
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                        errorAlert.style.zIndex = '9999';
                        errorAlert.innerHTML = `
            <strong>Error!</strong> Could not process subscription: ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
                        document.body.appendChild(errorAlert);

                        // Remove alert after 5 seconds
                        setTimeout(() => {
                            if (document.body.contains(errorAlert)) {
                                document.body.removeChild(errorAlert);
                            }
                        }, 5000);
                    });
            });
        });
    </script>
</body>

</html>