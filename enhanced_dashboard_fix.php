<?php
/**
 * Enhanced Dashboard Revenue Fix
 * This creates an improved dashboard with multiple revenue tracking cards
 */

echo "<h1>🚀 Enhanced Dashboard Revenue Fix</h1>";
echo "<p>This will create an enhanced dashboard with comprehensive revenue tracking.</p>";

// Read current dashboard
$dashboard_file = 'dashboard.php';
$dashboard_content = file_get_contents($dashboard_file);

if (!$dashboard_content) {
    echo "<p style='color: red;'>❌ Could not read dashboard.php file</p>";
    exit;
}

// Create backup
$backup_file = 'dashboard_enhanced_backup_' . date('Y-m-d_H-i-s') . '.php';
file_put_contents($backup_file, $dashboard_content);
echo "<p>✅ Enhanced backup created: $backup_file</p>";

// Find the statistics section and enhance it
$stats_section_start = '<!-- Statistics Cards -->';
$stats_section_end = '</div>';

// Find the position of the statistics section
$start_pos = strpos($dashboard_content, $stats_section_start);
if ($start_pos === false) {
    echo "<p style='color: red;'>❌ Could not find statistics section in dashboard</p>";
    exit;
}

// Find the end of the statistics grid
$search_start = $start_pos + strlen($stats_section_start);
$grid_start = strpos($dashboard_content, '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5', $search_start);
$grid_end = strpos($dashboard_content, '</div>', $grid_start);

// Find the actual end by counting nested divs
$div_count = 1;
$pos = $grid_end + 6; // Start after the first </div>
while ($div_count > 0 && $pos < strlen($dashboard_content)) {
    $next_open = strpos($dashboard_content, '<div', $pos);
    $next_close = strpos($dashboard_content, '</div>', $pos);
    
    if ($next_close !== false && ($next_open === false || $next_close < $next_open)) {
        $div_count--;
        $pos = $next_close + 6;
        if ($div_count === 0) {
            $grid_end = $pos;
            break;
        }
    } else if ($next_open !== false) {
        $div_count++;
        $pos = $next_open + 4;
    } else {
        break;
    }
}

