<?php
/**
 * 4nSolar Inventory System Reset Script
 * 
 * This script resets all stock quantities and serial numbers to default/0 values
 * Use with caution - this will permanently delete all inventory data!
 * 
 * WARNING: This script will:
 * - Reset all stock quantities to 0
 * - Reset all serial number counters to 1
 * - Delete all serial number records
 * - Clear stock movement history
 * - Reset serial counts in sales, quotes, and projects
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';

// Security check - only allow admin users
if (!isLoggedIn() || !hasPermission([ROLE_ADMIN])) {
    die('Access denied. Only administrators can run this script.');
}

// Confirmation check
$confirmed = $_GET['confirm'] ?? false;
$action = $_POST['action'] ?? '';

$message = '';
$error = '';

if ($action === 'reset' && $confirmed === 'yes') {
    try {
        $pdo->beginTransaction();
        
        echo "<h3>Starting Inventory System Reset...</h3>";
        echo "<ul>";
        
        // 1. Reset stock quantities to 0
        echo "<li>Resetting stock quantities to 0...</li>";
        $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = 0");
        $stmt->execute();
        $affected_rows = $stmt->rowCount();
        echo "<li>✓ Updated {$affected_rows} inventory items</li>";
        
        // 2. Reset serial number counters to 1
        echo "<li>Resetting serial number counters to 1...</li>";
        $stmt = $pdo->prepare("UPDATE inventory_items SET next_serial_number = 1");
        $stmt->execute();
        echo "<li>✓ Reset serial number counters</li>";
        
        // 3. Delete all serial number records
        echo "<li>Deleting all serial number records...</li>";
        $stmt = $pdo->prepare("DELETE FROM inventory_serials");
        $stmt->execute();
        $deleted_serials = $stmt->rowCount();
        echo "<li>✓ Deleted {$deleted_serials} serial number records</li>";
        
        // 4. Reset serial counts in pos_sale_items
        echo "<li>Resetting serial counts in POS sales...</li>";
        $stmt = $pdo->prepare("UPDATE pos_sale_items SET serial_numbers = NULL, serial_count = 0");
        $stmt->execute();
        $affected_sales = $stmt->rowCount();
        echo "<li>✓ Updated {$affected_sales} POS sale items</li>";
        
        // 5. Reset serial counts in quote_items
        echo "<li>Resetting serial counts in quotes...</li>";
        $stmt = $pdo->prepare("UPDATE quote_items SET serial_numbers = NULL, serial_count = 0");
        $stmt->execute();
        $affected_quotes = $stmt->rowCount();
        echo "<li>✓ Updated {$affected_quotes} quote items</li>";
        
        // 6. Reset serial counts in solar_project_items
        echo "<li>Resetting serial counts in solar projects...</li>";
        $stmt = $pdo->prepare("UPDATE solar_project_items SET serial_numbers = NULL, serial_count = 0");
        $stmt->execute();
        $affected_projects = $stmt->rowCount();
        echo "<li>✓ Updated {$affected_projects} solar project items</li>";
        
        // 7. Clear stock movements history
        echo "<li>Clearing stock movements history...</li>";
        $stmt = $pdo->prepare("DELETE FROM stock_movements");
        $stmt->execute();
        $deleted_movements = $stmt->rowCount();
        echo "<li>✓ Deleted {$deleted_movements} stock movement records</li>";
        
        // 8. Reset AUTO_INCREMENT for serials table
        echo "<li>Resetting serial number table auto-increment...</li>";
        $stmt = $pdo->prepare("ALTER TABLE inventory_serials AUTO_INCREMENT = 1");
        $stmt->execute();
        echo "<li>✓ Reset serial number table auto-increment</li>";
        
        // 9. Reset AUTO_INCREMENT for stock_movements table
        echo "<li>Resetting stock movements table auto-increment...</li>";
        $stmt = $pdo->prepare("ALTER TABLE stock_movements AUTO_INCREMENT = 1");
        $stmt->execute();
        echo "<li>✓ Reset stock movements table auto-increment</li>";
        
        $pdo->commit();
        
        echo "</ul>";
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Inventory System Reset Completed Successfully!</h4>";
        echo "<p><strong>Summary:</strong></p>";
        echo "<ul>";
        echo "<li>Stock quantities reset to 0 for all items</li>";
        echo "<li>Serial number counters reset to 1</li>";
        echo "<li>All serial number records deleted</li>";
        echo "<li>Serial counts reset in sales, quotes, and projects</li>";
        echo "<li>Stock movement history cleared</li>";
        echo "<li>Auto-increment counters reset</li>";
        echo "</ul>";
        echo "<p><strong>You can now start fresh with your inventory system!</strong></p>";
        echo "</div>";
        
        $message = "Inventory system has been successfully reset to default values.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error during reset: " . $e->getMessage();
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ Reset Failed!</h4>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>All changes have been rolled back.</p>";
        echo "</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Inventory System - 4nSolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        .card-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .danger-box {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #ee5a24, #ff6b6b);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .list-group-item {
            border: none;
            padding: 15px 20px;
            background: rgba(0,0,0,0.02);
            margin-bottom: 5px;
            border-radius: 8px;
        }
        .icon-large {
            font-size: 3rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <i class="fas fa-exclamation-triangle icon-large"></i>
                <h2 class="mb-0">Reset Inventory System</h2>
                <p class="mb-0">4nSolar Inventory Management</p>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($action !== 'reset' || $confirmed !== 'yes'): ?>
                    <div class="warning-box">
                        <h4><i class="fas fa-exclamation-triangle text-warning"></i> WARNING: DESTRUCTIVE OPERATION</h4>
                        <p><strong>This operation will permanently delete the following data:</strong></p>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-boxes text-danger"></i>
                                <strong>All stock quantities</strong> - Reset to 0 for all inventory items
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-barcode text-danger"></i>
                                <strong>All serial numbers</strong> - Delete all serial number records
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-sort-numeric-up text-danger"></i>
                                <strong>Serial counters</strong> - Reset to 1 for all items
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-chart-line text-danger"></i>
                                <strong>Stock movements</strong> - Clear all movement history
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-shopping-cart text-danger"></i>
                                <strong>Serial counts</strong> - Reset in sales, quotes, and projects
                            </li>
                        </ul>
                    </div>
                    
                    <div class="danger-box">
                        <h5><i class="fas fa-skull-crossbones text-danger"></i> This action cannot be undone!</h5>
                        <p>Make sure you have backed up your database before proceeding. All inventory data will be lost permanently.</p>
                    </div>
                    
                    <form method="post" onsubmit="return confirmReset()">
                        <input type="hidden" name="action" value="reset">
                        <input type="hidden" name="confirm" value="yes">
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-danger btn-lg me-3">
                                <i class="fas fa-trash-alt"></i> RESET INVENTORY SYSTEM
                            </button>
                            <a href="inventory.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i> Cancel & Return
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmReset() {
            return confirm(
                "⚠️ FINAL WARNING ⚠️\n\n" +
                "You are about to PERMANENTLY DELETE all inventory data:\n" +
                "• All stock quantities will be reset to 0\n" +
                "• All serial numbers will be deleted\n" +
                "• All stock movement history will be cleared\n" +
                "• This action CANNOT be undone!\n\n" +
                "Are you absolutely sure you want to continue?\n\n" +
                "Type 'YES' in the next prompt to confirm."
            );
        }
    </script>
</body>
</html>
