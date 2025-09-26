-- Add original_unit_price field to quote_items table to track original price
-- This allows us to show strikethrough original price when unit price is changed

ALTER TABLE `quote_items` 
ADD COLUMN `original_unit_price` decimal(10,2) DEFAULT NULL COMMENT 'Original unit price when item was first added to quote';

-- Update existing records to set original_unit_price = unit_price
UPDATE `quote_items` SET `original_unit_price` = `unit_price` WHERE `original_unit_price` IS NULL;
