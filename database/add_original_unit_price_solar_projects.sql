-- Add original_unit_price field to solar_project_items table to track original price
-- This allows us to show strikethrough original price when unit price is changed

ALTER TABLE `solar_project_items` 
ADD COLUMN `original_unit_price` decimal(10,2) DEFAULT NULL COMMENT 'Original unit price when item was first added to project';

-- Update existing records to set original_unit_price = unit_selling_price
UPDATE `solar_project_items` SET `original_unit_price` = `unit_selling_price` WHERE `original_unit_price` IS NULL;
