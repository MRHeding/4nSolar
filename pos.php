<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/pos.php';
require_once 'includes/inventory.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$action = $_GET['action'] ?? 'new';
$sale_id = $_GET['id'] ?? null;

// Check for success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'item_added':
            $message = 'Item added to sale successfully!';
            break;
        case 'quantity_updated':
            $message = 'Quantity updated successfully!';
            break;
        case 'item_removed':
            $message = 'Item removed from sale successfully!';
            break;
    }
}

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'create':
            $sale_id = createPOSSale($_POST['customer_name'] ?? null, $_POST['customer_phone'] ?? null);
            if ($sale_id) {
                $message = 'New sale created successfully!';
                $action = 'sale';
            } else {
                $error = 'Failed to create sale.';
            }
            break;
            
        case 'add_item':
            if ($sale_id && isset($_POST['inventory_item_id']) && isset($_POST['quantity'])) {
                $result = addPOSSaleItem($sale_id, $_POST['inventory_item_id'], $_POST['quantity'], $_POST['discount_percentage'] ?? 0);
                if ($result['success']) {
                    header("Location: ?action=sale&id=" . $sale_id . "&success=item_added");
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_quantity':
            if (isset($_POST['sale_item_id']) && isset($_POST['new_quantity'])) {
                $result = updatePOSSaleItemQuantity($_POST['sale_item_id'], $_POST['new_quantity']);
                if ($result['success']) {
                    header("Location: ?action=sale&id=" . $sale_id . "&success=quantity_updated");
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'complete_sale':
            if ($sale_id && isset($_POST['payment_method']) && isset($_POST['amount_paid'])) {
                $result = completePOSSale(
                    $sale_id, 
                    $_POST['payment_method'], 
                    $_POST['amount_paid'],
                    $_POST['customer_name'] ?? null,
                    $_POST['customer_phone'] ?? null
                );
                
                if ($result['success']) {
                    $message = $result['message'] . " Change: " . formatCurrency($result['change_amount']);
                    header("Location: ?action=receipt&id=" . $sale_id);
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
    }
}

// Handle delete actions
if ($action == 'remove_item' && isset($_GET['item_id'])) {
    if (removePOSSaleItem($_GET['item_id'])) {
        header("Location: ?action=sale&id=" . $sale_id . "&success=item_removed");
        exit();
    } else {
        $error = 'Failed to remove item.';
    }
}

if ($action == 'cancel_sale' && $sale_id) {
    if (cancelPOSSale($sale_id)) {
        $message = 'Sale cancelled successfully!';
        $action = 'new';
    } else {
        $error = 'Failed to cancel sale.';
    }
}

// Get data based on action
switch ($action) {
    case 'sale':
    case 'receipt':
        if ($sale_id) {
            $sale = getPOSSale($sale_id);
            if (!$sale) {
                $error = 'Sale not found.';
                $action = 'new';
            } else {
                $inventory_items = getPOSInventoryItems();
            }
        }
        break;
        
    case 'history':
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        $status = $_GET['status'] ?? null;
        $sales = getPOSSales($status, $date_from, $date_to);
        $stats = getPOSStats($date_from, $date_to);
        break;
        
    default:
        $inventory_items = getPOSInventoryItems();
        $stats = getPOSStats(date('Y-m-d'), date('Y-m-d')); // Today's stats
        break;
}

$page_title = 'Point of Sale (POS)';
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

<?php if ($action == 'new'): ?>
<!-- New Sale Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Point of Sale</h1>
            <p class="text-gray-600">Start a new sale for walk-in customers</p>
        </div>
        <div class="space-x-2">
            <a href="?action=history" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-history mr-2"></i>Sales History
            </a>
        </div>
    </div>
</div>

<!-- Today's Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-shopping-cart text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Today's Sales</h2>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['today_sales']; ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Today's Revenue</h2>
                <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['today_revenue']); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-boxes text-xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-sm font-medium text-gray-500">Available Items</h2>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($inventory_items); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Start New Sale -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Start New Sale</h2>
    <form method="POST" action="?action=create" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Customer Name (Optional)</label>
                <input type="text" id="customer_name" name="customer_name"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       placeholder="Enter customer name">
            </div>
            <div>
                <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">Customer Phone (Optional)</label>
                <input type="tel" id="customer_phone" name="customer_phone"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       placeholder="Enter phone number">
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-solar-blue text-white px-6 py-3 rounded-lg hover:bg-blue-800 transition text-lg">
                <i class="fas fa-plus mr-2"></i>Start Sale
            </button>
        </div>
    </form>
</div>

<?php elseif (($action == 'sale') && isset($sale)): ?>
<!-- Sale Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Sale: <?php echo htmlspecialchars($sale['receipt_number']); ?></h1>
            <p class="text-gray-600">
                <?php if ($sale['customer_name']): ?>
                Customer: <?php echo htmlspecialchars($sale['customer_name']); ?>
                <?php if ($sale['customer_phone']): ?>
                - <?php echo htmlspecialchars($sale['customer_phone']); ?>
                <?php endif; ?>
                <?php else: ?>
                Walk-in Customer
                <?php endif; ?>
            </p>
        </div>
        <div class="space-x-2">
            <a href="?action=cancel_sale&id=<?php echo $sale['id']; ?>" 
               class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition"
               onclick="return confirmDelete('Cancel this sale? All items will be removed.')">
                <i class="fas fa-times mr-2"></i>Cancel Sale
            </a>
            <a href="?" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>New Sale
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Sale Items -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Sale Items</h2>
                <button onclick="document.getElementById('add-item-modal').classList.remove('hidden')" 
                        class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition text-sm">
                    <i class="fas fa-plus mr-2"></i>Add Item
                </button>
            </div>
            
            <?php if (!empty($sale['items'])): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Qty</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Unit Price</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">Disc %</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Total</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($sale['items'] as $item): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                                <div class="text-xs text-blue-600">Stock: <?php echo $item['stock_quantity']; ?></div>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <form method="POST" action="?action=update_quantity&id=<?php echo $sale['id']; ?>" class="inline">
                                    <input type="hidden" name="sale_item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="new_quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock_quantity']; ?>"
                                           class="w-16 px-2 py-1 border rounded text-center text-sm"
                                           onchange="this.form.submit()">
                                </form>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <?php echo formatCurrency($item['unit_price']); ?>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900">
                                <?php echo $item['discount_percentage']; ?>%
                            </td>
                            <td class="px-3 py-4 text-sm font-medium text-gray-900">
                                <?php echo formatCurrency($item['total_amount']); ?>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <a href="?action=remove_item&id=<?php echo $sale['id']; ?>&item_id=<?php echo $item['id']; ?>" 
                                   class="text-red-600 hover:text-red-900 p-2"
                                   onclick="return confirmDelete('Remove this item?')"
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
            <p class="text-gray-500 text-center py-8">No items added to this sale yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sale Summary & Payment -->
    <div>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Sale Summary</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium"><?php echo formatCurrency($sale['subtotal']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Discount:</span>
                    <span class="font-medium text-green-600">-<?php echo formatCurrency($sale['total_discount']); ?></span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t pt-3">
                    <span>Total:</span>
                    <span class="text-solar-blue"><?php echo formatCurrency($sale['total_amount']); ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($sale['status'] === 'pending' && !empty($sale['items'])): ?>
        <!-- Payment Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Process Payment</h2>
            <form method="POST" action="?action=complete_sale&id=<?php echo $sale['id']; ?>" class="space-y-4">
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select id="payment_method" name="payment_method" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                        <option value="cash">Cash</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                    </select>
                </div>
                <div>
                    <label for="amount_paid" class="block text-sm font-medium text-gray-700 mb-2">Amount Paid</label>
                    <input type="number" step="0.01" id="amount_paid" name="amount_paid" 
                           value="<?php echo $sale['total_amount']; ?>" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" 
                           value="<?php echo htmlspecialchars($sale['customer_name'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                <div>
                    <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">Customer Phone</label>
                    <input type="tel" id="customer_phone" name="customer_phone" 
                           value="<?php echo htmlspecialchars($sale['customer_phone'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                <button type="submit" class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition font-semibold">
                    <i class="fas fa-credit-card mr-2"></i>Complete Sale
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Item Modal -->
<div id="add-item-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Item to Sale</h3>
            
            <!-- Search Bar -->
            <div class="mb-4">
                <label for="item-search" class="block text-sm font-medium text-gray-700 mb-2">Search Items</label>
                <input type="text" id="item-search" placeholder="Search by brand, model, or category..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       oninput="filterItems()">
            </div>
            
            <!-- Category Filter -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Filter by Category</label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="filterByPOSCategory('')" 
                            class="pos-category-filter-btn px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition active">
                        All Items
                    </button>
                    <?php 
                    $pos_categories = [];
                    foreach ($inventory_items as $item) {
                        if (!empty($item['category_name']) && !in_array($item['category_name'], $pos_categories)) {
                            $pos_categories[] = $item['category_name'];
                        }
                    }
                    foreach ($pos_categories as $category): ?>
                    <button type="button" onclick="filterByPOSCategory('<?php echo strtolower($category); ?>')" 
                            class="pos-category-filter-btn px-3 py-1 text-sm bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Items Grid -->
            <div class="mb-4 max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                <div id="items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                    <?php foreach ($inventory_items as $inv_item): ?>
                    <div class="item-card border border-gray-200 rounded-lg p-3 hover:bg-blue-50 cursor-pointer transition" 
                         data-item-id="<?php echo $inv_item['id']; ?>"
                         data-brand="<?php echo strtolower($inv_item['brand']); ?>"
                         data-model="<?php echo strtolower($inv_item['model']); ?>"
                         data-category="<?php echo strtolower($inv_item['category_name'] ?? ''); ?>"
                         data-price="<?php echo $inv_item['selling_price']; ?>"
                         data-stock="<?php echo $inv_item['stock_quantity']; ?>"
                         onclick="selectItem(this)">
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <img class="h-12 w-12 rounded-lg object-cover border" 
                                     src="<?php echo htmlspecialchars(getProductImageUrl($inv_item['image_path'])); ?>" 
                                     alt="<?php echo htmlspecialchars($inv_item['brand'] . ' ' . $inv_item['model']); ?>">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($inv_item['brand']); ?>
                                </div>
                                <div class="text-sm text-gray-500 truncate">
                                    <?php echo htmlspecialchars($inv_item['model']); ?>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <?php echo htmlspecialchars($inv_item['category_name'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo formatCurrency($inv_item['selling_price']); ?>
                                </div>
                                <div class="text-xs <?php echo $inv_item['stock_quantity'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
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
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="no-items-message" class="hidden text-center py-8 text-gray-500">
                    <i class="fas fa-search text-2xl mb-2"></i>
                    <p>No items found matching your search.</p>
                </div>
            </div>
            
            <!-- Selected Item Form -->
            <form id="add-item-form" method="POST" action="?action=add_item&id=<?php echo $sale['id']; ?>" class="hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h4 class="font-medium text-gray-900 mb-2">Selected Item:</h4>
                    <div id="selected-item-display" class="text-sm text-gray-700"></div>
                </div>
                
                <input type="hidden" id="selected_inventory_item_id" name="inventory_item_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" min="1" id="quantity" name="quantity" required value="1"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                               oninput="updateTotalPreview()">
                    </div>
                    <div>
                        <label for="discount_percentage" class="block text-sm font-medium text-gray-700 mb-2">Discount %</label>
                        <input type="number" min="0" max="100" step="0.01" id="discount_percentage" name="discount_percentage" value="0"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                               oninput="updateTotalPreview()">
                    </div>
                </div>
                
                <div id="total-preview" class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4 hidden">
                    <div class="flex justify-between text-sm">
                        <span>Subtotal:</span>
                        <span id="subtotal-amount">₱0.00</span>
                    </div>
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount:</span>
                        <span id="discount-amount">₱0.00</span>
                    </div>
                    <hr class="my-2">
                    <div class="flex justify-between font-medium">
                        <span>Total:</span>
                        <span id="total-amount">₱0.00</span>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="clearSelection()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Clear Selection
                    </button>
                    <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                        Add to Sale
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
let selectedItemData = null;
let currentPOSCategoryFilter = '';

function filterItems() {
    const searchTerm = document.getElementById('item-search').value.toLowerCase();
    const itemCards = document.querySelectorAll('.item-card');
    let visibleCount = 0;
    
    itemCards.forEach(card => {
        const brand = card.getAttribute('data-brand');
        const model = card.getAttribute('data-model');
        const category = card.getAttribute('data-category');
        
        const matchesSearch = brand.includes(searchTerm) || 
                             model.includes(searchTerm) || 
                             category.includes(searchTerm);
        
        const matchesCategory = currentPOSCategoryFilter === '' || category === currentPOSCategoryFilter;
        
        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    const noItemsMessage = document.getElementById('no-items-message');
    if (visibleCount === 0) {
        noItemsMessage.classList.remove('hidden');
    } else {
        noItemsMessage.classList.add('hidden');
    }
}

function filterByPOSCategory(category) {
    currentPOSCategoryFilter = category;
    
    // Update button states
    document.querySelectorAll('.pos-category-filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-green-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700');
        
        // Restore original colors for category buttons
        if (btn.textContent.trim() !== 'All Items') {
            btn.classList.remove('bg-gray-100', 'text-gray-700');
            btn.classList.add('bg-green-100', 'text-green-700');
        }
    });
    
    // Highlight active button
    if (category === '') {
        // All Items button
        event.target.classList.remove('bg-gray-100', 'text-gray-700');
        event.target.classList.add('active', 'bg-green-600', 'text-white');
    } else {
        // Category button
        event.target.classList.remove('bg-green-100', 'text-green-700');
        event.target.classList.add('active', 'bg-green-600', 'text-white');
    }
    
    filterItems();
}

function selectItem(cardElement) {
    // Remove previous selection
    document.querySelectorAll('.item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Mark current selection
    cardElement.classList.add('bg-blue-100', 'border-blue-500');
    
    // Store selected item data
    selectedItemData = {
        id: cardElement.getAttribute('data-item-id'),
        brand: cardElement.querySelector('.text-sm.font-medium').textContent,
        model: cardElement.querySelector('.text-sm.text-gray-500').textContent,
        price: parseFloat(cardElement.getAttribute('data-price')),
        stock: parseInt(cardElement.getAttribute('data-stock'))
    };
    
    // Update form
    document.getElementById('selected_inventory_item_id').value = selectedItemData.id;
    document.getElementById('selected-item-display').innerHTML = 
        `<strong>${selectedItemData.brand}</strong> - ${selectedItemData.model}<br>
         Price: ${formatCurrency(selectedItemData.price)} | Available: ${selectedItemData.stock}`;
    
    // Show form and update preview
    document.getElementById('add-item-form').classList.remove('hidden');
    updateTotalPreview();
    
    // Update quantity max
    document.getElementById('quantity').max = selectedItemData.stock;
}

function clearSelection() {
    // Clear visual selection
    document.querySelectorAll('.item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Hide form
    document.getElementById('add-item-form').classList.add('hidden');
    selectedItemData = null;
}

function updateTotalPreview() {
    if (!selectedItemData) return;
    
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const discountPercent = parseFloat(document.getElementById('discount_percentage').value) || 0;
    
    const subtotal = selectedItemData.price * quantity;
    const discountAmount = subtotal * (discountPercent / 100);
    const total = subtotal - discountAmount;
    
    document.getElementById('subtotal-amount').textContent = formatCurrency(subtotal);
    document.getElementById('discount-amount').textContent = formatCurrency(discountAmount);
    document.getElementById('total-amount').textContent = formatCurrency(total);
    
    document.getElementById('total-preview').classList.remove('hidden');
}

function formatCurrency(amount) {
    return '₱' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
</script>

<?php elseif (($action == 'receipt') && isset($sale)): ?>
<!-- Receipt Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Sale Receipt</h1>
            <p class="text-gray-600">Receipt #<?php echo htmlspecialchars($sale['receipt_number']); ?></p>
        </div>
        <div class="space-x-2">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-print mr-2"></i>Print Receipt
            </button>
            <a href="?" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
                <i class="fas fa-plus mr-2"></i>New Sale
            </a>
        </div>
    </div>
</div>

<!-- Receipt Content -->
<div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto print:shadow-none print:max-w-none">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">4nSolar</h2>
        <p class="text-gray-600">Solar Equipment & Services</p>
        <p class="text-sm text-gray-500">Receipt #<?php echo htmlspecialchars($sale['receipt_number']); ?></p>
    </div>
    
    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
        <div>
            <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($sale['completed_at'])); ?></p>
            <p><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></p>
        </div>
        <div>
            <?php if ($sale['customer_name']): ?>
            <p><strong>Customer:</strong> <?php echo htmlspecialchars($sale['customer_name']); ?></p>
            <?php if ($sale['customer_phone']): ?>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?></p>
            <?php endif; ?>
            <?php else: ?>
            <p><strong>Customer:</strong> Walk-in</p>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="w-full mb-6">
        <thead>
            <tr class="border-b">
                <th class="text-left py-2">Item</th>
                <th class="text-center py-2 w-16">Qty</th>
                <th class="text-right py-2 w-24">Price</th>
                <th class="text-right py-2 w-24">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale['items'] as $item): ?>
            <tr class="border-b">
                <td class="py-2">
                    <div class="font-medium"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                </td>
                <td class="text-center py-2"><?php echo $item['quantity']; ?></td>
                <td class="text-right py-2"><?php echo formatCurrency($item['unit_price']); ?></td>
                <td class="text-right py-2"><?php echo formatCurrency($item['total_amount']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="text-right space-y-1 mb-6">
        <div class="flex justify-between">
            <span>Subtotal:</span>
            <span><?php echo formatCurrency($sale['subtotal']); ?></span>
        </div>
        <?php if ($sale['total_discount'] > 0): ?>
        <div class="flex justify-between text-green-600">
            <span>Discount:</span>
            <span>-<?php echo formatCurrency($sale['total_discount']); ?></span>
        </div>
        <?php endif; ?>
        <div class="flex justify-between font-bold text-lg border-t pt-2">
            <span>Total:</span>
            <span><?php echo formatCurrency($sale['total_amount']); ?></span>
        </div>
        <div class="flex justify-between">
            <span>Paid (<?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?>):</span>
            <span><?php echo formatCurrency($sale['amount_paid']); ?></span>
        </div>
        <?php if ($sale['change_amount'] > 0): ?>
        <div class="flex justify-between font-medium">
            <span>Change:</span>
            <span><?php echo formatCurrency($sale['change_amount']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="text-center text-sm text-gray-500">
        <p>Thank you for your business!</p>
        <p>For warranty and support, please keep this receipt.</p>
    </div>
</div>

<?php elseif ($action == 'history'): ?>
<!-- Sales History -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Sales History</h1>
            <p class="text-gray-600">View all POS sales transactions</p>
        </div>
        <a href="?" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            <i class="fas fa-plus mr-2"></i>New Sale
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <input type="hidden" name="action" value="history">
        <div>
            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>"
                   class="border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
        </div>
        <div>
            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>"
                   class="border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="status" name="status"
                    class="border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                <option value="">All Status</option>
                <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
            <i class="fas fa-search mr-2"></i>Filter
        </button>
        <a href="?action=history" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
            <i class="fas fa-refresh mr-2"></i>Reset
        </a>
    </form>
</div>

<!-- Stats Summary -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">Total Sales</h3>
        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_sales']; ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
        <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['total_revenue']); ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">Today's Sales</h3>
        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['today_sales']; ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">Today's Revenue</h3>
        <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['today_revenue']); ?></p>
    </div>
</div>

<!-- Sales Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($sales)): ?>
                <?php foreach ($sales as $sale): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($sale['receipt_number']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $sale['customer_name'] ? htmlspecialchars($sale['customer_name']) : 'Walk-in'; ?>
                        <?php if ($sale['customer_phone']): ?>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($sale['customer_phone']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $sale['items_count']; ?> items
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($sale['total_amount']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $sale['payment_method'] ? ucfirst(str_replace('_', ' ', $sale['payment_method'])) : '-'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php echo $sale['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo ucfirst($sale['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y g:i A', strtotime($sale['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex justify-center items-center space-x-3">
                            <?php if ($sale['status'] === 'completed'): ?>
                            <a href="?action=receipt&id=<?php echo $sale['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 p-1" title="View Receipt">
                                <i class="fas fa-receipt"></i>
                            </a>
                            <?php else: ?>
                            <a href="?action=sale&id=<?php echo $sale['id']; ?>" 
                               class="text-green-600 hover:text-green-900 p-1" title="Continue Sale">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No sales found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print\:shadow-none, .print\:shadow-none * {
        visibility: visible;
    }
    .print\:shadow-none {
        position: absolute;
        left: 0;
        top: 0;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
