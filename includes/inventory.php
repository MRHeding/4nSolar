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

// Get available stock items (items with stock above minimum)
function getAvailableStockItems() {
    global $pdo;
    $stmt = $pdo->query("SELECT i.*, s.name as supplier_name, c.name as category_name 
                        FROM inventory_items i 
                        LEFT JOIN suppliers s ON i.supplier_id = s.id 
                        LEFT JOIN categories c ON i.category_id = c.id 
                        WHERE i.stock_quantity > i.minimum_stock AND i.is_active = 1 
                        ORDER BY i.stock_quantity DESC, i.brand, i.model");
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

// ================== QUOTATION FUNCTIONS ==================

// Create new quotation
function createQuote($customer_name = null, $customer_phone = null, $proposal_name = null) {
    global $pdo;
    
    try {
        $quote_number = generateQuoteNumber();
        
        $stmt = $pdo->prepare("INSERT INTO quotations 
                              (quote_number, customer_name, customer_phone, proposal_name, status, created_by) 
                              VALUES (?, ?, ?, ?, 'draft', ?)");
        
        $stmt->execute([
            $quote_number, $customer_name, $customer_phone, $proposal_name, $_SESSION['user_id']
        ]);
        
        return $pdo->lastInsertId();
    } catch(PDOException $e) {
        return false;
    }
}

// Generate unique quote number
function generateQuoteNumber() {
    return 'QTE' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Get all quotations
function getQuotes($status = null) {
    global $pdo;
    
    $sql = "SELECT q.*, u.full_name as created_by_name,
            (SELECT COUNT(*) FROM quote_items WHERE quote_id = q.id) as items_count,
            (SELECT COUNT(*) FROM installment_plans WHERE quotation_id = q.id AND status = 'active') as has_installment_plan,
            (SELECT installment_status FROM quotations WHERE id = q.id) as installment_status
            FROM quotations q 
            LEFT JOIN users u ON q.created_by = u.id 
            WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $sql .= " AND q.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY q.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get single quotation with items
function getQuote($id) {
    global $pdo;
    
    // Get quote details
    $stmt = $pdo->prepare("SELECT q.*, u.full_name as created_by_name 
                          FROM quotations q 
                          LEFT JOIN users u ON q.created_by = u.id 
                          WHERE q.id = ?");
    $stmt->execute([$id]);
    $quote = $stmt->fetch();
    
    if ($quote) {
        // Get quote items
        $stmt = $pdo->prepare("SELECT qi.*, i.brand, i.model, i.size_specification, 
                              c.name as category_name, i.stock_quantity, i.selling_price as current_price
                              FROM quote_items qi 
                              LEFT JOIN inventory_items i ON qi.inventory_item_id = i.id 
                              LEFT JOIN categories c ON i.category_id = c.id 
                              WHERE qi.quote_id = ?");
        $stmt->execute([$id]);
        $quote['items'] = $stmt->fetchAll();
    }
    
    return $quote;
}

// Add item to quotation
function addQuoteItem($quote_id, $inventory_item_id, $quantity, $discount_percentage = 0) {
    global $pdo;
    
    try {
        // Get item details
        $stmt = $pdo->prepare("SELECT brand, model, base_price, selling_price, stock_quantity 
                              FROM inventory_items WHERE id = ? AND is_active = 1");
        $stmt->execute([$inventory_item_id]);
        $item = $stmt->fetch();
        
        if (!$item) return ['success' => false, 'message' => 'Item not found or has been removed'];
        
        // Check if item already exists in this quote
        $stmt = $pdo->prepare("SELECT id, quantity FROM quote_items WHERE quote_id = ? AND inventory_item_id = ?");
        $stmt->execute([$quote_id, $inventory_item_id]);
        $existing_item = $stmt->fetch();
        
        // Use base price if use_base_price parameter is true
        $use_base_price = isset($_POST['use_base_price']) && $_POST['use_base_price'] === 'true';
        $unit_price = $use_base_price ? $item['base_price'] : $item['selling_price'];
        $discount_amount = ($unit_price * $discount_percentage / 100) * $quantity;
        $total_amount = ($unit_price * $quantity) - $discount_amount;
        
        if ($existing_item) {
            // Update existing item
            $new_quantity = $existing_item['quantity'] + $quantity;
            $new_discount_amount = ($unit_price * $discount_percentage / 100) * $new_quantity;
            $new_total_amount = ($unit_price * $new_quantity) - $new_discount_amount;
            
            $stmt = $pdo->prepare("UPDATE quote_items SET 
                                  quantity = ?, discount_percentage = ?, discount_amount = ?, total_amount = ? 
                                  WHERE id = ?");
            $result = $stmt->execute([$new_quantity, $discount_percentage, $new_discount_amount, $new_total_amount, $existing_item['id']]);
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO quote_items 
                                  (quote_id, inventory_item_id, quantity, unit_price, 
                                   discount_percentage, discount_amount, total_amount) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $quote_id, $inventory_item_id, $quantity, $unit_price,
                $discount_percentage, $discount_amount, $total_amount
            ]);
        }
        
        if ($result) {
            updateQuoteTotals($quote_id);
            return ['success' => true, 'message' => 'Item added successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to add item'];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Get or create a labor fee inventory item
function getLaborFeeItem() {
    global $pdo;
    
    try {
        // First check if labor fee item exists
        $stmt = $pdo->prepare("SELECT id FROM inventory_items WHERE brand = 'LABOR' AND model = 'Labor Fee' LIMIT 1");
        $stmt->execute();
        $item = $stmt->fetch();
        
        if ($item) {
            return $item['id'];
        }
        
        // Create labor fee item if it doesn't exist
        $stmt = $pdo->prepare("INSERT INTO inventory_items 
                              (brand, model, category_id, size_specification, base_price, selling_price, 
                               supplier_id, stock_quantity, minimum_stock, description, is_active) 
                              VALUES ('LABOR', 'Labor Fee', 1, 'Per KW', 0, 0, NULL, 9999, 0, 'Labor fee calculation item', 1)");
        
        if ($stmt->execute()) {
            return $pdo->lastInsertId();
        }
        
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Add custom quote item (like labor fee) using a special inventory item
function addCustomQuoteItem($quote_id, $item_name, $quantity, $unit_price, $discount_percentage = 0) {
    global $pdo;
    
    try {
        // Get or create labor fee inventory item
        $labor_item_id = getLaborFeeItem();
        if (!$labor_item_id) {
            return ['success' => false, 'message' => 'Failed to create labor fee item'];
        }
        
        $discount_amount = ($unit_price * $discount_percentage / 100) * $quantity;
        $total_amount = ($unit_price * $quantity) - $discount_amount;
        
        $stmt = $pdo->prepare("INSERT INTO quote_items 
                              (quote_id, inventory_item_id, quantity, unit_price, 
                               discount_percentage, discount_amount, total_amount) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([
            $quote_id, $labor_item_id, $quantity, $unit_price,
            $discount_percentage, $discount_amount, $total_amount
        ]);
        
        if ($result) {
            updateQuoteTotals($quote_id);
            return ['success' => true, 'message' => 'Custom item added successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to add custom item'];
        }
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Remove item from quotation
function removeQuoteItem($quote_item_id) {
    global $pdo;
    
    try {
        // Get quote ID before deleting
        $stmt = $pdo->prepare("SELECT quote_id FROM quote_items WHERE id = ?");
        $stmt->execute([$quote_item_id]);
        $quote_id = $stmt->fetchColumn();
        
        // Delete the item
        $stmt = $pdo->prepare("DELETE FROM quote_items WHERE id = ?");
        $result = $stmt->execute([$quote_item_id]);
        
        if ($result && $quote_id) {
            updateQuoteTotals($quote_id);
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

// Update quote item quantity
function updateQuoteItemQuantity($quote_item_id, $new_quantity) {
    global $pdo;
    
    try {
        // Get current item details
        $stmt = $pdo->prepare("SELECT qi.quote_id, qi.unit_price, qi.discount_percentage, 
                              qi.inventory_item_id, i.brand, i.model, i.is_active
                              FROM quote_items qi
                              LEFT JOIN inventory_items i ON qi.inventory_item_id = i.id
                              WHERE qi.id = ?");
        $stmt->execute([$quote_item_id]);
        $item = $stmt->fetch();
        
        if (!$item) return ['success' => false, 'message' => 'Item not found'];
        
        if (!$item['is_active']) {
            return ['success' => false, 'message' => "Item {$item['brand']} {$item['model']} has been removed from inventory"];
        }
        
        $discount_amount = ($item['unit_price'] * $item['discount_percentage'] / 100) * $new_quantity;
        $total_amount = ($item['unit_price'] * $new_quantity) - $discount_amount;
        
        // Update the item
        $stmt = $pdo->prepare("UPDATE quote_items SET 
                              quantity = ?, discount_amount = ?, total_amount = ? 
                              WHERE id = ?");
        
        $result = $stmt->execute([$new_quantity, $discount_amount, $total_amount, $quote_item_id]);
        
        if ($result) {
            updateQuoteTotals($item['quote_id']);
            return ['success' => true, 'message' => 'Quantity updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update quantity'];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Update quotation totals
function updateQuoteTotals($quote_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT 
                              SUM(unit_price * quantity) as subtotal,
                              SUM(discount_amount) as total_discount,
                              SUM(total_amount) as total_amount
                              FROM quote_items WHERE quote_id = ?");
        $stmt->execute([$quote_id]);
        $totals = $stmt->fetch();
        
        $stmt = $pdo->prepare("UPDATE quotations SET 
                              subtotal = ?, total_discount = ?, total_amount = ? 
                              WHERE id = ?");
        
        return $stmt->execute([
            $totals['subtotal'] ?: 0,
            $totals['total_discount'] ?: 0,
            $totals['total_amount'] ?: 0,
            $quote_id
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Update quotation status
function updateQuoteStatus($quote_id, $new_status) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE quotations SET status = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$new_status, $quote_id]);
        
        // If status is being set to 'accepted', deduct inventory and convert to solar project
        if ($result && $new_status === 'accepted') {
            // First deduct inventory
            $inventory_result = deductQuoteInventory($quote_id);
            if (!$inventory_result['success']) {
                // If inventory deduction fails, revert the status update
                $stmt = $pdo->prepare("UPDATE quotations SET status = 'draft', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$quote_id]);
                return ['success' => false, 'message' => $inventory_result['message'], 'inventory_error' => true];
            }
            
            // Then convert to solar project
            $conversion_result = convertQuotationToProject($quote_id);
            if (!$conversion_result['success']) {
                // Log error but don't fail the status update since inventory was already deducted
                error_log("Failed to convert quotation $quote_id to project: " . $conversion_result['message']);
            }
            
            return ['success' => true, 'inventory_result' => $inventory_result];
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

// Convert quotation to solar project
function convertQuotationToProject($quote_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get quotation details
        $quote = getQuote($quote_id);
        if (!$quote) {
            throw new Exception("Quotation not found");
        }
        
        // Prepare project data from quotation
        $project_data = [
            'project_name' => $quote['proposal_name'] ?? $quote['quote_number'] . ' - Solar Project',
            'customer_name' => $quote['customer_name'],
            'customer_email' => '', // quotations table doesn't have email field
            'customer_phone' => $quote['customer_phone'],
            'customer_address' => '', // quotations table doesn't have address field
            'remarks' => 'Converted from quotation ' . $quote['quote_number'],
            'system_size_kw' => calculateSystemSize($quote['items']),
            'quote_id' => $quote_id, // Link back to quotation
            'project_status' => 'approved', // Set as approved since quotation was accepted
        ];
        
        // Create solar project
        include_once 'projects.php';
        $project_id = createSolarProject($project_data);
        
        if (!$project_id) {
            throw new Exception("Failed to create solar project");
        }
        
        // Copy quote items to project items
        foreach ($quote['items'] as $item) {
            $stmt = $pdo->prepare("INSERT INTO solar_project_items 
                                  (project_id, inventory_item_id, quantity, unit_base_price, unit_selling_price, discount_amount, total_amount) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $project_id,
                $item['inventory_item_id'],
                $item['quantity'],
                $item['base_price'] ?? $item['current_price'], // Use base price if available
                $item['unit_price'],
                $item['discount_amount'],
                $item['total_amount']
            ]);
        }
        
        // Update quotation to link to project (if column exists)
        try {
            $stmt = $pdo->prepare("UPDATE quotations SET project_id = ? WHERE id = ?");
            $stmt->execute([$project_id, $quote_id]);
        } catch(PDOException $e) {
            // Column might not exist yet, continue without failing
            error_log("Could not update quotation project_id: " . $e->getMessage());
        }
        
        // Update project totals
        updateProjectTotals($project_id);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'project_id' => $project_id,
            'message' => 'Quotation successfully converted to solar project'
        ];
        
    } catch(Exception $e) {
        $pdo->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Calculate system size from solar panels in quote items
function calculateSystemSize($items) {
    $total_watts = 0;
    
    foreach ($items as $item) {
        // Check if item is a solar panel (category_id = 1)
        if ($item['category_name'] === 'Solar Panels') {
            // Extract wattage from size_specification (e.g., "550w" -> 550)
            $wattage = 0;
            if (preg_match('/(\d+)w/i', $item['size_specification'], $matches)) {
                $wattage = intval($matches[1]);
            }
            $total_watts += $wattage * $item['quantity'];
        }
    }
    
    // Convert watts to kilowatts
    return $total_watts / 1000;
}

// Delete quotation
function deleteQuote($quote_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Delete quote items first
        $stmt = $pdo->prepare("DELETE FROM quote_items WHERE quote_id = ?");
        $stmt->execute([$quote_id]);
        
        // Delete quotation
        $stmt = $pdo->prepare("DELETE FROM quotations WHERE id = ?");
        $result = $stmt->execute([$quote_id]);
        
        $pdo->commit();
        return $result;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

// Update quote item discount percentage
function updateQuoteItemDiscount($quote_item_id, $new_discount_percentage) {
    global $pdo;
    
    try {
        // Get current item details
        $stmt = $pdo->prepare("SELECT qi.quote_id, qi.unit_price, qi.quantity, 
                              qi.inventory_item_id, i.brand, i.model, i.is_active
                              FROM quote_items qi
                              LEFT JOIN inventory_items i ON qi.inventory_item_id = i.id
                              WHERE qi.id = ?");
        $stmt->execute([$quote_item_id]);
        $item = $stmt->fetch();
        
        if (!$item) return ['success' => false, 'message' => 'Item not found'];
        
        if (!$item['is_active']) {
            return ['success' => false, 'message' => "Item {$item['brand']} {$item['model']} has been removed from inventory"];
        }
        
        // Validate discount percentage
        if ($new_discount_percentage < 0 || $new_discount_percentage > 100) {
            return ['success' => false, 'message' => 'Discount percentage must be between 0 and 100'];
        }
        
        $discount_amount = ($item['unit_price'] * $new_discount_percentage / 100) * $item['quantity'];
        $total_amount = ($item['unit_price'] * $item['quantity']) - $discount_amount;
        
        // Update the item
        $stmt = $pdo->prepare("UPDATE quote_items SET 
                              discount_percentage = ?, discount_amount = ?, total_amount = ? 
                              WHERE id = ?");
        
        $result = $stmt->execute([$new_discount_percentage, $discount_amount, $total_amount, $quote_item_id]);
        
        if ($result) {
            updateQuoteTotals($item['quote_id']);
            return ['success' => true, 'message' => 'Discount updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update discount'];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Get available inventory items for quotes (all active items)
function getQuoteInventoryItems() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT i.*, c.name as category_name 
                          FROM inventory_items i 
                          LEFT JOIN categories c ON i.category_id = c.id 
                          WHERE i.is_active = 1 
                          ORDER BY i.brand, i.model");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get quote with profit data for profit analysis
function getQuoteWithProfitData($id) {
    global $pdo;
    
    // Get quote details
    $stmt = $pdo->prepare("SELECT q.*, u.full_name as created_by_name 
                          FROM quotations q 
                          LEFT JOIN users u ON q.created_by = u.id 
                          WHERE q.id = ?");
    $stmt->execute([$id]);
    $quote = $stmt->fetch();
    
    if ($quote) {
        // Get quote items with base price for profit calculation
        $stmt = $pdo->prepare("SELECT qi.*, i.brand, i.model, i.size_specification, 
                              i.base_price, i.selling_price as current_price,
                              c.name as category_name, i.stock_quantity
                              FROM quote_items qi 
                              LEFT JOIN inventory_items i ON qi.inventory_item_id = i.id 
                              LEFT JOIN categories c ON i.category_id = c.id 
                              WHERE qi.quote_id = ?");
        $stmt->execute([$id]);
        $quote['items'] = $stmt->fetchAll();
    }
    
    return $quote;
}

// Check if quotation has an active installment plan
function hasInstallmentPlan($quotation_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM installment_plans 
                          WHERE quotation_id = ? AND status = 'active'");
    $stmt->execute([$quotation_id]);
    $result = $stmt->fetch();
    
    return $result['count'] > 0;
}

// Get installment status for a quotation
function getInstallmentStatus($quotation_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT installment_status FROM quotations WHERE id = ?");
    $stmt->execute([$quotation_id]);
    $result = $stmt->fetch();
    
    return $result ? $result['installment_status'] : null;
}

// Deduct inventory items when quote is approved
function deductQuoteInventory($quote_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get all items in this quote
        $stmt = $pdo->prepare("SELECT qi.inventory_item_id, qi.quantity, i.brand, i.model, i.stock_quantity
                              FROM quote_items qi 
                              LEFT JOIN inventory_items i ON qi.inventory_item_id = i.id 
                              WHERE qi.quote_id = ? AND qi.inventory_item_id IS NOT NULL");
        $stmt->execute([$quote_id]);
        $quote_items = $stmt->fetchAll();
        
        $deducted_items = [];
        $insufficient_stock_items = [];
        
        foreach ($quote_items as $item) {
            $inventory_item_id = $item['inventory_item_id'];
            $quantity_needed = $item['quantity'];
            $current_stock = $item['stock_quantity'];
            
            // Check if we have enough stock
            if ($current_stock < $quantity_needed) {
                $insufficient_stock_items[] = [
                    'item' => $item['brand'] . ' ' . $item['model'],
                    'available' => $current_stock,
                    'needed' => $quantity_needed
                ];
                continue;
            }
            
            // Update the stock
            $new_stock = $current_stock - $quantity_needed;
            $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_stock, $inventory_item_id]);
            
            // Record stock movement
            $stmt = $pdo->prepare("INSERT INTO stock_movements 
                                  (inventory_item_id, movement_type, quantity, previous_stock, new_stock, 
                                   reference_type, reference_id, notes, created_by) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $inventory_item_id, 
                'out', 
                $quantity_needed, 
                $current_stock, 
                $new_stock, 
                'quotation', 
                $quote_id, 
                "Inventory deducted for approved quotation", 
                $_SESSION['user_id'] ?? 1
            ]);
            
            $deducted_items[] = [
                'item' => $item['brand'] . ' ' . $item['model'],
                'quantity' => $quantity_needed,
                'new_stock' => $new_stock
            ];
        }
        
        // If there are insufficient stock items, rollback and return error
        if (!empty($insufficient_stock_items)) {
            $pdo->rollback();
            $error_message = "Insufficient stock for the following items:\n";
            foreach ($insufficient_stock_items as $item) {
                $error_message .= "- {$item['item']}: Available {$item['available']}, Needed {$item['needed']}\n";
            }
            return ['success' => false, 'message' => $error_message, 'insufficient_items' => $insufficient_stock_items];
        }
        
        $pdo->commit();
        return [
            'success' => true, 
            'message' => 'Inventory deducted successfully',
            'deducted_items' => $deducted_items
        ];
        
    } catch(Exception $e) {
        $pdo->rollback();
        error_log("Quote inventory deduction failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to deduct inventory: ' . $e->getMessage()];
    }
}

// Restore inventory items when quote status is reverted from accepted
function restoreQuoteInventory($quote_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get all stock movements for this quote that were deductions
        $stmt = $pdo->prepare("SELECT sm.*, i.brand, i.model 
                              FROM stock_movements sm 
                              LEFT JOIN inventory_items i ON sm.inventory_item_id = i.id 
                              WHERE sm.reference_type = 'quotation' 
                              AND sm.reference_id = ? 
                              AND sm.movement_type = 'out'");
        $stmt->execute([$quote_id]);
        $movements = $stmt->fetchAll();
        
        $restored_items = [];
        
        foreach ($movements as $movement) {
            $inventory_item_id = $movement['inventory_item_id'];
            $quantity_to_restore = $movement['quantity'];
            
            // Get current stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM inventory_items WHERE id = ?");
            $stmt->execute([$inventory_item_id]);
            $current_stock = $stmt->fetchColumn();
            
            // Restore the stock
            $new_stock = $current_stock + $quantity_to_restore;
            $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_stock, $inventory_item_id]);
            
            // Record stock movement for restoration
            $stmt = $pdo->prepare("INSERT INTO stock_movements 
                                  (inventory_item_id, movement_type, quantity, previous_stock, new_stock, 
                                   reference_type, reference_id, notes, created_by) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $inventory_item_id, 
                'in', 
                $quantity_to_restore, 
                $current_stock, 
                $new_stock, 
                'quotation_restore', 
                $quote_id, 
                "Inventory restored - quote status reverted", 
                $_SESSION['user_id'] ?? 1
            ]);
            
            $restored_items[] = [
                'item' => $movement['brand'] . ' ' . $movement['model'],
                'quantity' => $quantity_to_restore,
                'new_stock' => $new_stock
            ];
        }
        
        $pdo->commit();
        return [
            'success' => true, 
            'message' => 'Inventory restored successfully',
            'restored_items' => $restored_items
        ];
        
    } catch(Exception $e) {
        $pdo->rollback();
        error_log("Quote inventory restoration failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to restore inventory: ' . $e->getMessage()];
    }
}

// Save customer information for a quote
function saveCustomerInfo($quote_id, $data) {
    global $pdo;
    
    try {
        // Check if customer info already exists for this quote
        $stmt = $pdo->prepare("SELECT id FROM quote_customer_info WHERE quote_id = ?");
        $stmt->execute([$quote_id]);
        $existing = $stmt->fetch();
        
        $contact_methods = $data['contact_method'] ?? [];
        
        if ($existing) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE quote_customer_info SET 
                                  full_name = ?, phone_number = ?, address = ?, 
                                  contact_method_email = ?, contact_method_phone = ?, contact_method_sms = ?,
                                  account_creation_date = ?, updated_at = NOW()
                                  WHERE quote_id = ?");
            $stmt->execute([
                $data['full_name'] ?? null,
                $data['phone_number'] ?? null,
                $data['address'] ?? null,
                in_array('email', $contact_methods) ? 1 : 0,
                in_array('phone', $contact_methods) ? 1 : 0,
                in_array('sms', $contact_methods) ? 1 : 0,
                $data['account_creation_date'] ?? null,
                $quote_id
            ]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO quote_customer_info 
                                  (quote_id, full_name, phone_number, address, 
                                   contact_method_email, contact_method_phone, contact_method_sms, account_creation_date) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $quote_id,
                $data['full_name'] ?? null,
                $data['phone_number'] ?? null,
                $data['address'] ?? null,
                in_array('email', $contact_methods) ? 1 : 0,
                in_array('phone', $contact_methods) ? 1 : 0,
                in_array('sms', $contact_methods) ? 1 : 0,
                $data['account_creation_date'] ?? null
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving customer info: " . $e->getMessage());
        return false;
    }
}

// Save solar project details for a quote
function saveSolarProjectDetails($quote_id, $data) {
    global $pdo;
    
    try {
        // Check if solar details already exist for this quote
        $stmt = $pdo->prepare("SELECT id FROM quote_solar_details WHERE quote_id = ?");
        $stmt->execute([$quote_id]);
        $existing = $stmt->fetch();
        
        $system_types = $data['system_type'] ?? [];
        $installation_types = $data['installation_type'] ?? [];
        $installation_statuses = $data['installation_status'] ?? [];
        
        if ($existing) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE quote_solar_details SET 
                                  system_type_grid_tie = ?, system_type_off_grid = ?, system_type_hybrid = ?,
                                  system_size_kw = ?, 
                                  installation_type_rooftop = ?, installation_type_ground_mounted = ?, installation_type_carport = ?,
                                  panel_brand_model = ?, inverter_brand_model = ?, estimated_installation_date = ?,
                                  installation_status_planned = ?, installation_status_in_progress = ?, 
                                  installation_status_completed = ?, installation_status_maintenance = ?,
                                  battery_backup_capacity = ?, battery_capacity_value = ?, net_metering = ?, confirmed = ?, client_signature = ?, remarks = ?, updated_at = NOW()
                                  WHERE quote_id = ?");
            $stmt->execute([
                in_array('grid_tie', $system_types) ? 1 : 0,
                in_array('off_grid', $system_types) ? 1 : 0,
                in_array('hybrid', $system_types) ? 1 : 0,
                $data['system_size'] ?? null,
                in_array('rooftop', $installation_types) ? 1 : 0,
                in_array('ground_mounted', $installation_types) ? 1 : 0,
                in_array('carport', $installation_types) ? 1 : 0,
                $data['panel_brand_model'] ?? null,
                $data['inverter_brand_model'] ?? null,
                $data['estimated_installation_date'] ?? null,
                in_array('planned', $installation_statuses) ? 1 : 0,
                in_array('in_progress', $installation_statuses) ? 1 : 0,
                in_array('completed', $installation_statuses) ? 1 : 0,
                in_array('maintenance', $installation_statuses) ? 1 : 0,
                $data['battery_backup_capacity'] ?? null,
                $data['battery_capacity_value'] ?? null,
                $data['net_metering'] ?? null,
                $data['confirmed'] ?? null,
                $data['client_signature'] ?? null,
                $data['remarks'] ?? null,
                $quote_id
            ]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO quote_solar_details 
                                  (quote_id, system_type_grid_tie, system_type_off_grid, system_type_hybrid,
                                   system_size_kw, installation_type_rooftop, installation_type_ground_mounted, installation_type_carport,
                                   panel_brand_model, inverter_brand_model, estimated_installation_date,
                                   installation_status_planned, installation_status_in_progress, 
                                   installation_status_completed, installation_status_maintenance,
                                   battery_backup_capacity, battery_capacity_value, net_metering, confirmed, client_signature, remarks) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $quote_id,
                in_array('grid_tie', $system_types) ? 1 : 0,
                in_array('off_grid', $system_types) ? 1 : 0,
                in_array('hybrid', $system_types) ? 1 : 0,
                $data['system_size'] ?? null,
                in_array('rooftop', $installation_types) ? 1 : 0,
                in_array('ground_mounted', $installation_types) ? 1 : 0,
                in_array('carport', $installation_types) ? 1 : 0,
                $data['panel_brand_model'] ?? null,
                $data['inverter_brand_model'] ?? null,
                $data['estimated_installation_date'] ?? null,
                in_array('planned', $installation_statuses) ? 1 : 0,
                in_array('in_progress', $installation_statuses) ? 1 : 0,
                in_array('completed', $installation_statuses) ? 1 : 0,
                in_array('maintenance', $installation_statuses) ? 1 : 0,
                $data['battery_backup_capacity'] ?? null,
                $data['battery_capacity_value'] ?? null,
                $data['net_metering'] ?? null,
                $data['confirmed'] ?? null,
                $data['client_signature'] ?? null,
                $data['remarks'] ?? null
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving solar project details: " . $e->getMessage());
        return false;
    }
}

// Get customer information for a quote
function getCustomerInfo($quote_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM quote_customer_info WHERE quote_id = ?");
    $stmt->execute([$quote_id]);
    return $stmt->fetch();
}

// Get solar project details for a quote
function getSolarProjectDetails($quote_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM quote_solar_details WHERE quote_id = ?");
    $stmt->execute([$quote_id]);
    return $stmt->fetch();
}
?>
