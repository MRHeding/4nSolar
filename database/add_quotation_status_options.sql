-- Add new status options to quotations table
-- This migration adds 'ongoing' and 'completed' status options to quotations

-- Update the status enum to include new options
ALTER TABLE `quotations` 
MODIFY COLUMN `status` enum('draft','sent','under_review','accepted','ongoing','completed','rejected','expired') DEFAULT 'draft';

-- Add comments for documentation
ALTER TABLE `quotations` 
COMMENT = 'Quotations table with extended status options including ongoing and completed';

