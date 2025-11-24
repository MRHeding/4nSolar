<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$invoice_id = $_GET['invoice_id'] ?? null;
$quotation_id = $_GET['quotation_id'] ?? null;

// Handle delete action (GET request)
if ($action === 'delete' && $invoice_id) {
    if (deleteInvoice($invoice_id)) {
        header("Location: invoices.php?message=Invoice deleted successfully");
        exit();
    } else {
        header("Location: invoices.php?error=Failed to delete invoice");
        exit();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        // Convert empty quotation_id to null to avoid foreign key constraint violation
        $quotation_id = !empty($_POST['quotation_id']) ? intval($_POST['quotation_id']) : null;
        
        // Auto-generate P.O. number if not provided
        $po_number = !empty($_POST['po_number']) ? trim($_POST['po_number']) : generatePONumber();
        
        $invoice_data = [
            'quotation_id' => $quotation_id,
            'invoice_date' => $_POST['invoice_date'],
            'due_date' => $_POST['due_date'],
            'po_number' => $po_number,
            'bill_to_name' => trim($_POST['bill_to_name']),
            'bill_to_address' => !empty($_POST['bill_to_address']) ? trim($_POST['bill_to_address']) : null,
            'ship_to_name' => !empty($_POST['ship_to_name']) ? trim($_POST['ship_to_name']) : trim($_POST['bill_to_name']),
            'ship_to_address' => !empty($_POST['ship_to_address']) ? trim($_POST['ship_to_address']) : (!empty($_POST['bill_to_address']) ? trim($_POST['bill_to_address']) : null),
            'tax_rate' => 0, // Always set tax rate to 0% for new invoices
            'terms_conditions' => !empty($_POST['terms_conditions']) ? trim($_POST['terms_conditions']) : null,
            'bank_name' => !empty($_POST['bank_name']) ? trim($_POST['bank_name']) : null,
            'bank_account_number' => !empty($_POST['bank_account_number']) ? trim($_POST['bank_account_number']) : null,
            'bank_routing' => !empty($_POST['bank_routing']) ? trim($_POST['bank_routing']) : null,
            'status' => $_POST['status'] ?? 'draft',
            'notes' => !empty($_POST['notes']) ? trim($_POST['notes']) : null
        ];
        
        $invoice_result = createInvoice($invoice_data);
        
        if (is_array($invoice_result) && isset($invoice_result['success']) && $invoice_result['success']) {
            $new_invoice_id = $invoice_result['invoice_id'];
            $items_added = false;
            
            // Add manually entered items
            if (!empty($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    // Clean up the item data
                    $item['description'] = trim($item['description'] ?? '');
                    $item['quantity'] = isset($item['quantity']) ? floatval($item['quantity']) : 0;
                    $item['unit_price'] = isset($item['unit_price']) ? floatval($item['unit_price']) : 0;
                    $item['inventory_item_id'] = isset($item['inventory_item_id']) && !empty(trim($item['inventory_item_id'])) ? intval($item['inventory_item_id']) : null;
                    
                    if (!empty($item['description']) && $item['quantity'] > 0 && $item['unit_price'] >= 0) {
                        if (addInvoiceItem($new_invoice_id, [
                            'inventory_item_id' => $item['inventory_item_id'],
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price']
                        ])) {
                            $items_added = true;
                        }
                    }
                }
            }
            
            // If no items were added, that's okay - invoice can be created without items initially
            header("Location: invoices.php?action=edit&invoice_id={$new_invoice_id}&message=Invoice created successfully");
            exit();
        } else {
            // Get the actual error message
            $error_msg = 'Failed to create invoice.';
            if (is_array($invoice_result) && isset($invoice_result['error'])) {
                $error_msg .= ' ' . htmlspecialchars($invoice_result['error']);
            } elseif (is_string($invoice_result)) {
                // Legacy return format (shouldn't happen but handle it)
                $error_msg .= ' ' . htmlspecialchars($invoice_result);
            } else {
                $error_msg .= ' Please check that all required fields are filled and try again.';
            }
            $error = $error_msg;
            
            // Debug: Log the full result
            error_log("Invoice creation result: " . print_r($invoice_result, true));
            error_log("POST data: " . print_r($_POST, true));
        }
    } elseif ($action === 'edit' && $invoice_id) {
        $invoice_data = [
            'invoice_date' => $_POST['invoice_date'],
            'due_date' => $_POST['due_date'],
            'po_number' => $_POST['po_number'] ?? null,
            'bill_to_name' => $_POST['bill_to_name'],
            'bill_to_address' => $_POST['bill_to_address'] ?? null,
            'ship_to_name' => $_POST['ship_to_name'] ?? $_POST['bill_to_name'],
            'ship_to_address' => $_POST['ship_to_address'] ?? $_POST['bill_to_address'],
            'tax_rate' => floatval($_POST['tax_rate'] ?? 0),
            'terms_conditions' => $_POST['terms_conditions'] ?? null,
            'bank_name' => $_POST['bank_name'] ?? null,
            'bank_account_number' => $_POST['bank_account_number'] ?? null,
            'bank_routing' => $_POST['bank_routing'] ?? null,
            'status' => $_POST['status'] ?? 'draft',
            'notes' => $_POST['notes'] ?? null
        ];
        
        if (updateInvoice($invoice_id, $invoice_data)) {
            if (!empty($_POST['items'])) {
                $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
                $stmt->execute([$invoice_id]);
                
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['description']) && $item['quantity'] > 0) {
                        addInvoiceItem($invoice_id, [
                            'inventory_item_id' => $item['inventory_item_id'] ?? null,
                            'description' => $item['description'],
                            'quantity' => floatval($item['quantity']),
                            'unit_price' => floatval($item['unit_price'])
                        ]);
                    }
                }
            }
            
            $message = 'Invoice updated successfully';
        } else {
            $error = 'Failed to update invoice';
        }
    }
}

