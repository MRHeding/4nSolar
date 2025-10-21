<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/payroll.php';

requireLogin();

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'Employee Attendance';

$employee_id = $_GET['employee_id'] ?? null;
$month = $_GET['month'] ?? date('Y-m');

// Get employee details
$employee = null;
if ($employee_id) {
    $employee = getEmployeeById($pdo, $employee_id);
}

// Get all employees for dropdown
$employees = getAllEmployees($pdo);

// Get attendance records for the selected month
$attendance_records = [];
if ($employee_id && $month) {
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $attendance_records = getEmployeeAttendance($pdo, $employee_id, $start_date, $end_date);
}

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_attendance') {
        try {
            $data = [
                'employee_id' => $_POST['employee_id'],
                'attendance_date' => $_POST['attendance_date'],
                'time_in' => $_POST['time_in'] ?: null,
                'time_out' => $_POST['time_out'] ?: null,
                'status' => $_POST['status'],
                'hours_worked' => $_POST['hours_worked'],
                'overtime_hours' => $_POST['overtime_hours'],
                'notes' => $_POST['notes']
            ];
            
            if (addAttendance($pdo, $data)) {
                $message = 'Attendance record added successfully!';
                // Refresh the page to show updated records
                header("Location: employee_attendance.php?employee_id={$_POST['employee_id']}&month=$month");
                exit();
            } else {
                $error = 'Failed to add attendance record.';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'bulk_attendance') {
        try {
            $employee_id = $_POST['employee_id'];
            $start_date = $_POST['start_date'];
            $attendance_data = $_POST['attendance'] ?? [];
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($attendance_data as $date => $data) {
                // Save all records, but adjust status if needed
                $has_time_data = !empty($data['time_in']) || !empty($data['time_out']);
                $is_absent = $data['status'] === 'absent';
                $has_notes = !empty($data['notes']);
                
                // If no time data but status is present, set to absent
                if (!$has_time_data && $data['status'] === 'present') {
                    $data['status'] = 'absent';
                    $data['hours_worked'] = 0;
                }
                
                // Save the record (even if blank - user understands this)
                $attendance_record = [
                    'employee_id' => $employee_id,
                    'attendance_date' => $date,
                    'time_in' => $data['time_in'] ?: null,
                    'time_out' => $data['time_out'] ?: null,
                    'status' => $data['status'],
                    'hours_worked' => $data['hours_worked'] ?? 0,
                    'overtime_hours' => $data['overtime_hours'] ?? 0,
                    'notes' => $data['notes'] ?? null
                ];
                
                if (addAttendance($pdo, $attendance_record)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            if ($success_count > 0) {
                $message = "Bulk attendance added successfully! $success_count records saved.";
                if ($error_count > 0) {
                    $message .= " $error_count records failed.";
                }
            } else {
                $error = 'Failed to add any attendance records.';
            }
            
            // Refresh the page to show updated records
            header("Location: employee_attendance.php?employee_id=$employee_id&month=" . date('Y-m', strtotime($start_date)));
            exit();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete_attendance') {
        try {
            if (deleteAttendance($pdo, $_POST['attendance_id'])) {
                $message = 'Attendance record deleted successfully!';
                // Refresh the page to show updated records
                header("Location: employee_attendance.php?employee_id=$employee_id&month=$month");
                exit();
            } else {
                $error = 'Failed to delete attendance record.';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-calendar-check me-2"></i>Employee Attendance</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <?php if ($employee): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                <i class="fas fa-plus"></i> Add Attendance
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkAttendanceModal">
                <i class="fas fa-calendar-plus"></i> Bulk Entry (15 Days)
            </button>
            <?php endif; ?>
            <a href="payroll.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Payroll
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Controls -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="employee_id" class="form-label">Select Employee</label>
                <select class="form-select" id="employee_id" name="employee_id" onchange="this.form.submit()">
                    <option value="">Choose an employee...</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>" <?php echo ($employee_id == $emp['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($emp['employee_code'] . ' - ' . $emp['employee_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="month" class="form-label">Month</label>
                <input type="month" class="form-control" id="month" name="month" value="<?php echo $month; ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> View Attendance
                </button>
                <?php if ($employee_id): ?>
                <a href="?employee_id=<?php echo $employee_id; ?>&month=<?php echo $month; ?>&export=csv" class="btn btn-outline-success">
                    <i class="fas fa-download"></i> Export CSV
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($employee): ?>
<!-- Employee Info Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Employee Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Employee Code:</strong><br>
                <?php echo htmlspecialchars($employee['employee_code']); ?>
            </div>
            <div class="col-md-3">
                <strong>Name:</strong><br>
                <?php echo htmlspecialchars($employee['employee_name']); ?>
            </div>
            <div class="col-md-3">
                <strong>Position:</strong><br>
                <?php echo htmlspecialchars($employee['position']); ?>
            </div>
            <div class="col-md-3">
                <strong>Date Joined:</strong><br>
                <?php echo date('M d, Y', strtotime($employee['date_of_joining'])); ?>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Summary -->
<?php
$total_days = count($attendance_records);
$present_days = count(array_filter($attendance_records, function($r) { return $r['status'] === 'present'; }));
$absent_days = count(array_filter($attendance_records, function($r) { return $r['status'] === 'absent'; }));
$late_days = count(array_filter($attendance_records, function($r) { return $r['status'] === 'late'; }));
$half_days = count(array_filter($attendance_records, function($r) { return $r['status'] === 'half_day'; }));
$total_hours = array_sum(array_column($attendance_records, 'hours_worked'));
$overtime_hours = array_sum(array_column($attendance_records, 'overtime_hours'));
?>

<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4><?php echo $present_days; ?></h4>
                <small>Present Days</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h4><?php echo $absent_days; ?></h4>
                <small>Absent Days</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h4><?php echo $late_days; ?></h4>
                <small>Late Days</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4><?php echo $half_days; ?></h4>
                <small>Half Days</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4><?php echo number_format($total_hours, 1); ?></h4>
                <small>Total Hours</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4><?php echo number_format($overtime_hours, 1); ?></h4>
                <small>Overtime Hours</small>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Records -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-calendar-alt me-2"></i>
            Attendance Records for <?php echo date('F Y', strtotime($month . '-01')); ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($attendance_records)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No attendance records found</h5>
                <p class="text-muted">Add attendance records using the "Add Attendance" button above.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Hours Worked</th>
                            <th>Overtime</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                            <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                            <td>
                                <div class="time-edit-container" data-attendance-id="<?php echo $record['id']; ?>" data-field="time_in">
                                    <span class="time-display">
                                        <?php echo $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '-'; ?>
                                    </span>
                                    <div class="time-edit-form" style="display: none;">
                                        <input type="time" class="form-control form-control-sm" 
                                               value="<?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : ''; ?>"
                                               data-original-value="<?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : ''; ?>">
                                        <div class="btn-group btn-group-sm mt-1">
                                            <button type="button" class="btn btn-success btn-sm" onclick="saveTimeEdit(this)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="cancelTimeEdit(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-1 edit-time-btn" onclick="editTime(this)" title="Edit Time">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="time-edit-container" data-attendance-id="<?php echo $record['id']; ?>" data-field="time_out">
                                    <span class="time-display">
                                        <?php echo $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '-'; ?>
                                    </span>
                                    <div class="time-edit-form" style="display: none;">
                                        <input type="time" class="form-control form-control-sm" 
                                               value="<?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : ''; ?>"
                                               data-original-value="<?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : ''; ?>">
                                        <div class="btn-group btn-group-sm mt-1">
                                            <button type="button" class="btn btn-success btn-sm" onclick="saveTimeEdit(this)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="cancelTimeEdit(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-1 edit-time-btn" onclick="editTime(this)" title="Edit Time">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                            <td><?php echo number_format($record['hours_worked'], 2); ?> hrs</td>
                            <td><?php echo number_format($record['overtime_hours'], 2); ?> hrs</td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($record['status']) {
                                    case 'present': $status_class = 'success'; break;
                                    case 'absent': $status_class = 'danger'; break;
                                    case 'late': $status_class = 'warning'; break;
                                    case 'half_day': $status_class = 'info'; break;
                                    case 'overtime': $status_class = 'primary'; break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $record['notes'] ? htmlspecialchars($record['notes']) : '-'; ?>
                            </td>
                            <td>
                                <?php if (hasRole(ROLE_ADMIN) || hasRole(ROLE_HR)): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteAttendance(<?php echo $record['id']; ?>)" title="Delete Record">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Attendance Modal -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Add Attendance for <?php echo htmlspecialchars($employee['employee_name']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_attendance">
                <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="attendance_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="time_in" class="form-label">Time In</label>
                                <input type="time" class="form-control" id="time_in" name="time_in">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="time_out" class="form-label">Time Out</label>
                                <input type="time" class="form-control" id="time_out" name="time_out">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="overtime">Overtime</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hours_worked" class="form-label">Hours Worked</label>
                                <input type="number" class="form-control" id="hours_worked" name="hours_worked" step="0.25" value="8">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="overtime_hours" class="form-label">Overtime Hours</label>
                                <input type="number" class="form-control" id="overtime_hours" name="overtime_hours" step="0.25" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Attendance Modal -->
<div class="modal fade" id="bulkAttendanceModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Bulk Attendance Entry for <?php echo htmlspecialchars($employee['employee_name']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="bulkAttendanceForm">
                <input type="hidden" name="action" value="bulk_attendance">
                <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                <input type="hidden" name="start_date" id="bulk_start_date">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bulk_period_start" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="bulk_period_start" value="<?php echo date('Y-m-01'); ?>" onchange="generateBulkDays()">
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_period_end" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="bulk_period_end" value="<?php echo date('Y-m-t'); ?>" onchange="generateBulkDays()">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instructions:</strong> 
                        <ul class="mb-0">
                            <li><strong>Flexible Entry:</strong> Enter time data for present days, leave blank for absent days</li>
                            <li><strong>Auto-Calculation:</strong> Hours calculated automatically based on time in/out</li>
                            <li><strong>Lunch Break:</strong> 1 hour automatically deducted for shifts longer than 5 hours</li>
                            <li><strong>8-Hour Cap:</strong> Regular work hours capped at 8.00 hours (unless "Overtime" status)</li>
                            <li><strong>Smart Validation:</strong> System will auto-correct "Present" records without time data to "Absent"</li>
                        </ul>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-sm table-bordered">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th style="width: 120px;">Date</th>
                                    <th style="width: 100px;">Day</th>
                                    <th style="width: 100px;">Time In</th>
                                    <th style="width: 100px;">Time Out</th>
                                    <th style="width: 80px;">Status</th>
                                    <th style="width: 80px;">Hours</th>
                                    <th style="width: 80px;">OT Hours</th>
                                    <th style="width: 200px;">Notes</th>
                                </tr>
                            </thead>
                            <tbody id="bulkAttendanceTable">
                                <!-- Days will be generated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info" onclick="generateBulkDays()">
                        <i class="fas fa-refresh"></i> Regenerate Days
                    </button>
                    <button type="submit" class="btn btn-success" onclick="return validateBulkAttendance()">
                        <i class="fas fa-save"></i> Save All Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Hidden form for delete operations -->
<form id="deleteAttendanceForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_attendance">
    <input type="hidden" name="attendance_id" id="deleteAttendanceId">
</form>

<script>
function deleteAttendance(attendanceId) {
    if (confirm('Are you sure you want to delete this attendance record?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteAttendanceId').value = attendanceId;
        document.getElementById('deleteAttendanceForm').submit();
    }
}
// Auto-calculate hours based on time in/out
document.getElementById('time_out')?.addEventListener('change', function() {
    const timeIn = document.getElementById('time_in').value;
    const timeOut = this.value;
    const status = document.getElementById('status').value;
    
    if (timeIn && timeOut) {
        const timeInDate = new Date('1970-01-01T' + timeIn + ':00');
        const timeOutDate = new Date('1970-01-01T' + timeOut + ':00');
        
        if (timeOutDate > timeInDate) {
            const diffMs = timeOutDate - timeInDate;
            let diffHours = diffMs / (1000 * 60 * 60);
            
            // Account for lunch break (1 hour) if working more than 5 hours
            if (diffHours > 5) {
                diffHours = diffHours - 1; // Subtract 1 hour for lunch break
            }
            
            // Cap regular work hours at 8.00 (unless it's overtime status)
            if (status !== 'overtime' && diffHours > 8) {
                diffHours = 8.00;
            }
            
            document.getElementById('hours_worked').value = diffHours.toFixed(2);
            
            // Auto-update status based on time_in and hours worked
            // Only mark as half_day if time_in is between 1:00 PM (13:00) and 5:30 PM (17:30)
            const timeInHour = parseInt(timeIn.split(':')[0]);
            const timeInMinute = parseInt(timeIn.split(':')[1]);
            const timeInMinutes = timeInHour * 60 + timeInMinute;
            const halfDayStart = 13 * 60; // 1:00 PM in minutes
            const halfDayEnd = 17 * 60 + 30; // 5:30 PM in minutes
            
            if (diffHours >= 8) {
                document.getElementById('status').value = 'present';
            } else if (diffHours >= 4 && timeInMinutes >= halfDayStart && timeInMinutes <= halfDayEnd) {
                // Only mark as half_day if time_in is between 1:00 PM and 5:30 PM
                document.getElementById('status').value = 'half_day';
            } else if (diffHours >= 4) {
                // If working 4+ hours but not in half_day time range, mark as present
                document.getElementById('status').value = 'present';
            } else {
                document.getElementById('status').value = 'absent';
            }
        }
    }
});

// Set hours worked based on status
document.getElementById('status')?.addEventListener('change', function() {
    const hoursWorked = document.getElementById('hours_worked');
    switch (this.value) {
        case 'absent':
            hoursWorked.value = '0';
            break;
        case 'half_day':
            hoursWorked.value = '4';
            break;
        case 'present':
        case 'late':
            hoursWorked.value = '8';
            break;
    }
});

// Bulk attendance functions
function generateBulkDays() {
    const startDate = document.getElementById('bulk_period_start').value;
    const endDate = document.getElementById('bulk_period_end').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates.');
        return;
    }
    
    document.getElementById('bulk_start_date').value = startDate;
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    const tableBody = document.getElementById('bulkAttendanceTable');
    tableBody.innerHTML = '';
    
    let dayCount = 0;
    const currentDate = new Date(start);
    
    while (currentDate <= end && dayCount < 15) {
        const dateStr = currentDate.toISOString().split('T')[0];
        const dayName = currentDate.toLocaleDateString('en-US', { weekday: 'short' });
        const isSunday = currentDate.getDay() === 0;
        
        // Include all days, but mark Sundays differently
        const row = document.createElement('tr');
        const sundayClass = isSunday ? 'table-warning' : '';
        const sundayLabel = isSunday ? ' (Sunday)' : '';
        
        row.innerHTML = `
            <td class="${sundayClass}">
                <input type="hidden" name="attendance[${dateStr}][date]" value="${dateStr}">
                ${dateStr}${sundayLabel}
            </td>
            <td class="${sundayClass}">${dayName}</td>
            <td>
                <input type="time" class="form-control form-control-sm" 
                       name="attendance[${dateStr}][time_in]" 
                       onchange="calculateBulkHours('${dateStr}')">
            </td>
            <td>
                <input type="time" class="form-control form-control-sm" 
                       name="attendance[${dateStr}][time_out]" 
                       onchange="calculateBulkHours('${dateStr}')">
            </td>
            <td>
                <select class="form-select form-select-sm" 
                        name="attendance[${dateStr}][status]" 
                        onchange="updateBulkStatus('${dateStr}')">
                    <option value="absent">Absent</option>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="half_day">Half Day</option>
                    <option value="overtime">Overtime</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       name="attendance[${dateStr}][hours_worked]" 
                       step="0.25" value="0" readonly>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       name="attendance[${dateStr}][overtime_hours]" 
                       step="0.25" value="0">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" 
                       name="attendance[${dateStr}][notes]" 
                       placeholder="Notes">
            </td>
        `;
        tableBody.appendChild(row);
        dayCount++;
        currentDate.setDate(currentDate.getDate() + 1);
    }
}

function calculateBulkHours(dateStr) {
    const timeIn = document.querySelector(`input[name="attendance[${dateStr}][time_in]"]`).value;
    const timeOut = document.querySelector(`input[name="attendance[${dateStr}][time_out]"]`).value;
    const hoursInput = document.querySelector(`input[name="attendance[${dateStr}][hours_worked]"]`);
    const statusSelect = document.querySelector(`select[name="attendance[${dateStr}][status]"]`);
    
    if (timeIn && timeOut) {
        const timeInDate = new Date('1970-01-01T' + timeIn + ':00');
        const timeOutDate = new Date('1970-01-01T' + timeOut + ':00');
        
        if (timeOutDate > timeInDate) {
            const diffMs = timeOutDate - timeInDate;
            let diffHours = diffMs / (1000 * 60 * 60);
            
            // Account for lunch break (1 hour) if working more than 5 hours
            if (diffHours > 5) {
                diffHours = diffHours - 1; // Subtract 1 hour for lunch break
            }
            
            // Cap regular work hours at 8.00 (unless it's overtime status)
            if (statusSelect.value !== 'overtime' && diffHours > 8) {
                diffHours = 8.00;
            }
            
            hoursInput.value = diffHours.toFixed(2);
            
            // Auto-set status based on time_in and hours worked
            // Only mark as half_day if time_in is between 1:00 PM (13:00) and 5:30 PM (17:30)
            const timeInHour = parseInt(timeIn.split(':')[0]);
            const timeInMinute = parseInt(timeIn.split(':')[1]);
            const timeInMinutes = timeInHour * 60 + timeInMinute;
            const halfDayStart = 13 * 60; // 1:00 PM in minutes
            const halfDayEnd = 17 * 60 + 30; // 5:30 PM in minutes
            
            if (diffHours >= 8) {
                statusSelect.value = 'present';
            } else if (diffHours >= 4 && timeInMinutes >= halfDayStart && timeInMinutes <= halfDayEnd) {
                // Only mark as half_day if time_in is between 1:00 PM and 5:30 PM
                statusSelect.value = 'half_day';
            } else if (diffHours >= 4) {
                // If working 4+ hours but not in half_day time range, mark as present
                statusSelect.value = 'present';
            } else {
                statusSelect.value = 'absent';
            }
        }
    }
}

function updateBulkStatus(dateStr) {
    const statusSelect = document.querySelector(`select[name="attendance[${dateStr}][status]"]`);
    const hoursInput = document.querySelector(`input[name="attendance[${dateStr}][hours_worked]"]`);
    const timeInInput = document.querySelector(`input[name="attendance[${dateStr}][time_in]"]`);
    const timeOutInput = document.querySelector(`input[name="attendance[${dateStr}][time_out]"]`);
    
    switch (statusSelect.value) {
        case 'absent':
            hoursInput.value = '0';
            timeInInput.value = '';
            timeOutInput.value = '';
            break;
        case 'half_day':
            hoursInput.value = '4';
            if (!timeInInput.value) timeInInput.value = '08:00';
            if (!timeOutInput.value) timeOutInput.value = '12:00';
            break;
        case 'present':
        case 'late':
        case 'overtime':
            hoursInput.value = '8';
            if (!timeInInput.value) timeInInput.value = '08:30';
            if (!timeOutInput.value) timeOutInput.value = '17:30';
            break;
    }
}

// Add validation before form submission
function validateBulkAttendance() {
    const form = document.getElementById('bulkAttendanceForm');
    const rows = document.querySelectorAll('#bulkAttendanceTable tr');
    let hasValidData = false;
    let emptyPresentCount = 0;
    let totalRecords = 0;
    
    rows.forEach(row => {
        const timeIn = row.querySelector('input[type="time"]:nth-of-type(1)');
        const timeOut = row.querySelector('input[type="time"]:nth-of-type(2)');
        const status = row.querySelector('select');
        
        if (timeIn && timeOut && status) {
            totalRecords++;
            const hasTimeData = timeIn.value || timeOut.value;
            const isPresent = status.value === 'present';
            
            if (isPresent && !hasTimeData) {
                emptyPresentCount++;
            }
            
            if (hasTimeData || status.value === 'absent') {
                hasValidData = true;
            }
        }
    });
    
    // Allow saving if there's at least one valid record, even if others are blank
    if (!hasValidData && totalRecords > 0) {
        alert('Please enter at least one attendance record with time data or set status to absent.');
        return false;
    }
    
    // If all records are blank, just proceed (user understands this)
    if (totalRecords === 0) {
        return true;
    }
    
    // Only warn if there are many empty present records
    if (emptyPresentCount > 0 && emptyPresentCount > totalRecords / 2) {
        const confirmMessage = `You have ${emptyPresentCount} days marked as "Present" but with no time data. These will be saved as "Absent". Continue?`;
        if (!confirm(confirmMessage)) {
            return false;
        }
    }
    
    return true;
}

// Initialize bulk days when modal opens
document.getElementById('bulkAttendanceModal').addEventListener('shown.bs.modal', function() {
    generateBulkDays();
    checkSundayDuty();
});

// Check for Sunday duty and pre-populate
function checkSundayDuty() {
    const employeeId = <?php echo $employee_id ?: 'null'; ?>;
    if (!employeeId) return;
    
    const startDate = document.getElementById('bulk_period_start').value;
    const endDate = document.getElementById('bulk_period_end').value;
    
    if (!startDate || !endDate) return;
    
    // Check each Sunday in the date range
    const start = new Date(startDate);
    const end = new Date(endDate);
    const currentDate = new Date(start);
    
    while (currentDate <= end) {
        if (currentDate.getDay() === 0) { // Sunday
            const dateStr = currentDate.toISOString().split('T')[0];
            checkAndPopulateSundayDuty(employeeId, dateStr);
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }
}

// Check if employee was on duty on a specific Sunday and pre-populate
function checkAndPopulateSundayDuty(employeeId, dateStr) {
    // Make AJAX request to check Sunday duty
    fetch(`check_attendance.php?employee_id=${employeeId}&date=${dateStr}`)
        .then(response => response.json())
        .then(data => {
            if (data.wasOnDuty) {
                // Pre-populate Sunday attendance
                const timeInInput = document.querySelector(`input[name="attendance[${dateStr}][time_in]"]`);
                const timeOutInput = document.querySelector(`input[name="attendance[${dateStr}][time_out]"]`);
                const statusSelect = document.querySelector(`select[name="attendance[${dateStr}][status]"]`);
                const hoursInput = document.querySelector(`input[name="attendance[${dateStr}][hours_worked]"]`);
                
                if (timeInInput && timeOutInput && statusSelect && hoursInput) {
                    // Set default values for Sunday duty
                    timeInInput.value = '08:00';
                    timeOutInput.value = '17:00';
                    statusSelect.value = 'present';
                    hoursInput.value = '8.00';
                    
                    // Add visual indicator
                    const row = timeInInput.closest('tr');
                    if (row) {
                        row.classList.add('table-info');
                        const dateCell = row.querySelector('td:first-child');
                        if (dateCell) {
                            dateCell.innerHTML = dateCell.innerHTML.replace('(Sunday)', '(Sunday - On Duty)');
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.log('Error checking Sunday duty:', error);
        });
}

// Time editing functions
function editTime(button) {
    const container = button.closest('.time-edit-container');
    const display = container.querySelector('.time-display');
    const editForm = container.querySelector('.time-edit-form');
    const input = editForm.querySelector('input[type="time"]');
    
    // Hide display and show edit form
    display.style.display = 'none';
    editForm.style.display = 'block';
    button.style.display = 'none';
    
    // Focus on input
    input.focus();
    input.select();
}

function cancelTimeEdit(button) {
    const container = button.closest('.time-edit-container');
    const display = container.querySelector('.time-display');
    const editForm = container.querySelector('.time-edit-form');
    const input = editForm.querySelector('input[type="time"]');
    const editBtn = container.querySelector('.edit-time-btn');
    
    // Reset input to original value
    input.value = input.getAttribute('data-original-value');
    
    // Hide edit form and show display
    editForm.style.display = 'none';
    display.style.display = 'inline';
    editBtn.style.display = 'inline-block';
}

function saveTimeEdit(button) {
    const container = button.closest('.time-edit-container');
    const attendanceId = container.getAttribute('data-attendance-id');
    const field = container.getAttribute('data-field');
    const input = container.querySelector('input[type="time"]');
    const newValue = input.value;
    const originalValue = input.getAttribute('data-original-value');
    
    // If no change, just cancel
    if (newValue === originalValue) {
        cancelTimeEdit(button);
        return;
    }
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Make AJAX request to update time
    fetch('update_attendance_time.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            attendance_id: attendanceId,
            field: field,
            value: newValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update display with new value
            const display = container.querySelector('.time-display');
            if (newValue) {
                // Convert 24-hour format to 12-hour format for display
                const [hours, minutes] = newValue.split(':');
                const hour12 = hours % 12 || 12;
                const ampm = hours >= 12 ? 'PM' : 'AM';
                display.textContent = `${hour12}:${minutes} ${ampm}`;
            } else {
                display.textContent = '-';
            }
            
            // Update original value
            input.setAttribute('data-original-value', newValue);
            
            // Hide edit form and show display
            container.querySelector('.time-edit-form').style.display = 'none';
            display.style.display = 'inline';
            container.querySelector('.edit-time-btn').style.display = 'inline-block';
            
            // Show success message
            showNotification('Time updated successfully!', 'success');
            
            // Recalculate hours if both time_in and time_out are present
            recalculateHours(container);
        } else {
            // Show error message
            showNotification('Failed to update time: ' + (data.message || 'Unknown error'), 'error');
            
            // Reset button
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check"></i>';
        }
    })
    .catch(error => {
        console.error('Error updating time:', error);
        showNotification('Error updating time: ' + error.message, 'error');
        
        // Reset button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check"></i>';
    });
}

function recalculateHours(container) {
    const row = container.closest('tr');
    const timeInContainer = row.querySelector('[data-field="time_in"]');
    const timeOutContainer = row.querySelector('[data-field="time_out"]');
    
    if (timeInContainer && timeOutContainer) {
        const timeInInput = timeInContainer.querySelector('input[type="time"]');
        const timeOutInput = timeOutContainer.querySelector('input[type="time"]');
        
        if (timeInInput && timeOutInput) {
            const timeIn = timeInInput.value;
            const timeOut = timeOutInput.value;
            
            if (timeIn && timeOut) {
                // Calculate hours worked
                const timeInDate = new Date('1970-01-01T' + timeIn + ':00');
                const timeOutDate = new Date('1970-01-01T' + timeOut + ':00');
                
                if (timeOutDate > timeInDate) {
                    const diffMs = timeOutDate - timeInDate;
                    let diffHours = diffMs / (1000 * 60 * 60);
                    
                    // Account for lunch break (1 hour) if working more than 5 hours
                    if (diffHours > 5) {
                        diffHours = diffHours - 1; // Subtract 1 hour for lunch break
                    }
                    
                    // Update hours worked display (you might want to make this editable too)
                    const hoursCell = row.querySelector('td:nth-child(5)'); // Hours Worked column
                    if (hoursCell) {
                        hoursCell.textContent = diffHours.toFixed(2) + ' hrs';
                    }
                }
            }
        }
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}
</script>

<style>
/* Bulk Attendance Modal Styles */
#bulkAttendanceModal .modal-dialog {
    max-width: 95%;
}

#bulkAttendanceModal .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

#bulkAttendanceModal .table th {
    background-color: #343a40;
    color: white;
    font-size: 0.875rem;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
}

#bulkAttendanceModal .table td {
    vertical-align: middle;
    padding: 0.5rem;
}

#bulkAttendanceModal .form-control-sm,
#bulkAttendanceModal .form-select-sm {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

#bulkAttendanceModal .table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Sticky header for better UX */
#bulkAttendanceModal .table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Status indicators */
.status-present { color: #28a745; }
.status-absent { color: #dc3545; }
.status-late { color: #ffc107; }
.status-half_day { color: #17a2b8; }
.status-overtime { color: #6f42c1; }

/* Sunday duty styling */
.table-warning {
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107;
}

.table-info {
    background-color: #d1ecf1 !important;
    border-left: 4px solid #17a2b8;
}

.sunday-duty-indicator {
    font-weight: bold;
    color: #856404;
}

/* Time editing styles */
.time-edit-container {
    position: relative;
    display: inline-block;
    min-width: 120px;
}

.time-display {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    min-width: 80px;
}

.time-edit-form {
    display: none;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
    background: white;
    border: 2px solid #007bff;
    border-radius: 4px;
    padding: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    min-width: 200px;
}

.time-edit-form input[type="time"] {
    width: 100%;
    margin-bottom: 8px;
}

.edit-time-btn {
    opacity: 0.7;
    transition: opacity 0.2s;
}

.edit-time-btn:hover {
    opacity: 1;
}

.time-edit-container:hover .edit-time-btn {
    opacity: 1;
}

/* Loading state for save button */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Notification styles */
.alert.position-fixed {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
