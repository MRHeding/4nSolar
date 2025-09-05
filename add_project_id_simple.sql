-- ===================================================
-- SIMPLE VERSION - Quotation to Project Enhancement
-- Execute this if you want a quick setup without checks
-- ===================================================

USE 4nsolar_inventory;

-- Add columns to link quotations and projects
ALTER TABLE quotations ADD COLUMN IF NOT EXISTS project_id INT NULL COMMENT 'ID of the solar project created from this quotation';
ALTER TABLE solar_projects ADD COLUMN IF NOT EXISTS quote_id INT NULL COMMENT 'ID of the quotation that created this project';

-- Add indexes for better performance
ALTER TABLE quotations ADD INDEX IF NOT EXISTS idx_project_id (project_id);
ALTER TABLE solar_projects ADD INDEX IF NOT EXISTS idx_quote_id (quote_id);

-- Add foreign key constraints for data integrity
ALTER TABLE quotations ADD CONSTRAINT IF NOT EXISTS fk_quotations_project_id 
    FOREIGN KEY (project_id) REFERENCES solar_projects(id) ON DELETE SET NULL;

ALTER TABLE solar_projects ADD CONSTRAINT IF NOT EXISTS fk_solar_projects_quote_id 
    FOREIGN KEY (quote_id) REFERENCES quotations(id) ON DELETE SET NULL;

-- Show completion message
SELECT 'Database enhancement completed successfully!' as message;
