<?php
/**
 * 4nSolar Inventory System Reset Script - Command Line Version
 * 
 * This script resets all stock quantities and serial numbers to default/0 values
 * Run from command line: php reset_inventory_cli.php
 * 
 * WARNING: This script will permanently delete all inventory data!
 */

require_once 'includes/config.php';

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

echo "========================================\n";
echo "4nSolar Inventory System Reset Script\n";
echo "========================================\n\n";

echo "WARNING: This will permanently delete all inventory data!\n";
echo "This includes:\n";
echo "- All stock quantities (reset to 0)\n";
echo "- All serial numbers (deleted)\n";
echo "- All stock movement history (cleared)\n";
echo "- Serial counters (reset to 1)\n";
echo "- Serial counts in sales/quotes/projects (reset)\n\n";

echo "Are you sure you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$confirmation = trim($line);
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "Operation cancelled.\n";
    exit(0);
}

echo "\nStarting inventory system reset...\n";
echo "========================================\n";

try {
    $pdo->beginTransaction();
    
    // 1. Reset stock quantities to 0
    echo "1. Resetting stock quantities to 0... ";
    $stmt = $pdo->prepare("UPDATE inventory_items SET stock_quantity = 0");
    $stmt->execute();
    $affected_rows = $stmt->rowCount();
    echo "✓ Updated {$affected_rows} items\n";
    
    // 2. Reset serial number counters to 1
    echo "2. Resetting serial number counters to 1... ";
    $stmt = $pdo->prepare("UPDATE inventory_items SET next_serial_number = 1");
    $stmt->execute();
    echo "✓ Done\n";
    
    // 3. Delete all serial number records
    echo "3. Deleting all serial number records... ";
    $stmt = $pdo->prepare("DELETE FROM inventory_serials");
    $stmt->execute();
    $deleted_serials = $stmt->rowCount();
    echo "✓ Deleted {$deleted_serials} records\n";
    
    // 4. Reset serial counts in pos_sale_items
    echo "4. Resetting serial counts in POS sales... ";
    $stmt = $pdo->prepare("UPDATE pos_sale_items SET serial_numbers = NULL, serial_count = 0");
    $stmt->execute();
    $affected_sales = $stmt->rowCount();
    echo "✓ Updated {$affected_sales} sale items\n";
    
    // 5. Reset serial counts in quote_items
    echo "5. Resetting serial counts in quotes... ";
    $stmt = $pdo->prepare("UPDATE quote_items SET serial_numbers = NULL, serial_count = 0");
    $stmt->execute();
    $affected_quotes = $stmt->rowCount();
    echo "✓ Updated {$affected_quotes} quote items\n";
    
    // 6. Reset serial counts in solar_project_items
    echo "6. Resetting serial counts in solar projects... ";
    $stmt = $pdo->prepare("UPDATE solar_project_items SET serial_numbers = NULL, serial_count = 0");
    $stmt->execute();
    $affected_projects = $stmt->rowCount();
    echo "✓ Updated {$affected_projects} project items\n";
    
    // 7. Clear stock movements history
    echo "7. Clearing stock movements history... ";
    $stmt = $pdo->prepare("DELETE FROM stock_movements");
    $stmt->execute();
    $deleted_movements = $stmt->rowCount();
    echo "✓ Deleted {$deleted_movements} movement records\n";
    
    // 8. Reset AUTO_INCREMENT for serials table
    echo "8. Resetting serial number table auto-increment... ";
    $stmt = $pdo->prepare("ALTER TABLE inventory_serials AUTO_INCREMENT = 1");
    $stmt->execute();
    echo "✓ Done\n";
    
    // 9. Reset AUTO_INCREMENT for stock_movements table
    echo "9. Resetting stock movements table auto-increment... ";
    $stmt = $pdo->prepare("ALTER TABLE stock_movements AUTO_INCREMENT = 1");
    $stmt->execute();
    echo "✓ Done\n";
    
    $pdo->commit();
    
    echo "\n========================================\n";
    echo "✅ INVENTORY SYSTEM RESET COMPLETED!\n";
    echo "========================================\n";
    echo "Summary:\n";
    echo "- Stock quantities reset to 0 for all items\n";
    echo "- Serial number counters reset to 1\n";
    echo "- All serial number records deleted\n";
    echo "- Serial counts reset in sales, quotes, and projects\n";
    echo "- Stock movement history cleared\n";
    echo "- Auto-increment counters reset\n";
    echo "\nYou can now start fresh with your inventory system!\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ RESET FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
    exit(1);
}
?>
