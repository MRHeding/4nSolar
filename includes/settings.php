<?php
/**
 * System Settings Management
 * Handles UI customization settings for the 4NSOLAR system
 */

/**
 * Get a system setting value
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getSystemSetting($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Error getting system setting: " . $e->getMessage());
        return $default;
    }
}

/**
 * Update a system setting
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $type Setting type (color, text, number, boolean)
 * @param string $description Setting description
 * @param string $category Setting category
 * @return bool Success status
 */
function updateSystemSetting($key, $value, $type = 'text', $description = null, $category = 'ui') {
    global $pdo;
    
    try {
        $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category, updated_by) 
                VALUES (?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                setting_type = VALUES(setting_type),
                description = COALESCE(VALUES(description), description),
                category = VALUES(category),
                updated_by = VALUES(updated_by)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$key, $value, $type, $description, $category, $_SESSION['user_id'] ?? 1]);
    } catch (PDOException $e) {
        error_log("Error updating system setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all settings by category
 * @param string $category Setting category
 * @return array Array of settings
 */
function getSettingsByCategory($category = 'ui') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE category = ? ORDER BY setting_key");
        $stmt->execute([$category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting settings by category: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all system settings
 * @return array Array of all settings
 */
function getAllSystemSettings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM system_settings ORDER BY category, setting_key");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting all system settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete a system setting
 * @param string $key Setting key
 * @return bool Success status
 */
function deleteSystemSetting($key) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ?");
        return $stmt->execute([$key]);
    } catch (PDOException $e) {
        error_log("Error deleting system setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset settings to default values
 * @return bool Success status
 */
function resetSettingsToDefault() {
    global $pdo;
    
    try {
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
        
        $pdo->beginTransaction();
        
        // Clear existing settings
        $stmt = $pdo->prepare("DELETE FROM system_settings");
        $stmt->execute();
        
        // Insert default settings
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($defaultSettings as $setting) {
            $stmt->execute([$setting[0], $setting[1], $setting[2], $setting[3], $setting[4], $_SESSION['user_id'] ?? 1]);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error resetting settings: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate CSS variables for dynamic styling
 * @return string CSS variables
 */
function generateDynamicCSS() {
    $headerBg = getSystemSetting('header_background_color', '#1e40af');
    $headerText = getSystemSetting('header_text_color', '#ffffff');
    $sidebarHover = getSystemSetting('sidebar_hover_color', '#3b82f6');
    $sidebarActive = getSystemSetting('sidebar_active_color', '#1e40af');
    $primaryColor = getSystemSetting('primary_color', '#1e40af');
    $secondaryColor = getSystemSetting('secondary_color', '#3b82f6');
    $accentColor = getSystemSetting('accent_color', '#fbbf24');
    
    return "
    :root {
        --header-bg-color: {$headerBg};
        --header-text-color: {$headerText};
        --sidebar-hover-color: {$sidebarHover};
        --sidebar-active-color: {$sidebarActive};
        --primary-color: {$primaryColor};
        --secondary-color: {$secondaryColor};
        --accent-color: {$accentColor};
    }
    
    /* Header background color */
    .bg-solar-blue:not(.sidebar-link) {
        background-color: var(--header-bg-color) !important;
    }
    
    /* Header navigation background */
    .navbar.bg-solar-blue,
    nav.bg-solar-blue {
        background-color: var(--header-bg-color) !important;
    }
    
    .text-solar-blue {
        color: var(--primary-color) !important;
    }
    
    .border-solar-blue {
        border-color: var(--primary-color) !important;
    }
    
    .hover\\:bg-solar-blue:hover {
        background-color: var(--sidebar-hover-color) !important;
    }
    
    .hover\\:text-solar-blue:hover {
        color: var(--primary-color) !important;
    }
    
    /* Sidebar active menu color - specific to sidebar only */
    .sidebar .nav-link.active,
    .sidebar .nav-link.active:hover {
        background-color: var(--sidebar-active-color) !important;
        color: white !important;
    }
    
    /* Tailwind sidebar active color - only for sidebar links */
    .sidebar-link.bg-solar-blue {
        background-color: var(--sidebar-active-color) !important;
        color: white !important;
    }
    
    .ring-solar-blue {
        --tw-ring-color: var(--primary-color);
    }
    
    .focus\\:ring-solar-blue:focus {
        --tw-ring-color: var(--primary-color);
    }
    
    .from-solar-blue {
        --tw-gradient-from: var(--primary-color);
    }
    
    .to-solar-blue {
        --tw-gradient-to: var(--primary-color);
    }
    
    /* Header text color overrides */
    .navbar {
        color: var(--header-text-color) !important;
    }
    
    .navbar * {
        color: inherit !important;
    }
    
    .navbar .navbar-brand,
    .navbar .navbar-brand * {
        color: var(--header-text-color) !important;
    }
    
    .navbar .text-white,
    .navbar .text-white * {
        color: var(--header-text-color) !important;
    }
    ";
}

/**
 * Get company title
 * @return string Company title
 */
function getCompanyTitle() {
    return getSystemSetting('company_title', '4NSOLAR ELECTRICZ');
}

/**
 * Get company subtitle
 * @return string Company subtitle
 */
function getCompanySubtitle() {
    return getSystemSetting('company_subtitle', 'Business Management System');
}

/**
 * Get logo URL
 * @return string Logo URL
 */
function getLogoUrl() {
    return getSystemSetting('logo_url', 'images/logo.png');
}

/**
 * Get favicon URL
 * @return string Favicon URL
 */
function getFaviconUrl() {
    return getSystemSetting('favicon_url', 'images/logo.png');
}
?>
