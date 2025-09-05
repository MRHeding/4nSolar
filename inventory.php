<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/suppliers.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$item_id = $_GET['id'] ?? null;

// Check for success messages from redirects
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'add':
            if (hasPermission([ROLE_ADMIN, ROLE_HR, ROLE_SALES])) {
                $data = [
                    'brand' => $_POST['brand'],
                    'model' => $_POST['model'],
                    'category_id' => $_POST['category_id'],
                    'size_specification' => $_POST['size_specification'],
                    'base_price' => $_POST['base_price'],
                    'selling_price' => $_POST['selling_price'],
                    'discount_percentage' => $_POST['discount_percentage'] ?? 0,
                    'supplier_id' => $_POST['supplier_id'],
                    'stock_quantity' => $_POST['stock_quantity'],
                    'minimum_stock' => $_POST['minimum_stock'],
                    'description' => $_POST['description']
                ];
                
                $image_file = isset($_FILES['product_image']) ? $_FILES['product_image'] : null;
                
                if (addInventoryItem($data, $image_file)) {
                    header("Location: inventory.php?message=" . urlencode('Item added successfully!'));
                    exit();
                } else {
                    $error = 'Failed to add item. Please check the image format and size.';
                }
            } else {
                $error = 'You do not have permission to add items.';
            }
            break;
            
        case 'edit':
            if (hasPermission([ROLE_ADMIN, ROLE_HR, ROLE_SALES]) && $item_id) {
                // Get current item to preserve existing image path
                $current_item = getInventoryItem($item_id);
                
                $data = [
                    'brand' => $_POST['brand'],
                    'model' => $_POST['model'],
                    'category_id' => $_POST['category_id'],
                    'size_specification' => $_POST['size_specification'],
                    'base_price' => $_POST['base_price'],
                    'selling_price' => $_POST['selling_price'],
                    'discount_percentage' => $_POST['discount_percentage'] ?? 0,
                    'supplier_id' => $_POST['supplier_id'],
                    'stock_quantity' => $_POST['stock_quantity'],
                    'minimum_stock' => $_POST['minimum_stock'],
                    'description' => $_POST['description'],
                    'current_image_path' => $current_item['image_path'] ?? null
                ];
                
                $image_file = isset($_FILES['product_image']) ? $_FILES['product_image'] : null;
                
                if (updateInventoryItem($item_id, $data, $image_file)) {
                    header("Location: inventory.php?message=" . urlencode('Item updated successfully!'));
                    exit();
                } else {
                    $error = 'Failed to update item. Please check the image format and size.';
                }
            }
            break;
            
        case 'update_stock':
            if (hasPermission([ROLE_ADMIN, ROLE_HR, ROLE_SALES]) && $item_id) {
                $new_quantity = $_POST['new_quantity'];
                $movement_type = $new_quantity > $_POST['current_quantity'] ? 'in' : 'out';
                $notes = $_POST['notes'] ?? '';
                
                if (updateStock($item_id, $new_quantity, $movement_type, 'adjustment', null, $notes)) {
                    header("Location: inventory.php?action=view&id=" . $item_id . "&message=" . urlencode('Stock updated successfully!'));
                    exit();
                } else {
                    $error = 'Failed to update stock.';
                }
            }
            break;
    }
}

// Handle delete action
if ($action == 'delete' && $item_id && hasPermission([ROLE_ADMIN])) {
    if (deleteInventoryItem($item_id)) {
        header("Location: inventory.php?message=" . urlencode('Item deleted successfully!'));
        exit();
    } else {
        $error = 'Failed to delete item.';
        $action = 'list';
    }
}

// Get data based on action
switch ($action) {
    case 'add':
    case 'edit':
        $suppliers = getSuppliers();
        $categories = getCategories();
        if ($action == 'edit' && $item_id) {
            $item = getInventoryItem($item_id);
            if (!$item) {
                $error = 'Item not found.';
                $action = 'list';
            }
        }
        break;
        
    case 'view':
        if ($item_id) {
            $item = getInventoryItem($item_id);
            $stock_movements = getStockMovements($item_id);
            if (!$item) {
                $error = 'Item not found.';
                $action = 'list';
            }
        }
        break;
        
    default:
        $filter = $_GET['filter'] ?? null;
        $category_filter = $_GET['category'] ?? null;
        $brand_filter = $_GET['brand'] ?? null;
        
        if ($filter == 'low_stock') {
            $items = getLowStockItems();
        } else {
            $items = getInventoryItems($category_filter, $brand_filter);
        }
        $categories = getCategories();
        $available_brands = getAvailableBrands();
        break;
}

