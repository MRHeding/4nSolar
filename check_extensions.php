<?php
/**
 * PHP Extension Check and Fix Script
 * This script checks for required PHP extensions and provides instructions to fix them
 */

echo "<h1>PHP Extension Status Check</h1>\n";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>\n";

// Required extensions for the 4nSolar system
$required_extensions = [
    'pdo' => [
        'name' => 'PDO (PHP Data Objects)',
        'critical' => true,
        'description' => 'Required for database connectivity'
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL Driver', 
        'critical' => true,
        'description' => 'Required for MySQL database connections'
    ],
    'gd' => [
        'name' => 'GD Library',
        'critical' => false,
        'description' => 'Required for image processing and uploads'
    ],
    'curl' => [
        'name' => 'cURL',
        'critical' => false,
        'description' => 'Useful for external API calls'
    ],
    'mbstring' => [
        'name' => 'Multibyte String',
        'critical' => false,
        'description' => 'Better string handling for international characters'
    ],
    'openssl' => [
        'name' => 'OpenSSL',
        'critical' => false,
        'description' => 'Required for secure connections and password hashing'
    ],
    'zip' => [
        'name' => 'ZIP Archive',
        'critical' => false,
        'description' => 'Useful for data exports and backups'
    ]
];

echo "<h2>Extension Status:</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background: #f0f0f0;'><th>Extension</th><th>Status</th><th>Description</th><th>Action Required</th></tr>\n";

$missing_critical = [];
$missing_optional = [];

foreach ($required_extensions as $ext => $info) {
    $loaded = extension_loaded($ext);
    $status_color = $loaded ? '#28a745' : ($info['critical'] ? '#dc3545' : '#ffc107');
    $status_text = $loaded ? 'LOADED' : 'MISSING';
    
    if (!$loaded) {
        if ($info['critical']) {
            $missing_critical[] = $ext;
        } else {
            $missing_optional[] = $ext;
        }
    }
    
    echo "<tr>\n";
    echo "<td><strong>{$info['name']}</strong></td>\n";
    echo "<td style='color: $status_color; font-weight: bold;'>$status_text</td>\n";
    echo "<td>{$info['description']}</td>\n";
    echo "<td>" . ($loaded ? 'None' : ($info['critical'] ? 'REQUIRED' : 'Recommended')) . "</td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

// Show detailed information about missing extensions
if (!empty($missing_critical)) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ Critical Extensions Missing</h3>\n";
    echo "<p>The following critical extensions are missing and must be installed:</p>\n";
    echo "<ul>\n";
    foreach ($missing_critical as $ext) {
        echo "<li><strong>$ext</strong>: {$required_extensions[$ext]['description']}</li>\n";
    }
    echo "</ul>\n";
    echo "</div>\n";
}

if (!empty($missing_optional)) {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>⚠️ Optional Extensions Missing</h3>\n";
    echo "<p>The following optional extensions are recommended for full functionality:</p>\n";
    echo "<ul>\n";
    foreach ($missing_optional as $ext) {
        echo "<li><strong>$ext</strong>: {$required_extensions[$ext]['description']}</li>\n";
    }
    echo "</ul>\n";
    echo "</div>\n";
}

// PHP.ini information
echo "<h2>PHP Configuration:</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background: #f0f0f0;'><th>Setting</th><th>Current Value</th><th>Recommended</th><th>Status</th></tr>\n";

$php_settings = [
    'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'recommended' => '10M'],
    'post_max_size' => ['current' => ini_get('post_max_size'), 'recommended' => '20M'],
    'max_execution_time' => ['current' => ini_get('max_execution_time'), 'recommended' => '300'],
    'memory_limit' => ['current' => ini_get('memory_limit'), 'recommended' => '256M'],
    'display_errors' => ['current' => ini_get('display_errors') ? 'On' : 'Off', 'recommended' => 'Off (production)']
];

foreach ($php_settings as $setting => $info) {
    $status = '✓ OK';
    $status_color = '#28a745';
    
    // Check specific recommendations
    if ($setting === 'display_errors' && ini_get('display_errors')) {
        $status = '⚠️ Consider Off for production';
        $status_color = '#ffc107';
    }
    
    echo "<tr>\n";
    echo "<td><strong>$setting</strong></td>\n";
    echo "<td>{$info['current']}</td>\n";
    echo "<td>{$info['recommended']}</td>\n";
    echo "<td style='color: $status_color;'>$status</td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

// Instructions for XAMPP users
echo "<div style='background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h2>🔧 How to Fix Missing Extensions (XAMPP)</h2>\n";

if (in_array('gd', $missing_critical) || in_array('gd', $missing_optional)) {
    echo "<h3>Enable GD Extension:</h3>\n";
    echo "<ol>\n";
    echo "<li>Open XAMPP Control Panel</li>\n";
    echo "<li>Click 'Config' button next to Apache</li>\n";
    echo "<li>Select 'PHP (php.ini)'</li>\n";
    echo "<li>Find the line: <code>;extension=gd</code></li>\n";
    echo "<li>Remove the semicolon (;) to uncomment it: <code>extension=gd</code></li>\n";
    echo "<li>Save the file and restart Apache</li>\n";
    echo "</ol>\n";
}

echo "<h3>Common Extension Fixes:</h3>\n";
echo "<p>In the php.ini file, uncomment these lines by removing the semicolon (;):</p>\n";
echo "<ul>\n";
echo "<li><code>extension=gd</code> - For image processing</li>\n";
echo "<li><code>extension=curl</code> - For external API calls</li>\n";
echo "<li><code>extension=mbstring</code> - For string handling</li>\n";
echo "<li><code>extension=openssl</code> - For security features</li>\n";
echo "<li><code>extension=zip</code> - For archive handling</li>\n";
echo "</ul>\n";

echo "<h3>Alternative Method:</h3>\n";
echo "<p>If you're using a different PHP installation:</p>\n";
echo "<ol>\n";
echo "<li>Find your php.ini file location: <code>" . php_ini_loaded_file() . "</code></li>\n";
echo "<li>Edit the file and uncomment the required extensions</li>\n";
echo "<li>Restart your web server</li>\n";
echo "</ol>\n";
echo "</div>\n";

// System information
echo "<h2>📋 System Information:</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><td><strong>PHP Version</strong></td><td>" . phpversion() . "</td></tr>\n";
echo "<tr><td><strong>PHP SAPI</strong></td><td>" . php_sapi_name() . "</td></tr>\n";
echo "<tr><td><strong>Server Software</strong></td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>\n";
echo "<tr><td><strong>PHP Configuration File</strong></td><td>" . php_ini_loaded_file() . "</td></tr>\n";
echo "<tr><td><strong>Loaded Extensions</strong></td><td>" . implode(', ', get_loaded_extensions()) . "</td></tr>\n";
echo "</table>\n";

// Summary and next steps
if (empty($missing_critical)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>✅ All Critical Extensions Available</h3>\n";
    echo "<p>Your PHP installation has all the critical extensions needed for the 4nSolar system.</p>\n";
    if (!empty($missing_optional)) {
        echo "<p>Consider installing the optional extensions listed above for enhanced functionality.</p>\n";
    }
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ Action Required</h3>\n";
    echo "<p>Please install/enable the missing critical extensions before using the system.</p>\n";
    echo "</div>\n";
}

echo "<p><a href='web_test.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run System Test Again</a></p>\n";
?>
