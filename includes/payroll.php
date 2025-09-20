<?php
// Payroll Management Functions
// 4NSOLAR ELECTRICZ Management System

// Get all employees
function getAllEmployees($pdo, $active_only = true) {
    $sql = "SELECT * FROM employees";
    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY employee_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get employee by ID
function getEmployeeById($pdo, $employee_id) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    return $stmt->fetch();
}

// Add new employee
function addEmployee($pdo, $data) {
    $sql = "INSERT INTO employees (employee_code, employee_name, position, date_of_joining, basic_salary, allowances) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['employee_code'],
        $data['employee_name'],
        $data['position'],
        $data['date_of_joining'],
        $data['basic_salary'],
        $data['allowances'] ?? 0
    ]);
}

// Update employee
function updateEmployee($pdo, $employee_id, $data) {
    $sql = "UPDATE employees SET 
            employee_name = ?, 
            position = ?, 
            date_of_joining = ?, 
            basic_salary = ?, 
            allowances = ? 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['employee_name'],
        $data['position'],
        $data['date_of_joining'],
        $data['basic_salary'],
        $data['allowances'] ?? 0,
        $employee_id
    ]);
}

// Get attendance records for an employee
function getEmployeeAttendance($pdo, $employee_id, $start_date = null, $end_date = null) {
    $sql = "SELECT * FROM employee_attendance WHERE employee_id = ?";
    $params = [$employee_id];
    
    if ($start_date && $end_date) {
        $sql .= " AND attendance_date BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    }
    
    $sql .= " ORDER BY attendance_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Add attendance record
function addAttendance($pdo, $data) {
    $sql = "INSERT INTO employee_attendance (employee_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            time_in = VALUES(time_in),
            time_out = VALUES(time_out),
            status = VALUES(status),
            hours_worked = VALUES(hours_worked),
            overtime_hours = VALUES(overtime_hours),
            notes = VALUES(notes)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['employee_id'],
        $data['attendance_date'],
        $data['time_in'],
        $data['time_out'],
        $data['status'],
        $data['hours_worked'] ?? 0,
        $data['overtime_hours'] ?? 0,
        $data['notes'] ?? null
    ]);
}

// Calculate working days in a period
function calculateWorkingDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $working_days = 0;
    
    while ($start <= $end) {
        // Exclude Sundays (0 = Sunday, 6 = Saturday)
        if ($start->format('w') != 0) {
            $working_days++;
        }
        $start->add(new DateInterval('P1D'));
    }
    
    return $working_days;
}

// Get attendance summary for payroll calculation
function getAttendanceSummary($pdo, $employee_id, $start_date, $end_date) {
    $sql = "SELECT 
                COUNT(*) as total_records,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
                COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_days,
                COUNT(CASE WHEN status = 'overtime' THEN 1 END) as overtime_days,
                SUM(hours_worked) as total_hours_worked,
                SUM(overtime_hours) as total_overtime,
                AVG(hours_worked) as avg_hours_per_day
            FROM employee_attendance 
            WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id, $start_date, $end_date]);
    return $stmt->fetch();
}

