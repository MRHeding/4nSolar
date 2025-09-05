<?php
/**
 * ===================================================
 * Quotation to Solar Project Conversion Enhancement
 * Database Schema Update Script (PHP Version)
 * Date: September 5, 2025
 * ===================================================
 * 
 * This script adds the necessary database columns and constraints
 * to enable automatic conversion of quotations to solar projects.
 * 
 * Usage: Run this script once from your browser or command line
 * URL: http://localhost/4nsolarSystem/add_project_id.php
 */

// Include database configuration
require_once 'includes/config.php';

// Set content type for better output formatting
header('Content-Type: text/plain; charset=utf-8');

echo "===================================================\n";
echo "Quotation to Solar Project Enhancement Script\n";
echo "Starting database schema update...\n";
echo "===================================================\n\n";

try {
    // Start transaction for safety
    $pdo->beginTransaction();
    
    $updates_made = [];
    $errors = [];
    
    // ===================================================
    // 1. Add project_id column to quotations table
    // ===================================================
    
    echo "1. Checking quotations table for project_id column...\n";
    
    // Check if project_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM quotations LIKE 'project_id'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        try {
            $pdo->exec("ALTER TABLE quotations ADD COLUMN project_id INT NULL COMMENT 'ID of the solar project created from this quotation'");
            $updates_made[] = "✅ Added project_id column to quotations table";
            echo "   ✅ Added project_id column to quotations table\n";
        } catch (PDOException $e) {
            $errors[] = "❌ Failed to add project_id column to quotations: " . $e->getMessage();
            echo "   ❌ Failed to add project_id column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ℹ️  project_id column already exists in quotations table\n";
    }
    
    // Add index for project_id
    try {
        $stmt = $pdo->query("SHOW INDEX FROM quotations WHERE Key_name = 'idx_project_id'");
        $index_exists = $stmt->rowCount() > 0;
        
        if (!$index_exists) {
            $pdo->exec("ALTER TABLE quotations ADD INDEX idx_project_id (project_id)");
            $updates_made[] = "✅ Added index idx_project_id to quotations table";
            echo "   ✅ Added index idx_project_id to quotations table\n";
        } else {
            echo "   ℹ️  Index idx_project_id already exists on quotations table\n";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Failed to add index to quotations: " . $e->getMessage();
        echo "   ❌ Failed to add index: " . $e->getMessage() . "\n";
    }
    
    // ===================================================
    // 2. Add quote_id column to solar_projects table
    // ===================================================
    
    echo "\n2. Checking solar_projects table for quote_id column...\n";
    
    // Check if quote_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM solar_projects LIKE 'quote_id'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        try {
            $pdo->exec("ALTER TABLE solar_projects ADD COLUMN quote_id INT NULL COMMENT 'ID of the quotation that created this project'");
            $updates_made[] = "✅ Added quote_id column to solar_projects table";
            echo "   ✅ Added quote_id column to solar_projects table\n";
        } catch (PDOException $e) {
            $errors[] = "❌ Failed to add quote_id column to solar_projects: " . $e->getMessage();
            echo "   ❌ Failed to add quote_id column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ℹ️  quote_id column already exists in solar_projects table\n";
    }
    
    // Add index for quote_id
    try {
        $stmt = $pdo->query("SHOW INDEX FROM solar_projects WHERE Key_name = 'idx_quote_id'");
        $index_exists = $stmt->rowCount() > 0;
        
        if (!$index_exists) {
            $pdo->exec("ALTER TABLE solar_projects ADD INDEX idx_quote_id (quote_id)");
            $updates_made[] = "✅ Added index idx_quote_id to solar_projects table";
            echo "   ✅ Added index idx_quote_id to solar_projects table\n";
        } else {
            echo "   ℹ️  Index idx_quote_id already exists on solar_projects table\n";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Failed to add index to solar_projects: " . $e->getMessage();
        echo "   ❌ Failed to add index: " . $e->getMessage() . "\n";
    }
    
    // ===================================================
    // 3. Add foreign key constraints (optional)
    // ===================================================
    
    echo "\n3. Setting up foreign key constraints...\n";
    
    // Check and add foreign key for quotations.project_id -> solar_projects.id
    try {
        $stmt = $pdo->query("SELECT * FROM information_schema.key_column_usage 
                            WHERE table_schema = DATABASE() 
                            AND table_name = 'quotations' 
                            AND constraint_name = 'fk_quotations_project_id'");
        $fk_exists = $stmt->rowCount() > 0;
        
        if (!$fk_exists) {
            $pdo->exec("ALTER TABLE quotations ADD CONSTRAINT fk_quotations_project_id 
                       FOREIGN KEY (project_id) REFERENCES solar_projects(id) ON DELETE SET NULL");
            $updates_made[] = "✅ Added foreign key constraint for quotations.project_id";
            echo "   ✅ Added foreign key constraint for quotations.project_id\n";
        } else {
            echo "   ℹ️  Foreign key constraint for quotations.project_id already exists\n";
        }
    } catch (PDOException $e) {
        $errors[] = "⚠️  Could not add foreign key for quotations.project_id: " . $e->getMessage();
        echo "   ⚠️  Could not add foreign key for quotations.project_id: " . $e->getMessage() . "\n";
    }
    
    // Check and add foreign key for solar_projects.quote_id -> quotations.id
    try {
        $stmt = $pdo->query("SELECT * FROM information_schema.key_column_usage 
                            WHERE table_schema = DATABASE() 
                            AND table_name = 'solar_projects' 
                            AND constraint_name = 'fk_solar_projects_quote_id'");
        $fk_exists = $stmt->rowCount() > 0;
        
        if (!$fk_exists) {
            $pdo->exec("ALTER TABLE solar_projects ADD CONSTRAINT fk_solar_projects_quote_id 
                       FOREIGN KEY (quote_id) REFERENCES quotations(id) ON DELETE SET NULL");
            $updates_made[] = "✅ Added foreign key constraint for solar_projects.quote_id";
            echo "   ✅ Added foreign key constraint for solar_projects.quote_id\n";
        } else {
            echo "   ℹ️  Foreign key constraint for solar_projects.quote_id already exists\n";
        }
    } catch (PDOException $e) {
        $errors[] = "⚠️  Could not add foreign key for solar_projects.quote_id: " . $e->getMessage();
        echo "   ⚠️  Could not add foreign key for solar_projects.quote_id: " . $e->getMessage() . "\n";
    }
    
    // ===================================================
    // 4. Verification and Summary
    // ===================================================
    
    echo "\n===================================================\n";
    echo "VERIFICATION & SUMMARY\n";
    echo "===================================================\n";
    
    // Verify quotations table structure
    echo "Quotations table columns:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM quotations");
    while ($row = $stmt->fetch()) {
        $mark = ($row['Field'] == 'project_id') ? '✅' : '  ';
        echo "  $mark {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n";
    
    // Verify solar_projects table structure
    echo "Solar_projects table columns:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM solar_projects");
    while ($row = $stmt->fetch()) {
        $mark = ($row['Field'] == 'quote_id') ? '✅' : '  ';
        echo "  $mark {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n";
    
    // Show current data counts
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quotations");
    $quotation_count = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM solar_projects");
    $project_count = $stmt->fetch()['total'];
    
    echo "Current data:\n";
    echo "  📊 Total quotations: $quotation_count\n";
    echo "  📊 Total solar projects: $project_count\n";
    
    // Summary of changes
    echo "\nChanges made in this session:\n";
    if (empty($updates_made)) {
        echo "  ℹ️  No changes needed - database already up to date\n";
    } else {
        foreach ($updates_made as $update) {
            echo "  $update\n";
        }
    }
    
    if (!empty($errors)) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo "  $error\n";
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n===================================================\n";
    echo "✅ DATABASE ENHANCEMENT COMPLETED SUCCESSFULLY!\n";
    echo "===================================================\n\n";
    
    echo "🎉 Your quotation to project conversion feature is now ready!\n\n";
    echo "Next steps:\n";
    echo "1. Go to Quotations page\n";
    echo "2. Create or open a quotation\n";
    echo "3. Click 'Approve Quote' to automatically create a solar project\n";
    echo "4. Check the Projects page to see the converted project\n\n";
    
    echo "Note: You can safely run this script multiple times.\n";
    echo "It will only make changes that haven't been made yet.\n\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    echo "\n❌ CRITICAL ERROR OCCURRED:\n";
    echo $e->getMessage() . "\n";
    echo "\nAll changes have been rolled back.\n";
    echo "Please check your database connection and try again.\n";
}

// Show execution time
$execution_time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
echo "Execution time: " . number_format($execution_time, 3) . " seconds\n";

// If running from browser, add some styling
if (isset($_SERVER['HTTP_HOST'])) {
    echo "\n<style>body { font-family: monospace; white-space: pre; background: #f5f5f5; padding: 20px; }</style>";
}
?>
