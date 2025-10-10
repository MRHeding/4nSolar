<?php
/**
 * Settings System Installation Script
 * Run this script once to set up the settings system
 */

require_once 'includes/config.php';

try {
    // Check if system_settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "Creating system_settings table...\n";
        
        // Read and execute the SQL file
        $sql = file_get_contents('database/system_settings.sql');
        $pdo->exec($sql);
        
        echo "✅ System settings table created successfully!\n";
    } else {
        echo "✅ System settings table already exists.\n";
    }
    
    // Check if default settings exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM system_settings");
    $result = $stmt->fetch();
    $settings_count = $result['count'];
    
    if ($settings_count == 0) {
        echo "Inserting default settings...\n";
        
        $defaultSettings = [
            ['header_background_color', '#1e40af', 'color', 'Header background color', 'ui'],
            ['header_text_color', '#ffffff', 'color', 'Header text color', 'ui'],
            ['sidebar_hover_color', '#3b82f6', 'color', 'Sidebar menu hover color', 'ui'],
            ['sidebar_active_color', '#1e40af', 'color', 'Sidebar active menu item color', 'ui'],
            ['company_title', '4NSOLAR ELECTRICZ', 'text', 'Company title displayed in header', 'ui'],
            ['company_subtitle', 'Business Management System', 'text', 'Company subtitle displayed in header', 'ui'],
            ['primary_color', '#1e40af', 'color', 'Primary brand color', 'ui'],
            ['secondary_color', '#3b82f6', 'color', 'Secondary brand color', 'ui'],
            ['accent_color', '#fbbf24', 'color', 'Accent color for highlights', 'ui'],
            ['theme_mode', 'light', 'text', 'Default theme mode (light/dark)', 'ui'],
            ['logo_url', 'images/logo.png', 'text', 'Company logo URL', 'ui'],
            ['favicon_url', 'images/logo.png', 'text', 'Favicon URL', 'ui']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($defaultSettings as $setting) {
            $stmt->execute([$setting[0], $setting[1], $setting[2], $setting[3], $setting[4], 1]);
        }
        
        echo "✅ Default settings inserted successfully!\n";
    } else {
        echo "✅ Settings already exist in the database.\n";
    }
    
    echo "\n🎉 Settings system installation completed successfully!\n";
    echo "You can now access the settings page at: settings.php\n";
    echo "Make sure you are logged in as an admin user to access the settings.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}
?>
