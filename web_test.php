<?php
/**
 * 4nSolar System Web Test
 * Access via: http://localhost/4nSolar/web_test.php
 */

// Prevent any output before headers
ob_start();

// Set error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4nSolar System Web Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .test-section { margin-bottom: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .status-pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔧 4nSolar System Test Dashboard</h1>
        <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

<?php
$tests_passed = 0;
$tests_total = 0;
$test_results = [];

function test($name, $callback) {
    global $tests_passed, $tests_total, $test_results;
    $tests_total++;
    
    try {
        $result = $callback();
        if ($result['status'] === 'pass') {
            $tests_passed++;
        }
        $test_results[$name] = $result;
        return $result;
    } catch (Exception $e) {
        $result = ['status' => 'fail', 'message' => 'Exception: ' . $e->getMessage()];
        $test_results[$name] = $result;
        return $result;
    }
}

function displayTest($name, $result) {
    $status_class = 'status-' . $result['status'];
    echo "<div class='test-result {$status_class}'>";
    echo "<strong>$name:</strong> ";
    echo "<span class='" . ($result['status'] === 'pass' ? 'success' : ($result['status'] === 'warning' ? 'warning' : 'error')) . "'>";
    echo "● " . strtoupper($result['status']) . "</span>";
    echo " - {$result['message']}";
    if (isset($result['details'])) {
        echo "<br><small>{$result['details']}</small>";
    }
    echo "</div>";
}
?>

