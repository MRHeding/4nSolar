<?php
require_once 'config.php';

// Get all inventory items with supplier info
function getInventoryItems($category_id = null, $brand_filter = null, $active_only = true) {
    global $pdo;
    
    $sql = "SELECT i.*, s.name as supplier_name, c.name as category_name 
            FROM inventory_items i 
            LEFT JOIN suppliers s ON i.supplier_id = s.id 
            LEFT JOIN categories c ON i.category_id = c.id 
            WHERE 1=1";
    
    $params = [];
    
    if ($active_only) {
        $sql .= " AND i.is_active = 1";
    }
    
    if ($category_id) {
        $sql .= " AND i.category_id = ?";
        $params[] = $category_id;
    }
    
    if ($brand_filter) {
        $sql .= " AND i.brand = ?";
        $params[] = $brand_filter;
    }
    
    $sql .= " ORDER BY i.brand, i.model";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get single inventory item
function getInventoryItem($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT i.*, s.name as supplier_name, c.name as category_name 
                          FROM inventory_items i 
                          LEFT JOIN suppliers s ON i.supplier_id = s.id 
                          LEFT JOIN categories c ON i.category_id = c.id 
                          WHERE i.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Add new inventory item
function addInventoryItem($data, $image_file = null) {
    global $pdo;
    
    // Validate stock quantity is not negative
    if (isset($data['stock_quantity']) && $data['stock_quantity'] < 0) {
        return false;
    }
    
    // Validate prices are not negative
    if (isset($data['base_price']) && $data['base_price'] < 0) {
        return false;
    }
    if (isset($data['selling_price']) && $data['selling_price'] < 0) {
        return false;
    }
    
    try {
        // Handle image upload
        $image_path = null;
        if ($image_file && $image_file['error'] === UPLOAD_ERR_OK) {
            $image_path = uploadProductImage($image_file);
            if (!$image_path) {
                return false; // Failed to upload image
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO inventory_items 
                              (brand, model, category_id, size_specification, base_price, selling_price, 
                               discount_percentage, supplier_id, stock_quantity, minimum_stock, description, image_path, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        return $stmt->execute([
            $data['brand'], $data['model'], $data['category_id'], $data['size_specification'],
            $data['base_price'], $data['selling_price'], $data['discount_percentage'],
            $data['supplier_id'], $data['stock_quantity'], $data['minimum_stock'],
            $data['description'], $image_path, $_SESSION['user_id']
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Update inventory item
function updateInventoryItem($id, $data, $image_file = null) {
    global $pdo;
    
    // Validate stock quantity is not negative
    if (isset($data['stock_quantity']) && $data['stock_quantity'] < 0) {
        return false;
    }
    
    // Validate prices are not negative
    if (isset($data['base_price']) && $data['base_price'] < 0) {
        return false;
    }
    if (isset($data['selling_price']) && $data['selling_price'] < 0) {
        return false;
    }
    
    try {
        // Handle image upload
        $image_path = $data['current_image_path'] ?? null; // Keep current image if no new image
        if ($image_file && $image_file['error'] === UPLOAD_ERR_OK) {
            $new_image_path = uploadProductImage($image_file);
            if ($new_image_path) {
                // Delete old image if it exists
                if ($image_path && file_exists($image_path)) {
                    unlink($image_path);
                }
                $image_path = $new_image_path;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE inventory_items SET 
                              brand = ?, model = ?, category_id = ?, size_specification = ?, 
                              base_price = ?, selling_price = ?, discount_percentage = ?, 
                              supplier_id = ?, stock_quantity = ?, minimum_stock = ?, description = ?, image_path = ?
                              WHERE id = ?");
        
        return $stmt->execute([
            $data['brand'], $data['model'], $data['category_id'], $data['size_specification'],
            $data['base_price'], $data['selling_price'], $data['discount_percentage'],
            $data['supplier_id'], $data['stock_quantity'], $data['minimum_stock'],
            $data['description'], $image_path, $id
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Delete inventory item (soft delete)
function deleteInventoryItem($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE inventory_items SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Get low stock items
function getLowStockItems() {
    global $pdo;
    $stmt = $pdo->query("SELECT i.*, s.name as supplier_name, c.name as category_name 
                        FROM inventory_items i 
                        LEFT JOIN suppliers s ON i.supplier_id = s.id 
                        LEFT JOIN categories c ON i.category_id = c.id 
                        WHERE i.stock_quantity <= i.minimum_stock AND i.is_active = 1 
                        ORDER BY (i.stock_quantity - i.minimum_stock)");
    return $stmt->fetchAll();
}

// Get available brands
function getAvailableBrands() {
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT brand FROM inventory_items 
                        WHERE is_active = 1 AND brand IS NOT NULL AND brand != '' 
                        ORDER BY brand");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Update stock quantity
function updateStock($item_id, $new_quantity, $movement_type, $reference_type, $reference_id = null, $notes = '') {
    global $pdo;
    
    // Validate quantity is not negative
    if ($new_quantity < 0) {
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get current stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM inventory_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $current_stock = $stmt->fetchColumn();
        
        // Update inventory item stock
        $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $item_id]);
        
        // Record stock movement
        $quantity_change = $new_quantity - $current_stock;
        $stmt = $pdo->prepare("INSERT INTO stock_movements 
                              (inventory_item_id, movement_type, quantity, previous_stock, new_stock, 
                               reference_type, reference_id, notes, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$item_id, $movement_type, abs($quantity_change), $current_stock, $new_quantity, 
                       $reference_type, $reference_id, $notes, $_SESSION['user_id']]);
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

// Get stock movements for an item
function getStockMovements($item_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT sm.*, u.full_name as created_by_name 
                          FROM stock_movements sm 
                          LEFT JOIN users u ON sm.created_by = u.id 
                          WHERE sm.inventory_item_id = ? 
                          ORDER BY sm.created_at DESC");
    $stmt->execute([$item_id]);
    return $stmt->fetchAll();
}

// Upload product image
function uploadProductImage($file) {
    $upload_dir = 'images/products/';
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Check file type
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_') . '.' . strtolower($extension);
    $filepath = $upload_dir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return false;
}

// Get product image URL
function getProductImageUrl($image_path) {
    if ($image_path && file_exists($image_path)) {
        return $image_path;
    }
    return 'images/no-image.png'; // Default placeholder image
}
?>
