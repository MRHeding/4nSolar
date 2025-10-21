-- 4nSolar Inventory Backup
-- Created: 2025-10-16 04:10:06

-- Table: inventory_items
DROP TABLE IF EXISTS `inventory_items_backup`;
CREATE TABLE `inventory_items_backup` AS SELECT * FROM `inventory_items`;

-- Table: inventory_serials
DROP TABLE IF EXISTS `inventory_serials_backup`;
CREATE TABLE `inventory_serials_backup` AS SELECT * FROM `inventory_serials`;

-- Table: stock_movements
DROP TABLE IF EXISTS `stock_movements_backup`;
CREATE TABLE `stock_movements_backup` AS SELECT * FROM `stock_movements`;

-- Table: pos_sale_items
DROP TABLE IF EXISTS `pos_sale_items_backup`;
CREATE TABLE `pos_sale_items_backup` AS SELECT * FROM `pos_sale_items`;

-- Table: quote_items
DROP TABLE IF EXISTS `quote_items_backup`;
CREATE TABLE `quote_items_backup` AS SELECT * FROM `quote_items`;

-- Table: solar_project_items
DROP TABLE IF EXISTS `solar_project_items_backup`;
CREATE TABLE `solar_project_items_backup` AS SELECT * FROM `solar_project_items`;

