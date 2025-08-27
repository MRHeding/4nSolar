<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/projects.php';
require_once 'includes/pos.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Date filtering
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today
$report_type = $_GET['report_type'] ?? 'overview';

// Helper functions for enhanced reports
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

function getDateRangeStats($date_from, $date_to) {
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
    $pos_stats = getPOSStats($date_from, $date_to);
    
    return [
        'projects' => $project_stats,
        'pos' => $pos_stats,
        'total_revenue' => ($project_stats['project_revenue'] ?: 0) + ($pos_stats['total_revenue'] ?: 0)
    ];
}

function getTopSellingItems($date_from, $date_to, $limit = 10) {
    global $pdo;
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
                          LIMIT ?");
    $stmt->execute([$date_from, $date_to, $limit]);
    return $stmt->fetchAll();
}

function getInventoryMovements($date_from, $date_to, $limit = 20) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 
                          sm.*, i.brand, i.model, i.size_specification, u.full_name as user_name
                          FROM stock_movements sm
                          LEFT JOIN inventory_items i ON sm.inventory_item_id = i.id
                          LEFT JOIN users u ON sm.created_by = u.id
                          WHERE DATE(sm.created_at) BETWEEN ? AND ?
                          ORDER BY sm.created_at DESC
                          LIMIT ?");
    $stmt->execute([$date_from, $date_to, $limit]);
    return $stmt->fetchAll();
}

// Get comprehensive statistics
$inventory_stats = [
    'total_items' => count(getInventoryItems()),
    'low_stock_count' => count(getLowStockItems()),
    'total_value' => 0,
    'by_category' => []
];

$project_stats = getProjectStats();
$pos_stats_all_time = getPOSStats();
$pos_stats_today = getPOSStats(date('Y-m-d'), date('Y-m-d'));
$pos_stats_month = getPOSStats(date('Y-m-01'), date('Y-m-d'));

$all_inventory = getInventoryItems();
$date_range_stats = getDateRangeStats($date_from, $date_to);
$top_selling_items = getTopSellingItems($date_from, $date_to);
$recent_movements = getInventoryMovements($date_from, $date_to);

// Calculate inventory value and categorization
foreach ($all_inventory as $item) {
    $inventory_stats['total_value'] += $item['stock_quantity'] * $item['base_price'];
    $category = $item['category_name'] ?? 'Uncategorized';
    $inventory_stats['by_category'][$category] = ($inventory_stats['by_category'][$category] ?? 0) + 1;
}

// Monthly trends for the last 12 months
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
global $pdo;
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

$page_title = 'Enhanced Reports & Analytics';
$content_start = true;
include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">📊 Enhanced Reports & Analytics</h1>
    <p class="text-gray-600">Comprehensive business insights with POS integration and advanced analytics</p>
</div>

<!-- Date Filter and Report Type Selector -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <h2 class="text-lg font-semibold text-gray-800">📅 Report Filters</h2>
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">From:</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                       class="border border-gray-300 rounded px-3 py-1 text-sm">
            </div>
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">To:</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                       class="border border-gray-300 rounded px-3 py-1 text-sm">
            </div>
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Report:</label>
                <select name="report_type" class="border border-gray-300 rounded px-3 py-1 text-sm">
                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                    <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales Analysis</option>
                    <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Inventory Analysis</option>
                    <option value="projects" <?php echo $report_type === 'projects' ? 'selected' : ''; ?>>Project Analysis</option>
                </select>
            </div>
            <button type="submit" class="bg-solar-blue text-white px-4 py-1 rounded text-sm hover:bg-blue-800 transition">
                <i class="fas fa-search mr-1"></i>Update Report
            </button>
        </form>
    </div>
</div>

