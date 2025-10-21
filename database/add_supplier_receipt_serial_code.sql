-- Add supplier_receipt and serial_code columns to stock_movements table
ALTER TABLE `stock_movements` 
ADD COLUMN `supplier_receipt` VARCHAR(255) DEFAULT NULL AFTER `notes`,
ADD COLUMN `serial_code` VARCHAR(255) DEFAULT NULL AFTER `supplier_receipt`;

