<?php
require_once 'config.php';

// Get all POS sales
function getPOSSales($status = null, $date_from = null, $date_to = null) {
    global $pdo;
    
    $sql = "SELECT s.*, u.full_name as cashier_name,
            (SELECT COUNT(*) FROM pos_sale_items WHERE sale_id = s.id) as items_count
            FROM pos_sales s 
            LEFT JOIN users u ON s.created_by = u.id 
            WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $sql .= " AND s.status = ?";
        $params[] = $status;
    }
    
    if ($date_from) {
        $sql .= " AND DATE(s.created_at) >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $sql .= " AND DATE(s.created_at) <= ?";
        $params[] = $date_to;
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get single POS sale with items
function getPOSSale($id) {
    global $pdo;
    
    // Get sale details
    $stmt = $pdo->prepare("SELECT s.*, u.full_name as cashier_name 
                          FROM pos_sales s 
                          LEFT JOIN users u ON s.created_by = u.id 
                          WHERE s.id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();
    
    if ($sale) {
        // Get sale items
        $stmt = $pdo->prepare("SELECT si.*, i.brand, i.model, i.size_specification, 
                              c.name as category_name, i.stock_quantity
                              FROM pos_sale_items si 
                              LEFT JOIN inventory_items i ON si.inventory_item_id = i.id 
                              LEFT JOIN categories c ON i.category_id = c.id 
                              WHERE si.sale_id = ?");
        $stmt->execute([$id]);
        $sale['items'] = $stmt->fetchAll();
    }
    
    return $sale;
}

// Create new POS sale
function createPOSSale($customer_name = null, $customer_phone = null) {
    global $pdo;
    
    try {
        $receipt_number = generateReceiptNumber();
        
        $stmt = $pdo->prepare("INSERT INTO pos_sales 
                              (receipt_number, customer_name, customer_phone, status, created_by) 
                              VALUES (?, ?, ?, 'pending', ?)");
        
        $stmt->execute([
            $receipt_number, $customer_name, $customer_phone, $_SESSION['user_id']
        ]);
        
        return $pdo->lastInsertId();
    } catch(PDOException $e) {
        return false;
    }
}

// Generate unique receipt number
function generateReceiptNumber() {
    return 'RCP' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Add item to POS sale
function addPOSSaleItem($sale_id, $inventory_item_id, $quantity, $discount_percentage = 0) {
    global $pdo;
    
    try {
        // Get item details
        $stmt = $pdo->prepare("SELECT base_price, selling_price, stock_quantity, brand, model 
                              FROM inventory_items WHERE id = ?");
        $stmt->execute([$inventory_item_id]);
        $item = $stmt->fetch();
        
        if (!$item) return ['success' => false, 'message' => 'Item not found'];
        
        // Check stock availability
        if ($item['stock_quantity'] < $quantity) {
            return ['success' => false, 'message' => "Insufficient stock for {$item['brand']} {$item['model']}. Available: {$item['stock_quantity']}"];
        }
        
        // Check if item already exists in this sale
        $stmt = $pdo->prepare("SELECT id, quantity FROM pos_sale_items WHERE sale_id = ? AND inventory_item_id = ?");
        $stmt->execute([$sale_id, $inventory_item_id]);
        $existing_item = $stmt->fetch();
        
        $unit_price = $item['selling_price'];
        $discount_amount = ($unit_price * $discount_percentage / 100) * $quantity;
        $total_amount = ($unit_price * $quantity) - $discount_amount;
        
        if ($existing_item) {
            // Update existing item
            $new_quantity = $existing_item['quantity'] + $quantity;
            $new_discount_amount = ($unit_price * $discount_percentage / 100) * $new_quantity;
            $new_total_amount = ($unit_price * $new_quantity) - $new_discount_amount;
            
            // Check total stock for updated quantity
            if ($item['stock_quantity'] < $new_quantity) {
                return ['success' => false, 'message' => "Insufficient stock for total quantity {$new_quantity}. Available: {$item['stock_quantity']}"];
            }
            
            $stmt = $pdo->prepare("UPDATE pos_sale_items SET 
                                  quantity = ?, discount_percentage = ?, discount_amount = ?, total_amount = ? 
                                  WHERE id = ?");
            $result = $stmt->execute([$new_quantity, $discount_percentage, $new_discount_amount, $new_total_amount, $existing_item['id']]);
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO pos_sale_items 
                                  (sale_id, inventory_item_id, quantity, unit_price, 
                                   discount_percentage, discount_amount, total_amount) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $sale_id, $inventory_item_id, $quantity, $unit_price,
                $discount_percentage, $discount_amount, $total_amount
            ]);
        }
        
        if ($result) {
            updatePOSSaleTotals($sale_id);
            return ['success' => true, 'message' => 'Item added successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to add item'];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Remove item from POS sale
function removePOSSaleItem($sale_item_id) {
    global $pdo;
    
    try {
        // Get sale ID before deleting
        $stmt = $pdo->prepare("SELECT sale_id FROM pos_sale_items WHERE id = ?");
        $stmt->execute([$sale_item_id]);
        $sale_id = $stmt->fetchColumn();
        
        // Delete the item
        $stmt = $pdo->prepare("DELETE FROM pos_sale_items WHERE id = ?");
        $result = $stmt->execute([$sale_item_id]);
        
        if ($result && $sale_id) {
            updatePOSSaleTotals($sale_id);
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

// Update POS sale item quantity
function updatePOSSaleItemQuantity($sale_item_id, $new_quantity) {
    global $pdo;
    
    try {
        // Get current item details
        $stmt = $pdo->prepare("SELECT si.sale_id, si.unit_price, si.discount_percentage, 
                              si.inventory_item_id, i.stock_quantity, i.brand, i.model
                              FROM pos_sale_items si
                              LEFT JOIN inventory_items i ON si.inventory_item_id = i.id
                              WHERE si.id = ?");
        $stmt->execute([$sale_item_id]);
        $item = $stmt->fetch();
        
        if (!$item) return ['success' => false, 'message' => 'Item not found'];
        
        // Check stock availability
        if ($item['stock_quantity'] < $new_quantity) {
            return ['success' => false, 'message' => "Insufficient stock for {$item['brand']} {$item['model']}. Available: {$item['stock_quantity']}"];
        }
        
        $discount_amount = ($item['unit_price'] * $item['discount_percentage'] / 100) * $new_quantity;
        $total_amount = ($item['unit_price'] * $new_quantity) - $discount_amount;
        
        // Update the item
        $stmt = $pdo->prepare("UPDATE pos_sale_items SET 
                              quantity = ?, discount_amount = ?, total_amount = ? 
                              WHERE id = ?");
        
        $result = $stmt->execute([$new_quantity, $discount_amount, $total_amount, $sale_item_id]);
        
        if ($result) {
            updatePOSSaleTotals($item['sale_id']);
            return ['success' => true, 'message' => 'Quantity updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update quantity'];
        
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Update POS sale totals
function updatePOSSaleTotals($sale_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT 
                              SUM(unit_price * quantity) as subtotal,
                              SUM(discount_amount) as total_discount,
                              SUM(total_amount) as total_amount
                              FROM pos_sale_items WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        $totals = $stmt->fetch();
        
        $stmt = $pdo->prepare("UPDATE pos_sales SET 
                              subtotal = ?, total_discount = ?, total_amount = ? 
                              WHERE id = ?");
        
        return $stmt->execute([
            $totals['subtotal'] ?: 0,
            $totals['total_discount'] ?: 0,
            $totals['total_amount'] ?: 0,
            $sale_id
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Complete POS sale (process payment and deduct inventory)
function completePOSSale($sale_id, $payment_method, $amount_paid, $customer_name = null, $customer_phone = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get sale details
        $sale = getPOSSale($sale_id);
        if (!$sale || $sale['status'] === 'completed') {
            $pdo->rollback();
            return ['success' => false, 'message' => 'Sale not found or already completed'];
        }
        
        // Check if payment amount is sufficient
        if ($amount_paid < $sale['total_amount']) {
            $pdo->rollback();
            return ['success' => false, 'message' => 'Insufficient payment amount'];
        }
        
        $change_amount = $amount_paid - $sale['total_amount'];
        
        // Check inventory availability for all items
        foreach ($sale['items'] as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                $pdo->rollback();
                return ['success' => false, 'message' => "Insufficient stock for {$item['brand']} {$item['model']}"];
            }
        }
        
        // Deduct inventory for each item
        foreach ($sale['items'] as $item) {
            $inventory_item_id = $item['inventory_item_id'];
            $quantity_sold = $item['quantity'];
            
            // Get current stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM inventory_items WHERE id = ?");
            $stmt->execute([$inventory_item_id]);
            $current_stock = $stmt->fetchColumn();
            
            // Update the stock
            $new_stock = $current_stock - $quantity_sold;
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
                $quantity_sold, 
                $current_stock, 
                $new_stock, 
                'pos_sale', 
                $sale_id, 
                "POS sale - " . $sale['receipt_number'], 
                $_SESSION['user_id']
            ]);
        }
        
        // Update sale record
        $stmt = $pdo->prepare("UPDATE pos_sales SET 
                              customer_name = ?, customer_phone = ?, status = 'completed', 
                              payment_method = ?, amount_paid = ?, change_amount = ?, 
                              completed_at = NOW() 
                              WHERE id = ?");
        
        $stmt->execute([
            $customer_name, $customer_phone, $payment_method, 
            $amount_paid, $change_amount, $sale_id
        ]);
        
        $pdo->commit();
        return [
            'success' => true, 
            'message' => 'Sale completed successfully',
            'change_amount' => $change_amount,
            'receipt_number' => $sale['receipt_number']
        ];
        
    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        return ['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()];
    }
}

// Cancel POS sale
function cancelPOSSale($sale_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Delete sale items first
        $stmt = $pdo->prepare("DELETE FROM pos_sale_items WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        
        // Delete sale
        $stmt = $pdo->prepare("DELETE FROM pos_sales WHERE id = ? AND status = 'pending'");
        $result = $stmt->execute([$sale_id]);
        
        $pdo->commit();
        return $result;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

// Get POS statistics
function getPOSStats($date_from = null, $date_to = null) {
    global $pdo;
    
    $stats = [];
    $date_condition = "";
    $params = [];
    
    if ($date_from && $date_to) {
        $date_condition = " AND DATE(created_at) BETWEEN ? AND ?";
        $params = [$date_from, $date_to];
    } elseif ($date_from) {
        $date_condition = " AND DATE(created_at) >= ?";
        $params = [$date_from];
    } elseif ($date_to) {
        $date_condition = " AND DATE(created_at) <= ?";
        $params = [$date_to];
    }
    
    // Total sales
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total_amount) as total 
                          FROM pos_sales WHERE status = 'completed'" . $date_condition);
    $stmt->execute($params);
    $totals = $stmt->fetch();
    $stats['total_sales'] = $totals['count'] ?: 0;
    $stats['total_revenue'] = $totals['total'] ?: 0;
    
    // Sales by payment method
    $stmt = $pdo->prepare("SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total 
                          FROM pos_sales WHERE status = 'completed'" . $date_condition . " 
                          GROUP BY payment_method");
    $stmt->execute($params);
    $stats['by_payment_method'] = $stmt->fetchAll();
    
    // Today's sales
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total_amount) as total 
                          FROM pos_sales WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
    $stmt->execute();
    $today = $stmt->fetch();
    $stats['today_sales'] = $today['count'] ?: 0;
    $stats['today_revenue'] = $today['total'] ?: 0;
    
    return $stats;
}

// Get available inventory items for POS (items with stock > 0)
function getPOSInventoryItems() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT i.*, c.name as category_name 
                          FROM inventory_items i 
                          LEFT JOIN categories c ON i.category_id = c.id 
                          WHERE i.stock_quantity > 0 
                          ORDER BY i.brand, i.model");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
