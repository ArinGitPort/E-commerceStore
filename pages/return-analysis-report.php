<?php
// returns-analysis-report.php

// Include required files
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../includes/session-init.php';

// Date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Make sure $conn is available globally - add this if your db_connection.php doesn't make it global
global $conn;

// If $conn is still null, try to recreate the connection
if (!isset($conn)) {
    // This is a fallback connection code - adjust according to your actual db_connection.php implementation
    $conn = new mysqli("localhost", "root", "1234", "bunnishop");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Get return statistics
function getReturnStats($start_date, $end_date) {
    global $conn;
    
    $query = "
        SELECT 
            COUNT(r.return_id) AS total_returns,
            SUM(CASE WHEN r.return_status = 'Approved' THEN 1 ELSE 0 END) AS approved_returns,
            SUM(CASE WHEN r.return_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_returns,
            SUM(CASE WHEN r.return_status = 'Pending' THEN 1 ELSE 0 END) AS pending_returns
        FROM returns r
        WHERE r.return_date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ['total_returns' => 0, 'approved_returns' => 0, 'rejected_returns' => 0, 'pending_returns' => 0];
    }
    
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get most returned products
function getMostReturnedProducts($start_date, $end_date, $limit = 10) {
    global $conn;
    
    $query = "
        SELECT 
            p.product_id,
            p.product_name,
            p.sku,
            c.category_name,
            SUM(ri.quantity) AS return_quantity,
            COUNT(DISTINCT r.return_id) AS return_count
        FROM return_items ri
        JOIN returns r ON ri.return_id = r.return_id
        JOIN products p ON ri.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE r.return_date BETWEEN ? AND ?
        GROUP BY p.product_id
        ORDER BY return_quantity DESC
        LIMIT ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("ssi", $start_date, $end_date, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Get return reasons
function getReturnReasons($start_date, $end_date) {
    global $conn;
    
    $query = "
        SELECT 
            reason,
            COUNT(*) AS count
        FROM returns
        WHERE return_date BETWEEN ? AND ?
            AND reason IS NOT NULL
        GROUP BY reason
        ORDER BY count DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result();
}

// Get return trends by date
function getReturnTrends($start_date, $end_date) {
    global $conn;
    
    $query = "
        SELECT 
            DATE(return_date) AS date,
            COUNT(*) AS return_count
        FROM returns
        WHERE return_date BETWEEN ? AND ?
        GROUP BY DATE(return_date)
        ORDER BY date";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result();
}

// Get returns by product condition
function getReturnsByCondition($start_date, $end_date) {
    global $conn;
    
    $query = "
        SELECT 
            ri.item_condition,
            COUNT(*) AS count
        FROM return_items ri
        JOIN returns r ON ri.return_id = r.return_id
        WHERE r.return_date BETWEEN ? AND ?
        GROUP BY ri.item_condition
        ORDER BY count DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result();
}

// Get stats by membership type
function getReturnsByMembership($start_date, $end_date) {
    global $conn;
    
    $query = "
        SELECT 
            mt.type_name AS membership_type,
            COUNT(r.return_id) AS return_count
        FROM returns r
        JOIN archived_orders ao ON r.archived_order_id = ao.order_id
        JOIN users u ON ao.customer_id = u.user_id
        JOIN memberships m ON u.user_id = m.user_id
        JOIN membership_types mt ON m.membership_type_id = mt.membership_type_id
        WHERE r.return_date BETWEEN ? AND ?
        GROUP BY mt.type_name
        ORDER BY return_count DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result();
}

try {
    // Execute queries
    $stats = getReturnStats($start_date, $end_date);
    $top_returned_products = getMostReturnedProducts($start_date, $end_date);
    $return_reasons = getReturnReasons($start_date, $end_date);
    $return_trends = getReturnTrends($start_date, $end_date);
    $return_conditions = getReturnsByCondition($start_date, $end_date);
    $returns_by_membership = getReturnsByMembership($start_date, $end_date);

    // Prepare data for charts
    $trend_dates = [];
    $trend_counts = [];
    if ($return_trends && $return_trends instanceof mysqli_result) {
        while ($row = $return_trends->fetch_assoc()) {
            $trend_dates[] = $row['date'];
            $trend_counts[] = $row['return_count'];
        }
        $return_trends->data_seek(0);
    }

    $reason_labels = [];
    $reason_counts = [];
    if ($return_reasons && $return_reasons instanceof mysqli_result) {
        while ($row = $return_reasons->fetch_assoc()) {
            $reason_labels[] = $row['reason'];
            $reason_counts[] = $row['count'];
        }
        $return_reasons->data_seek(0);
    }

    $condition_labels = [];
    $condition_counts = [];
    if ($return_conditions && $return_conditions instanceof mysqli_result) {
        while ($row = $return_conditions->fetch_assoc()) {
            $condition_labels[] = $row['item_condition'];
            $condition_counts[] = $row['count'];
        }
        $return_conditions->data_seek(0);
    }

    $membership_labels = [];
    $membership_counts = [];
    if ($returns_by_membership && $returns_by_membership instanceof mysqli_result) {
        while ($row = $returns_by_membership->fetch_assoc()) {
            $membership_labels[] = $row['membership_type'];
            $membership_counts[] = $row['return_count'];
        }
        $returns_by_membership->data_seek(0);
    }
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bunniwinkle - Returned Report</title>
    <link rel="stylesheet" href="../assets/css/admin-styles.css">
    <link rel="stylesheet" href="../assets/css/return-analysis-report.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="content">
            
            <!-- Return Summary Cards -->
            <div class="summary-section">
                <div class="summary-cards">
                    <div class="card">
                        <h3>Total Returns</h3>
                        <p class="number"><?php echo $stats['total_returns'] ?? 0; ?></p>
                    </div>
                    <div class="card">
                        <h3>Approved Returns</h3>
                        <p class="number green"><?php echo $stats['approved_returns'] ?? 0; ?></p>
                    </div>
                    <div class="card">
                        <h3>Rejected Returns</h3>
                        <p class="number red"><?php echo $stats['rejected_returns'] ?? 0; ?></p>
                    </div>
                    <div class="card">
                        <h3>Pending Returns</h3>
                        <p class="number yellow"><?php echo $stats['pending_returns'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Main Charts Section -->
            <div class="charts-grid">
                <!-- Row 1: Two larger charts side by side -->
                <div class="chart-row main-charts">
                    <div class="chart-container">
                        <h2>Return Trends</h2>
                        <canvas id="returnTrendsChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h2>Return Reasons</h2>
                        <canvas id="returnReasonsChart"></canvas>
                    </div>
                </div>
                
                <!-- Row 2: Two smaller charts side by side -->
                <div class="chart-row secondary-charts">
                    <div class="chart-container">
                        <h2>Returns by Product Condition</h2>
                        <canvas id="returnConditionsChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h2>Returns by Membership Type</h2>
                        <canvas id="membershipReturnsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Two tables side by side -->
            <div class="data-tables-grid">
                <div class="table-section">
                    <h2>Most Returned Products</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Return Qty</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($top_returned_products && $top_returned_products instanceof mysqli_result):
                                if ($top_returned_products->num_rows > 0):
                                    while ($product = $top_returned_products->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo $product['return_quantity']; ?></td>
                                <td><?php echo $product['return_count']; ?></td>
                            </tr>
                            <?php 
                                    endwhile;
                                else: 
                            ?>
                            <tr>
                                <td colspan="5" class="no-data">No returned products found.</td>
                            </tr>
                            <?php 
                                endif;
                            else: 
                            ?>
                            <tr>
                                <td colspan="5" class="no-data">Error retrieving product data.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-section">
                    <h2>Return Reasons</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reason</th>
                                <th>Count</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($return_reasons && $return_reasons instanceof mysqli_result):
                                if ($return_reasons->num_rows > 0):
                                    $total_reasons = array_sum($reason_counts);
                                    $return_reasons->data_seek(0);
                                    while ($reason = $return_reasons->fetch_assoc()):
                                        $percentage = $total_reasons > 0 ? round(($reason['count'] / $total_reasons) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reason['reason']); ?></td>
                                <td><?php echo $reason['count']; ?></td>
                                <td><?php echo $percentage; ?>%</td>
                            </tr>
                            <?php 
                                    endwhile;
                                else: 
                            ?>
                            <tr>
                                <td colspan="3" class="no-data">No return reasons found.</td>
                            </tr>
                            <?php 
                                endif;
                            else: 
                            ?>
                            <tr>
                                <td colspan="3" class="no-data">Error retrieving reason data.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Line chart for return trends
        const trendsCtx = document.getElementById('returnTrendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_dates); ?>,
                datasets: [{
                    label: 'Number of Returns',
                    data: <?php echo json_encode($trend_counts); ?>,
                    borderColor: '#ff9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
        
        // Bar chart for return reasons
        const reasonsCtx = document.getElementById('returnReasonsChart').getContext('2d');
        new Chart(reasonsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($reason_labels); ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode($reason_counts); ?>,
                    backgroundColor: '#3f51b5',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
        
        // Pie chart for product conditions
        const conditionsCtx = document.getElementById('returnConditionsChart').getContext('2d');
        new Chart(conditionsCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($condition_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($condition_counts); ?>,
                    backgroundColor: [
                        '#4CAF50',  // New
                        '#FFC107',  // Opened
                        '#F44336'   // Damaged
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
        
        // Doughnut chart for membership types
        const membershipCtx = document.getElementById('membershipReturnsChart').getContext('2d');
        new Chart(membershipCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($membership_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($membership_counts); ?>,
                    backgroundColor: [
                        '#2196F3',  // Free
                        '#9C27B0',  // Dreamy Nook
                        '#E91E63',  // Secret Paper Stash
                        '#00BCD4',  // Crafty Wonderland
                        '#CDDC39',  // Little Charm Box
                        '#FF5722'   // Bunni's Enchanted Garden
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    </script>
</body>
</html>