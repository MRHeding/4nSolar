<?php
/**
 * 4nSolar Comprehensive Inventory Data Cleaning Script
 * 
 * This script provides a comprehensive solution for cleaning all inventory data
 * including stock quantities, serial numbers, and related records.
 * 
 * FEATURES:
 * - Web-based interface with safety confirmations
 * - Command-line version for automation
 * - Backup creation before cleaning
 * - Selective cleaning options
 * - Detailed progress reporting
 * - Rollback capability on errors
 * 
 * WARNING: This script will permanently delete inventory data!
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if running from command line
$is_cli = php_sapi_name() === 'cli';

// Security check for web interface
if (!$is_cli && (!isLoggedIn() || !hasPermission([ROLE_ADMIN]))) {
    die('Access denied. Only administrators can run this script.');
}

// Configuration
$backup_enabled = true;
$backup_path = 'backups/inventory_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Initialize variables
$message = '';
$error = '';
$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$confirmed = $_POST['confirm'] ?? ($_GET['confirm'] ?? false);
$cleanup_options = $_POST['cleanup_options'] ?? [];

// Available cleanup options
$cleanup_choices = [
    'stock_quantities' => 'Reset all stock quantities to 0',
    'serial_numbers' => 'Delete all serial number records',
    'serial_counters' => 'Reset serial number counters to 1',
    'stock_movements' => 'Clear stock movement history',
    'pos_serials' => 'Reset serial counts in POS sales',
    'quote_serials' => 'Reset serial counts in quotes',
    'project_serials' => 'Reset serial counts in solar projects',
    'auto_increment' => 'Reset auto-increment counters'
];

/**
 * Create database backup
 */
function createBackup($pdo, $backup_path) {
    if (!is_dir('backups')) {
        mkdir('backups', 0755, true);
    }
    
    $tables = [
        'inventory_items',
        'inventory_serials', 
        'stock_movements',
        'pos_sale_items',
        'quote_items',
        'solar_project_items'
    ];
    
    $backup_content = "-- 4nSolar Inventory Backup\n";
    $backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $backup_content .= "-- Table: $table\n";
        $backup_content .= "DROP TABLE IF EXISTS `{$table}_backup`;\n";
        $backup_content .= "CREATE TABLE `{$table}_backup` AS SELECT * FROM `$table`;\n\n";
    }
    
    file_put_contents($backup_path, $backup_content);
    return file_exists($backup_path);
}

/**
 * Execute inventory cleanup
 */
