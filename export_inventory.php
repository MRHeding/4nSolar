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

// Get export parameters
$format = $_GET['format'] ?? 'csv';
$category_filter = $_GET['category'] ?? null;
$brand_filter = $_GET['brand'] ?? null;
$filter = $_GET['filter'] ?? null;

// Get the data based on filters
if ($filter == 'low_stock') {
    $items = getLowStockItems();
    $filename = 'low_stock_inventory';
} else {
    $items = getInventoryItems($category_filter, $brand_filter);
    $filename = 'inventory';
}

// Add category name to filename if filtered
if ($category_filter) {
    $categories = getCategories();
    $category_name = '';
    foreach ($categories as $category) {
        if ($category['id'] == $category_filter) {
            $category_name = $category['name'];
            break;
        }
    }
    if ($category_name) {
        $filename .= '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($category_name));
    }
}

// Add brand name to filename if filtered
if ($brand_filter) {
    $filename .= '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($brand_filter));
}

// Add timestamp to filename
$filename .= '_' . date('Y-m-d_H-i-s');

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create file pointer connected to output stream
    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8 (helps with Excel)
    fwrite($output, "\xEF\xBB\xBF");

    // Add CSV headers
    fputcsv($output, [
        'ID',
        'Brand',
        'Model',
        'Category',
        'Size/Specification',
        'Base Price (₱)',
        'Selling Price (₱)',
        'Discount (%)',
        'Stock Quantity',
        'Minimum Stock',
        'Stock Status',
        'Supplier',
        'Description',
        'Created Date'
    ]);

    // Add data rows
    foreach ($items as $item) {
        $stock_status = $item['stock_quantity'] <= $item['minimum_stock'] ? 'Low Stock' : 'Normal';
        
        fputcsv($output, [
            $item['id'],
            $item['brand'],
            $item['model'],
            $item['category_name'] ?? 'N/A',
            $item['size_specification'],
            number_format($item['base_price'], 2),
            number_format($item['selling_price'], 2),
            $item['discount_percentage'],
            $item['stock_quantity'],
            $item['minimum_stock'],
            $stock_status,
            $item['supplier_name'] ?? 'N/A',
            $item['description'],
            date('Y-m-d H:i:s', strtotime($item['created_at']))
        ]);
    }

    fclose($output);
    exit();

} elseif ($format === 'json') {
    // JSON export
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    $export_data = [
        'export_info' => [
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['full_name'] ?? $_SESSION['username'],
            'total_items' => count($items),
            'filters_applied' => [
                'category' => $category_filter,
                'brand' => $brand_filter,
                'type' => $filter
            ]
        ],
        'items' => []
    ];

    foreach ($items as $item) {
        $export_data['items'][] = [
            'id' => (int)$item['id'],
            'brand' => $item['brand'],
            'model' => $item['model'],
            'category' => $item['category_name'] ?? null,
            'size_specification' => $item['size_specification'],
            'pricing' => [
                'base_price' => (float)$item['base_price'],
                'selling_price' => (float)$item['selling_price'],
                'discount_percentage' => (float)$item['discount_percentage']
            ],
            'stock' => [
                'current_quantity' => (int)$item['stock_quantity'],
                'minimum_stock' => (int)$item['minimum_stock'],
                'status' => $item['stock_quantity'] <= $item['minimum_stock'] ? 'low' : 'normal'
            ],
            'supplier' => $item['supplier_name'] ?? null,
            'description' => $item['description'],
            'created_at' => $item['created_at']
        ];
    }

    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();

} else {
    http_response_code(400);
    exit('Invalid format specified');
}
?>