// Get invoice data if editing
$invoice = null;
if ($invoice_id && ($action === 'edit' || $action === 'view')) {
    $invoice = getInvoice($invoice_id);
    if (!$invoice) {
        header("Location: invoices.php?error=Invoice not found");
        exit();
    }
}

// Get quotation data if creating from quotation
$quotation = null;
if ($quotation_id) {
    $quotation = getQuote($quotation_id);
}

// Get all quotations for dropdown
$all_quotations = getQuotes();

// Get all inventory items for manual selection
$inventory_items = getInventoryItems(null, null, true);

// Check if invoice tables exist
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'invoices'");
    $table_exists = $stmt->rowCount() > 0;
    if (!$table_exists) {
        $error = 'Invoice system tables not found. Please run the database migration first: <a href="setup_invoice_system.php" class="text-blue-600 underline">Setup Invoice System</a>';
    }
} catch(PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

$page_title = 'Invoices Management';
$content_start = true;
include 'includes/header.php';
?>

<?php if ($message): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showNotification('<?php echo addslashes($message); ?>', 'success');
});
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showNotification('<?php echo addslashes($error); ?>', 'error');
});
</script>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- Invoices List -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Invoices Management</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage all customer invoices</p>
        </div>
        <div class="space-x-2">
            <a href="?action=create" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-plus mr-2"></i>New Invoice
            </a>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Invoices</label>
            <input type="text" name="search" id="search" placeholder="Invoice #, Customer, PO #" 
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                   class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
            <select name="status" id="status" 
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                <option value="">All Statuses</option>
                <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="sent" <?php echo ($_GET['status'] ?? '') === 'sent' ? 'selected' : ''; ?>>Sent</option>
                <option value="paid" <?php echo ($_GET['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="overdue" <?php echo ($_GET['status'] ?? '') === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>
        <div class="md:col-span-3 flex justify-end gap-2">
            <button type="submit" class="bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
            <a href="invoices.php" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Invoices Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invoice #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bill To</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quotation</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tax</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php 
                $invoices = getInvoices($_GET['status'] ?? null, $_GET['search'] ?? null);
                if (empty($invoices)): ?>
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No invoices found</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                            <?php echo htmlspecialchars($inv['invoice_number']); ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo date('M d, Y', strtotime($inv['invoice_date'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-200">
                            <?php echo htmlspecialchars($inv['bill_to_name']); ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                            <?php if ($inv['quotation_number']): ?>
                                <a href="quotations.php?quote_id=<?php echo $inv['quotation_id']; ?>" class="text-solar-blue hover:underline">
                                    <?php echo htmlspecialchars($inv['quotation_number']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                            <?php echo formatCurrency($inv['subtotal']); ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                            <?php echo formatCurrency($inv['tax_amount']); ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-200">
                            <?php echo formatCurrency($inv['total_amount']); ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php 
                                $status = $inv['status'];
                                if ($status === 'draft') {
                                    echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                } elseif ($status === 'sent') {
                                    echo 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                                } elseif ($status === 'paid') {
                                    echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                } elseif ($status === 'overdue') {
                                    echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                } else {
                                    echo 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                }
                                ?>">
                                <?php echo ucfirst($inv['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex justify-center items-center gap-2">
                                <a href="print_invoice.php?id=<?php echo $inv['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" 
                                   title="Print">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="?action=edit&invoice_id=<?php echo $inv['id']; ?>" 
                                   class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300" 
                                   title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&invoice_id=<?php echo $inv['id']; ?>" 
                                   class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" 
                                   onclick="return confirm('Are you sure you want to delete this invoice?')" 
                                   title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<!-- Create/Edit Invoice Form -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">
                <?php echo $action === 'edit' ? 'Edit Invoice' : 'Create New Invoice'; ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                <?php echo $action === 'edit' ? 'Update invoice details and items' : 'Create a new invoice from quotation or manually'; ?>
            </p>
        </div>
        <div>
            <a href="invoices.php" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
            </a>
        </div>
    </div>
</div>

<form method="POST" id="invoiceForm">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Invoice Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Invoice Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="invoice_date" required 
                               value="<?php echo $invoice ? htmlspecialchars($invoice['invoice_date']) : date('Y-m-d'); ?>"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Due Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="due_date" required 
                               value="<?php echo $invoice ? htmlspecialchars($invoice['due_date']) : date('Y-m-d', strtotime('+15 days')); ?>"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            P.O. Number
                            <span class="text-xs text-gray-500 ml-2">(Auto-generated if left empty)</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="text" name="po_number" id="po_number"
                                   value="<?php echo $invoice ? htmlspecialchars($invoice['po_number']) : generatePONumber(); ?>"
                                   class="flex-1 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <?php if (!$invoice): ?>
                            <button type="button" onclick="generateNewPONumber()" 
                                    class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                                    title="Generate new P.O. number">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select name="status" 
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="draft" <?php echo ($invoice && $invoice['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="sent" <?php echo ($invoice && $invoice['status'] === 'sent') ? 'selected' : ''; ?>>Sent</option>
                            <option value="paid" <?php echo ($invoice && $invoice['status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Billing & Shipping -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Billing & Shipping Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Bill To</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="bill_to_name" required 
                                       value="<?php echo $invoice ? htmlspecialchars($invoice['bill_to_name']) : ($quotation ? htmlspecialchars($quotation['customer_name'] ?? '') : ''); ?>"
                                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address</label>
                                <textarea name="bill_to_address" rows="3"
                                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white"><?php echo $invoice ? htmlspecialchars($invoice['bill_to_address']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Ship To</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                                <input type="text" name="ship_to_name" 
                                       value="<?php echo $invoice ? htmlspecialchars($invoice['ship_to_name']) : ($quotation ? htmlspecialchars($quotation['customer_name'] ?? '') : ''); ?>"
                                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address</label>
                                <textarea name="ship_to_address" rows="3"
                                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white"><?php echo $invoice ? htmlspecialchars($invoice['ship_to_address']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Items</h2>
                    <?php if ($quotation && $action === 'create'): ?>
                    <label class="flex items-center">
                        <input type="checkbox" name="use_quotation_items" checked 
                               class="mr-2 rounded border-gray-300 text-solar-blue focus:ring-solar-blue">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Use items from quotation</span>
                    </label>
                    <?php endif; ?>
                </div>
                <div id="items-container" class="space-y-4">
                    <?php if ($invoice && !empty($invoice['items'])): ?>
                        <?php foreach ($invoice['items'] as $index => $item): ?>
                        <div class="item-row p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-5">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="items[<?php echo $index; ?>][description]" required 
                                           value="<?php echo htmlspecialchars($item['description']); ?>"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                                    <input type="hidden" name="items[<?php echo $index; ?>][inventory_item_id]" 
                                           value="<?php echo $item['inventory_item_id'] ?? ''; ?>">
                                </div>
                                <div class="col-span-6 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Qty <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="items[<?php echo $index; ?>][quantity]" 
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-qty focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white" 
                                           step="0.01" min="0" required value="<?php echo $item['quantity']; ?>">
                                </div>
                                <div class="col-span-6 md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Unit Price <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="items[<?php echo $index; ?>][unit_price]" 
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-price focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white" 
                                           step="0.01" min="0" required value="<?php echo $item['unit_price']; ?>">
                                </div>
                                <div class="col-span-12 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount</label>
                                    <input type="text" readonly 
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-amount bg-gray-50 dark:bg-gray-900 dark:text-white" 
                                           value="<?php echo formatCurrency($item['amount']); ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php elseif ($quotation && !empty($quotation['items']) && $action === 'create'): ?>
                        <?php foreach ($quotation['items'] as $index => $item): ?>
                        <div class="item-row p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-5">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="items[<?php echo $index; ?>][description]" required 
                                           value="<?php echo htmlspecialchars(($item['brand'] ?? '') . ' ' . ($item['model'] ?? '') . ' ' . ($item['size_specification'] ?? '')); ?>"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                                    <input type="hidden" name="items[<?php echo $index; ?>][inventory_item_id]" 
                                           value="<?php echo $item['inventory_item_id'] ?? ''; ?>">
                                </div>
                                <div class="col-span-6 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Qty <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="items[<?php echo $index; ?>][quantity]" 
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-qty focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white" 
                                           step="0.01" min="0" required value="<?php echo $item['quantity']; ?>">
                                </div>
                                <div class="col-span-6 md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Unit Price <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="items[<?php echo $index; ?>][unit_price]" 
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-price focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white" 
                                           step="0.01" min="0" required value="<?php echo $item['unit_price']; ?>">
                                </div>
                                <div class="col-span-12 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount</label>
                                    <input type="text" readonly 
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-amount bg-gray-50 dark:bg-gray-900 dark:text-white" 
                                           value="<?php echo formatCurrency($item['quantity'] * $item['unit_price']); ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" id="add-item-btn" 
                        class="mt-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-plus mr-2"></i>Add Item
                </button>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quotation Selection -->
            <?php if ($action === 'create'): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Create from Quotation</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Quotation</label>
                        <select name="quotation_id" id="quotation-select" 
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">None (Manual Entry)</option>
                            <?php foreach ($all_quotations as $q): ?>
                            <option value="<?php echo $q['id']; ?>" 
                                    <?php echo ($quotation_id == $q['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($q['quote_number'] . ' - ' . ($q['customer_name'] ?? 'No Customer')); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <a href="?action=create&quotation_id=" id="load-quotation-btn" 
                       class="block w-full bg-solar-blue text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition text-center">
                        Load Quotation
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tax & Totals -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Tax & Totals</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tax Rate (%)
                            <?php if (!$invoice): ?>
                                <span class="text-xs text-gray-500 ml-2">(Fixed at 0% for new invoices)</span>
                            <?php endif; ?>
                        </label>
                        <input type="number" name="tax_rate" id="tax-rate" 
                               step="0.01" min="0" max="100" 
                               value="<?php echo $invoice ? $invoice['tax_rate'] : '0'; ?>"
                               <?php echo !$invoice ? 'readonly' : ''; ?>
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white <?php echo !$invoice ? 'bg-gray-100 dark:bg-gray-900 cursor-not-allowed' : ''; ?>">
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700 dark:text-gray-300">Subtotal:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-200" id="subtotal-display"><?php echo formatCurrency(0); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700 dark:text-gray-300">Tax:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-200" id="tax-display"><?php echo formatCurrency(0); ?></span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between">
                            <span class="font-semibold text-gray-900 dark:text-gray-200">Total:</span>
                            <span class="font-bold text-lg text-gray-900 dark:text-gray-200" id="total-display"><?php echo formatCurrency(0); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Additional Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Terms & Conditions</label>
                        <textarea name="terms_conditions" rows="3"
                                  class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white"><?php echo $invoice ? htmlspecialchars($invoice['terms_conditions']) : 'Payment is due within 15 days'; ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bank Name</label>
                        <input type="text" name="bank_name" 
                               value="<?php echo $invoice ? htmlspecialchars($invoice['bank_name']) : ''; ?>"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account Number</label>
                        <input type="text" name="bank_account_number" 
                               value="<?php echo $invoice ? htmlspecialchars($invoice['bank_account_number']) : ''; ?>"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Routing</label>
                        <input type="text" name="bank_routing" 
                               value="<?php echo $invoice ? htmlspecialchars($invoice['bank_routing']) : ''; ?>"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                        <textarea name="notes" rows="3"
                                  class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white"><?php echo $invoice ? htmlspecialchars($invoice['notes']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="mt-6 flex justify-end gap-3">
        <a href="invoices.php" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            Cancel
        </a>
        <?php if ($action === 'edit'): ?>
        <a href="print_invoice.php?id=<?php echo $invoice_id; ?>" 
           class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition" 
           target="_blank">
            <i class="fas fa-print mr-2"></i>Print Invoice
        </a>
        <?php endif; ?>
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-save mr-2"></i><?php echo $action === 'edit' ? 'Update' : 'Create'; ?> Invoice
        </button>
    </div>
</form>

<script>
let itemIndex = <?php echo $invoice && !empty($invoice['items']) ? count($invoice['items']) : ($quotation && !empty($quotation['items']) ? count($quotation['items']) : 0); ?>;

document.getElementById('add-item-btn')?.addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const row = document.createElement('div');
    row.className = 'item-row p-4 border border-gray-200 dark:border-gray-700 rounded-lg';
    
    let inventoryOptions = '<option value="">Select from Inventory (Optional)</option>';
    <?php foreach ($inventory_items as $inv_item): ?>
    inventoryOptions += '<option value="<?php echo $inv_item['id']; ?>" data-price="<?php echo $inv_item['selling_price']; ?>" data-desc="<?php echo htmlspecialchars(($inv_item['brand'] ?? '') . ' ' . ($inv_item['model'] ?? '') . ' ' . ($inv_item['size_specification'] ?? '')); ?>"><?php echo htmlspecialchars(($inv_item['brand'] ?? '') . ' ' . ($inv_item['model'] ?? '') . ' ' . ($inv_item['size_specification'] ?? '')); ?></option>';
    <?php endforeach; ?>
    
    row.innerHTML = `
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select from Inventory</label>
                <select class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 mb-2 inventory-select focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                    ${inventoryOptions}
                </select>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description <span class="text-red-500">*</span></label>
                <input type="text" name="items[${itemIndex}][description]" required 
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-description focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white">
                <input type="hidden" name="items[${itemIndex}][inventory_item_id]" class="item-inventory-id" value="">
            </div>
            <div class="col-span-6 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Qty <span class="text-red-500">*</span></label>
                <input type="number" name="items[${itemIndex}][quantity]" 
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-qty focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white" 
                       step="0.01" min="0" required value="1">
            </div>
            <div class="col-span-6 md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unit Price <span class="text-red-500">*</span></label>
                <input type="number" name="items[${itemIndex}][unit_price]" 
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-price focus:ring-2 focus:ring-solar-blue focus:border-transparent dark:bg-gray-700 dark:text-white" 
                       step="0.01" min="0" required>
            </div>
            <div class="col-span-12 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Amount</label>
                <input type="text" readonly 
                       class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 item-amount bg-gray-50 dark:bg-gray-900 dark:text-white">
            </div>
        </div>
    `;
    container.appendChild(row);
    itemIndex++;
    attachItemListeners(row);
    
    const inventorySelect = row.querySelector('.inventory-select');
    inventorySelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            const desc = option.getAttribute('data-desc');
            const price = option.getAttribute('data-price');
            row.querySelector('.item-description').value = desc;
            row.querySelector('.item-price').value = price;
            row.querySelector('.item-inventory-id').value = option.value;
            calculateItemAmount(row);
        }
    });
});

function calculateItemAmount(row) {
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const amount = qty * price;
    row.querySelector('.item-amount').value = '<?php echo CURRENCY_SYMBOL; ?>' + amount.toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        subtotal += qty * price;
    });
    
    const taxRate = parseFloat(document.getElementById('tax-rate').value) || 0;
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + taxAmount;
    
    document.getElementById('subtotal-display').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + subtotal.toFixed(2);
    document.getElementById('tax-display').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + taxAmount.toFixed(2);
    document.getElementById('total-display').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + total.toFixed(2);
}

function attachItemListeners(row) {
    row.querySelector('.item-qty').addEventListener('input', () => calculateItemAmount(row));
    row.querySelector('.item-price').addEventListener('input', () => calculateItemAmount(row));
}

document.querySelectorAll('.item-row').forEach(row => attachItemListeners(row));

document.getElementById('tax-rate')?.addEventListener('input', calculateTotals);

calculateTotals();

document.getElementById('quotation-select')?.addEventListener('change', function() {
    const btn = document.getElementById('load-quotation-btn');
    if (btn) {
        btn.href = '?action=create&quotation_id=' + this.value;
    }
});

// Generate new P.O. number
function generateNewPONumber() {
    // Generate a new P.O. number using the same format (PO-YYYY-XXXX)
    const year = new Date().getFullYear();
    const random = Math.floor(Math.random() * 9999) + 1;
    const newPONumber = `PO-${year}-${String(random).padStart(4, '0')}`;
    
    const poInput = document.getElementById('po_number');
    if (poInput) {
        poInput.value = newPONumber;
    }
}

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg max-w-md transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}
</script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
