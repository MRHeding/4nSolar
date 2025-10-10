<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/settings.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Header Color Demo';
$content_start = true;

// Handle color changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_color'])) {
        $testColor = $_POST['test_color'];
        updateSystemSetting('header_text_color', $testColor, 'color');
        $message = "Header text color updated to: " . $testColor;
        $message_type = "success";
    }
    
    if (isset($_POST['test_active_color'])) {
        $testColor = $_POST['test_active_color'];
        updateSystemSetting('sidebar_active_color', $testColor, 'color');
        $message = "Active menu color updated to: " . $testColor;
        $message_type = "success";
    }
    
    if (isset($_POST['test_header_bg_color'])) {
        $testColor = $_POST['test_header_bg_color'];
        updateSystemSetting('header_background_color', $testColor, 'color');
        $message = "Header background color updated to: " . $testColor;
        $message_type = "success";
    }
    
    if (isset($_POST['reset_color'])) {
        updateSystemSetting('header_text_color', '#ffffff', 'color');
        $message = "Header text color reset to white";
        $message_type = "success";
    }
}

$current_color = getSystemSetting('header_text_color', '#ffffff');
$current_active_color = getSystemSetting('sidebar_active_color', '#1e40af');
$current_header_bg = getSystemSetting('header_background_color', '#1e40af');

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Header Color Demo</h1>
        <p class="text-gray-600 dark:text-gray-400">Test the header text color functionality. The header above should reflect the color you choose below.</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-400' : 'bg-red-100 text-red-800 border border-red-400'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Test Header Text Color</h2>
        
        <div class="mb-4">
            <p class="text-gray-600 dark:text-gray-400 mb-2">Current header text color: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?php echo $current_color; ?></code></p>
            <p class="text-gray-600 dark:text-gray-400 mb-2">Current header background color: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?php echo $current_header_bg; ?></code></p>
            <p class="text-gray-600 dark:text-gray-400 mb-2">Current active menu color: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?php echo $current_active_color; ?></code></p>
        </div>
        
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="test_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Choose a test color:
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="color" 
                               id="test_color" 
                               name="test_color" 
                               value="<?php echo $current_color; ?>"
                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                        <input type="text" 
                               value="<?php echo $current_color; ?>"
                               class="flex-1 form-input"
                               readonly>
                    </div>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" name="test_color" class="btn-primary w-full">
                        Apply Color
                    </button>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" name="reset_color" class="btn-secondary w-full">
                        Reset to White
                    </button>
                </div>
            </div>
        </form>
        
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
            <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Quick Test Colors:</h3>
            <div class="flex flex-wrap gap-2">
                <?php 
                $test_colors = [
                    '#ffffff' => 'White',
                    '#000000' => 'Black', 
                    '#ff0000' => 'Red',
                    '#00ff00' => 'Green',
                    '#0000ff' => 'Blue',
                    '#ffff00' => 'Yellow',
                    '#ff00ff' => 'Magenta',
                    '#00ffff' => 'Cyan'
                ];
                
                foreach ($test_colors as $color => $name): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="test_color" value="<?php echo $color; ?>">
                        <button type="submit" class="px-3 py-1 text-sm rounded text-white" style="background-color: <?php echo $color; ?>; <?php echo $color === '#ffffff' ? 'border: 1px solid #ccc; color: #000;' : ''; ?>">
                            <?php echo $name; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-green-50 dark:bg-green-900 rounded-lg">
            <h3 class="font-semibold text-green-900 dark:text-green-100 mb-2">Test Active Menu Color:</h3>
            <p class="text-green-800 dark:text-green-200 mb-3">The active menu item in the sidebar should use the color you select below. <strong>This should NOT affect the header color.</strong></p>
            <div class="flex flex-wrap gap-2">
                <?php 
                $active_test_colors = [
                    '#1e40af' => 'Blue (Default)',
                    '#dc2626' => 'Red',
                    '#059669' => 'Green',
                    '#7c3aed' => 'Purple',
                    '#ea580c' => 'Orange',
                    '#be123c' => 'Pink',
                    '#0891b2' => 'Cyan',
                    '#ca8a04' => 'Yellow'
                ];
                
                foreach ($active_test_colors as $color => $name): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="test_active_color" value="<?php echo $color; ?>">
                        <button type="submit" class="px-3 py-1 text-sm rounded text-white" style="background-color: <?php echo $color; ?>;">
                            <?php echo $name; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-purple-50 dark:bg-purple-900 rounded-lg">
            <h3 class="font-semibold text-purple-900 dark:text-purple-100 mb-2">Test Header Background Color:</h3>
            <p class="text-purple-800 dark:text-purple-200 mb-3">The header background should use the color you select below. <strong>This should NOT affect the active menu color.</strong></p>
            <div class="flex flex-wrap gap-2">
                <?php 
                $header_test_colors = [
                    '#1e40af' => 'Blue (Default)',
                    '#dc2626' => 'Red',
                    '#059669' => 'Green',
                    '#7c3aed' => 'Purple',
                    '#ea580c' => 'Orange',
                    '#be123c' => 'Pink',
                    '#0891b2' => 'Cyan',
                    '#ca8a04' => 'Yellow'
                ];
                
                foreach ($header_test_colors as $color => $name): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="test_header_bg_color" value="<?php echo $color; ?>">
                        <button type="submit" class="px-3 py-1 text-sm rounded text-white" style="background-color: <?php echo $color; ?>;">
                            <?php echo $name; ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
            <h3 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">Instructions:</h3>
            <ol class="list-decimal list-inside text-yellow-800 dark:text-yellow-200 space-y-1">
                <li>Look at the header above - it should show the current text color</li>
                <li>Choose a color using the color picker or click one of the quick test colors</li>
                <li>Click "Apply Color" to update the header text color</li>
                <li>The header text should immediately change to your selected color</li>
                <li>Use "Reset to White" to return to the default white color</li>
            </ol>
        </div>
    </div>
    
    <div class="mt-6 text-center">
        <a href="settings.php" class="btn-primary">
            Go to Full Settings
        </a>
    </div>
</div>

<script>
// Sync color input with text input
document.getElementById('test_color').addEventListener('input', function() {
    this.nextElementSibling.value = this.value;
});

document.getElementById('test_color').nextElementSibling.addEventListener('input', function() {
    if (this.value.match(/^#[0-9A-F]{6}$/i)) {
        document.getElementById('test_color').value = this.value;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
