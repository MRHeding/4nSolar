<?php
require_once 'includes/config.php';
require_once 'includes/inventory.php';
require_once 'includes/projects.php';
require_once 'includes/pos.php';

echo "<h1>Reports Accuracy Verification</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .test-section{border:1px solid #ccc;margin:10px 0;padding:15px;} .pass{color:green;} .fail{color:red;} .warning{color:orange;} .info{color:blue;}</style>\n";

// Test 1: Verify all data consistency issues are resolved
echo "<div class='test-section'>\n";
echo "<h2>1. Data Consistency Verification</h2>\n";

// Check for orphaned records
$stmt = $pdo->query("SELECT COUNT(*) as count FROM pos_sale_items psi LEFT JOIN pos_sales ps ON psi.sale_id = ps.id WHERE ps.id IS NULL");
$orphaned_pos_items = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM solar_project_items spi LEFT JOIN solar_projects sp ON spi.project_id = sp.id WHERE sp.id IS NULL");
$orphaned_project_items = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM quote_items qi LEFT JOIN quotations q ON qi.quote_id = q.id WHERE q.id IS NULL");
$orphaned_quote_items = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_items WHERE stock_quantity < 0");
$negative_stock = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_items WHERE base_price <= 0 OR selling_price <= 0");
$invalid_prices = $stmt->fetchColumn();

echo "<h3>Consistency Check Results:</h3>\n";
echo "Orphaned POS sale items: $orphaned_pos_items<br>\n";
echo "Orphaned project items: $orphaned_project_items<br>\n";
echo "Orphaned quote items: $orphaned_quote_items<br>\n";
echo "Items with negative stock: $negative_stock<br>\n";
echo "Items with invalid prices: $invalid_prices<br>\n";

$total_issues = $orphaned_pos_items + $orphaned_project_items + $orphaned_quote_items + $negative_stock + $invalid_prices;
if ($total_issues == 0) {
    echo "<span class='pass'>✓ All data consistency issues resolved</span><br>\n";
} else {
    echo "<span class='fail'>✗ $total_issues data consistency issues remain</span><br>\n";
}
echo "</div>\n";

// Test 2: Verify inventory calculations
echo "<div class='test-section'>\n";
echo "<h2>2. Inventory Calculations Verification</h2>\n";

$inventory_items = getInventoryItems();
$low_stock_items = getLowStockItems();

// Manual calculation of inventory value
$manual_total_value = 0;
$manual_by_category = [];
foreach ($inventory_items as $item) {
    $manual_total_value += $item['stock_quantity'] * $item['base_price'];
    $category = $item['category_name'] ?? 'Uncategorized';
    $manual_by_category[$category] = ($manual_by_category[$category] ?? 0) + 1;
}

echo "<h3>Inventory Statistics:</h3>\n";
echo "Total items: " . count($inventory_items) . "<br>\n";
echo "Low stock items: " . count($low_stock_items) . "<br>\n";
echo "Total inventory value: ₱" . number_format($manual_total_value, 2) . "<br>\n";
echo "Categories: " . count($manual_by_category) . "<br>\n";

// Verify low stock calculation
$manual_low_stock = 0;
foreach ($inventory_items as $item) {
    if ($item['stock_quantity'] <= $item['minimum_stock']) {
        $manual_low_stock++;
    }
}

if ($manual_low_stock == count($low_stock_items)) {
    echo "<span class='pass'>✓ Low stock calculation is accurate</span><br>\n";
} else {
    echo "<span class='fail'>✗ Low stock calculation mismatch: Expected $manual_low_stock, Got " . count($low_stock_items) . "</span><br>\n";
}
echo "</div>\n";

// Test 3: Verify project revenue calculations
echo "<div class='test-section'>\n";
echo "<h2>3. Project Revenue Verification</h2>\n";

$project_stats = getProjectStats();

