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
                                <?php echo $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '-'; ?>
                            </td>
                            <td>
                                <?php echo $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '-'; ?>
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
    
    if (timeIn && timeOut) {
        const timeInDate = new Date('1970-01-01T' + timeIn + ':00');
        const timeOutDate = new Date('1970-01-01T' + timeOut + ':00');
        
        if (timeOutDate > timeInDate) {
            const diffMs = timeOutDate - timeInDate;
            const diffHours = diffMs / (1000 * 60 * 60);
            document.getElementById('hours_worked').value = diffHours.toFixed(2);
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
</script>

<?php include 'includes/footer.php'; ?>
