<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';

// Check if user is logged in and has admin permissions
if (!isLoggedIn() || !hasPermission([ROLE_ADMIN])) {
    header('Location: login.php');
    exit;
}

$page_title = 'System Settings';
$content_start = true;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    $errors = [];
    
    // Update settings with proper sanitization
    $settings = [
        'header_background_color' => trim($_POST['header_background_color'] ?? ''),
        'header_text_color' => trim($_POST['header_text_color'] ?? ''),
        'sidebar_hover_color' => trim($_POST['sidebar_hover_color'] ?? ''),
        'sidebar_active_color' => trim($_POST['sidebar_active_color'] ?? ''),
        'company_title' => strip_tags(trim($_POST['company_title'] ?? '')),
        'company_subtitle' => strip_tags(trim($_POST['company_subtitle'] ?? '')),
        'company_tagline' => strip_tags(trim($_POST['company_tagline'] ?? '')),
        'company_tin' => strip_tags(trim($_POST['company_tin'] ?? '')),
        'company_email' => trim($_POST['company_email'] ?? ''),
        'company_phone' => trim($_POST['company_phone'] ?? ''),
        'company_address' => strip_tags(trim($_POST['company_address'] ?? '')),
        'primary_color' => trim($_POST['primary_color'] ?? ''),
        'secondary_color' => trim($_POST['secondary_color'] ?? ''),
        'accent_color' => trim($_POST['accent_color'] ?? ''),
        'theme_mode' => trim($_POST['theme_mode'] ?? 'light'),
        'logo_url' => trim($_POST['logo_url'] ?? ''),
        'favicon_url' => trim($_POST['favicon_url'] ?? '')
    ];
    
    // Validate and update each setting
    foreach ($settings as $key => $value) {
        if (!empty($value)) {
            $type = 'text';
            if (strpos($key, '_color') !== false) {
                $type = 'color';
            }
            
            if (!updateSystemSetting($key, $value, $type)) {
                $success = false;
                $errors[] = "Failed to update {$key}";
            }
        }
    }
    
    if ($success) {
        $message = "Settings updated successfully!";
        $message_type = "success";
    } else {
        $message = "Some settings could not be updated: " . implode(', ', $errors);
        $message_type = "error";
    }
}

// Handle reset to defaults
if (isset($_POST['reset_defaults'])) {
    if (resetSettingsToDefault()) {
        $message = "Settings reset to default values!";
        $message_type = "success";
    } else {
        $message = "Failed to reset settings to defaults.";
        $message_type = "error";
    }
}

// Get current settings
$current_settings = [];
$settings_keys = [
    'header_background_color', 'header_text_color', 'sidebar_hover_color', 
    'sidebar_active_color', 'company_title', 'company_subtitle',
    'company_tagline', 'company_tin', 'company_email', 'company_phone', 'company_address',
    'primary_color', 'secondary_color', 'accent_color', 'theme_mode',
    'logo_url', 'favicon_url'
];