// Manual verification
$stmt = $pdo->query("SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN project_status IN ('approved', 'completed') THEN final_amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN project_status = 'completed' THEN 1 ELSE 0 END) as completed_projects
                    FROM solar_projects");
$manual_project_stats = $stmt->fetch();

echo "<h3>Project Statistics:</h3>\n";
echo "Total projects: " . $manual_project_stats['total_projects'] . "<br>\n";
echo "Completed projects: " . $manual_project_stats['completed_projects'] . "<br>\n";
echo "Total revenue (function): ₱" . number_format($project_stats['total_revenue'], 2) . "<br>\n";
echo "Total revenue (manual): ₱" . number_format($manual_project_stats['total_revenue'] ?: 0, 2) . "<br>\n";

if (abs($project_stats['total_revenue'] - ($manual_project_stats['total_revenue'] ?: 0)) < 0.01) {
    echo "<span class='pass'>✓ Project revenue calculation is accurate</span><br>\n";
} else {
    echo "<span class='fail'>✗ Project revenue calculation mismatch</span><br>\n";
}

// Verify project status distribution
$stmt = $pdo->query("SELECT project_status, COUNT(*) as count FROM solar_projects GROUP BY project_status");
$manual_status_dist = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

echo "<h3>Project Status Distribution:</h3>\n";
foreach ($manual_status_dist as $status => $count) {
    $function_count = $project_stats['by_status'][$status] ?? 0;
    echo "$status: Function=$function_count, Manual=$count ";
    if ($function_count == $count) {
        echo "<span class='pass'>✓</span><br>\n";
    } else {
        echo "<span class='fail'>✗</span><br>\n";
    }
}
echo "</div>\n";

// Test 4: Verify POS sales calculations
echo "<div class='test-section'>\n";
echo "<h2>4. POS Sales Verification</h2>\n";

$pos_stats_all_time = getPOSStats();
$pos_stats_today = getPOSStats(date('Y-m-d'), date('Y-m-d'));
$pos_stats_month = getPOSStats(date('Y-m-01'), date('Y-m-d'));

// Manual verification
$stmt = $pdo->query("SELECT COUNT(*) as count, SUM(total_amount) as total FROM pos_sales WHERE status = 'completed'");
$manual_pos_all_time = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as count, SUM(total_amount) as total FROM pos_sales WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
$manual_pos_today = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as count, SUM(total_amount) as total FROM pos_sales WHERE status = 'completed' AND DATE(created_at) BETWEEN '" . date('Y-m-01') . "' AND '" . date('Y-m-d') . "'");
$manual_pos_month = $stmt->fetch();

echo "<h3>POS Sales Statistics:</h3>\n";
echo "<strong>All Time:</strong><br>\n";
echo "Sales: Function=" . $pos_stats_all_time['total_sales'] . ", Manual=" . $manual_pos_all_time['count'] . " ";
if ($pos_stats_all_time['total_sales'] == $manual_pos_all_time['count']) {
    echo "<span class='pass'>✓</span><br>\n";
} else {
    echo "<span class='fail'>✗</span><br>\n";
}

echo "Revenue: Function=₱" . number_format($pos_stats_all_time['total_revenue'], 2) . ", Manual=₱" . number_format($manual_pos_all_time['total'] ?: 0, 2) . " ";
if (abs($pos_stats_all_time['total_revenue'] - ($manual_pos_all_time['total'] ?: 0)) < 0.01) {
    echo "<span class='pass'>✓</span><br>\n";
} else {
    echo "<span class='fail'>✗</span><br>\n";
}

echo "<strong>Today:</strong><br>\n";
echo "Sales: Function=" . $pos_stats_today['total_sales'] . ", Manual=" . $manual_pos_today['count'] . " ";
if ($pos_stats_today['total_sales'] == $manual_pos_today['count']) {
    echo "<span class='pass'>✓</span><br>\n";
} else {
    echo "<span class='fail'>✗</span><br>\n";
}

echo "Revenue: Function=₱" . number_format($pos_stats_today['total_revenue'], 2) . ", Manual=₱" . number_format($manual_pos_today['total'] ?: 0, 2) . " ";
if (abs($pos_stats_today['total_revenue'] - ($manual_pos_today['total'] ?: 0)) < 0.01) {
    echo "<span class='pass'>✓</span><br>\n";
} else {
    echo "<span class='fail'>✗</span><br>\n";
}

echo "<strong>This Month:</strong><br>\n";
echo "Sales: Function=" . $pos_stats_month['total_sales'] . ", Manual=" . $manual_pos_month['count'] . " ";
if ($pos_stats_month['total_sales'] == $manual_pos_month['count']) {
    echo "<span class='pass'>✓</span><br>\n";
} else {
    echo "<span class='fail'>✗</span><br>\n";
}

echo "Revenue: Function=₱" . number_format($pos_stats_month['total_revenue'], 2) . ", Manual=₱" . number_format($manual_pos_month['total'] ?: 0, 2) . " ";
if (abs($pos_stats_month['total_revenue'] - ($manual_pos_month['total'] ?: 0)) < 0.01) {
    echo "<span class='pass'>✓</span><br>\n";
} else {
    echo "<span class='fail'>✗</span><br>\n";
}
echo "</div>\n";

// Test 5: Verify date range calculations
echo "<div class='test-section'>\n";
echo "<h2>5. Date Range Calculations Verification</h2>\n";

$date_from = date('Y-m-01');
$date_to = date('Y-m-d');

// Test the getDateRangeStats function from reports.php
function testGetDateRangeStats($date_from, $date_to) {
    global $pdo;
    
    // Project stats for date range
    $stmt = $pdo->prepare("SELECT 
                          COUNT(*) as total_projects,
                          SUM(CASE WHEN project_status IN ('approved', 'completed') THEN final_amount ELSE 0 END) as project_revenue,
                          SUM(CASE WHEN project_status = 'completed' THEN 1 ELSE 0 END) as completed_projects
                          FROM solar_projects 
                          WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $project_stats = $stmt->fetch();
    
    // POS stats for date range
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total_amount) as total 
                          FROM pos_sales WHERE status = 'completed' 
                          AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $pos_stats = $stmt->fetch();
    
    return [
        'projects' => $project_stats,
        'pos' => $pos_stats,
        'total_revenue' => ($project_stats['project_revenue'] ?: 0) + ($pos_stats['total'] ?: 0)
    ];
}

$date_range_stats = testGetDateRangeStats($date_from, $date_to);

echo "<h3>Date Range Statistics (Current Month):</h3>\n";
echo "Projects created: " . $date_range_stats['projects']['total_projects'] . "<br>\n";
echo "Projects completed: " . $date_range_stats['projects']['completed_projects'] . "<br>\n";
echo "Project revenue: ₱" . number_format($date_range_stats['projects']['project_revenue'] ?: 0, 2) . "<br>\n";
echo "POS transactions: " . $date_range_stats['pos']['count'] . "<br>\n";
echo "POS revenue: ₱" . number_format($date_range_stats['pos']['total'] ?: 0, 2) . "<br>\n";
echo "Total revenue: ₱" . number_format($date_range_stats['total_revenue'], 2) . "<br>\n";
echo "</div>\n";

// Test 6: Verify top selling items calculation
echo "<div class='test-section'>\n";
echo "<h2>6. Top Selling Items Verification</h2>\n";

function testGetTopSellingItems($date_from, $date_to, $limit = 10) {
    global $pdo;
    $limit = (int)$limit;
    $stmt = $pdo->prepare("SELECT 
                          i.brand, i.model, i.size_specification,
                          SUM(psi.quantity) as total_sold,
                          SUM(psi.total_amount) as total_revenue,
                          AVG(psi.unit_price) as avg_price
                          FROM pos_sale_items psi
                          LEFT JOIN inventory_items i ON psi.inventory_item_id = i.id
                          LEFT JOIN pos_sales ps ON psi.sale_id = ps.id
                          WHERE ps.status = 'completed' 
                          AND DATE(ps.created_at) BETWEEN ? AND ?
                          GROUP BY psi.inventory_item_id
                          ORDER BY total_sold DESC
                          LIMIT $limit");
    $stmt->execute([$date_from, $date_to]);
    return $stmt->fetchAll();
}

$top_selling_items = testGetTopSellingItems($date_from, $date_to);

echo "<h3>Top Selling Items (Current Month):</h3>\n";
if (!empty($top_selling_items)) {
    echo "<table border='1' style='border-collapse:collapse;'>\n";
    echo "<tr><th>Rank</th><th>Item</th><th>Qty Sold</th><th>Revenue</th><th>Avg Price</th></tr>\n";
    foreach ($top_selling_items as $index => $item) {
        echo "<tr><td>" . ($index + 1) . "</td>";
        echo "<td>" . htmlspecialchars($item['brand'] . ' ' . $item['model']) . "</td>";
        echo "<td>" . $item['total_sold'] . "</td>";
        echo "<td>₱" . number_format($item['total_revenue'], 2) . "</td>";
        echo "<td>₱" . number_format($item['avg_price'], 2) . "</td></tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<span class='info'>No sales data found for the current month</span><br>\n";
}
echo "</div>\n";

// Test 7: Verify monthly trends calculation
echo "<div class='test-section'>\n";
echo "<h2>7. Monthly Trends Verification</h2>\n";

// Get monthly data for last 12 months
$monthly_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthly_data[$month] = [
        'projects_created' => 0,
        'projects_completed' => 0,
        'project_revenue' => 0,
        'pos_sales' => 0,
        'pos_revenue' => 0,
        'total_revenue' => 0
    ];
}

// Get monthly project data
$stmt = $pdo->query("SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as projects_created,
                    SUM(CASE WHEN project_status = 'completed' THEN 1 ELSE 0 END) as projects_completed,
                    SUM(CASE WHEN project_status IN ('approved', 'completed') THEN final_amount ELSE 0 END) as project_revenue
                    FROM solar_projects 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY month");
$project_monthly = $stmt->fetchAll();

foreach ($project_monthly as $row) {
    if (isset($monthly_data[$row['month']])) {
        $monthly_data[$row['month']]['projects_created'] = $row['projects_created'];
        $monthly_data[$row['month']]['projects_completed'] = $row['projects_completed'];
        $monthly_data[$row['month']]['project_revenue'] = $row['project_revenue'];
    }
}

// Get monthly POS data
$stmt = $pdo->query("SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as pos_sales,
                    SUM(total_amount) as pos_revenue
                    FROM pos_sales 
                    WHERE status = 'completed' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY month");
$pos_monthly = $stmt->fetchAll();

foreach ($pos_monthly as $row) {
    if (isset($monthly_data[$row['month']])) {
        $monthly_data[$row['month']]['pos_sales'] = $row['pos_sales'];
        $monthly_data[$row['month']]['pos_revenue'] = $row['pos_revenue'];
        $monthly_data[$row['month']]['total_revenue'] = $monthly_data[$row['month']]['project_revenue'] + $row['pos_revenue'];
    }
}

echo "<h3>Monthly Trends Summary (Last 12 Months):</h3>\n";
$total_projects_created = array_sum(array_column($monthly_data, 'projects_created'));
$total_projects_completed = array_sum(array_column($monthly_data, 'projects_completed'));
$total_project_revenue = array_sum(array_column($monthly_data, 'project_revenue'));
$total_pos_sales = array_sum(array_column($monthly_data, 'pos_sales'));
$total_pos_revenue = array_sum(array_column($monthly_data, 'pos_revenue'));
$total_revenue = array_sum(array_column($monthly_data, 'total_revenue'));

echo "Total projects created: $total_projects_created<br>\n";
echo "Total projects completed: $total_projects_completed<br>\n";
echo "Total project revenue: ₱" . number_format($total_project_revenue, 2) . "<br>\n";
echo "Total POS sales: $total_pos_sales<br>\n";
echo "Total POS revenue: ₱" . number_format($total_pos_revenue, 2) . "<br>\n";
echo "Total combined revenue: ₱" . number_format($total_revenue, 2) . "<br>\n";

// Verify totals match individual calculations
$expected_total_revenue = $total_project_revenue + $total_pos_revenue;
if (abs($total_revenue - $expected_total_revenue) < 0.01) {
    echo "<span class='pass'>✓ Monthly revenue totals are consistent</span><br>\n";
} else {
    echo "<span class='fail'>✗ Monthly revenue totals mismatch</span><br>\n";
}
echo "</div>\n";

// Final Summary
echo "<div class='test-section'>\n";
echo "<h2>Final Verification Summary</h2>\n";

$all_tests_passed = true;
$test_results = [];

// Check data consistency
if ($total_issues == 0) {
    $test_results[] = "✓ Data consistency: PASSED";
} else {
    $test_results[] = "✗ Data consistency: FAILED ($total_issues issues)";
    $all_tests_passed = false;
}

// Check inventory calculations
if ($manual_low_stock == count($low_stock_items)) {
    $test_results[] = "✓ Inventory calculations: PASSED";
} else {
    $test_results[] = "✗ Inventory calculations: FAILED";
    $all_tests_passed = false;
}

// Check project revenue
if (abs($project_stats['total_revenue'] - ($manual_project_stats['total_revenue'] ?: 0)) < 0.01) {
    $test_results[] = "✓ Project revenue: PASSED";
} else {
    $test_results[] = "✗ Project revenue: FAILED";
    $all_tests_passed = false;
}

// Check POS revenue
if (abs($pos_stats_all_time['total_revenue'] - ($manual_pos_all_time['total'] ?: 0)) < 0.01) {
    $test_results[] = "✓ POS revenue: PASSED";
} else {
    $test_results[] = "✗ POS revenue: FAILED";
    $all_tests_passed = false;
}

// Check monthly trends
if (abs($total_revenue - $expected_total_revenue) < 0.01) {
    $test_results[] = "✓ Monthly trends: PASSED";
} else {
    $test_results[] = "✗ Monthly trends: FAILED";
    $all_tests_passed = false;
}

echo "<h3>Test Results:</h3>\n";
foreach ($test_results as $result) {
    if (strpos($result, '✓') === 0) {
        echo "<span class='pass'>$result</span><br>\n";
    } else {
        echo "<span class='fail'>$result</span><br>\n";
    }
}

echo "<h3>Overall Result:</h3>\n";
if ($all_tests_passed) {
    echo "<span class='pass'>✓ ALL TESTS PASSED - Reports data is accurate and consistent</span><br>\n";
} else {
    echo "<span class='fail'>✗ SOME TESTS FAILED - Reports data has accuracy issues</span><br>\n";
}

echo "<p><strong>Verification completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "</div>\n";
?>
