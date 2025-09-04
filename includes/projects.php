<?php
require_once 'config.php';

// Get all solar projects
function getSolarProjects($status = null) {
    global $pdo;
    
    $sql = "SELECT p.*, u.full_name as created_by_name 
            FROM solar_projects p 
            LEFT JOIN users u ON p.created_by = u.id 
            WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $sql .= " AND p.project_status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get single solar project with items
function getSolarProject($id) {
    global $pdo;
    
    // Get project details
    $stmt = $pdo->prepare("SELECT p.*, u.full_name as created_by_name 
                          FROM solar_projects p 
                          LEFT JOIN users u ON p.created_by = u.id 
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    
    if ($project) {
        // Get project items
        $stmt = $pdo->prepare("SELECT pi.*, i.brand, i.model, i.size_specification, c.name as category_name 
                              FROM solar_project_items pi 
                              LEFT JOIN inventory_items i ON pi.inventory_item_id = i.id 
                              LEFT JOIN categories c ON i.category_id = c.id 
                              WHERE pi.project_id = ?");
        $stmt->execute([$id]);
        $project['items'] = $stmt->fetchAll();
    }
    
    return $project;
}

// Create new solar project
function createSolarProject($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO solar_projects 
                              (project_name, customer_name, customer_email, customer_phone, 
                               customer_address, remarks, system_size_kw, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['project_name'], $data['customer_name'], $data['customer_email'],
            $data['customer_phone'], $data['customer_address'], $data['remarks'] ?? '',
            $data['system_size_kw'], $_SESSION['user_id']
        ]);
        
        return $pdo->lastInsertId();
    } catch(PDOException $e) {
        return false;
    }
}

// Update solar project
function updateSolarProject($id, $data) {
    global $pdo;
    
    try {
        // First, get the current project status to check if we need to deduct inventory
        $stmt = $pdo->prepare("SELECT project_status FROM solar_projects WHERE id = ?");
        $stmt->execute([$id]);
        $current_project = $stmt->fetch();
        
        if (!$current_project) {
            return false;
        }
        
        $current_status = $current_project['project_status'];
        $new_status = $data['project_status'];
        
        // Check if status is changing to approved or completed
        $should_deduct_inventory = false;
        $should_restore_inventory = false;
        
        if (($current_status !== 'approved' && $current_status !== 'completed') && 
            ($new_status === 'approved' || $new_status === 'completed')) {
            $should_deduct_inventory = true;
        } elseif (($current_status === 'approved' || $current_status === 'completed') && 
                  ($new_status !== 'approved' && $new_status !== 'completed')) {
            $should_restore_inventory = true;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update the project
        $stmt = $pdo->prepare("UPDATE solar_projects SET 
                              project_name = ?, customer_name = ?, customer_email = ?, 
                              customer_phone = ?, customer_address = ?, remarks = ?, system_size_kw = ?, 
                              project_status = ? 
                              WHERE id = ?");
        
        $update_result = $stmt->execute([
            $data['project_name'], $data['customer_name'], $data['customer_email'],
            $data['customer_phone'], $data['customer_address'], $data['remarks'] ?? '',
            $data['system_size_kw'], $data['project_status'], $id
        ]);
        
        if (!$update_result) {
            $pdo->rollback();
            return false;
        }
        
        // If status changed to approved/completed, deduct inventory
        if ($should_deduct_inventory) {
            $deduction_result = deductProjectInventory($id);
            if (!$deduction_result) {
                $pdo->rollback();
                return false;
            }
        }
        
        // If status changed back from approved/completed, restore inventory
        if ($should_restore_inventory) {
            $restore_result = restoreProjectInventory($id);
            if (!$restore_result) {
                $pdo->rollback();
                return false;
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        return false;
    }
}

// Deduct inventory items when project is approved/completed
function deductProjectInventory($project_id) {
    global $pdo;
    
    try {
        // Get all items in this project
        $stmt = $pdo->prepare("SELECT inventory_item_id, quantity FROM solar_project_items WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $project_items = $stmt->fetchAll();
        
        foreach ($project_items as $item) {
            $inventory_item_id = $item['inventory_item_id'];
            $quantity_needed = $item['quantity'];
            
            // Get current stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM inventory_items WHERE id = ?");
            $stmt->execute([$inventory_item_id]);
            $current_stock = $stmt->fetchColumn();
            
            if ($current_stock === false) {
                throw new Exception("Inventory item not found: $inventory_item_id");
            }
            
            // Check if we have enough stock
            if ($current_stock < $quantity_needed) {
                // Get item details for error message
                $stmt = $pdo->prepare("SELECT brand, model FROM inventory_items WHERE id = ?");
                $stmt->execute([$inventory_item_id]);
                $item_details = $stmt->fetch();
                throw new Exception("Insufficient stock for {$item_details['brand']} {$item_details['model']}. Available: $current_stock, Needed: $quantity_needed");
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
                'project', 
                $project_id, 
                "Inventory deducted for approved/completed project", 
                $_SESSION['user_id']
            ]);
        }
        
        return true;
        
    } catch(Exception $e) {
        error_log("Inventory deduction failed: " . $e->getMessage());
        return false;
    }
}

// Restore inventory items when project status is changed back from approved/completed
function restoreProjectInventory($project_id) {
    global $pdo;
    
    try {
        // Get all items in this project
        $stmt = $pdo->prepare("SELECT inventory_item_id, quantity FROM solar_project_items WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $project_items = $stmt->fetchAll();
        
        foreach ($project_items as $item) {
            $inventory_item_id = $item['inventory_item_id'];
            $quantity_to_restore = $item['quantity'];
            
            // Get current stock
            $stmt = $pdo->prepare("SELECT stock_quantity FROM inventory_items WHERE id = ?");
            $stmt->execute([$inventory_item_id]);
            $current_stock = $stmt->fetchColumn();
            
            if ($current_stock === false) {
                throw new Exception("Inventory item not found: $inventory_item_id");
            }
            
            // Restore the stock
            $new_stock = $current_stock + $quantity_to_restore;
            $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_stock, $inventory_item_id]);
            
            // Record stock movement
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
                'project', 
                $project_id, 
                "Inventory restored from project status change", 
                $_SESSION['user_id']
            ]);
        }
        
        return true;
        
    } catch(Exception $e) {
        error_log("Inventory restoration failed: " . $e->getMessage());
        return false;
    }
}

// Check if enough inventory is available for a project
function checkProjectInventoryAvailability($project_id) {
    global $pdo;
    
    try {
        // Get all items in this project
        $stmt = $pdo->prepare("SELECT pi.inventory_item_id, pi.quantity, i.brand, i.model, i.stock_quantity 
                              FROM solar_project_items pi 
                              LEFT JOIN inventory_items i ON pi.inventory_item_id = i.id 
                              WHERE pi.project_id = ?");
        $stmt->execute([$project_id]);
        $project_items = $stmt->fetchAll();
        
        $insufficient_items = [];
        
        foreach ($project_items as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                $insufficient_items[] = "{$item['brand']} {$item['model']} (Available: {$item['stock_quantity']}, Needed: {$item['quantity']})";
            }
        }
        
        if (empty($insufficient_items)) {
            return ['available' => true, 'message' => 'All items available'];
        } else {
            return [
                'available' => false, 
                'message' => 'Insufficient stock for: ' . implode(', ', $insufficient_items)
            ];
        }
        
    } catch(Exception $e) {
        return ['available' => false, 'message' => 'Error checking inventory: ' . $e->getMessage()];
    }
}

// Add item to solar project
function addProjectItem($project_id, $inventory_item_id, $quantity) {
    global $pdo;
    
    try {
        // Check stock availability first - only for active items
        $stmt = $pdo->prepare("SELECT stock_quantity, base_price, selling_price, discount_percentage FROM inventory_items WHERE id = ? AND is_active = 1");
        $stmt->execute([$inventory_item_id]);
        $item = $stmt->fetch();
        
        if (!$item) return false;
        
        // Check if there's enough stock
        if ($item['stock_quantity'] < $quantity) {
            return false; // Insufficient stock
        }
        
        $unit_base_price = $item['base_price'];
        $unit_selling_price = $item['selling_price'];
        $discount_amount = ($unit_selling_price * $item['discount_percentage'] / 100) * $quantity;
        $total_amount = ($unit_selling_price * $quantity) - $discount_amount;
        
        // Add project item
        $stmt = $pdo->prepare("INSERT INTO solar_project_items 
                              (project_id, inventory_item_id, quantity, unit_base_price, 
                               unit_selling_price, discount_amount, total_amount) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([
            $project_id, $inventory_item_id, $quantity, $unit_base_price,
            $unit_selling_price, $discount_amount, $total_amount
        ]);
        
        if ($result) {
            updateProjectTotals($project_id);
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

// Remove item from solar project
function removeProjectItem($project_item_id) {
    global $pdo;
    
    try {
        // Get project ID before deleting
        $stmt = $pdo->prepare("SELECT project_id FROM solar_project_items WHERE id = ?");
        $stmt->execute([$project_item_id]);
        $project_id = $stmt->fetchColumn();
        
        // Delete the item
        $stmt = $pdo->prepare("DELETE FROM solar_project_items WHERE id = ?");
        $result = $stmt->execute([$project_item_id]);
        
        if ($result && $project_id) {
            updateProjectTotals($project_id);
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

// Update project item quantity
function updateProjectItemQuantity($project_item_id, $new_quantity) {
    global $pdo;
    
    try {
        // Get current item details
        $stmt = $pdo->prepare("SELECT project_id, unit_selling_price, unit_base_price, 
                              inventory_item_id FROM solar_project_items WHERE id = ?");
        $stmt->execute([$project_item_id]);
        $item = $stmt->fetch();
        
        if (!$item) return false;
        
        // Get discount percentage from inventory
        $stmt = $pdo->prepare("SELECT discount_percentage FROM inventory_items WHERE id = ?");
        $stmt->execute([$item['inventory_item_id']]);
        $discount_pct = $stmt->fetchColumn();
        
        $discount_amount = ($item['unit_selling_price'] * $discount_pct / 100) * $new_quantity;
        $total_amount = ($item['unit_selling_price'] * $new_quantity) - $discount_amount;
        
        // Update the item
        $stmt = $pdo->prepare("UPDATE solar_project_items SET 
                              quantity = ?, discount_amount = ?, total_amount = ? 
                              WHERE id = ?");
        
        $result = $stmt->execute([$new_quantity, $discount_amount, $total_amount, $project_item_id]);
        
        if ($result) {
            updateProjectTotals($item['project_id']);
        }
        
        return $result;
    } catch(PDOException $e) {
        return false;
    }
}

// Update project totals
function updateProjectTotals($project_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT 
                              SUM(unit_base_price * quantity) as total_base_cost,
                              SUM(unit_selling_price * quantity) as total_selling_price,
                              SUM(discount_amount) as total_discount,
                              SUM(total_amount) as final_amount
                              FROM solar_project_items WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $totals = $stmt->fetch();
        
        $stmt = $pdo->prepare("UPDATE solar_projects SET 
                              total_base_cost = ?, total_selling_price = ?, 
                              total_discount = ?, final_amount = ? 
                              WHERE id = ?");
        
        return $stmt->execute([
            $totals['total_base_cost'] ?: 0,
            $totals['total_selling_price'] ?: 0,
            $totals['total_discount'] ?: 0,
            $totals['final_amount'] ?: 0,
            $project_id
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Delete solar project
function deleteSolarProject($id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Delete project items first
        $stmt = $pdo->prepare("DELETE FROM solar_project_items WHERE project_id = ?");
        $stmt->execute([$id]);
        
        // Delete project
        $stmt = $pdo->prepare("DELETE FROM solar_projects WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

// Get project statistics
function getProjectStats() {
    global $pdo;
    
    $stats = [];
    
    // Total projects by status
    $stmt = $pdo->query("SELECT project_status, COUNT(*) as count FROM solar_projects GROUP BY project_status");
    $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(final_amount) as total_revenue FROM solar_projects WHERE project_status IN ('approved', 'completed')");
    $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // Monthly revenue
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(final_amount) as revenue 
                        FROM solar_projects 
                        WHERE project_status IN ('approved', 'completed') 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY month 
                        ORDER BY month");
    $stats['monthly_revenue'] = $stmt->fetchAll();
    
    return $stats;
}
?>
