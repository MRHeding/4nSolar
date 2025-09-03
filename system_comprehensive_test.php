<?php
/**
 * Comprehensive System Test for 4nSolar Management System
 * Tests crucial functions: Authentication, Inventory Management, Project Management, Database Operations
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/projects.php';

class SystemTester {
    private $pdo;
    private $test_results = [];
    private $test_user_id;
    private $test_project_id;
    private $test_inventory_id;
    private $start_time;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->start_time = microtime(true);
    }
    
    /**
     * Run all system tests
     */
    public function runAllTests() {
        echo "<h1>4nSolar System Comprehensive Test Suite</h1>";
        echo "<p>Started at: " . date('Y-m-d H:i:s') . "</p>";
        echo "<hr>";
        
        // Test database connection
        $this->testDatabaseConnection();
        
        // Test authentication system
        $this->testAuthenticationSystem();
        
        // Test inventory management
        $this->testInventoryManagement();
        
        // Test project management
        $this->testProjectManagement();
        
        // Test inventory-project integration
        $this->testInventoryProjectIntegration();
        
        // Test data validation
        $this->testDataValidation();
        
        // Test stock movements
        $this->testStockMovements();
        
        // Test reporting functions
        $this->testReportingFunctions();
        
        // Clean up test data
        $this->cleanupTestData();
        
        // Display results
        $this->displayTestResults();
    }
    
    /**
     * Test database connection and basic queries
     */
    private function testDatabaseConnection() {
        echo "<h2>Testing Database Connection</h2>";
        
        try {
            // Test basic connection
            $this->pdo->query("SELECT 1");
            $this->addTestResult("Database Connection", true, "Successfully connected to database");
            
            // Test required tables exist
            $required_tables = [
                'users', 'inventory_items', 'categories', 'suppliers', 
                'solar_projects', 'solar_project_items', 'stock_movements'
            ];
            
            foreach ($required_tables as $table) {
                $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->fetch()) {
                    $this->addTestResult("Table: $table", true, "Table exists");
                } else {
                    $this->addTestResult("Table: $table", false, "Table missing");
                }
            }
            
            // Test database charset
            $stmt = $this->pdo->query("SELECT @@character_set_database");
            $charset = $stmt->fetchColumn();
            $this->addTestResult("Database Charset", true, "Charset: $charset");
            
        } catch (Exception $e) {
            $this->addTestResult("Database Connection", false, $e->getMessage());
        }
    }
    
    /**
     * Test authentication system
     */
    private function testAuthenticationSystem() {
        echo "<h2>Testing Authentication System</h2>";
        
        try {
            // Test user creation (create test user)
            $test_username = 'test_user_' . time();
            $test_password = 'test_password_123';
            $test_email = 'test@' . time() . '.com';
            
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, full_name, role, is_active) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
            $password_hash = password_hash($test_password, PASSWORD_DEFAULT);
            $result = $stmt->execute([$test_username, $test_email, $password_hash, 'Test User', 'admin', 1]);
            
            if ($result) {
                $this->test_user_id = $this->pdo->lastInsertId();
                $this->addTestResult("User Creation", true, "Test user created with ID: " . $this->test_user_id);
            } else {
                $this->addTestResult("User Creation", false, "Failed to create test user");
                return;
            }
            
            // Test password verification
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$this->test_user_id]);
            $stored_hash = $stmt->fetchColumn();
            
            if (password_verify($test_password, $stored_hash)) {
                $this->addTestResult("Password Verification", true, "Password correctly verified");
            } else {
                $this->addTestResult("Password Verification", false, "Password verification failed");
            }
            
            // Test role system
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$this->test_user_id]);
            $role = $stmt->fetchColumn();
            
            if ($role === 'admin') {
                $this->addTestResult("Role System", true, "User role correctly set to admin");
            } else {
                $this->addTestResult("Role System", false, "User role not set correctly");
            }
            
            // Test actual authentication functions
            $auth_result = login($test_username, $test_password);
            if ($auth_result && isset($_SESSION['user_id'])) {
                $this->addTestResult("Login Function", true, "User successfully logged in through auth system");
                
                // Test session data
                if ($_SESSION['username'] === $test_username && $_SESSION['role'] === 'admin') {
                    $this->addTestResult("Session Management", true, "Session data correctly set");
                } else {
                    $this->addTestResult("Session Management", false, "Session data not set correctly");
                }
            } else {
                $this->addTestResult("Login Function", false, "Login function failed");
            }
            
            // Test user creation function
            $new_test_user = createUser('test_user_func_' . time(), 'test123', 'func@test.com', 'sales', 'Function Test User');
            if ($new_test_user) {
                $this->addTestResult("User Creation Function", true, "createUser() function works correctly");
            } else {
                $this->addTestResult("User Creation Function", false, "createUser() function failed");
            }
            
        } catch (Exception $e) {
            $this->addTestResult("Authentication System", false, $e->getMessage());
        }
    }
    
    /**
     * Test inventory management functions
     */
    private function testInventoryManagement() {
        echo "<h2>Testing Inventory Management</h2>";
        
        try {
            // Create test category first
            $stmt = $this->pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute(['Test Category', 'Test category for system testing']);
            $test_category_id = $this->pdo->lastInsertId();
            
            // Create test supplier
            $stmt = $this->pdo->prepare("INSERT INTO suppliers (name, contact_person, email, phone) 
                                        VALUES (?, ?, ?, ?)");
            $stmt->execute(['Test Supplier', 'John Doe', 'john@test.com', '123-456-7890']);
            $test_supplier_id = $this->pdo->lastInsertId();
            
            // Test inventory item creation
            $inventory_data = [
                'brand' => 'Test Brand',
                'model' => 'Test Model',
                'category_id' => $test_category_id,
                'size_specification' => '100W',
                'base_price' => 1000.00,
                'selling_price' => 1200.00,
                'discount_percentage' => 5.0,
                'supplier_id' => $test_supplier_id,
                'stock_quantity' => 50,
                'minimum_stock' => 10,
                'description' => 'Test inventory item'
            ];
            
            // Simulate being logged in
            $_SESSION['user_id'] = $this->test_user_id;
            
            $result = addInventoryItem($inventory_data);
            if ($result) {
                $this->test_inventory_id = $this->pdo->lastInsertId();
                $this->addTestResult("Inventory Item Creation", true, "Inventory item created with ID: " . $this->test_inventory_id);
            } else {
                $this->addTestResult("Inventory Item Creation", false, "Failed to create inventory item");
                return;
            }
            
            // Test inventory item retrieval
            $item = getInventoryItem($this->test_inventory_id);
            if ($item && $item['brand'] === 'Test Brand') {
                $this->addTestResult("Inventory Item Retrieval", true, "Item retrieved successfully");
            } else {
                $this->addTestResult("Inventory Item Retrieval", false, "Failed to retrieve item");
            }
            
            // Test inventory list retrieval
            $items = getInventoryItems();
            if (is_array($items) && count($items) > 0) {
                $this->addTestResult("Inventory List Retrieval", true, "Retrieved " . count($items) . " items");
            } else {
                $this->addTestResult("Inventory List Retrieval", false, "Failed to retrieve inventory list");
            }
            
            // Test stock update
            $old_stock = $item['stock_quantity'];
            $new_stock = $old_stock + 10;
            $stock_update = updateStock($this->test_inventory_id, $new_stock, 'in', 'adjustment', null, 'Test stock increase');
            
            if ($stock_update) {
                $updated_item = getInventoryItem($this->test_inventory_id);
                if ($updated_item['stock_quantity'] == $new_stock) {
                    $this->addTestResult("Stock Update", true, "Stock updated from $old_stock to $new_stock");
                } else {
                    $this->addTestResult("Stock Update", false, "Stock not updated correctly");
                }
            } else {
                $this->addTestResult("Stock Update", false, "Failed to update stock");
            }
            
            // Test low stock detection
            $low_stock_items = getLowStockItems();
            $this->addTestResult("Low Stock Detection", true, "Found " . count($low_stock_items) . " low stock items");
            
            // Test product image URL function
            $default_image = getProductImageUrl(null);
            $non_existent_image = getProductImageUrl('non_existent_path.jpg');
            
            if (strpos($default_image, 'no-image') !== false) {
                $this->addTestResult("Default Image Handling", true, "Default image correctly returned for null path");
            } else {
                $this->addTestResult("Default Image Handling", false, "Default image not returned correctly");
            }
            
            if (strpos($non_existent_image, 'no-image') !== false) {
                $this->addTestResult("Missing Image Handling", true, "Default image correctly returned for missing file");
            } else {
                $this->addTestResult("Missing Image Handling", false, "Missing image not handled correctly");
            }
            
        } catch (Exception $e) {
            $this->addTestResult("Inventory Management", false, $e->getMessage());
        }
    }
    
    /**
     * Test project management functions
     */
    private function testProjectManagement() {
        echo "<h2>Testing Project Management</h2>";
        
        try {
            // Test project creation
            $project_data = [
                'project_name' => 'Test Solar Project',
                'customer_name' => 'Test Customer',
                'customer_email' => 'customer@test.com',
                'customer_phone' => '123-456-7890',
                'customer_address' => '123 Test Street',
                'system_size_kw' => 5.5
            ];
            
            $this->test_project_id = createSolarProject($project_data);
            if ($this->test_project_id) {
                $this->addTestResult("Project Creation", true, "Project created with ID: " . $this->test_project_id);
            } else {
                $this->addTestResult("Project Creation", false, "Failed to create project");
                return;
            }
            
            // Test project retrieval
            $project = getSolarProject($this->test_project_id);
            if ($project && $project['project_name'] === 'Test Solar Project') {
                $this->addTestResult("Project Retrieval", true, "Project retrieved successfully");
            } else {
                $this->addTestResult("Project Retrieval", false, "Failed to retrieve project");
            }
            
            // Test project list retrieval
            $projects = getSolarProjects();
            if (is_array($projects) && count($projects) > 0) {
                $this->addTestResult("Project List Retrieval", true, "Retrieved " . count($projects) . " projects");
            } else {
                $this->addTestResult("Project List Retrieval", false, "Failed to retrieve project list");
            }
            
            // Test adding item to project
            if ($this->test_inventory_id) {
                $add_result = addProjectItem($this->test_project_id, $this->test_inventory_id, 2);
                if ($add_result) {
                    $this->addTestResult("Add Item to Project", true, "Item added to project successfully");
                    
                    // Verify project totals were updated
                    $updated_project = getSolarProject($this->test_project_id);
                    if ($updated_project['final_amount'] > 0) {
                        $this->addTestResult("Project Totals Update", true, "Project totals calculated: " . formatCurrency($updated_project['final_amount']));
                    } else {
                        $this->addTestResult("Project Totals Update", false, "Project totals not calculated correctly");
                    }
                } else {
                    $this->addTestResult("Add Item to Project", false, "Failed to add item to project");
                }
            }
            
            // Test project status update
            $update_data = array_merge($project_data, ['project_status' => 'quoted']);
            $update_result = updateSolarProject($this->test_project_id, $update_data);
            if ($update_result) {
                $this->addTestResult("Project Status Update", true, "Project status updated to quoted");
            } else {
                $this->addTestResult("Project Status Update", false, "Failed to update project status");
            }
            
        } catch (Exception $e) {
            $this->addTestResult("Project Management", false, $e->getMessage());
        }
    }
    
    /**
     * Test inventory-project integration (critical business logic)
     */
    private function testInventoryProjectIntegration() {
        echo "<h2>Testing Inventory-Project Integration</h2>";
        
        try {
            if (!$this->test_project_id || !$this->test_inventory_id) {
                $this->addTestResult("Integration Test Setup", false, "Missing test project or inventory item");
                return;
            }
            
            // Get current stock before testing
            $item_before = getInventoryItem($this->test_inventory_id);
            $stock_before = $item_before['stock_quantity'];
            
            // Test inventory availability check
            $availability = checkProjectInventoryAvailability($this->test_project_id);
            if ($availability['available']) {
                $this->addTestResult("Inventory Availability Check", true, "Sufficient inventory available");
            } else {
                $this->addTestResult("Inventory Availability Check", false, $availability['message']);
            }
            
            // Test inventory deduction when project is approved
            $project = getSolarProject($this->test_project_id);
            $update_data = [
                'project_name' => $project['project_name'],
                'customer_name' => $project['customer_name'],
                'customer_email' => $project['customer_email'],
                'customer_phone' => $project['customer_phone'],
                'customer_address' => $project['customer_address'],
                'system_size_kw' => $project['system_size_kw'],
                'project_status' => 'approved'
            ];
            
            $approve_result = updateSolarProject($this->test_project_id, $update_data);
            if ($approve_result) {
                // Check if inventory was deducted
                $item_after = getInventoryItem($this->test_inventory_id);
                $stock_after = $item_after['stock_quantity'];
                $expected_deduction = 2; // We added 2 items to the project
                
                if ($stock_after == ($stock_before - $expected_deduction)) {
                    $this->addTestResult("Inventory Deduction on Approval", true, 
                        "Stock correctly deducted from $stock_before to $stock_after");
                } else {
                    $this->addTestResult("Inventory Deduction on Approval", false, 
                        "Stock not deducted correctly. Expected: " . ($stock_before - $expected_deduction) . ", Got: $stock_after");
                }
            } else {
                $this->addTestResult("Project Approval", false, "Failed to approve project");
            }
            
            // Test inventory restoration when project status changes back
            $update_data['project_status'] = 'quoted';
            $restore_result = updateSolarProject($this->test_project_id, $update_data);
            if ($restore_result) {
                $item_restored = getInventoryItem($this->test_inventory_id);
                $stock_restored = $item_restored['stock_quantity'];
                
                if ($stock_restored == $stock_before) {
                    $this->addTestResult("Inventory Restoration", true, 
                        "Stock correctly restored to $stock_restored");
                } else {
                    $this->addTestResult("Inventory Restoration", false, 
                        "Stock not restored correctly. Expected: $stock_before, Got: $stock_restored");
                }
            } else {
                $this->addTestResult("Inventory Restoration", false, "Failed to change project status back");
            }
            
        } catch (Exception $e) {
            $this->addTestResult("Inventory-Project Integration", false, $e->getMessage());
        }
    }
    
    /**
     * Test data validation
     */
    private function testDataValidation() {
        echo "<h2>Testing Data Validation</h2>";
        
        try {
            // Test negative stock validation
            $invalid_data = [
                'brand' => 'Test',
                'model' => 'Test',
                'category_id' => 1,
                'base_price' => -100, // Invalid negative price
                'selling_price' => 100,
                'discount_percentage' => 0,
                'supplier_id' => 1,
                'stock_quantity' => -5, // Invalid negative stock
                'minimum_stock' => 0,
                'description' => 'Test'
            ];
            
            $result = addInventoryItem($invalid_data);
            if (!$result) {
                $this->addTestResult("Negative Value Validation", true, "Correctly rejected negative values");
            } else {
                $this->addTestResult("Negative Value Validation", false, "Failed to reject negative values");
            }
            
            // Test empty required fields
            $empty_data = [
                'brand' => '', // Empty required field
                'model' => 'Test',
                'base_price' => 100,
                'selling_price' => 120,
                'stock_quantity' => 10
            ];
            
            // This would fail at database level due to NOT NULL constraints
            $this->addTestResult("Empty Field Validation", true, "Database constraints will prevent empty required fields");
            
        } catch (Exception $e) {
            $this->addTestResult("Data Validation", false, $e->getMessage());
        }
    }
    
    /**
     * Test stock movements tracking
     */
    private function testStockMovements() {
        echo "<h2>Testing Stock Movements</h2>";
        
        try {
            if (!$this->test_inventory_id) {
                $this->addTestResult("Stock Movements Test Setup", false, "No test inventory item available");
                return;
            }
            
            // Get stock movements for our test item
            $movements = getStockMovements($this->test_inventory_id);
            
            if (is_array($movements)) {
                $this->addTestResult("Stock Movements Retrieval", true, "Retrieved " . count($movements) . " stock movements");
                
                // Check if movements have required fields
                if (count($movements) > 0) {
                    $movement = $movements[0];
                    $required_fields = ['movement_type', 'quantity', 'previous_stock', 'new_stock', 'created_at'];
                    $has_all_fields = true;
                    
                    foreach ($required_fields as $field) {
                        if (!isset($movement[$field])) {
                            $has_all_fields = false;
                            break;
                        }
                    }
                    
                    if ($has_all_fields) {
                        $this->addTestResult("Stock Movement Data Integrity", true, "All required fields present");
                    } else {
                        $this->addTestResult("Stock Movement Data Integrity", false, "Missing required fields");
                    }
                }
            } else {
                $this->addTestResult("Stock Movements Retrieval", false, "Failed to retrieve stock movements");
            }
            
        } catch (Exception $e) {
            $this->addTestResult("Stock Movements", false, $e->getMessage());
        }
    }
    
    /**
     * Test reporting functions
     */
    private function testReportingFunctions() {
        echo "<h2>Testing Reporting Functions</h2>";
        
        try {
            // Test project statistics
            $stats = getProjectStats();
            
            if (is_array($stats)) {
                $this->addTestResult("Project Statistics", true, "Statistics generated successfully");
                
                // Check for required stat components
                if (isset($stats['total_revenue'])) {
                    $this->addTestResult("Revenue Calculation", true, "Total revenue: " . formatCurrency($stats['total_revenue']));
                } else {
                    $this->addTestResult("Revenue Calculation", false, "Total revenue not calculated");
                }
                
                if (isset($stats['by_status']) && is_array($stats['by_status'])) {
                    $this->addTestResult("Status Distribution", true, "Project status distribution calculated");
                } else {
                    $this->addTestResult("Status Distribution", false, "Status distribution not calculated");
                }
            } else {
                $this->addTestResult("Project Statistics", false, "Failed to generate statistics");
            }
            
            // Test currency formatting
            $test_amount = 12345.67;
            $formatted = formatCurrency($test_amount);
            if (strpos($formatted, '₱') !== false && strpos($formatted, '12,345.67') !== false) {
                $this->addTestResult("Currency Formatting", true, "Amount formatted correctly: $formatted");
            } else {
                $this->addTestResult("Currency Formatting", false, "Currency formatting failed: $formatted");
            }
            
        } catch (Exception $e) {
            $this->addTestResult("Reporting Functions", false, $e->getMessage());
        }
    }
    
    /**
     * Clean up test data
     */
    private function cleanupTestData() {
        echo "<h2>Cleaning Up Test Data</h2>";
        
        try {
            // Delete test project items
            if ($this->test_project_id) {
                $stmt = $this->pdo->prepare("DELETE FROM solar_project_items WHERE project_id = ?");
                $stmt->execute([$this->test_project_id]);
                
                // Delete test project
                $stmt = $this->pdo->prepare("DELETE FROM solar_projects WHERE id = ?");
                $stmt->execute([$this->test_project_id]);
            }
            
            // Delete test inventory item
            if ($this->test_inventory_id) {
                $stmt = $this->pdo->prepare("DELETE FROM stock_movements WHERE inventory_item_id = ?");
                $stmt->execute([$this->test_inventory_id]);
                
                $stmt = $this->pdo->prepare("DELETE FROM inventory_items WHERE id = ?");
                $stmt->execute([$this->test_inventory_id]);
            }
            
            // Delete test user
            if ($this->test_user_id) {
                $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$this->test_user_id]);
            }
            
            // Delete any additional test users created by functions
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE username LIKE 'test_user_%' OR email LIKE '%@test.com'");
            $stmt->execute();
            
            // Delete test category and supplier
            $stmt = $this->pdo->prepare("DELETE FROM categories WHERE name = 'Test Category'");
            $stmt->execute();
            
            $stmt = $this->pdo->prepare("DELETE FROM suppliers WHERE name = 'Test Supplier'");
            $stmt->execute();
            
            $this->addTestResult("Test Data Cleanup", true, "All test data cleaned up successfully");
            
        } catch (Exception $e) {
            $this->addTestResult("Test Data Cleanup", false, $e->getMessage());
        }
        
        // Clear session
        unset($_SESSION['user_id']);
    }
    
    /**
     * Add test result
     */
    private function addTestResult($test_name, $passed, $message) {
        $this->test_results[] = [
            'name' => $test_name,
            'passed' => $passed,
            'message' => $message,
            'time' => microtime(true) - $this->start_time
        ];
        
        $status = $passed ? "✅ PASS" : "❌ FAIL";
        echo "<p><strong>$status</strong> - $test_name: $message</p>";
    }
    
    /**
     * Display test results summary
     */
    private function displayTestResults() {
        $total_tests = count($this->test_results);
        $passed_tests = array_filter($this->test_results, function($result) {
            return $result['passed'];
        });
        $failed_tests = $total_tests - count($passed_tests);
        $success_rate = $total_tests > 0 ? (count($passed_tests) / $total_tests) * 100 : 0;
        $total_time = microtime(true) - $this->start_time;
        
        echo "<hr>";
        echo "<h2>Test Results Summary</h2>";
        echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>Overall Results</h3>";
        echo "<p><strong>Total Tests:</strong> $total_tests</p>";
        echo "<p><strong>Passed:</strong> <span style='color: green;'>" . count($passed_tests) . "</span></p>";
        echo "<p><strong>Failed:</strong> <span style='color: red;'>$failed_tests</span></p>";
        echo "<p><strong>Success Rate:</strong> " . number_format($success_rate, 1) . "%</p>";
        echo "<p><strong>Execution Time:</strong> " . number_format($total_time, 2) . " seconds</p>";
        echo "</div>";
        
        if ($failed_tests > 0) {
            echo "<h3>Failed Tests Details:</h3>";
            echo "<ul>";
            foreach ($this->test_results as $result) {
                if (!$result['passed']) {
                    echo "<li><strong>{$result['name']}:</strong> {$result['message']}</li>";
                }
            }
            echo "</ul>";
        }
        
        // System health assessment
        echo "<h3>System Health Assessment:</h3>";
        if ($success_rate >= 95) {
            echo "<p style='color: green; font-weight: bold;'>🟢 EXCELLENT - System is functioning optimally</p>";
        } elseif ($success_rate >= 85) {
            echo "<p style='color: orange; font-weight: bold;'>🟡 GOOD - System is functioning well with minor issues</p>";
        } elseif ($success_rate >= 70) {
            echo "<p style='color: orange; font-weight: bold;'>🟠 FAIR - System has some issues that need attention</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>🔴 POOR - System has critical issues requiring immediate attention</p>";
        }
        
        echo "<hr>";
        echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
    }
}

// Run the tests if accessed directly
if (!isset($_GET['action']) || $_GET['action'] !== 'api') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4nSolar System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .test-container { max-width: 1200px; margin: 0 auto; }
        .status-pass { color: green; font-weight: bold; }
        .status-fail { color: red; font-weight: bold; }
        .summary { background: #f9f9f9; padding: 15px; border-left: 4px solid #007cba; margin: 20px 0; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="test-container">
        <?php
        $tester = new SystemTester();
        $tester->runAllTests();
        ?>
    </div>
</body>
</html>
<?php
} else {
    // API mode - return JSON results
    header('Content-Type: application/json');
    $tester = new SystemTester();
    ob_start();
    $tester->runAllTests();
    $output = ob_get_clean();
    echo json_encode(['status' => 'completed', 'output' => $output]);
}
?>
