<?php
require_once 'includes/config.php';

try {
    $sql = "ALTER TABLE inventory_items ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER description";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo "✓ Image column added successfully to inventory_items table\n";
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ Image column already exists in inventory_items table\n";
    } else {
        echo "✗ Error adding image column: " . $e->getMessage() . "\n";
    }
}
?>
