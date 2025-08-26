<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/projects.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get statistics for reports
$inventory_stats = [
    'total_items' => count(getInventoryItems()),
    'low_stock_count' => count(getLowStockItems()),
    'total_value' => 0,
    'by_category' => []
];

$project_stats = getProjectStats();
$all_projects = getSolarProjects();
$all_inventory = getInventoryItems();

// Calculate inventory value
foreach ($all_inventory as $item) {
    $inventory_stats['total_value'] += $item['stock_quantity'] * $item['base_price'];
}

// Group inventory by category
$categories_count = [];
foreach ($all_inventory as $item) {
    $category = $item['category_name'] ?? 'Uncategorized';
    if (!isset($categories_count[$category])) {
        $categories_count[$category] = 0;
    }
    $categories_count[$category]++;
}
$inventory_stats['by_category'] = $categories_count;

// Monthly project data for chart
$monthly_projects = [];
$monthly_revenue = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthly_projects[$month] = 0;
    $monthly_revenue[$month] = 0;
}

foreach ($all_projects as $project) {
    $month = date('Y-m', strtotime($project['created_at']));
    if (isset($monthly_projects[$month])) {
        $monthly_projects[$month]++;
        if (in_array($project['project_status'], ['approved', 'completed'])) {
            $monthly_revenue[$month] += $project['final_amount'];
        }
    }
}

$page_title = 'Reports';
$content_start = true;
include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
    <p class="text-gray-600">Business insights and performance metrics</p>
</div>

<!-- Overview Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100">
                <i class="fas fa-boxes text-solar-blue text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Inventory Items</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo $inventory_stats['total_items']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
                <i class="fas fa-dollar-sign text-solar-yellow text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Inventory Value</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo formatCurrency($inventory_stats['total_value'], 0); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100">
                <i class="fas fa-project-diagram text-solar-green text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Projects</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo count($all_projects); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100">
                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo formatCurrency($project_stats['total_revenue'], 0); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Project Status Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Projects by Status</h2>
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

    <!-- Inventory by Category -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Inventory by Category</h2>
        <div class="space-y-3">
            <?php if (!empty($inventory_stats['by_category'])): ?>
                <?php 
                $total_items = array_sum($inventory_stats['by_category']);
                ?>
                <?php foreach ($inventory_stats['by_category'] as $category => $count): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700"><?php echo htmlspecialchars($category); ?></span>
                    <div class="flex items-center">
                        <span class="text-gray-900 font-medium mr-2"><?php echo $count; ?></span>
                        <span class="text-gray-500 text-sm"><?php echo $total_items > 0 ? round(($count / $total_items) * 100) : 0; ?>%</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" 
                         style="width: <?php echo $total_items > 0 ? ($count / $total_items) * 100 : 0; ?>%"></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No inventory data available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Monthly Trends -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Monthly Trends (Last 12 Months)</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projects Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue Generated</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($monthly_projects as $month => $project_count): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo date('F Y', strtotime($month . '-01')); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $project_count; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($monthly_revenue[$month]); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Low Stock Alert -->
<?php $low_stock_items = getLowStockItems(); ?>
<?php if (!empty($low_stock_items)): ?>
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
        Low Stock Items (<?php echo count($low_stock_items); ?>)
    </h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minimum Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach (array_slice($low_stock_items, 0, 10) as $item): ?>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($low_stock_items) > 10): ?>
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center">
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

<!-- Export Options -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Export Data</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="inventory.php" class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-solar-blue hover:bg-blue-50 transition">
            <div class="text-center">
                <i class="fas fa-boxes text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Export Inventory</p>
                <p class="text-xs text-gray-500">Download inventory data as CSV</p>
            </div>
        </a>
        
        <a href="projects.php" class="flex items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-solar-blue hover:bg-blue-50 transition">
            <div class="text-center">
                <i class="fas fa-project-diagram text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Export Projects</p>
                <p class="text-xs text-gray-500">Download project data as CSV</p>
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

<?php include 'includes/footer.php'; ?>
