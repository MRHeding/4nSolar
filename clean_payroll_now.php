<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    die('Access denied. You do not have permission to reset the payroll system.');
}

echo "<h2>Cleaning Payroll System...</h2>";
echo "<p>This will permanently delete ALL payroll data.</p>";

try {
    $pdo->beginTransaction();
    
    echo "<ul>";
    
    // Clear all payroll-related tables in the correct order
    echo "<li>Clearing payroll deductions...</li>";
    $pdo->exec("DELETE FROM payroll_deductions");
    
    echo "<li>Clearing payroll records...</li>";
    $pdo->exec("DELETE FROM payroll");
    
    echo "<li>Clearing employee attendance records...</li>";
    $pdo->exec("DELETE FROM employee_attendance");
    
    echo "<li>Clearing employee leave records...</li>";
    $pdo->exec("DELETE FROM employee_leaves");
    
    echo "<li>Clearing employee records...</li>";
    $pdo->exec("DELETE FROM employees");
    
    echo "<li>Resetting auto-increment counters...</li>";
    $pdo->exec("ALTER TABLE employees AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE employee_attendance AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE payroll AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE payroll_deductions AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE employee_leaves AUTO_INCREMENT = 1");
    
    $pdo->commit();
    
    echo "</ul>";
    echo "<div style='color: green; font-weight: bold;'>";
    echo "✅ Payroll system has been completely reset!<br>";
    echo "✅ All records have been cleared and the system is now fresh.";
    echo "</div>";
    
    echo "<p><a href='payroll.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Payroll System</a></p>";
    
} catch (Exception $e) {
    // Only rollback if there's an active transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div style='color: red; font-weight: bold;'>";
    echo "❌ Error resetting payroll system: " . $e->getMessage();
    echo "</div>";
}
?>
