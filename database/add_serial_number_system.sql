-- Add Serial Number System to 4nSolar Inventory
-- This migration adds unique serial number tracking for inventory items

-- 1. Create inventory_serials table to track individual serial numbers
CREATE TABLE `inventory_serials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_item_id` int(11) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `status` enum('available','sold','reserved','damaged','returned') DEFAULT 'available',
  `sale_id` int(11) DEFAULT NULL COMMENT 'POS sale ID when sold',
  `quote_id` int(11) DEFAULT NULL COMMENT 'Quote ID when reserved',
  `project_id` int(11) DEFAULT NULL COMMENT 'Project ID when used in project',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `inventory_item_id` (`inventory_item_id`),
  KEY `status` (`status`),
  KEY `sale_id` (`sale_id`),
  KEY `quote_id` (`quote_id`),
  KEY `project_id` (`project_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_inventory_serials_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_serials_sale` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inventory_serials_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inventory_serials_project` FOREIGN KEY (`project_id`) REFERENCES `solar_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inventory_serials_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Add serial number tracking fields to pos_sale_items
ALTER TABLE `pos_sale_items` 
ADD COLUMN `serial_numbers` text DEFAULT NULL COMMENT 'JSON array of serial numbers used in this sale item',
ADD COLUMN `serial_count` int(11) DEFAULT 0 COMMENT 'Number of serial numbers used';

-- 3. Add serial number tracking fields to quote_items
ALTER TABLE `quote_items` 
ADD COLUMN `serial_numbers` text DEFAULT NULL COMMENT 'JSON array of serial numbers reserved for this quote item',
ADD COLUMN `serial_count` int(11) DEFAULT 0 COMMENT 'Number of serial numbers reserved';

-- 4. Add serial number tracking fields to solar_project_items
ALTER TABLE `solar_project_items` 
ADD COLUMN `serial_numbers` text DEFAULT NULL COMMENT 'JSON array of serial numbers used in this project item',
ADD COLUMN `serial_count` int(11) DEFAULT 0 COMMENT 'Number of serial numbers used';

-- 5. Add serial number generation settings to inventory_items
ALTER TABLE `inventory_items` 
ADD COLUMN `generate_serials` tinyint(1) DEFAULT 0 COMMENT 'Whether to generate serial numbers for this item',
ADD COLUMN `serial_prefix` varchar(20) DEFAULT NULL COMMENT 'Prefix for serial numbers (e.g., INV, BAT, PAN)',
ADD COLUMN `serial_format` varchar(50) DEFAULT 'YYYY-NNNNNN' COMMENT 'Format for serial numbers',
ADD COLUMN `next_serial_number` int(11) DEFAULT 1 COMMENT 'Next serial number to generate';

-- 6. Create index for better performance
CREATE INDEX `idx_inventory_serials_item_status` ON `inventory_serials` (`inventory_item_id`, `status`);
CREATE INDEX `idx_inventory_serials_available` ON `inventory_serials` (`status`, `inventory_item_id`) WHERE `status` = 'available';

-- 7. Insert default serial number settings for existing categories
UPDATE `inventory_items` SET 
  `generate_serials` = 1,
  `serial_prefix` = CASE 
    WHEN `category_id` = 2 THEN 'INV'  -- Inverters
    WHEN `category_id` = 9 THEN 'HYB'  -- Hybrid Inverters  
    WHEN `category_id` = 3 THEN 'BAT'  -- Batteries
    WHEN `category_id` = 1 THEN 'PAN'  -- Solar Panels
    ELSE 'ITM'  -- Other items
  END,
  `serial_format` = 'YYYY-NNNNNN',
  `next_serial_number` = 1
WHERE `category_id` IN (1, 2, 3, 9); -- Only for Solar Panels, Inverters, Batteries, Hybrid Inverters

-- 8. Create function to generate serial numbers
DELIMITER $$

CREATE FUNCTION `generate_serial_number`(
  p_inventory_item_id INT,
  p_quantity INT DEFAULT 1
) RETURNS JSON
READS SQL DATA
DETERMINISTIC
BEGIN
  DECLARE v_prefix VARCHAR(20);
  DECLARE v_format VARCHAR(50);
  DECLARE v_next_number INT;
  DECLARE v_year VARCHAR(4);
  DECLARE v_serial VARCHAR(100);
  DECLARE v_serials JSON DEFAULT JSON_ARRAY();
  DECLARE v_counter INT DEFAULT 0;
  
  -- Get item settings
  SELECT `serial_prefix`, `serial_format`, `next_serial_number`
  INTO v_prefix, v_format, v_next_number
  FROM `inventory_items`
  WHERE `id` = p_inventory_item_id AND `generate_serials` = 1;
  
  -- If item doesn't generate serials, return empty array
  IF v_prefix IS NULL THEN
    RETURN JSON_ARRAY();
  END IF;
  
  -- Get current year
  SET v_year = YEAR(NOW());
  
  -- Generate serial numbers
  WHILE v_counter < p_quantity DO
    SET v_serial = CONCAT(v_prefix, '-', v_year, '-', LPAD(v_next_number + v_counter, 6, '0'));
    SET v_serials = JSON_ARRAY_APPEND(v_serials, '$', v_serial);
    SET v_counter = v_counter + 1;
  END WHILE;
  
  -- Update next serial number
  UPDATE `inventory_items` 
  SET `next_serial_number` = `next_serial_number` + p_quantity
  WHERE `id` = p_inventory_item_id;
  
  RETURN v_serials;
END$$

DELIMITER ;

-- 9. Create procedure to add serial numbers to inventory
DELIMITER $$

CREATE PROCEDURE `add_inventory_serials`(
  IN p_inventory_item_id INT,
  IN p_quantity INT,
  IN p_created_by INT
)
BEGIN
  DECLARE v_serials JSON;
  DECLARE v_serial VARCHAR(100);
  DECLARE v_counter INT DEFAULT 0;
  DECLARE v_serial_count INT;
  
  -- Generate serial numbers
  SET v_serials = generate_serial_number(p_inventory_item_id, p_quantity);
  SET v_serial_count = JSON_LENGTH(v_serials);
  
  -- Insert serial numbers into inventory_serials table
  WHILE v_counter < v_serial_count DO
    SET v_serial = JSON_UNQUOTE(JSON_EXTRACT(v_serials, CONCAT('$[', v_counter, ']')));
    
    INSERT INTO `inventory_serials` (
      `inventory_item_id`, 
      `serial_number`, 
      `status`, 
      `created_by`
    ) VALUES (
      p_inventory_item_id, 
      v_serial, 
      'available', 
      p_created_by
    );
    
    SET v_counter = v_counter + 1;
  END WHILE;
  
  -- Update stock quantity
  UPDATE `inventory_items` 
  SET `stock_quantity` = `stock_quantity` + p_quantity
  WHERE `id` = p_inventory_item_id;
  
END$$

DELIMITER ;

-- 10. Create procedure to reserve serial numbers for quotes
DELIMITER $$

CREATE PROCEDURE `reserve_serials_for_quote`(
  IN p_quote_id INT,
  IN p_inventory_item_id INT,
  IN p_quantity INT
)
BEGIN
  DECLARE v_available_count INT;
  DECLARE v_serial_id INT;
  DECLARE v_counter INT DEFAULT 0;
  DECLARE v_serials JSON DEFAULT JSON_ARRAY();
  
  -- Check available serials
  SELECT COUNT(*) INTO v_available_count
  FROM `inventory_serials`
  WHERE `inventory_item_id` = p_inventory_item_id 
    AND `status` = 'available';
  
  -- If not enough available serials, generate new ones
  IF v_available_count < p_quantity THEN
    CALL add_inventory_serials(p_inventory_item_id, p_quantity - v_available_count, 1);
  END IF;
  
  -- Reserve serials
  WHILE v_counter < p_quantity DO
    SELECT `id` INTO v_serial_id
    FROM `inventory_serials`
    WHERE `inventory_item_id` = p_inventory_item_id 
      AND `status` = 'available'
    LIMIT 1;
    
    UPDATE `inventory_serials`
    SET `status` = 'reserved', `quote_id` = p_quote_id
    WHERE `id` = v_serial_id;
    
    SET v_serials = JSON_ARRAY_APPEND(v_serials, '$', v_serial_id);
    SET v_counter = v_counter + 1;
  END WHILE;
  
  -- Update quote_items with serial information
  UPDATE `quote_items`
  SET `serial_numbers` = (
    SELECT JSON_ARRAYAGG(`serial_number`)
    FROM `inventory_serials`
    WHERE `id` IN (SELECT JSON_UNQUOTE(JSON_EXTRACT(v_serials, CONCAT('$[', numbers.n, ']')))
                   FROM JSON_TABLE(JSON_ARRAY(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19), '$[*]' COLUMNS (n FOR ORDINALITY) numbers)
                   WHERE numbers.n < JSON_LENGTH(v_serials))
  ),
  `serial_count` = p_quantity
  WHERE `quote_id` = p_quote_id AND `inventory_item_id` = p_inventory_item_id;
  
END$$

DELIMITER ;

-- 11. Create procedure to sell serial numbers in POS
DELIMITER $$

CREATE PROCEDURE `sell_serials_in_pos`(
  IN p_sale_id INT,
  IN p_inventory_item_id INT,
  IN p_quantity INT
)
BEGIN
  DECLARE v_available_count INT;
  DECLARE v_serial_id INT;
  DECLARE v_counter INT DEFAULT 0;
  DECLARE v_serials JSON DEFAULT JSON_ARRAY();
  
  -- Check available serials
  SELECT COUNT(*) INTO v_available_count
  FROM `inventory_serials`
  WHERE `inventory_item_id` = p_inventory_item_id 
    AND `status` = 'available';
  
  -- If not enough available serials, generate new ones
  IF v_available_count < p_quantity THEN
    CALL add_inventory_serials(p_inventory_item_id, p_quantity - v_available_count, 1);
  END IF;
  
  -- Sell serials
  WHILE v_counter < p_quantity DO
    SELECT `id` INTO v_serial_id
    FROM `inventory_serials`
    WHERE `inventory_item_id` = p_inventory_item_id 
      AND `status` = 'available'
    LIMIT 1;
    
    UPDATE `inventory_serials`
    SET `status` = 'sold', `sale_id` = p_sale_id
    WHERE `id` = v_serial_id;
    
    SET v_serials = JSON_ARRAY_APPEND(v_serials, '$', v_serial_id);
    SET v_counter = v_counter + 1;
  END WHILE;
  
  -- Update pos_sale_items with serial information
  UPDATE `pos_sale_items`
  SET `serial_numbers` = (
    SELECT JSON_ARRAYAGG(`serial_number`)
    FROM `inventory_serials`
    WHERE `id` IN (SELECT JSON_UNQUOTE(JSON_EXTRACT(v_serials, CONCAT('$[', numbers.n, ']')))
                   FROM JSON_TABLE(JSON_ARRAY(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19), '$[*]' COLUMNS (n FOR ORDINALITY) numbers)
                   WHERE numbers.n < JSON_LENGTH(v_serials))
  ),
  `serial_count` = p_quantity
  WHERE `sale_id` = p_sale_id AND `inventory_item_id` = p_inventory_item_id;
  
  -- Update stock quantity
  UPDATE `inventory_items` 
  SET `stock_quantity` = `stock_quantity` - p_quantity
  WHERE `id` = p_inventory_item_id;
  
END$$

DELIMITER ;

-- 12. Create view for available serials by item
CREATE VIEW `available_serials` AS
SELECT 
  i.`id` as `inventory_item_id`,
  i.`brand`,
  i.`model`,
  i.`category_id`,
  c.`name` as `category_name`,
  i.`size_specification`,
  i.`selling_price`,
  COUNT(s.`id`) as `available_serials_count`,
  GROUP_CONCAT(s.`serial_number` ORDER BY s.`serial_number` SEPARATOR ', ') as `available_serial_numbers`
FROM `inventory_items` i
LEFT JOIN `categories` c ON i.`category_id` = c.`id`
LEFT JOIN `inventory_serials` s ON i.`id` = s.`inventory_item_id` AND s.`status` = 'available'
WHERE i.`is_active` = 1 AND i.`generate_serials` = 1
GROUP BY i.`id`, i.`brand`, i.`model`, i.`category_id`, c.`name`, i.`size_specification`, i.`selling_price`
HAVING `available_serials_count` > 0;

-- 13. Create trigger to automatically generate serials when stock is added
DELIMITER $$

CREATE TRIGGER `tr_inventory_stock_add_serials`
AFTER UPDATE ON `inventory_items`
FOR EACH ROW
BEGIN
  DECLARE v_stock_diff INT;
  
  -- Calculate stock difference
  SET v_stock_diff = NEW.`stock_quantity` - OLD.`stock_quantity`;
  
  -- If stock increased and item generates serials, create serial numbers
  IF v_stock_diff > 0 AND NEW.`generate_serials` = 1 THEN
    CALL add_inventory_serials(NEW.`id`, v_stock_diff, NEW.`created_by`);
  END IF;
END$$

DELIMITER ;

COMMIT;



