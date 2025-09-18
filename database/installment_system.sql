-- Installment System Database Schema
-- Run this SQL to add installment functionality to your 4nsolar system

-- Table for installment plans
CREATE TABLE `installment_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL DEFAULT 'Payment Plan',
  `total_amount` decimal(15,2) NOT NULL,
  `down_payment` decimal(15,2) DEFAULT 0.00,
  `installment_amount` decimal(15,2) NOT NULL,
  `number_of_installments` int(11) NOT NULL,
  `payment_frequency` enum('weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
  `interest_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Annual interest rate percentage',
  `late_fee_amount` decimal(10,2) DEFAULT 0.00,
  `late_fee_type` enum('fixed','percentage') DEFAULT 'fixed',
  `start_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','completed','cancelled','suspended') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`quotation_id`) REFERENCES `quotations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for individual installment payments
CREATE TABLE `installment_payments` (
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
  `paid_by` int(11) DEFAULT NULL COMMENT 'User who recorded the payment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`plan_id`) REFERENCES `installment_plans`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`paid_by`) REFERENCES `users`(`id`),
  UNIQUE KEY `unique_installment` (`plan_id`, `installment_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for payment history/transactions
CREATE TABLE `installment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `transaction_type` enum('payment','late_fee','adjustment','refund') DEFAULT 'payment',
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded receipt/proof',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`payment_id`) REFERENCES `installment_payments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add installment-related columns to quotations table
ALTER TABLE `quotations` 
ADD COLUMN `has_installment_plan` tinyint(1) DEFAULT 0,
ADD COLUMN `payment_terms` text DEFAULT NULL,
ADD COLUMN `installment_status` enum('none','pending','active','completed','default') DEFAULT 'none';

-- Create indexes for better performance
CREATE INDEX `idx_installment_due_date` ON `installment_payments`(`due_date`);
CREATE INDEX `idx_installment_status` ON `installment_payments`(`status`);
CREATE INDEX `idx_plan_status` ON `installment_plans`(`status`);
CREATE INDEX `idx_quotation_installment` ON `quotations`(`has_installment_plan`);

-- Sample data for testing (optional)
-- You can remove this section after implementing

-- Insert sample installment plan
-- INSERT INTO `installment_plans` (`quotation_id`, `plan_name`, `total_amount`, `down_payment`, `installment_amount`, `number_of_installments`, `payment_frequency`, `interest_rate`, `start_date`, `created_by`)
-- VALUES (14, '32kw Solar Panel - 12 Month Plan', 1920000.00, 384000.00, 128000.00, 12, 'monthly', 2.50, '2025-10-01', 1);

-- System configuration table for installment settings
CREATE TABLE `installment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting` (`setting_key`),
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default installment settings
INSERT INTO `installment_settings` (`setting_key`, `setting_value`, `description`) VALUES
('default_interest_rate', '2.5', 'Default annual interest rate percentage'),
('default_late_fee', '500.00', 'Default late fee amount in PHP'),
('late_fee_type', 'fixed', 'Default late fee type (fixed or percentage)'),
('grace_period_days', '5', 'Days after due date before late fee applies'),
('min_down_payment_percent', '20', 'Minimum down payment percentage required'),
('max_installment_months', '36', 'Maximum number of installment months allowed'),
('auto_generate_receipts', '1', 'Automatically generate receipt numbers for payments'),
('payment_reminder_days', '3', 'Days before due date to send payment reminders');
