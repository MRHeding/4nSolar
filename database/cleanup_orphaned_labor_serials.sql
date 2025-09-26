-- Labor Serial Cleanup SQL Script
-- This script identifies and cleans up orphaned serial numbers in the inventory_serials table
-- that reference deleted inventory items (particularly labor items)

-- =============================================
-- STEP 1: IDENTIFY ORPHANED SERIALS
-- =============================================

-- Show all orphaned serials (serials referencing non-existent inventory items)
SELECT 
    s.id,
    s.serial_number,
    s.inventory_item_id,
    s.status,
    s.created_at,
    CASE 
        WHEN s.serial_number LIKE 'LAB-%' THEN 'LABOR_PREFIX'
        WHEN s.serial_number LIKE 'PAN-%' THEN 'PANEL_PREFIX'
        WHEN s.serial_number LIKE 'INV-%' THEN 'INVERTER_PREFIX'
        WHEN s.serial_number LIKE 'BAT-%' THEN 'BATTERY_PREFIX'
        WHEN s.serial_number LIKE 'HYB-%' THEN 'HYBRID_PREFIX'
        ELSE 'OTHER'
    END as serial_type
FROM inventory_serials s
LEFT JOIN inventory_items i ON s.inventory_item_id = i.id
WHERE i.id IS NULL
ORDER BY s.serial_number;

-- Count orphaned serials
SELECT COUNT(*) as orphaned_serials_count
FROM inventory_serials s
LEFT JOIN inventory_items i ON s.inventory_item_id = i.id
WHERE i.id IS NULL;

-- =============================================
-- STEP 2: IDENTIFY LABOR-RELATED SERIALS
-- =============================================

-- Show labor-related serials (by prefix or item type)
SELECT 
    s.id,
    s.serial_number,
    s.inventory_item_id,
    s.status,
    i.brand,
    i.model,
    i.is_active,
    CASE 
        WHEN s.serial_number LIKE 'LAB-%' THEN 'LABOR_PREFIX'
        WHEN s.serial_number LIKE 'PAN-%' AND (i.brand = 'LABOR' OR i.model LIKE '%Labor%') THEN 'LABOR_ITEM'
        WHEN i.brand = 'LABOR' OR i.model LIKE '%Labor%' THEN 'LABOR_ITEM'
        ELSE 'OTHER'
    END as serial_type
FROM inventory_serials s
LEFT JOIN inventory_items i ON s.inventory_item_id = i.id
WHERE s.serial_number LIKE 'LAB-%' 
   OR s.serial_number LIKE 'PAN-%'
   OR i.brand = 'LABOR' 
   OR i.model LIKE '%Labor%'
ORDER BY s.serial_number;

-- =============================================
-- STEP 3: CREATE BACKUP (RECOMMENDED)
-- =============================================

-- Create a backup table with all current serials
CREATE TABLE inventory_serials_backup AS 
SELECT * FROM inventory_serials;

-- =============================================
-- STEP 4: CLEANUP ORPHANED SERIALS
-- =============================================

-- WARNING: This will permanently delete orphaned serials!
-- Make sure you have a backup before running this!

-- Delete orphaned serials (uncomment to execute)
/*
DELETE FROM inventory_serials 
WHERE id IN (
    SELECT s.id
    FROM inventory_serials s
    LEFT JOIN inventory_items i ON s.inventory_item_id = i.id
    WHERE i.id IS NULL
);
*/

-- =============================================
-- STEP 5: VERIFICATION
-- =============================================

-- Verify no orphaned serials remain
SELECT COUNT(*) as remaining_orphaned_serials
FROM inventory_serials s
LEFT JOIN inventory_items i ON s.inventory_item_id = i.id
WHERE i.id IS NULL;

-- Show remaining serials by status
SELECT status, COUNT(*) as count
FROM inventory_serials
GROUP BY status
ORDER BY count DESC;

-- Show remaining serials by prefix
SELECT 
    CASE 
        WHEN serial_number LIKE 'LAB-%' THEN 'LAB'
        WHEN serial_number LIKE 'PAN-%' THEN 'PAN'
        WHEN serial_number LIKE 'INV-%' THEN 'INV'
        WHEN serial_number LIKE 'BAT-%' THEN 'BAT'
        WHEN serial_number LIKE 'HYB-%' THEN 'HYB'
        ELSE 'OTHER'
    END as prefix,
    COUNT(*) as count
FROM inventory_serials 
GROUP BY prefix
ORDER BY count DESC;

-- =============================================
-- STEP 6: CLEANUP BACKUP TABLE (OPTIONAL)
-- =============================================

-- After verifying everything is correct, you can drop the backup table
-- DROP TABLE inventory_serials_backup;

-- =============================================
-- USAGE INSTRUCTIONS
-- =============================================

/*
INSTRUCTIONS:

1. Run the identification queries (Steps 1-2) to see what orphaned serials exist
2. Create a backup (Step 3) - this is HIGHLY RECOMMENDED
3. Review the orphaned serials list carefully
4. Uncomment and run the cleanup query (Step 4) if you want to delete orphaned serials
5. Run the verification queries (Step 5) to confirm the cleanup worked
6. Optionally drop the backup table (Step 6) after confirming everything is correct

SAFETY NOTES:
- Always create a backup before running any DELETE operations
- Test on a copy of your database first if possible
- The backup table created in Step 3 can be used to restore data if needed
- Orphaned serials are those that reference inventory items that no longer exist
*/









