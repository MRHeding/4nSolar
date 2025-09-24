<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/projects.php';
require_once 'includes/inventory.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$project_id = $_GET['id'] ?? null;

// Check for success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'item_added':
            $message = 'Item added to project successfully!';
            break;
        case 'item_removed':
            $message = 'Item removed from project successfully!';
            break;
    }
}

// Handle form submissions
if ($_POST) {
    switch ($action) {
            
        case 'add_item':
            if ($project_id && isset($_POST['inventory_item_id']) && isset($_POST['quantity'])) {
                if (addProjectItem($project_id, $_POST['inventory_item_id'], $_POST['quantity'])) {
                    $message = 'Item added to project successfully!';
                    // Redirect to project view after successful addition
                    header("Location: ?action=view&id=" . $project_id . "&success=item_added");
                    exit();
                } else {
                    $error = 'Failed to add item to project.';
                }
            }
            break;
            
        case 'update_quantity':
            if (isset($_POST['project_item_id']) && isset($_POST['new_quantity'])) {
                if (updateProjectItemQuantity($_POST['project_item_id'], $_POST['new_quantity'])) {
                    $message = 'Quantity updated successfully!';
                } else {
                    $error = 'Failed to update quantity.';
                }
            }
            break;
    }
}

// Handle delete actions
if ($action == 'delete' && $project_id && hasPermission([ROLE_ADMIN])) {
    if (deleteSolarProject($project_id)) {
        $message = 'Project deleted successfully!';
        $action = 'list';
    } else {
        $error = 'Failed to delete project.';
    }
}

if ($action == 'remove_item' && isset($_GET['item_id'])) {
    if (removeProjectItem($_GET['item_id'])) {
        $message = 'Item removed from project successfully!';
        // Redirect to project view after successful removal
        header("Location: ?action=view&id=" . $project_id . "&success=item_removed");
        exit();
    } else {
        $error = 'Failed to remove item from project.';
    }
}

// Get data based on action
switch ($action) {
    case 'view':
        if ($project_id) {
            $project = getSolarProject($project_id);
            if (!$project) {
                $error = 'Project not found.';
                $action = 'list';
            } else {
                $inventory_items = getInventoryItems();
            }
        }
        break;
        
    default:
        $status_filter = $_GET['status'] ?? null;
        $projects = getSolarProjects($status_filter);
        break;
}

$page_title = 'Approved Projects';
$content_start = true;
include 'includes/header.php';
?>

<?php if ($message): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 alert-auto-hide">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 alert-auto-hide">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
<!-- Projects List -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Solar Projects</h1>
            <p class="text-gray-600 dark:text-gray-400">View approved quotes converted to projects</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <div class="flex flex-wrap gap-4 items-center">
        <div class="flex gap-2">
            <a href="?" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">All Projects</a>
            <a href="?status=draft" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">Draft</a>
            <a href="?status=quoted" class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-md hover:bg-yellow-200 transition">Quoted</a>
            <a href="?status=approved" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition">Approved</a>
            <a href="?status=completed" class="px-4 py-2 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition">Completed</a>
        </div>
        <div class="ml-auto">
            <button onclick="exportToCSV('projects-table', 'projects')" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                <i class="fas fa-download mr-2"></i>Export CSV
            </button>
        </div>
    </div>
</div>

<!-- Projects Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table id="projects-table" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">System Size</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></div>
                        <div class="text-sm text-gray-500">ID: #<?php echo $project['id']; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($project['customer_name']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($project['customer_email']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $project['system_size_kw'] ? $project['system_size_kw'] . ' kW' : 'N/A'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php 
                            switch($project['project_status']) {
                                case 'completed': echo 'bg-green-100 text-green-800'; break;
                                case 'approved': echo 'bg-blue-100 text-blue-800'; break;
                                case 'quoted': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'in_progress': echo 'bg-purple-100 text-purple-800'; break;
                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                default: echo 'bg-gray-100 text-gray-800 dark:text-gray-200';
                            }
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $project['project_status'])); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($project['final_amount']); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                        <?php if (!empty($project['remarks'])): ?>
                            <div class="truncate" title="<?php echo htmlspecialchars($project['remarks']); ?>">
                                <?php echo htmlspecialchars(substr($project['remarks'], 0, 50)) . (strlen($project['remarks']) > 50 ? '...' : ''); ?>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                    </td>
                    <td class="px-3 py-4 whitespace-nowrap text-center">
                        <div class="flex justify-center items-center space-x-2">
                            <a href="?action=view&id=<?php echo $project['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 p-1.5 rounded hover:bg-blue-50 transition" title="View">
                                <i class="fas fa-eye text-sm"></i>
                            </a>
                            <?php if (hasRole(ROLE_ADMIN)): ?>
                            <a href="?action=delete&id=<?php echo $project['id']; ?>" 
                               class="text-red-600 hover:text-red-900 p-1.5 rounded hover:bg-red-50 transition" title="Delete"
                               onclick="return confirmDelete('Are you sure you want to delete this project?')">
                                <i class="fas fa-trash text-sm"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No projects found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php if ($action == 'view' && isset($project)): ?>