$page_title = 'Inventory Management';
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
<!-- Inventory List -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Inventory Management</h1>
            <p class="text-gray-600">Manage your solar equipment inventory</p>
        </div>
            <div class="space-x-2">
                <?php if (hasPermission([ROLE_ADMIN, ROLE_HR, ROLE_SALES])): ?>
                <a href="?action=add" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
                    <i class="fas fa-plus mr-2"></i>Add Item
                </a>
                <?php endif; ?>
            </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <!-- Category Filter Section -->
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Category Filter</label>
            <select onchange="updateFilters('category', this.value)" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent category-select"
                    style="max-height: 200px; overflow-y: auto;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Brand Filter Section -->
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Brand Filter</label>
            <select onchange="updateFilters('brand', this.value)" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent brand-select"
                    style="max-height: 200px; overflow-y: auto;">
                <option value="">All Brands</option>
                <?php 
                $priority_brands = ['Canadian Solar', 'OSDA', 'SUNRI'];
                // Show priority brands first
                foreach ($priority_brands as $priority_brand): 
                    if (in_array($priority_brand, $available_brands)): ?>
                    <option value="<?php echo htmlspecialchars($priority_brand); ?>" <?php echo ($brand_filter == $priority_brand) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($priority_brand); ?>
                    </option>
                    <?php endif;
                endforeach; 
                
                // Then show other brands
                foreach ($available_brands as $brand): 
                    if (!in_array($brand, $priority_brands)): ?>
                    <option value="<?php echo htmlspecialchars($brand); ?>" <?php echo ($brand_filter == $brand) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brand); ?>
                    </option>
                    <?php endif;
                endforeach; ?>
            </select>
        </div>
        
        <!-- Quick Filter Buttons -->
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Quick Filters</label>
            <div class="flex gap-2 flex-wrap">
                <a href="?" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition text-xs">
                    <i class="fas fa-list mr-1"></i>All Items
                </a>
                <a href="?filter=low_stock" class="px-3 py-1.5 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition text-xs">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Low Stock
                </a>
            </div>
            <div class="flex gap-2 flex-wrap mt-2">
                <a href="?brand=Canadian+Solar" class="px-3 py-1.5 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition text-xs">
                    Canadian Solar
                </a>
                <a href="?brand=OSDA" class="px-3 py-1.5 bg-green-600 text-white rounded-md hover:bg-green-700 transition text-xs">
                    OSDA
                </a>
                <a href="?brand=SUNRI" class="px-3 py-1.5 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition text-xs">
                    SUNRI
                </a>
            </div>
        </div>
        
        <!-- Export Section -->
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Export & Print</label>
            <div class="flex gap-2">
                <button onclick="exportToCSV('inventory-table', 'inventory')" 
                        class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition text-sm">
                    <i class="fas fa-download mr-1"></i>Quick CSV
                </button>
                <div class="relative">
                    <button onclick="toggleExportMenu()" 
                            class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm">
                        <i class="fas fa-file-export mr-1"></i>Export <i class="fas fa-chevron-down ml-1"></i>
                    </button>
                    <div id="export-menu" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10 min-w-56 max-h-80 overflow-y-auto">
                        <!-- Category-specific exports -->
                        <div class="px-4 py-2 border-b border-gray-200 sticky top-0 bg-white z-10">
                            <div class="text-xs font-medium text-gray-500 uppercase">By Category</div>
                        </div>
                        
                        <!-- All Items Export -->
                        <a href="export_inventory.php?format=csv<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $brand_filter ? '&brand=' . urlencode($brand_filter) : ''; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>" 
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-file-csv text-green-600 mr-2"></i>
                            All Items (CSV)
                        </a>
                        
                        <!-- All Category CSV Exports -->
                        <?php foreach ($categories as $category): ?>
                        <a href="export_inventory.php?format=csv&category=<?php echo $category['id']; ?>" 
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-file-csv text-blue-600 mr-2"></i>
                            <?php echo htmlspecialchars($category['name']); ?> (CSV)
                        </a>
                        <?php endforeach; ?>
                        
                        <div class="border-t border-gray-200"></div>
                        
                        <!-- JSON Exports -->
                        <div class="px-4 py-2 border-b border-gray-200 sticky top-0 bg-white z-10">
                            <div class="text-xs font-medium text-gray-500 uppercase">JSON Format</div>
                        </div>
                        
                        <a href="export_inventory.php?format=json<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $brand_filter ? '&brand=' . urlencode($brand_filter) : ''; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>" 
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-file-code text-purple-600 mr-2"></i>
                            All Items (JSON)
                        </a>
                        
                        <!-- All Category JSON Exports -->
                        <?php foreach ($categories as $category): ?>
                        <a href="export_inventory.php?format=json&category=<?php echo $category['id']; ?>" 
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-file-code text-indigo-600 mr-2"></i>
                            <?php echo htmlspecialchars($category['name']); ?> (JSON)
                        </a>
                        <?php endforeach; ?>
                        
                        <div class="border-t border-gray-200"></div>
                        
                        <!-- Print Options -->
                        <div class="px-4 py-2 border-b border-gray-200 sticky top-0 bg-white z-10">
                            <div class="text-xs font-medium text-gray-500 uppercase">Print Options</div>
                        </div>
                        
                        <button onclick="printInventoryReport('all')" 
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left">
                            <i class="fas fa-print text-gray-600 mr-2"></i>
                            Print All Items
                        </button>
                        
                        <button onclick="printInventoryReport('current')" 
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left">
                            <i class="fas fa-print text-blue-600 mr-2"></i>
                            Print Current View
                        </button>
                        
                        <!-- All Category Print Options -->
                        <?php foreach ($categories as $category): ?>
                        <button onclick="printInventoryReport('category', <?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" 
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left">
                            <i class="fas fa-print text-orange-600 mr-2"></i>
                            Print <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                        <?php endforeach; ?>
                        
                        <div class="border-t border-gray-200"></div>
                        
                        <!-- Special Reports -->
                        <div class="px-4 py-2 border-b border-gray-200">
                            <div class="text-xs font-medium text-gray-500 uppercase">Special Reports</div>
                        </div>
                        
                        <a href="export_inventory.php?format=csv&filter=low_stock" 
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                            Low Stock Report (CSV)
                        </a>
                        
                        <button onclick="printInventoryReport('low_stock')" 
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left">
                            <i class="fas fa-print text-red-600 mr-2"></i>
                            Print Low Stock Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Status Display -->
    <?php if ($category_filter || $brand_filter || isset($_GET['filter'])): ?>
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Active filters:</span>
                <?php if ($category_filter): ?>
                    <?php 
                    $active_category = array_filter($categories, function($cat) use ($category_filter) {
                        return $cat['id'] == $category_filter;
                    });
                    $active_category = reset($active_category);
                    ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Category: <?php echo htmlspecialchars($active_category['name']); ?>
                    </span>
                <?php endif; ?>
                <?php if ($brand_filter): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Brand: <?php echo htmlspecialchars($brand_filter); ?>
                    </span>
                <?php endif; ?>
                <?php if (isset($_GET['filter']) && $_GET['filter'] == 'low_stock'): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Low Stock Items
                    </span>
                <?php endif; ?>
            </div>
            <a href="?" class="text-sm text-gray-500 hover:text-gray-700 transition">
                <i class="fas fa-times mr-1"></i>Clear all filters
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Search Bar for Inventory Items -->
<div class="bg-white rounded-lg shadow p-4 mb-4">
    <div class="flex items-center space-x-4">
        <div class="flex-1">
            <label for="inventory-search" class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-search mr-2"></i>Search Inventory Items
            </label>
            <input type="text" 
                   id="inventory-search" 
                   placeholder="Search by brand, model, category, or size specification..." 
                   class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                   oninput="filterInventoryItems()">
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-lightbulb mr-1"></i>
                Tip: Use <kbd class="px-1 py-0.5 bg-gray-100 rounded text-xs">Ctrl+F</kbd> to focus search, <kbd class="px-1 py-0.5 bg-gray-100 rounded text-xs">Esc</kbd> to clear
            </p>
        </div>
        <div class="flex flex-col">
            <label class="block text-sm font-medium text-gray-700 mb-2">Quick Actions</label>
            <div class="flex space-x-2 search-actions">
                <button onclick="clearInventorySearch()" 
                        class="px-3 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition text-sm">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
                <button onclick="focusInventorySearch()" 
                        class="px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-sm">
                    <i class="fas fa-search mr-1"></i>Focus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Items Count and Scroll Info -->
