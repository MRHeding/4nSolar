<?php
require_once 'includes/config.php';
require_once 'includes/settings.php';

// Set test colors
updateSystemSetting('company_title', 'SOLAR POWER SOLUTIONS', 'text');
updateSystemSetting('company_subtitle', 'Advanced Energy Management', 'text');
updateSystemSetting('primary_color', '#ff0000', 'color');
updateSystemSetting('secondary_color', '#00ff00', 'color');
updateSystemSetting('header_background_color', '#ff0000', 'color');

$page_title = 'Dynamic Print Test';
$content_start = true;

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Dynamic Print Test</h1>
        <p class="text-gray-600 dark:text-gray-400">Testing dynamic colors and company information in print quote.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Current Settings</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Company Information</h3>
                <p><strong>Title:</strong> <?php echo htmlspecialchars(getSystemSetting('company_title')); ?></p>
                <p><strong>Subtitle:</strong> <?php echo htmlspecialchars(getSystemSetting('company_subtitle')); ?></p>
            </div>
            
            <div>
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Colors</h3>
                <p><strong>Primary:</strong> <span style="color: <?php echo getSystemSetting('primary_color'); ?>"><?php echo getSystemSetting('primary_color'); ?></span></p>
                <p><strong>Secondary:</strong> <span style="color: <?php echo getSystemSetting('secondary_color'); ?>"><?php echo getSystemSetting('secondary_color'); ?></span></p>
                <p><strong>Header BG:</strong> <span style="color: <?php echo getSystemSetting('header_background_color'); ?>"><?php echo getSystemSetting('header_background_color'); ?></span></p>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
            <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Test Print Quote</h3>
            <p class="text-blue-800 dark:text-blue-200 mb-3">Click the link below to test the print quote with dynamic settings:</p>
            <a href="print_inventory_quote.php?id=76" target="_blank" class="btn-primary">
                <i class="fas fa-print mr-2"></i>
                Test Print Quote
            </a>
        </div>
        
        <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
            <h3 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">Expected Results</h3>
            <ul class="list-disc list-inside text-yellow-800 dark:text-yellow-200 space-y-1">
                <li>Company title should show: <strong>SOLAR POWER SOLUTIONS</strong></li>
                <li>Company subtitle should show: <strong>Advanced Energy Management</strong></li>
                <li>Header background should be: <strong style="color: #ff0000;">Red (#ff0000)</strong></li>
                <li>Borders and accents should be: <strong style="color: #ff0000;">Red (#ff0000)</strong></li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