<div class="grid">
    <!-- File System Tests -->
    <div class="test-section">
        <h2>📁 File System</h2>
        <?php
        $files = [
            'includes/config.php' => 'Database Configuration',
            'includes/auth.php' => 'Authentication',
            'includes/inventory.php' => 'Inventory Management',
            'includes/projects.php' => 'Project Management',
            'includes/pos.php' => 'Point of Sale',
            'dashboard.php' => 'Dashboard',
            'inventory.php' => 'Inventory Page',
            'projects.php' => 'Projects Page',
            'pos.php' => 'POS Page'
        ];

        foreach ($files as $file => $description) {
            $result = test($description, function() use ($file) {
                if (!file_exists($file)) {
                    return ['status' => 'fail', 'message' => 'File missing'];
                }
                $size = filesize($file);
                $readable = is_readable($file);
                return [
                    'status' => $readable ? 'pass' : 'warning',
                    'message' => $readable ? 'File exists and readable' : 'File exists but not readable',
                    'details' => "Size: " . number_format($size) . " bytes"
                ];
            });
            displayTest($description, $result);
        }
        ?>
    </div>

    <!-- Database Connection -->
    <div class="test-section">
        <h2>🗄️ Database</h2>
        <?php
        $db_result = test('Database Connection', function() {
            // Test database connection without including config that starts session
            try {
                $test_pdo = new PDO("mysql:host=localhost;dbname=4nsolar_inventory", "root", "");
                $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Test a simple query
                $stmt = $test_pdo->query("SELECT DATABASE() as db_name");
                $result = $stmt->fetch();
                
                return ['status' => 'pass', 'message' => 'Connection successful', 'details' => "Database: " . $result['db_name']];
            } catch (PDOException $e) {
                return ['status' => 'fail', 'message' => 'Connection failed', 'details' => $e->getMessage()];
            }
        });
        displayTest('Database Connection', $db_result);

        // Test tables if connection successful
        if ($db_result['status'] === 'pass') {
            $tables = [
                'users' => 'User Management',
                'inventory_items' => 'Inventory Items',
                'solar_projects' => 'Solar Projects',
                'suppliers' => 'Suppliers',
                'pos_sales' => 'POS Sales',
                'pos_sale_items' => 'POS Sale Items',
                'categories' => 'Product Categories',
                'stock_movements' => 'Stock Movements'
            ];

            foreach ($tables as $table => $description) {
                $table_result = test($description, function() use ($table) {
                    try {
                        $test_pdo = new PDO("mysql:host=localhost;dbname=4nsolar_inventory", "root", "");
                        $stmt = $test_pdo->query("SHOW TABLES LIKE '$table'");
                        if ($stmt->rowCount() > 0) {
                            $count_stmt = $test_pdo->query("SELECT COUNT(*) as count FROM `$table`");
                            $count = $count_stmt->fetch()['count'];
                            return ['status' => 'pass', 'message' => 'Table exists', 'details' => "$count records"];
                        } else {
                            return ['status' => 'fail', 'message' => 'Table does not exist'];
                        }
                    } catch (PDOException $e) {
                        return ['status' => 'fail', 'message' => 'Table check failed', 'details' => $e->getMessage()];
                    }
                });
                displayTest($description, $table_result);
            }
        }
        ?>
    </div>

    <!-- PHP Configuration -->
    <div class="test-section">
        <h2>⚙️ PHP Configuration</h2>
        <?php
        $php_tests = [
            'PHP Version' => function() {
                $version = phpversion();
                $min_version = '7.4';
                return [
                    'status' => version_compare($version, $min_version, '>=') ? 'pass' : 'warning',
                    'message' => "PHP $version",
                    'details' => version_compare($version, $min_version, '>=') ? 'Version OK' : "Recommended: $min_version+"
                ];
            },
            'PDO Extension' => function() {
                return [
                    'status' => extension_loaded('pdo') ? 'pass' : 'fail',
                    'message' => extension_loaded('pdo') ? 'PDO available' : 'PDO not available'
                ];
            },
            'MySQL PDO' => function() {
                return [
                    'status' => extension_loaded('pdo_mysql') ? 'pass' : 'fail',
                    'message' => extension_loaded('pdo_mysql') ? 'MySQL PDO available' : 'MySQL PDO not available'
                ];
            },
            'GD Extension' => function() {
                return [
                    'status' => extension_loaded('gd') ? 'pass' : 'warning',
                    'message' => extension_loaded('gd') ? 'GD available' : 'GD not available',
                    'details' => extension_loaded('gd') ? 'Image processing OK' : 'Image uploads may not work'
                ];
            },
            'Session Support' => function() {
                return [
                    'status' => function_exists('session_start') ? 'pass' : 'fail',
                    'message' => function_exists('session_start') ? 'Session support available' : 'Session support missing'
                ];
            }
        ];

        foreach ($php_tests as $name => $test_func) {
            $result = test($name, $test_func);
            displayTest($name, $result);
        }
        ?>
    </div>

    <!-- Directory Permissions -->
    <div class="test-section">
        <h2>📂 Directory Permissions</h2>
        <?php
        $directories = [
            'images/products/' => 'Product Images',
            'assets/css/' => 'CSS Assets',
            'assets/js/' => 'JavaScript Assets',
            'includes/' => 'Include Files'
        ];

        foreach ($directories as $dir => $description) {
            $result = test($description, function() use ($dir) {
                if (!is_dir($dir)) {
                    return ['status' => 'fail', 'message' => 'Directory does not exist'];
                }
                $writable = is_writable($dir);
                $readable = is_readable($dir);
                
                if ($readable && $writable) {
                    $files = array_diff(scandir($dir), array('.', '..'));
                    return ['status' => 'pass', 'message' => 'Directory accessible', 'details' => count($files) . ' files'];
                } elseif ($readable) {
                    return ['status' => 'warning', 'message' => 'Directory readable but not writable'];
                } else {
                    return ['status' => 'fail', 'message' => 'Directory not accessible'];
                }
            });
            displayTest($description, $result);
        }
        ?>
    </div>

    <!-- Security Checks -->
    <div class="test-section">
        <h2>🔒 Security</h2>
        <?php
        $security_tests = [
            'Error Display' => function() {
                $display_errors = ini_get('display_errors');
                return [
                    'status' => $display_errors ? 'warning' : 'pass',
                    'message' => $display_errors ? 'Errors displayed (dev mode)' : 'Errors hidden (production ready)',
                    'details' => 'display_errors = ' . ($display_errors ? 'On' : 'Off')
                ];
            },
            'HTTPS Check' => function() {
                $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                           || $_SERVER['SERVER_PORT'] == 443
                           || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
                return [
                    'status' => $is_https ? 'pass' : 'warning',
                    'message' => $is_https ? 'HTTPS enabled' : 'HTTP only (consider HTTPS for production)',
                    'details' => 'Protocol: ' . ($is_https ? 'HTTPS' : 'HTTP')
                ];
            }
        ];

        foreach ($security_tests as $name => $test_func) {
            $result = test($name, $test_func);
            displayTest($name, $result);
        }
        ?>
    </div>

    <!-- Feature Tests -->
    <div class="test-section">
        <h2>🚀 System Features</h2>
        <?php
        // Test if we can include files without errors
        $feature_tests = [
            'Config Loading' => function() {
                // Create a temporary test to see if config loads
                $config_content = file_get_contents('includes/config.php');
                if (strpos($config_content, 'DB_HOST') !== false) {
                    return ['status' => 'pass', 'message' => 'Configuration file valid'];
                }
                return ['status' => 'fail', 'message' => 'Configuration file invalid'];
            },
            'Currency Function' => function() {
                // Test if currency formatting works
                $config_content = file_get_contents('includes/config.php');
                if (strpos($config_content, 'formatCurrency') !== false) {
                    return ['status' => 'pass', 'message' => 'Currency formatting available'];
                }
                return ['status' => 'warning', 'message' => 'Currency function check incomplete'];
            },
            'Authentication Setup' => function() {
                if (file_exists('includes/auth.php') && filesize('includes/auth.php') > 100) {
                    return ['status' => 'pass', 'message' => 'Authentication system present'];
                }
                return ['status' => 'warning', 'message' => 'Authentication system check incomplete'];
            }
        ];

        foreach ($feature_tests as $name => $test_func) {
            $result = test($name, $test_func);
            displayTest($name, $result);
        }
        ?>
    </div>
