<?php
/**
 * Dashboard Revenue Fix
 * This script updates the dashboard.php to show accurate revenue calculations
 */

echo "<h1>🔧 Dashboard Revenue Fix</h1>";
echo "<p>This will update the dashboard to show accurate revenue calculations.</p>";

// Read current dashboard
$dashboard_file = 'dashboard.php';
$dashboard_content = file_get_contents($dashboard_file);

if (!$dashboard_content) {
    echo "<p style='color: red;'>❌ Could not read dashboard.php file</p>";
    exit;
}

// Create backup
$backup_file = 'dashboard_backup_' . date('Y-m-d_H-i-s') . '.php';
file_put_contents($backup_file, $dashboard_content);
echo "<p>✅ Backup created: $backup_file</p>";

// Define the replacement
$old_revenue_section = '<div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
                <i class="fas fa-dollar-sign text-solar-yellow text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo formatCurrency($project_stats[\'total_revenue\'] + $pos_stats[\'today_revenue\'], 0); ?></p>
            </div>
        </div>
    </div>';

$new_revenue_section = '<div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
                <i class="fas fa-dollar-sign text-solar-yellow text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Revenue (All-Time)</p>
                <?php 
                $pos_stats_all_time = getPOSStats(); // Get all-time POS stats
                $total_revenue_all_time = $project_stats[\'total_revenue\'] + $pos_stats_all_time[\'total_revenue\'];
                ?>
                <p class="text-2xl font-semibold text-gray-900"><?php echo formatCurrency($total_revenue_all_time, 0); ?></p>
                <p class="text-xs text-gray-500">
                    Projects: <?php echo formatCurrency($project_stats[\'total_revenue\']); ?> | 
                    POS: <?php echo formatCurrency($pos_stats_all_time[\'total_revenue\']); ?>
                </p>
            </div>
        </div>
    </div>';

// Replace the problematic revenue calculation
$new_dashboard_content = str_replace($old_revenue_section, $new_revenue_section, $dashboard_content);

if ($new_dashboard_content === $dashboard_content) {
    echo "<p style='color: orange;'>⚠️ Could not find exact revenue section to replace. Trying alternative approach...</p>";
    
    // Try replacing just the calculation part
    $old_calc = '<?php echo formatCurrency($project_stats[\'total_revenue\'] + $pos_stats[\'today_revenue\'], 0); ?>';
    $new_calc = '<?php 
                $pos_stats_all_time = getPOSStats(); // Get all-time POS stats
                $total_revenue_all_time = $project_stats[\'total_revenue\'] + $pos_stats_all_time[\'total_revenue\'];
                echo formatCurrency($total_revenue_all_time, 0); 
                ?>';
    
    $new_dashboard_content = str_replace($old_calc, $new_calc, $dashboard_content);
    
    if ($new_dashboard_content === $dashboard_content) {
        echo "<p style='color: red;'>❌ Could not apply automatic fix. Manual update required.</p>";
        echo "<h3>Manual Fix Instructions:</h3>";
        echo "<ol>";
        echo "<li>Open dashboard.php in your editor</li>";
        echo "<li>Find line with: <code>\$project_stats['total_revenue'] + \$pos_stats['today_revenue']</code></li>";
        echo "<li>Replace with the corrected calculation shown below</li>";
        echo "</ol>";
        
        echo "<h3>Corrected Code:</h3>";
        echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
        echo htmlspecialchars('<?php 
// Get all-time POS stats for accurate total revenue
$pos_stats_all_time = getPOSStats(); // All-time POS stats instead of today only
$total_revenue_all_time = $project_stats[\'total_revenue\'] + $pos_stats_all_time[\'total_revenue\'];
echo formatCurrency($total_revenue_all_time, 0); 
?>');
        echo "</pre>";
        exit;
    }
}

// Write the updated content
if (file_put_contents($dashboard_file, $new_dashboard_content)) {
    echo "<p style='color: green;'>✅ Dashboard updated successfully!</p>";
    
    echo "<h3>✅ Changes Applied:</h3>";
    echo "<ul>";
    echo "<li>✅ Fixed Total Revenue calculation to use all-time POS data</li>";
    echo "<li>✅ Added breakdown showing Projects vs POS revenue</li>";
    echo "<li>✅ Changed label to 'Total Revenue (All-Time)' for clarity</li>";
    echo "</ul>";
    
    echo "<h3>📊 Revenue Calculation Now Shows:</h3>";
    echo "<ul>";
    echo "<li><strong>Projects:</strong> All-time revenue from approved/completed projects</li>";
    echo "<li><strong>POS Sales:</strong> All-time revenue from completed sales</li>";
    echo "<li><strong>Total:</strong> Accurate sum of both revenue streams</li>";
    echo "</ul>";
    
} else {
    echo "<p style='color: red;'>❌ Could not write updated dashboard file. Check permissions.</p>";
}

echo "<div style='background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🎯 Additional Improvements Available:</h3>";
echo "<p>For even better revenue tracking, consider adding these features:</p>";
echo "<ul>";
echo "<li><strong>Today's Revenue Card:</strong> Show today's projects + POS revenue</li>";
echo "<li><strong>This Month's Revenue Card:</strong> Show current month's revenue</li>";
echo "<li><strong>Revenue Trend Chart:</strong> Show daily/monthly revenue trends</li>";
echo "<li><strong>Date Range Filter:</strong> Allow users to select custom date ranges</li>";
echo "</ul>";
echo "</div>";

echo "<p>";
echo "<a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Fixed Dashboard</a>";
echo "<a href='revenue_analysis.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Analysis</a>";
echo "<a href='enhanced_dashboard_fix.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Apply Enhanced Fix</a>";
echo "</p>";
?>