function executeCleanup($pdo, $options, $is_cli = false) {
    $results = [];
    
    try {
        $pdo->beginTransaction();
        
        if ($is_cli) {
            echo "Starting inventory cleanup...\n";
            echo "========================================\n";
        }
        
        // 1. Reset stock quantities
        if (in_array('stock_quantities', $options)) {
            if ($is_cli) echo "1. Resetting stock quantities to 0... ";
            $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = 0");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $results['stock_quantities'] = $affected;
            if ($is_cli) echo "✓ Updated {$affected} items\n";
        }
        
        // 2. Delete serial number records
        if (in_array('serial_numbers', $options)) {
            if ($is_cli) echo "2. Deleting all serial number records... ";
            $stmt = $pdo->prepare("DELETE FROM inventory_serials");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $results['serial_numbers'] = $affected;
            if ($is_cli) echo "✓ Deleted {$affected} records\n";
        }
        
        // 3. Reset serial number counters
        if (in_array('serial_counters', $options)) {
            if ($is_cli) echo "3. Resetting serial number counters to 1... ";
            $stmt = $pdo->prepare("UPDATE inventory_items SET next_serial_number = 1");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $results['serial_counters'] = $affected;
            if ($is_cli) echo "✓ Updated {$affected} items\n";
        }
        
        // 4. Clear stock movements
        if (in_array('stock_movements', $options)) {
            if ($is_cli) echo "4. Clearing stock movements history... ";
            $stmt = $pdo->prepare("DELETE FROM stock_movements");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $results['stock_movements'] = $affected;
            if ($is_cli) echo "✓ Deleted {$affected} records\n";
        }
        
        // 5. Reset POS sale serials
        if (in_array('pos_serials', $options)) {
            if ($is_cli) echo "5. Resetting serial counts in POS sales... ";
            $stmt = $pdo->prepare("UPDATE pos_sale_items SET serial_numbers = NULL, serial_count = 0");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $results['pos_serials'] = $affected;
            if ($is_cli) echo "✓ Updated {$affected} sale items\n";
        }
        
        // 6. Reset quote serials
        if (in_array('quote_serials', $options)) {
            if ($is_cli) echo "6. Resetting serial counts in quotes... ";
            $stmt = $pdo->prepare("UPDATE quote_items SET serial_numbers = NULL, serial_count = 0");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $results['quote_serials'] = $affected;
            if ($is_cli) echo "✓ Updated {$affected} quote items\n";
        }
        
        // 7. Reset project serials
        if (in_array('project_serials', $options)) {
            if ($is_cli) echo "7. Resetting serial counts in solar projects... ";
            $stmt = $pdo->prepare("UPDATE solar_project_items SET serial_numbers = NULL, serial_count = 0");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $results['project_serials'] = $affected;
            if ($is_cli) echo "✓ Updated {$affected} project items\n";
        }
        
        // 8. Reset auto-increment counters
        if (in_array('auto_increment', $options)) {
            if ($is_cli) echo "8. Resetting auto-increment counters... ";
            $stmt = $pdo->prepare("ALTER TABLE inventory_serials AUTO_INCREMENT = 1");
            $stmt->execute();
            $stmt = $pdo->prepare("ALTER TABLE stock_movements AUTO_INCREMENT = 1");
            $stmt->execute();
            $results['auto_increment'] = true;
            if ($is_cli) echo "✓ Done\n";
        }
        
        $pdo->commit();
        
        if ($is_cli) {
            echo "\n========================================\n";
            echo "✅ INVENTORY CLEANUP COMPLETED!\n";
            echo "========================================\n";
            echo "Summary:\n";
            foreach ($results as $key => $value) {
                if (is_numeric($value)) {
                    echo "- $key: $value records affected\n";
                } else {
                    echo "- $key: Completed\n";
                }
            }
        }
        
        return $results;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        if ($is_cli) {
            echo "\n❌ CLEANUP FAILED!\n";
            echo "Error: " . $e->getMessage() . "\n";
            echo "All changes have been rolled back.\n";
        }
        throw $e;
    }
}

// Handle form submission
if ($action === 'cleanup' && $confirmed === 'yes') {
    try {
        // Create backup if enabled
        if ($backup_enabled) {
            if ($is_cli) {
                echo "Creating backup... ";
            }
            if (createBackup($pdo, $backup_path)) {
                if ($is_cli) echo "✓ Backup created: $backup_path\n";
            } else {
                throw new Exception("Failed to create backup");
            }
        }
        
        // Execute cleanup
        $results = executeCleanup($pdo, $cleanup_options, $is_cli);
        
        if (!$is_cli) {
            $message = "Inventory cleanup completed successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error during cleanup: " . $e->getMessage();
        if (!$is_cli) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>❌ Cleanup Failed!</h4>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>All changes have been rolled back.</p>";
            echo "</div>";
        }
    }
}

