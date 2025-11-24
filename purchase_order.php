<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/purchase_orders.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
if ($po_id <= 0) {
    header('Location: quotations.php');
    exit();
}

$purchase_order = getPurchaseOrderWithItems($po_id);

if (!$purchase_order) {
    $page_title = 'Purchase Order Not Found';
    include 'includes/header.php';
    ?>
    <div class="max-w-4xl mx-auto py-12 px-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Purchase Order Not Found</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                The purchase order you are trying to view does not exist or may have been removed.
            </p>
            <a href="quotations.php" class="inline-flex items-center px-4 py-2 bg-solar-blue text-white rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-clipboard-list mr-2"></i>Back to Quotations
            </a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
}

$is_print_view = isset($_GET['print']) && $_GET['print'] === '1';
$page_title = 'Purchase Order ' . htmlspecialchars($purchase_order['po_number']);
$quote = null;
if (!empty($purchase_order['quote_id'])) {
    $quote = getQuote($purchase_order['quote_id']);
}

$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'ordered' => 'bg-blue-100 text-blue-800',
    'received' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800',
];
$status = $purchase_order['status'] ?? 'pending';
$statusBadgeClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';

$createdAt = !empty($purchase_order['created_at'])
    ? date('M j, Y g:i A', strtotime($purchase_order['created_at']))
    : 'N/A';
$updatedAt = !empty($purchase_order['updated_at'])
    ? date('M j, Y g:i A', strtotime($purchase_order['updated_at']))
    : null;