// Create payroll record
function createPayroll($pdo, $data) {
    $pdo->beginTransaction();
    
    try {
        // Insert main payroll record
        $sql = "INSERT INTO payroll (
                    employee_id, pay_period_start, pay_period_end, total_working_days, 
                    working_days_present, leaves_taken, balance_leaves,
                    basic_salary, allowances, overtime_pay, bonus_pay,
                    cash_advance, uniforms, tools, lates, miscellaneous,
                    gross_salary, total_deductions, net_salary,
                    status, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['employee_id'],
            $data['pay_period_start'],
            $data['pay_period_end'],
            $data['total_working_days'],
            $data['working_days_present'],
            $data['leaves_taken'] ?? 0,
            $data['balance_leaves'] ?? 0,
            $data['basic_salary'] ?? 0,
            $data['allowances'] ?? 0,
            $data['overtime_pay'] ?? 0,
            $data['bonus_pay'] ?? 0,
            $data['cash_advance'] ?? 0,
            $data['uniforms'] ?? 0,
            $data['tools'] ?? 0,
            $data['lates'] ?? 0,
            $data['miscellaneous'] ?? 0,
            $data['gross_salary'],
            $data['total_deductions'],
            $data['net_salary'],
            $data['status'] ?? 'draft',
            $_SESSION['user_id'] ?? null
        ]);
        
        if (!$result) {
            throw new Exception("Failed to insert payroll record: " . implode(', ', $stmt->errorInfo()));
        }
        
        $payroll_id = $pdo->lastInsertId();
        
        // Insert package salaries
        if (!empty($data['packages'])) {
            $package_sql = "INSERT INTO payroll_packages (payroll_id, package_name, amount) VALUES (?, ?, ?)";
            $package_stmt = $pdo->prepare($package_sql);
            
            foreach ($data['packages'] as $package) {
                if (!empty($package['name']) && $package['amount'] > 0) {
                    $result = $package_stmt->execute([
                        $payroll_id,
                        $package['name'],
                        $package['amount']
                    ]);
                    if (!$result) {
                        throw new Exception("Failed to insert package: " . implode(', ', $package_stmt->errorInfo()));
                    }
                }
            }
        }
        
        // Insert custom deductions
        if (!empty($data['custom_deductions'])) {
            $deduction_sql = "INSERT INTO payroll_deductions (payroll_id, deduction_type, description, amount) VALUES (?, ?, ?, ?)";
            $deduction_stmt = $pdo->prepare($deduction_sql);
            
            foreach ($data['custom_deductions'] as $deduction) {
                if (!empty($deduction['name']) && $deduction['amount'] > 0) {
                    $result = $deduction_stmt->execute([
                        $payroll_id,
                        'custom', // deduction_type
                        $deduction['name'], // description
                        $deduction['amount']
                    ]);
                    if (!$result) {
                        throw new Exception("Failed to insert deduction: " . implode(', ', $deduction_stmt->errorInfo()));
                    }
                }
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payroll creation error: " . $e->getMessage());
        return false;
    }
}

// Get payroll records
function getPayrollRecords($pdo, $employee_id = null, $limit = 50) {
    // Ensure limit is an integer and safe
    $limit = (int)$limit;
    if ($limit <= 0) $limit = 50;
    
    $sql = "SELECT p.*, e.employee_name, e.employee_code, e.position,
            (SELECT SUM(hours_worked) FROM employee_attendance 
             WHERE employee_id = p.employee_id 
             AND attendance_date BETWEEN p.pay_period_start AND p.pay_period_end) as total_hours_worked
            FROM payroll p 
            JOIN employees e ON p.employee_id = e.id";
    
    $params = [];
    if ($employee_id) {
        $sql .= " WHERE p.employee_id = ?";
        $params[] = $employee_id;
    }
    
    $sql .= " ORDER BY p.pay_period_end DESC LIMIT " . $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get payroll by ID
function getPayrollById($pdo, $payroll_id) {
    $sql = "SELECT p.*, e.employee_name, e.employee_code, e.position 
            FROM payroll p 
            JOIN employees e ON p.employee_id = e.id 
            WHERE p.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$payroll_id]);
    $payroll = $stmt->fetch();
    
    if ($payroll) {
        // Get package salaries
        $payroll['packages'] = getPayrollPackages($pdo, $payroll_id);
        // Get custom deductions
        $payroll['custom_deductions'] = getPayrollDeductions($pdo, $payroll_id);
    }
    
    return $payroll;
}

// Get payroll packages
function getPayrollPackages($pdo, $payroll_id) {
    $sql = "SELECT * FROM payroll_packages WHERE payroll_id = ? ORDER BY package_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$payroll_id]);
    return $stmt->fetchAll();
}