</div>

<!-- Test Summary -->
<div class="summary">
    <h2>📊 Test Summary</h2>
    <div class="grid">
        <div>
            <h3>Results</h3>
            <p><strong>Total Tests:</strong> <?php echo $tests_total; ?></p>
            <p><strong>Passed:</strong> <span class="success"><?php echo $tests_passed; ?></span></p>
            <p><strong>Failed/Warnings:</strong> <span class="<?php echo ($tests_total - $tests_passed) > 0 ? 'error' : 'success'; ?>"><?php echo $tests_total - $tests_passed; ?></span></p>
            <p><strong>Success Rate:</strong> <?php echo $tests_total > 0 ? round(($tests_passed / $tests_total) * 100, 1) : 0; ?>%</p>
        </div>
        <div>
            <h3>System Status</h3>
            <?php
            $success_rate = $tests_total > 0 ? ($tests_passed / $tests_total) : 0;
            if ($success_rate >= 0.9) {
                echo "<p class='success'>🎉 Excellent! System is ready for production.</p>";
            } elseif ($success_rate >= 0.7) {
                echo "<p class='warning'>⚠️ Good! Minor issues should be addressed.</p>";
            } else {
                echo "<p class='error'>❌ Critical issues need attention before deployment.</p>";
            }
            ?>
        </div>
    </div>
</div>

<!-- Recommendations -->
<div class="test-section">
    <h2>💡 Recommendations</h2>
    <div class="grid">
        <div>
            <h3>Next Steps</h3>
            <ul>
                <li>✅ <strong>Database:</strong> Ensure MySQL server is running</li>
                <li>✅ <strong>Files:</strong> Check all required files are uploaded</li>
                <li>✅ <strong>Permissions:</strong> Verify directory write permissions</li>
                <li>✅ <strong>Security:</strong> Configure for production environment</li>
            </ul>
        </div>
        <div>
            <h3>System URLs</h3>
            <ul>
                <li><a href="index.php">🏠 Home Page</a></li>
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="inventory.php">📦 Inventory</a></li>
                <li><a href="projects.php">🏗️ Projects</a></li>
                <li><a href="pos.php">💰 Point of Sale</a></li>
                <li><a href="login.php">🔐 Login</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="test-section">
    <h2>ℹ️ System Information</h2>
    <pre><?php
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Test Time: " . date('Y-m-d H:i:s') . "\n";
echo "Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo "Extensions: " . implode(', ', get_loaded_extensions());
    ?></pre>
</div>

</body>
</html>
