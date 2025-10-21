<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';
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
        case 'quotation_imported':
            $message = 'Quotation items imported successfully!';
            break;
        case 'quantity_updated':
            $message = 'Quantity updated successfully!';
            break;
        case 'price_updated':
            $message = 'Unit price updated successfully!';
            break;
        case 'item_removed':
            $message = 'Item removed from sale successfully!';
            break;
    }
}

// Handle form submissions
if ($_POST) {
    $form_action = $_POST['action'] ?? $action;
    switch ($form_action) {
        case 'create':
            // Check if inventory is available before creating sale
            $available_inventory = getPOSInventoryItems();
            if (empty($available_inventory)) {
                $error = 'Cannot create sale: No inventory items available. Please add inventory items with stock before creating a sale.';
            } else {
                $sale_id = createPOSSale(null, null);
                
                if ($sale_id) {
                    $message = 'New sale created successfully!';
                    $action = 'sale';
                    // Redirect to avoid form resubmission
                    header("Location: pos.php?action=sale&id=" . $sale_id);
                    exit();
                } else {
                    $error = 'Failed to create sale. Please check database connection and try again.';
                }
            }
            break;
            
        case 'add_item':
            if ($sale_id && isset($_POST['inventory_item_id']) && isset($_POST['quantity'])) {
                $selected_serials = isset($_POST['selected_serials']) ? $_POST['selected_serials'] : [];
                
                // Debug form submission
                error_log("FORM DEBUG - POST data: " . print_r($_POST, true));
                error_log("FORM DEBUG - Selected serials: " . print_r($selected_serials, true));
                error_log("FORM DEBUG - Inventory item ID: " . $_POST['inventory_item_id']);
                error_log("FORM DEBUG - Quantity: " . $_POST['quantity']);
                
                $result = addPOSSaleItemWithSerials($sale_id, $_POST['inventory_item_id'], $_POST['quantity'], $_POST['discount_percentage'] ?? 0, $selected_serials);
                if ($result['success']) {
                    header("Location: ?action=sale&id=" . $sale_id . "&success=item_added");
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'import_quotation':
            if ($sale_id && isset($_POST['quote_identifier'])) {
                $quote_identifier = trim($_POST['quote_identifier']);
                
                if (empty($quote_identifier)) {
                    $error = 'Please enter a quotation code or ID.';
                } else {
                    $result = importQuotationToPOS($sale_id, $quote_identifier);
                    if ($result['success']) {
                        $imported_count = count($result['imported_items']);
                        $message = "Successfully imported {$imported_count} items from quotation {$result['quote_number']}";
                        
                        // Add customer information to message if available
                        if (!empty($result['customer_name'])) {
                            $message .= " for customer: {$result['customer_name']}";
                            if (!empty($result['customer_phone'])) {
                                $message .= " ({$result['customer_phone']})";
                            }
                        }
                        
                        if (!empty($result['errors'])) {
                            $message .= ". Warnings: " . implode(', ', $result['errors']);
                        }
                        
                        header("Location: ?action=sale&id=" . $sale_id . "&success=quotation_imported");
                        exit();
                    } else {
                        $error = $result['message'];
                    }
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
            
        case 'update_price':
            if (isset($_POST['sale_item_id']) && isset($_POST['new_unit_price'])) {
                $result = updatePOSSaleItemPrice($_POST['sale_item_id'], $_POST['new_unit_price']);
                if ($result['success']) {
                    header("Location: ?action=sale&id=" . $sale_id . "&success=price_updated");
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'complete_sale':
            if ($sale_id && isset($_POST['payment_method']) && isset($_POST['amount_paid'])) {
                $result = completePOSSaleWithSerials(
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
            $sale = getPOSSaleWithSerials($sale_id);
            if (!$sale) {
                $error = 'Sale not found.';
                $action = 'new';
            } else {
                $inventory_items = getPOSInventoryItems();
            }
        } else {
            $error = 'No sale ID provided.';
            $action = 'new';
        }
        break;
        
    case 'history':
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        $status = $_GET['status'] ?? null;
        
        // Check if user wants to include incomplete transactions
        $include_incomplete = isset($_GET['include_incomplete']) && $_GET['include_incomplete'] === '1';
        
        $sales = getPOSSales($status, $date_from, $date_to, $include_incomplete);
        $stats = getPOSStats($date_from, $date_to);
        break;
        
    default:
        $inventory_items = getPOSInventoryItems();
        $stats = getPOSStats(date('Y-m-d'), date('Y-m-d')); // Today's stats
        break;
}

// Check if inventory is empty (no items with stock > 0)
$has_inventory = !empty($inventory_items);
if (!$has_inventory && in_array($action, ['new', 'sale'])) {
    $inventory_warning = 'No inventory items available for sale. Please add inventory items with stock before using POS.';
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

<?php if (isset($inventory_warning)): ?>
<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle mr-3"></i>
        <div>
            <strong>Warning:</strong> <?php echo htmlspecialchars($inventory_warning); ?>
            <div class="mt-2">
                <a href="inventory.php" class="text-yellow-800 underline hover:text-yellow-900">
                    <i class="fas fa-arrow-right mr-1"></i>Go to Inventory Management
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($action == 'new'): ?>
<!-- New Sale Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Point of Sale</h1>
            <p class="text-gray-600 dark:text-gray-400">Start a new sale for walk-in customers</p>
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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
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
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Start New Sale</h2>
    <form method="POST" action="pos.php" class="space-y-4">
        <input type="hidden" name="action" value="create">
        <div class="text-center">
            <p class="text-gray-600 dark:text-gray-400 mb-4">Start a new sale and add items. Customer details will be collected before payment.</p>
        </div>
        <div class="flex justify-end">
            <button type="submit" <?php echo !$has_inventory ? 'disabled' : ''; ?>
                    class="<?php echo $has_inventory ? 'bg-solar-blue hover:bg-blue-800' : 'bg-gray-400 cursor-not-allowed'; ?> text-white px-6 py-3 rounded-lg transition text-lg">
                <i class="fas fa-plus mr-2"></i>Start Sale
            </button>
            <?php if (!$has_inventory): ?>
            <p class="text-sm text-gray-500 mt-2">Cannot start sale without inventory items</p>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php elseif (($action == 'sale') && isset($sale)): ?>
<!-- Sale Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Sale: <?php echo htmlspecialchars($sale['receipt_number']); ?></h1>
            <p class="text-gray-600 dark:text-gray-400">
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
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Sale Items</h2>
                <div class="flex space-x-2">
                    <button onclick="document.getElementById('import-quotation-modal').classList.remove('hidden')" 
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                        <i class="fas fa-file-import mr-2"></i>Import Quote
                    </button>
                    <?php if (!empty($inventory_items)): ?>
                    <button onclick="document.getElementById('add-item-modal').classList.remove('hidden')" 
                            class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition text-sm">
                        <i class="fas fa-plus mr-2"></i>Add Item
                    </button>
                    <?php else: ?>
                    <button disabled class="bg-gray-400 text-white px-4 py-2 rounded-lg cursor-not-allowed text-sm">
                        <i class="fas fa-plus mr-2"></i>Add Item
                    </button>
                    <p class="text-xs text-gray-500 mt-1">No inventory available</p>
                    <?php endif; ?>
                </div>
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
                                <?php if (!empty($item['serial_numbers'])): ?>
                                <div class="text-xs text-green-600 mt-1">
                                    <strong>Serials:</strong> <?php echo htmlspecialchars($item['serial_numbers']); ?>
                                </div>
                                <?php endif; ?>
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
                                <form method="POST" action="?action=update_price&id=<?php echo $sale['id']; ?>" class="inline">
                                    <input type="hidden" name="sale_item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="new_unit_price" value="<?php echo $item['unit_price']; ?>" 
                                           min="0.01" step="0.01"
                                           class="w-20 px-2 py-1 border rounded text-center text-sm"
                                           onchange="this.form.submit()">
                                </form>
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
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Sale Summary</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                    <span class="font-medium"><?php echo formatCurrency($sale['subtotal']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Discount:</span>
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
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Process Payment</h2>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Please enter customer details before completing the transaction.
                </p>
            </div>
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
                    <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Customer Name <span class="text-red-500">*</span></label>
                    <input type="text" id="customer_name" name="customer_name" required
                           value="<?php echo htmlspecialchars($sale['customer_name'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter customer name">
                </div>
                <div>
                    <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">Customer Contact Number <span class="text-red-500">*</span></label>
                    <input type="tel" id="customer_phone" name="customer_phone" required
                           value="<?php echo htmlspecialchars($sale['customer_phone'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter contact number">
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
            <div class="mb-4 max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                <?php if (!empty($inventory_items)): ?>
                <div id="items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                    <?php foreach ($inventory_items as $inv_item): ?>
                    <div class="item-card border border-gray-200 dark:border-gray-600 rounded-lg p-3 hover:bg-blue-50 cursor-pointer transition" 
                         data-item-id="<?php echo $inv_item['id']; ?>"
                         data-brand="<?php echo strtolower($inv_item['brand']); ?>"
                         data-model="<?php echo strtolower($inv_item['model']); ?>"
                         data-category="<?php echo strtolower($inv_item['category_name'] ?? ''); ?>"
                         data-price="<?php echo $inv_item['selling_price']; ?>"
                         data-stock="<?php echo $inv_item['stock_quantity']; ?>"
                         data-generates-serials="<?php echo $inv_item['generate_serials'] ? '1' : '0'; ?>"
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
                                <?php if ($inv_item['generate_serials'] && $inv_item['available_serials'] > 0): ?>
                                <div class="text-xs text-blue-600">
                                    <i class="fas fa-barcode mr-1"></i><?php echo $inv_item['available_serials']; ?> serials available
                                </div>
                                <?php endif; ?>
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
                <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-box-open text-3xl mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No Inventory Available</h3>
                    <p class="text-sm text-gray-500 mb-4">There are no items with stock available for sale.</p>
                    <a href="inventory.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Add Inventory Items
                    </a>
                </div>
                <?php endif; ?>
                
                <div id="no-items-message" class="hidden text-center py-8 text-gray-500">
                    <i class="fas fa-search text-2xl mb-2"></i>
                    <p>No items found matching your search.</p>
                </div>
            </div>
            
            <!-- Selected Item Form -->
            <form id="add-item-form" method="POST" action="?action=add_item&id=<?php echo $sale['id']; ?>" class="hidden" onsubmit="return validateFormSubmission()">
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
                               oninput="updateTotalPreview(); loadAvailableSerials()">
                    </div>
                    <div>
                        <label for="discount_percentage" class="block text-sm font-medium text-gray-700 mb-2">Discount %</label>
                        <input type="number" min="0" max="100" step="0.01" id="discount_percentage" name="discount_percentage" value="0"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                               oninput="updateTotalPreview()">
                    </div>
                </div>
                
                <!-- Serial Number Selection -->
                <div id="serial-selection-section" class="mb-4 hidden">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Serial Number Selection</h4>
                    <div id="serial-selection-status" class="text-sm text-blue-600 mb-2 font-medium">
                        <!-- Status will be updated here -->
                    </div>
                    <div id="available-serials" class="border border-gray-200 rounded-lg p-3 bg-gray-50 max-h-32 overflow-y-auto">
                        <!-- Serial numbers will be loaded here -->
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Select the specific serial numbers to sell</p>
                </div>
                
                <div id="total-preview" class="bg-gray-50 border border-gray-200 dark:border-gray-600 rounded-lg p-3 mb-4 hidden">
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

<!-- Import Quotation Modal -->
<div id="import-quotation-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Import Items from Quotation</h3>
            
            <form method="POST" action="?action=import_quotation&id=<?php echo $sale['id']; ?>" onsubmit="return validateQuotationImport()">
                <div class="space-y-4">
                    <div>
                        <label for="quote_identifier" class="block text-sm font-medium text-gray-700 mb-2">Quotation Code or ID</label>
                        <input type="text" id="quote_identifier" name="quote_identifier" required
                               placeholder="Enter quotation code (e.g., QTE20241220-0001) or ID"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">You can paste the quotation code or enter the quotation ID</p>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-2">Import Information:</h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• All items from the quotation will be added to this sale</li>
                            <li>• Customer name and phone number will be imported</li>
                            <li>• Original prices, quantities, and discounts will be preserved</li>
                            <li>• Items that are out of stock will be skipped with a warning</li>
                            <li>• If items already exist in the sale, quantities will be combined</li>
                            <li>• Prices will match exactly what was used in the quotation</li>
                        </ul>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="document.getElementById('import-quotation-modal').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        <i class="fas fa-file-import mr-2"></i>Import Items
                    </button>
                </div>
            </form>
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
    const dataGeneratesSerials = cardElement.getAttribute('data-generates-serials');
    
    selectedItemData = {
        id: cardElement.getAttribute('data-item-id'),
        brand: cardElement.querySelector('.text-sm.font-medium').textContent,
        model: cardElement.querySelector('.text-sm.text-gray-500').textContent,
        price: parseFloat(cardElement.getAttribute('data-price')),
        stock: parseInt(cardElement.getAttribute('data-stock')),
        generatesSerials: dataGeneratesSerials === '1'
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
    
    // Load available serials if item generates them
    if (selectedItemData.generatesSerials) {
        loadAvailableSerials();
    } else {
        document.getElementById('serial-selection-section').classList.add('hidden');
    }
}

function clearSelection() {
    // Clear visual selection
    document.querySelectorAll('.item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Hide form and serial selection
    document.getElementById('add-item-form').classList.add('hidden');
    document.getElementById('serial-selection-section').classList.add('hidden');
    selectedItemData = null;
}

function loadAvailableSerials() {
    if (!selectedItemData || !selectedItemData.generatesSerials) {
        return;
    }
    
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    
    // Fetch available serials via AJAX
    fetch(`get_available_serials.php?item_id=${selectedItemData.id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySerialSelection(data.serials, quantity);
            } else {
                console.error('Failed to load serials:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading serials:', error);
        });
}

function displaySerialSelection(serials, quantity) {
    const container = document.getElementById('available-serials');
    const section = document.getElementById('serial-selection-section');
    
    if (serials.length === 0) {
        container.innerHTML = '<p class="text-red-500 text-sm font-medium">No stock available</p>';
        section.classList.add('hidden');
        return;
    }
    
    if (quantity > serials.length) {
        container.innerHTML = `<p class="text-red-500 text-sm">Only ${serials.length} serial numbers available, but ${quantity} requested</p>`;
        section.classList.remove('hidden');
        return;
    }
    
    let html = '<div class="space-y-2">';
    // Controls row to match quotation style
    html += `
        <div class="mb-3 pb-2 border-b border-gray-200">
            <button type="button" onclick="selectAllPOSSerials(${quantity})" 
                    class="px-3 py-1 bg-solar-blue text-white text-sm rounded hover:bg-blue-700 transition-colors">
                Select All (${quantity})
            </button>
            <button type="button" onclick="clearAllPOSSerials()" 
                    class="ml-2 px-3 py-1 bg-gray-500 text-white text-sm rounded hover:bg-gray-600 transition-colors">
                Clear All
            </button>
        </div>
    `;
    serials.forEach(serial => {
        html += `
            <label class="flex items-center">
                <input type="checkbox" name="selected_serials[]" value="${serial.serial_number}" 
                       class="serial-checkbox rounded border-gray-300 text-solar-blue focus:ring-solar-blue"
                       onchange="validateSerialSelection()">
                <span class="ml-2 text-sm font-mono">${serial.serial_number}</span>
            </label>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
    section.classList.remove('hidden');
    
    // Update submit button state after displaying serials
    updateSubmitButtonState();
}

function validateSerialSelection() {
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    const checkboxes = document.querySelectorAll('.serial-checkbox:checked');
    
    if (checkboxes.length > quantity) {
        alert(`You can only select ${quantity} serial number(s). Please uncheck some selections.`);
        // Uncheck the last selected checkbox
        checkboxes[checkboxes.length - 1].checked = false;
    }
    
    // Update submit button state based on validation
    updateSubmitButtonState();
}

function selectAllPOSSerials(maxQuantity) {
    const checkboxes = document.querySelectorAll('.serial-checkbox');
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    const targetQuantity = Math.min(quantity, maxQuantity);
    // Clear all first
    checkboxes.forEach(cb => { cb.checked = false; });
    // Select up to the required quantity
    let selectedCount = 0;
    checkboxes.forEach(cb => {
        if (selectedCount < targetQuantity) {
            cb.checked = true;
            selectedCount++;
        }
    });
    updateSubmitButtonState();
}

function clearAllPOSSerials() {
    const checkboxes = document.querySelectorAll('.serial-checkbox');
    checkboxes.forEach(cb => { cb.checked = false; });
    updateSubmitButtonState();
}

function updateSubmitButtonState() {
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    const checkboxes = document.querySelectorAll('.serial-checkbox:checked');
    const submitButton = document.querySelector('#add-item-form button[type="submit"]');
    const serialSection = document.getElementById('serial-selection-section');
    const statusElement = document.getElementById('serial-selection-status');
    
    // Check if this is a serialized item
    if (selectedItemData && selectedItemData.generatesSerials && !serialSection.classList.contains('hidden')) {
        const selectedCount = checkboxes.length;
        
        // Update status message
        if (statusElement) {
            if (selectedCount === 0) {
                statusElement.textContent = `Please select ${quantity} serial number(s)`;
                statusElement.className = 'text-sm text-red-600 mb-2 font-medium';
            } else if (selectedCount < quantity) {
                statusElement.textContent = `Selected ${selectedCount} of ${quantity} serial number(s)`;
                statusElement.className = 'text-sm text-orange-600 mb-2 font-medium';
            } else if (selectedCount === quantity) {
                statusElement.textContent = `✓ Selected ${selectedCount} serial number(s) - Ready to add`;
                statusElement.className = 'text-sm text-green-600 mb-2 font-medium';
            } else {
                statusElement.textContent = `Too many selected (${selectedCount}/${quantity})`;
                statusElement.className = 'text-sm text-red-600 mb-2 font-medium';
            }
        }
        
        if (checkboxes.length !== quantity) {
            submitButton.disabled = true;
            submitButton.textContent = `Select ${quantity} Serial Number(s)`;
            submitButton.classList.add('bg-gray-400', 'cursor-not-allowed');
            submitButton.classList.remove('bg-solar-blue', 'hover:bg-blue-800');
        } else {
            submitButton.disabled = false;
            submitButton.textContent = 'Add to Sale';
            submitButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
            submitButton.classList.add('bg-solar-blue', 'hover:bg-blue-800');
        }
    } else {
        // For non-serialized items, always enable the button
        if (statusElement) {
            statusElement.textContent = '';
        }
        submitButton.disabled = false;
        submitButton.textContent = 'Add to Sale';
        submitButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
        submitButton.classList.add('bg-solar-blue', 'hover:bg-blue-800');
    }
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
    
    // Update submit button state when quantity changes
    updateSubmitButtonState();
}

function validateQuotationImport() {
    const quoteIdentifier = document.getElementById('quote_identifier').value.trim();
    
    if (!quoteIdentifier) {
        alert('Please enter a quotation code or ID.');
        return false;
    }
    
    return confirm('Are you sure you want to import items from this quotation? This will add all items to the current sale.');
}

function validateFormSubmission() {
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    const checkboxes = document.querySelectorAll('.serial-checkbox:checked');
    const serialSection = document.getElementById('serial-selection-section');
    
    // Check if this is a serialized item
    if (selectedItemData && selectedItemData.generatesSerials && !serialSection.classList.contains('hidden')) {
        if (checkboxes.length !== quantity) {
            alert(`Please select exactly ${quantity} serial number(s). Currently selected: ${checkboxes.length}`);
            return false;
        }
    }
    
    return true;
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
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Sale Receipt</h1>
            <p class="text-gray-600 dark:text-gray-400">Receipt #<?php echo htmlspecialchars($sale['receipt_number']); ?></p>
        </div>
        <div class="space-x-2">
            <button onclick="printReceipt()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-receipt mr-2"></i>Print Receipt
            </button>
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-print mr-2"></i>Print Full Page
            </button>
            <a href="?" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
                <i class="fas fa-plus mr-2"></i>New Sale
            </a>
        </div>
    </div>
</div>

<script>
function printReceipt() {
    console.log('Print receipt function called');
    
    // Get the thermal receipt element
    const thermalReceipt = document.getElementById('thermal-receipt');
    console.log('Thermal receipt element:', thermalReceipt);
    
    if (!thermalReceipt) {
        alert('Receipt template not found!');
        return;
    }
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=600,height=800');
    
    // Get the receipt content
    const receiptContent = thermalReceipt.innerHTML;
    
    // Write the content to the new window
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt Print</title>
            <style>
                body {
                    font-family: 'Courier New', monospace;
                    font-size: 14px;
                    line-height: 1.3;
                    margin: 0;
                    padding: 20px;
                    background: white;
                    color: black;
                }
                .thermal-receipt {
                    width: 100%;
                    max-width: 400px;
                    margin: 0 auto;
                    background: white;
                    color: black;
                }
                .thermal-content {
                    padding: 0;
                }
                .thermal-header {
                    text-align: center;
                    margin-bottom: 15px;
                }
                .thermal-company-name {
                    font-weight: bold;
                    font-size: 18px;
                    margin-bottom: 4px;
                }
                .thermal-company-subtitle {
                    font-size: 12px;
                    margin-bottom: 3px;
                }
                .thermal-company-tagline {
                    font-size: 10px;
                    margin-bottom: 3px;
                }
                .thermal-company-tin {
                    font-size: 9px;
                    margin-bottom: 3px;
                }
                .thermal-company-contact {
                    font-size: 9px;
                    margin-bottom: 3px;
                }
                .thermal-company-address {
                    font-size: 9px;
                    margin-bottom: 8px;
                }
                .thermal-separator {
                    text-align: center;
                    font-size: 12px;
                    margin: 8px 0;
                }
                .thermal-receipt-number {
                    font-weight: bold;
                    font-size: 14px;
                    margin-top: 8px;
                }
                .thermal-details {
                    margin-bottom: 15px;
                }
                .thermal-detail-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 4px;
                    font-size: 12px;
                }
                .thermal-label {
                    font-weight: bold;
                }
                .thermal-value {
                    text-align: right;
                }
                .thermal-items {
                    margin-bottom: 15px;
                }
                .thermal-item {
                    margin-bottom: 8px;
                    font-size: 12px;
                }
                .thermal-item-name {
                    font-weight: bold;
                    margin-bottom: 3px;
                    word-wrap: break-word;
                }
                .thermal-item-details {
                    display: flex;
                    justify-content: space-between;
                    font-size: 11px;
                    margin-bottom: 3px;
                }
                .thermal-item-qty {
                    font-weight: bold;
                }
                .thermal-item-price {
                    text-align: center;
                }
                .thermal-item-discount {
                    color: #666;
                    font-style: italic;
                }
                .thermal-item-total {
                    text-align: right;
                    font-weight: bold;
                    font-size: 13px;
                }
                .thermal-totals {
                    margin-bottom: 15px;
                }
                .thermal-total-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 4px;
                    font-size: 12px;
                }
                .thermal-total-label {
                    font-weight: bold;
                }
                .thermal-total-value {
                    text-align: right;
                }
                .thermal-grand-total {
                    font-size: 14px;
                    font-weight: bold;
                    border-top: 2px solid #000;
                    padding-top: 5px;
                    margin-top: 5px;
                }
                .thermal-discount {
                    color: #666;
                }
                .thermal-change {
                    color: #0066cc;
                    font-weight: bold;
                }
                .thermal-footer {
                    text-align: center;
                    margin-top: 20px;
                }
                .thermal-thank-you {
                    font-weight: bold;
                    margin-bottom: 8px;
                    font-size: 13px;
                }
                .thermal-warranty {
                    font-size: 10px;
                    margin-bottom: 20px;
                    color: #666;
                }
                .thermal-signature {
                    margin-top: 25px;
                }
                .thermal-signature-line {
                    margin-bottom: 8px;
                    font-size: 12px;
                }
                .thermal-signature-label {
                    font-size: 10px;
                    color: #666;
                }
                @media print {
                    body { margin: 0; }
                    @page { margin: 0.5in; }
                }
            </style>
        </head>
        <body>
            <div class="thermal-receipt">
                ${receiptContent}
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for content to load, then print
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
}
</script>

<!-- Thermal Receipt Template (Hidden) -->
<div id="thermal-receipt" class="thermal-receipt hidden">
    <div class="thermal-content">
        <div class="thermal-header">
            <div class="thermal-company-name"><?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></div>
            <div class="thermal-company-subtitle"><?php echo htmlspecialchars(getSystemSetting('company_subtitle', 'Solar Power Installation Services')); ?></div>
            <div class="thermal-company-tagline"><?php echo htmlspecialchars(getSystemSetting('company_tagline', 'Your Trusted Partner in Solar Solutions')); ?></div>
            <div class="thermal-company-tin"><?php echo htmlspecialchars(getSystemSetting('company_tin', 'NON VAT Reg TIN: 247-334-690-00001')); ?></div>
            <div class="thermal-company-contact">
                📧 <?php echo htmlspecialchars(getSystemSetting('company_email', 'info@4nsolar.com')); ?> | 
                📞 <?php echo htmlspecialchars(getSystemSetting('company_phone', '+63 906 386 1728')); ?>
            </div>
            <div class="thermal-company-address">📍 <?php echo htmlspecialchars(getSystemSetting('company_address', 'Zambonga City, Philippines')); ?></div>
            <div class="thermal-separator">═══════════════════════════════════</div>
            <div class="thermal-receipt-number">Receipt #<?php echo htmlspecialchars($sale['receipt_number']); ?></div>
        </div>
        
        <div class="thermal-details">
            <div class="thermal-detail-row">
                <span class="thermal-label">Date:</span>
                <span class="thermal-value"><?php echo date('M j, Y g:i A', strtotime($sale['completed_at'])); ?></span>
            </div>
            <div class="thermal-detail-row">
                <span class="thermal-label">Cashier:</span>
                <span class="thermal-value"><?php echo htmlspecialchars($sale['cashier_name']); ?></span>
            </div>
            <?php if ($sale['customer_name']): ?>
            <div class="thermal-detail-row">
                <span class="thermal-label">Customer:</span>
                <span class="thermal-value"><?php echo htmlspecialchars($sale['customer_name']); ?></span>
            </div>
            <?php if ($sale['customer_phone']): ?>
            <div class="thermal-detail-row">
                <span class="thermal-label">Phone:</span>
                <span class="thermal-value"><?php echo htmlspecialchars($sale['customer_phone']); ?></span>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="thermal-detail-row">
                <span class="thermal-label">Customer:</span>
                <span class="thermal-value">Walk-in</span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="thermal-separator">─────────────────────────────────────</div>
        
        <div class="thermal-items">
            <?php foreach ($sale['items'] as $item): ?>
            <div class="thermal-item">
                <div class="thermal-item-name"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                <div class="thermal-item-details">
                    <span class="thermal-item-qty"><?php echo $item['quantity']; ?>x</span>
                    <span class="thermal-item-price"><?php echo formatCurrency($item['unit_price']); ?></span>
                    <?php if ($item['discount_percentage'] > 0): ?>
                    <span class="thermal-item-discount">-<?php echo $item['discount_percentage']; ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="thermal-item-total"><?php echo formatCurrency($item['total_amount']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="thermal-separator">─────────────────────────────────────</div>
        
        <div class="thermal-totals">
            <div class="thermal-total-row">
                <span class="thermal-total-label">Subtotal:</span>
                <span class="thermal-total-value"><?php echo formatCurrency($sale['subtotal']); ?></span>
            </div>
            <?php if ($sale['total_discount'] > 0): ?>
            <div class="thermal-total-row thermal-discount">
                <span class="thermal-total-label">Discount:</span>
                <span class="thermal-total-value">-<?php echo formatCurrency($sale['total_discount']); ?></span>
            </div>
            <?php endif; ?>
            <div class="thermal-total-row thermal-grand-total">
                <span class="thermal-total-label">TOTAL:</span>
                <span class="thermal-total-value"><?php echo formatCurrency($sale['total_amount']); ?></span>
            </div>
            <div class="thermal-total-row">
                <span class="thermal-total-label">Paid (<?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?>):</span>
                <span class="thermal-total-value"><?php echo formatCurrency($sale['amount_paid']); ?></span>
            </div>
            <?php if ($sale['change_amount'] > 0): ?>
            <div class="thermal-total-row thermal-change">
                <span class="thermal-total-label">Change:</span>
                <span class="thermal-total-value"><?php echo formatCurrency($sale['change_amount']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="thermal-separator">═══════════════════════════════════</div>
        
        <div class="thermal-footer">
            <div class="thermal-thank-you">Thank you for your business!</div>
            <div class="thermal-warranty">For warranty and support, please keep this receipt.</div>
            <div class="thermal-signature">
                <div class="thermal-signature-line">_________________________</div>
                <div class="thermal-signature-label">Cashier Authorized Representative</div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Content -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 max-w-2xl mx-auto print:shadow-none print:max-w-none">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></h2>
        <p class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars(getSystemSetting('company_subtitle', 'Solar Power Installation Services')); ?></p>
        <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars(getSystemSetting('company_tagline', 'Your Trusted Partner in Solar Solutions')); ?></p>
        <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars(getSystemSetting('company_tin', 'NON VAT Reg TIN: 247-334-690-00001')); ?></p>
        <p class="text-xs text-gray-500 mb-2">📧 <?php echo htmlspecialchars(getSystemSetting('company_email', 'info@4nsolar.com')); ?> | 📞 <?php echo htmlspecialchars(getSystemSetting('company_phone', '+63 906 386 1728')); ?> | 📍 <?php echo htmlspecialchars(getSystemSetting('company_address', 'Zambonga City, Philippines')); ?></p>
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
                    <th class="text-right py-2 w-20">Disc %</th>
                <th class="text-right py-2 w-24">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale['items'] as $item): ?>
            <tr class="border-b">
                <td class="py-2">
                    <div class="font-medium"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                </td>
                <td class="text-center py-2"><?php echo $item['quantity']; ?></td>
                <td class="text-right py-2"><?php echo formatCurrency($item['unit_price']); ?></td>
                <td class="text-right py-2">
                    <?php if ($item['discount_percentage'] > 0): ?>
                    <span class="text-green-600 font-medium"><?php echo $item['discount_percentage']; ?>%</span>
                    <?php else: ?>
                    <span class="text-gray-400">0%</span>
                    <?php endif; ?>
                </td>
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
    
    <div class="text-center text-sm text-gray-500 mb-8">
        <p>Thank you for your business!</p>
        <p>For warranty and support, please keep this receipt.</p>
    </div>
    
    <!-- Signature Section -->
    <div class="mt-8 pt-6 border-t border-gray-300">
        <div class="flex justify-center">
            <div class="text-center" style="width: 45%;">
                <div class="border-b border-gray-400 mb-2 pb-8"></div>
                <p class="text-sm text-gray-700 font-medium">Cashier Authorized Representative</p>
            </div>
        </div>
    </div>
</div>

<?php elseif ($action == 'history'): ?>
<!-- Sales History -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Sales History</h1>
            <p class="text-gray-600 dark:text-gray-400">View all POS sales transactions</p>
        </div>
        <a href="?" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            <i class="fas fa-plus mr-2"></i>New Sale
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
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
                <option value="">Completed Only (Default)</option>
                <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
        </div>
        <div>
            <label class="flex items-center text-sm font-medium text-gray-700">
                <input type="checkbox" name="include_incomplete" value="1" 
                       <?php echo (isset($_GET['include_incomplete']) && $_GET['include_incomplete'] === '1') ? 'checked' : ''; ?>
                       class="mr-2 rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                Show All Transactions (including incomplete)
            </label>
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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">Total Sales</h3>
        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_sales']; ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
        <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['total_revenue']); ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">Today's Sales</h3>
        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['today_sales']; ?></p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-500">Today's Revenue</h3>
        <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['today_revenue']); ?></p>
    </div>
</div>

<!-- Sales Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
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
/* Receipt Styles for Regular Printer */
.thermal-receipt {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    line-height: 1.3;
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    background: white;
    color: black;
    padding: 20px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.thermal-content {
    padding: 0;
}

.thermal-header {
    text-align: center;
    margin-bottom: 10px;
}

.thermal-company-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 4px;
}

.thermal-company-subtitle {
    font-size: 12px;
    margin-bottom: 3px;
}

.thermal-company-tagline {
    font-size: 10px;
    margin-bottom: 3px;
}

.thermal-company-tin {
    font-size: 9px;
    margin-bottom: 3px;
}

.thermal-company-contact {
    font-size: 9px;
    margin-bottom: 3px;
}

.thermal-company-address {
    font-size: 9px;
    margin-bottom: 8px;
}

.thermal-separator {
    text-align: center;
    font-size: 12px;
    margin: 8px 0;
}

.thermal-receipt-number {
    font-weight: bold;
    font-size: 14px;
    margin-top: 8px;
}

.thermal-details {
    margin-bottom: 15px;
}

.thermal-detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 12px;
}

.thermal-label {
    font-weight: bold;
}

.thermal-value {
    text-align: right;
}

.thermal-items {
    margin-bottom: 15px;
}

.thermal-item {
    margin-bottom: 8px;
    font-size: 12px;
}

.thermal-item-name {
    font-weight: bold;
    margin-bottom: 3px;
    word-wrap: break-word;
}

.thermal-item-details {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    margin-bottom: 3px;
}

.thermal-item-qty {
    font-weight: bold;
}

.thermal-item-price {
    text-align: center;
}

.thermal-item-discount {
    color: #666;
    font-style: italic;
}

.thermal-item-total {
    text-align: right;
    font-weight: bold;
    font-size: 13px;
}

.thermal-totals {
    margin-bottom: 15px;
}

.thermal-total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 12px;
}

.thermal-total-label {
    font-weight: bold;
}

.thermal-total-value {
    text-align: right;
}

.thermal-grand-total {
    font-size: 14px;
    font-weight: bold;
    border-top: 2px solid #000;
    padding-top: 5px;
    margin-top: 5px;
}

.thermal-discount {
    color: #666;
}

.thermal-change {
    color: #0066cc;
    font-weight: bold;
}

.thermal-footer {
    text-align: center;
    margin-top: 20px;
}

.thermal-thank-you {
    font-weight: bold;
    margin-bottom: 8px;
    font-size: 13px;
}

.thermal-warranty {
    font-size: 10px;
    margin-bottom: 20px;
    color: #666;
}

.thermal-signature {
    margin-top: 25px;
}

.thermal-signature-line {
    margin-bottom: 8px;
    font-size: 12px;
}

.thermal-signature-label {
    font-size: 10px;
    color: #666;
}

/* Print styles for regular printer */
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
    
    /* Receipt print styles for regular printer */
    .thermal-receipt, .thermal-receipt * {
        visibility: visible;
    }
    
    .thermal-receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 400px;
        margin: 0;
        padding: 20px;
        background: white;
        color: black;
        border: none;
        box-shadow: none;
    }
    
    @page {
        margin: 0.5in;
        size: A4;
    }
    
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