<?php if (!empty($items)): ?>
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            <span class="text-sm text-blue-800">
                Showing <strong><span id="visible-items-count"><?php echo count($items); ?></span></strong> of <strong><?php echo count($items); ?></strong> items
                <?php if (count($items) > 5): ?>
                - <em>Scroll down in the table below to view all items</em>
                <?php endif; ?>
            </span>
        </div>
        <?php if (count($items) > 10): ?>
        <div class="text-xs text-blue-600">
            <i class="fas fa-mouse mr-1"></i>Use mouse wheel or scroll bar to navigate
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Search Results Info -->
    <div id="search-results-info" class="hidden mt-2 pt-2 border-t border-blue-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-blue-700">
                <i class="fas fa-filter mr-2"></i>
                <span id="search-results-text"></span>
                <button onclick="clearInventorySearch()" class="ml-2 text-blue-600 hover:text-blue-800 underline">
                    Clear search
                </button>
            </div>
            <div class="flex space-x-2">
                <button onclick="exportFilteredToCSV()" 
                        class="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition">
                    <i class="fas fa-download mr-1"></i>Export Results
                </button>
                <button onclick="printInventoryReport('filtered')" 
                        class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 transition">
                    <i class="fas fa-print mr-1"></i>Print Results
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Inventory Table -->
<div class="bg-white rounded-lg shadow overflow-hidden inventory-table-container relative">
    <!-- Fixed Table Header -->
    <div class="bg-gray-50 border-b border-gray-200">
        <div class="min-w-full">
            <div class="grid grid-cols-7 gap-4 px-6 py-3">
                <div class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Details</div>
                <div class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</div>
                <div class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</div>
                <div class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pricing</div>
                <div class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</div>
                <div class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</div>
                <div class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</div>
            </div>
        </div>
    </div>
    
    <!-- Scrollable Table Body -->
    <div class="overflow-y-auto max-h-96 inventory-scroll-container">
        <table id="inventory-table" class="min-w-full divide-y divide-gray-200">
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50 inventory-item-row" 
                    data-brand="<?php echo strtolower(htmlspecialchars($item['brand'])); ?>"
                    data-model="<?php echo strtolower(htmlspecialchars($item['model'])); ?>"
                    data-category="<?php echo strtolower(htmlspecialchars($item['category_name'] ?? '')); ?>"
                    data-size="<?php echo strtolower(htmlspecialchars($item['size_specification'])); ?>"
                    data-supplier="<?php echo strtolower(htmlspecialchars($item['supplier_name'] ?? '')); ?>"
                    data-full-text="<?php echo strtolower(htmlspecialchars($item['brand'] . ' ' . $item['model'] . ' ' . ($item['category_name'] ?? '') . ' ' . $item['size_specification'] . ' ' . ($item['supplier_name'] ?? '') . ' ' . ($item['description'] ?? ''))); ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-16 w-16">
                                <a href="?action=view&id=<?php echo $item['id']; ?>">
                                    <img class="h-16 w-16 rounded-lg object-cover border hover:opacity-80 transition cursor-pointer" 
                                         src="<?php echo htmlspecialchars(getProductImageUrl($item['image_path'])); ?>" 
                                         alt="<?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?>"
                                         title="Click to view details">
                                </a>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <a href="?action=view&id=<?php echo $item['id']; ?>" class="hover:text-solar-blue transition">
                                        <?php echo htmlspecialchars($item['brand']); ?>
                                    </a>
                                </div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['model']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($item['size_specification']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div>Base: <?php echo formatCurrency($item['base_price']); ?></div>
                        <div>Sell: <?php echo formatCurrency($item['selling_price']); ?></div>
                        <?php if ($item['discount_percentage'] > 0): ?>
                        <div class="text-green-600">-<?php echo $item['discount_percentage']; ?>%</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo $item['stock_quantity']; ?></div>
                        <?php if ($item['stock_quantity'] <= $item['minimum_stock']): ?>
                        <div class="text-xs text-red-600">Low Stock!</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <a href="?action=view&id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission([ROLE_ADMIN, ROLE_HR, ROLE_SALES])): ?>
                        <a href="?action=edit&id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasRole(ROLE_ADMIN)): ?>
                        <a href="?action=delete&id=<?php echo $item['id']; ?>" class="text-red-600 hover:text-red-900" 
                           onclick="return confirmDelete('Are you sure you want to delete this item?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr id="no-items-row">
                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No items found.
                    </td>
                </tr>
            <?php endif; ?>
            
            <!-- Search No Results Message -->
            <tr id="no-search-results" class="hidden">
                <td colspan="7" class="px-6 py-8 text-center">
                    <div class="text-gray-500">
                        <i class="fas fa-search text-3xl mb-3"></i>
                        <p class="text-lg font-medium mb-2">No items found</p>
                        <p class="text-sm">Try adjusting your search terms or <button onclick="clearInventorySearch()" class="text-blue-600 hover:text-blue-800 underline">clear the search</button> to see all items.</p>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Scroll to top button for inventory table -->
    <div id="scroll-to-top-inventory" class="hidden absolute bottom-4 right-4 z-10">
        <button onclick="scrollInventoryToTop()" 
                class="bg-blue-600 text-white p-2 rounded-full shadow-lg hover:bg-blue-700 transition-all duration-300 opacity-90 hover:opacity-100" 
                title="Scroll to top of inventory">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>
</div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
<!-- Add/Edit Form -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800"><?php echo $action == 'add' ? 'Add New' : 'Edit'; ?> Inventory Item</h1>
    <p class="text-gray-600">Enter the details for the inventory item</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Product Image -->
            <div class="md:col-span-2">
                <label for="product_image" class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                <?php if (isset($item) && $item['image_path']): ?>
                <div class="mb-3">
                    <img src="<?php echo htmlspecialchars(getProductImageUrl($item['image_path'])); ?>" 
                         alt="Current Product Image" class="h-32 w-32 object-cover rounded-lg border">
                    <p class="text-sm text-gray-500 mt-1">Current image</p>
                </div>
                <?php endif; ?>
                <input type="file" id="product_image" name="product_image" 
                       accept="image/jpeg,image/jpg,image/png,image/gif"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                <p class="text-sm text-gray-500 mt-1">Accepted formats: JPG, PNG, GIF. Max size: 5MB</p>
            </div>
            
            <div>
                <label for="brand" class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                <input type="text" id="brand" name="brand" required
                       value="<?php echo isset($item) ? htmlspecialchars($item['brand']) : ''; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div>
                <label for="model" class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                <input type="text" id="model" name="model" required
                       value="<?php echo isset($item) ? htmlspecialchars($item['model']) : ''; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select id="category_id" name="category_id" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" 
                            <?php echo (isset($item) && $item['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="size_specification" class="block text-sm font-medium text-gray-700 mb-2">Size/Specification</label>
                <input type="text" id="size_specification" name="size_specification"
                       value="<?php echo isset($item) ? htmlspecialchars($item['size_specification']) : ''; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div>
                <label for="base_price" class="block text-sm font-medium text-gray-700 mb-2">Base Price (₱)</label>
                <input type="number" step="0.01" id="base_price" name="base_price" required
                       value="<?php echo isset($item) ? $item['base_price'] : ''; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div>
                <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-2">Selling Price (₱)</label>
                <input type="number" step="0.01" id="selling_price" name="selling_price" required
                       value="<?php echo isset($item) ? $item['selling_price'] : ''; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div>
                <label for="discount_percentage" class="block text-sm font-medium text-gray-700 mb-2">Discount (%)</label>
                <input type="number" step="0.01" min="0" max="100" id="discount_percentage" name="discount_percentage"
                       value="<?php echo isset($item) ? $item['discount_percentage'] : '0'; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div>
                <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                <select id="supplier_id" name="supplier_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                    <option value="">Select Supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>" 
                            <?php echo (isset($item) && $item['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                <input type="number" min="0" id="stock_quantity" name="stock_quantity" required
                       value="<?php echo isset($item) ? $item['stock_quantity'] : '0'; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
            
            <div>
                <label for="minimum_stock" class="block text-sm font-medium text-gray-700 mb-2">Minimum Stock</label>
                <input type="number" min="0" id="minimum_stock" name="minimum_stock" required
                       value="<?php echo isset($item) ? $item['minimum_stock'] : '10'; ?>"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
            </div>
        </div>
        
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea id="description" name="description" rows="3"
                      class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"><?php echo isset($item) ? htmlspecialchars($item['description']) : ''; ?></textarea>
        </div>
        
        <div class="flex justify-end space-x-4">
            <a href="?" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                <?php echo $action == 'add' ? 'Add Item' : 'Update Item'; ?>
            </button>
        </div>
    </form>
</div>

<?php elseif ($action == 'view' && isset($item)): ?>
<!-- View Item Details -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($item['description']); ?></p>
        </div>
        <div class="space-x-2">
            <?php if (hasPermission([ROLE_ADMIN, ROLE_HR, ROLE_SALES])): ?>
            <a href="?action=edit&id=<?php echo $item['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <button onclick="document.getElementById('stock-modal').classList.remove('hidden')" 
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-boxes mr-2"></i>Update Stock
            </button>
            <?php endif; ?>
            <a href="?" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- Item Details -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Item Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Brand</label>
                    <p class="text-gray-900"><?php echo htmlspecialchars($item['brand']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Model</label>
                    <p class="text-gray-900"><?php echo htmlspecialchars($item['model']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Category</label>
                    <p class="text-gray-900"><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Size/Specification</label>
                    <p class="text-gray-900"><?php echo htmlspecialchars($item['size_specification']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Base Price</label>
                    <p class="text-gray-900"><?php echo formatCurrency($item['base_price']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Selling Price</label>
                    <p class="text-gray-900"><?php echo formatCurrency($item['selling_price']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Discount</label>
                    <p class="text-gray-900"><?php echo $item['discount_percentage']; ?>%</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Supplier</label>
                    <p class="text-gray-900"><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></p>
                </div>
            </div>
            
            <?php if (!empty($item['description'])): ?>
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-500 mb-2">Description</label>
                <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Image -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Product Image</h2>
            <div class="text-center">
                <img src="<?php echo htmlspecialchars(getProductImageUrl($item['image_path'])); ?>" 
                     alt="<?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?>"
                     class="w-full max-w-xs mx-auto rounded-lg shadow-md border border-gray-200 cursor-pointer"
                     onclick="openImageModal(this.src)">
                <p class="text-xs text-gray-500 mt-1">Click to view full size</p>
            </div>
        </div>
    </div>
</div>

<!-- Stock Information and Quick Stats -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mt-6">
    <!-- Stock Information -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Stock Information</h2>
            <div class="space-y-4">
                <div class="text-center">
                    <div class="text-3xl font-bold <?php echo $item['stock_quantity'] <= $item['minimum_stock'] ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo $item['stock_quantity']; ?>
                    </div>
                    <div class="text-sm text-gray-500">Current Stock</div>
                </div>
                
                <div class="border-t pt-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Minimum Stock:</span>
                        <span class="font-medium"><?php echo $item['minimum_stock']; ?></span>
                    </div>
                </div>
                
                <?php if ($item['stock_quantity'] <= $item['minimum_stock']): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 mr-2"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800">Low Stock Alert</p>
                            <p class="text-sm text-red-700">This item is running low on stock.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stock Movements -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Stock Movements</h2>
            <?php if (!empty($stock_movements)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach (array_slice($stock_movements, 0, 10) as $movement): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M j, Y g:i A', strtotime($movement['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $movement['movement_type'] == 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($movement['movement_type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $movement['quantity']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $movement['previous_stock']; ?> → <?php echo $movement['new_stock']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($movement['notes']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($stock_movements) > 10): ?>
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-500">Showing 10 most recent movements out of <?php echo count($stock_movements); ?> total</p>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <p class="text-gray-500 text-center py-8">No stock movements recorded.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stock Update Modal -->
<?php if (hasPermission([ROLE_ADMIN, ROLE_HR, ROLE_SALES])): ?>
<div id="stock-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Update Stock</h3>
            <form method="POST" action="?action=update_stock&id=<?php echo $item['id']; ?>">
                <input type="hidden" name="current_quantity" value="<?php echo $item['stock_quantity']; ?>">
                <div class="mb-4">
                    <label for="new_quantity" class="block text-sm font-medium text-gray-700 mb-2">New Quantity</label>
                    <input type="number" min="0" id="new_quantity" name="new_quantity" 
                           value="<?php echo $item['stock_quantity']; ?>" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                              placeholder="Reason for stock adjustment..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('stock-modal').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                        Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Image Modal -->
<div id="image-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 overflow-y-auto h-full w-full z-50" onclick="closeImageModal()">
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative max-w-4xl max-h-full" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white text-2xl hover:text-gray-300 transition">
                <i class="fas fa-times"></i>
            </button>
            <img id="modal-image" src="" alt="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl">
        </div>
    </div>
</div>

<script>
function updateFilters(filterType, filterValue) {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (filterValue) {
        urlParams.set(filterType, filterValue);
    } else {
        urlParams.delete(filterType);
    }
    
    // Remove low_stock filter when changing other filters
    if (filterType !== 'filter') {
        urlParams.delete('filter');
    }
    
    // Construct new URL
    const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
    window.location.href = newUrl;
}

function openImageModal(imageSrc) {
    document.getElementById('modal-image').src = imageSrc;
    document.getElementById('image-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeImageModal() {
    document.getElementById('image-modal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

// Toggle export menu
function toggleExportMenu() {
    const menu = document.getElementById('export-menu');
    menu.classList.toggle('hidden');
}

// Enhanced CSV export with current search results
function exportFilteredToCSV() {
    const visibleRows = document.querySelectorAll('.inventory-item-row:not([style*="display: none"])');
    const headers = ['Brand', 'Model', 'Category', 'Size/Specification', 'Base Price', 'Selling Price', 'Stock Quantity', 'Minimum Stock', 'Supplier'];
    
    let csvContent = '\uFEFF'; // BOM for UTF-8
    csvContent += headers.join(',') + '\n';
    
    visibleRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const rowData = [
                cells[0].querySelector('.text-sm.font-medium a') ? cells[0].querySelector('.text-sm.font-medium a').textContent.trim() : '',
                cells[0].querySelector('.text-sm.text-gray-500') ? cells[0].querySelector('.text-sm.text-gray-500').textContent.trim() : '',
                cells[1].textContent.trim(),
                cells[2].textContent.trim(),
                cells[3].querySelector('div:first-child') ? cells[3].querySelector('div:first-child').textContent.replace('Base: ', '').trim() : '',
                cells[3].querySelector('div:nth-child(2)') ? cells[3].querySelector('div:nth-child(2)').textContent.replace('Sell: ', '').trim() : '',
                cells[4].querySelector('.text-sm.text-gray-900') ? cells[4].querySelector('.text-sm.text-gray-900').textContent.trim() : '',
                '', // Min stock not easily accessible in this view
                cells[5].textContent.trim()
            ];
            csvContent += rowData.map(field => '"' + field.replace(/"/g, '""') + '"').join(',') + '\n';
        }
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'filtered_inventory_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
    URL.revokeObjectURL(link.href);
}

// Close export menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('export-menu');
    const button = event.target.closest('button');
    
    if (!button || !button.onclick || button.onclick.toString().indexOf('toggleExportMenu') === -1) {
        menu.classList.add('hidden');
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
        document.getElementById('export-menu').classList.add('hidden');
    }
});

// Enhanced inventory table scrolling
document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.querySelector('.inventory-scroll-container');
    
    if (scrollContainer) {
        // Check if content is scrollable and add appropriate classes
        function checkScrollability() {
            if (scrollContainer.scrollHeight > scrollContainer.clientHeight) {
                scrollContainer.classList.add('has-scroll');
            } else {
                scrollContainer.classList.remove('has-scroll');
            }
        }
        
        // Add scroll position indicator
        function updateScrollIndicator() {
            const scrollTop = scrollContainer.scrollTop;
            const scrollHeight = scrollContainer.scrollHeight;
            const clientHeight = scrollContainer.clientHeight;
            const scrollPercent = (scrollTop / (scrollHeight - clientHeight)) * 100;
            
            // Update visual indicators
            if (scrollPercent > 0 && scrollPercent < 100) {
                scrollContainer.style.borderBottom = '3px solid #3b82f6';
            } else {
                scrollContainer.style.borderBottom = 'none';
            }
        }
        
        // Smooth scrolling behavior
        scrollContainer.addEventListener('scroll', function() {
            updateScrollIndicator();
            
            // Show/hide scroll to top button
            const scrollToTopButton = document.getElementById('scroll-to-top-inventory');
            if (scrollToTopButton) {
                if (scrollContainer.scrollTop > 200) {
                    scrollToTopButton.classList.remove('hidden');
                } else {
                    scrollToTopButton.classList.add('hidden');
                }
            }
        });
        
        // Keyboard navigation for accessibility
        scrollContainer.addEventListener('keydown', function(event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                scrollContainer.scrollTop += 100;
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                scrollContainer.scrollTop -= 100;
            } else if (event.key === 'PageDown') {
                event.preventDefault();
                scrollContainer.scrollTop += scrollContainer.clientHeight;
            } else if (event.key === 'PageUp') {
                event.preventDefault();
                scrollContainer.scrollTop -= scrollContainer.clientHeight;
            } else if (event.key === 'Home') {
                event.preventDefault();
                scrollContainer.scrollTop = 0;
            } else if (event.key === 'End') {
                event.preventDefault();
                scrollContainer.scrollTop = scrollContainer.scrollHeight;
            }
        });
        
        // Make container focusable for keyboard navigation
        scrollContainer.setAttribute('tabindex', '0');
        scrollContainer.setAttribute('aria-label', 'Inventory items table - use arrow keys to scroll');
        
        // Initial setup
        checkScrollability();
        updateScrollIndicator();
        
        // Update on window resize
        window.addEventListener('resize', checkScrollability);
        
        // Initialize search functionality
        initializeInventorySearch();
        
        // Initialize enhanced select dropdowns
        initializeEnhancedSelects();
    }
});

// Initialize enhanced select dropdowns
function initializeEnhancedSelects() {
    const categorySelect = document.querySelector('.category-select');
    const brandSelect = document.querySelector('.brand-select');
    
    // Add keyboard navigation for category select
    if (categorySelect) {
        categorySelect.addEventListener('keydown', function(event) {
            // Allow typing to search within select options
            if (event.key.length === 1) {
                const options = this.querySelectorAll('option');
                const currentIndex = this.selectedIndex;
                const searchLetter = event.key.toLowerCase();
                
                // Find next option starting with the typed letter
                for (let i = currentIndex + 1; i < options.length; i++) {
                    if (options[i].textContent.toLowerCase().startsWith(searchLetter)) {
                        this.selectedIndex = i;
                        event.preventDefault();
                        break;
                    }
                }
                
                // If not found after current, search from beginning
                if (this.selectedIndex === currentIndex) {
                    for (let i = 0; i < currentIndex; i++) {
                        if (options[i].textContent.toLowerCase().startsWith(searchLetter)) {
                            this.selectedIndex = i;
                            event.preventDefault();
                            break;
                        }
                    }
                }
            }
        });
        
        // Add visual feedback when scrolling in select
        categorySelect.addEventListener('mouseenter', function() {
            this.style.borderColor = '#3b82f6';
        });
        
        categorySelect.addEventListener('mouseleave', function() {
            if (!this.matches(':focus')) {
                this.style.borderColor = '#d1d5db';
            }
        });
    }
    
    // Same enhancements for brand select
    if (brandSelect) {
        brandSelect.addEventListener('keydown', function(event) {
            if (event.key.length === 1) {
                const options = this.querySelectorAll('option');
                const currentIndex = this.selectedIndex;
                const searchLetter = event.key.toLowerCase();
                
                for (let i = currentIndex + 1; i < options.length; i++) {
                    if (options[i].textContent.toLowerCase().startsWith(searchLetter)) {
                        this.selectedIndex = i;
                        event.preventDefault();
                        break;
                    }
                }
                
                if (this.selectedIndex === currentIndex) {
                    for (let i = 0; i < currentIndex; i++) {
                        if (options[i].textContent.toLowerCase().startsWith(searchLetter)) {
                            this.selectedIndex = i;
                            event.preventDefault();
                            break;
                        }
                    }
                }
            }
        });
        
        brandSelect.addEventListener('mouseenter', function() {
            this.style.borderColor = '#3b82f6';
        });
        
        brandSelect.addEventListener('mouseleave', function() {
            if (!this.matches(':focus')) {
                this.style.borderColor = '#d1d5db';
            }
        });
    }
}

// Initialize inventory search functionality
function initializeInventorySearch() {
    const searchInput = document.getElementById('inventory-search');
    
    if (searchInput) {
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // Ctrl+F or Cmd+F to focus search
            if ((event.ctrlKey || event.metaKey) && event.key === 'f' && !event.shiftKey) {
                event.preventDefault();
                focusInventorySearch();
            }
            
            // Escape to clear search when focused on search input
            if (event.key === 'Escape' && document.activeElement === searchInput) {
                clearInventorySearch();
            }
        });
        
        // Real-time search with debouncing
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterInventoryItems, 300);
        });
        
        // Search on Enter key
        searchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                filterInventoryItems();
            }
        });
        
        // Add search icon animation
        searchInput.addEventListener('focus', function() {
            this.parentElement.classList.add('ring-2', 'ring-solar-blue');
        });
        
        searchInput.addEventListener('blur', function() {
            this.parentElement.classList.remove('ring-2', 'ring-solar-blue');
        });
    }
}

// Scroll to top function for inventory table
function scrollInventoryToTop() {
    const scrollContainer = document.querySelector('.inventory-scroll-container');
    if (scrollContainer) {
        scrollContainer.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}

// Inventory search functionality
function filterInventoryItems() {
    const searchTerm = document.getElementById('inventory-search').value.toLowerCase().trim();
    const itemRows = document.querySelectorAll('.inventory-item-row');
    const noItemsRow = document.getElementById('no-items-row');
    const noSearchResults = document.getElementById('no-search-results');
    const searchResultsInfo = document.getElementById('search-results-info');
    const searchResultsText = document.getElementById('search-results-text');
    const visibleItemsCount = document.getElementById('visible-items-count');
    
    let visibleCount = 0;
    let totalItems = itemRows.length;
    
    if (searchTerm === '') {
        // Show all items
        itemRows.forEach(row => {
            row.style.display = 'table-row';
            visibleCount++;
        });
        
        // Hide search-specific elements
        if (noSearchResults) noSearchResults.style.display = 'none';
        if (searchResultsInfo) searchResultsInfo.classList.add('hidden');
        
        // Show original no items message if no items exist
        if (noItemsRow && totalItems === 0) {
            noItemsRow.style.display = 'table-row';
        }
    } else {
        // Filter items based on search term
        itemRows.forEach(row => {
            const fullText = row.getAttribute('data-full-text') || '';
            const brand = row.getAttribute('data-brand') || '';
            const model = row.getAttribute('data-model') || '';
            const category = row.getAttribute('data-category') || '';
            const size = row.getAttribute('data-size') || '';
            const supplier = row.getAttribute('data-supplier') || '';
            
            const matchesSearch = fullText.includes(searchTerm) ||
                                brand.includes(searchTerm) ||
                                model.includes(searchTerm) ||
                                category.includes(searchTerm) ||
                                size.includes(searchTerm) ||
                                supplier.includes(searchTerm);
            
            if (matchesSearch) {
                row.style.display = 'table-row';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Hide original no items message
        if (noItemsRow) noItemsRow.style.display = 'none';
        
        // Show/hide search results info
        if (searchResultsInfo && searchResultsText) {
            if (visibleCount === 0) {
                searchResultsInfo.classList.add('hidden');
                if (noSearchResults) noSearchResults.style.display = 'table-row';
            } else {
                searchResultsInfo.classList.remove('hidden');
                searchResultsText.textContent = `Found ${visibleCount} item${visibleCount !== 1 ? 's' : ''} matching "${searchTerm}"`;
                if (noSearchResults) noSearchResults.style.display = 'none';
            }
        }
    }
    
    // Update visible items count
    if (visibleItemsCount) {
        visibleItemsCount.textContent = visibleCount;
    }
    
    // Scroll to top of results
    scrollInventoryToTop();
}

function clearInventorySearch() {
    const searchInput = document.getElementById('inventory-search');
    if (searchInput) {
        searchInput.value = '';
        filterInventoryItems();
        searchInput.focus();
    }
}

function focusInventorySearch() {
    const searchInput = document.getElementById('inventory-search');
    if (searchInput) {
        searchInput.focus();
        searchInput.select();
    }
}

// Print inventory report functionality
function printInventoryReport(type, categoryId = null, categoryName = null) {
    let url = 'print_inventory_report.php?';
    
    switch (type) {
        case 'all':
            url += 'type=all';
            break;
        case 'current':
            // Get current filters
            const urlParams = new URLSearchParams(window.location.search);
            url += 'type=all';
            if (urlParams.get('category')) {
                url += '&category=' + urlParams.get('category');
            }
            if (urlParams.get('brand')) {
                url += '&brand=' + encodeURIComponent(urlParams.get('brand'));
            }
            if (urlParams.get('filter')) {
                url += '&type=' + urlParams.get('filter');
            }
            break;
        case 'filtered':
            // Create a temporary form with filtered data
            const searchTerm = document.getElementById('inventory-search').value;
            if (searchTerm) {
                url += 'type=search&search=' + encodeURIComponent(searchTerm);
                // Also include current page filters
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('category')) {
                    url += '&category=' + urlParams.get('category');
                }
                if (urlParams.get('brand')) {
                    url += '&brand=' + encodeURIComponent(urlParams.get('brand'));
                }
            } else {
                // No search term, fall back to current view
                return printInventoryReport('current');
            }
            break;
        case 'category':
            url += 'type=category&category=' + categoryId;
            break;
        case 'low_stock':
            url += 'type=low_stock';
            break;
        case 'brand':
            url += 'type=brand&brand=' + encodeURIComponent(categoryName);
            break;
    }
    
    // Open print window
    const printWindow = window.open(url, '_blank', 'width=1024,height=768,scrollbars=yes,resizable=yes');
    
    if (printWindow) {
        printWindow.focus();
    } else {
        alert('Please allow popups to print reports.');
    }
}
</script>

<style>
/* Custom scrollbar styling for inventory table */
.inventory-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
    transition: border-bottom 0.3s ease;
}

.inventory-scroll-container::-webkit-scrollbar {
    width: 8px;
}

.inventory-scroll-container::-webkit-scrollbar-track {
    background: #f7fafc;
    border-radius: 4px;
}

.inventory-scroll-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.inventory-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

.inventory-scroll-container::-webkit-scrollbar-thumb:active {
    background: #718096;
}

/* Enhanced select dropdown styling */
.category-select, .brand-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.category-select:focus, .brand-select:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Export menu scrollbar styling */
#export-menu {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

#export-menu::-webkit-scrollbar {
    width: 8px;
}

#export-menu::-webkit-scrollbar-track {
    background: #f7fafc;
    border-radius: 4px;
}

#export-menu::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
    transition: background 0.3s ease;
}

#export-menu::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* Sticky headers in export menu */
#export-menu .sticky {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(8px);
}

/* Smooth scrolling for export menu */
#export-menu {
    scroll-behavior: smooth;
}

/* Search input enhancements */
#inventory-search {
    transition: all 0.3s ease;
    position: relative;
}

