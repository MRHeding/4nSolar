<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/payroll.php';

requireLogin();

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Set content type for JSON response
header('Content-Type: application/json');

// Handle GET request (for Sunday duty check)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $employee_id = $_GET['employee_id'] ?? null;
    $date = $_GET['date'] ?? null;

    if (!$employee_id || !$date) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }

    // Check if employee was on duty on Sunday
    $wasOnDuty = wasEmployeeOnDutyOnSunday($pdo, $employee_id, $date);

    // Return JSON response
    echo json_encode([
        'wasOnDuty' => $wasOnDuty,
        'date' => $date,
        'employee_id' => $employee_id
    ]);
}
// Handle POST request (for attendance records check)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    if (!$employee_id || !$start_date || !$end_date) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }

    try {
        // Check if employee has attendance records for the period
        $has_attendance = hasAttendanceRecords($pdo, $employee_id, $start_date, $end_date);
        
        if ($has_attendance) {
            // Get detailed attendance summary
            $attendance_summary = getAttendanceRecordsCount($pdo, $employee_id, $start_date, $end_date);
            
            echo json_encode([
                'has_attendance' => true,
                'total_records' => $attendance_summary['total_records'],
                'present_days' => $attendance_summary['present_days'],
                'absent_days' => $attendance_summary['absent_days'],
                'employee_id' => $employee_id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
        } else {
            echo json_encode([
                'has_attendance' => false,
                'total_records' => 0,
                'present_days' => 0,
                'absent_days' => 0,
                'employee_id' => $employee_id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>