<!-- Enhanced Overview Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
    <!-- Today's Revenue -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-calendar-day text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-blue-100">Today's Revenue</p>
                <?php $today_total = getTodayProjectRevenue() + $pos_stats_today['total_revenue']; ?>
                <p class="text-2xl font-semibold"><?php echo formatCurrency($today_total, 0); ?></p>
                <p class="text-xs text-blue-100">
                    Projects: <?php echo formatCurrency(getTodayProjectRevenue()); ?> | 
                    POS: <?php echo formatCurrency($pos_stats_today['total_revenue']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- This Month's Revenue -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-calendar-alt text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-green-100">This Month</p>
                <?php $month_total = getMonthProjectRevenue() + $pos_stats_month['total_revenue']; ?>
                <p class="text-2xl font-semibold"><?php echo formatCurrency($month_total, 0); ?></p>
                <p class="text-xs text-green-100">
                    Projects: <?php echo formatCurrency(getMonthProjectRevenue()); ?> | 
                    POS: <?php echo formatCurrency($pos_stats_month['total_revenue']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-purple-100">Total Revenue</p>
                <?php $total_all_time = $project_stats['total_revenue'] + $pos_stats_all_time['total_revenue']; ?>
                <p class="text-2xl font-semibold"><?php echo formatCurrency($total_all_time, 0); ?></p>
                <p class="text-xs text-purple-100">
                    Projects: <?php echo formatCurrency($project_stats['total_revenue']); ?> | 
                    POS: <?php echo formatCurrency($pos_stats_all_time['total_revenue']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Inventory Value -->
    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-boxes text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-yellow-100">Inventory Value</p>
                <p class="text-2xl font-semibold"><?php echo formatCurrency($inventory_stats['total_value'], 0); ?></p>
                <p class="text-xs text-yellow-100"><?php echo $inventory_stats['total_items']; ?> items</p>
            </div>
        </div>
    </div>

    <!-- POS Sales Today -->
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-cash-register text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-indigo-100">Today's Sales</p>
                <p class="text-2xl font-semibold"><?php echo $pos_stats_today['today_sales']; ?></p>
                <p class="text-xs text-indigo-100">transactions</p>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-white bg-opacity-20">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-red-100">Low Stock</p>
                <p class="text-2xl font-semibold"><?php echo $inventory_stats['low_stock_count']; ?></p>
                <p class="text-xs text-red-100">items need restock</p>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Performance -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">
        📈 Performance for Selected Period (<?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?>)
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="text-center p-4 bg-blue-50 rounded-lg">
            <div class="text-2xl font-bold text-blue-600"><?php echo $date_range_stats['projects']['total_projects']; ?></div>
            <div class="text-sm text-gray-600">Projects Created</div>
        </div>
        <div class="text-center p-4 bg-green-50 rounded-lg">
            <div class="text-2xl font-bold text-green-600"><?php echo $date_range_stats['projects']['completed_projects']; ?></div>
            <div class="text-sm text-gray-600">Projects Completed</div>
        </div>
        <div class="text-center p-4 bg-purple-50 rounded-lg">
            <div class="text-2xl font-bold text-purple-600"><?php echo $date_range_stats['pos']['total_sales']; ?></div>
            <div class="text-sm text-gray-600">POS Transactions</div>
        </div>
        <div class="text-center p-4 bg-yellow-50 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600"><?php echo formatCurrency($date_range_stats['total_revenue'], 0); ?></div>
            <div class="text-sm text-gray-600">Total Revenue</div>
        </div>
    </div>
</div>

<?php if ($report_type === 'overview' || $report_type === 'sales'): ?>
<!-- Top Selling Items -->
<?php if (!empty($top_selling_items)): ?>
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">🏆 Top Selling Items (Selected Period)</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Sold</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Price</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($top_selling_items as $index => $item): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $index + 1; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $item['total_sold']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatCurrency($item['total_revenue']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatCurrency($item['avg_price']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Project Status Distribution -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📊 Projects by Status</h2>
        <div class="space-y-4">
            <?php if (!empty($project_stats['by_status'])): ?>
                <?php 
                $total_projects = array_sum($project_stats['by_status']);
                $status_colors = [
                    'draft' => 'bg-gray-400',
                    'quoted' => 'bg-yellow-400',
                    'approved' => 'bg-blue-400',
                    'in_progress' => 'bg-purple-400',
                    'completed' => 'bg-green-400',
                    'cancelled' => 'bg-red-400'
                ];
                ?>
                <?php foreach ($project_stats['by_status'] as $status => $count): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded-full <?php echo $status_colors[$status] ?? 'bg-gray-400'; ?> mr-3"></div>
                        <span class="text-gray-700 capitalize"><?php echo str_replace('_', ' ', $status); ?></span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-900 font-medium mr-2"><?php echo $count; ?></span>
                        <span class="text-gray-500 text-sm"><?php echo $total_projects > 0 ? round(($count / $total_projects) * 100) : 0; ?>%</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="<?php echo $status_colors[$status] ?? 'bg-gray-400'; ?> h-2 rounded-full" 
                         style="width: <?php echo $total_projects > 0 ? ($count / $total_projects) * 100 : 0; ?>%"></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No project data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- POS Payment Methods -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">💳 POS Sales by Payment Method</h2>
        <div class="space-y-3">
            <?php if (!empty($pos_stats_all_time['by_payment_method'])): ?>
                <?php 
                $total_pos_sales = array_sum(array_column($pos_stats_all_time['by_payment_method'], 'count'));
                $payment_colors = ['cash' => 'bg-green-500', 'card' => 'bg-blue-500', 'bank_transfer' => 'bg-purple-500', 'check' => 'bg-yellow-500'];
                ?>
                <?php foreach ($pos_stats_all_time['by_payment_method'] as $payment): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-4 h-4 rounded-full <?php echo $payment_colors[$payment['payment_method']] ?? 'bg-gray-400'; ?> mr-3"></div>
                        <span class="text-gray-700 capitalize"><?php echo str_replace('_', ' ', $payment['payment_method'] ?? 'Unknown'); ?></span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-900 font-medium mr-2"><?php echo $payment['count']; ?></span>
                        <span class="text-gray-500 text-sm"><?php echo formatCurrency($payment['total']); ?></span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="<?php echo $payment_colors[$payment['payment_method']] ?? 'bg-gray-400'; ?> h-2 rounded-full" 
                         style="width: <?php echo $total_pos_sales > 0 ? ($payment['count'] / $total_pos_sales) * 100 : 0; ?>%"></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No POS data available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Enhanced Monthly Trends -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📈 Monthly Business Trends (Last 12 Months)</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projects Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projects Completed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Revenue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">POS Sales</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">POS Revenue</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($monthly_data as $month => $data): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo date('F Y', strtotime($month . '-01')); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['projects_created']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['projects_completed']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatCurrency($data['project_revenue']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['pos_sales']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatCurrency($data['pos_revenue']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo formatCurrency($data['total_revenue']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($report_type === 'overview' || $report_type === 'inventory'): ?>
<!-- Recent Inventory Movements -->
<?php if (!empty($recent_movements)): ?>
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📦 Recent Inventory Movements (Selected Period)</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($recent_movements as $movement): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo date('M j, Y H:i', strtotime($movement['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($movement['brand'] . ' ' . $movement['model']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($movement['size_specification']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $movement['movement_type'] === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo strtoupper($movement['movement_type']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $movement['movement_type'] === 'in' ? '+' : '-'; ?><?php echo $movement['quantity']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div><?php echo ucfirst(str_replace('_', ' ', $movement['reference_type'])); ?></div>
                        <?php if ($movement['reference_id']): ?>
                        <div class="text-xs text-gray-500">#<?php echo $movement['reference_id']; ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($movement['user_name'] ?? 'System'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Inventory by Category -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Inventory Distribution by Category</h2>
    <div class="space-y-3">
        <?php if (!empty($inventory_stats['by_category'])): ?>
            <?php 
            $total_items = array_sum($inventory_stats['by_category']);
            $category_colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-red-500', 'bg-gray-500'];
            $color_index = 0;
            ?>
            <?php foreach ($inventory_stats['by_category'] as $category => $count): ?>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-4 h-4 rounded-full <?php echo $category_colors[$color_index % count($category_colors)]; ?> mr-3"></div>
                    <span class="text-gray-700"><?php echo htmlspecialchars($category); ?></span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-900 font-medium mr-2"><?php echo $count; ?></span>
                    <span class="text-gray-500 text-sm"><?php echo $total_items > 0 ? round(($count / $total_items) * 100) : 0; ?>%</span>
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="<?php echo $category_colors[$color_index % count($category_colors)]; ?> h-2 rounded-full" 
                     style="width: <?php echo $total_items > 0 ? ($count / $total_items) * 100 : 0; ?>%"></div>
            </div>
            <?php $color_index++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">No inventory data available</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Low Stock Alert -->
<?php $low_stock_items = getLowStockItems(); ?>
<?php if (!empty($low_stock_items)): ?>
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
        ⚠️ Low Stock Alert (<?php echo count($low_stock_items); ?> items)
    </h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minimum Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shortage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach (array_slice($low_stock_items, 0, 15) as $item): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium">
                        <?php echo $item['stock_quantity']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $item['minimum_stock']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium">
                        <?php echo max(0, $item['minimum_stock'] - $item['stock_quantity']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="inventory.php?action=edit&id=<?php echo $item['id']; ?>" 
                           class="text-solar-blue hover:underline">Restock</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($low_stock_items) > 15): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center">
                        <a href="inventory.php?filter=low_stock" class="text-solar-blue hover:underline">
                            View all <?php echo count($low_stock_items); ?> low stock items
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Enhanced Export Options -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📤 Export & Actions</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="export_inventory.php" class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-solar-blue hover:bg-blue-50 transition">
            <div class="text-center">
                <i class="fas fa-boxes text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Export Inventory</p>
                <p class="text-xs text-gray-500">Download inventory data as CSV</p>
            </div>
        </a>
        
        <a href="projects.php?export=csv" class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-solar-blue hover:bg-blue-50 transition">
            <div class="text-center">
                <i class="fas fa-project-diagram text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Export Projects</p>
                <p class="text-xs text-gray-500">Download project data as CSV</p>
            </div>
        </a>
        
        <a href="pos.php?export=csv&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
           class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-solar-blue hover:bg-blue-50 transition">
            <div class="text-center">
                <i class="fas fa-cash-register text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Export POS Sales</p>
                <p class="text-xs text-gray-500">Download sales data as CSV</p>
            </div>
        </a>
        
        <button onclick="window.print()" class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-solar-blue hover:bg-blue-50 transition">
            <div class="text-center">
                <i class="fas fa-print text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Print Report</p>
                <p class="text-xs text-gray-500">Print this report page</p>
            </div>
        </button>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    .no-print { display: none !important; }
    .bg-gradient-to-r { background: #f8f9fa !important; color: #000 !important; }
    .text-white { color: #000 !important; }
</style>

<?php include 'includes/footer.php'; ?>
