-- Add direct quote system support
-- This allows quotations to have items with custom quantities and prices
-- while still pulling item data from inventory

-- Add is_direct_quote column to quote_items table
ALTER TABLE `quote_items` 
ADD COLUMN `is_direct_quote` tinyint(1) DEFAULT 0 COMMENT 'Whether this is a direct quote item with custom pricing';

-- Create index for better performance
CREATE INDEX `idx_quote_items_direct_quote` ON `quote_items` (`is_direct_quote`);
