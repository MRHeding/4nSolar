<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/payroll.php';

requireLogin();

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    die('Access denied');
}

echo "<h2>Attendance Detection Test</h2>";

// Get all employees
$employees = getAllEmployees($pdo);
echo "<h3>Available Employees:</h3>";
echo "<ul>";
foreach ($employees as $employee) {
    echo "<li>ID: {$employee['id']}, Code: {$employee['employee_code']}, Name: {$employee['employee_name']}</li>";
}
echo "</ul>";

// Test with first employee if available
if (!empty($employees)) {
    $test_employee = $employees[0];
    $employee_id = $test_employee['id'];
    
    echo "<h3>Testing with Employee: {$test_employee['employee_name']} (ID: {$employee_id})</h3>";
    
    // Test current month
    $current_month = date('Y-m');
    $start_date = $current_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    echo "<p><strong>Test Period:</strong> {$start_date} to {$end_date}</p>";
    
    // Check attendance records
    $has_attendance = hasAttendanceRecords($pdo, $employee_id, $start_date, $end_date);
    echo "<p><strong>Has Attendance Records:</strong> " . ($has_attendance ? 'YES' : 'NO') . "</p>";
    
    if ($has_attendance) {
        $attendance_count = getAttendanceRecordsCount($pdo, $employee_id, $start_date, $end_date);
        echo "<p><strong>Attendance Summary:</strong></p>";
        echo "<ul>";
        echo "<li>Total Records: {$attendance_count['total_records']}</li>";
        echo "<li>Present Days: {$attendance_count['present_days']}</li>";
        echo "<li>Absent Days: {$attendance_count['absent_days']}</li>";
        echo "</ul>";
    }
    
    // Show all attendance records for this employee
    echo "<h4>All Attendance Records for this Employee:</h4>";
    $all_attendance = getEmployeeAttendance($pdo, $employee_id);
    if (empty($all_attendance)) {
        echo "<p>No attendance records found for this employee.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th><th>Hours</th></tr>";
        foreach ($all_attendance as $record) {
            echo "<tr>";
            echo "<td>{$record['attendance_date']}</td>";
            echo "<td>{$record['time_in']}</td>";
            echo "<td>{$record['time_out']}</td>";
            echo "<td>{$record['status']}</td>";
            echo "<td>{$record['hours_worked']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Test the check_attendance.php endpoint
echo "<h3>Testing check_attendance.php Endpoint</h3>";
if (!empty($employees)) {
    $test_employee = $employees[0];
    $employee_id = $test_employee['id'];
    $current_month = date('Y-m');
    $start_date = $current_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    echo "<p>Testing POST request to check_attendance.php...</p>";
    
    // Simulate the POST request
    $post_data = http_build_query([
        'employee_id' => $employee_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $post_data
        ]
    ]);
    
    $result = file_get_contents('http://localhost/4nsolarSystem/check_attendance.php', false, $context);
    
    if ($result !== false) {
        echo "<p><strong>Response:</strong></p>";
        echo "<pre>" . htmlspecialchars($result) . "</pre>";
        
        $response_data = json_decode($result, true);
        if ($response_data) {
            echo "<p><strong>Parsed Response:</strong></p>";
            echo "<ul>";
            foreach ($response_data as $key => $value) {
                echo "<li><strong>{$key}:</strong> " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p><strong>Error:</strong> Failed to get response from check_attendance.php</p>";
    }
}
?>