// Create enhanced statistics section
$enhanced_stats = '<!-- Enhanced Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
    <!-- Total Items -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100">
                <i class="fas fa-boxes text-solar-blue text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Items</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo $total_items; ?></p>
            </div>
        </div>
    </div>

    <!-- Low Stock Items -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo count($low_stock_items); ?></p>
            </div>
        </div>
    </div>

    <!-- Total Projects -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100">
                <i class="fas fa-project-diagram text-solar-green text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Projects</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo $total_projects; ?></p>
            </div>
        </div>
    </div>

    <!-- Today\'s Sales -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100">
                <i class="fas fa-cash-register text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Today\'s Sales</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo $pos_stats[\'today_sales\']; ?></p>
                <p class="text-xs text-gray-500">
                    <?php echo formatCurrency($pos_stats[\'today_revenue\']); ?> revenue
                </p>
            </div>
        </div>
    </div>

    <!-- Today\'s Revenue -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-indigo-100">
                <i class="fas fa-calendar-day text-indigo-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Today\'s Revenue</p>
                <?php 
                $today_project_revenue = getTodayProjectRevenue();
                $today_total_revenue = $today_project_revenue + $pos_stats[\'today_revenue\'];
                ?>
                <p class="text-2xl font-semibold text-gray-900"><?php echo formatCurrency($today_total_revenue, 0); ?></p>
                <p class="text-xs text-gray-500">
                    Projects: <?php echo formatCurrency($today_project_revenue); ?> | 
                    POS: <?php echo formatCurrency($pos_stats[\'today_revenue\']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Total Revenue (All-Time) -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
                <i class="fas fa-dollar-sign text-solar-yellow text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
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
    </div>
</div>

<!-- This Month\'s Revenue Summary -->
<div class="mb-8">
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-chart-line mr-2"></i>
                    This Month\'s Performance
                </h2>
                <?php 
                $pos_stats_month = getPOSStats(date(\'Y-m-01\'), date(\'Y-m-d\')); // This month
                $month_project_revenue = getMonthProjectRevenue();
                $month_total_revenue = $month_project_revenue + $pos_stats_month[\'total_revenue\'];
                ?>
                <p class="text-3xl font-bold"><?php echo formatCurrency($month_total_revenue); ?></p>
                <p class="text-blue-100">
                    Projects: <?php echo formatCurrency($month_project_revenue); ?> | 
                    POS: <?php echo formatCurrency($pos_stats_month[\'total_revenue\']); ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-blue-100">Sales Count</p>
                <p class="text-2xl font-bold"><?php echo $pos_stats_month[\'total_sales\']; ?></p>
                <p class="text-blue-100">transactions</p>
            </div>
        </div>
    </div>
</div>';

// Add helper functions after the statistics section
$helper_functions = '
<?php
// Helper functions for enhanced revenue calculations
function getTodayProjectRevenue() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(final_amount) as revenue 
                          FROM solar_projects 
                          WHERE project_status IN (\'approved\', \'completed\') 
                          AND DATE(created_at) = CURDATE()");
    $stmt->execute();
    return $stmt->fetchColumn() ?: 0;
}

function getMonthProjectRevenue() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(final_amount) as revenue 
                          FROM solar_projects 
                          WHERE project_status IN (\'approved\', \'completed\') 
                          AND YEAR(created_at) = YEAR(CURDATE()) 
                          AND MONTH(created_at) = MONTH(CURDATE())");
    $stmt->execute();
    return $stmt->fetchColumn() ?: 0;
}
?>';

// Replace the old statistics section
$before_stats = substr($dashboard_content, 0, $start_pos);
$after_stats = substr($dashboard_content, $grid_end);

$new_dashboard_content = $before_stats . $enhanced_stats . $after_stats;

// Add helper functions before the include header
$header_include_pos = strpos($new_dashboard_content, "include 'includes/header.php';");
if ($header_include_pos !== false) {
    $before_header = substr($new_dashboard_content, 0, $header_include_pos);
    $after_header = substr($new_dashboard_content, $header_include_pos);
    $new_dashboard_content = $before_header . $helper_functions . "\n\n" . $after_header;
}

// Write the enhanced dashboard
if (file_put_contents($dashboard_file, $new_dashboard_content)) {
    echo "<p style='color: green;'>✅ Enhanced dashboard created successfully!</p>";
    
    echo "<h3>🎯 Enhancements Applied:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Today\'s Revenue Card:</strong> Shows combined projects + POS revenue for today</li>";
    echo "<li>✅ <strong>Total Revenue Card:</strong> Fixed to show all-time projects + POS revenue</li>";
    echo "<li>✅ <strong>This Month\'s Performance:</strong> New summary card for current month</li>";
    echo "<li>✅ <strong>Revenue Breakdown:</strong> Each card shows projects vs POS breakdown</li>";
    echo "<li>✅ <strong>Improved Layout:</strong> Better grid layout with 6 columns</li>";
    echo "<li>✅ <strong>Helper Functions:</strong> Added functions for accurate calculations</li>";
    echo "</ul>";
    
    echo "<h3>📊 Revenue Cards Now Show:</h3>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;'>";
    echo "<h4>Today\'s Revenue</h4>";
    echo "<p>Projects + POS sales for current date</p>";
    echo "</div>";
    
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;'>";
    echo "<h4>Total Revenue (All-Time)</h4>";
    echo "<p>All approved/completed projects + all POS sales</p>";
    echo "</div>";
    
    echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 8px; border-left: 4px solid #9c27b0;'>";
    echo "<h4>This Month\'s Performance</h4>";
    echo "<p>Current month projects + POS with transaction count</p>";
    echo "</div>";
    
    echo "</div>";
    
} else {
    echo "<p style='color: red;'>❌ Could not write enhanced dashboard file. Check permissions.</p>";
}

echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ Revenue Accuracy Fixed!</h3>";
echo "<p>The dashboard now provides accurate revenue calculations:</p>";
echo "<ul>";
echo "<li><strong>Consistent Time Periods:</strong> No more mixing all-time projects with today\'s POS</li>";
echo "<li><strong>Clear Breakdowns:</strong> Each revenue card shows Projects vs POS contribution</li>";
echo "<li><strong>Multiple Views:</strong> Today, This Month, and All-Time revenue tracking</li>";
echo "<li><strong>Better Business Insights:</strong> More comprehensive performance overview</li>";
echo "</ul>";
echo "</div>";

echo "<p>";
echo "<a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Enhanced Dashboard</a>";
echo "<a href='revenue_analysis.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Revenue Analysis</a>";
echo "</p>";
?>
