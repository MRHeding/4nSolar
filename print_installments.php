<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/installments.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$quote_id = $_GET['quote_id'] ?? null;
if (!$quote_id) {
    die('Quote ID is required');
}

// Get quotation details
$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    die('Quotation not found');
}

// Get customer information from quote_customer_info table
$stmt = $pdo->prepare("SELECT * FROM quote_customer_info WHERE quote_id = ?");
$stmt->execute([$quote_id]);
$customer_info = $stmt->fetch(PDO::FETCH_ASSOC);


// Get installment plan details
$installment_plan = getInstallmentPlanWithAdjustments($quote_id);
if (!$installment_plan) {
    die('No installment plan found for this quotation');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installment Plan - <?php echo htmlspecialchars($quote['customer_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            body { 
                font-size: 14px;
                color: black !important;
                background: white !important;
                margin: 0;
            }
            /* Force light mode for printing */
            .dark * {
                background: white !important;
                color: black !important;
                border-color: #d1d5db !important;
            }
            .no-print { 
                display: none !important; 
            }
            .print-break { 
                page-break-before: avoid; 
            }
            .print-break-before {
                page-break-before: avoid;
            }
            .print-break-after {
                page-break-after: avoid;
            }
            .shadow-lg, .shadow { 
                box-shadow: none !important; 
            }
            .company-header {
                background: #1e40af !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .bg-blue-600 {
                background: #2563eb !important;
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
            .bg-green-50 {
                background: #f0fdf4 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .bg-orange-50 {
                background: #fff7ed !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .bg-red-50 {
                background: #fef2f2 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .bg-purple-50 {
                background: #faf5ff !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .bg-yellow-50 {
                background: #fefce8 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            table {
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #ddd !important;
                padding: 8px 10px !important;
                font-size: 13px !important;
            }
            .rounded-lg {
                border-radius: 4px !important;
            }
            .max-w-4xl {
                max-width: 100% !important;
                margin: 0 !important;
            }
            .mx-auto {
                margin: 0 !important;
            }
            .quotation-content {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .header-section {
                padding: 20px !important;
            }
            .details-section {
                padding: 20px !important;
            }
            .items-section {
                margin-bottom: 20px !important;
            }
            .totals-section {
                margin-bottom: 20px !important;
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
            
            /* Preserve grid layout for print */
            .customer-details-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 1.5rem !important;
            }
            
            /* Preserve payment summary grid in one row for print */
            .grid {
                display: grid !important;
            }
            .grid-cols-1 {
                grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            }
            .md\\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                display: grid !important;
            }
            .md\\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            
            /* Force payment summary to stay in one row when printing */
            .grid.grid-cols-1.md\\:grid-cols-4 {
                grid-template-columns: repeat(4, 1fr) !important;
                display: grid !important;
                gap: 0.5rem !important;
            }
            
            /* Ensure payment summary cards are smaller in print to fit in one row */
            .grid.grid-cols-1.md\\:grid-cols-4 > div {
                padding: 8px !important;
                font-size: 12px !important;
                min-width: 0 !important;
                flex-shrink: 0 !important;
            }
            
            .grid.grid-cols-1.md\\:grid-cols-4 .text-xl {
                font-size: 14px !important;
            }
            
            .grid.grid-cols-1.md\\:grid-cols-4 .text-sm {
                font-size: 10px !important;
            }
            
            /* Override any responsive grid behavior for print */
            .grid.grid-cols-1 {
                grid-template-columns: repeat(4, 1fr) !important;
                display: grid !important;
            }
            
            /* Force all grid containers to use 4 columns in print */
            .items-section .grid {
                grid-template-columns: repeat(4, 1fr) !important;
                display: grid !important;
                gap: 0.5rem !important;
            }
            
            /* Specific targeting for payment summary grid */
            .items-section .grid.grid-cols-1.md\\:grid-cols-4 {
                grid-template-columns: repeat(4, 1fr) !important;
                display: grid !important;
                gap: 0.5rem !important;
                width: 100% !important;
            }
            
            /* Override any media query behavior */
            @media print {
                .grid.grid-cols-1.md\\:grid-cols-4 {
                    grid-template-columns: repeat(4, 1fr) !important;
                    display: grid !important;
                }
                
                .grid.grid-cols-1 {
                    grid-template-columns: repeat(4, 1fr) !important;
                    display: grid !important;
                }
            }
        }
        
        .company-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
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
            border: 2px solid #1e40af;
            background-color: #f8f9fa;
        }
        
        @page {
            margin: 0.5in;
            size: A4;
        }
        
        .print-header {
            border-bottom: 2px solid #1e40af;
            margin-bottom: 8px;
        }
        
        /* Screen view optimizations */
        body {
            font-size: 16px;
            line-height: 1.6;
        }
        
        .quotation-content {
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
        
        /* Payment summary grid layout */
        .grid.grid-cols-1.md\\:grid-cols-4 {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 1rem !important;
        }
        
        @media (max-width: 768px) {
            .customer-details-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
            .grid.grid-cols-1.md\\:grid-cols-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        
        @media (max-width: 480px) {
            .grid.grid-cols-1.md\\:grid-cols-4 {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Print Controls -->
    <div class="no-print bg-white shadow-sm border-b p-4 mb-6">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-800">Print Installment Plan</h1>
            <div class="space-x-3">
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <a href="quotations.php?action=installments&quote_id=<?php echo $quote_id; ?>" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Installments
                </a>
            </div>
        </div>
    </div>

    <!-- Installment Plan Content -->
    <div class="quotation-content max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden print:shadow-none print:rounded-none">
        <!-- Company Header -->
        <div class="header-section print-header company-header text-white p-8 print:bg-blue-600">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold mb-2">4NSOLAR ELECTRICZ</h1>
                    <p class="text-blue-100 text-lg">Solar Power Installation Services</p>
                    <p class="text-blue-100 text-lg">Your Trusted Partner in Solar Solutions</p>
                    <p class="text-blue-100 text-lg">NON VAT Reg TIN: 247-334-690-00001</p>
                    <div class="mt-4 text-sm text-blue-100">
                        <p>📧 info@4nsolar.com | 📞 +63 906 386 1728 | 📍 Zambonga City, Philippines</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold mb-2">INSTALLMENT PAYMENT PLAN</h2>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <p class="text-sm opacity-90">Quote Number</p>
                        <p class="text-xl font-bold"><?php echo htmlspecialchars($quote['quote_number']); ?></p>
                        <p class="text-sm opacity-90 mt-1">Plan: <?php echo htmlspecialchars($installment_plan['plan_name']); ?></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Quote Details -->
        <div class="details-section p-8">
            <div class="customer-details-grid mb-4">
                <!-- Customer Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Bill To:</h3>
                    <div class="space-y-1">
                        <?php 
                        // Get customer name with fallback
                        $customer_name = '';
                        if (!empty($quote['customer_name'])) {
                            $customer_name = $quote['customer_name'];
                        } elseif ($customer_info && !empty($customer_info['full_name'])) {
                            $customer_name = $customer_info['full_name'];
                        }
                        ?>
                        <?php if ($customer_name): ?>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($customer_name); ?></p>
                        <?php else: ?>
                        <p class="font-medium text-gray-500 italic">Customer name not provided</p>
                        <?php endif; ?>
                        
                        <?php 
                        // Get phone number with fallback
                        $phone = '';
                        if ($customer_info && !empty($customer_info['phone_number'])) {
                            $phone = $customer_info['phone_number'];
                        } elseif (!empty($quote['customer_phone'])) {
                            $phone = $quote['customer_phone'];
                        }
                        ?>
                        <?php if ($phone): ?>
                        <p class="text-gray-600 text-sm">📞 <?php echo htmlspecialchars($phone); ?></p>
                        <?php else: ?>
                        <p class="text-gray-500 italic text-sm">📞 Phone not provided</p>
                        <?php endif; ?>
                        
                        <?php 
                        // Get address with fallback
                        $address = '';
                        if ($customer_info && !empty($customer_info['address'])) {
                            $address = $customer_info['address'];
                        }
                        ?>
                        <?php if ($address): ?>
                        <p class="text-gray-600 text-sm">📍 <?php echo nl2br(htmlspecialchars($address)); ?></p>
                        <?php else: ?>
                        <p class="text-gray-500 italic text-sm">📍 Address not provided</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Installment Plan Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Plan Details:</h3>
                    <div class="space-y-1 text-sm">
                        <div>
                            <span class="text-gray-600">Plan Name: </span>
                            <span class="font-medium"><?php echo htmlspecialchars($installment_plan['plan_name']); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Total Amount: </span>
                            <span class="font-medium"><?php echo formatCurrency($installment_plan['total_amount']); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Down Payment: </span>
                            <span class="font-medium"><?php echo formatCurrency($installment_plan['down_payment']); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Installments: </span>
                            <span class="font-medium"><?php echo $installment_plan['number_of_installments']; ?> payments</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Frequency: </span>
                            <span class="font-medium"><?php echo ucfirst($installment_plan['payment_frequency']); ?></span>
                        </div>
                        <?php if ($installment_plan['interest_rate'] > 0): ?>
                        <div>
                            <span class="text-gray-600">Interest Rate: </span>
                            <span class="font-medium"><?php echo $installment_plan['interest_rate']; ?>%</span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="text-gray-600">Start Date: </span>
                            <span class="font-medium"><?php echo date('M j, Y', strtotime($installment_plan['start_date'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="items-section mb-4 print-break-after">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Payment Summary:</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4" style="grid-template-columns: repeat(4, 1fr) !important; display: grid !important;">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                        <div class="text-sm font-medium text-green-800">Total Paid</div>
                        <div class="text-xl font-bold text-green-900"><?php echo formatCurrency($installment_plan['total_paid']); ?></div>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
                        <div class="text-sm font-medium text-orange-800">Remaining Balance</div>
                        <div class="text-xl font-bold text-orange-900"><?php echo formatCurrency($installment_plan['total_remaining']); ?></div>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                        <div class="text-sm font-medium text-blue-800">Pending Amount</div>
                        <div class="text-xl font-bold text-blue-900"><?php echo formatCurrency($installment_plan['summary']['pending_amount'] ?? 0); ?></div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                        <div class="text-sm font-medium text-red-800">Overdue Amount</div>
                        <div class="text-xl font-bold text-red-900"><?php echo formatCurrency($installment_plan['summary']['overdue_amount'] ?? 0); ?></div>
                    </div>
                </div>
            </div>

            <!-- Payment Schedule -->
            <div class="items-section mb-4 print-break-after">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1">Payment Schedule:</h3>
                <?php if (!empty($installment_plan['payments'])): ?>
                <div class="overflow-x-auto">
                    <table class="invoice-table w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-4 py-3 text-center text-sm font-medium text-gray-700" style="width: 5%">#</th>
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-medium text-gray-700" style="width: 15%">Due Date</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700" style="width: 15%">Due Amount</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700" style="width: 15%">Paid Amount</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700" style="width: 15%">Remaining</th>
                                <th class="border border-gray-300 px-4 py-3 text-center text-sm font-medium text-gray-700" style="width: 10%">Status</th>
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-medium text-gray-700" style="width: 25%">Payment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($installment_plan['payments'] as $index => $payment): ?>
                            <?php 
                            $total_due = floatval($payment['due_amount']) + floatval($payment['late_fee_applied']);
                            $remaining = $total_due - floatval($payment['paid_amount']);
                            ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                <td class="border border-gray-300 px-4 py-3 text-center text-sm text-gray-900"><?php echo $payment['installment_number']; ?></td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($payment['due_date'])); ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm text-gray-900">
                                    <?php echo formatCurrency($payment['due_amount']); ?>
                                    <?php if ($payment['late_fee_applied'] > 0): ?>
                                    <div class="text-xs text-red-600">+<?php echo formatCurrency($payment['late_fee_applied']); ?> late fee</div>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm text-gray-900">
                                    <?php if ($payment['paid_amount'] > 0): ?>
                                        <?php echo formatCurrency($payment['paid_amount']); ?>
                                        <?php if ($payment['payment_method']): ?>
                                        <div class="text-xs text-gray-500"><?php echo ucfirst($payment['payment_method']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">₱0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm text-gray-900">
                                    <?php if ($remaining > 0): ?>
                                        <span class="font-medium text-orange-600"><?php echo formatCurrency($remaining); ?></span>
                                    <?php else: ?>
                                        <span class="font-medium text-green-600">₱0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php 
                                        switch($payment['status']) {
                                            case 'paid': echo 'bg-green-100 text-green-800'; break;
                                            case 'partial': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'overdue': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800'; break;
                                        }
                                        ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900">
                                    <?php if ($payment['payment_date']): ?>
                                        <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                        <?php if ($payment['reference_number']): ?>
                                        <div class="text-xs text-gray-500">Ref: <?php echo htmlspecialchars($payment['reference_number']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-gray-500 text-center py-4">No payment schedule available.</p>
                <?php endif; ?>
            </div>

            <!-- Terms and Conditions -->
            <div class="border-t pt-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Payment Terms & Conditions:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600">
                    <div>
                        <strong>Payment Schedule:</strong> Payments are due according to the schedule above. Late payments may incur additional fees.<br>
                        <strong>Late Fees:</strong> A late fee of <?php echo formatCurrency($installment_plan['late_fee_amount']); ?> will be applied to payments made after the grace period.<br>
                        <strong>Payment Methods:</strong> We accept cash, check, bank transfer, GCash, PayMaya, and credit/debit cards.
                    </div>
                    <div>
                        <strong>Receipts:</strong> Payment receipts will be provided for all transactions. Please keep them for your records.<br>
                        <strong>Early Payment:</strong> Early payments are welcome and will reduce the total interest paid.<br>
                        <strong>Default:</strong> Failure to make payments may result in project suspension or cancellation.
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="mt-4 border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-800 mb-2">Payment Options:</h4>
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
                </div>
            </div>

            <?php if ($installment_plan['notes']): ?>
            <!-- Additional Notes -->
            <div class="border-t pt-2 mt-2">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Special Notes:</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-2">
                    <p class="text-xs text-gray-700"><?php echo nl2br(htmlspecialchars($installment_plan['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

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
