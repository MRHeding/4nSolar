<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$quote_id = $_GET['id'] ?? null;
if (!$quote_id) {
    header("Location: inventory.php?action=quotes");
    exit();
}

$quote = getQuote($quote_id);
if (!$quote) {
    header("Location: inventory.php?action=quotes&error=" . urlencode('Quotation not found'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation <?php echo htmlspecialchars($quote['quote_number']); ?> - 4nSolar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            body { 
                font-size: 12px;
                color: black !important;
            }
            .no-print { 
                display: none !important; 
            }
            .print-break { 
                page-break-before: always; 
            }
            .shadow-lg, .shadow { 
                box-shadow: none !important; 
            }
        }
        
        .company-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Print Controls -->
    <div class="no-print bg-white shadow-sm border-b p-4 mb-6">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-800">Print Quotation</h1>
            <div class="space-x-3">
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <a href="inventory.php?action=quote&quote_id=<?php echo $quote['id']; ?>" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Quote
                </a>
            </div>
        </div>
    </div>

    <!-- Quotation Content -->
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden print:shadow-none print:rounded-none">
        <!-- Company Header -->
        <div class="company-header text-white p-8 print:bg-blue-600">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold mb-2">4nSolar</h1>
                    <p class="text-blue-100 text-lg">Solar Equipment & Installation Services</p>
                    <div class="mt-4 text-sm text-blue-100">
                        <p>📧 info@4nsolar.com</p>
                        <p>📞 +63 XXX XXX XXXX</p>
                        <p>📍 Philippines</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold mb-2">QUOTATION</h2>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <p class="text-sm opacity-90">Quote Number</p>
                        <p class="text-xl font-bold"><?php echo htmlspecialchars($quote['quote_number']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quote Details -->
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- Customer Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Bill To:</h3>
                    <div class="space-y-2">
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($quote['customer_name']); ?></p>
                        <?php if ($quote['customer_phone']): ?>
                        <p class="text-gray-600">📞 <?php echo htmlspecialchars($quote['customer_phone']); ?></p>
                        <?php endif; ?>
                        <?php if ($quote['proposal_name']): ?>
                        <p class="text-gray-600">📋 Proposal: <?php echo htmlspecialchars($quote['proposal_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quote Information -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Quote Details:</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Date:</span>
                            <span class="font-medium"><?php echo date('F j, Y', strtotime($quote['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-medium capitalize 
                                <?php 
                                switch($quote['status']) {
                                    case 'draft': echo 'text-gray-600'; break;
                                    case 'sent': echo 'text-blue-600'; break;
                                    case 'accepted': echo 'text-green-600'; break;
                                    case 'rejected': echo 'text-red-600'; break;
                                    case 'expired': echo 'text-yellow-600'; break;
                                }
                                ?>">
                                <?php echo htmlspecialchars($quote['status']); ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Prepared by:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($quote['created_by_name']); ?></span>
                        </div>
                        <?php if ($quote['valid_until']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Valid until:</span>
                            <span class="font-medium"><?php echo date('F j, Y', strtotime($quote['valid_until'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Items:</h3>
                <?php if (!empty($quote['items'])): ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-medium text-gray-700">#</th>
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-medium text-gray-700">Description</th>
                                <th class="border border-gray-300 px-4 py-3 text-center text-sm font-medium text-gray-700">Qty</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700">Unit Price</th>
                                <th class="border border-gray-300 px-4 py-3 text-center text-sm font-medium text-gray-700">Disc %</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-700">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quote['items'] as $index => $item): ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                <td class="border border-gray-300 px-4 py-3 text-sm text-gray-900"><?php echo $index + 1; ?></td>
                                <td class="border border-gray-300 px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?>
                                    </div>
                                    <?php if ($item['size_specification']): ?>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($item['size_specification']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($item['category_name']): ?>
                                    <div class="text-xs text-blue-600">
                                        Category: <?php echo htmlspecialchars($item['category_name']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center text-sm text-gray-900">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm text-gray-900">
                                    <?php echo formatCurrency($item['unit_price']); ?>
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-center text-sm text-gray-900">
                                    <?php echo $item['discount_percentage']; ?>%
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-sm font-medium text-gray-900">
                                    <?php echo formatCurrency($item['total_amount']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-gray-500 text-center py-8">No items in this quotation.</p>
                <?php endif; ?>
            </div>

            <!-- Totals -->
            <div class="flex justify-end mb-8">
                <div class="w-80">
                    <div class="bg-gray-50 rounded-lg p-6 border">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium"><?php echo formatCurrency($quote['subtotal']); ?></span>
                            </div>
                            <?php if ($quote['total_discount'] > 0): ?>
                            <div class="flex justify-between text-sm text-green-600">
                                <span>Total Discount:</span>
                                <span class="font-medium">-<?php echo formatCurrency($quote['total_discount']); ?></span>
                            </div>
                            <?php endif; ?>
                            <hr class="border-gray-300">
                            <div class="flex justify-between text-lg font-bold">
                                <span class="text-gray-900">Grand Total:</span>
                                <span class="text-blue-600"><?php echo formatCurrency($quote['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="border-t pt-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Terms & Conditions:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600">
                    <div>
                        <h4 class="font-medium text-gray-800 mb-2">Payment Terms:</h4>
                        <ul class="space-y-1">
                            <li>• 50% down payment upon acceptance</li>
                            <li>• 50% balance upon installation completion</li>
                            <li>• Payment via bank transfer or check</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800 mb-2">Installation:</h4>
                        <ul class="space-y-1">
                            <li>• Installation timeline: 5-10 working days</li>
                            <li>• Includes all necessary permits</li>
                            <li>• 1-year warranty on installation</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800 mb-2">Equipment Warranty:</h4>
                        <ul class="space-y-1">
                            <li>• Solar panels: 25 years performance</li>
                            <li>• Inverters: 10-15 years manufacturer</li>
                            <li>• Mounting system: 10 years</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800 mb-2">Validity:</h4>
                        <ul class="space-y-1">
                            <li>• This quotation is valid for 30 days</li>
                            <li>• Prices subject to change without notice</li>
                            <li>• Site inspection may be required</li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if ($quote['notes']): ?>
            <!-- Additional Notes -->
            <div class="border-t pt-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Additional Notes:</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($quote['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Contact Information -->
            <div class="border-t pt-8 mt-8 text-center">
                <div class="bg-blue-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Contact Us for Questions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <i class="fas fa-envelope text-blue-600 mb-2"></i>
                            <p class="font-medium">Email</p>
                            <p class="text-gray-600">info@4nsolar.com</p>
                        </div>
                        <div>
                            <i class="fas fa-phone text-blue-600 mb-2"></i>
                            <p class="font-medium">Phone</p>
                            <p class="text-gray-600">+63 XXX XXX XXXX</p>
                        </div>
                        <div>
                            <i class="fas fa-clock text-blue-600 mb-2"></i>
                            <p class="font-medium">Business Hours</p>
                            <p class="text-gray-600">Mon-Fri 8AM-6PM</p>
                        </div>
                    </div>
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
