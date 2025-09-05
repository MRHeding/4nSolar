<?php
/**
 * Add "Under Review" status to quotations table
 * This script adds the "under_review" status option to the quotations status enum
 */

// Include database configuration
require_once 'includes/config.php';

// Set content type for better output formatting
header('Content-Type: text/plain; charset=utf-8');

echo "===============================================\n";
echo "Adding 'Under Review' Status to Quotations\n";
echo "===============================================\n\n";

try {
    // Start transaction for safety
    $pdo->beginTransaction();
    
    echo "1. Checking current quotations table status enum...\n";
    
    // Get current column definition
    $stmt = $pdo->query("SHOW COLUMNS FROM quotations LIKE 'status'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "   Current status enum: " . $column['Type'] . "\n";
        
        // Check if 'under_review' is already in the enum
        if (strpos($column['Type'], 'under_review') !== false) {
            echo "   ℹ️  'under_review' status already exists in quotations table\n\n";
        } else {
            echo "   🔧 Adding 'under_review' status to quotations table...\n";
            
            // Modify the enum to add 'under_review' status
            $sql = "ALTER TABLE quotations MODIFY COLUMN status 
                   ENUM('draft','sent','under_review','accepted','rejected','expired') 
                   DEFAULT 'draft'";
            
            $pdo->exec($sql);
            echo "   ✅ Successfully added 'under_review' status to quotations table\n\n";
        }
    } else {
        echo "   ❌ Could not find status column in quotations table\n\n";
    }
    
    // Verify the change
    echo "2. Verifying the updated status enum...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM quotations LIKE 'status'");
    $updated_column = $stmt->fetch();
    
    if ($updated_column) {
        echo "   Updated status enum: " . $updated_column['Type'] . "\n";
        
        if (strpos($updated_column['Type'], 'under_review') !== false) {
            echo "   ✅ Verification successful - 'under_review' status is now available\n\n";
        } else {
            echo "   ❌ Verification failed - 'under_review' status was not added\n\n";
        }
    }
    
    // Show current quotations with their statuses
    echo "3. Current quotations status summary...\n";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM quotations GROUP BY status ORDER BY status");
    $status_counts = $stmt->fetchAll();
    
    if ($status_counts) {
        echo "   Current status distribution:\n";
        foreach ($status_counts as $status) {
            echo "   - " . ucfirst(str_replace('_', ' ', $status['status'])) . ": " . $status['count'] . " quotation(s)\n";
        }
    } else {
        echo "   No quotations found in database\n";
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n===============================================\n";
    echo "✅ UPDATE COMPLETED SUCCESSFULLY!\n";
    echo "===============================================\n\n";
    
    echo "🎉 'Under Review' status is now available for quotations!\n\n";
    echo "Status flow:\n";
    echo "1. Draft → Initial quotation creation\n";
    echo "2. Sent → Quotation sent to customer\n";
    echo "3. Under Review → Customer is reviewing the quotation\n";
    echo "4. Accepted → Customer approved the quotation (creates project)\n";
    echo "5. Rejected → Customer declined the quotation\n";
    echo "6. Expired → Quotation validity period has passed\n\n";
    
    echo "You can now use 'Under Review' status in:\n";
    echo "- Custom Status dropdown in quotations\n";
    echo "- Status badges will show purple color\n";
    echo "- Status will display as 'Under Review' (formatted)\n\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    echo "\n❌ ERROR OCCURRED:\n";
    echo $e->getMessage() . "\n";
    echo "\nAll changes have been rolled back.\n";
}

// Show execution time
$execution_time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
echo "Execution time: " . number_format($execution_time, 3) . " seconds\n";

// If running from browser, add some styling
if (isset($_SERVER['HTTP_HOST'])) {
    echo "\n<style>body { font-family: monospace; white-space: pre; background: #f5f5f5; padding: 20px; }</style>";
}
?>
