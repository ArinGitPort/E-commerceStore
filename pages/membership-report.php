<?php
require_once __DIR__ . '/../includes/session-init.php';
require_once __DIR__ . '/../config/db_connection.php';

// Get membership distribution
$membership_query = "SELECT 
                       mt.type_name, 
                       COUNT(m.membership_id) AS member_count,
                       mt.price AS price_per_member,
                       COUNT(m.membership_id) * mt.price AS projected_revenue
                     FROM membership_types mt
                     LEFT JOIN memberships m ON mt.membership_type_id = m.membership_type_id
                     WHERE m.expiry_date IS NULL OR m.expiry_date >= CURDATE()
                     GROUP BY mt.membership_type_id
                     ORDER BY mt.price DESC";
$stmt = $pdo->prepare($membership_query);
$stmt->execute();
$membership_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get membership growth (sign-ups per month)
$growth_query = "SELECT 
                   DATE_FORMAT(m.start_date, '%Y-%m') AS month,
                   COUNT(*) AS new_members
                 FROM memberships m
                 WHERE m.start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                 GROUP BY month
                 ORDER BY month";
$stmt = $pdo->prepare($growth_query);
$stmt->execute();
$growth_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary metrics
$total_members = array_sum(array_column($membership_data, 'member_count'));
$total_revenue = array_sum(array_column($membership_data, 'projected_revenue'));
$paid_members = 0;
foreach ($membership_data as $row) {
    if ($row['price_per_member'] > 0) {
        $paid_members += $row['member_count'];
    }
}
$paid_percentage = $total_members ? round(($paid_members / $total_members) * 100) : 0;

// Get recent subscription payments
$recent_payments_query = "SELECT 
                            u.name, 
                            mt.type_name, 
                            sa.payment_amount,
                            sa.payment_date
                          FROM subscriptions_audit sa
                          JOIN users u ON sa.user_id = u.user_id
                          JOIN membership_types mt ON sa.membership_type_id = mt.membership_type_id
                          WHERE sa.payment_status = 'completed'
                          ORDER BY sa.payment_date DESC
                          LIMIT 10";
$stmt = $pdo->prepare($recent_payments_query);
$stmt->execute();
$recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<?php include '../includes/sidebar.php'; ?>

<body class="bg-light">
    <div class="container-fluid p-4">

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Members</h5>
                        <h2><?= $total_members ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Paid Membership</h5>
                        <h2><?= $paid_percentage ?>%</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Revenue</h5>
                        <h2>$<?= number_format($total_revenue, 2) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Membership Distribution Chart -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Membership Distribution</h5>
                    </div>
                    <div class="card-body">
                        <!-- Added container with aspect ratio -->
                        <div class="ratio ratio-16x9">
                            <canvas id="membershipChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Growth Chart -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Membership Growth (Last 12 Months)</h5>
                    </div>
                    <div class="card-body">
                        <!-- Added container with aspect ratio -->
                        <div class="ratio ratio-16x9">
                            <canvas id="growthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Membership Tiers Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Membership Tiers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tier Name</th>
                                    <th>Members</th>
                                    <th>Price</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($membership_data as $tier): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tier['type_name']) ?></td>
                                        <td><?= $tier['member_count'] ?></td>
                                        <td>₱<?= number_format($tier['price_per_member'], 2) ?></td>
                                        <td>₱<?= number_format($tier['projected_revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($membership_data)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No membership data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Subscriptions -->
            <div class="card">
                <div class="card h-100">
                    <h5 class="card-title mb-0">Recent Subscription Payments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Membership Type</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['name']) ?></td>
                                        <td><?= htmlspecialchars($payment['type_name']) ?></td>
                                        <td>$<?= number_format($payment['payment_amount'], 2) ?></td>
                                        <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_payments)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No recent payments</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Membership Distribution Chart
            const membershipCtx = document.getElementById('membershipChart').getContext('2d');
            new Chart(membershipCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_column($membership_data, 'type_name')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($membership_data, 'member_count')) ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });

            // Growth Chart
            const growthCtx = document.getElementById('growthChart').getContext('2d');
            new Chart(growthCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($growth_data, 'month')) ?>,
                    datasets: [{
                        label: 'New Members',
                        data: <?= json_encode(array_column($growth_data, 'new_members')) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'New Member Count'
                            }
                        }
                    }
                }
            });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>