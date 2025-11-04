-- Update POS Sales Records to 168shop
-- This script updates sales records from October 15, 2025 to November 3, 2025
-- to set shop_type to '168shop'

-- Update records in the date range
UPDATE `pos_sales` 
SET `shop_type` = '168shop' 
WHERE DATE(`created_at`) BETWEEN '2025-10-15' AND '2025-11-03';

-- Verify the update
SELECT 
    DATE(created_at) as sale_date,
    shop_type,
    COUNT(*) as record_count,
    SUM(total_amount) as total_revenue
FROM `pos_sales`
WHERE DATE(created_at) BETWEEN '2025-10-15' AND '2025-11-03'
GROUP BY DATE(created_at), shop_type
ORDER BY sale_date;
