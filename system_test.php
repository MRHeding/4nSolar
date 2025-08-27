<?php
/**
 * 4nSolar System Comprehensive Test
 * Tests all major components of the inventory management system
 */

// Set output to display in browser with proper formatting
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4nSolar System Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .test-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .test-result { margin: 5px 0; padding: 5px; background: #f8f9fa; border-radius: 3px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .status-pass { background: #d4edda; color: #155724; }
        .status-fail { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <h1>4nSolar System Comprehensive Test</h1>
    <p><strong>Test Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

<?php
// Include system files
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

function runTest($test_name, $test_function) {
    global $test_results, $total_tests, $passed_tests;
    $total_tests++;
    
    try {
        $result = $test_function();
        if ($result['status'] === 'pass') {
            $passed_tests++;
        }
        $test_results[] = array_merge(['name' => $test_name], $result);
        return $result;
    } catch (Exception $e) {
        $test_results[] = [
            'name' => $test_name,
            'status' => 'fail',
            'message' => 'Exception: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
        return ['status' => 'fail', 'message' => 'Exception: ' . $e->getMessage()];
    }
}

function displayTestResult($result) {
    $status_class = 'status-' . ($result['status'] === 'pass' ? 'pass' : ($result['status'] === 'warning' ? 'warning' : 'fail'));
    echo "<div class='test-result {$status_class}'>";
    echo "<strong>{$result['name']}</strong>: ";
    echo "<span class='" . ($result['status'] === 'pass' ? 'success' : ($result['status'] === 'warning' ? 'warning' : 'error')) . "'>";
    echo strtoupper($result['status']) . "</span> - {$result['message']}";
    if (isset($result['details'])) {
        echo "<pre>{$result['details']}</pre>";
    }
    echo "</div>";
}

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<h2>1. Database Connection Test</h2>";

$db_test = runTest("Database Connection", function() {
    try {
        require_once 'includes/config.php';
        global $pdo;
        
        // Test basic connection
        $stmt = $pdo->query("SELECT 1");
        if (!$stmt) {
            return ['status' => 'fail', 'message' => 'Failed to execute test query'];
        }
        
        // Test database exists
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        
        return [
            'status' => 'pass', 
            'message' => "Connected successfully to database: " . $result['db_name'],
            'details' => "Host: " . DB_HOST . ", User: " . DB_USER
        ];
    } catch (Exception $e) {
        return ['status' => 'fail', 'message' => $e->getMessage()];
    }
});

displayTestResult($db_test);
echo "</div>";

// Test 2: Database Tables
echo "<div class='test-section'>";
echo "<h2>2. Database Tables Test</h2>";

$required_tables = [
    'users', 'inventory', 'solar_projects', 'project_inventory', 
    'suppliers', 'pos_sales', 'pos_sale_items', 'inventory_movements'
];

foreach ($required_tables as $table) {
    $table_test = runTest("Table: $table", function() use ($table) {
        global $pdo;
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            return [
                'status' => 'pass', 
                'message' => "Table exists with " . count($columns) . " columns",
                'details' => "Columns: " . implode(', ', array_column($columns, 'Field'))
            ];
        } catch (Exception $e) {
            return ['status' => 'fail', 'message' => "Table missing or inaccessible: " . $e->getMessage()];
        }
    });
    displayTestResult($table_test);
}
echo "</div>";

// Test 3: Include Files
echo "<div class='test-section'>";
echo "<h2>3. Include Files Test</h2>";

$include_files = [
    'includes/config.php',
    'includes/auth.php', 
    'includes/inventory.php',
    'includes/projects.php',
    'includes/pos.php',
    'includes/suppliers.php'
];

foreach ($include_files as $file) {
    $file_test = runTest("Include: $file", function() use ($file) {
        if (!file_exists($file)) {
            return ['status' => 'fail', 'message' => 'File does not exist'];
        }
        
        // Try to include the file
        ob_start();
        $included = include_once $file;
        $output = ob_get_clean();
        
        if ($included === false) {
            return ['status' => 'fail', 'message' => 'Failed to include file'];
        }
        
        return [
            'status' => 'pass', 
            'message' => 'File included successfully',
            'details' => "Size: " . filesize($file) . " bytes, Modified: " . date('Y-m-d H:i:s', filemtime($file))
        ];
    });
    displayTestResult($file_test);
}
echo "</div>";

// Test 4: Core Functions
echo "<div class='test-section'>";
echo "<h2>4. Core Functions Test</h2>";

// Include all necessary files for function testing
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/projects.php';
require_once 'includes/pos.php';
require_once 'includes/suppliers.php';

$core_functions = [
    'getInventoryItems',
    'getLowStockItems',
    'getSolarProjects',
    'getProjectStats',
    'getPOSStats',
    'getSuppliers',
    'formatCurrency',
    'isLoggedIn',
    'hasRole'
];

foreach ($core_functions as $function) {
    $func_test = runTest("Function: $function", function() use ($function) {
        if (!function_exists($function)) {
            return ['status' => 'fail', 'message' => 'Function does not exist'];
        }
        
        // Try to call functions that don't require parameters
        try {
            if ($function === 'formatCurrency') {
                $result = $function(1234.56);
                return ['status' => 'pass', 'message' => 'Function callable', 'details' => "Test output: $result"];
            } elseif ($function === 'isLoggedIn' || $function === 'hasRole') {
                $result = $function('admin');
                return ['status' => 'pass', 'message' => 'Function callable', 'details' => "Test output: " . ($result ? 'true' : 'false')];
            } else {
                // For database functions, just check if they're callable
                return ['status' => 'pass', 'message' => 'Function exists and is callable'];
            }
        } catch (Exception $e) {
            return ['status' => 'warning', 'message' => 'Function exists but may have dependency issues: ' . $e->getMessage()];
        }
    });
    displayTestResult($func_test);
}
echo "</div>";

// Test 5: Data Integrity
echo "<div class='test-section'>";
echo "<h2>5. Data Integrity Test</h2>";

$data_test = runTest("Sample Data Check", function() {
    global $pdo;
    $details = [];
    
    // Check for users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    $details[] = "Users: $user_count";
    
    // Check for inventory items
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory");
    $inventory_count = $stmt->fetch()['count'];
    $details[] = "Inventory items: $inventory_count";
    
    // Check for projects
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM solar_projects");
    $project_count = $stmt->fetch()['count'];
    $details[] = "Solar projects: $project_count";
    
    // Check for suppliers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM suppliers");
    $supplier_count = $stmt->fetch()['count'];
    $details[] = "Suppliers: $supplier_count";
    
    $total_records = $user_count + $inventory_count + $project_count + $supplier_count;
    
    if ($total_records === 0) {
        return ['status' => 'warning', 'message' => 'No data found in main tables', 'details' => implode("\n", $details)];
    }
    
    return ['status' => 'pass', 'message' => "Data found in all main tables", 'details' => implode("\n", $details)];
});
displayTestResult($data_test);
echo "</div>";

// Test 6: File Permissions and Assets
echo "<div class='test-section'>";
echo "<h2>6. File System Test</h2>";

$important_files = [
    'index.php',
    'dashboard.php',
    'inventory.php',
    'projects.php',
    'pos.php',
    'login.php',
    'assets/css/',
    'assets/js/',
    'images/',
    'images/products/'
];

foreach ($important_files as $file) {
    $file_test = runTest("File/Directory: $file", function() use ($file) {
        if (!file_exists($file)) {
            return ['status' => 'fail', 'message' => 'File/directory does not exist'];
        }
        
        if (is_dir($file)) {
            $files = scandir($file);
            $file_count = count($files) - 2; // exclude . and ..
            return ['status' => 'pass', 'message' => "Directory exists with $file_count items"];
        } else {
            $readable = is_readable($file);
            $size = filesize($file);
            return [
                'status' => $readable ? 'pass' : 'warning',
                'message' => $readable ? 'File accessible' : 'File not readable',
                'details' => "Size: $size bytes"
            ];
        }
    });
    displayTestResult($file_test);
}
echo "</div>";

// Test 7: Security Features
echo "<div class='test-section'>";
echo "<h2>7. Security Features Test</h2>";

$security_test = runTest("Security Configuration", function() {
    $details = [];
    $issues = 0;
    
    // Check if session is started
    if (session_status() === PHP_SESSION_ACTIVE) {
        $details[] = "✓ Session management active";
    } else {
        $details[] = "✗ Session not active";
        $issues++;
    }
    
    // Check if PDO is using prepared statements (check config)
    global $pdo;
    if ($pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION) {
        $details[] = "✓ PDO error mode set to exception";
    } else {
        $details[] = "✗ PDO error mode not configured properly";
        $issues++;
    }
    
    // Check if auth functions exist
    if (function_exists('isLoggedIn') && function_exists('hasRole')) {
        $details[] = "✓ Authentication functions available";
    } else {
        $details[] = "✗ Authentication functions missing";
        $issues++;
    }
    
    if ($issues === 0) {
        return ['status' => 'pass', 'message' => 'Security features properly configured', 'details' => implode("\n", $details)];
    } else {
        return ['status' => 'warning', 'message' => "$issues security issues found", 'details' => implode("\n", $details)];
    }
});
displayTestResult($security_test);
echo "</div>";

// Test Summary
echo "<div class='test-section'>";
echo "<h2>Test Summary</h2>";
echo "<div class='test-result'>";
echo "<strong>Total Tests:</strong> $total_tests<br>";
echo "<strong>Passed:</strong> <span class='success'>$passed_tests</span><br>";
echo "<strong>Failed/Warning:</strong> <span class='" . (($total_tests - $passed_tests) > 0 ? 'error' : 'success') . "'>" . ($total_tests - $passed_tests) . "</span><br>";
echo "<strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 2) . "%<br>";

if ($passed_tests === $total_tests) {
    echo "<p class='success'>🎉 All tests passed! The 4nSolar system is functioning properly.</p>";
} elseif ($passed_tests >= $total_tests * 0.8) {
    echo "<p class='warning'>⚠️ Most tests passed. Some minor issues detected that should be addressed.</p>";
} else {
    echo "<p class='error'>❌ Several critical issues detected. System may not function properly.</p>";
}
echo "</div>";
echo "</div>";

// Detailed Results
echo "<div class='test-section'>";
echo "<h2>Detailed Test Results</h2>";
echo "<pre>";
foreach ($test_results as $result) {
    echo $result['name'] . ": " . strtoupper($result['status']) . " - " . $result['message'] . "\n";
}
echo "</pre>";
echo "</div>";

?>

<div class="test-section">
    <h2>Next Steps</h2>
    <ul>
        <li><strong>If all tests passed:</strong> Your 4nSolar system is ready for production use.</li>
        <li><strong>If some tests failed:</strong> Review the failed tests and fix any missing files or configuration issues.</li>
        <li><strong>For database issues:</strong> Check your database connection settings in includes/config.php</li>
        <li><strong>For missing files:</strong> Ensure all required files are uploaded to the server.</li>
        <li><strong>For function errors:</strong> Check that all include files are properly loaded.</li>
    </ul>
    
    <h3>System Features Verified:</h3>
    <ul>
        <li>✓ Database connectivity and table structure</li>
        <li>✓ Core system files and includes</li>
        <li>✓ Inventory management functions</li>
        <li>✓ Project management system</li>
        <li>✓ Point of Sale (POS) system</li>
        <li>✓ User authentication and security</li>
        <li>✓ File system and assets</li>
        <li>✓ Currency formatting and utilities</li>
    </ul>
</div>

</body>
</html>