#inventory-search:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
}

#inventory-search::placeholder {
    transition: opacity 0.3s ease;
}

#inventory-search:focus::placeholder {
    opacity: 0.7;
}

/* Search results highlighting */
.inventory-item-row {
    transition: all 0.3s ease;
}

.inventory-item-row.search-match {
    background-color: #eff6ff;
    border-left: 3px solid #3b82f6;
}

/* Search animation */
@keyframes searchPulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.searching {
    animation: searchPulse 1.5s infinite;
}

/* Quick action buttons */
.search-actions button {
    transition: all 0.2s ease;
    transform: translateY(0);
}

.search-actions button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.search-actions button:active {
    transform: translateY(0);
}

/* Ensure table maintains proper spacing */
.inventory-table-container table {
    table-layout: fixed;
    width: 100%;
}

.inventory-table-container th,
.inventory-table-container td {
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Focus styles for accessibility */
.inventory-scroll-container:focus {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
}

/* Responsive scroll height */
@media (max-width: 768px) {
    .inventory-scroll-container {
        max-height: 60vh;
    }
    
    #export-menu {
        max-height: 60vh;
        max-width: 90vw;
        left: auto;
        right: 0;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .inventory-scroll-container {
        max-height: 70vh;
    }
    
    #export-menu {
        max-height: 70vh;
    }
}

@media (min-width: 1025px) {
    .inventory-scroll-container {
        max-height: 80vh;
    }
    
    #export-menu {
        max-height: 75vh;
    }
}

/* Shadow effects to indicate scrollable content */
.inventory-scroll-container {
    position: relative;
}

.inventory-scroll-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(to bottom, rgba(0,0,0,0.1), transparent);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 1;
}

.inventory-scroll-container.has-scroll::before {
    opacity: 1;
}

.inventory-scroll-container::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(to top, rgba(0,0,0,0.1), transparent);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 1;
}

.inventory-scroll-container.has-scroll::after {
    opacity: 1;
}
</style>

<?php include 'includes/footer.php'; ?>
