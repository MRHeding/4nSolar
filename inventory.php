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

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'add':
            if (hasPermission([ROLE_ADMIN, ROLE_HR])) {
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
                    $message = 'Item added successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to add item. Please check the image format and size.';
                }
            } else {
                $error = 'You do not have permission to add items.';
            }
            break;
            
        case 'edit':
            if (hasPermission([ROLE_ADMIN, ROLE_HR]) && $item_id) {
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
                    $message = 'Item updated successfully!';
                    $action = 'list';
                } else {
                    $error = 'Failed to update item. Please check the image format and size.';
                }
            }
            break;
            
        case 'update_stock':
            if (hasPermission([ROLE_ADMIN, ROLE_HR]) && $item_id) {
                $new_quantity = $_POST['new_quantity'];
                $movement_type = $new_quantity > $_POST['current_quantity'] ? 'in' : 'out';
                $notes = $_POST['notes'] ?? '';
                
                if (updateStock($item_id, $new_quantity, $movement_type, 'adjustment', null, $notes)) {
                    $message = 'Stock updated successfully!';
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
        $message = 'Item deleted successfully!';
    } else {
        $error = 'Failed to delete item.';
    }
    $action = 'list';
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
        <?php if (hasPermission([ROLE_ADMIN, ROLE_HR])): ?>
        <a href="?action=add" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            <i class="fas fa-plus mr-2"></i>Add Item
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <!-- Category Filter Section -->
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Category Filter</label>
            <select onchange="updateFilters('category', this.value)" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
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
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
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
            <label class="block text-sm font-medium text-gray-700">Export Data</label>
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
                    <div id="export-menu" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10 min-w-40">
                        <a href="export_inventory.php?format=csv<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $brand_filter ? '&brand=' . urlencode($brand_filter) : ''; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-file-csv mr-2"></i>Full CSV Export
                        </a>
                        <a href="export_inventory.php?format=json<?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $brand_filter ? '&brand=' . urlencode($brand_filter) : ''; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?>" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-file-code mr-2"></i>JSON Export
                        </a>
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

<!-- Inventory Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table id="inventory-table" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Details</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pricing</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50">
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
                        <?php if (hasPermission([ROLE_ADMIN, ROLE_HR])): ?>
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
                <tr>
                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No items found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
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
            <?php if (hasPermission([ROLE_ADMIN, ROLE_HR])): ?>
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
<?php if (hasPermission([ROLE_ADMIN, ROLE_HR])): ?>
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
</script>

<?php include 'includes/footer.php'; ?>
