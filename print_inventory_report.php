<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/suppliers.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    exit('Access denied');
}

// Get print parameters
$type = $_GET['type'] ?? 'all';
$category_id = $_GET['category'] ?? null;
$brand_filter = $_GET['brand'] ?? null;
$search_term = $_GET['search'] ?? null;

// Get the data based on type
switch ($type) {
    case 'low_stock':
        $items = getLowStockItems();
        $report_title = 'Low Stock Inventory Report';
        break;
    case 'category':
        $items = getInventoryItems($category_id);
        $categories = getCategories();
        $category_name = 'Unknown Category';
        foreach ($categories as $category) {
            if ($category['id'] == $category_id) {
                $category_name = $category['name'];
                break;
            }
        }
        $report_title = $category_name . ' Inventory Report';
        break;
    case 'brand':
        $items = getInventoryItems(null, $brand_filter);
        $report_title = $brand_filter . ' Brand Inventory Report';
        break;
    case 'search':
        $items = getInventoryItems($category_id, $brand_filter);
        // Filter items based on search term
        if ($search_term) {
            $filtered_items = array_filter($items, function($item) use ($search_term) {
                $searchable_text = strtolower(
                    $item['brand'] . ' ' . 
                    $item['model'] . ' ' . 
                    ($item['category_name'] ?? '') . ' ' . 
                    $item['size_specification'] . ' ' . 
                    ($item['supplier_name'] ?? '') . ' ' . 
                    ($item['description'] ?? '')
                );
                return strpos($searchable_text, strtolower($search_term)) !== false;
            });
            $items = array_values($filtered_items);
        }
        $report_title = 'Search Results: "' . htmlspecialchars($search_term) . '"';
        break;
    default:
        $items = getInventoryItems($category_id, $brand_filter);
        $report_title = 'Complete Inventory Report';
        break;
}

$current_user = $_SESSION['full_name'] ?? $_SESSION['username'];
$print_date = date('F j, Y g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report_title); ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
        }
        
        .company-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin: 10px 0;
        }
        
        .report-info {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0;
        }
        
        .print-controls {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        
        .inventory-table th,
        .inventory-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        
        .inventory-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        
        .inventory-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .low-stock {
            background-color: #fef2f2 !important;
            color: #dc2626;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #6b7280;
        }
        
        .signature-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .signature-box {
            border-top: 1px solid #374151;
            padding-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Print Controls (Hidden when printing) -->
    <div class="print-controls no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Report
        </button>
        <a href="inventory.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
    </div>

    <!-- Report Header -->
    <div class="header">
        <img src="images/logo.png" alt="4nSolar Logo" class="company-logo">
        <div class="report-title"><?php echo htmlspecialchars($report_title); ?></div>
        <div class="report-info">Generated on: <?php echo $print_date; ?></div>
        <div class="report-info">Prepared by: <?php echo htmlspecialchars($current_user); ?></div>
        <?php if ($type === 'category' && isset($category_name)): ?>
        <div class="report-info">Category: <?php echo htmlspecialchars($category_name); ?></div>
        <?php endif; ?>
        <?php if ($type === 'brand' && $brand_filter): ?>
        <div class="report-info">Brand: <?php echo htmlspecialchars($brand_filter); ?></div>
        <?php endif; ?>
        <?php if ($type === 'search' && $search_term): ?>
        <div class="report-info">Search Term: "<?php echo htmlspecialchars($search_term); ?>"</div>
        <?php if ($category_id): ?>
        <div class="report-info">Additional Filter: Category</div>
        <?php endif; ?>
        <?php if ($brand_filter): ?>
        <div class="report-info">Additional Filter: Brand - <?php echo htmlspecialchars($brand_filter); ?></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Summary Statistics -->
    <div class="summary-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($items); ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $total_value = 0;
                foreach ($items as $item) {
                    $total_value += $item['selling_price'] * $item['stock_quantity'];
                }
                echo '₱' . number_format($total_value, 2);
                ?>
            </div>
            <div class="stat-label">Total Inventory Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $low_stock_count = 0;
                foreach ($items as $item) {
                    if ($item['stock_quantity'] <= $item['minimum_stock']) {
                        $low_stock_count++;
                    }
                }
                echo $low_stock_count;
                ?>
            </div>
            <div class="stat-label">Low Stock Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $total_stock = 0;
                foreach ($items as $item) {
                    $total_stock += $item['stock_quantity'];
                }
                echo $total_stock;
                ?>
            </div>
            <div class="stat-label">Total Stock Units</div>
        </div>
    </div>

    <!-- Inventory Table -->
    <?php if (!empty($items)): ?>
    <table class="inventory-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Category</th>
                <th>Size/Spec</th>
                <th class="text-right">Base Price</th>
                <th class="text-right">Selling Price</th>
                <th class="text-center">Stock</th>
                <th class="text-center">Min Stock</th>
                <th class="text-center">Status</th>
                <th>Supplier</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $index => $item): ?>
            <tr class="<?php echo ($item['stock_quantity'] <= $item['minimum_stock']) ? 'low-stock' : ''; ?>">
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($item['brand']); ?></td>
                <td><?php echo htmlspecialchars($item['model']); ?></td>
                <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['size_specification']); ?></td>
                <td class="text-right">₱<?php echo number_format($item['base_price'], 2); ?></td>
                <td class="text-right">₱<?php echo number_format($item['selling_price'], 2); ?></td>
                <td class="text-center"><?php echo $item['stock_quantity']; ?></td>
                <td class="text-center"><?php echo $item['minimum_stock']; ?></td>
                <td class="text-center">
                    <?php echo ($item['stock_quantity'] <= $item['minimum_stock']) ? 'LOW STOCK' : 'OK'; ?>
                </td>
                <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align: center; padding: 40px; color: #6b7280;">
        <h3>No items found for this report.</h3>
    </div>
    <?php endif; ?>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div>Prepared by</div>
            <div style="margin-top: 10px; font-weight: bold;"><?php echo htmlspecialchars($current_user); ?></div>
        </div>
        <div class="signature-box">
            <div>Approved by</div>
            <div style="margin-top: 10px;">_______________________</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div style="text-align: center;">
            <strong>4nSolar Systems</strong> - Solar Equipment Inventory Management System<br>
            This report was generated automatically on <?php echo $print_date; ?>
        </div>
    </div>

    <script>
        // Auto-print when opened in new window
        window.addEventListener('load', function() {
            if (window.location.search.includes('auto_print=1')) {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        });
    </script>
</body>
</html>