if ($is_print_view) {
    $items = $purchase_order['items'] ?? [];
    $totalQuantity = number_format((float)($purchase_order['total_quantity'] ?? 0), 2);
    $totalAmount = formatCurrency($purchase_order['total_amount'] ?? 0);
    $companyName = $_SESSION['company_name'] ?? '4NSOLAR ELECTRICZ';
    $companyAddress = $_SESSION['company_address'] ?? 'Blk 1 Lot 1, Business Park, Philippines';
    $companyContact = $_SESSION['company_contact'] ?? 'Phone: +63 (000) 000 0000';
    $companyEmail = $_SESSION['company_email'] ?? 'Email: sales@4nsolar.com';
    $companyTin = $_SESSION['company_tax_id'] ?? 'TIN: ____________________';
    $preparedBy = $_SESSION['full_name'] ?? '__________________________';

    $companyLogoSrc = null;
    $logoCandidates = [
        __DIR__ . '/assets/img/logo-print.png' => 'assets/img/logo-print.png',
        __DIR__ . '/assets/img/logo-dark.png' => 'assets/img/logo-dark.png',
        __DIR__ . '/assets/img/logo.png' => 'assets/img/logo.png',
        __DIR__ . '/images/logo.png' => 'images/logo.png',
    ];
    foreach ($logoCandidates as $absolute => $relative) {
        if (file_exists($absolute)) {
            $companyLogoSrc = $relative;
            break;
        }
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $page_title; ?> (Print)</title>
    <style>
        body {
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 40px;
            color: #0f172a;
            background: #ffffff;
            font-size: 14px;
        }
        h1, h2, h3, h4, h5, h6 {
            color: #0f172a;
            margin: 0;
        }
        .po-container {
            max-width: 960px;
            margin: 0 auto;
        }
        .section {
            margin-bottom: 32px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .meta-table th, .meta-table td {
            text-align: left;
            padding: 6px 0;
            vertical-align: top;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .items-table thead th {
            border-bottom: 1px solid #cbd5f5;
            padding: 10px;
            text-align: left;
            background: #eff6ff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .items-table tbody td {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px;
        }
        .summary {
            margin-top: 18px;
            border-top: 2px solid #1d4ed8;
            padding-top: 12px;
            text-align: right;
        }
        .summary strong {
            font-size: 20px;
            color: #1d4ed8;
        }
        .tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .tag.pending { background: #fef3c7; color: #92400e; }
        .tag.ordered { background: #dbeafe; color: #1d4ed8; }
        .tag.received { background: #dcfce7; color: #166534; }
        .tag.cancelled { background: #fee2e2; color: #b91c1c; }
        .small {
            color: #475569;
            font-size: 12px;
        }
        .notes {
            margin-top: 16px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            white-space: pre-wrap;
        }
        .po-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .po-company {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .po-company img {
            max-height: 80px;
            width: auto;
            object-fit: contain;
        }
        .po-company-details h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .po-reference {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
            min-width: 240px;
            background: #f8fafc;
        }
        .po-reference dt {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #475569;
        }
        .po-reference dd {
            margin: 4px 0 12px;
            font-weight: 600;
            color: #0f172a;
        }
        .two-column {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }
        .section-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
            background: #ffffff;
        }
        .section-card h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .po-meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .po-meta-table th,
        .po-meta-table td {
            text-align: left;
            padding: 4px 0;
            font-size: 13px;
        }
        .po-meta-table th {
            color: #475569;
            width: 120px;
        }
        .signature-area {
            margin-top: 28px;
        }
        .signature-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 28px;
        }
        .signature-slot {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 16px 24px;
            min-height: 150px;
        }
        .signature-label {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            color: #475569;
        }
        .signature-line {
            border-top: 1px solid #1f2937;
            margin-top: 50px;
            padding-top: 6px;
            font-weight: 600;
            text-align: center;
        }
        .signature-notes {
            margin-top: 18px;
            font-size: 11px;
            color: #6b7280;
            line-height: 1.5;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="po-container">
        <div class="po-header">
            <div class="po-company">
                <?php if ($companyLogoSrc): ?>
                <img src="<?php echo htmlspecialchars($companyLogoSrc); ?>" alt="Company Logo">
                <?php endif; ?>
            <div class="po-company-details">
                <h1><?php echo htmlspecialchars($companyName); ?></h1>
                <p class="small" style="margin-top:6px;"><?php echo htmlspecialchars($companyAddress); ?></p>
                <p class="small"><?php echo htmlspecialchars($companyContact); ?> &middot; <?php echo htmlspecialchars($companyEmail); ?></p>
                <p class="small"><?php echo htmlspecialchars($companyTin); ?></p>
            </div>
        </div>
            <div class="po-reference">
                <dl>
                    <dt>Purchase Order #</dt>
                    <dd><?php echo htmlspecialchars($purchase_order['po_number']); ?></dd>
                    <dt>PO Date</dt>
                    <dd><?php echo date('M j, Y', strtotime($purchase_order['created_at'] ?? 'now')); ?></dd>
                    <dt>Status</dt>
                    <dd><span class="tag <?php echo htmlspecialchars($status); ?>"><?php echo ucfirst($status); ?></span></dd>
                    <?php if (!empty($purchase_order['quote_number'])): ?>
                    <dt>Reference Quote</dt>
                    <dd><?php echo htmlspecialchars($purchase_order['quote_number']); ?></dd>
                    <?php endif; ?>
                    <dt>Generated</dt>
                    <dd>
                        <?php echo $createdAt; ?>
                        <?php if ($updatedAt && $updatedAt !== $createdAt): ?>
                            <br><span class="small">Updated <?php echo $updatedAt; ?></span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="section two-column">
            <div class="section-card">
                <h3>Bill To</h3>
                <p><strong><?php echo htmlspecialchars($companyName); ?></strong></p>
                <p><?php echo htmlspecialchars($companyAddress); ?></p>
                <p><?php echo htmlspecialchars($companyContact); ?></p>
                <p><?php echo htmlspecialchars($companyEmail); ?></p>
            </div>
            <div class="section-card">
                <h3>Supplier</h3>
                <table class="po-meta-table">
                    <tr>
                        <th>Company</th>
                        <td><?php echo htmlspecialchars($purchase_order['supplier_name']); ?></td>
                    </tr>
                    <?php if (!empty($purchase_order['contact_person'])): ?>
                    <tr>
                        <th>Contact</th>
                        <td><?php echo htmlspecialchars($purchase_order['contact_person']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($purchase_order['supplier_phone'])): ?>
                    <tr>
                        <th>Phone</th>
                        <td><?php echo htmlspecialchars($purchase_order['supplier_phone']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($purchase_order['supplier_email'])): ?>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($purchase_order['supplier_email']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($purchase_order['supplier_address'])): ?>
                    <tr>
                        <th>Address</th>
                        <td><?php echo nl2br(htmlspecialchars($purchase_order['supplier_address'])); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="section">
            <h2 style="font-size:18px; font-weight:600;">Purchase Order Items</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:40%;">Item</th>
                        <th style="width:20%;">Category</th>
                        <th style="width:15%; text-align:right;">Quantity</th>
                        <th style="width:15%; text-align:right;">Unit Price</th>
                        <th style="width:10%; text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars(trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''))); ?></div>
                                <?php if (!empty($item['notes'])): ?>
                                <div style="color:#475569; font-size:12px; margin-top:4px;"><?php echo htmlspecialchars($item['notes']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? '—'); ?></td>
                            <td style="text-align:right;"><?php echo number_format((float)$item['quantity'], 2); ?></td>
                            <td style="text-align:right;"><?php echo formatCurrency($item['unit_price']); ?></td>
                            <td style="text-align:right;"><?php echo formatCurrency($item['total_amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:24px; color:#64748b;">
                                No items recorded for this purchase order.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="summary">
                <div>Total Quantity: <strong><?php echo $totalQuantity; ?></strong></div>
                <div style="margin-top:6px;">Total Amount: <strong><?php echo $totalAmount; ?></strong></div>
            </div>
        </div>

        <?php if (!empty($purchase_order['delivery_requirements']) || !empty($purchase_order['special_instructions'])): ?>
        <div class="section two-column">
            <?php if (!empty($purchase_order['delivery_requirements'])): ?>
            <div class="section-card">
                <h3>Delivery Requirements</h3>
                <div class="notes"><?php echo nl2br(htmlspecialchars($purchase_order['delivery_requirements'])); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($purchase_order['special_instructions'])): ?>
            <div class="section-card">
                <h3>Special Instructions</h3>
                <div class="notes"><?php echo nl2br(htmlspecialchars($purchase_order['special_instructions'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="signature-area">
            <div class="signature-title">Acknowledgements & Authorization</div>
            <div class="signature-grid">
                <div class="signature-slot">
                    <div class="signature-label">Prepared By</div>
                    <div class="signature-line"><?php echo htmlspecialchars($preparedBy); ?></div>
                    <div class="small" style="text-align:center;">Signature over Printed Name & Date</div>
                </div>
                <div class="signature-slot">
                    <div class="signature-label">Checked / Approved By</div>
                    <div class="signature-line">__________________________</div>
                    <div class="small" style="text-align:center;">Signature over Printed Name & Date</div>
                </div>
                <div class="signature-slot">
                    <div class="signature-label">Supplier Confirmation</div>
                    <div class="signature-line">__________________________</div>
                    <div class="small" style="text-align:center;">Authorized Signature & Date</div>
                </div>
                <div class="signature-slot">
                    <div class="signature-label">Received By (Warehouse)</div>
                    <div class="signature-line">__________________________</div>
                    <div class="small" style="text-align:center;">Signature over Printed Name & Date</div>
                </div>
            </div>
            <div class="signature-notes">
                <strong>Note:</strong> Please ensure all items, quantities, and pricing are verified prior to approval. Supplier must sign as confirmation of acceptance of terms and delivery schedule.
            </div>
        </div>

        <?php if (!empty($quote)): ?>
        <div class="section">
            <h3 style="font-size:16px; font-weight:600;">Customer & Project Summary</h3>
            <table class="meta-table">
                <?php if (!empty($quote['customer_name'])): ?>
                <tr>
                    <th style="width:160px;">Customer</th>
                    <td><?php echo htmlspecialchars($quote['customer_name']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($quote['customer_phone'])): ?>
                <tr>
                    <th>Contact</th>
                    <td><?php echo htmlspecialchars($quote['customer_phone']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($quote['proposal_name'])): ?>
                <tr>
                    <th>Proposal</th>
                    <td><?php echo htmlspecialchars($quote['proposal_name']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Quote Total</th>
                    <td><?php echo formatCurrency($quote['total_amount'] ?? 0); ?></td>
                </tr>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
    <?php
    exit();
}

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-semibold text-gray-800 dark:text-gray-100">
                    Purchase Order <?php echo htmlspecialchars($purchase_order['po_number']); ?>
                </h1>
                <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $statusBadgeClass; ?> uppercase tracking-wide">
                    <?php echo ucfirst($status); ?>
                </span>
            </div>
            <?php if (!empty($quote)): ?>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Linked Quote:
                <a href="quotations.php?action=quote&quote_id=<?php echo (int)$quote['id']; ?>"
                   class="text-solar-blue hover:underline">
                    <?php echo htmlspecialchars($quote['quote_number'] ?? ('Quote #' . $quote['id'])); ?>
                </a>
                <?php if (!empty($quote['customer_name'])): ?>
                    &middot; <?php echo htmlspecialchars($quote['customer_name']); ?>
                <?php endif; ?>
            </p>
            <?php endif; ?>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                Created <?php echo $createdAt; ?>
                <?php if ($updatedAt && $updatedAt !== $createdAt): ?>
                    &middot; Updated <?php echo $updatedAt; ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php if (!empty($purchase_order['quote_id'])): ?>
            <a href="quotations.php?action=quote&quote_id=<?php echo (int)$purchase_order['quote_id']; ?>"
               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Quote
            </a>
            <?php endif; ?>
            <a href="purchase_order.php?po_id=<?php echo (int)$purchase_order['id']; ?>&print=1"
               target="_blank" rel="noopener"
               class="inline-flex items-center px-3 py-2 bg-solar-blue text-white rounded-md text-sm hover:bg-blue-700 transition">
                <i class="fas fa-print mr-2"></i>Print PO
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Supplier Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                    <div>
                        <p class="uppercase text-xs text-gray-500 dark:text-gray-400">Supplier</p>
                        <p class="font-medium text-base">
                            <?php echo htmlspecialchars($purchase_order['supplier_name']); ?>
                        </p>
                    </div>
                    <?php if (!empty($purchase_order['contact_person'])): ?>
                    <div>
                        <p class="uppercase text-xs text-gray-500 dark:text-gray-400">Contact Person</p>
                        <p class="font-medium text-base">
                            <?php echo htmlspecialchars($purchase_order['contact_person']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($purchase_order['supplier_phone'])): ?>
                    <div>
                        <p class="uppercase text-xs text-gray-500 dark:text-gray-400">Phone</p>
                        <p class="font-medium text-base">
                            <?php echo htmlspecialchars($purchase_order['supplier_phone']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($purchase_order['supplier_email'])): ?>
                    <div>
                        <p class="uppercase text-xs text-gray-500 dark:text-gray-400">Email</p>
                        <p class="font-medium text-base">
                            <a href="mailto:<?php echo htmlspecialchars($purchase_order['supplier_email']); ?>"
                               class="text-solar-blue hover:underline">
                                <?php echo htmlspecialchars($purchase_order['supplier_email']); ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

        <?php if (!empty($purchase_order['supplier_address'])): ?>
                <div class="mt-4">
                    <p class="uppercase text-xs text-gray-500 dark:text-gray-400">Address</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                        <?php echo nl2br(htmlspecialchars($purchase_order['supplier_address'])); ?>
                    </p>
                </div>
        <?php endif; ?>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Items</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <?php echo (int)($purchase_order['items_count'] ?? 0); ?> item(s)
                        &middot; Qty <?php echo number_format((float)($purchase_order['total_quantity'] ?? 0), 2); ?>
                    </span>
                </div>

                <?php if (!empty($purchase_order['items'])): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300 uppercase tracking-wide text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Item</th>
                                <th class="px-4 py-3 text-left w-32">Category</th>
                                <th class="px-4 py-3 text-right w-24">Quantity</th>
                                <th class="px-4 py-3 text-right w-32">Unit Price</th>
                                <th class="px-4 py-3 text-right w-32">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php foreach ($purchase_order['items'] as $item): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800 dark:text-gray-100">
                                        <?php echo htmlspecialchars(trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''))); ?>
                                    </div>
                                    <?php if (!empty($item['notes'])): ?>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <?php echo htmlspecialchars($item['notes']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    <?php echo htmlspecialchars($item['category_name'] ?? '—'); ?>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">
                                    <?php echo number_format((float)$item['quantity'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">
                                    <?php echo formatCurrency($item['unit_price']); ?>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-100">
                                    <?php echo formatCurrency($item['total_amount']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Total
                                </td>
                                <td class="px-4 py-3 text-right text-lg font-bold text-blue-600 dark:text-blue-400">
                                    <?php echo formatCurrency($purchase_order['total_amount']); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-box-open text-gray-300 text-3xl mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">No items added to this purchase order yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Order Summary</h2>
                <dl class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <dt>Purchase Order #</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">
                            <?php echo htmlspecialchars($purchase_order['po_number']); ?>
                        </dd>
                    </div>
                    <?php if (!empty($purchase_order['delivery_requirements'])): ?>
                    <div>
                        <dt class="font-medium text-gray-900 dark:text-gray-100 mb-1">Delivery Requirements</dt>
                        <dd class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">
                            <?php echo nl2br(htmlspecialchars($purchase_order['delivery_requirements'])); ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($purchase_order['special_instructions'])): ?>
                    <div>
                        <dt class="font-medium text-gray-900 dark:text-gray-100 mb-1">Special Instructions</dt>
                        <dd class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">
                            <?php echo nl2br(htmlspecialchars($purchase_order['special_instructions'])); ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                </dl>

                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Total Quantity</span>
        <span class="font-medium text-gray-900 dark:text-gray-100"><?php echo number_format((float)$purchase_order['total_quantity'], 2); ?></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-blue-600 dark:text-blue-400 mt-3">
                        <span>Total Cost</span>
                        <span><?php echo formatCurrency($purchase_order['total_amount']); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($quote)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Customer & Project</h2>
                <dl class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <?php if (!empty($quote['customer_name'])): ?>
                    <div class="flex justify-between">
                        <dt>Customer</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">
                            <?php echo htmlspecialchars($quote['customer_name']); ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($quote['customer_phone'])): ?>
                    <div class="flex justify-between">
                        <dt>Contact</dt>
                        <dd><?php echo htmlspecialchars($quote['customer_phone']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($quote['proposal_name'])): ?>
                    <div class="flex justify-between">
                        <dt>Proposal</dt>
                        <dd><?php echo htmlspecialchars($quote['proposal_name']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <dt>Quote Total</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">
                            <?php echo formatCurrency($quote['total_amount'] ?? 0); ?>
                        </dd>
                    </div>
                </dl>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

