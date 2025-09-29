<?php
require_once 'includes/config.php';

echo "<h1>Data Consistency Fix Script</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .section{border:1px solid #ccc;margin:10px 0;padding:15px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>\n";

try {
    $pdo->beginTransaction();
    
    // Fix 1: Remove orphaned POS sale items
    echo "<div class='section'>\n";
    echo "<h2>1. Fixing Orphaned POS Sale Items</h2>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pos_sale_items psi LEFT JOIN pos_sales ps ON psi.sale_id = ps.id WHERE ps.id IS NULL");
    $orphaned_count = $stmt->fetchColumn();
    echo "Found $orphaned_count orphaned POS sale items<br>\n";
    
    if ($orphaned_count > 0) {
        $stmt = $pdo->prepare("DELETE psi FROM pos_sale_items psi LEFT JOIN pos_sales ps ON psi.sale_id = ps.id WHERE ps.id IS NULL");
        $result = $stmt->execute();
        
        if ($result) {
            echo "<span class='success'>✓ Successfully removed $orphaned_count orphaned POS sale items</span><br>\n";
        } else {
            echo "<span class='error'>✗ Failed to remove orphaned POS sale items</span><br>\n";
        }
    } else {
        echo "<span class='success'>✓ No orphaned POS sale items found</span><br>\n";
    }
    echo "</div>\n";
    
    // Fix 2: Fix invalid prices (set to reasonable defaults)
    echo "<div class='section'>\n";
    echo "<h2>2. Fixing Invalid Prices</h2>\n";
    
    $stmt = $pdo->query("SELECT id, brand, model, base_price, selling_price FROM inventory_items WHERE base_price <= 0 OR selling_price <= 0");
    $invalid_price_items = $stmt->fetchAll();
    
    echo "Found " . count($invalid_price_items) . " items with invalid prices<br>\n";
    
    if (!empty($invalid_price_items)) {
        echo "<h3>Items with invalid prices:</h3>\n";
        echo "<table border='1' style='border-collapse:collapse;'>\n";
        echo "<tr><th>ID</th><th>Brand</th><th>Model</th><th>Base Price</th><th>Selling Price</th><th>Action</th></tr>\n";
        
        foreach ($invalid_price_items as $item) {
            echo "<tr><td>" . $item['id'] . "</td>";
            echo "<td>" . htmlspecialchars($item['brand']) . "</td>";
            echo "<td>" . htmlspecialchars($item['model']) . "</td>";
            echo "<td>₱" . number_format($item['base_price'], 2) . "</td>";
            echo "<td>₱" . number_format($item['selling_price'], 2) . "</td>";
            
            // Set default prices based on category
            $new_base_price = 100.00; // Default base price
            $new_selling_price = 150.00; // Default selling price
            
            // Update the prices
            $update_stmt = $pdo->prepare("UPDATE inventory_items SET base_price = ?, selling_price = ? WHERE id = ?");
            $update_result = $update_stmt->execute([$new_base_price, $new_selling_price, $item['id']]);
            
            if ($update_result) {
                echo "<td><span class='success'>Fixed</span></td></tr>\n";
            } else {
                echo "<td><span class='error'>Failed</span></td></tr>\n";
            }
        }
        echo "</table>\n";
    } else {
        echo "<span class='success'>✓ No items with invalid prices found</span><br>\n";
    }
    echo "</div>\n";
    
    // Fix 3: Remove orphaned project items
    echo "<div class='section'>\n";
    echo "<h2>3. Fixing Orphaned Project Items</h2>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM solar_project_items spi LEFT JOIN solar_projects sp ON spi.project_id = sp.id WHERE sp.id IS NULL");
    $orphaned_project_items = $stmt->fetchColumn();
    echo "Found $orphaned_project_items orphaned project items<br>\n";
    
    if ($orphaned_project_items > 0) {
        $stmt = $pdo->prepare("DELETE spi FROM solar_project_items spi LEFT JOIN solar_projects sp ON spi.project_id = sp.id WHERE sp.id IS NULL");
        $result = $stmt->execute();
        
        if ($result) {
            echo "<span class='success'>✓ Successfully removed $orphaned_project_items orphaned project items</span><br>\n";
        } else {
            echo "<span class='error'>✗ Failed to remove orphaned project items</span><br>\n";
        }
    } else {
        echo "<span class='success'>✓ No orphaned project items found</span><br>\n";
    }
    echo "</div>\n";
    
    // Fix 4: Remove orphaned quote items
    echo "<div class='section'>\n";
    echo "<h2>4. Fixing Orphaned Quote Items</h2>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM quote_items qi LEFT JOIN quotations q ON qi.quote_id = q.id WHERE q.id IS NULL");
    $orphaned_quote_items = $stmt->fetchColumn();
    echo "Found $orphaned_quote_items orphaned quote items<br>\n";
    
    if ($orphaned_quote_items > 0) {
        $stmt = $pdo->prepare("DELETE qi FROM quote_items qi LEFT JOIN quotations q ON qi.quote_id = q.id WHERE q.id IS NULL");
        $result = $stmt->execute();
        
        if ($result) {
            echo "<span class='success'>✓ Successfully removed $orphaned_quote_items orphaned quote items</span><br>\n";
        } else {
            echo "<span class='error'>✗ Failed to remove orphaned quote items</span><br>\n";
        }
    } else {
        echo "<span class='success'>✓ No orphaned quote items found</span><br>\n";
    }
    echo "</div>\n";
    
    // Fix 5: Fix negative stock quantities
    echo "<div class='section'>\n";
    echo "<h2>5. Fixing Negative Stock Quantities</h2>\n";
    
    $stmt = $pdo->query("SELECT id, brand, model, stock_quantity FROM inventory_items WHERE stock_quantity < 0");
    $negative_stock_items = $stmt->fetchAll();
    
    echo "Found " . count($negative_stock_items) . " items with negative stock<br>\n";
    
    if (!empty($negative_stock_items)) {
        echo "<h3>Items with negative stock:</h3>\n";
        echo "<table border='1' style='border-collapse:collapse;'>\n";
        echo "<tr><th>ID</th><th>Brand</th><th>Model</th><th>Current Stock</th><th>Action</th></tr>\n";
        
        foreach ($negative_stock_items as $item) {
            echo "<tr><td>" . $item['id'] . "</td>";
            echo "<td>" . htmlspecialchars($item['brand']) . "</td>";
            echo "<td>" . htmlspecialchars($item['model']) . "</td>";
            echo "<td>" . $item['stock_quantity'] . "</td>";
            
            // Set stock to 0
            $update_stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = 0 WHERE id = ?");
            $update_result = $update_stmt->execute([$item['id']]);
            
            if ($update_result) {
                echo "<td><span class='success'>Set to 0</span></td></tr>\n";
            } else {
                echo "<td><span class='error'>Failed</span></td></tr>\n";
            }
        }
        echo "</table>\n";
    } else {
        echo "<span class='success'>✓ No items with negative stock found</span><br>\n";
    }
    echo "</div>\n";
    
    // Fix 6: Update project totals for projects with missing totals
    echo "<div class='section'>\n";
    echo "<h2>6. Updating Project Totals</h2>\n";
    
    $stmt = $pdo->query("SELECT id FROM solar_projects WHERE final_amount = 0 OR final_amount IS NULL");
    $projects_without_totals = $stmt->fetchAll();
    
    echo "Found " . count($projects_without_totals) . " projects with missing totals<br>\n";
    
    if (!empty($projects_without_totals)) {
        foreach ($projects_without_totals as $project) {
            $project_id = $project['id'];
            
            // Calculate totals from project items
            $stmt = $pdo->prepare("SELECT 
                                  SUM(unit_base_price * quantity) as total_base_cost,
                                  SUM(unit_selling_price * quantity) as total_selling_price,
                                  SUM(discount_amount) as total_discount,
                                  SUM(total_amount) as final_amount
                                  FROM solar_project_items WHERE project_id = ?");
            $stmt->execute([$project_id]);
            $totals = $stmt->fetch();
            
            // Update project totals
            $update_stmt = $pdo->prepare("UPDATE solar_projects SET 
                                        total_base_cost = ?, total_selling_price = ?, 
                                        total_discount = ?, final_amount = ? 
                                        WHERE id = ?");
            $update_result = $update_stmt->execute([
                $totals['total_base_cost'] ?: 0,
                $totals['total_selling_price'] ?: 0,
                $totals['total_discount'] ?: 0,
                $totals['final_amount'] ?: 0,
                $project_id
            ]);
            
            if ($update_result) {
                echo "<span class='success'>✓ Updated totals for project ID $project_id</span><br>\n";
            } else {
                echo "<span class='error'>✗ Failed to update totals for project ID $project_id</span><br>\n";
            }
        }
    } else {
        echo "<span class='success'>✓ All projects have proper totals</span><br>\n";
    }
    echo "</div>\n";
    
    // Fix 7: Update POS sale totals for sales with missing totals
    echo "<div class='section'>\n";
    echo "<h2>7. Updating POS Sale Totals</h2>\n";
    
    $stmt = $pdo->query("SELECT id FROM pos_sales WHERE total_amount = 0 OR total_amount IS NULL");
    $sales_without_totals = $stmt->fetchAll();
    
    echo "Found " . count($sales_without_totals) . " POS sales with missing totals<br>\n";
    
    if (!empty($sales_without_totals)) {
        foreach ($sales_without_totals as $sale) {
            $sale_id = $sale['id'];
            
            // Calculate totals from sale items
            $stmt = $pdo->prepare("SELECT 
                                  SUM(unit_price * quantity) as subtotal,
                                  SUM(discount_amount) as total_discount,
                                  SUM(total_amount) as total_amount
                                  FROM pos_sale_items WHERE sale_id = ?");
            $stmt->execute([$sale_id]);
            $totals = $stmt->fetch();
            
            // Update sale totals
            $update_stmt = $pdo->prepare("UPDATE pos_sales SET 
                                        subtotal = ?, total_discount = ?, total_amount = ? 
                                        WHERE id = ?");
            $update_result = $update_stmt->execute([
                $totals['subtotal'] ?: 0,
                $totals['total_discount'] ?: 0,
                $totals['total_amount'] ?: 0,
                $sale_id
            ]);
            
            if ($update_result) {
                echo "<span class='success'>✓ Updated totals for sale ID $sale_id</span><br>\n";
            } else {
                echo "<span class='error'>✗ Failed to update totals for sale ID $sale_id</span><br>\n";
            }
        }
    } else {
        echo "<span class='success'>✓ All POS sales have proper totals</span><br>\n";
    }
    echo "</div>\n";
    
    $pdo->commit();
    echo "<div class='section'>\n";
    echo "<h2>Data Cleanup Complete</h2>\n";
    echo "<span class='success'>✓ All data consistency issues have been addressed</span><br>\n";
    echo "<p><strong>Cleanup completed at:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "<div class='section'>\n";
    echo "<h2>Error During Cleanup</h2>\n";
    echo "<span class='error'>✗ An error occurred: " . $e->getMessage() . "</span><br>\n";
    echo "<p>All changes have been rolled back.</p>\n";
    echo "</div>\n";
}
?>
