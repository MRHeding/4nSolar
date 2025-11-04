<?php
/**
 * Purchase Order System Database Setup Script
 * This script creates the necessary database tables for the purchase order functionality
 */

require_once 'includes/config.php';

// Function to execute SQL file
function executeSQLFile($filePath) {
    global $pdo;
    
    try {
        // Read the SQL file
        $sql = file_get_contents($filePath);
        
        if ($sql === false) {
            throw new Exception("Could not read SQL file: $filePath");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        echo "<h2>Executing Purchase Order System Setup</h2>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
        
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            try {
                $pdo->exec($statement);
                $successCount++;
                
                // Extract table/operation name for display
                if (preg_match('/CREATE TABLE.*?`([^`]+)`/', $statement, $matches)) {
                    echo "✅ Created table: <strong>{$matches[1]}</strong><br>\n";
                } elseif (preg_match('/CREATE INDEX.*?`([^`]+)`/', $statement, $matches)) {
                    echo "✅ Created index: <strong>{$matches[1]}</strong><br>\n";
                } else {
                    echo "✅ Executed SQL statement successfully<br>\n";
                }
                
            } catch (PDOException $e) {
                $errorCount++;
                echo "❌ Error executing statement: " . htmlspecialchars($e->getMessage()) . "<br>\n";
                echo "Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...<br>\n";
            }
        }
        
        echo "</div>\n";
        
        // Summary
        echo "<div style='margin-top: 20px; padding: 15px; border-radius: 5px;";
        if ($errorCount > 0) {
            echo "background: #ffebee; border: 1px solid #f44336;'>\n";
            echo "<h3 style='color: #d32f2f; margin-top: 0;'>⚠️ Setup Completed with Errors</h3>\n";
            echo "<p>Successfully executed: <strong>$successCount</strong> statements</p>\n";
            echo "<p>Failed: <strong>$errorCount</strong> statements</p>\n";
        } else {
            echo "background: #e8f5e8; border: 1px solid #4caf50;'>\n";
            echo "<h3 style='color: #2e7d32; margin-top: 0;'>✅ Setup Completed Successfully</h3>\n";
            echo "<p>Successfully executed: <strong>$successCount</strong> statements</p>\n";
        }
        echo "</div>\n";
        
        return $errorCount === 0;
        
    } catch (Exception $e) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px;'>\n";
        echo "<h3 style='color: #d32f2f; margin-top: 0;'>❌ Setup Failed</h3>\n";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "</div>\n";
        return false;
    }
}

// Function to verify tables were created
function verifyTables() {
    global $pdo;
    
    $requiredTables = ['purchase_orders', 'purchase_order_items'];
    $existingTables = [];
    
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Table Verification</h3>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $tables)) {
                echo "✅ Table <strong>$table</strong> exists<br>\n";
                $existingTables[] = $table;
                
                // Get table structure
                $stmt = $pdo->query("DESCRIBE `$table`");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "&nbsp;&nbsp;&nbsp;Columns: " . implode(', ', $columns) . "<br>\n";
            } else {
                echo "❌ Table <strong>$table</strong> missing<br>\n";
            }
        }
        
        echo "</div>\n";
        
        return count($existingTables) === count($requiredTables);
        
    } catch (PDOException $e) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px;'>\n";
        echo "<p>Error verifying tables: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "</div>\n";
        return false;
    }
}

// Main execution
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order System Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            background: #4caf50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .back-link:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛒 Purchase Order System</h1>
        <p>Database Setup Script</p>
    </div>

    <div class="info-box">
        <h3>📋 What this script does:</h3>
        <ul>
            <li>Creates <strong>purchase_orders</strong> table for storing purchase order headers</li>
            <li>Creates <strong>purchase_order_items</strong> table for storing individual items</li>
            <li>Sets up proper foreign key relationships</li>
            <li>Adds performance indexes</li>
            <li>Verifies the installation</li>
        </ul>
    </div>

<?php
// Check if we're running the setup
if (isset($_GET['run_setup']) && $_GET['run_setup'] === '1') {
    
    echo "<h2>🚀 Starting Database Setup...</h2>\n";
    
    $sqlFile = 'database/add_purchase_order_system.sql';
    
    if (!file_exists($sqlFile)) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px;'>\n";
        echo "<h3 style='color: #d32f2f; margin-top: 0;'>❌ SQL File Not Found</h3>\n";
        echo "<p>The SQL file <code>$sqlFile</code> was not found.</p>\n";
        echo "</div>\n";
    } else {
        // Execute the SQL file
        $success = executeSQLFile($sqlFile);
        
        if ($success) {
            // Verify tables were created
            $verified = verifyTables();
            
            if ($verified) {
                echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 15px; border-radius: 5px; margin-top: 20px;'>\n";
                echo "<h3 style='color: #2e7d32; margin-top: 0;'>🎉 Setup Complete!</h3>\n";
                echo "<p>The Purchase Order system has been successfully installed. You can now:</p>\n";
                echo "<ul>\n";
                echo "<li>Create purchase orders from quotations</li>\n";
                echo "<li>Manage supplier information</li>\n";
                echo "<li>Track purchase order status</li>\n";
                echo "<li>Print purchase orders</li>\n";
                echo "</ul>\n";
                echo "</div>\n";
            }
        }
    }
    
    echo "<a href='?' class='back-link'>← Back to Setup</a>\n";
    
} else {
    // Show setup button
    echo "<div style='text-align: center;'>\n";
    echo "<h2>Ready to Install Purchase Order System?</h2>\n";
    echo "<p>This will create the necessary database tables for the purchase order functionality.</p>\n";
    echo "<a href='?run_setup=1' style='background: #ff9800; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 1.2em; display: inline-block; margin: 20px;'>🚀 Run Setup</a>\n";
    echo "</div>\n";
    
    echo "<div class='info-box'>\n";
    echo "<h3>⚠️ Important Notes:</h3>\n";
    echo "<ul>\n";
    echo "<li>Make sure you have a database backup before running this setup</li>\n";
    echo "<li>This script will create new tables and won't affect existing data</li>\n";
    echo "<li>If tables already exist, the script will skip creating them</li>\n";
    echo "<li>Ensure your database user has CREATE TABLE permissions</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
}
?>

</body>
</html>








