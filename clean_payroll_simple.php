<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    die('Access denied. You do not have permission to reset the payroll system.');
}

echo "<h2>🧹 Cleaning Payroll System...</h2>";
echo "<p>This will permanently delete ALL payroll data.</p>";

try {
    echo "<ul>";
    
    // Clear all payroll-related tables in the correct order
    echo "<li>Clearing payroll deductions...</li>";
    $result1 = $pdo->exec("DELETE FROM payroll_deductions");
    echo "✅ Deleted $result1 payroll deduction records<br>";
    
    echo "<li>Clearing payroll records...</li>";
    $result2 = $pdo->exec("DELETE FROM payroll");
    echo "✅ Deleted $result2 payroll records<br>";
    
    echo "<li>Clearing employee attendance records...</li>";
    $result3 = $pdo->exec("DELETE FROM employee_attendance");
    echo "✅ Deleted $result3 attendance records<br>";
    
    echo "<li>Clearing employee leave records...</li>";
    $result4 = $pdo->exec("DELETE FROM employee_leaves");
    echo "✅ Deleted $result4 leave records<br>";
    
    echo "<li>Clearing employee records...</li>";
    $result5 = $pdo->exec("DELETE FROM employees");
    echo "✅ Deleted $result5 employee records<br>";
    
    echo "<li>Resetting auto-increment counters...</li>";
    $pdo->exec("ALTER TABLE employees AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE employee_attendance AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE payroll AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE payroll_deductions AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE employee_leaves AUTO_INCREMENT = 1");
    echo "✅ Reset all auto-increment counters<br>";
    
    echo "</ul>";
    
    $total_deleted = $result1 + $result2 + $result3 + $result4 + $result5;
    
    echo "<div style='color: green; font-weight: bold; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "🎉 <strong>SUCCESS!</strong><br>";
    echo "✅ Payroll system has been completely reset!<br>";
    echo "✅ Total records deleted: <strong>$total_deleted</strong><br>";
    echo "✅ All tables are now empty and ready for fresh data<br>";
    echo "✅ Auto-increment counters have been reset to 1";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='payroll.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;'>🏠 Go to Payroll System</a>";
    echo "<a href='reset_payroll_system.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🔄 Reset Again</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "❌ <strong>ERROR:</strong> " . $e->getMessage();
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='payroll.php' style='background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🏠 Back to Payroll</a>";
    echo "</div>";
}
?>