foreach ($settings_keys as $key) {
    $current_settings[$key] = getSystemSetting($key);
}

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">System Settings</h1>
        <p class="text-gray-600 dark:text-gray-400 text-lg">Customize the appearance and branding of your 4NSOLAR system.</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-400' : 'bg-red-100 text-red-800 border border-red-400'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-10">
        <!-- Header Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Header Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label for="header_background_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Header Background Color
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="header_background_color" 
                               name="header_background_color" 
                               value="<?php echo htmlspecialchars($current_settings['header_background_color']); ?>"
                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer shadow-sm">
                        <input type="text" 
                               value="<?php echo htmlspecialchars($current_settings['header_background_color']); ?>"
                               class="flex-1 form-input shadow-sm"
                               readonly>
                    </div>
                </div>
                
                <div>
                    <label for="header_text_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Header Text Color
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="header_text_color" 
                               name="header_text_color" 
                               value="<?php echo htmlspecialchars($current_settings['header_text_color']); ?>"
                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer shadow-sm">
                        <input type="text" 
                               value="<?php echo htmlspecialchars($current_settings['header_text_color']); ?>"
                               class="flex-1 form-input shadow-sm"
                               readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Sidebar Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label for="sidebar_hover_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Menu Hover Color
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="sidebar_hover_color" 
                               name="sidebar_hover_color" 
                               value="<?php echo htmlspecialchars($current_settings['sidebar_hover_color']); ?>"
                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer shadow-sm">
                        <input type="text" 
                               value="<?php echo htmlspecialchars($current_settings['sidebar_hover_color']); ?>"
                               class="flex-1 form-input shadow-sm"
                               readonly>
                    </div>
                </div>
                
                <div>
                    <label for="sidebar_active_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Active Menu Color
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="sidebar_active_color" 
                               name="sidebar_active_color" 
                               value="<?php echo htmlspecialchars($current_settings['sidebar_active_color']); ?>"
                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer shadow-sm">
                        <input type="text" 
                               value="<?php echo htmlspecialchars($current_settings['sidebar_active_color']); ?>"
                               class="flex-1 form-input shadow-sm"
                               readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Brand Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Brand Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label for="company_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Company Title
                    </label>
                    <input type="text" 
                           id="company_title" 
                           name="company_title" 
                           value="<?php echo htmlspecialchars($current_settings['company_title']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter company title">
                </div>
                
                <div>
                    <label for="company_subtitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Company Subtitle
                    </label>
                    <input type="text" 
                           id="company_subtitle" 
                           name="company_subtitle" 
                           value="<?php echo htmlspecialchars($current_settings['company_subtitle']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter company subtitle">
                </div>
                
                <div>
                    <label for="company_tagline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Company Tagline
                    </label>
                    <input type="text" 
                           id="company_tagline" 
                           name="company_tagline" 
                           value="<?php echo htmlspecialchars($current_settings['company_tagline']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter company tagline">
                </div>
                
                <div>
                    <label for="company_tin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Company TIN
                    </label>
                    <input type="text" 
                           id="company_tin" 
                           name="company_tin" 
                           value="<?php echo htmlspecialchars($current_settings['company_tin']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter company TIN">
                </div>
                
                <div>
                    <label for="company_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Company Email
                    </label>
                    <input type="email" 
                           id="company_email" 
                           name="company_email" 
                           value="<?php echo htmlspecialchars($current_settings['company_email']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter company email">
                </div>
                
                <div>
                    <label for="company_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Company Phone
                    </label>
                    <input type="text" 
                           id="company_phone" 
                           name="company_phone" 
                           value="<?php echo htmlspecialchars($current_settings['company_phone']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter company phone">
                </div>
                
                <div>
                    <label for="company_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Company Address
                    </label>
                    <input type="text" 
                           id="company_address" 
                           name="company_address" 
                           value="<?php echo htmlspecialchars($current_settings['company_address']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter company address">
                </div>
            </div>
        </div>

        <!-- Color Scheme -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Color Scheme</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Primary Color
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="primary_color" 
                               name="primary_color" 
                               value="<?php echo htmlspecialchars($current_settings['primary_color']); ?>"
                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer shadow-sm">
                        <input type="text" 
                               value="<?php echo htmlspecialchars($current_settings['primary_color']); ?>"
                               class="flex-1 form-input shadow-sm"
                               readonly>
                    </div>
                </div>
                
                <div>
                    <label for="secondary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Secondary Color
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="secondary_color" 
                               name="secondary_color" 
                               value="<?php echo htmlspecialchars($current_settings['secondary_color']); ?>"
                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer shadow-sm">
                        <input type="text" 
                               value="<?php echo htmlspecialchars($current_settings['secondary_color']); ?>"
                               class="flex-1 form-input shadow-sm"
                               readonly>
                    </div>
                </div>
                
                <div>
                    <label for="accent_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Accent Color
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="accent_color" 
                               name="accent_color" 
                               value="<?php echo htmlspecialchars($current_settings['accent_color']); ?>"
                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer shadow-sm">
                        <input type="text" 
                               value="<?php echo htmlspecialchars($current_settings['accent_color']); ?>"
                               class="flex-1 form-input shadow-sm"
                               readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Theme Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Theme Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label for="theme_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Default Theme Mode
                    </label>
                    <select id="theme_mode" name="theme_mode" class="form-select">
                        <option value="light" <?php echo $current_settings['theme_mode'] === 'light' ? 'selected' : ''; ?>>Light</option>
                        <option value="dark" <?php echo $current_settings['theme_mode'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Asset Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Asset Settings</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label for="logo_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Logo URL
                    </label>
                    <input type="text" 
                           id="logo_url" 
                           name="logo_url" 
                           value="<?php echo htmlspecialchars($current_settings['logo_url']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter logo URL">
                </div>
                
                <div>
                    <label for="favicon_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Favicon URL
                    </label>
                    <input type="text" 
                           id="favicon_url" 
                           name="favicon_url" 
                           value="<?php echo htmlspecialchars($current_settings['favicon_url']); ?>"
                           class="form-input shadow-sm"
                           placeholder="Enter favicon URL">
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                <button type="submit" name="reset_defaults" 
                        class="w-full sm:w-auto btn-secondary"
                        onclick="return confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')">
                    <i class="fas fa-undo mr-2"></i>
                    Reset to Defaults
                </button>
                
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 w-full sm:w-auto">
                    <button type="button" 
                            onclick="window.location.href='dashboard.php'" 
                            class="w-full sm:w-auto btn-secondary">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button type="submit" class="w-full sm:w-auto btn-primary">
                        <i class="fas fa-save mr-2"></i>
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Sync color inputs with text inputs
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textInput = colorInput.nextElementSibling;
    
    colorInput.addEventListener('input', function() {
        textInput.value = this.value;
    });
    
    textInput.addEventListener('input', function() {
        if (this.value.match(/^#[0-9A-F]{6}$/i)) {
            colorInput.value = this.value;
        }
    });
});

// Live preview functionality
function updatePreview() {
    const headerBg = document.getElementById('header_background_color').value;
    const headerText = document.getElementById('header_text_color').value;
    const sidebarHover = document.getElementById('sidebar_hover_color').value;
    const companyTitle = document.getElementById('company_title').value;
    
    // Update CSS variables for live preview
    document.documentElement.style.setProperty('--header-bg-color', headerBg);
    document.documentElement.style.setProperty('--header-text-color', headerText);
    document.documentElement.style.setProperty('--sidebar-hover-color', sidebarHover);
    
    // Update title in header if it exists
    const titleElement = document.querySelector('h1');
    if (titleElement && companyTitle) {
        titleElement.textContent = companyTitle;
    }
}

// Add event listeners for live preview
document.querySelectorAll('input[type="color"], input[name="company_title"]').forEach(input => {
    input.addEventListener('input', updatePreview);
});
</script>

<?php include 'includes/footer.php'; ?>
