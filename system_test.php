<?php
/**
 * 4NSOLAR ELECTRICZ System Testing Script
 * Comprehensive testing of all system functionality
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/inventory.php';
require_once 'includes/projects.php';
require_once 'includes/pos.php';
require_once 'includes/payroll.php';
require_once 'includes/suppliers.php';

class SystemTester {
    private $pdo;
    private $testResults = [];
    private $testData = [];
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    public function runAllTests() {
        echo "<h1>4NSOLAR ELECTRICZ System Testing</h1>";
        echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px;'>";
        
        $this->testDatabaseConnection();
        $this->testAuthentication();
        $this->testInventorySystem();
        $this->testPOSSystem();
        $this->testProjectSystem();
        $this->testQuotationSystem();
        $this->testPayrollSystem();
        $this->testSupplierSystem();
        $this->testReportsSystem();
        $this->testUserManagement();
        $this->testDataIntegrity();
        $this->testSecurityFeatures();
        
        $this->displayTestSummary();
        echo "</div>";
    }
    
    private function logTest($testName, $status, $message = '', $details = []) {
        $this->testResults[] = [
            'test' => $testName,
            'status' => $status,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $color = $status === 'PASS' ? 'green' : ($status === 'FAIL' ? 'red' : 'orange');
        echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid $color; background: #f9f9f9;'>";
        echo "<strong style='color: $color;'>[$status]</strong> $testName";
        if ($message) echo " - $message";
        if (!empty($details)) {
            echo "<br><small style='color: #666;'>Details: " . implode(', ', $details) . "</small>";
        }
        echo "</div>";
    }
    
    private function testDatabaseConnection() {
        echo "<h2>🗄️ Database Connection Tests</h2>";
        
        try {
            $stmt = $this->pdo->query("SELECT 1");
            $this->logTest("Database Connection", "PASS", "Successfully connected to database");
            
            // Test database structure
            $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $this->logTest("Database Tables", "PASS", "Found " . count($tables) . " tables", $tables);
            
            // Test critical tables exist
            $criticalTables = ['users', 'inventory', 'solar_projects', 'pos_sales', 'pos_sale_items'];
            $missingTables = [];
            foreach ($criticalTables as $table) {
                if (!in_array($table, $tables)) {
                    $missingTables[] = $table;
                }
            }
            
            if (empty($missingTables)) {
                $this->logTest("Critical Tables", "PASS", "All critical tables exist");
            } else {
                $this->logTest("Critical Tables", "FAIL", "Missing tables: " . implode(', ', $missingTables));
            }
            
        } catch (Exception $e) {
            $this->logTest("Database Connection", "FAIL", "Connection failed: " . $e->getMessage());
        }
    }
    
    private function testAuthentication() {
        echo "<h2>🔐 Authentication Tests</h2>";
        
        // Test login function
        try {
            // Test with invalid credentials
            $result = login('invalid_user', 'invalid_pass');
            if ($result === false) {
                $this->logTest("Invalid Login", "PASS", "Correctly rejects invalid credentials");
            } else {
                $this->logTest("Invalid Login", "FAIL", "Should reject invalid credentials");
            }
            
            // Test session functions
            if (function_exists('isLoggedIn')) {
                $this->logTest("Session Functions", "PASS", "Session functions available");
            } else {
                $this->logTest("Session Functions", "FAIL", "Session functions not available");
            }
            
            // Test role functions
            if (function_exists('hasRole') && function_exists('hasPermission')) {
                $this->logTest("Role Functions", "PASS", "Role management functions available");
            } else {
                $this->logTest("Role Functions", "FAIL", "Role management functions missing");
            }
            
        } catch (Exception $e) {
            $this->logTest("Authentication", "FAIL", "Authentication test failed: " . $e->getMessage());
        }
    }
    
    private function testInventorySystem() {
        echo "<h2>📦 Inventory System Tests</h2>";
        
        try {
            // Test inventory functions
            if (function_exists('getInventoryItems')) {
                $items = getInventoryItems();
                $this->logTest("Get Inventory Items", "PASS", "Retrieved " . count($items) . " items");
            } else {
                $this->logTest("Get Inventory Items", "FAIL", "Function not available");
            }
            
            if (function_exists('getLowStockItems')) {
                $lowStock = getLowStockItems();
                $this->logTest("Low Stock Items", "PASS", "Found " . count($lowStock) . " low stock items");
            } else {
                $this->logTest("Low Stock Items", "FAIL", "Function not available");
            }
            
            // Test inventory table structure
            $stmt = $this->pdo->query("DESCRIBE inventory");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['id', 'brand', 'model', 'stock_quantity', 'unit_price'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                $this->logTest("Inventory Table Structure", "PASS", "All required columns present");
            } else {
                $this->logTest("Inventory Table Structure", "FAIL", "Missing columns: " . implode(', ', $missingColumns));
            }
            
        } catch (Exception $e) {
            $this->logTest("Inventory System", "FAIL", "Inventory test failed: " . $e->getMessage());
        }
    }
    
    private function testPOSSystem() {
        echo "<h2>💰 POS System Tests</h2>";
        
        try {
            // Test POS functions
            if (function_exists('getPOSSales')) {
                $sales = getPOSSales('completed');
                $this->logTest("Get POS Sales", "PASS", "Retrieved " . count($sales) . " completed sales");
            } else {
                $this->logTest("Get POS Sales", "FAIL", "Function not available");
            }
            
            if (function_exists('getPOSStats')) {
                $stats = getPOSStats();
                $this->logTest("POS Statistics", "PASS", "Retrieved POS statistics");
            } else {
                $this->logTest("POS Statistics", "FAIL", "Function not available");
            }
            
            // Test POS tables
            $posTables = ['pos_sales', 'pos_sale_items'];
            foreach ($posTables as $table) {
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    $this->logTest("POS Table: $table", "PASS", "Contains $count records");
                } catch (Exception $e) {
                    $this->logTest("POS Table: $table", "FAIL", "Table error: " . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            $this->logTest("POS System", "FAIL", "POS test failed: " . $e->getMessage());
        }
    }
    
    private function testProjectSystem() {
        echo "<h2>🏗️ Project System Tests</h2>";
        
        try {
            // Test project functions
            if (function_exists('getSolarProjects')) {
                $projects = getSolarProjects();
                $this->logTest("Get Solar Projects", "PASS", "Retrieved " . count($projects) . " projects");
            } else {
                $this->logTest("Get Solar Projects", "FAIL", "Function not available");
            }
            
            if (function_exists('getProjectStats')) {
                $stats = getProjectStats();
                $this->logTest("Project Statistics", "PASS", "Retrieved project statistics");
            } else {
                $this->logTest("Project Statistics", "FAIL", "Function not available");
            }
            
            // Test project table
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM solar_projects");
                $count = $stmt->fetchColumn();
                $this->logTest("Solar Projects Table", "PASS", "Contains $count projects");
            } catch (Exception $e) {
                $this->logTest("Solar Projects Table", "FAIL", "Table error: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            $this->logTest("Project System", "FAIL", "Project test failed: " . $e->getMessage());
        }
    }
    
    private function testQuotationSystem() {
        echo "<h2>📄 Quotation System Tests</h2>";
        
        try {
            // Test quotation table
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM quotations");
            $count = $stmt->fetchColumn();
            $this->logTest("Quotations Table", "PASS", "Contains $count quotations");
            
            // Test quotation items table
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM quotation_items");
            $count = $stmt->fetchColumn();
            $this->logTest("Quotation Items Table", "PASS", "Contains $count quotation items");
            
        } catch (Exception $e) {
            $this->logTest("Quotation System", "FAIL", "Quotation test failed: " . $e->getMessage());
        }
    }
    
    private function testPayrollSystem() {
        echo "<h2>💼 Payroll System Tests</h2>";
        
        try {
            // Test payroll tables
            $payrollTables = ['employees', 'payroll', 'attendance'];
            foreach ($payrollTables as $table) {
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    $this->logTest("Payroll Table: $table", "PASS", "Contains $count records");
                } catch (Exception $e) {
                    $this->logTest("Payroll Table: $table", "FAIL", "Table error: " . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            $this->logTest("Payroll System", "FAIL", "Payroll test failed: " . $e->getMessage());
        }
    }
    
    private function testSupplierSystem() {
        echo "<h2>🚚 Supplier System Tests</h2>";
        
        try {
            // Test suppliers table
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM suppliers");
            $count = $stmt->fetchColumn();
            $this->logTest("Suppliers Table", "PASS", "Contains $count suppliers");
            
        } catch (Exception $e) {
            $this->logTest("Supplier System", "FAIL", "Supplier test failed: " . $e->getMessage());
        }
    }
    
    private function testReportsSystem() {
        echo "<h2>📊 Reports System Tests</h2>";
        
        try {
            // Test if reports.php exists and is accessible
            if (file_exists('reports.php')) {
                $this->logTest("Reports File", "PASS", "Reports file exists");
            } else {
                $this->logTest("Reports File", "FAIL", "Reports file missing");
            }
            
            // Test revenue analysis
            if (file_exists('revenue_analysis.php')) {
                $this->logTest("Revenue Analysis", "PASS", "Revenue analysis file exists");
            } else {
                $this->logTest("Revenue Analysis", "FAIL", "Revenue analysis file missing");
            }
            
        } catch (Exception $e) {
            $this->logTest("Reports System", "FAIL", "Reports test failed: " . $e->getMessage());
        }
    }
    
    private function testUserManagement() {
        echo "<h2>👥 User Management Tests</h2>";
        
        try {
            // Test users table
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            $count = $stmt->fetchColumn();
            $this->logTest("Users Table", "PASS", "Contains $count users");
            
            // Test user functions
            if (function_exists('getUsers')) {
                $users = getUsers();
                $this->logTest("Get Users Function", "PASS", "Retrieved " . count($users) . " users");
            } else {
                $this->logTest("Get Users Function", "FAIL", "Function not available");
            }
            
        } catch (Exception $e) {
            $this->logTest("User Management", "FAIL", "User management test failed: " . $e->getMessage());
        }
    }
    
    private function testDataIntegrity() {
        echo "<h2>🔍 Data Integrity Tests</h2>";
        
        try {
            // Test for orphaned records
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM pos_sale_items psi 
                                     LEFT JOIN pos_sales ps ON psi.sale_id = ps.id 
                                     WHERE ps.id IS NULL");
            $orphaned = $stmt->fetchColumn();
            
            if ($orphaned == 0) {
                $this->logTest("POS Sale Items Integrity", "PASS", "No orphaned sale items");
            } else {
                $this->logTest("POS Sale Items Integrity", "FAIL", "Found $orphaned orphaned sale items");
            }
            
            // Test for negative stock quantities
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM inventory WHERE stock_quantity < 0");
            $negativeStock = $stmt->fetchColumn();
            
            if ($negativeStock == 0) {
                $this->logTest("Stock Quantity Integrity", "PASS", "No negative stock quantities");
            } else {
                $this->logTest("Stock Quantity Integrity", "FAIL", "Found $negativeStock negative stock quantities");
            }
            
        } catch (Exception $e) {
            $this->logTest("Data Integrity", "FAIL", "Data integrity test failed: " . $e->getMessage());
        }
    }
    
    private function testSecurityFeatures() {
        echo "<h2>🔒 Security Tests</h2>";
        
        try {
            // Test password hashing
            $stmt = $this->pdo->query("SELECT password FROM users LIMIT 1");
            $user = $stmt->fetch();
            
            if ($user && password_get_info($user['password'])['algo'] !== null) {
                $this->logTest("Password Hashing", "PASS", "Passwords are properly hashed");
            } else {
                $this->logTest("Password Hashing", "FAIL", "Passwords may not be properly hashed");
            }
            
            // Test session security
            if (session_status() === PHP_SESSION_ACTIVE) {
                $this->logTest("Session Security", "PASS", "Sessions are active");
            } else {
                $this->logTest("Session Security", "WARN", "Sessions not active");
            }
            
        } catch (Exception $e) {
            $this->logTest("Security Features", "FAIL", "Security test failed: " . $e->getMessage());
        }
    }
    
    private function displayTestSummary() {
        echo "<h2>📋 Test Summary</h2>";
        
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, function($test) {
            return $test['status'] === 'PASS';
        }));
        $failedTests = count(array_filter($this->testResults, function($test) {
            return $test['status'] === 'FAIL';
        }));
        $warnedTests = count(array_filter($this->testResults, function($test) {
            return $test['status'] === 'WARN';
        }));
        
        $passRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
        
        echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>Overall Results</h3>";
        echo "<p><strong>Total Tests:</strong> $totalTests</p>";
        echo "<p><strong>Passed:</strong> <span style='color: green;'>$passedTests</span></p>";
        echo "<p><strong>Failed:</strong> <span style='color: red;'>$failedTests</span></p>";
        echo "<p><strong>Warnings:</strong> <span style='color: orange;'>$warnedTests</span></p>";
        echo "<p><strong>Pass Rate:</strong> <span style='color: " . ($passRate >= 80 ? 'green' : ($passRate >= 60 ? 'orange' : 'red')) . ";'>$passRate%</span></p>";
        echo "</div>";
        
        if ($failedTests > 0) {
            echo "<h3>❌ Failed Tests</h3>";
            foreach ($this->testResults as $test) {
                if ($test['status'] === 'FAIL') {
                    echo "<div style='background: #ffe6e6; padding: 10px; margin: 5px 0; border-radius: 4px;'>";
                    echo "<strong>{$test['test']}</strong> - {$test['message']}";
                    echo "</div>";
                }
            }
        }
        
        if ($warnedTests > 0) {
            echo "<h3>⚠️ Warnings</h3>";
            foreach ($this->testResults as $test) {
                if ($test['status'] === 'WARN') {
                    echo "<div style='background: #fff3cd; padding: 10px; margin: 5px 0; border-radius: 4px;'>";
                    echo "<strong>{$test['test']}</strong> - {$test['message']}";
                    echo "</div>";
                }
            }
        }
        
        echo "<h3>✅ System Health Status</h3>";
        if ($passRate >= 90) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; text-align: center;'>";
            echo "<h4>🟢 EXCELLENT</h4><p>System is in excellent condition with minimal issues.</p>";
            echo "</div>";
        } elseif ($passRate >= 80) {
            echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; text-align: center;'>";
            echo "<h4>🟡 GOOD</h4><p>System is functioning well with minor issues to address.</p>";
            echo "</div>";
        } elseif ($passRate >= 60) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; text-align: center;'>";
            echo "<h4>🟠 FAIR</h4><p>System has some issues that should be addressed.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; text-align: center;'>";
            echo "<h4>🔴 POOR</h4><p>System has significant issues that need immediate attention.</p>";
            echo "</div>";
        }
    }
}

// Run the tests
$tester = new SystemTester();
$tester->runAllTests();
?>


