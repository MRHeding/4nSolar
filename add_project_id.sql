-- ===================================================
-- Quotation to Solar Project Conversion Enhancement
-- Database Schema Update Script
-- Date: September 5, 2025
-- ===================================================

-- Use the correct database
USE 4nsolar_inventory;

-- ===================================================
-- 1. Add project_id column to quotations table
-- This allows tracking which project was created from a quotation
-- ===================================================

-- Check if column already exists before adding
SET @sql = 'SELECT COUNT(*) INTO @col_exists 
           FROM information_schema.columns 
           WHERE table_schema = DATABASE() 
           AND table_name = "quotations" 
           AND column_name = "project_id"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add project_id column if it doesn't exist
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE quotations ADD COLUMN project_id INT NULL COMMENT "ID of the solar project created from this quotation"',
    'SELECT "Column project_id already exists in quotations table" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for project_id if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @idx_exists 
           FROM information_schema.statistics 
           WHERE table_schema = DATABASE() 
           AND table_name = "quotations" 
           AND index_name = "idx_project_id"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE quotations ADD INDEX idx_project_id (project_id)',
    'SELECT "Index idx_project_id already exists on quotations table" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ===================================================
-- 2. Add quote_id column to solar_projects table
-- This allows tracking which quotation created a project
-- ===================================================

-- Check if column already exists before adding
SET @sql = 'SELECT COUNT(*) INTO @col_exists 
           FROM information_schema.columns 
           WHERE table_schema = DATABASE() 
           AND table_name = "solar_projects" 
           AND column_name = "quote_id"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add quote_id column if it doesn't exist
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE solar_projects ADD COLUMN quote_id INT NULL COMMENT "ID of the quotation that created this project"',
    'SELECT "Column quote_id already exists in solar_projects table" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for quote_id if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @idx_exists 
           FROM information_schema.statistics 
           WHERE table_schema = DATABASE() 
           AND table_name = "solar_projects" 
           AND index_name = "idx_quote_id"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@idx_exists = 0, 
    'ALTER TABLE solar_projects ADD INDEX idx_quote_id (quote_id)',
    'SELECT "Index idx_quote_id already exists on solar_projects table" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ===================================================
-- 3. Add foreign key constraints (optional but recommended)
-- These ensure data integrity between quotations and projects
-- ===================================================

-- Add foreign key constraint for quotations.project_id -> solar_projects.id
SET @sql = 'SELECT COUNT(*) INTO @fk_exists 
           FROM information_schema.key_column_usage 
           WHERE table_schema = DATABASE() 
           AND table_name = "quotations" 
           AND constraint_name = "fk_quotations_project_id"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE quotations ADD CONSTRAINT fk_quotations_project_id FOREIGN KEY (project_id) REFERENCES solar_projects(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_quotations_project_id already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint for solar_projects.quote_id -> quotations.id
SET @sql = 'SELECT COUNT(*) INTO @fk_exists 
           FROM information_schema.key_column_usage 
           WHERE table_schema = DATABASE() 
           AND table_name = "solar_projects" 
           AND constraint_name = "fk_solar_projects_quote_id"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE solar_projects ADD CONSTRAINT fk_solar_projects_quote_id FOREIGN KEY (quote_id) REFERENCES quotations(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_solar_projects_quote_id already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ===================================================
-- 4. Display final table structures
-- ===================================================

SELECT "===== QUOTATIONS TABLE STRUCTURE =====" as info;
DESCRIBE quotations;

SELECT "===== SOLAR_PROJECTS TABLE STRUCTURE =====" as info;
DESCRIBE solar_projects;

-- ===================================================
-- 5. Verify the new columns and constraints
-- ===================================================

SELECT "===== VERIFICATION RESULTS =====" as info;

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'SUCCESS: project_id column exists in quotations table'
        ELSE 'ERROR: project_id column missing in quotations table'
    END as quotations_project_id_status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'quotations' 
AND column_name = 'project_id';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'SUCCESS: quote_id column exists in solar_projects table'
        ELSE 'ERROR: quote_id column missing in solar_projects table'
    END as projects_quote_id_status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'solar_projects' 
AND column_name = 'quote_id';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'SUCCESS: Index idx_project_id exists on quotations table'
        ELSE 'WARNING: Index idx_project_id missing on quotations table'
    END as quotations_index_status
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'quotations' 
AND index_name = 'idx_project_id';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'SUCCESS: Index idx_quote_id exists on solar_projects table'
        ELSE 'WARNING: Index idx_quote_id missing on solar_projects table'
    END as projects_index_status
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'solar_projects' 
AND index_name = 'idx_quote_id';

-- ===================================================
-- 6. Show sample data to verify everything is working
-- ===================================================

SELECT "===== SAMPLE DATA VERIFICATION =====" as info;

SELECT 
    COUNT(*) as total_quotations,
    COUNT(project_id) as quotations_with_projects
FROM quotations;

SELECT 
    COUNT(*) as total_projects,
    COUNT(quote_id) as projects_from_quotes
FROM solar_projects;

SELECT "Database enhancement completed successfully!" as completion_message;
