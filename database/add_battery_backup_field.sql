-- SQL script to add battery_backup_capacity field to quote_solar_details table

ALTER TABLE `quote_solar_details` 
ADD COLUMN `battery_backup_capacity` enum('yes','no') DEFAULT NULL AFTER `installation_status_maintenance`;