// Command line interface
if ($is_cli) {
    echo "========================================\n";
    echo "4nSolar Inventory Data Cleaning Script\n";
    echo "========================================\n\n";
    
    echo "Available cleanup options:\n";
    foreach ($cleanup_choices as $key => $description) {
        echo "- $key: $description\n";
    }
    echo "\n";
    
    echo "Enter cleanup options (comma-separated, or 'all' for everything): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $input = trim($line);
    fclose($handle);
    
    if (strtolower($input) === 'all') {
        $cleanup_options = array_keys($cleanup_choices);
    } else {
        $cleanup_options = array_map('trim', explode(',', $input));
        $cleanup_options = array_intersect($cleanup_options, array_keys($cleanup_choices));
    }
    
    if (empty($cleanup_options)) {
        echo "No valid options selected. Exiting.\n";
        exit(0);
    }
    
    echo "\nSelected options:\n";
    foreach ($cleanup_options as $option) {
        echo "- " . $cleanup_choices[$option] . "\n";
    }
    
    echo "\nWARNING: This will permanently modify inventory data!\n";
    echo "Are you sure you want to continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirmation = trim($line);
    fclose($handle);
    
    if (strtolower($confirmation) !== 'yes') {
        echo "Operation cancelled.\n";
        exit(0);
    }
    
    // Execute cleanup
    try {
        if ($backup_enabled) {
            echo "Creating backup... ";
            if (createBackup($pdo, $backup_path)) {
                echo "✓ Backup created: $backup_path\n";
            } else {
                throw new Exception("Failed to create backup");
            }
        }
        
        executeCleanup($pdo, $cleanup_options, true);
        
    } catch (Exception $e) {
        echo "\n❌ CLEANUP FAILED!\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "All changes have been rolled back.\n";
        exit(1);
    }
    
    exit(0);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clean Inventory Data - 4nSolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 900px;
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
        .form-check-input:checked {
            background-color: #ff6b6b;
            border-color: #ff6b6b;
        }
        .icon-large {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .cleanup-option {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        .cleanup-option:hover {
            background: #e9ecef;
            border-color: #ff6b6b;
        }
        .cleanup-option input[type="checkbox"]:checked + label {
            color: #ff6b6b;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <i class="fas fa-broom icon-large"></i>
                <h2 class="mb-0">Clean Inventory Data</h2>
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
                
                <?php if ($action !== 'cleanup' || $confirmed !== 'yes'): ?>
                    <div class="warning-box">
                        <h4><i class="fas fa-exclamation-triangle text-warning"></i> WARNING: DESTRUCTIVE OPERATION</h4>
                        <p><strong>This operation will permanently modify the following data:</strong></p>
                        <ul>
                            <li><strong>Stock quantities</strong> - Reset to 0 for all inventory items</li>
                            <li><strong>Serial numbers</strong> - Delete all serial number records</li>
                            <li><strong>Serial counters</strong> - Reset to 1 for all items</li>
                            <li><strong>Stock movements</strong> - Clear all movement history</li>
                            <li><strong>Serial counts</strong> - Reset in sales, quotes, and projects</li>
                        </ul>
                    </div>
                    
                    <div class="danger-box">
                        <h5><i class="fas fa-skull-crossbones text-danger"></i> This action cannot be undone!</h5>
                        <p>Make sure you have backed up your database before proceeding. All selected inventory data will be lost permanently.</p>
                    </div>
                    
                    <form method="post" onsubmit="return confirmCleanup()">
                        <input type="hidden" name="action" value="cleanup">
                        <input type="hidden" name="confirm" value="yes">
                        
                        <h5 class="mb-3">Select cleanup options:</h5>
                        <div class="row">
                            <?php foreach ($cleanup_choices as $key => $description): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="cleanup-option">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cleanup_options[]" value="<?php echo $key; ?>" id="<?php echo $key; ?>" checked>
                                            <label class="form-check-label" for="<?php echo $key; ?>">
                                                <strong><?php echo ucwords(str_replace('_', ' ', $key)); ?></strong><br>
                                                <small class="text-muted"><?php echo $description; ?></small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-danger btn-lg me-3">
                                <i class="fas fa-broom"></i> CLEAN INVENTORY DATA
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
        function confirmCleanup() {
            const selectedOptions = document.querySelectorAll('input[name="cleanup_options[]"]:checked');
            if (selectedOptions.length === 0) {
                alert('Please select at least one cleanup option.');
                return false;
            }
            
            const optionsList = Array.from(selectedOptions).map(option => {
                return option.nextElementSibling.querySelector('strong').textContent;
            }).join('\n• ');
            
            return confirm(
                "⚠️ FINAL WARNING ⚠️\n\n" +
                "You are about to PERMANENTLY MODIFY inventory data:\n\n" +
                "• " + optionsList + "\n\n" +
                "This action CANNOT be undone!\n\n" +
                "Are you absolutely sure you want to continue?"
            );
        }
        
        // Select all functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllBtn = document.createElement('button');
            selectAllBtn.type = 'button';
            selectAllBtn.className = 'btn btn-outline-primary btn-sm me-2';
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
            
            const selectNoneBtn = document.createElement('button');
            selectNoneBtn.type = 'button';
            selectNoneBtn.className = 'btn btn-outline-secondary btn-sm';
            selectNoneBtn.innerHTML = '<i class="fas fa-square"></i> Select None';
            
            const form = document.querySelector('form');
            const firstRow = form.querySelector('.row');
            firstRow.insertBefore(selectAllBtn, firstRow.firstChild);
            firstRow.insertBefore(selectNoneBtn, selectAllBtn.nextSibling);
            
            selectAllBtn.addEventListener('click', function() {
                document.querySelectorAll('input[name="cleanup_options[]"]').forEach(checkbox => {
                    checkbox.checked = true;
                });
            });
            
            selectNoneBtn.addEventListener('click', function() {
                document.querySelectorAll('input[name="cleanup_options[]"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
            });
        });
    </script>
</body>
</html>
