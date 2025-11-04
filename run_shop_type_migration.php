<?php
/**
 * Shop Type Migration Script
 * This script runs the database migration to add shop_type column to pos_sales table
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
        
        echo "<h2>Executing Shop Type Migration</h2>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;'>\n";
        
        foreach ($statements as $statement) {
            if (empty($statement)) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $successCount++;
                echo "✅ Successfully executed statement<br>\n";
            } catch (PDOException $e) {
                $errorCount++;
                // Check if it's a "duplicate column" error (column already exists)
                if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                    strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "⚠️ Warning: " . $e->getMessage() . " (Migration may have already been run)<br>\n";
                } else {
                    echo "❌ Error: " . $e->getMessage() . "<br>\n";
                }
            }
        }
        
        echo "</div>\n";
        echo "<div style='padding: 20px; background: #e8f5e9; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<h3>Migration Summary</h3>\n";
        echo "<p><strong>Successful statements:</strong> $successCount</p>\n";
        echo "<p><strong>Errors/Warnings:</strong> $errorCount</p>\n";
        
        if ($errorCount == 0 || ($errorCount > 0 && strpos($e->getMessage(), 'Duplicate') !== false)) {
            echo "<p style='color: green; font-weight: bold;'>✅ Migration completed successfully!</p>\n";
            echo "<p>The shop_type column has been added to the pos_sales table.</p>\n";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>⚠️ Migration completed with warnings. Please review the errors above.</p>\n";
        }
        
        echo "</div>\n";
        
        // Verify the migration
        echo "<h3>Verification</h3>\n";
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM pos_sales LIKE 'shop_type'");
            $column = $stmt->fetch();
            
            if ($column) {
                echo "<div style='padding: 15px; background: #c8e6c9; border-radius: 5px; margin: 10px 0;'>\n";
                echo "✅ <strong>shop_type column exists!</strong><br>\n";
                echo "Type: " . htmlspecialchars($column['Type']) . "<br>\n";
                echo "Default: " . htmlspecialchars($column['Default'] ?? 'NULL') . "<br>\n";
                echo "</div>\n";
            } else {
                echo "<div style='padding: 15px; background: #ffcdd2; border-radius: 5px; margin: 10px 0;'>\n";
                echo "❌ <strong>shop_type column not found!</strong> Please check the errors above.\n";
                echo "</div>\n";
            }
        } catch (PDOException $e) {
            echo "<div style='padding: 15px; background: #ffcdd2; border-radius: 5px; margin: 10px 0;'>\n";
            echo "❌ Error verifying migration: " . htmlspecialchars($e->getMessage()) . "\n";
            echo "</div>\n";
        }
        
        return $errorCount == 0;
        
    } catch (Exception $e) {
        echo "<div style='padding: 20px; background: #ffcdd2; border-radius: 5px; margin: 20px 0;'>\n";
        echo "❌ <strong>Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
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
    <title>Shop Type Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f9f9f9;
        }
        h1 {
            color: #1e40af;
        }
        .info {
            padding: 15px;
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Shop Type Migration Script</h1>
    <div class="info">
        <strong>What this does:</strong> Adds the <code>shop_type</code> column to the <code>pos_sales</code> table to distinguish between 4NSOLAR and 168SHOP transactions.
    </div>
    
    <?php
    $sqlFile = 'database/add_shop_type_to_pos.sql';
    
    if (!file_exists($sqlFile)) {
        echo "<div style='padding: 20px; background: #ffcdd2; border-radius: 5px; margin: 20px 0;'>";
        echo "❌ <strong>Error:</strong> SQL file not found: $sqlFile";
        echo "</div>";
    } else {
        executeSQLFile($sqlFile);
    }
    ?>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">
        <p><a href="pos.php">← Back to POS System</a></p>
        <p><small>You can safely delete this file after running the migration.</small></p>
    </div>
</body>
</html>
