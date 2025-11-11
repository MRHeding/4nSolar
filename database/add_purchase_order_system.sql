-- Purchase Order System Database Tables
-- This file creates the necessary tables for the purchase order functionality

-- Create purchase_orders table
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `supplier_phone` varchar(50) DEFAULT NULL,
  `supplier_email` varchar(255) DEFAULT NULL,
  `supplier_address` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `delivery_requirements` text DEFAULT NULL,
  `status` enum('pending','ordered','received','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `quote_id` (`quote_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_purchase_orders_quote_id` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create purchase_order_items table
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `quote_item_id` int(11) NOT NULL,
  `inventory_item_id` int(11) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `received_quantity` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`),
  KEY `quote_item_id` (`quote_item_id`),
  KEY `inventory_item_id` (`inventory_item_id`),
  CONSTRAINT `fk_purchase_order_items_po_id` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_purchase_order_items_quote_item_id` FOREIGN KEY (`quote_item_id`) REFERENCES `quote_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_purchase_order_items_inventory_item_id` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX `idx_purchase_orders_supplier` ON `purchase_orders` (`supplier_name`);
CREATE INDEX `idx_purchase_orders_status_date` ON `purchase_orders` (`status`, `created_at`);
CREATE INDEX `idx_purchase_order_items_brand_model` ON `purchase_order_items` (`brand`, `model`);

-- Insert sample data (optional - remove if not needed)
-- INSERT INTO `purchase_orders` (`quote_id`, `po_number`, `supplier_name`, `contact_person`, `supplier_phone`, `supplier_email`, `supplier_address`, `special_instructions`, `delivery_requirements`, `status`) VALUES
-- (1, 'PO-2024-0001', 'SolarTech Supplies', 'John Smith', '+1-555-0123', 'john@solartech.com', '123 Solar Street, Tech City, TC 12345', 'Please ensure all items are properly packaged', 'Delivery required within 5 business days', 'pending');

















