<?php
/**
 * Revenue Calculation Fix for Dashboard
 * This file provides corrected revenue calculations and analysis
 */

require_once 'includes/config.php';
require_once 'includes/projects.php';
require_once 'includes/pos.php';

echo "<h1>🔍 Revenue Calculation Analysis</h1>";
echo "<p><strong>Analysis Date:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // Get current calculations from dashboard
    $project_stats = getProjectStats();
    $pos_stats = getPOSStats(date('Y-m-d'), date('Y-m-d')); // Today's POS stats
    
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>❌ Current Dashboard Issue</h2>";
    echo "<p><strong>Current Total Revenue Calculation:</strong></p>";
    echo "<code>Project Revenue (All-Time) + POS Revenue (Today Only)</code>";
    echo "<p><strong>Result:</strong> " . formatCurrency($project_stats['total_revenue'] + $pos_stats['today_revenue']) . "</p>";
    echo "<ul>";
    echo "<li>Project Revenue (All-Time): " . formatCurrency($project_stats['total_revenue']) . "</li>";
    echo "<li>POS Revenue (Today Only): " . formatCurrency($pos_stats['today_revenue']) . "</li>";
    echo "</ul>";
    echo "<p><strong>Problem:</strong> Mixing all-time projects with today's POS sales gives inaccurate totals!</p>";
    echo "</div>";

    // Corrected calculations
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>✅ Corrected Revenue Calculations</h2>";
    
    // Option 1: All-time totals
    $pos_stats_all_time = getPOSStats(); // All-time POS stats
    $total_revenue_all_time = $project_stats['total_revenue'] + $pos_stats_all_time['total_revenue'];
    
    echo "<h3>Option 1: All-Time Total Revenue</h3>";
    echo "<ul>";
    echo "<li>Project Revenue (All-Time): " . formatCurrency($project_stats['total_revenue']) . "</li>";
    echo "<li>POS Revenue (All-Time): " . formatCurrency($pos_stats_all_time['total_revenue']) . "</li>";
    echo "<li><strong>Corrected Total Revenue (All-Time): " . formatCurrency($total_revenue_all_time) . "</strong></li>";
    echo "</ul>";
    
    // Option 2: Today's totals
    echo "<h3>Option 2: Today's Total Revenue</h3>";
    echo "<ul>";
    $today_projects = getTodayProjectRevenue();
    echo "<li>Project Revenue (Today): " . formatCurrency($today_projects) . "</li>";
    echo "<li>POS Revenue (Today): " . formatCurrency($pos_stats['today_revenue']) . "</li>";
    echo "<li><strong>Today's Total Revenue: " . formatCurrency($today_projects + $pos_stats['today_revenue']) . "</strong></li>";
    echo "</ul>";
    
    // Option 3: This month's totals
    $pos_stats_month = getPOSStats(date('Y-m-01'), date('Y-m-d')); // This month
    $month_projects = getMonthProjectRevenue();
    echo "<h3>Option 3: This Month's Total Revenue</h3>";
    echo "<ul>";
    echo "<li>Project Revenue (This Month): " . formatCurrency($month_projects) . "</li>";
    echo "<li>POS Revenue (This Month): " . formatCurrency($pos_stats_month['total_revenue']) . "</li>";
    echo "<li><strong>This Month's Total Revenue: " . formatCurrency($month_projects + $pos_stats_month['total_revenue']) . "</strong></li>";
    echo "</ul>";
    echo "</div>";

    // Detailed breakdown
    echo "<div style='background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>📊 Detailed Revenue Breakdown</h2>";
    
    echo "<h3>Project Revenue by Status:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Status</th><th>Count</th><th>Revenue</th></tr>";
    
    // Get project revenue by status
    global $pdo;
    $stmt = $pdo->query("SELECT project_status, COUNT(*) as count, SUM(final_amount) as revenue 
                        FROM solar_projects 
                        GROUP BY project_status 
                        ORDER BY project_status");
    $project_breakdown = $stmt->fetchAll();
    
    foreach ($project_breakdown as $row) {
        echo "<tr>";
        echo "<td>" . ucfirst($row['project_status']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . formatCurrency($row['revenue'] ?: 0) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>POS Sales by Payment Method:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Payment Method</th><th>Count</th><th>Revenue</th></tr>";
    
    foreach ($pos_stats_all_time['by_payment_method'] as $payment) {
        echo "<tr>";
        echo "<td>" . ucfirst($payment['payment_method'] ?: 'Unknown') . "</td>";
        echo "<td>" . $payment['count'] . "</td>";
        echo "<td>" . formatCurrency($payment['total'] ?: 0) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // Recommendations
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>💡 Recommendations</h2>";
    echo "<h3>Dashboard Display Options:</h3>";
    echo "<ol>";
    echo "<li><strong>Show All-Time Total:</strong> Best for overall business performance tracking</li>";
    echo "<li><strong>Show Multiple Cards:</strong> Separate cards for today, month, and all-time totals</li>";
    echo "<li><strong>Add Time Filter:</strong> Allow users to select date range for revenue calculation</li>";
    echo "</ol>";
    
    echo "<h3>Suggested Dashboard Layout:</h3>";
    echo "<ul>";
    echo "<li><strong>Today's Revenue:</strong> Projects + POS for today</li>";
    echo "<li><strong>This Month's Revenue:</strong> Projects + POS for current month</li>";
    echo "<li><strong>All-Time Revenue:</strong> Projects + POS total</li>";
    echo "<li><strong>Yesterday's Revenue:</strong> For comparison</li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h2>Error</h2>";
    echo "<p>Error analyzing revenue: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Helper functions for corrected calculations
function getTodayProjectRevenue() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(final_amount) as revenue 
                          FROM solar_projects 
                          WHERE project_status IN ('approved', 'completed') 
                          AND DATE(created_at) = CURDATE()");
    $stmt->execute();
    return $stmt->fetchColumn() ?: 0;
}

function getMonthProjectRevenue() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(final_amount) as revenue 
                          FROM solar_projects 
                          WHERE project_status IN ('approved', 'completed') 
                          AND YEAR(created_at) = YEAR(CURDATE()) 
                          AND MONTH(created_at) = MONTH(CURDATE())");
    $stmt->execute();
    return $stmt->fetchColumn() ?: 0;
}

echo "<p><a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Dashboard</a> ";
echo "<a href='fix_dashboard_revenue.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Apply Fix</a></p>";
?>
