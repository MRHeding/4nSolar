<?php
require_once 'includes/config.php';

try {
    // POS Sales table
    $sql = "CREATE TABLE IF NOT EXISTS `pos_sales` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `receipt_number` varchar(50) NOT NULL UNIQUE,
      `customer_name` varchar(255) DEFAULT NULL,
      `customer_phone` varchar(20) DEFAULT NULL,
      `subtotal` decimal(10,2) DEFAULT 0.00,
      `total_discount` decimal(10,2) DEFAULT 0.00,
      `total_amount` decimal(10,2) DEFAULT 0.00,
      `payment_method` enum('cash','credit_card','debit_card','bank_transfer','check') DEFAULT NULL,
      `amount_paid` decimal(10,2) DEFAULT 0.00,
      `change_amount` decimal(10,2) DEFAULT 0.00,
      `status` enum('pending','completed','cancelled') DEFAULT 'pending',
      `notes` text DEFAULT NULL,
      `created_by` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `completed_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_receipt_number` (`receipt_number`),
      KEY `idx_status` (`status`),
      KEY `idx_created_at` (`created_at`),
      KEY `fk_pos_sales_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "pos_sales table created successfully.\n";

    // POS Sale Items table
    $sql = "CREATE TABLE IF NOT EXISTS `pos_sale_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `sale_id` int(11) NOT NULL,
      `inventory_item_id` int(11) NOT NULL,
      `quantity` int(11) NOT NULL,
      `unit_price` decimal(10,2) NOT NULL,
      `discount_percentage` decimal(5,2) DEFAULT 0.00,
      `discount_amount` decimal(10,2) DEFAULT 0.00,
      `total_amount` decimal(10,2) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_sale_id` (`sale_id`),
      KEY `idx_inventory_item_id` (`inventory_item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "pos_sale_items table created successfully.\n";

    // Add indexes for better performance
    try {
        $pdo->exec("CREATE INDEX idx_pos_sales_customer ON pos_sales (customer_name, customer_phone)");
        echo "Customer index created.\n";
    } catch (Exception $e) {
        echo "Customer index already exists or error: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("CREATE INDEX idx_pos_sales_payment ON pos_sales (payment_method, status)");
        echo "Payment index created.\n";
    } catch (Exception $e) {
        echo "Payment index already exists or error: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("CREATE INDEX idx_pos_sale_items_composite ON pos_sale_items (sale_id, inventory_item_id)");
        echo "Composite index created.\n";
    } catch (Exception $e) {
        echo "Composite index already exists or error: " . $e->getMessage() . "\n";
    }

    echo "\nPOS system database setup completed successfully!\n";
    echo "You can now access the POS system at: http://localhost/4nSolar/pos.php\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
