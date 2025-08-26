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

$page_title = 'Dashboard';
$content_start = true;

// Get dashboard statistics
$total_items = count(getInventoryItems());
$low_stock_items = getLowStockItems();
$total_projects = count(getSolarProjects());
$project_stats = getProjectStats();
$pos_stats = getPOSStats(date('Y-m-d'), date('Y-m-d')); // Today's POS stats

// Get recent projects and POS sales
$recent_projects = array_slice(getSolarProjects(), 0, 5);
try {
    $recent_pos_sales = array_slice(getPOSSales('completed', null, null), 0, 10); // Get last 10 completed sales
} catch (Exception $e) {
    $recent_pos_sales = []; // Fallback to empty array if POS function fails
}

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-600">Welcome to 4NSOLAR ELECTRICZ Inventory Management System</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
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

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100">
                <i class="fas fa-cash-register text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Today's Sales</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo $pos_stats['today_sales']; ?></p>
                <p class="text-xs text-gray-500">
                    <?php echo formatCurrency($pos_stats['today_revenue']); ?> revenue
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
                <i class="fas fa-dollar-sign text-solar-yellow text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo formatCurrency($project_stats['total_revenue'] + $pos_stats['today_revenue'], 0); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-bolt text-solar-blue mr-2"></i>
            Quick Actions
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="pos.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                <i class="fas fa-cash-register text-green-600 text-xl mr-3"></i>
                <span class="font-medium text-gray-900">Start New Sale</span>
            </a>
            <a href="inventory.php?action=create" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                <i class="fas fa-plus text-blue-600 text-xl mr-3"></i>
                <span class="font-medium text-gray-900">Add Inventory</span>
            </a>
            <a href="projects.php?action=create" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                <i class="fas fa-project-diagram text-purple-600 text-xl mr-3"></i>
                <span class="font-medium text-gray-900">New Project</span>
            </a>
            <a href="reports.php" class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                <i class="fas fa-chart-bar text-yellow-600 text-xl mr-3"></i>
                <span class="font-medium text-gray-900">View Reports</span>
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
    <!-- Low Stock Alert -->
    <?php if (!empty($low_stock_items)): ?>
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    Low Stock Alert
                </h2>
                <a href="inventory.php?filter=low_stock" class="text-solar-blue hover:underline text-sm">View All</a>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php foreach (array_slice($low_stock_items, 0, 5) as $item): ?>
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($item['size_specification']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-red-600">Stock: <?php echo $item['stock_quantity']; ?></p>
                        <p class="text-xs text-gray-500">Min: <?php echo $item['minimum_stock']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Projects -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-project-diagram text-solar-blue mr-2"></i>
                    Recent Projects
                </h2>
                <a href="projects.php" class="text-solar-blue hover:underline text-sm">View All</a>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($recent_projects)): ?>
            <div class="space-y-4">
                <?php foreach ($recent_projects as $project): ?>
                <div class="flex items-center justify-between p-3 border rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($project['customer_name']); ?></p>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php 
                            switch($project['project_status']) {
                                case 'completed': echo 'bg-green-100 text-green-800'; break;
                                case 'approved': echo 'bg-blue-100 text-blue-800'; break;
                                case 'quoted': echo 'bg-yellow-100 text-yellow-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($project['project_status']); ?>
                        </span>
                        <p class="text-sm font-medium text-gray-900 mt-1"><?php echo formatCurrency($project['final_amount']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-4">No projects yet. <a href="projects.php" class="text-solar-blue hover:underline">Create your first project</a></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Project Status Overview -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-chart-pie text-solar-green mr-2"></i>
                Project Status Overview
            </h2>
        </div>
        <div class="p-6">
            <?php if (!empty($project_stats['by_status'])): ?>
            <div class="space-y-3">
                <?php foreach ($project_stats['by_status'] as $status => $count): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 capitalize"><?php echo $status; ?></span>
                    <span class="font-medium text-gray-900"><?php echo $count; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-4">No project data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-cash-register text-solar-yellow mr-2"></i>
                Recent POS Transactions
            </h2>
        </div>
        <div class="p-6">
            <?php if (!empty($recent_pos_sales)): ?>
            <div class="space-y-3">
                <?php foreach (array_slice($recent_pos_sales, 0, 5) as $sale): ?>
                <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-gray-900">Receipt #<?php echo htmlspecialchars($sale['receipt_number']); ?></p>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                <?php echo ucfirst($sale['status']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-600">
                            <?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?>
                            <?php if (!empty($sale['customer_phone'])): ?>
                            • <?php echo htmlspecialchars($sale['customer_phone']); ?>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-gray-500">
                            <?php echo date('M j, Y g:i A', strtotime($sale['created_at'])); ?>
                            • <?php echo $sale['items_count']; ?> item(s)
                            <?php if (!empty($sale['cashier_name'])): ?>
                            • by <?php echo htmlspecialchars($sale['cashier_name']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="text-right ml-4">
                        <p class="text-lg font-semibold text-gray-900"><?php echo formatCurrency($sale['total_amount']); ?></p>
                        <?php if ($sale['payment_method']): ?>
                        <p class="text-xs text-gray-500 capitalize"><?php echo htmlspecialchars($sale['payment_method']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($recent_pos_sales) > 5): ?>
                <div class="text-center pt-3 border-t">
                    <a href="pos.php?action=history" class="text-solar-blue hover:underline text-sm">
                        View All Transactions (<?php echo count($recent_pos_sales); ?> total)
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-cash-register text-gray-300 text-3xl mb-3"></i>
                <p class="text-gray-500 mb-3">No POS transactions yet</p>
                <a href="pos.php" class="inline-flex items-center px-4 py-2 bg-solar-blue text-white rounded-lg hover:bg-blue-800 transition">
                    <i class="fas fa-plus mr-2"></i>Start Your First Sale
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
