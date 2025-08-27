<?php
/**
 * Database Setup and Fix Script
 * This script will check and create missing database tables
 */

// Database configuration
$db_host = 'localhost';
$db_name = '4nsolar_inventory';
$db_user = 'root';
$db_pass = '';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Setup Results</h2>\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    echo "<p>✓ Database '$db_name' created/verified</p>\n";
    
    // Use the database
    $pdo->exec("USE `$db_name`");
    
    // Check which tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Existing Tables:</h3>\n";
    echo "<ul>\n";
    foreach ($existing_tables as $table) {
        echo "<li>$table</li>\n";
    }
    echo "</ul>\n";
    
    // Required tables
    $required_tables = [
        'users', 'suppliers', 'categories', 'inventory_items', 'solar_projects', 
        'solar_project_items', 'stock_movements', 'pos_sales', 'pos_sale_items'
    ];
    
    $missing_tables = array_diff($required_tables, $existing_tables);
    
    if (!empty($missing_tables)) {
        echo "<h3>Missing Tables (will be created):</h3>\n";
        echo "<ul>\n";
        foreach ($missing_tables as $table) {
            echo "<li>$table</li>\n";
        }
        echo "</ul>\n";
        
        // Read and execute the database.sql file
        $sql_file = 'database.sql';
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            
            // Split SQL statements and execute them
            $statements = array_filter(array_map('trim', explode(';', $sql_content)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !stripos($statement, 'CREATE DATABASE')) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore errors for existing tables/data
                        if (!stripos($e->getMessage(), 'already exists') && !stripos($e->getMessage(), 'Duplicate entry')) {
                            echo "<p>Warning: " . htmlspecialchars($e->getMessage()) . "</p>\n";
                        }
                    }
                }
            }
            echo "<p>✓ Database schema executed</p>\n";
        } else {
            echo "<p>❌ database.sql file not found</p>\n";
        }
    } else {
        echo "<p>✓ All required tables exist</p>\n";
    }
    
    // Check table structures and add missing columns
    echo "<h3>Checking Table Structures:</h3>\n";
    
    // Check if inventory_items has image_path column
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM inventory_items LIKE 'image_path'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE inventory_items ADD COLUMN image_path VARCHAR(255) NULL AFTER description");
            echo "<p>✓ Added image_path column to inventory_items</p>\n";
        } else {
            echo "<p>✓ image_path column exists in inventory_items</p>\n";
        }
    } catch (PDOException $e) {
        echo "<p>Warning: Could not check/add image_path column: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    // Create alias table 'inventory' as a view if it doesn't exist
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'inventory'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("CREATE VIEW inventory AS SELECT * FROM inventory_items");
            echo "<p>✓ Created 'inventory' view as alias for 'inventory_items'</p>\n";
        } else {
            echo "<p>✓ 'inventory' table/view already exists</p>\n";
        }
    } catch (PDOException $e) {
        echo "<p>Warning: Could not create inventory view: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    // Add POS tables if missing
    $pos_tables_sql = "
    CREATE TABLE IF NOT EXISTS pos_sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_number VARCHAR(50) UNIQUE NOT NULL,
        customer_name VARCHAR(100),
        customer_phone VARCHAR(20),
        customer_email VARCHAR(100),
        subtotal DECIMAL(12,2) NOT NULL,
        tax_amount DECIMAL(12,2) DEFAULT 0,
        discount_amount DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) NOT NULL,
        payment_method ENUM('cash', 'card', 'bank_transfer', 'check') NOT NULL,
        payment_status ENUM('pending', 'completed', 'refunded') DEFAULT 'completed',
        status ENUM('draft', 'completed', 'cancelled', 'refunded') DEFAULT 'completed',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (created_by) REFERENCES users(id)
    );
    
    CREATE TABLE IF NOT EXISTS pos_sale_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        inventory_item_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        total_amount DECIMAL(12,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES pos_sales(id) ON DELETE CASCADE,
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
    );
    ";
    
    $pos_statements = array_filter(array_map('trim', explode(';', $pos_tables_sql)));
    foreach ($pos_statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                if (!stripos($e->getMessage(), 'already exists')) {
                    echo "<p>Warning: " . htmlspecialchars($e->getMessage()) . "</p>\n";
                }
            }
        }
    }
    echo "<p>✓ POS tables checked/created</p>\n";
    
    // Final verification
    echo "<h3>Final Verification:</h3>\n";
    $stmt = $pdo->query("SHOW TABLES");
    $final_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Total tables in database:</strong> " . count($final_tables) . "</p>\n";
    echo "<ul>\n";
    foreach ($final_tables as $table) {
        // Count records in each table
        try {
            $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $count_stmt->fetch()['count'];
            echo "<li>$table ($count records)</li>\n";
        } catch (PDOException $e) {
            echo "<li>$table (view/error)</li>\n";
        }
    }
    echo "</ul>\n";
    
    echo "<h2>✅ Database setup completed successfully!</h2>\n";
    echo "<p><a href='web_test.php'>Run System Test Again</a></p>\n";
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Setup Failed</h2>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please ensure:</p>\n";
    echo "<ul>\n";
    echo "<li>MySQL server is running</li>\n";
    echo "<li>Database credentials are correct</li>\n";
    echo "<li>User has CREATE and ALTER privileges</li>\n";
    echo "</ul>\n";
}
?>
