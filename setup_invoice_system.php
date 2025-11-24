<?php
/**
 * Invoice System Database Setup Script
 * This script creates the necessary database tables for the invoice functionality
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
        // Remove comments and empty lines, then split by semicolons
        $lines = explode("\n", $sql);
        $cleanSQL = '';
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comment-only lines
            if (empty($line) || preg_match('/^--/', $line)) {
                continue;
            }
            // Remove inline comments (but keep the SQL part)
            if (strpos($line, '--') !== false) {
                $line = substr($line, 0, strpos($line, '--'));
                $line = trim($line);
            }
            if (!empty($line)) {
                $cleanSQL .= $line . "\n";
            }
        }
        
        // Split by semicolons
        $statements = array_filter(
            array_map('trim', explode(';', $cleanSQL)),
            function($stmt) {
                return !empty($stmt) && strlen(trim($stmt)) > 0;
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        echo "<h2>Executing Invoice System Setup</h2>\n";
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
                } elseif (preg_match('/ALTER TABLE.*?`([^`]+)`/', $statement, $matches)) {
                    echo "✅ Altered table: <strong>{$matches[1]}</strong><br>\n";
                } else {
                    echo "✅ Executed SQL statement successfully<br>\n";
                }
                
            } catch (PDOException $e) {
                // Check if error is because table already exists
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "ℹ️ Table already exists (skipped)<br>\n";
                    $successCount++;
                } else {
                    $errorCount++;
                    echo "❌ Error executing statement: " . htmlspecialchars($e->getMessage()) . "<br>\n";
                    echo "Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...<br>\n";
                }
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
    
    $requiredTables = ['invoices', 'invoice_items'];
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
            } else {
                echo "❌ Table <strong>$table</strong> not found<br>\n";
            }
        }
        
        echo "</div>\n";
        
        // Check table structure
        if (in_array('invoices', $existingTables)) {
            echo "<h4>Invoices Table Structure</h4>\n";
            echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px; font-size: 12px;'>\n";
            $stmt = $pdo->query("DESCRIBE invoices");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
            echo "</div>\n";
        }
        
        if (in_array('invoice_items', $existingTables)) {
            echo "<h4>Invoice Items Table Structure</h4>\n";
            echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px; font-size: 12px;'>\n";
            $stmt = $pdo->query("DESCRIBE invoice_items");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
            echo "</div>\n";
        }
        
        return count($existingTables) === count($requiredTables);
        
    } catch (PDOException $e) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px;'>\n";
        echo "<h3 style='color: #d32f2f; margin-top: 0;'>❌ Verification Failed</h3>\n";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "</div>\n";
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice System Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1E40AF;
            border-bottom: 3px solid #1E40AF;
            padding-bottom: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #1E40AF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #1e3a8a;
        }
        .setup-button {
            display: inline-block;
            padding: 15px 30px;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }
        .setup-button:hover {
            background: #45a049;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Invoice System Database Setup</h1>
        
        <div class="info">
            <h3>What This Script Does:</h3>
            <ul>
                <li>Creates the <code>invoices</code> table to store invoice information</li>
                <li>Creates the <code>invoice_items</code> table to store invoice line items</li>
                <li>Sets up foreign key relationships with quotations and inventory items</li>
                <li>Verifies that all tables were created successfully</li>
            </ul>
        </div>
        
        <div class="warning">
            <strong>⚠️ Important:</strong> Make sure you have a database backup before running this migration. 
            This script will create new tables in your database.
        </div>

<?php
// Check if we're running the setup
if (isset($_GET['run_setup']) && $_GET['run_setup'] === '1') {
    
    echo "<h2>🚀 Starting Database Setup...</h2>\n";
    
    $sqlFile = 'database/add_invoice_system.sql';
    
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
                echo "<p>The Invoice system has been successfully installed. You can now:</p>\n";
                echo "<ul>\n";
                echo "<li>Create invoices from quotations</li>\n";
                echo "<li>Create invoices manually with items from inventory</li>\n";
                echo "<li>Print professional invoices</li>\n";
                echo "<li>Manage invoice status and tracking</li>\n";
                echo "</ul>\n";
                echo "<p><a href='invoices.php' style='color: #2e7d32; font-weight: bold;'>→ Go to Invoices Page</a></p>\n";
                echo "</div>\n";
            }
        }
    }
    
    echo "<a href='?' class='back-link'>← Back to Setup</a>\n";
    
} else {
    // Show setup button
    ?>
        <div style="text-align: center; margin: 40px 0;">
            <a href="?run_setup=1" class="setup-button">🚀 Run Database Migration</a>
        </div>
        
        <div class="info">
            <h3>Before Running:</h3>
            <ol>
                <li>Ensure you have a database backup</li>
                <li>Make sure the database connection is configured correctly in <code>includes/config.php</code></li>
                <li>Verify that the <code>quotations</code> and <code>inventory_items</code> tables exist (required for foreign keys)</li>
            </ol>
        </div>
        
        <div class="info">
            <h3>After Running:</h3>
            <ul>
                <li>You'll be able to access the Invoice system from the sidebar menu</li>
                <li>Create invoices from existing quotations</li>
                <li>Create invoices manually with items from inventory</li>
                <li>Print professional invoices with the designed layout</li>
            </ul>
        </div>
    <?php
}
?>

    </div>
</body>
</html>

