<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/payroll.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $attendance_id = $input['attendance_id'] ?? null;
    $field = $input['field'] ?? null;
    $value = $input['value'] ?? null;
    
    if (!$attendance_id || !$field) {
        throw new Exception('Missing required fields');
    }
    
    // Validate field name
    if (!in_array($field, ['time_in', 'time_out', 'status'])) {
        throw new Exception('Invalid field name');
    }
    
    // Validate time format if value is provided and field is time
    if (in_array($field, ['time_in', 'time_out']) && $value && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
        throw new Exception('Invalid time format');
    }
    
    // Validate status value if field is status
    if ($field === 'status' && $value && !in_array($value, ['present', 'absent', 'late', 'half_day', 'overtime'])) {
        throw new Exception('Invalid status value');
    }
    
    // Check if attendance record exists
    $stmt = $pdo->prepare("SELECT * FROM employee_attendance WHERE id = ?");
    $stmt->execute([$attendance_id]);
    $attendance = $stmt->fetch();
    
    if (!$attendance) {
        throw new Exception('Attendance record not found');
    }
    
    // Update the time field
    $sql = "UPDATE employee_attendance SET {$field} = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$value ?: null, $attendance_id]);
    
    if (!$result) {
        throw new Exception('Failed to update attendance record');
    }
    
    // If both time_in and time_out are present, recalculate hours_worked
    if ($field === 'time_in' || $field === 'time_out') {
        $stmt = $pdo->prepare("SELECT time_in, time_out, status FROM employee_attendance WHERE id = ?");
        $stmt->execute([$attendance_id]);
        $updated_attendance = $stmt->fetch();
        
        if ($updated_attendance['time_in'] && $updated_attendance['time_out']) {
            // Calculate hours with lunch break deduction
            $hours_worked = calculateHoursWithLunchBreak(
                $updated_attendance['time_in'], 
                $updated_attendance['time_out'], 
                $updated_attendance['status']
            );
            
            // Update hours_worked
            $stmt = $pdo->prepare("UPDATE employee_attendance SET hours_worked = ? WHERE id = ?");
            $stmt->execute([$hours_worked, $attendance_id]);
        }
    }
    
    // If status is updated, adjust hours_worked based on status
    if ($field === 'status') {
        $stmt = $pdo->prepare("SELECT time_in, time_out, status FROM employee_attendance WHERE id = ?");
        $stmt->execute([$attendance_id]);
        $updated_attendance = $stmt->fetch();
        
        // Update hours_worked based on status
        switch ($updated_attendance['status']) {
            case 'absent':
                $stmt = $pdo->prepare("UPDATE employee_attendance SET hours_worked = 0, overtime_hours = 0 WHERE id = ?");
                $stmt->execute([$attendance_id]);
                break;
            case 'half_day':
                $stmt = $pdo->prepare("UPDATE employee_attendance SET hours_worked = 4, overtime_hours = 0 WHERE id = ?");
                $stmt->execute([$attendance_id]);
                break;
            case 'present':
            case 'late':
                // If time_in and time_out exist, recalculate; otherwise set to 8
                if ($updated_attendance['time_in'] && $updated_attendance['time_out']) {
                    $hours_worked = calculateHoursWithLunchBreak(
                        $updated_attendance['time_in'], 
                        $updated_attendance['time_out'], 
                        $updated_attendance['status']
                    );
                    $stmt = $pdo->prepare("UPDATE employee_attendance SET hours_worked = ? WHERE id = ?");
                    $stmt->execute([$hours_worked, $attendance_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE employee_attendance SET hours_worked = 8, overtime_hours = 0 WHERE id = ?");
                    $stmt->execute([$attendance_id]);
                }
                break;
        }
    }
    
    $message = $field === 'status' ? 'Status updated successfully' : 'Time updated successfully';
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'data' => [
            'attendance_id' => $attendance_id,
            'field' => $field,
            'value' => $value
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Update attendance time error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