// Get payroll deductions
function getPayrollDeductions($pdo, $payroll_id) {
    $sql = "SELECT * FROM payroll_deductions WHERE payroll_id = ? ORDER BY description";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$payroll_id]);
    return $stmt->fetchAll();
}

// Calculate payroll automatically
function calculatePayroll($pdo, $employee_id, $pay_period_start, $pay_period_end, $adjustments = []) {
    // Get employee details
    $employee = getEmployeeById($pdo, $employee_id);
    if (!$employee) {
        return false;
    }
    
    // Get attendance summary
    $attendance = getAttendanceSummary($pdo, $employee_id, $pay_period_start, $pay_period_end);
    
    // Calculate working days
    $total_working_days = calculateWorkingDays($pay_period_start, $pay_period_end);
    
    // Calculate working days present - if no attendance records, assume full working days
    $working_days_present = 0;
    if ($attendance['total_records'] > 0) {
        // If there are attendance records, use them
        $working_days_present = ($attendance['present_days'] ?? 0) + (($attendance['half_days'] ?? 0) * 0.5) + ($attendance['late_days'] ?? 0);
    } else {
        // If no attendance records, assume employee worked all working days
        $working_days_present = $total_working_days;
    }
    
    // Calculate basic components
    $daily_rate = floatval($employee['basic_salary']);
    $hourly_rate = $daily_rate / 8; // Assuming 8 hours per day
    
    // Calculate basic salary based on actual hours worked
    $total_hours_worked = floatval($attendance['total_hours_worked'] ?? 0);
    if ($total_hours_worked > 0) {
        // Use actual hours worked for salary calculation
        $basic_salary = $total_hours_worked * $hourly_rate;
    } else {
        // Fallback to daily rate calculation if no hours recorded
        $basic_salary = $daily_rate * $working_days_present;
    }
    
    // Calculate package salaries total
    $total_packages = 0;
    if (!empty($adjustments['packages'])) {
        foreach ($adjustments['packages'] as $package) {
            if (!empty($package['amount']) && $package['amount'] > 0) {
                $total_packages += $package['amount'];
            }
        }
    }
    
    // Calculate overtime (using hourly rate)
    $overtime_pay = ($attendance['total_overtime'] ?? 0) * $hourly_rate;
    
    // Gross salary calculation
    $gross_salary = $basic_salary + $total_packages + 
                   ($employee['allowances'] ?? 0) + $overtime_pay + ($adjustments['bonus_pay'] ?? 0);
    
    // Calculate custom deductions total
    $total_custom_deductions = 0;
    if (!empty($adjustments['custom_deductions'])) {
        foreach ($adjustments['custom_deductions'] as $deduction) {
            if (!empty($deduction['amount']) && $deduction['amount'] > 0) {
                $total_custom_deductions += $deduction['amount'];
            }
        }
    }
    
    // Deductions
    $deductions = [
        'cash_advance' => $adjustments['cash_advance'] ?? 0,
        'uniforms' => $adjustments['uniforms'] ?? 0,
        'tools' => $adjustments['tools'] ?? 0,
        'lates' => ($attendance['late_days'] ?? 0) * ($adjustments['late_penalty'] ?? 0),
        'miscellaneous' => $adjustments['miscellaneous'] ?? 0,
        'custom_deductions' => $total_custom_deductions
    ];
    
    $total_deductions = array_sum($deductions);
    $net_salary = $gross_salary - $total_deductions;
    
    return [
        'employee_id' => $employee_id,
        'pay_period_start' => $pay_period_start,
        'pay_period_end' => $pay_period_end,
        'total_working_days' => $total_working_days,
        'working_days_present' => $working_days_present,
        'leaves_taken' => $attendance['absent_days'] ?? 0,
        'attendance_summary' => $attendance, // For debugging
        'balance_leaves' => max(0, 30 - ($attendance['absent_days'] ?? 0)), // Assume 30 days annual leave
        'daily_rate' => $daily_rate,
        'hourly_rate' => $hourly_rate,
        'total_hours_worked' => $total_hours_worked,
        'basic_salary' => $basic_salary,
        'allowances' => $employee['allowances'] ?? 0,
        'packages' => $adjustments['packages'] ?? [],
        'overtime_pay' => $overtime_pay,
        'bonus_pay' => $adjustments['bonus_pay'] ?? 0,
        'cash_advance' => $deductions['cash_advance'],
        'uniforms' => $deductions['uniforms'],
        'tools' => $deductions['tools'],
        'lates' => $deductions['lates'],
        'miscellaneous' => $deductions['miscellaneous'],
        'custom_deductions' => $adjustments['custom_deductions'] ?? [],
        'gross_salary' => $gross_salary,
        'total_deductions' => $total_deductions,
        'net_salary' => $net_salary,
        'status' => 'draft'
    ];
}

