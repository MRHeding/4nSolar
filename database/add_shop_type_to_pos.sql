-- Add Shop Type to POS Sales
-- This migration adds shop_type field to distinguish between 4nsolar and 168shop transactions

-- 1. Add shop_type column to pos_sales table
ALTER TABLE `pos_sales` 
ADD COLUMN `shop_type` ENUM('4nsolar', '168shop') DEFAULT '4nsolar' NOT NULL COMMENT 'Shop type for this POS transaction';

-- 2. Add index on shop_type for faster filtering
ALTER TABLE `pos_sales`
ADD INDEX `idx_shop_type` (`shop_type`);

-- 3. Add composite index for optimized queries (shop_type, status, created_at)
ALTER TABLE `pos_sales`
ADD INDEX `idx_shop_status_date` (`shop_type`, `status`, `created_at`);

-- 4. Update existing records to have default shop_type (optional, but ensures data integrity)
UPDATE `pos_sales` SET `shop_type` = '4nsolar' WHERE `shop_type` IS NULL OR `shop_type` = '';