<!-- View Project -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($project['project_name']); ?></h1>
            <p class="text-gray-600 dark:text-gray-400">Project #<?php echo $project['id']; ?> - <?php echo htmlspecialchars($project['customer_name']); ?></p>
        </div>
        <div class="space-x-2">
            <a href="?" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Project Items -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Project Items</h2>
                <button onclick="document.getElementById('add-item-modal').classList.remove('hidden')" 
                        class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition text-sm">
                    <i class="fas fa-plus mr-2"></i>Add Item
                </button>
            </div>
            
            <?php if (!empty($project['items'])): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-0">Item</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Qty</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">Stock</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Unit Price</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Discount</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Total</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($project['items'] as $item): ?>
                        <?php 
                        // Get current stock for this item
                        $current_item = getInventoryItem($item['inventory_item_id']);
                        $current_stock = $current_item ? $current_item['stock_quantity'] : 0;
                        $is_low_stock = $current_stock < $item['quantity'];
                        ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-center">
                                <span class="<?php echo $is_low_stock ? 'text-red-600 font-medium' : 'text-gray-900'; ?>">
                                    <?php echo $current_stock; ?>
                                    <?php if ($is_low_stock): ?>
                                    <i class="fas fa-exclamation-triangle ml-1" title="Insufficient stock"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo formatCurrency($item['unit_selling_price']); ?>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo formatCurrency($item['discount_amount']); ?>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                <?php echo formatCurrency($item['total_amount']); ?>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                <a href="?action=remove_item&id=<?php echo $project['id']; ?>&item_id=<?php echo $item['id']; ?>" 
                                   class="text-red-600 hover:text-red-900 p-2 inline-block"
                                   onclick="return confirmDelete('Remove this item from the project?')"
                                   title="Remove item">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-8">No items added to this project yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Project Summary -->
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Project Summary</h2>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                        <?php 
                        switch($project['project_status']) {
                            case 'completed': echo 'bg-green-100 text-green-800'; break;
                            case 'approved': echo 'bg-blue-100 text-blue-800'; break;
                            case 'quoted': echo 'bg-yellow-100 text-yellow-800'; break;
                            case 'in_progress': echo 'bg-purple-100 text-purple-800'; break;
                            case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                            default: echo 'bg-gray-100 text-gray-800 dark:text-gray-200';
                        }
                        ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $project['project_status'])); ?>
                    </span>
                </div>
                
                <div class="border-t pt-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                        <span class="font-medium"><?php echo formatCurrency($project['total_selling_price']); ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Discount:</span>
                        <span class="font-medium text-green-600">-<?php echo formatCurrency($project['total_discount']); ?></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2">
                        <span>Total:</span>
                        <span class="text-solar-blue"><?php echo formatCurrency($project['final_amount']); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($project['remarks'])): ?>
                <div class="border-t pt-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Remarks</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 p-3 rounded-md">
                        <?php echo nl2br(htmlspecialchars($project['remarks'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="border-t pt-4 text-sm text-gray-600 dark:text-gray-400">
                    <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($project['created_at'])); ?></p>
                    <p><strong>By:</strong> <?php echo htmlspecialchars($project['created_by_name']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="add-item-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-5xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Item to Project</h3>
            
            <!-- Search Bar -->
            <div class="mb-4">
                <label for="project-item-search" class="block text-sm font-medium text-gray-700 mb-2">Search Items</label>
                <input type="text" id="project-item-search" placeholder="Search by brand, model, category, or specifications..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       oninput="filterProjectItems()">
            </div>
            
            <!-- Category Filter -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Filter by Category</label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="filterByCategory('')" 
                            class="category-filter-btn px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition active">
                        All Items
                    </button>
                    <?php 
                    $categories = [];
                    foreach ($inventory_items as $item) {
                        if (!empty($item['category_name']) && !in_array($item['category_name'], $categories)) {
                            $categories[] = $item['category_name'];
                        }
                    }
                    foreach ($categories as $category): ?>
                    <button type="button" onclick="filterByCategory('<?php echo strtolower($category); ?>')" 
                            class="category-filter-btn px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Items Grid -->
            <div class="mb-4 max-h-80 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                <div id="project-items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 p-4">
                    <?php foreach ($inventory_items as $inv_item): ?>
                    <div class="project-item-card border border-gray-200 dark:border-gray-600 rounded-lg p-3 hover:bg-blue-50 cursor-pointer transition" 
                         data-item-id="<?php echo $inv_item['id']; ?>"
                         data-brand="<?php echo strtolower($inv_item['brand']); ?>"
                         data-model="<?php echo strtolower($inv_item['model']); ?>"
                         data-category="<?php echo strtolower($inv_item['category_name'] ?? ''); ?>"
                         data-size="<?php echo strtolower($inv_item['size_specification'] ?? ''); ?>"
                         data-price="<?php echo $inv_item['selling_price']; ?>"
                         data-base-price="<?php echo $inv_item['base_price']; ?>"
                         data-stock="<?php echo $inv_item['stock_quantity']; ?>"
                         onclick="selectProjectItem(this)">
                        
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <img class="h-12 w-12 rounded-lg object-cover border" 
                                     src="<?php echo htmlspecialchars(getProductImageUrl($inv_item['image_path'])); ?>" 
                                     alt="<?php echo htmlspecialchars($inv_item['brand'] . ' ' . $inv_item['model']); ?>">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($inv_item['brand']); ?>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                    <?php echo htmlspecialchars($inv_item['model']); ?>
                                </div>
                                <div class="text-xs text-gray-500 truncate">
                                    <?php echo htmlspecialchars($inv_item['category_name'] ?? 'N/A'); ?>
                                </div>
                                <?php if (!empty($inv_item['size_specification'])): ?>
                                <div class="text-xs text-gray-400 truncate">
                                    <?php echo htmlspecialchars($inv_item['size_specification']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-2 flex justify-between items-center">
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">
                                    <?php echo formatCurrency($inv_item['selling_price']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Base: <?php echo formatCurrency($inv_item['base_price']); ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs <?php echo $inv_item['stock_quantity'] > 10 ? 'text-green-600' : ($inv_item['stock_quantity'] > 0 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    Stock: <?php echo $inv_item['stock_quantity']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($inv_item['stock_quantity'] <= 0): ?>
                        <div class="mt-2 text-center">
                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                Out of Stock
                            </span>
                        </div>
                        <?php elseif ($inv_item['stock_quantity'] <= 10): ?>
                        <div class="mt-2 text-center">
                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                Low Stock
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="no-project-items-message" class="hidden text-center py-8 text-gray-500">
                    <i class="fas fa-search text-2xl mb-2"></i>
                    <p>No items found matching your criteria.</p>
                </div>
            </div>
            
            <!-- Selected Item Form -->
            <form id="add-project-item-form" method="POST" action="?action=add_item&id=<?php echo $project['id']; ?>" class="hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h4 class="font-medium text-gray-900 mb-2">Selected Item:</h4>
                    <div id="selected-project-item-display" class="text-sm text-gray-700"></div>
                </div>
                
                <input type="hidden" id="selected_project_inventory_item_id" name="inventory_item_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="project_quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity Needed</label>
                        <input type="number" min="1" id="project_quantity" name="quantity" required value="1"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                               oninput="updateProjectTotalPreview()">
                        <p class="text-xs text-gray-500 mt-1">Available stock: <span id="available-stock">0</span></p>
                    </div>
                    <div>
                        <label for="project_notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <input type="text" id="project_notes" name="notes" placeholder="Installation notes, specifications, etc."
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                    </div>
                </div>
                
                <div id="project-total-preview" class="bg-gray-50 border border-gray-200 dark:border-gray-600 rounded-lg p-3 mb-4 hidden">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Unit Cost:</span>
                            <div class="font-medium" id="unit-cost">₱0.00</div>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Unit Price:</span>
                            <div class="font-medium" id="unit-price">₱0.00</div>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Total Cost:</span>
                            <div class="font-medium text-blue-600" id="total-cost">₱0.00</div>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Total Price:</span>
                            <div class="font-medium text-green-600" id="total-price">₱0.00</div>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t text-xs text-gray-500">
                        Profit Margin: <span id="profit-margin" class="font-medium">₱0.00</span>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="clearProjectSelection()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Clear Selection
                    </button>
                    <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                        Add to Project
                    </button>
                </div>
            </form>
            
            <div class="flex justify-end mt-4">
                <button type="button" onclick="document.getElementById('add-item-modal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedProjectItemData = null;
let currentCategoryFilter = '';

function filterProjectItems() {
    const searchTerm = document.getElementById('project-item-search').value.toLowerCase();
    const itemCards = document.querySelectorAll('.project-item-card');
    let visibleCount = 0;
    
    itemCards.forEach(card => {
        const brand = card.getAttribute('data-brand');
        const model = card.getAttribute('data-model');
        const category = card.getAttribute('data-category');
        const size = card.getAttribute('data-size');
        
        const matchesSearch = brand.includes(searchTerm) || 
                             model.includes(searchTerm) || 
                             category.includes(searchTerm) ||
                             size.includes(searchTerm);
        
        const matchesCategory = currentCategoryFilter === '' || category === currentCategoryFilter;
        
        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    const noItemsMessage = document.getElementById('no-project-items-message');
    if (visibleCount === 0) {
        noItemsMessage.classList.remove('hidden');
    } else {
        noItemsMessage.classList.add('hidden');
    }
}

function filterByCategory(category) {
    currentCategoryFilter = category;
    
    // Update button states
    document.querySelectorAll('.category-filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700');
    });
    
    event.target.classList.remove('bg-gray-100', 'text-gray-700', 'bg-blue-100', 'text-blue-700');
    event.target.classList.add('active', 'bg-blue-600', 'text-white');
    
    filterProjectItems();
}

function selectProjectItem(cardElement) {
    // Remove previous selection
    document.querySelectorAll('.project-item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Mark current selection
    cardElement.classList.add('bg-blue-100', 'border-blue-500');
    
    // Store selected item data
    selectedProjectItemData = {
        id: cardElement.getAttribute('data-item-id'),
        brand: cardElement.querySelector('.text-sm.font-medium').textContent,
        model: cardElement.querySelector('.text-sm.text-gray-600 dark:text-gray-400').textContent,
        category: cardElement.querySelector('.text-xs.text-gray-500').textContent,
        price: parseFloat(cardElement.getAttribute('data-price')),
        basePrice: parseFloat(cardElement.getAttribute('data-base-price')),
        stock: parseInt(cardElement.getAttribute('data-stock'))
    };
    
    // Update form
    document.getElementById('selected_project_inventory_item_id').value = selectedProjectItemData.id;
    document.getElementById('selected-project-item-display').innerHTML = 
        `<strong>${selectedProjectItemData.brand}</strong> - ${selectedProjectItemData.model}<br>
         <span class="text-gray-600 dark:text-gray-400">${selectedProjectItemData.category}</span><br>
         Cost: ${formatCurrency(selectedProjectItemData.basePrice)} | Price: ${formatCurrency(selectedProjectItemData.price)}`;
    
    document.getElementById('available-stock').textContent = selectedProjectItemData.stock;
    
    // Show form and update preview
    document.getElementById('add-project-item-form').classList.remove('hidden');
    updateProjectTotalPreview();
    
    // Update quantity max
    document.getElementById('project_quantity').max = selectedProjectItemData.stock;
}

function clearProjectSelection() {
    // Clear visual selection
    document.querySelectorAll('.project-item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Hide form
    document.getElementById('add-project-item-form').classList.add('hidden');
    selectedProjectItemData = null;
}

function updateProjectTotalPreview() {
    if (!selectedProjectItemData) return;
    
    const quantity = parseInt(document.getElementById('project_quantity').value) || 0;
    
    const totalCost = selectedProjectItemData.basePrice * quantity;
    const totalPrice = selectedProjectItemData.price * quantity;
    const profitMargin = totalPrice - totalCost;
    
    document.getElementById('unit-cost').textContent = formatCurrency(selectedProjectItemData.basePrice);
    document.getElementById('unit-price').textContent = formatCurrency(selectedProjectItemData.price);
    document.getElementById('total-cost').textContent = formatCurrency(totalCost);
    document.getElementById('total-price').textContent = formatCurrency(totalPrice);
    document.getElementById('profit-margin').textContent = formatCurrency(profitMargin);
    
    document.getElementById('project-total-preview').classList.remove('hidden');
}

function formatCurrency(amount) {
    return '₱' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}
</script>


<?php endif; ?>

<?php include 'includes/footer.php'; ?>
