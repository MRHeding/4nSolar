<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/projects.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    die('Project ID is required');
}

$project = getSolarProject($project_id);
if (!$project) {
    die('Project not found');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote - <?php echo htmlspecialchars($project['project_name']); ?></title>
    <link href="assets/css/output.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-white">
    <div class="max-w-4xl mx-auto p-8">
        <!-- Print Button -->
        <div class="no-print mb-6">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-print mr-2"></i>Print Quote
            </button>
            <button onclick="window.close()" class="ml-2 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                Close
            </button>
        </div>

        <!-- Quote Header -->
        <div class="border-b-2 border-blue-600 pb-6 mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-4xl font-bold text-blue-600">4NSOLAR ELECTRICZ</h1>
                    <p class="text-gray-600 mt-2">Solar Energy Solutions</p>
                    <div class="mt-4 text-sm text-gray-600">
                        <p>📧 info@4nsolar.com</p>
                        <p>📞 +1 (555) 123-4567</p>
                        <p>🌐 www.4nsolar.com</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold text-gray-800">SOLAR SYSTEM QUOTE</h2>
                    <p class="text-gray-600 mt-2">Quote #<?php echo str_pad($project['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-gray-600">Date: <?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Customer Information</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="font-medium"><?php echo htmlspecialchars($project['customer_name']); ?></p>
                    <?php if ($project['customer_email']): ?>
                    <p class="text-gray-600"><?php echo htmlspecialchars($project['customer_email']); ?></p>
                    <?php endif; ?>
                    <?php if ($project['customer_phone']): ?>
                    <p class="text-gray-600"><?php echo htmlspecialchars($project['customer_phone']); ?></p>
                    <?php endif; ?>
                    <?php if ($project['customer_address']): ?>
                    <p class="text-gray-600 mt-2"><?php echo nl2br(htmlspecialchars($project['customer_address'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Project Information</h3>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="font-medium"><?php echo htmlspecialchars($project['project_name']); ?></p>
                    <?php if ($project['system_size_kw']): ?>
                    <p class="text-gray-600">System Size: <?php echo $project['system_size_kw']; ?> kW</p>
                    <?php endif; ?>
                    <p class="text-gray-600">Status: <?php echo ucfirst(str_replace('_', ' ', $project['project_status'])); ?></p>
                    <p class="text-gray-600">Created: <?php echo date('F j, Y', strtotime($project['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">System Components</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border border-gray-300 px-4 py-3 text-left font-medium text-gray-700">Description</th>
                            <th class="border border-gray-300 px-4 py-3 text-center font-medium text-gray-700">Qty</th>
                            <th class="border border-gray-300 px-4 py-3 text-right font-medium text-gray-700">Unit Price</th>
                            <th class="border border-gray-300 px-4 py-3 text-center font-medium text-gray-700">Disc %</th>
                            <th class="border border-gray-300 px-4 py-3 text-right font-medium text-gray-700">Discount</th>
                            <th class="border border-gray-300 px-4 py-3 text-right font-medium text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($project['items'])): ?>
                            <?php foreach ($project['items'] as $item): ?>
                            <tr>
                                <td class="border border-gray-300 px-4 py-3">
                                    <div class="font-medium"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                                    <div class="text-sm text-gray-600"><?php echo htmlspecialchars($item['size_specification']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                    <?php 
                                    $discount_percentage = 0;
                                    if ($item['discount_amount'] > 0 && $item['unit_selling_price'] > 0) {
                                        $discount_percentage = ($item['discount_amount'] / ($item['unit_selling_price'] * $item['quantity'])) * 100;
                                    }
                                    if ($discount_percentage > 0): ?>
                                    <div class="text-xs text-green-600">
                                        <?php echo number_format($discount_percentage, 1); ?>% discount applied
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center"><?php echo $item['quantity']; ?></td>
                                <td class="border border-gray-300 px-4 py-3 text-right">
                                    <?php 
                                    $discount_percentage = 0;
                                    if ($item['discount_amount'] > 0 && $item['unit_selling_price'] > 0) {
                                        $discount_percentage = ($item['discount_amount'] / ($item['unit_selling_price'] * $item['quantity'])) * 100;
                                    }
                                    if ($discount_percentage > 0): 
                                        $original_price = $item['unit_selling_price'] / (1 - $discount_percentage / 100);
                                    ?>
                                        <div class="text-xs text-gray-500 line-through"><?php echo formatCurrency($original_price); ?></div>
                                        <div class="text-xs text-green-600"><?php echo formatCurrency($item['unit_selling_price']); ?></div>
                                    <?php else: ?>
                                        <?php echo formatCurrency($item['unit_selling_price']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center">
                                    <?php 
                                    $discount_percentage = 0;
                                    if ($item['discount_amount'] > 0 && $item['unit_selling_price'] > 0) {
                                        $discount_percentage = ($item['discount_amount'] / ($item['unit_selling_price'] * $item['quantity'])) * 100;
                                    }
                                    if ($discount_percentage > 0): ?>
                                        <span class="text-green-600 font-medium"><?php echo number_format($discount_percentage, 1); ?>%</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">0%</span>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-green-600">
                                    <?php if ($item['discount_amount'] > 0): ?>
                                        -<?php echo formatCurrency($item['discount_amount']); ?>
                                    <?php else: ?>
                                        <?php echo formatCurrency(0); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right font-medium"><?php echo formatCurrency($item['total_amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="border border-gray-300 px-4 py-8 text-center text-gray-500">
                                    No items added to this project yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <!-- Totals Section -->
                        <tr class="bg-gray-50">
                            <td colspan="5" class="border border-gray-300 px-4 py-3 text-right font-medium">Subtotal:</td>
                            <td class="border border-gray-300 px-4 py-3 text-right font-medium"><?php echo formatCurrency($project['total_selling_price']); ?></td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td colspan="5" class="border border-gray-300 px-4 py-3 text-right font-medium">Total Discount:</td>
                            <td class="border border-gray-300 px-4 py-3 text-right font-medium text-green-600">-<?php echo formatCurrency($project['total_discount']); ?></td>
                        </tr>
                        <tr class="bg-blue-50">
                            <td colspan="5" class="border border-gray-300 px-4 py-3 text-right text-lg font-bold">Total Amount:</td>
                            <td class="border border-gray-300 px-4 py-3 text-right text-lg font-bold text-blue-600"><?php echo formatCurrency($project['final_amount']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Terms & Conditions</h3>
            <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-700 space-y-2">
                <p><strong>1. Quote Validity:</strong> This quote is valid for 30 days from the date of issue.</p>
                <p><strong>2. Payment Terms:</strong> 50% deposit required upon contract signing, remaining balance due upon completion.</p>
                <p><strong>3. Installation:</strong> Installation timeline will be provided upon project approval and permitting completion.</p>
                <p><strong>4. Warranty:</strong> All equipment comes with manufacturer warranty. Installation warranty: 5 years.</p>
                <p><strong>5. Permits:</strong> Customer is responsible for obtaining necessary permits and approvals.</p>
                <p><strong>6. Weather Dependency:</strong> Installation schedules may be affected by weather conditions.</p>
                <p><strong>7. System Performance:</strong> Actual system performance may vary based on weather, shading, and usage patterns.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t pt-6 text-center text-sm text-gray-600">
            <p class="mb-2">Thank you for choosing 4NSOLAR ELECTRICZ for your solar energy needs!</p>
            <p>For questions about this quote, please contact us at info@4nsolar.com or (555) 123-4567</p>
            <p class="mt-4 text-xs">© 2025 4NSOLAR ELECTRICZ. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
