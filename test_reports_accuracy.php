<?php
require_once 'includes/config.php';
require_once 'includes/inventory.php';
require_once 'includes/projects.php';
require_once 'includes/pos.php';

echo "<h1>Reports Data Accuracy Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .test-section{border:1px solid #ccc;margin:10px 0;padding:15px;} .pass{color:green;} .fail{color:red;} .warning{color:orange;}</style>\n";

// Test 1: Inventory Statistics
echo "<div class='test-section'>\n";
echo "<h2>1. Inventory Statistics Test</h2>\n";

$inventory_items = getInventoryItems();
$low_stock_items = getLowStockItems();

echo "<h3>Inventory Items Count:</h3>\n";
echo "Total items: " . count($inventory_items) . "<br>\n";
echo "Low stock items: " . count($low_stock_items) . "<br>\n";

// Calculate inventory value manually
$total_value = 0;
$by_category = [];
foreach ($inventory_items as $item) {
    $total_value += $item['stock_quantity'] * $item['base_price'];
    $category = $item['category_name'] ?? 'Uncategorized';
    $by_category[$category] = ($by_category[$category] ?? 0) + 1;
}

echo "<h3>Inventory Value Calculation:</h3>\n";
echo "Total inventory value: ₱" . number_format($total_value, 2) . "<br>\n";
echo "Categories: " . count($by_category) . "<br>\n";

// Test 2: Project Statistics
echo "</div><div class='test-section'>\n";
echo "<h2>2. Project Statistics Test</h2>\n";

$project_stats = getProjectStats();
echo "<h3>Project Statistics:</h3>\n";
echo "Projects by status: " . json_encode($project_stats['by_status']) . "<br>\n";
echo "Total revenue: ₱" . number_format($project_stats['total_revenue'], 2) . "<br>\n";

// Manual verification of project revenue
global $pdo;
$stmt = $pdo->query("SELECT SUM(final_amount) as total_revenue FROM solar_projects WHERE project_status IN ('approved', 'completed')");
$manual_revenue = $stmt->fetchColumn() ?: 0;
echo "Manual revenue calculation: ₱" . number_format($manual_revenue, 2) . "<br>\n";

if (abs($project_stats['total_revenue'] - $manual_revenue) < 0.01) {
    echo "<span class='pass'>✓ Project revenue calculation is accurate</span><br>\n";
} else {
    echo "<span class='fail'>✗ Project revenue calculation mismatch</span><br>\n";
}

// Test 3: POS Statistics
echo "</div><div class='test-section'>\n";
echo "<h2>3. POS Statistics Test</h2>\n";

$pos_stats_all_time = getPOSStats();
$pos_stats_today = getPOSStats(date('Y-m-d'), date('Y-m-d'));
$pos_stats_month = getPOSStats(date('Y-m-01'), date('Y-m-d'));

echo "<h3>POS Statistics:</h3>\n";
echo "All time sales: " . $pos_stats_all_time['total_sales'] . "<br>\n";
echo "All time revenue: ₱" . number_format($pos_stats_all_time['total_revenue'], 2) . "<br>\n";
echo "Today's sales: " . $pos_stats_today['total_sales'] . "<br>\n";
echo "Today's revenue: ₱" . number_format($pos_stats_today['total_revenue'], 2) . "<br>\n";
echo "This month's sales: " . $pos_stats_month['total_sales'] . "<br>\n";
echo "This month's revenue: ₱" . number_format($pos_stats_month['total_revenue'], 2) . "<br>\n";

// Manual verification of POS revenue
$stmt = $pdo->query("SELECT COUNT(*) as count, SUM(total_amount) as total FROM pos_sales WHERE status = 'completed'");
$manual_pos = $stmt->fetch();
echo "Manual POS calculation - Sales: " . $manual_pos['count'] . ", Revenue: ₱" . number_format($manual_pos['total'] ?: 0, 2) . "<br>\n";

if (abs($pos_stats_all_time['total_revenue'] - ($manual_pos['total'] ?: 0)) < 0.01) {
    echo "<span class='pass'>✓ POS revenue calculation is accurate</span><br>\n";
} else {
    echo "<span class='fail'>✗ POS revenue calculation mismatch</span><br>\n";
}

// Test 4: Date Range Statistics
echo "</div><div class='test-section'>\n";
echo "<h2>4. Date Range Statistics Test</h2>\n";

$date_from = date('Y-m-01'); // First day of current month
$date_to = date('Y-m-d'); // Today

