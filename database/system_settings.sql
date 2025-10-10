-- System Settings Table for UI Customization
-- This table stores customizable settings for the 4NSOLAR system

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('color','text','number','boolean') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'ui',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting` (`setting_key`),
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`, `updated_by`) VALUES
('header_background_color', '#1e40af', 'color', 'Header background color', 'ui', 1),
('header_text_color', '#ffffff', 'color', 'Header text color', 'ui', 1),
('sidebar_hover_color', '#3b82f6', 'color', 'Sidebar menu hover color', 'ui', 1),
('sidebar_active_color', '#1e40af', 'color', 'Sidebar active menu item color', 'ui', 1),
('company_title', '4NSOLAR ELECTRICZ', 'text', 'Company title displayed in header', 'ui', 1),
('company_subtitle', 'Business Management System', 'text', 'Company subtitle displayed in header', 'ui', 1),
('primary_color', '#1e40af', 'color', 'Primary brand color', 'ui', 1),
('secondary_color', '#3b82f6', 'color', 'Secondary brand color', 'ui', 1),
('accent_color', '#fbbf24', 'color', 'Accent color for highlights', 'ui', 1),
('theme_mode', 'light', 'text', 'Default theme mode (light/dark)', 'ui', 1),
('logo_url', 'images/logo.png', 'text', 'Company logo URL', 'ui', 1),
('favicon_url', 'images/logo.png', 'text', 'Favicon URL', 'ui', 1);

-- Create indexes for better performance
CREATE INDEX `idx_setting_category` ON `system_settings`(`category`);
CREATE INDEX `idx_setting_type` ON `system_settings`(`setting_type`);
