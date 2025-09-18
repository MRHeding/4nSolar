<?php
// Simple Installment System Database Setup
require_once 'includes/config.php';

// Check if running from command line or web
$is_web = isset($_SERVER['HTTP_HOST']);

if ($is_web) {
    echo "<h2>Installing Installment System...</h2><pre>";
}

try {
    echo "Creating installment_plans table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `installment_plans` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `quotation_id` int(11) NOT NULL,
        `plan_name` varchar(255) NOT NULL DEFAULT 'Payment Plan',
        `total_amount` decimal(15,2) NOT NULL,
        `down_payment` decimal(15,2) DEFAULT 0.00,
        `installment_amount` decimal(15,2) NOT NULL,
        `number_of_installments` int(11) NOT NULL,
        `payment_frequency` enum('weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
        `interest_rate` decimal(5,2) DEFAULT 0.00,
        `late_fee_amount` decimal(10,2) DEFAULT 0.00,
        `late_fee_type` enum('fixed','percentage') DEFAULT 'fixed',
        `start_date` date NOT NULL,
        `notes` text DEFAULT NULL,
        `status` enum('active','completed','cancelled','suspended') DEFAULT 'active',
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `quotation_id` (`quotation_id`),
        KEY `created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "✓ installment_plans table created\n";

    echo "Creating installment_payments table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `installment_payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plan_id` int(11) NOT NULL,
        `installment_number` int(11) NOT NULL,
        `due_date` date NOT NULL,
        `due_amount` decimal(15,2) NOT NULL,
        `paid_amount` decimal(15,2) DEFAULT 0.00,
        `payment_date` date DEFAULT NULL,
        `late_fee_applied` decimal(10,2) DEFAULT 0.00,
        `payment_method` enum('cash','check','bank_transfer','gcash','paymaya','card','other') DEFAULT 'cash',
        `reference_number` varchar(100) DEFAULT NULL,
        `receipt_number` varchar(100) DEFAULT NULL,
        `status` enum('pending','paid','partial','overdue','waived') DEFAULT 'pending',
        `notes` text DEFAULT NULL,
        `paid_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `plan_id` (`plan_id`),
        KEY `paid_by` (`paid_by`),
        UNIQUE KEY `unique_installment` (`plan_id`, `installment_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "✓ installment_payments table created\n";

    echo "Creating installment_transactions table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `installment_transactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `payment_id` int(11) NOT NULL,
        `transaction_type` enum('payment','late_fee','adjustment','refund') DEFAULT 'payment',
        `amount` decimal(15,2) NOT NULL,
        `description` varchar(255) DEFAULT NULL,
        `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
        `processed_by` int(11) DEFAULT NULL,
        `reference_number` varchar(100) DEFAULT NULL,
        `receipt_path` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `payment_id` (`payment_id`),
        KEY `processed_by` (`processed_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "✓ installment_transactions table created\n";

    echo "Creating installment_settings table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `installment_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(100) NOT NULL,
        `setting_value` text NOT NULL,
        `description` text DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_setting` (`setting_key`),
        KEY `updated_by` (`updated_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    echo "✓ installment_settings table created\n";

    echo "Adding columns to quotations table...\n";
    try {
        $pdo->exec("ALTER TABLE `quotations` 
                   ADD COLUMN `has_installment_plan` tinyint(1) DEFAULT 0,
                   ADD COLUMN `payment_terms` text DEFAULT NULL,
                   ADD COLUMN `installment_status` enum('none','pending','active','completed','default') DEFAULT 'none'");
        echo "✓ quotations table updated\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ quotations table columns already exist\n";
        } else {
            throw $e;
        }
    }

    echo "Creating indexes...\n";
    try {
        $pdo->exec("CREATE INDEX `idx_installment_due_date` ON `installment_payments`(`due_date`)");
        $pdo->exec("CREATE INDEX `idx_installment_status` ON `installment_payments`(`status`)");
        $pdo->exec("CREATE INDEX `idx_plan_status` ON `installment_plans`(`status`)");
        $pdo->exec("CREATE INDEX `idx_quotation_installment` ON `quotations`(`has_installment_plan`)");
        echo "✓ indexes created\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⚠ indexes already exist\n";
        } else {
            throw $e;
        }
    }

    echo "Inserting default settings...\n";
    $default_settings = [
        ['default_interest_rate', '2.5', 'Default annual interest rate percentage'],
        ['default_late_fee', '500.00', 'Default late fee amount in PHP'],
        ['late_fee_type', 'fixed', 'Default late fee type (fixed or percentage)'],
        ['grace_period_days', '5', 'Days after due date before late fee applies'],
        ['min_down_payment_percent', '20', 'Minimum down payment percentage required'],
        ['max_installment_months', '36', 'Maximum number of installment months allowed'],
        ['auto_generate_receipts', '1', 'Automatically generate receipt numbers for payments'],
        ['payment_reminder_days', '3', 'Days before due date to send payment reminders']
    ];

    foreach ($default_settings as [$key, $value, $description]) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO installment_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        $stmt->execute([$key, $value, $description]);
    }
    echo "✓ default settings inserted\n";

    echo "\n🎉 INSTALLATION COMPLETE!\n\n";
    echo "The installment system has been successfully installed.\n";
    echo "You can now create payment plans for accepted quotations.\n\n";
    
    if ($is_web) {
        echo "</pre>";
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>✅ Installation Successful!</strong><br>";
        echo "The installment system is now ready to use.<br><br>";
        echo "<a href='quotations.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Quotations</a>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    if ($is_web) {
        echo "</pre>";
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>Installation Failed:</strong><br>" . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
}
?>