// Delete employee
function deleteEmployee($pdo, $employee_id) {
    try {
        // Check if employee has payroll records
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payroll WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $payroll_count = $stmt->fetchColumn();
        
        // Check if employee has attendance records
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_attendance WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $attendance_count = $stmt->fetchColumn();
        
        if ($payroll_count > 0 || $attendance_count > 0) {
            // If employee has records, just deactivate instead of deleting
            $stmt = $pdo->prepare("UPDATE employees SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$employee_id]);
        } else {
            // If no records, safe to delete
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            return $stmt->execute([$employee_id]);
        }
    } catch (Exception $e) {
        return false;
    }
}

// Delete attendance record
function deleteAttendance($pdo, $attendance_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM employee_attendance WHERE id = ?");
        return $stmt->execute([$attendance_id]);
    } catch (Exception $e) {
        return false;
    }
}

// Delete payroll record
function deletePayroll($pdo, $payroll_id) {
    try {
        // Only allow deletion of draft payroll records
        $stmt = $pdo->prepare("SELECT status FROM payroll WHERE id = ?");
        $stmt->execute([$payroll_id]);
        $status = $stmt->fetchColumn();
        
        if ($status === 'draft') {
            $stmt = $pdo->prepare("DELETE FROM payroll WHERE id = ?");
            return $stmt->execute([$payroll_id]);
        }
        return false; // Cannot delete non-draft payroll
    } catch (Exception $e) {
        return false;
    }
}

// Generate employee code in format: YYYY-XXX (e.g., 2025-001, 2025-002, etc.)
function generateEmployeeCode($pdo) {
    $current_year = date('Y');
    
    // Get the highest employee code for the current year (new format: YYYY-XXX)
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(employee_code, 6) AS UNSIGNED)) as max_code FROM employees WHERE employee_code LIKE ?");
    $stmt->execute([$current_year . '-%']);
    $result = $stmt->fetch();
    
    $next_code = ($result['max_code'] ?? 0) + 1;
    return $current_year . '-' . str_pad($next_code, 3, '0', STR_PAD_LEFT);
}

// Validate employee code format (YYYY-XXX)
function isValidEmployeeCode($code) {
    return preg_match('/^\d{4}-\d{3}$/', $code);
}

// Approve payroll
function approvePayroll($pdo, $payroll_id, $approved_by) {
    $sql = "UPDATE payroll SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$approved_by, $payroll_id]);
}

// Mark payroll as paid
function markPayrollAsPaid($pdo, $payroll_id) {
    $sql = "UPDATE payroll SET status = 'paid', paid_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$payroll_id]);
}

// Get current pay period (1st to 15th or 16th to end of month)
function getCurrentPayPeriod() {
    $today = new DateTime();
    $day = (int)$today->format('d');
    $month = $today->format('Y-m');
    
    if ($day <= 15) {
        // First half of month
        return [
            'start' => $month . '-01',
            'end' => $month . '-15'
        ];
    } else {
        // Second half of month
        $last_day = $today->format('Y-m-t');
        return [
            'start' => $month . '-16',
            'end' => $last_day
        ];
    }
}
?>
