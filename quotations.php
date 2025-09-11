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

// Handle AJAX request for profit data
if ($action === 'get_profit_data' && $quote_id) {
    header('Content-Type: application/json');
    
    try {
        $quote = getQuoteWithProfitData($quote_id);
        if ($quote) {
            echo json_encode([
                'success' => true,
                'profit_data' => $quote
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Quotation not found'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading profit data: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle AJAX request for customer details
if ($action === 'get_customer_details' && $quote_id) {
    header('Content-Type: application/json');
    
    try {
        // Get quote information
        $quote = getQuote($quote_id);
        
        // Get customer information
        $customer_info = getCustomerInfo($quote_id);
        
        // Get solar project details
        $solar_details = getSolarProjectDetails($quote_id);
        
        echo json_encode([
            'success' => true,
            'quote_info' => $quote,
            'customer_info' => $customer_info,
            'solar_details' => $solar_details
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading customer details: ' . $e->getMessage()
        ]);
    }
    exit();
}

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
                // Save customer information
                saveCustomerInfo($quote_id, $_POST);
                
                // Save solar project details
                saveSolarProjectDetails($quote_id, $_POST);
                
                // Check if KW and Labor Fee are provided
                $kw = floatval($_POST['kw'] ?? 0);
                $labor_fee = floatval($_POST['labor_fee'] ?? 0);
                
                if ($kw > 0 && $labor_fee > 0) {
                    $total_labor_cost = $kw * $labor_fee;
                    $labor_item_name = "Labor Fee Calculation";
                    
                    // Add the labor fee as a custom quote item
                    $labor_result = addCustomQuoteItem($quote_id, $labor_item_name, $kw, $labor_fee);
                    
                    if ($labor_result['success']) {
                        $message = 'New quotation created successfully with labor fee and additional details!';
                    } else {
                        $message = 'Quotation created with additional details, but failed to add labor fee: ' . $labor_result['message'];
                    }
                } else {
                    $message = 'New quotation created successfully with additional details!';
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
            
        case 'update_quote_discount':
            if (isset($_POST['quote_item_id']) && isset($_POST['new_discount_percentage'])) {
                $result = updateQuoteItemDiscount($_POST['quote_item_id'], $_POST['new_discount_percentage']);
                if ($result['success']) {
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Discount updated successfully!'));
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'update_customer_details':
            if ($quote_id) {
                $customer_updated = saveCustomerInfo($quote_id, $_POST);
                $solar_updated = saveSolarProjectDetails($quote_id, $_POST);
                
                if ($customer_updated && $solar_updated) {
                    header("Location: ?action=quote&quote_id=" . $quote_id . "&message=" . urlencode('Customer and solar project details updated successfully!'));
                    exit();
                } else {
                    $error = 'Failed to update customer or solar project details.';
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
        
    case 'order_fulfillment':
        if ($quote_id) {
            $quote = getQuote($quote_id);
            if (!$quote) {
                $error = 'Quotation not found.';
                $action = 'list';
            } else {
                // Get customer information for the order fulfillment
                $customer_info = getCustomerInfo($quote_id);
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

<style>
/* Compact action buttons layout */
.action-buttons {
    min-width: 140px;
}

.action-buttons .inline-block {
    margin: 0;
}

.action-buttons button,
.action-buttons a {
    display: inline-block;
    min-width: 18px;
    padding: 2px 4px;
    text-align: center;
    font-size: 12px;
}

/* Compact actions column */
.actions-column {
    width: 140px;
    min-width: 140px;
    padding: 8px 4px;
}

/* Make table more compact */
.compact-table td {
    padding: 8px 12px;
}

.compact-table th {
    padding: 8px 12px;
}
</style>

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
    <table class="min-w-full divide-y divide-gray-200 compact-table">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quote #</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposal</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider actions-column">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($quotes)): ?>
                <?php foreach ($quotes as $quote): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($quote['quote_number']); ?>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($quote['customer_name']); ?>
                        <?php if ($quote['customer_phone']): ?>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($quote['customer_phone']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($quote['proposal_name'] ?? '-'); ?>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $quote['items_count']; ?> items
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($quote['total_amount']); ?>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap">
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
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M j, Y', strtotime($quote['created_at'])); ?>
                    </td>
                    <td class="px-2 py-3 whitespace-nowrap text-center actions-column">
                        <div class="flex justify-center items-center space-x-0 flex-wrap action-buttons">
                            <a href="?action=quote&quote_id=<?php echo $quote['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 p-1 inline-block" title="View/Edit Quote">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            <button onclick="viewCustomerDetails(<?php echo $quote['id']; ?>)" 
                                    class="text-green-600 hover:text-green-900 p-1 inline-block" title="View Customer Details">
                                <i class="fas fa-user text-xs"></i>
                            </button>
                            <button onclick="editCustomerDetails(<?php echo $quote['id']; ?>)" 
                                    class="text-orange-600 hover:text-orange-900 p-1 inline-block" title="Edit Customer Details">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            
                            <!-- Status Update Buttons -->
                            <?php if ($quote['status'] == 'draft'): ?>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block">
                                <input type="hidden" name="new_status" value="sent">
                                <button type="submit" class="text-purple-600 hover:text-purple-900 p-1" title="Mark as Sent">
                                    <i class="fas fa-paper-plane text-xs"></i>
                                </button>
                            </form>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block">
                                <input type="hidden" name="new_status" value="accepted">
                                <button type="submit" class="text-green-600 hover:text-green-900 p-1" title="Approve Quote">
                                    <i class="fas fa-check text-xs"></i>
                                </button>
                            </form>
                            <?php elseif ($quote['status'] == 'sent'): ?>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block">
                                <input type="hidden" name="new_status" value="accepted">
                                <button type="submit" class="text-green-600 hover:text-green-900 p-1" title="Approve Quote">
                                    <i class="fas fa-check text-xs"></i>
                                </button>
                            </form>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block">
                                <input type="hidden" name="new_status" value="rejected">
                                <button type="submit" class="text-red-600 hover:text-red-900 p-1" title="Reject Quote">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </form>
                            <?php elseif ($quote['status'] == 'accepted' || $quote['status'] == 'approved'): ?>
                            <form method="POST" action="?action=update_quote_status&quote_id=<?php echo $quote['id']; ?>" class="inline-block">
                                <input type="hidden" name="new_status" value="draft">
                                <button type="submit" class="text-gray-600 hover:text-gray-900 p-1" title="Revert to Draft">
                                    <i class="fas fa-undo text-xs"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <a href="print_inventory_quote.php?id=<?php echo $quote['id']; ?>" target="_blank"
                               class="text-orange-600 hover:text-orange-900 p-1 inline-block" title="Print Quote">
                                <i class="fas fa-print text-xs"></i>
                            </a>
                            <a href="?action=delete_quote&quote_id=<?php echo $quote['id']; ?>" 
                               class="text-red-600 hover:text-red-900 p-1 inline-block"
                               onclick="return confirm('Are you sure you want to delete this quotation?')"
                               title="Delete Quote">
                                <i class="fas fa-trash text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
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
    <form method="POST" action="?action=create_quote" class="space-y-6">
        <!-- Basic Quotation Info -->
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
        
        <!-- Customer Information Section -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Customer Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter full name">
                </div>
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter phone number">
                </div>
                <div>
                    <label for="account_creation_date" class="block text-sm font-medium text-gray-700 mb-2">Account Creation Date</label>
                    <input type="date" id="account_creation_date" name="account_creation_date"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
            </div>
            
            <div class="mt-4">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                <textarea id="address" name="address" rows="2"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                          placeholder="Enter complete address"></textarea>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Contact Method</label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="contact_method[]" value="email" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                        <span class="ml-2 text-sm text-gray-700">Email</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="contact_method[]" value="phone" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                        <span class="ml-2 text-sm text-gray-700">Phone</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="contact_method[]" value="sms" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                        <span class="ml-2 text-sm text-gray-700">SMS</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Solar Project Details Section -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Solar Project Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">System Type</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="system_type[]" value="grid_tie" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Grid Tie</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="system_type[]" value="off_grid" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Off Grid</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="system_type[]" value="hybrid" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Hybrid</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label for="system_size" class="block text-sm font-medium text-gray-700 mb-2">System Size (kW)</label>
                    <input type="number" id="system_size" name="system_size" min="0" step="0.01"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter system size in kW">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Installation Type</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_type[]" value="rooftop" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Rooftop</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_type[]" value="ground_mounted" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Ground Mounted</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_type[]" value="carport" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Carport</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="panel_brand_model" class="block text-sm font-medium text-gray-700 mb-2">Panel Brand/Model</label>
                    <input type="text" id="panel_brand_model" name="panel_brand_model"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter panel brand and model">
                </div>
                <div>
                    <label for="inverter_brand_model" class="block text-sm font-medium text-gray-700 mb-2">Inverter Brand/Model</label>
                    <input type="text" id="inverter_brand_model" name="inverter_brand_model"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                           placeholder="Enter inverter brand and model">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                <div>
                    <label for="estimated_installation_date" class="block text-sm font-medium text-gray-700 mb-2">Estimated Installation Date</label>
                    <input type="date" id="estimated_installation_date" name="estimated_installation_date"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Installation Status</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_status[]" value="planned" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Planned</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_status[]" value="in_progress" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">In Progress</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_status[]" value="completed" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Completed</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="installation_status[]" value="maintenance" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                            <span class="ml-2 text-sm text-gray-700">Maintenance</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Battery Backup Capacity</label>
                        <div class="flex gap-4 mb-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="battery_backup_capacity" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue" onchange="toggleBatteryCapacityInput()">
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="battery_backup_capacity" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue" onchange="toggleBatteryCapacityInput()">
                                <span class="ml-2 text-sm text-gray-700">No</span>
                            </label>
                        </div>
                        <div id="battery_capacity_input" class="hidden">
                            <input type="text" name="battery_capacity_value" placeholder="Enter battery capacity (e.g., 10kWh, 5000Wh)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-solar-blue focus:border-transparent text-sm">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Net Metering</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="net_metering" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="net_metering" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">No</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmed</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="confirmed" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="confirmed" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">No</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="client_signature" class="block text-sm font-medium text-gray-700 mb-2">Client Signature Line</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center bg-gray-50">
                        <p class="text-sm text-gray-500 mb-2">Client Signature</p>
                        <div class="border-b-2 border-gray-400 w-full h-12"></div>
                        <input type="text" id="client_signature" name="client_signature" placeholder="Type signature or leave blank for manual signing"
                               class="w-full mt-2 border-0 bg-transparent text-center focus:ring-0 text-sm text-gray-600">
                    </div>
                </div>
                
                <div>
                    <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="4"
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                              placeholder="Enter any additional remarks or notes"></textarea>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end pt-6 border-t">
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
            <button onclick="editCustomerDetails(<?php echo $quote['id']; ?>)" 
                    class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition">
                <i class="fas fa-edit mr-2"></i>Edit Details
            </button>
            <a href="?action=order_fulfillment&quote_id=<?php echo $quote['id']; ?>" 
               class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                <i class="fas fa-clipboard-check mr-2"></i>Order Fulfillment
            </a>
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
                                <form method="POST" action="?action=update_quote_discount&quote_id=<?php echo $quote['id']; ?>" class="inline">
                                    <input type="hidden" name="quote_item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="new_discount_percentage" value="<?php echo $item['discount_percentage']; ?>" 
                                           min="0" max="100" step="0.01" class="w-16 px-2 py-1 border rounded text-center text-sm"
                                           onchange="this.form.submit()">
                                </form>
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
            
            <!-- View Profit Button -->
            <div class="mt-6 pt-6 border-t">
                <button onclick="showProfitModal()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition mb-2">
                    <i class="fas fa-chart-line mr-2"></i>View Profit Breakdown
                </button>
                <button onclick="editCustomerDetails(<?php echo $quote['id']; ?>)" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition mb-4">
                    <i class="fas fa-edit mr-2"></i>Edit Customer Details
                </button>
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

<!-- Profit Breakdown Modal -->
<div id="profit-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-6xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Profit Breakdown - <?php echo htmlspecialchars($quote['quote_number']); ?></h3>
                <button type="button" onclick="closeProfitModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="profit-content" class="mb-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                        <span class="text-blue-800">Loading profit data...</span>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeProfitModal()"
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

// Profit Modal Functions
function showProfitModal() {
    const modal = document.getElementById('profit-modal');
    modal.classList.remove('hidden');
    loadProfitData();
}

function closeProfitModal() {
    const modal = document.getElementById('profit-modal');
    modal.classList.add('hidden');
}

function loadProfitData() {
    const quote_id = <?php echo $quote['id']; ?>;
    const contentDiv = document.getElementById('profit-content');
    
    // Show loading state
    contentDiv.innerHTML = `
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                <span class="text-blue-800">Loading profit data...</span>
            </div>
        </div>
    `;
    
    // Make AJAX request to get profit data
    fetch(`quotations.php?action=get_profit_data&quote_id=${quote_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProfitData(data.profit_data);
            } else {
                showProfitError(data.message || 'Failed to load profit data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showProfitError('Network error occurred while loading profit data');
        });
}

function displayProfitData(profitData) {
    const contentDiv = document.getElementById('profit-content');
    
    let totalBaseCost = 0;
    let totalSellingPrice = 0;
    let totalProfit = 0;
    let totalProfitAfterDiscount = 0;
    
    let itemsHtml = '';
    
    profitData.items.forEach(item => {
        const baseCost = item.base_price * item.quantity;
        const sellingPrice = item.unit_price * item.quantity;
        const profit = sellingPrice - baseCost;
        const profitAfterDiscount = item.total_amount - baseCost;
        const profitMargin = baseCost > 0 ? ((profit / baseCost) * 100) : 0;
        const profitMarginAfterDiscount = baseCost > 0 ? ((profitAfterDiscount / baseCost) * 100) : 0;
        
        totalBaseCost += baseCost;
        totalSellingPrice += sellingPrice;
        totalProfit += profit;
        totalProfitAfterDiscount += profitAfterDiscount;
        
        itemsHtml += `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 border-b">
                    <div class="text-sm font-medium text-gray-900">${item.brand} ${item.model}</div>
                    <div class="text-xs text-gray-500">${item.size_specification || ''}</div>
                </td>
                <td class="px-3 py-3 border-b text-center text-sm">${item.quantity}</td>
                <td class="px-3 py-3 border-b text-sm text-right">${formatCurrency(item.base_price)}</td>
                <td class="px-3 py-3 border-b text-sm text-right">${formatCurrency(item.unit_price)}</td>
                <td class="px-3 py-3 border-b text-sm text-right">${formatCurrency(baseCost)}</td>
                <td class="px-3 py-3 border-b text-sm text-right">${formatCurrency(sellingPrice)}</td>
                <td class="px-3 py-3 border-b text-sm text-right font-medium ${profit >= 0 ? 'text-green-600' : 'text-red-600'}">
                    ${formatCurrency(profit)}
                    <div class="text-xs ${profitMargin >= 0 ? 'text-green-500' : 'text-red-500'}">(${profitMargin.toFixed(1)}%)</div>
                </td>
                <td class="px-3 py-3 border-b text-sm text-center">
                    <span class="text-xs ${item.discount_percentage > 0 ? 'text-orange-600' : 'text-gray-400'}">${item.discount_percentage}%</span>
                </td>
                <td class="px-3 py-3 border-b text-sm text-right font-medium ${profitAfterDiscount >= 0 ? 'text-green-600' : 'text-red-600'}">
                    ${formatCurrency(profitAfterDiscount)}
                    <div class="text-xs ${profitMarginAfterDiscount >= 0 ? 'text-green-500' : 'text-red-500'}">(${profitMarginAfterDiscount.toFixed(1)}%)</div>
                </td>
            </tr>
        `;
    });
    
    const overallProfitMargin = totalBaseCost > 0 ? ((totalProfit / totalBaseCost) * 100) : 0;
    const overallProfitMarginAfterDiscount = totalBaseCost > 0 ? ((totalProfitAfterDiscount / totalBaseCost) * 100) : 0;
    
    contentDiv.innerHTML = `
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-sm font-medium text-blue-800">Total Base Cost</div>
                <div class="text-xl font-bold text-blue-900">${formatCurrency(totalBaseCost)}</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-sm font-medium text-green-800">Total Selling Price</div>
                <div class="text-xl font-bold text-green-900">${formatCurrency(totalSellingPrice)}</div>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="text-sm font-medium text-purple-800">Gross Profit</div>
                <div class="text-xl font-bold ${totalProfit >= 0 ? 'text-purple-900' : 'text-red-600'}">${formatCurrency(totalProfit)}</div>
                <div class="text-sm ${overallProfitMargin >= 0 ? 'text-purple-600' : 'text-red-500'}">(${overallProfitMargin.toFixed(1)}% margin)</div>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="text-sm font-medium text-orange-800">Net Profit (After Discounts)</div>
                <div class="text-xl font-bold ${totalProfitAfterDiscount >= 0 ? 'text-orange-900' : 'text-red-600'}">${formatCurrency(totalProfitAfterDiscount)}</div>
                <div class="text-sm ${overallProfitMarginAfterDiscount >= 0 ? 'text-orange-600' : 'text-red-500'}">(${overallProfitMarginAfterDiscount.toFixed(1)}% margin)</div>
            </div>
        </div>
        
        <!-- Detailed Item Breakdown -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h4 class="text-sm font-medium text-gray-900">Item-wise Profit Breakdown</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Base Price</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Selling Price</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Base Cost</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Selling</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross Profit</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Discount</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        ${itemsHtml}
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr class="font-medium">
                            <td class="px-4 py-3 text-sm font-bold text-gray-900" colspan="4">TOTAL</td>
                            <td class="px-3 py-3 text-sm font-bold text-gray-900 text-right">${formatCurrency(totalBaseCost)}</td>
                            <td class="px-3 py-3 text-sm font-bold text-gray-900 text-right">${formatCurrency(totalSellingPrice)}</td>
                            <td class="px-3 py-3 text-sm font-bold ${totalProfit >= 0 ? 'text-green-600' : 'text-red-600'} text-right">
                                ${formatCurrency(totalProfit)}
                                <div class="text-xs ${overallProfitMargin >= 0 ? 'text-green-500' : 'text-red-500'}">(${overallProfitMargin.toFixed(1)}%)</div>
                            </td>
                            <td class="px-3 py-3 text-sm text-center">-</td>
                            <td class="px-3 py-3 text-sm font-bold ${totalProfitAfterDiscount >= 0 ? 'text-orange-600' : 'text-red-600'} text-right">
                                ${formatCurrency(totalProfitAfterDiscount)}
                                <div class="text-xs ${overallProfitMarginAfterDiscount >= 0 ? 'text-orange-500' : 'text-red-500'}">(${overallProfitMarginAfterDiscount.toFixed(1)}%)</div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex">
                <i class="fas fa-info-circle text-yellow-600 mr-2 mt-0.5"></i>
                <div class="text-sm text-yellow-800">
                    <strong>Note:</strong> Gross Profit = Selling Price - Base Price. Net Profit accounts for discounts applied to individual items.
                </div>
            </div>
        </div>
    `;
}

function showProfitError(message) {
    const contentDiv = document.getElementById('profit-content');
    contentDiv.innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                <span class="text-red-800">${message}</span>
            </div>
        </div>
    `;
}
</script>

<?php elseif ($action == 'order_fulfillment' && isset($quote)): ?>
<!-- Order Fulfillment Checklist Screen -->
<div class="mb-6 order-fulfillment-page-header">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Order Fulfillment Checklist</h1>
            <p class="text-gray-600">Quotation: <?php echo htmlspecialchars($quote['quote_number']); ?></p>
        </div>
        <div class="space-x-2">
            <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-print mr-2"></i>Print Checklist
            </button>
            <a href="?action=quote&quote_id=<?php echo $quote['id']; ?>" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Quote
            </a>
        </div>
    </div>
</div>

<!-- Order Fulfillment Form -->
<div id="order-fulfillment-form-container" class="bg-white rounded-lg shadow-lg p-8 print:shadow-none print:p-0">
    <form id="fulfillment-form" method="POST" action="">
        <!-- Header Section -->
        <div class="text-center mb-8 border-b-2 border-gray-300 pb-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Order Fulfillment Checklist</h2>
            <p class="text-gray-600">Date: <?php echo date('Y-m-d'); ?></p>
        </div>

        <!-- Customer Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quotation Code:</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($quote['quote_number']); ?></p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name:</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($quote['customer_name']); ?></p>
                </div>
            </div>
            <div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location:</label>
                    <p class="text-gray-900"><?php echo $customer_info && $customer_info['address'] ? htmlspecialchars($customer_info['address']) : 'Not specified'; ?></p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number:</label>
                    <p class="text-gray-900"><?php echo $quote['customer_phone'] ? htmlspecialchars($quote['customer_phone']) : ($customer_info && $customer_info['phone_number'] ? htmlspecialchars($customer_info['phone_number']) : 'Not specified'); ?></p>
                </div>
            </div>
        </div>

        <!-- Items Checklist Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            No.
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Description
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Check
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Quantity
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-300">
                            Unit Amount
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Amount
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($quote['items'])): ?>
                        <?php $item_no = 1; ?>
                        <?php foreach ($quote['items'] as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r border-gray-300">
                                <?php echo $item_no++; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-300">
                                <div class="font-medium"><?php echo htmlspecialchars($item['brand'] ?? 'Custom Item'); ?></div>
                                <div class="text-gray-500"><?php echo htmlspecialchars($item['model'] ?? $item['custom_item_name'] ?? ''); ?></div>
                                <?php if (!empty($item['category'])): ?>
                                <div class="text-xs text-blue-600 mt-1"><?php echo htmlspecialchars($item['category']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center border-r border-gray-300">
                                <input type="checkbox" name="item_checked[]" value="<?php echo isset($item['quote_item_id']) ? $item['quote_item_id'] : $item['id']; ?>" class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2 print:hidden">
                                <span class="hidden print:inline-block w-5 h-5 border-2 border-gray-400"></span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r border-gray-300">
                                <?php echo number_format($item['quantity'], 0); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-gray-900 border-r border-gray-300">
                                <?php echo formatCurrency($item['unit_price']); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                                <?php echo formatCurrency($item['total_amount']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Total Row -->
                        <tr class="bg-gray-50 font-semibold border-t-2 border-gray-400">
                            <td colspan="5" class="px-4 py-4 text-right text-sm text-gray-900 border-r border-gray-300">
                                <strong>Grand Total:</strong>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-lg font-bold text-green-600">
                                <?php echo formatCurrency($quote['total_amount']); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No items found in this quotation.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer Section -->
        <div class="mt-8 pt-6 border-t-2 border-gray-300">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Notes:</h4>
                    <div class="space-y-2">
                        <div class="flex items-start">
                            <input type="checkbox" class="mt-1 mr-2 print:hidden">
                            <span class="hidden print:inline-block w-4 h-4 border border-gray-400 mr-2 mt-1"></span>
                            <span class="text-sm text-gray-600">All items checked and verified</span>
                        </div>
                        <div class="flex items-start">
                            <input type="checkbox" class="mt-1 mr-2 print:hidden">
                            <span class="hidden print:inline-block w-4 h-4 border border-gray-400 mr-2 mt-1"></span>
                            <span class="text-sm text-gray-600">Customer signature obtained</span>
                        </div>
                        <div class="flex items-start">
                            <input type="checkbox" class="mt-1 mr-2 print:hidden">
                            <span class="hidden print:inline-block w-4 h-4 border border-gray-400 mr-2 mt-1"></span>
                            <span class="text-sm text-gray-600">Delivery completed</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Signatures:</h4>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs text-gray-600 mb-2">Prepared By:</label>
                            <div class="border-b-2 border-gray-300 h-12"></div>
                            <p class="text-xs text-gray-500 mt-1">Name & Signature</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-2">Customer Signature:</label>
                            <div class="border-b-2 border-gray-300 h-12"></div>
                            <p class="text-xs text-gray-500 mt-1">Name & Signature</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons (Hidden when printing) -->
        <div class="mt-8 pt-6 border-t flex justify-between print:hidden">
            <button type="button" onclick="checkAllItems()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-check-double mr-2"></i>Check All Items
            </button>
            <div class="space-x-2">
                <button type="button" onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-print mr-2"></i>Print Checklist
                </button>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-save mr-2"></i>Save Progress
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function checkAllItems() {
    const checkboxes = document.querySelectorAll('input[name="item_checked[]"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

// Auto-save functionality
document.getElementById('fulfillment-form').addEventListener('change', function() {
    // Could implement auto-save functionality here
    console.log('Form changed - could auto-save progress');
});
</script>

<style>
@media print {
    /* Hide everything on the page first */
    * {
        visibility: hidden;
    }
    
    /* Show only the order fulfillment form container and its children */
    #order-fulfillment-form-container,
    #order-fulfillment-form-container * {
        visibility: visible;
    }
    
    /* Position the form container at the top-left */
    #order-fulfillment-form-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: auto;
        margin: 0;
        padding: 20px;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: white;
    }
    
    /* Hide print-specific elements */
    .print\:hidden {
        display: none !important;
    }
    .print\:inline-block {
        display: inline-block !important;
    }
    
    /* Reset body styles for printing */
    body {
        font-size: 12px;
        margin: 0;
        padding: 0;
        background: white;
        color: black;
    }
    
    /* Optimize table printing */
    table {
        page-break-inside: auto;
        width: 100%;
        border-collapse: collapse;
    }
    
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    th, td {
        border: 1px solid #000 !important;
        padding: 8px !important;
    }
    
    /* Ensure text is black for printing */
    h1, h2, h3, h4, h5, h6, p, span, div, td, th {
        color: black !important;
    }
    
    /* Hide action buttons and interactive elements */
    button, .print\:hidden {
        display: none !important;
    }
}
</style>

<?php endif; ?>

<!-- Customer Details Modal -->
<div id="customer-details-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Customer & Solar Project Details</h3>
                <button type="button" onclick="closeCustomerDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="customer-details-content" class="mb-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                        <span class="text-blue-800">Loading customer details...</span>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="button" onclick="closeCustomerDetailsModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Details Modal -->
<div id="edit-customer-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-5 mx-auto p-5 border w-full max-w-6xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Customer & Solar Project Details</h3>
                <button type="button" onclick="closeEditCustomerModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="edit-customer-form" method="POST" class="space-y-6">
                <input type="hidden" id="edit_quote_id" name="quote_id" value="">
                
                <!-- Customer Information Section -->
                <div class="border-b pb-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Customer Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="edit_full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" id="edit_full_name" name="full_name"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter full name">
                        </div>
                        <div>
                            <label for="edit_phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="edit_phone_number" name="phone_number"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter phone number">
                        </div>
                        <div>
                            <label for="edit_account_creation_date" class="block text-sm font-medium text-gray-700 mb-2">Account Creation Date</label>
                            <input type="date" id="edit_account_creation_date" name="account_creation_date"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label for="edit_address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea id="edit_address" name="address" rows="2"
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                  placeholder="Enter complete address"></textarea>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Contact Method</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="edit_contact_email" name="contact_method[]" value="email" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">Email</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="edit_contact_phone" name="contact_method[]" value="phone" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">Phone</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" id="edit_contact_sms" name="contact_method[]" value="sms" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                <span class="ml-2 text-sm text-gray-700">SMS</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Solar Project Details Section -->
                <div class="pb-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Solar Project Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">System Type</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_system_grid_tie" name="system_type[]" value="grid_tie" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Grid Tie</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_system_off_grid" name="system_type[]" value="off_grid" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Off Grid</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_system_hybrid" name="system_type[]" value="hybrid" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Hybrid</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label for="edit_system_size" class="block text-sm font-medium text-gray-700 mb-2">System Size (kW)</label>
                            <input type="number" id="edit_system_size" name="system_size" min="0" step="0.01"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter system size in kW">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Installation Type</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_install_rooftop" name="installation_type[]" value="rooftop" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Rooftop</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_install_ground" name="installation_type[]" value="ground_mounted" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Ground Mounted</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_install_carport" name="installation_type[]" value="carport" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Carport</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="edit_panel_brand_model" class="block text-sm font-medium text-gray-700 mb-2">Panel Brand/Model</label>
                            <input type="text" id="edit_panel_brand_model" name="panel_brand_model"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter panel brand and model">
                        </div>
                        <div>
                            <label for="edit_inverter_brand_model" class="block text-sm font-medium text-gray-700 mb-2">Inverter Brand/Model</label>
                            <input type="text" id="edit_inverter_brand_model" name="inverter_brand_model"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Enter inverter brand and model">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label for="edit_estimated_installation_date" class="block text-sm font-medium text-gray-700 mb-2">Estimated Installation Date</label>
                            <input type="date" id="edit_estimated_installation_date" name="estimated_installation_date"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Installation Status</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_status_planned" name="installation_status[]" value="planned" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Planned</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_status_progress" name="installation_status[]" value="in_progress" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">In Progress</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_status_completed" name="installation_status[]" value="completed" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Completed</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="edit_status_maintenance" name="installation_status[]" value="maintenance" class="rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                                    <span class="ml-2 text-sm text-gray-700">Maintenance</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Battery Backup Capacity</label>
                                <div class="flex gap-4 mb-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_battery_backup_capacity_yes" name="battery_backup_capacity" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue" onchange="toggleEditBatteryCapacityInput()">
                                        <span class="ml-2 text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_battery_backup_capacity_no" name="battery_backup_capacity" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue" onchange="toggleEditBatteryCapacityInput()">
                                        <span class="ml-2 text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                                <div id="edit_battery_capacity_input" class="hidden">
                                    <input type="text" id="edit_battery_capacity_value" name="battery_capacity_value" placeholder="Enter battery capacity (e.g., 10kWh, 5000Wh)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-solar-blue focus:border-transparent text-sm">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Net Metering</label>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_net_metering_yes" name="net_metering" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                        <span class="ml-2 text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_net_metering_no" name="net_metering" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                        <span class="ml-2 text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirmed</label>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_confirmed_yes" name="confirmed" value="yes" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                        <span class="ml-2 text-sm text-gray-700">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" id="edit_confirmed_no" name="confirmed" value="no" class="border-gray-300 text-solar-blue focus:ring-solar-blue">
                                        <span class="ml-2 text-sm text-gray-700">No</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="edit_client_signature" class="block text-sm font-medium text-gray-700 mb-2">Client Signature</label>
                            <input type="text" id="edit_client_signature" name="client_signature"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                   placeholder="Type signature or leave blank for manual signing">
                        </div>
                        
                        <div>
                            <label for="edit_remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                            <textarea id="edit_remarks" name="remarks" rows="4"
                                      class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent"
                                      placeholder="Enter any additional remarks or notes"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <button type="button" onclick="closeEditCustomerModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>Update Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Customer Details Modal Functions
function viewCustomerDetails(quoteId) {
    const modal = document.getElementById('customer-details-modal');
    modal.classList.remove('hidden');
    loadCustomerDetails(quoteId);
}

function closeCustomerDetailsModal() {
    const modal = document.getElementById('customer-details-modal');
    modal.classList.add('hidden');
}

function loadCustomerDetails(quoteId) {
    const contentDiv = document.getElementById('customer-details-content');
    
    // Show loading state
    contentDiv.innerHTML = `
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-blue-600 mr-2"></i>
                <span class="text-blue-800">Loading customer details...</span>
            </div>
        </div>
    `;
    
    // Make AJAX request to get customer details
    fetch(`quotations.php?action=get_customer_details&quote_id=${quoteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCustomerDetails(data.customer_info, data.solar_details, data.quote_info);
            } else {
                showCustomerDetailsError(data.message || 'Failed to load customer details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCustomerDetailsError('Network error occurred while loading customer details');
        });
}

function displayCustomerDetails(customerInfo, solarDetails, quoteInfo) {
    const contentDiv = document.getElementById('customer-details-content');
    
    // Helper function to display boolean as Yes/No
    const boolToYesNo = (value) => value ? 'Yes' : 'No';
    
    // Helper function to display checkbox arrays
    const displayCheckboxes = (obj, prefix) => {
        const values = [];
        for (const key in obj) {
            if (key.startsWith(prefix) && obj[key]) {
                values.push(key.replace(prefix, '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
            }
        }
        return values.length > 0 ? values.join(', ') : 'None selected';
    };
    
    contentDiv.innerHTML = `
        <!-- Quote Information -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-3">Quote Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div><strong>Quote Number:</strong> ${quoteInfo?.quote_number || 'N/A'}</div>
                <div><strong>Customer Name:</strong> ${quoteInfo?.customer_name || 'N/A'}</div>
                <div><strong>Proposal:</strong> ${quoteInfo?.proposal_name || 'N/A'}</div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
            <h4 class="text-lg font-semibold text-gray-800 mb-3">Customer Information</h4>
            ${customerInfo ? `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><strong>Full Name:</strong> ${customerInfo.full_name || 'Not provided'}</div>
                    <div><strong>Phone Number:</strong> ${customerInfo.phone_number || 'Not provided'}</div>
                    <div><strong>Account Creation Date:</strong> ${customerInfo.account_creation_date || 'Not provided'}</div>
                    <div class="md:col-span-2"><strong>Address:</strong> ${customerInfo.address || 'Not provided'}</div>
                    <div class="md:col-span-2">
                        <strong>Preferred Contact Methods:</strong> 
                        ${[
                            customerInfo.contact_method_email ? 'Email' : null,
                            customerInfo.contact_method_phone ? 'Phone' : null,
                            customerInfo.contact_method_sms ? 'SMS' : null
                        ].filter(Boolean).join(', ') || 'None selected'}
                    </div>
                </div>
            ` : `
                <p class="text-gray-500 italic">No customer information provided for this quote.</p>
            `}
        </div>
        
        <!-- Solar Project Details -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="text-lg font-semibold text-gray-800 mb-3">Solar Project Details</h4>
            ${solarDetails ? `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <div class="mb-3">
                            <strong>System Type:</strong><br>
                            <span class="text-gray-600">
                                ${[
                                    solarDetails.system_type_grid_tie ? 'Grid Tie' : null,
                                    solarDetails.system_type_off_grid ? 'Off Grid' : null,
                                    solarDetails.system_type_hybrid ? 'Hybrid' : null
                                ].filter(Boolean).join(', ') || 'Not specified'}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>System Size:</strong> ${solarDetails.system_size_kw ? solarDetails.system_size_kw + ' kW' : 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Installation Type:</strong><br>
                            <span class="text-gray-600">
                                ${[
                                    solarDetails.installation_type_rooftop ? 'Rooftop' : null,
                                    solarDetails.installation_type_ground_mounted ? 'Ground Mounted' : null,
                                    solarDetails.installation_type_carport ? 'Carport' : null
                                ].filter(Boolean).join(', ') || 'Not specified'}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Panel Brand/Model:</strong> ${solarDetails.panel_brand_model || 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Inverter Brand/Model:</strong> ${solarDetails.inverter_brand_model || 'Not specified'}
                        </div>
                    </div>
                    
                    <div>
                        <div class="mb-3">
                            <strong>Estimated Installation Date:</strong> ${solarDetails.estimated_installation_date || 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Installation Status:</strong><br>
                            <span class="text-gray-600">
                                ${[
                                    solarDetails.installation_status_planned ? 'Planned' : null,
                                    solarDetails.installation_status_in_progress ? 'In Progress' : null,
                                    solarDetails.installation_status_completed ? 'Completed' : null,
                                    solarDetails.installation_status_maintenance ? 'Maintenance' : null
                                ].filter(Boolean).join(', ') || 'Not specified'}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Battery Backup Capacity:</strong> ${solarDetails.battery_backup_capacity ? solarDetails.battery_backup_capacity.charAt(0).toUpperCase() + solarDetails.battery_backup_capacity.slice(1) : 'Not specified'}${solarDetails.battery_backup_capacity === 'yes' && solarDetails.battery_capacity_value ? ' - ' + solarDetails.battery_capacity_value : ''}
                        </div>
                        <div class="mb-3">
                            <strong>Net Metering:</strong> ${solarDetails.net_metering ? solarDetails.net_metering.charAt(0).toUpperCase() + solarDetails.net_metering.slice(1) : 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Confirmed:</strong> ${solarDetails.confirmed ? solarDetails.confirmed.charAt(0).toUpperCase() + solarDetails.confirmed.slice(1) : 'Not specified'}
                        </div>
                        <div class="mb-3">
                            <strong>Client Signature:</strong> ${solarDetails.client_signature || 'Not provided'}
                        </div>
                    </div>
                </div>
                
                ${solarDetails.remarks ? `
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <strong>Remarks:</strong><br>
                        <p class="text-gray-600 mt-1 whitespace-pre-line">${solarDetails.remarks}</p>
                    </div>
                ` : ''}
            ` : `
                <p class="text-gray-500 italic">No solar project details provided for this quote.</p>
            `}
        </div>
    `;
}

function showCustomerDetailsError(message) {
    const contentDiv = document.getElementById('customer-details-content');
    contentDiv.innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                <span class="text-red-800">${message}</span>
            </div>
        </div>
    `;
}

// Edit Customer Details Modal Functions
function editCustomerDetails(quoteId) {
    const modal = document.getElementById('edit-customer-modal');
    modal.classList.remove('hidden');
    loadCustomerDetailsForEdit(quoteId);
}

function closeEditCustomerModal() {
    const modal = document.getElementById('edit-customer-modal');
    modal.classList.add('hidden');
}

function loadCustomerDetailsForEdit(quoteId) {
    // Set the quote ID in the form
    document.getElementById('edit_quote_id').value = quoteId;
    
    // Set form action
    const form = document.getElementById('edit-customer-form');
    form.action = `?action=update_customer_details&quote_id=${quoteId}`;
    
    // Load existing data
    fetch(`quotations.php?action=get_customer_details&quote_id=${quoteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.customer_info, data.solar_details);
            } else {
                alert('Failed to load customer details for editing');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred while loading customer details');
        });
}

function populateEditForm(customerInfo, solarDetails) {
    // Populate customer information
    if (customerInfo) {
        document.getElementById('edit_full_name').value = customerInfo.full_name || '';
        document.getElementById('edit_phone_number').value = customerInfo.phone_number || '';
        document.getElementById('edit_account_creation_date').value = customerInfo.account_creation_date || '';
        document.getElementById('edit_address').value = customerInfo.address || '';
        
        // Contact methods
        document.getElementById('edit_contact_email').checked = customerInfo.contact_method_email == 1;
        document.getElementById('edit_contact_phone').checked = customerInfo.contact_method_phone == 1;
        document.getElementById('edit_contact_sms').checked = customerInfo.contact_method_sms == 1;
    }
    
    // Populate solar project details
    if (solarDetails) {
        document.getElementById('edit_system_size').value = solarDetails.system_size_kw || '';
        document.getElementById('edit_panel_brand_model').value = solarDetails.panel_brand_model || '';
        document.getElementById('edit_inverter_brand_model').value = solarDetails.inverter_brand_model || '';
        document.getElementById('edit_estimated_installation_date').value = solarDetails.estimated_installation_date || '';
        document.getElementById('edit_client_signature').value = solarDetails.client_signature || '';
        document.getElementById('edit_remarks').value = solarDetails.remarks || '';
        
        // System types
        document.getElementById('edit_system_grid_tie').checked = solarDetails.system_type_grid_tie == 1;
        document.getElementById('edit_system_off_grid').checked = solarDetails.system_type_off_grid == 1;
        document.getElementById('edit_system_hybrid').checked = solarDetails.system_type_hybrid == 1;
        
        // Installation types
        document.getElementById('edit_install_rooftop').checked = solarDetails.installation_type_rooftop == 1;
        document.getElementById('edit_install_ground').checked = solarDetails.installation_type_ground_mounted == 1;
        document.getElementById('edit_install_carport').checked = solarDetails.installation_type_carport == 1;
        
        // Installation statuses
        document.getElementById('edit_status_planned').checked = solarDetails.installation_status_planned == 1;
        document.getElementById('edit_status_progress').checked = solarDetails.installation_status_in_progress == 1;
        document.getElementById('edit_status_completed').checked = solarDetails.installation_status_completed == 1;
        document.getElementById('edit_status_maintenance').checked = solarDetails.installation_status_maintenance == 1;
        
        // Battery backup capacity radio buttons
        if (solarDetails.battery_backup_capacity === 'yes') {
            document.getElementById('edit_battery_backup_capacity_yes').checked = true;
        } else if (solarDetails.battery_backup_capacity === 'no') {
            document.getElementById('edit_battery_backup_capacity_no').checked = true;
        }
        
        // Net metering and confirmed radio buttons
        if (solarDetails.net_metering === 'yes') {
            document.getElementById('edit_net_metering_yes').checked = true;
        } else if (solarDetails.net_metering === 'no') {
            document.getElementById('edit_net_metering_no').checked = true;
        }
        
        if (solarDetails.confirmed === 'yes') {
            document.getElementById('edit_confirmed_yes').checked = true;
        } else if (solarDetails.confirmed === 'no') {
            document.getElementById('edit_confirmed_no').checked = true;
        }
        
        // Populate battery capacity value if exists
        if (solarDetails.battery_capacity_value) {
            document.getElementById('edit_battery_capacity_value').value = solarDetails.battery_capacity_value;
        }
        
        // Show/hide battery capacity input based on selection
        toggleEditBatteryCapacityInput();
    }
}

// Battery Backup Capacity Input Toggle Functions
function toggleBatteryCapacityInput() {
    const yesRadio = document.querySelector('input[name="battery_backup_capacity"][value="yes"]');
    const inputDiv = document.getElementById('battery_capacity_input');
    
    if (yesRadio && yesRadio.checked) {
        inputDiv.classList.remove('hidden');
    } else {
        inputDiv.classList.add('hidden');
    }
}

function toggleEditBatteryCapacityInput() {
    const yesRadio = document.getElementById('edit_battery_backup_capacity_yes');
    const inputDiv = document.getElementById('edit_battery_capacity_input');
    
    if (yesRadio && yesRadio.checked) {
        inputDiv.classList.remove('hidden');
    } else {
        inputDiv.classList.add('hidden');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
