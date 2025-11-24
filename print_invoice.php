<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';
require_once 'includes/inventory.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    header("Location: invoices.php?error=" . urlencode('Invoice ID is required'));
    exit();
}

$invoice = getInvoice($invoice_id);
if (!$invoice) {
    header("Location: invoices.php?error=" . urlencode('Invoice not found'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?> - <?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></title>
    <link rel="stylesheet" href="assets/css/output.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Dynamic CSS Variables - Generated at <?php echo date('Y-m-d H:i:s'); ?> */
        :root {
            --header-bg-color: <?php echo getSystemSetting('header_background_color', '#1e40af'); ?>;
            --header-text-color: <?php echo getSystemSetting('header_text_color', '#ffffff'); ?>;
            --primary-color: <?php echo getSystemSetting('primary_color', '#1e40af'); ?>;
            --secondary-color: <?php echo getSystemSetting('secondary_color', '#3b82f6'); ?>;
            --accent-color: <?php echo getSystemSetting('accent_color', '#fbbf24'); ?>;
        }
        
        @media print {
        body {
                font-size: 12px;
                color: black !important;
                background: white !important;
                margin: 0;
            }
            /* Force light mode for printing */
            .dark * {
                background: white !important;
                color: black !important;
                border-color: #d1d5db !important;
                padding: 0;
                line-height: 1.3;
            }
            .no-print { 
                display: none !important; 
            }
            .print-break { 
                page-break-before: avoid; 
            }
            .print-break-before {
                page-break-before: avoid;
                margin-top: 10px;
            }
            .print-break-after {
                page-break-after: avoid;
                margin-bottom: 10px;
            }
            .shadow-lg, .shadow { 
                box-shadow: none !important; 
            }
            .company-header {
                background: var(--primary-color) !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .bg-blue-600 {
                background: var(--secondary-color) !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .bg-gray-50 {
                background: #f9fafb !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .bg-blue-50 {
                background: #eff6ff !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            table {
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #333 !important;
                padding: 6px 8px !important;
                font-size: 11px !important;
            }
            .rounded-lg {
                border-radius: 0 !important;
            }
            .max-w-4xl {
                max-width: 100% !important;
                margin: 0 !important;
            }
            .mx-auto {
                margin: 0 !important;
            }
            /* Logo print styles */
            img[alt*="Logo"], img[alt*="logo"] {
                max-height: 60px !important;
                width: auto !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .p-8 {
                padding: 12px !important;
            }
            .p-6 {
                padding: 8px !important;
            }
            .p-4 {
                padding: 6px !important;
            }
            .p-3 {
                padding: 4px !important;
            }
            .p-2 {
                padding: 2px !important;
            }
            .gap-8 {
                gap: 6px !important;
            }
            .gap-4 {
                gap: 4px !important;
            }
            h3 {
                font-size: 13px !important;
                margin-bottom: 4px !important;
            }
            h4 {
                font-size: 12px !important;
                margin-bottom: 3px !important;
            }
            .w-64 {
                width: 200px !important;
            }
            .w-80 {
                width: 200px !important;
            }
            .mb-8 {
                margin-bottom: 10px !important;
            }
            .mb-4 {
                margin-bottom: 6px !important;
            }
            .mt-4 {
                margin-top: 6px !important;
            }
            .mt-8 {
                margin-top: 10px !important;
            }
            .mt-12 {
                margin-top: 10px !important;
            }
            .pt-8 {
                padding-top: 10px !important;
            }
            .pt-6 {
                padding-top: 8px !important;
            }
            .pb-2 {
                padding-bottom: 3px !important;
            }
            .space-y-3 > * + * {
                margin-top: 3px !important;
            }
            .space-y-2 > * + * {
                margin-top: 2px !important;
            }
            .text-3xl {
                font-size: 20px !important;
            }
            .text-2xl {
                font-size: 18px !important;
            }
            .text-lg {
                font-size: 14px !important;
            }
            .text-xl {
                font-size: 16px !important;
            }
            .text-sm {
                font-size: 11px !important;
            }
            .text-xs {
                font-size: 10px !important;
            }
            .grid {
                display: block !important;
            }
            .grid > div {
                display: inline-block !important;
                width: 48% !important;
                vertical-align: top !important;
                margin-right: 2% !important;
            }
            
            /* Force two-column layout for Customer & Invoice Details */
            .customer-invoice-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 1rem !important;
            }
            
            .customer-invoice-grid > div {
                display: block !important;
                width: auto !important;
                margin-right: 0 !important;
            }
            
            /* Smart page break for totals section - only break if needed */
            .totals-section {
                page-break-before: auto !important;
                page-break-inside: avoid !important;
                margin-top: 15px !important;
                clear: both !important;
            }
            
            /* Allow table to break across pages naturally */
            .invoice-table {
                page-break-inside: auto !important;
            }
            
            /* Allow summary box to break if needed */
            .total-section {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            
            /* Reduce spacing for continuous flow */
            .items-section {
                margin-bottom: 15px !important;
            }
        }
        
        .company-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .invoice-table th,
        .invoice-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        .invoice-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .total-section {
            border: 2px solid var(--primary-color);
            background-color: #f8f9fa;
        }
        
        @page {
            margin: 0.5in;
            size: A4;
        }
        
        .print-header {
            border-bottom: 2px solid var(--primary-color);
            margin-bottom: 8px;
        }
        
        /* Screen view optimizations */
        body {
            font-size: 16px;
            line-height: 1.6;
        }
        
        .invoice-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-section {
            padding: 30px;
        }
        
        .details-section {
            padding: 30px;
        }
        
        .items-section {
            margin-bottom: 30px;
        }
        
        .totals-section {
            margin-bottom: 30px;
        }
        
        /* Enhanced font sizes for better readability */
        .text-sm {
            font-size: 15px !important;
        }
        
        .text-lg {
            font-size: 18px !important;
        }
        
        .text-xl {
            font-size: 20px !important;
        }
        
        .text-2xl {
            font-size: 24px !important;
        }
        
        .text-3xl {
            font-size: 28px !important;
        }
        
        /* Table font sizes */
        .invoice-table th,
        .invoice-table td {
            font-size: 14px !important;
        }
        
        /* Force two-column layout */
        .customer-details-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 2rem !important;
        }
        
        @media (max-width: 768px) {
            .customer-details-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 1rem !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Print Controls -->
    <div class="no-print bg-white shadow-sm border-b p-4 mb-6">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-800">Print Invoice</h1>
            <div class="space-x-3">
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-print mr-2"></i>Print
            </button>
                <a href="invoices.php" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
                </a>
            </div>
        </div>
        </div>

    <!-- Invoice Content -->
    <div class="invoice-content max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden print:shadow-none print:rounded-none">
        <!-- Company Header -->
        <div class="header-section print-header company-header text-white p-8 print:bg-blue-600">
            <div class="flex justify-between items-start">
                <div>
                    <div class="mb-4">
                        <img src="<?php echo htmlspecialchars(getSystemSetting('logo_url', 'images/logo.png')); ?>" 
                             alt="<?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?> Logo" 
                             class="h-20 w-auto print:h-16"
                             style="max-height: 80px; object-fit: contain;">
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars(getSystemSetting('company_title', '4NSOLAR ELECTRICZ')); ?></h1>
                        <p class="text-blue-100 text-lg"><?php echo htmlspecialchars(getSystemSetting('company_subtitle', 'Business Management System')); ?></p>
                        <p class="text-blue-100 text-lg">Your Trusted Partner in Solar Solutions</p>
                        <p class="text-blue-100 text-lg">NON VAT Reg TIN: 247-334-690-00001</p>
                        <div class="mt-4 text-sm text-blue-100">
                            <p>📧 info@4nsolar.com | 📞 +63 906 386 1728 | 📍 Zambonga City, Philippines</p>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold mb-2">INVOICE</h2>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <p class="text-sm opacity-90">Invoice Number</p>
                        <p class="text-xl font-bold"><?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="details-section p-8">
            <div class="customer-details-grid mb-4">
                <!-- Bill To Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Bill To:</h3>
                    <div class="space-y-1">
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($invoice['bill_to_name']); ?></p>
                <?php if ($invoice['bill_to_address']): ?>
                        <p class="text-gray-600 text-sm"><?php echo nl2br(htmlspecialchars($invoice['bill_to_address'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

                <!-- Invoice Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Invoice Details:</h3>
                    <div class="space-y-1 text-sm">
                        <div>
                            <span class="text-gray-600">Invoice Date: </span>
                            <span class="font-medium"><?php echo date('M j, Y', strtotime($invoice['invoice_date'])); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Due Date: </span>
                            <span class="font-medium"><?php echo date('M j, Y', strtotime($invoice['due_date'])); ?></span>
                        </div>
                        <?php if ($invoice['po_number']): ?>
                        <div>
                            <span class="text-gray-600">P.O. Number: </span>
                            <span class="font-medium"><?php echo htmlspecialchars($invoice['po_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($invoice['quotation_number']): ?>
                        <div>
                            <span class="text-gray-600">Quotation #: </span>
                            <span class="font-medium"><?php echo htmlspecialchars($invoice['quotation_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-gray-600">Status: </span>
                            <span class="font-medium capitalize 
                                <?php 
                                switch($invoice['status']) {
                                    case 'draft': echo 'text-gray-600'; break;
                                    case 'sent': echo 'text-blue-600'; break;
                                    case 'paid': echo 'text-green-600'; break;
                                    case 'overdue': echo 'text-red-600'; break;
                                    case 'cancelled': echo 'text-yellow-600'; break;
                                    default: echo 'text-gray-600';
                                }
                                ?>">
                                <?php echo htmlspecialchars($invoice['status'] ?? 'draft'); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600">Prepared by: </span>
                            <span class="font-medium"><?php echo htmlspecialchars($invoice['created_by_name'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ship To Information (if different from Bill To) -->
            <?php if ($invoice['ship_to_name'] && $invoice['ship_to_name'] !== $invoice['bill_to_name']): ?>
            <div class="mb-6 print-break-after">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Ship To:</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($invoice['ship_to_name']); ?></p>
                    <?php if ($invoice['ship_to_address']): ?>
                    <p class="text-gray-600 text-sm mt-1"><?php echo nl2br(htmlspecialchars($invoice['ship_to_address'])); ?></p>
                    <?php endif; ?>
            </div>
            </div>
            <?php endif; ?>

        <!-- Items Table -->
            <div class="items-section mb-4 print-break-after">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Items:</h3>
                <?php if (!empty($invoice['items'])): ?>
                <div class="overflow-x-auto">
                    <table class="invoice-table w-full border-collapse border border-gray-300">
            <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-medium text-gray-700" style="width: 5%">#</th>
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-medium text-gray-700" style="width: 50%">Description</th>
                                <th class="border border-gray-300 px-4 py-3 text-center text-sm font-medium text-gray-700" style="width: 10%">Qty</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700" style="width: 17.5%">Unit Price</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700" style="width: 17.5%">Total</th>
                </tr>
            </thead>
            <tbody>
                            <?php foreach ($invoice['items'] as $index => $item): ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900"><?php echo $index + 1; ?></td>
                                <td class="border border-gray-300 px-4 py-3">
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                        // Display full product information if available
                                        if (!empty($item['brand']) || !empty($item['model'])) {
                                            $item_name = trim($item['brand'] ?? '');
                                            
                                            // Add model if it exists and is not just a placeholder
                                            if (!empty($item['model']) && $item['model'] !== 'N/A') {
                                                $model = trim($item['model']);
                                                if (stripos($item_name, $model) === false) {
                                                    $item_name .= ' ' . $model;
                                                }
                                            }
                                            
                                            // Add size specification if it exists
                                            if (!empty($item['size_specification']) && $item['size_specification'] !== 'N/A') {
                                                $size_spec = trim($item['size_specification']);
                                                if (stripos($item_name, $size_spec) === false) {
                                                    $item_name .= ' ' . $size_spec;
                                                }
                                            }
                                            
                                            if ($item_name) {
                                                echo htmlspecialchars($item_name);
                                            } else {
                                                echo htmlspecialchars($item['description']);
                                            }
                                        } else {
                                            echo htmlspecialchars($item['description']);
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center text-sm text-gray-900">
                                    <?php 
                            $qty = floatval($item['quantity']);
                            echo $qty == intval($qty) ? number_format($qty, 0) : number_format($qty, 2);
                                    ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm text-gray-900">
                                    <?php echo formatCurrency($item['unit_price']); ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-900">
                                    <?php echo formatCurrency($item['amount']); ?>
                                </td>
                    </tr>
                    <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-gray-500 text-center py-4">No items in this invoice.</p>
                <?php endif; ?>
            </div>

            <!-- Totals -->
            <div class="totals-section flex justify-end mb-4">
                <div class="w-64">
                    <div class="total-section bg-gray-50 rounded-lg p-4 border-2">
                        <h4 class="text-sm font-bold text-gray-800 mb-2 text-center">INVOICE SUMMARY</h4>
                        <div class="space-y-1">
                            <div class="flex justify-between text-sm border-b pb-1">
                                <span class="text-gray-600 font-medium">Subtotal:</span>
                                <span class="font-bold"><?php echo formatCurrency($invoice['subtotal']); ?></span>
            </div>
            <?php if ($invoice['tax_rate'] > 0): ?>
                            <div class="flex justify-between text-sm border-b pb-1">
                                <span class="text-gray-600 font-medium">Tax (<?php echo number_format($invoice['tax_rate'], 1); ?>%):</span>
                                <span class="font-bold"><?php echo formatCurrency($invoice['tax_amount']); ?></span>
            </div>
            <?php endif; ?>
                            <div class="flex justify-between text-lg font-bold bg-blue-100 p-2 rounded">
                                <span class="text-gray-900">TOTAL:</span>
                                <span class="text-blue-600"><?php echo formatCurrency($invoice['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>
            </div>
        </div>

            <!-- Terms and Conditions -->
            <div class="border-t pt-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Terms & Conditions:</h3>
            <?php if ($invoice['terms_conditions']): ?>
                <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-3">
                    <p class="text-xs text-gray-700"><?php echo nl2br(htmlspecialchars($invoice['terms_conditions'])); ?></p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600">
                    <div>
                        <strong>Payment:</strong> 50% down, 50% upon completion • <strong>Installation:</strong> 5-10 working days • <strong>Warranty:</strong> Solar panels 25 yrs, Inverters 10-15 yrs
                    </div>
                    <div>
                        <strong>Due Date:</strong> Payment due within 30 days • <strong>Late fees may apply</strong> • Site inspection may be required
                    </div>
            </div>
            <?php endif; ?>
            
                <!-- Payment Information -->
                <div class="mt-4 border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-800 mb-2">Payment Options:</h4>
            <?php if ($invoice['bank_name'] || $invoice['bank_account_number']): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600 mb-3">
                        <div>
                            <strong>Bank Transfer:</strong><br>
                <?php if ($invoice['bank_name']): ?>
                            • <strong><?php echo htmlspecialchars($invoice['bank_name']); ?>:</strong> 
                <?php endif; ?>
                <?php if ($invoice['bank_account_number']): ?>
                            <?php echo htmlspecialchars($invoice['bank_account_number']); ?>
                <?php endif; ?>
                <?php if ($invoice['bank_routing']): ?>
                            (Routing: <?php echo htmlspecialchars($invoice['bank_routing']); ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600">
                        <div>
                            <strong>Bank Transfer:</strong><br>
                            • <strong>BPI:</strong> 952926574 (Novie G. Mohadsa)<br>
                            • <strong>MayBank:</strong> 02015000094 (Novie G. Mohadsa)<br>
                            • <strong>PSBank:</strong> 193110014214 (Novie G. Mohadsa)
                        </div>
                        <div>
                            <strong>Digital Payments:</strong><br>
                            • <strong>GCASH:</strong> 09063861729<br>
                            • <strong>Maya:</strong> 09063861728<br>
                            <em>All payments via bank transfer preferred</em>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="border-t pt-4 mt-4 text-center">
                <div class="bg-blue-50 rounded p-3">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Contact Us</h3>
                    <div class="text-xs text-gray-600">
                        📧 info@4nsolar.com • 📞 +63 906 386 1728 • Mon-Sat 8AM-6PM
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="mt-4 border-t pt-4">
                <div class="flex justify-between items-center">
                    <div class="text-left">
                        <div class="border-t border-gray-400 w-48 pt-1">
                            <p class="text-xs font-medium">Customer Signature / Date</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="border-t border-gray-400 w-48 pt-1">
                            <p class="text-xs font-medium">4nSolar Representative</p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($invoice['created_by_name'] ?? 'Authorized Signature'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-2 text-center text-xs text-gray-500">
                    Generated on <?php echo date('M j, Y \a\t g:i A'); ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when opened in new window
        if (window.location.search.includes('auto_print=1')) {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>