// Test the getDateRangeStats function
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

// Test 5: Top Selling Items
echo "</div><div class='test-section'>\n";
echo "<h2>5. Top Selling Items Test</h2>\n";

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
    echo "<tr><th>Item</th><th>Qty Sold</th><th>Revenue</th><th>Avg Price</th></tr>\n";
    foreach ($top_selling_items as $item) {
        echo "<tr><td>" . htmlspecialchars($item['brand'] . ' ' . $item['model']) . "</td>";
        echo "<td>" . $item['total_sold'] . "</td>";
        echo "<td>₱" . number_format($item['total_revenue'], 2) . "</td>";
        echo "<td>₱" . number_format($item['avg_price'], 2) . "</td></tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<span class='warning'>No sales data found for the current month</span><br>\n";
}

// Test 6: Monthly Trends
echo "</div><div class='test-section'>\n";
echo "<h2>6. Monthly Trends Test</h2>\n";

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

echo "<h3>Monthly Trends (Last 12 Months):</h3>\n";
echo "<table border='1' style='border-collapse:collapse;'>\n";
echo "<tr><th>Month</th><th>Projects Created</th><th>Projects Completed</th><th>Project Revenue</th><th>POS Sales</th><th>POS Revenue</th><th>Total Revenue</th></tr>\n";
foreach ($monthly_data as $month => $data) {
    echo "<tr><td>" . date('F Y', strtotime($month . '-01')) . "</td>";
    echo "<td>" . $data['projects_created'] . "</td>";
    echo "<td>" . $data['projects_completed'] . "</td>";
    echo "<td>₱" . number_format($data['project_revenue'], 2) . "</td>";
    echo "<td>" . $data['pos_sales'] . "</td>";
    echo "<td>₱" . number_format($data['pos_revenue'], 2) . "</td>";
    echo "<td>₱" . number_format($data['total_revenue'], 2) . "</td></tr>\n";
}
echo "</table>\n";

// Test 7: Data Consistency Checks
echo "</div><div class='test-section'>\n";
echo "<h2>7. Data Consistency Checks</h2>\n";

// Check for orphaned records
$stmt = $pdo->query("SELECT COUNT(*) as count FROM pos_sale_items psi LEFT JOIN pos_sales ps ON psi.sale_id = ps.id WHERE ps.id IS NULL");
$orphaned_pos_items = $stmt->fetchColumn();
echo "Orphaned POS sale items: " . $orphaned_pos_items . "<br>\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM solar_project_items spi LEFT JOIN solar_projects sp ON spi.project_id = sp.id WHERE sp.id IS NULL");
$orphaned_project_items = $stmt->fetchColumn();
echo "Orphaned project items: " . $orphaned_project_items . "<br>\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM quote_items qi LEFT JOIN quotations q ON qi.quote_id = q.id WHERE q.id IS NULL");
$orphaned_quote_items = $stmt->fetchColumn();
echo "Orphaned quote items: " . $orphaned_quote_items . "<br>\n";

// Check for negative stock quantities
$stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_items WHERE stock_quantity < 0");
$negative_stock = $stmt->fetchColumn();
echo "Items with negative stock: " . $negative_stock . "<br>\n";

// Check for zero or negative prices
$stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_items WHERE base_price <= 0 OR selling_price <= 0");
$invalid_prices = $stmt->fetchColumn();
echo "Items with invalid prices: " . $invalid_prices . "<br>\n";

// Summary
echo "</div><div class='test-section'>\n";
echo "<h2>Test Summary</h2>\n";

$issues = [];
if ($orphaned_pos_items > 0) $issues[] = "Found $orphaned_pos_items orphaned POS sale items";
if ($orphaned_project_items > 0) $issues[] = "Found $orphaned_project_items orphaned project items";
if ($orphaned_quote_items > 0) $issues[] = "Found $orphaned_quote_items orphaned quote items";
if ($negative_stock > 0) $issues[] = "Found $negative_stock items with negative stock";
if ($invalid_prices > 0) $issues[] = "Found $invalid_prices items with invalid prices";

if (empty($issues)) {
    echo "<span class='pass'>✓ All data consistency checks passed</span><br>\n";
} else {
    echo "<span class='fail'>✗ Data consistency issues found:</span><br>\n";
    foreach ($issues as $issue) {
        echo "• $issue<br>\n";
    }
}

echo "</div>\n";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>
