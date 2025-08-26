-- Add image field to inventory_items table
ALTER TABLE inventory_items ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER description;
