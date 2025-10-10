# 4NSOLAR System Settings Guide

## Overview
The 4NSOLAR system now includes a comprehensive settings system that allows administrators to customize the appearance and branding of the application. This includes header colors, tab menu hover colors, company title, and more.

## Features

### 🎨 Visual Customization
- **Header Background Color**: Customize the main header background color
- **Header Text Color**: Set the text color for header elements
- **Sidebar Hover Color**: Customize the color when hovering over menu items
- **Sidebar Active Color**: Set the color for the currently active menu item
- **Primary Color**: Main brand color used throughout the system
- **Secondary Color**: Secondary brand color
- **Accent Color**: Highlight color for special elements

### 🏢 Brand Customization
- **Company Title**: Change the main company name displayed in the header
- **Company Subtitle**: Customize the subtitle text
- **Logo URL**: Set the path to your company logo
- **Favicon URL**: Set the path to your favicon

### 🌙 Theme Settings
- **Default Theme Mode**: Set the default theme (light/dark)

## Installation

The settings system is automatically installed when you run the installation script:

```bash
php install_settings.php
```

Or manually execute the SQL file:
```bash
mysql -u root 4nsolar_inventory < database/system_settings.sql
```

## Usage

### Accessing Settings
1. Log in as an administrator
2. Navigate to the "Settings" menu item in the sidebar
3. Customize your preferences
4. Click "Save Settings"

### Available Settings

| Setting | Type | Description | Default Value |
|---------|------|-------------|---------------|
| `header_background_color` | Color | Header background color | `#1e40af` |
| `header_text_color` | Color | Header text color | `#ffffff` |
| `sidebar_hover_color` | Color | Sidebar menu hover color | `#3b82f6` |
| `sidebar_active_color` | Color | Sidebar active menu item color | `#1e40af` |
| `company_title` | Text | Company title displayed in header | `4NSOLAR ELECTRICZ` |
| `company_subtitle` | Text | Company subtitle displayed in header | `Business Management System` |
| `primary_color` | Color | Primary brand color | `#1e40af` |
| `secondary_color` | Color | Secondary brand color | `#3b82f6` |
| `accent_color` | Color | Accent color for highlights | `#fbbf24` |
| `theme_mode` | Text | Default theme mode (light/dark) | `light` |
| `logo_url` | Text | Company logo URL | `images/logo.png` |
| `favicon_url` | Text | Favicon URL | `images/logo.png` |

## Technical Implementation

### Database Structure
The settings are stored in the `system_settings` table with the following structure:

```sql
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('color','text','number','boolean') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'ui',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting` (`setting_key`)
);
```

### PHP Functions
The system includes several helper functions in `includes/settings.php`:

- `getSystemSetting($key, $default)` - Get a setting value
- `updateSystemSetting($key, $value, $type, $description, $category)` - Update a setting
- `getSettingsByCategory($category)` - Get all settings in a category
- `getAllSystemSettings()` - Get all settings
- `deleteSystemSetting($key)` - Delete a setting
- `resetSettingsToDefault()` - Reset all settings to defaults
- `generateDynamicCSS()` - Generate CSS variables for dynamic styling

### Dynamic CSS
The system automatically generates CSS variables that are applied throughout the application:

```css
:root {
    --header-bg-color: #1e40af;
    --header-text-color: #ffffff;
    --sidebar-hover-color: #3b82f6;
    --sidebar-active-color: #1e40af;
    --primary-color: #1e40af;
    --secondary-color: #3b82f6;
    --accent-color: #fbbf24;
}
```

## File Structure

```
4nsolarSystem/
├── database/
│   └── system_settings.sql          # Database schema
├── includes/
│   ├── settings.php                 # Settings helper functions
│   └── header.php                   # Updated header with dynamic content
├── settings.php                     # Settings management page
├── install_settings.php             # Installation script
└── SETTINGS_SYSTEM_GUIDE.md         # This guide
```

## Security

- Only administrators can access the settings page
- All settings are validated before being saved
- SQL injection protection through prepared statements
- XSS protection through proper HTML escaping

## Troubleshooting

### Settings Not Updating
1. Check if you're logged in as an administrator
2. Verify the database connection
3. Check for JavaScript errors in the browser console
4. Clear browser cache

### Colors Not Applying
1. Ensure the settings are saved successfully
2. Check if the CSS variables are being generated correctly
3. Verify the color format (should be hex codes like #1e40af)

### Database Errors
1. Run the installation script again: `php install_settings.php`
2. Check database permissions
3. Verify the table structure

## Customization Examples

### Changing to a Green Theme
```php
updateSystemSetting('header_background_color', '#22c55e', 'color');
updateSystemSetting('primary_color', '#22c55e', 'color');
updateSystemSetting('sidebar_hover_color', '#16a34a', 'color');
```

### Changing Company Branding
```php
updateSystemSetting('company_title', 'Your Company Name', 'text');
updateSystemSetting('company_subtitle', 'Your Subtitle', 'text');
updateSystemSetting('logo_url', 'images/your-logo.png', 'text');
```

## Support

For technical support or questions about the settings system, please refer to the main system documentation or contact the development team.

## Version History

- **v1.0** - Initial release with basic UI customization
- **v1.1** - Added brand customization options
- **v1.2** - Added theme mode settings
- **v1.3** - Added asset management (logo, favicon)
