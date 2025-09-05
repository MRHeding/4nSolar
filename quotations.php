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
$quote_id = $_GET['quote_id'] ?? null;

// Check for success messages from redirects
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'create_quote':
            $quote_id = createQuote($_POST['customer_name'] ?? null, $_POST['customer_phone'] ?? null, $_POST['proposal_name'] ?? null);
            if ($quote_id) {
                // Check if KW and Labor Fee are provided
                $kw = floatval($_POST['kw'] ?? 0);
                $labor_fee = floatval($_POST['labor_fee'] ?? 0);
                
                if ($kw > 0 && $labor_fee > 0) {
                    $total_labor_cost = $kw * $labor_fee;
                    $labor_item_name = "Labor Fee Calculation";
                    
                    // Add the labor fee as a custom quote item
                    $labor_result = addCustomQuoteItem($quote_id, $labor_item_name, $kw, $labor_fee);
                    
                    if ($labor_result['success']) {
                        $message = 'New quotation created successfully with labor fee!';
                    } else {
                        $message = 'Quotation created, but failed to add labor fee: ' . $labor_result['message'];
                    }
                } else {
                    $message = 'New quotation created successfully!';
                }
                $action = 'quote';
            } else {
                $error = 'Failed to create quotation.';
            }
            break;
            
        case 'add_to_quote':
            if ($quote_id && isset($_POST['inventory_item_id']) && isset($_POST['quantity'])) {
                $result = addQuoteItem($quote_id, $_POST['inventory_item_id'], $_POST['quantity'], $_POST['discount_percentage'] ?? 0);
                if ($result['success']) {
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Item added to quotation successfully!'));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_quote_quantity':
            if (isset($_POST['quote_item_id']) && isset($_POST['new_quantity'])) {
                $result = updateQuoteItemQuantity($_POST['quote_item_id'], $_POST['new_quantity']);
                if ($result['success']) {
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Quantity updated successfully!'));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_quote_status':
            if ($quote_id && isset($_POST['new_status'])) {
                $result = updateQuoteStatus($quote_id, $_POST['new_status']);
                if ($result) {
                    $success_message = 'Status updated successfully!';
                    
                    // If status was changed to accepted, show additional message about project conversion
                    if ($_POST['new_status'] === 'accepted') {
                        $success_message .= ' Quotation has been converted to an approved solar project.';
                    }
                    
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode($success_message));
                    exit();
                } else {
                    $error = 'Failed to update quotation status.';
                }
            }
            break;
    }
}

// Handle quote actions
if ($action == 'remove_quote_item' && isset($_GET['item_id']) && $quote_id) {
    if (removeQuoteItem($_GET['item_id'])) {
        header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Item removed from quotation successfully!'));
        exit();
    } else {
        $error = 'Failed to remove item from quotation.';
    }
}

if ($action == 'delete_quote' && $quote_id) {
    if (deleteQuote($quote_id)) {
        $message = 'Quotation deleted successfully!';
        $action = 'list';
    } else {
        $error = 'Failed to delete quotation.';
    }
}

// Get data based on action
switch ($action) {
    case 'quote':
        if ($quote_id) {
            $quote = getQuote($quote_id);
            if (!$quote) {
                $error = 'Quotation not found.';
                $action = 'list';
            } else {
                $inventory_items = getQuoteInventoryItems();
            }
        }
        break;
        
    case 'new_quote':
        $inventory_items = getQuoteInventoryItems();
        break;
        
    default:
        $quotes = getQuotes();
        break;
}

$page_title = 'Quotations Management';
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
<!-- Quotations List -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Quotations Management</h1>
            <p class="text-gray-600">Manage all customer quotations</p>
        </div>
        <div class="space-x-2">
            <a href="?action=new_quote" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-plus mr-2"></i>New Quote
            </a>
        </div>
    </div>
</div>

<!-- Quotations Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quote #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($quotes)): ?>
                <?php foreach ($quotes as $quote): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($quote['quote_number']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($quote['customer_name']); ?>
                        <?php if ($quote['customer_phone']): ?>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($quote['customer_phone']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($quote['proposal_name'] ?? '-'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $quote['items_count']; ?> items
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($quote['total_amount']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php 
                            switch($quote['status']) {
                                case 'draft': echo 'bg-gray-100 text-gray-800'; break;
                                case 'sent': echo 'bg-blue-100 text-blue-800'; break;
                                case 'under_review': echo 'bg-purple-100 text-purple-800'; break;
                                case 'accepted': 
                                case 'approved': echo 'bg-green-100 text-green-800'; break;
                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                case 'expired': echo 'bg-yellow-100 text-yellow-800'; break;
                            }
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $quote['status'])); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($quote['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex justify-center items-center space-x-2">
                            <a href="?action=quote&quote_id=<?php echo $quote['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 p-1" title="View/Edit Quote">
                                <i class="fas fa-eye"></i>
                            </a>
                            
                            <!-- Status Update Buttons -->
                            <?php if ($quote['status'] == 'draft'): ?>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                <input type="hidden" name="new_status" value="sent">
                                <button type="submit" class="text-purple-600 hover:text-purple-900 p-1" title="Mark as Sent">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                <input type="hidden" name="new_status" value="accepted">
                                <button type="submit" class="text-green-600 hover:text-green-900 p-1" title="Approve Quote">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <?php elseif ($quote['status'] == 'sent'): ?>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                <input type="hidden" name="new_status" value="accepted">
                                <button type="submit" class="text-green-600 hover:text-green-900 p-1" title="Approve Quote">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                <input type="hidden" name="new_status" value="rejected">
                                <button type="submit" class="text-red-600 hover:text-red-900 p-1" title="Reject Quote">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php elseif ($quote['status'] == 'accepted' || $quote['status'] == 'approved'): ?>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                <input type="hidden" name="new_status" value="draft">
                                <button type="submit" class="text-gray-600 hover:text-gray-900 p-1" title="Revert to Draft">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <a href="print_inventory_quote.php?id=<?php echo $quote['id']; ?>" target="_blank"
                               class="text-orange-600 hover:text-orange-900 p-1" title="Print Quote">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="?action=delete_quote&quote_id=<?php echo $quote['id']; ?>" 
                               class="text-red-600 hover:text-red-900 p-1"
                               onclick="return confirm('Are you sure you want to delete this quotation?')"
                               title="Delete Quote">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No quotations found. <a href="?action=new_quote" class="text-blue-600 hover:text-blue-800">Create your first quotation</a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($action == 'new_quote'): ?>
<!-- New Quotation Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">New Quotation</h1>
            <p class="text-gray-600">Create a new quotation for customer</p>
        </div>
        <div class="space-x-2">
            <a href="?" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Quotes
            </a>
        </div>
    </div>
</div>

<!-- Create New Quote -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New Quotation</h2>
    <form method="POST" action="?action=create_quote" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                <input type="text" id="customer_name" name="customer_name" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       placeholder="Enter customer name">
            </div>
            <div>
                <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">Customer Phone</label>
                <input type="tel" id="customer_phone" name="customer_phone"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       placeholder="Enter phone number">
            </div>
            <div>
                <label for="proposal_name" class="block text-sm font-medium text-gray-700 mb-2">Proposal Name</label>
                <input type="text" id="proposal_name" name="proposal_name"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       placeholder="Enter proposal name">
            </div>
            <div>
                <label for="kw" class="block text-sm font-medium text-gray-700 mb-2">KW</label>
                <input type="number" id="kw" name="kw" min="0" step="any" 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent" 
                       placeholder="Enter KW value">
            </div>
            <div>
                <label for="labor_fee" class="block text-sm font-medium text-gray-700 mb-2">Labor Fee (PHP)</label>
                <input type="number" id="labor_fee" name="labor_fee" min="0" step="any" 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent" 
                       placeholder="Enter Labor Fee">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Total (KW * Labor Fee)</label>
                <input type="text" id="total_kw_labor" readonly 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-100" 
                       value="" placeholder="Total will appear here">
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition text-lg">
                <i class="fas fa-plus mr-2"></i>Create Quotation
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateTotal() {
        var kw = parseFloat(document.getElementById('kw').value) || 0;
        var labor = parseFloat(document.getElementById('labor_fee').value) || 0;
        document.getElementById('total_kw_labor').value = kw * labor ? (kw * labor).toLocaleString('en-PH', {style: 'currency', currency: 'PHP'}) : '';
    }
    document.getElementById('kw').addEventListener('input', updateTotal);
    document.getElementById('labor_fee').addEventListener('input', updateTotal);
});
</script>

<?php elseif (($action == 'quote') && isset($quote)): ?>
<!-- Quote Detail Screen -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Quotation: <?php echo htmlspecialchars($quote['quote_number']); ?></h1>
            <p class="text-gray-600">
                Customer: <?php echo htmlspecialchars($quote['customer_name']); ?>
                <?php if ($quote['customer_phone']): ?>
                - <?php echo htmlspecialchars($quote['customer_phone']); ?>
                <?php endif; ?>
                <?php if ($quote['proposal_name']): ?>
                | Proposal: <?php echo htmlspecialchars($quote['proposal_name']); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="space-x-2">
            <a href="print_inventory_quote.php?id=<?php echo $quote['id']; ?>" target="_blank"
               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-print mr-2"></i>Print Quote
            </a>
            <a href="?action=delete_quote&quote_id=<?php echo $quote['id']; ?>" 
               class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition"
               onclick="return confirm('Delete this quotation? All items will be removed.')">
                <i class="fas fa-trash mr-2"></i>Delete Quote
            </a>
            <a href="?" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Quotes
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Quote Items -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Quote Items</h2>
                <?php if (!empty($inventory_items)): ?>
                <button onclick="document.getElementById('add-quote-item-modal').classList.remove('hidden')" 
                        class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition text-sm">
                    <i class="fas fa-plus mr-2"></i>Add Item
                </button>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($quote['items'])): ?>
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
                        <?php foreach ($quote['items'] as $item): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                                <div class="text-xs text-blue-600">Stock: <?php echo $item['stock_quantity']; ?></div>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <form method="POST" action="?action=update_quote_quantity&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                    <input type="hidden" name="quote_item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="new_quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" class="w-16 px-2 py-1 border rounded text-center text-sm"
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
                                <a href="?action=remove_quote_item&quote_id=<?php echo $quote['id']; ?>&item_id=<?php echo $item['id']; ?>" 
                                   class="text-red-600 hover:text-red-900 p-2"
                                   onclick="return confirm('Remove this item?')"
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
            <p class="text-gray-500 text-center py-8">No items added to this quotation yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quote Summary -->
    <div>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Quote Summary</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium"><?php echo formatCurrency($quote['subtotal']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Discount:</span>
                    <span class="font-medium text-green-600">-<?php echo formatCurrency($quote['total_discount']); ?></span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t pt-3">
                    <span>Total:</span>
                    <span class="text-solar-blue"><?php echo formatCurrency($quote['total_amount']); ?></span>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Quote Details</h3>
                <div class="text-sm text-gray-600">
                    <p><strong>Status:</strong> 
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?php 
                            switch($quote['status']) {
                                case 'draft': echo 'bg-gray-100 text-gray-800'; break;
                                case 'sent': echo 'bg-blue-100 text-blue-800'; break;
                                case 'under_review': echo 'bg-purple-100 text-purple-800'; break;
                                case 'accepted': 
                                case 'approved': echo 'bg-green-100 text-green-800'; break;
                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                case 'expired': echo 'bg-yellow-100 text-yellow-800'; break;
                            }
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $quote['status'])); ?>
                        </span>
                    </p>
                    <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($quote['created_at'])); ?></p>
                    <p><strong>Created by:</strong> <?php echo htmlspecialchars($quote['created_by_name']); ?></p>
                </div>
            </div>
            
            <!-- Status Update Section -->
            <div class="mt-6 pt-6 border-t">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Update Status</h3>
                <div class="space-y-2">
                    <?php if ($quote['status'] == 'draft'): ?>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="sent">
                        <button type="submit" class="w-full bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 transition text-sm">
                            <i class="fas fa-paper-plane mr-2"></i>Mark as Sent
                        </button>
                    </form>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="under_review">
                        <button type="submit" class="w-full bg-purple-600 text-white px-3 py-2 rounded-md hover:bg-purple-700 transition text-sm">
                            <i class="fas fa-eye mr-2"></i>Mark Under Review
                        </button>
                    </form>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="accepted">
                        <button type="submit" class="w-full bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition text-sm">
                            <i class="fas fa-check mr-2"></i>Approve Quote
                        </button>
                    </form>
                    <?php elseif ($quote['status'] == 'sent'): ?>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="under_review">
                        <button type="submit" class="w-full bg-purple-600 text-white px-3 py-2 rounded-md hover:bg-purple-700 transition text-sm">
                            <i class="fas fa-eye mr-2"></i>Mark Under Review
                        </button>
                    </form>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="accepted">
                        <button type="submit" class="w-full bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition text-sm">
                            <i class="fas fa-check mr-2"></i>Approve Quote
                        </button>
                    </form>
                    <?php elseif ($quote['status'] == 'under_review'): ?>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="accepted">
                        <button type="submit" class="w-full bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition text-sm">
                            <i class="fas fa-check mr-2"></i>Approve Quote
                        </button>
                    </form>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="rejected">
                        <button type="submit" class="w-full bg-red-600 text-white px-3 py-2 rounded-md hover:bg-red-700 transition text-sm">
                            <i class="fas fa-times mr-2"></i>Reject Quote
                        </button>
                    </form>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="draft">
                        <button type="submit" class="w-full bg-gray-600 text-white px-3 py-2 rounded-md hover:bg-gray-700 transition text-sm">
                            <i class="fas fa-undo mr-2"></i>Revert to Draft
                        </button>
                    </form>
                    <?php elseif ($quote['status'] == 'accepted' || $quote['status'] == 'approved'): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span class="text-green-800 font-medium">Quote <?php echo ucfirst($quote['status']); ?></span>
                        </div>
                        <p class="text-green-700 text-sm mt-1">This quote has been <?php echo $quote['status']; ?> and is ready for processing.</p>
                    </div>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="draft">
                        <button type="submit" class="w-full bg-gray-600 text-white px-3 py-2 rounded-md hover:bg-gray-700 transition text-sm">
                            <i class="fas fa-undo mr-2"></i>Revert to Draft
                        </button>
                    </form>
                    <?php elseif ($quote['status'] == 'rejected'): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-500 mr-2"></i>
                            <span class="text-red-800 font-medium">Quote Rejected</span>
                        </div>
                        <p class="text-red-700 text-sm mt-1">This quote has been rejected.</p>
                    </div>
                    <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block w-full">
                        <input type="hidden" name="new_status" value="draft">
                        <button type="submit" class="w-full bg-gray-600 text-white px-3 py-2 rounded-md hover:bg-gray-700 transition text-sm">
                            <i class="fas fa-undo mr-2"></i>Revert to Draft
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Custom Status Selector -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Change to Custom Status:</label>
                        <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="flex space-x-2">
                            <select name="new_status" class="flex-1 text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                                <option value="">Select Status</option>
                                <option value="draft" <?php echo $quote['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="sent" <?php echo $quote['status'] == 'sent' ? 'selected' : ''; ?>>Sent</option>
                                <option value="under_review" <?php echo $quote['status'] == 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                <option value="accepted" <?php echo $quote['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="approved" <?php echo $quote['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $quote['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="expired" <?php echo $quote['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded-md hover:bg-blue-700 transition text-sm">
                                Update
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Quote Item Modal -->
<div id="add-quote-item-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Item to Quotation</h3>
            
            <!-- Search Bar -->
            <div class="mb-4">
                <label for="quote-item-search" class="block text-sm font-medium text-gray-700 mb-2">Search Items</label>
                <input type="text" id="quote-item-search" placeholder="Search by brand, model, or category..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                       oninput="filterQuoteItems()">
            </div>
            
            <!-- Items Grid -->
            <div class="mb-4 max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                <?php if (!empty($inventory_items)): ?>
                <div id="quote-items-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                    <?php foreach ($inventory_items as $inv_item): ?>
                    <div class="quote-item-card border border-gray-200 rounded-lg p-3 hover:bg-blue-50 cursor-pointer transition" 
                         data-item-id="<?php echo $inv_item['id']; ?>"
                         data-brand="<?php echo strtolower($inv_item['brand']); ?>"
                         data-model="<?php echo strtolower($inv_item['model']); ?>"
                         data-category="<?php echo strtolower($inv_item['category_name'] ?? ''); ?>"
                         data-price="<?php echo $inv_item['selling_price']; ?>"
                         data-stock="<?php echo $inv_item['stock_quantity']; ?>"
                         onclick="selectQuoteItem(this)">
                        
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
                                <div class="text-xs text-blue-600">
                                    Stock: <?php echo $inv_item['stock_quantity']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div id="no-quote-items-message" class="hidden text-center py-8 text-gray-500">
                    <i class="fas fa-search text-2xl mb-2"></i>
                    <p>No items found matching your search.</p>
                </div>
            </div>
            
            <!-- Selected Item Form -->
            <form id="add-quote-item-form" method="POST" action="?action=add_to_quote&quote_id=<?php echo $quote['id']; ?>" class="hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h4 class="font-medium text-gray-900 mb-2">Selected Item:</h4>
                    <div id="selected-quote-item-display" class="text-sm text-gray-700"></div>
                </div>
                
                <input type="hidden" id="selected_quote_inventory_item_id" name="inventory_item_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="quote_quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" min="1" id="quote_quantity" name="quantity" required value="1"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                               oninput="updateQuoteTotalPreview()">
                    </div>
                    <div>
                        <label for="quote_discount_percentage" class="block text-sm font-medium text-gray-700 mb-2">Discount %</label>
                        <input type="number" min="0" max="100" step="0.01" id="quote_discount_percentage" name="discount_percentage" value="0"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                               oninput="updateQuoteTotalPreview()">
                    </div>
                </div>
                
                <div id="quote-total-preview" class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4 hidden">
                    <div class="flex justify-between text-sm">
                        <span>Subtotal:</span>
                        <span id="quote-subtotal-amount">₱0.00</span>
                    </div>
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount:</span>
                        <span id="quote-discount-amount">₱0.00</span>
                    </div>
                    <hr class="my-2">
                    <div class="flex justify-between font-medium">
                        <span>Total:</span>
                        <span id="quote-total-amount">₱0.00</span>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="clearQuoteSelection()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Clear Selection
                    </button>
                    <button type="submit" class="px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-800 transition">
                        Add to Quote
                    </button>
                </div>
            </form>
            
            <div class="flex justify-end mt-4">
                <button type="button" onclick="document.getElementById('add-quote-item-modal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedQuoteItemData = null;

function filterQuoteItems() {
    const searchTerm = document.getElementById('quote-item-search').value.toLowerCase();
    const itemCards = document.querySelectorAll('.quote-item-card');
    let visibleCount = 0;
    
    itemCards.forEach(card => {
        const brand = card.getAttribute('data-brand');
        const model = card.getAttribute('data-model');
        const category = card.getAttribute('data-category');
        
        const matchesSearch = brand.includes(searchTerm) || 
                             model.includes(searchTerm) || 
                             category.includes(searchTerm);
        
        if (matchesSearch) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    const noItemsMessage = document.getElementById('no-quote-items-message');
    if (visibleCount === 0) {
        noItemsMessage.classList.remove('hidden');
    } else {
        noItemsMessage.classList.add('hidden');
    }
}

function selectQuoteItem(cardElement) {
    // Remove previous selection
    document.querySelectorAll('.quote-item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Mark current selection
    cardElement.classList.add('bg-blue-100', 'border-blue-500');
    
    // Store selected item data
    selectedQuoteItemData = {
        id: cardElement.getAttribute('data-item-id'),
        brand: cardElement.querySelector('.text-sm.font-medium').textContent,
        model: cardElement.querySelector('.text-sm.text-gray-500').textContent,
        price: parseFloat(cardElement.getAttribute('data-price')),
        stock: parseInt(cardElement.getAttribute('data-stock'))
    };
    
    // Update form
    document.getElementById('selected_quote_inventory_item_id').value = selectedQuoteItemData.id;
    document.getElementById('selected-quote-item-display').innerHTML = 
        `<strong>${selectedQuoteItemData.brand}</strong> - ${selectedQuoteItemData.model}<br>
         Price: ${formatCurrency(selectedQuoteItemData.price)} | Available: ${selectedQuoteItemData.stock}`;
    
    // Show form and update preview
    document.getElementById('add-quote-item-form').classList.remove('hidden');
    updateQuoteTotalPreview();
}

function clearQuoteSelection() {
    // Clear visual selection
    document.querySelectorAll('.quote-item-card').forEach(card => {
        card.classList.remove('bg-blue-100', 'border-blue-500');
    });
    
    // Hide form
    document.getElementById('add-quote-item-form').classList.add('hidden');
    selectedQuoteItemData = null;
}

function updateQuoteTotalPreview() {
    if (!selectedQuoteItemData) return;
    
    const quantity = parseInt(document.getElementById('quote_quantity').value) || 0;
    const discountPercent = parseFloat(document.getElementById('quote_discount_percentage').value) || 0;
    
    const subtotal = selectedQuoteItemData.price * quantity;
    const discountAmount = subtotal * (discountPercent / 100);
    const total = subtotal - discountAmount;
    
    document.getElementById('quote-subtotal-amount').textContent = formatCurrency(subtotal);
    document.getElementById('quote-discount-amount').textContent = formatCurrency(discountAmount);
    document.getElementById('quote-total-amount').textContent = formatCurrency(total);
    
    document.getElementById('quote-total-preview').classList.remove('hidden');
}

function formatCurrency(amount) {
    return '₱' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
</script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
