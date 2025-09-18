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

$page_title = 'Payroll Management';

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_employee':
                try {
                    $employee_code = generateEmployeeCode($pdo);
                    $data = [
                        'employee_code' => $employee_code,
                        'employee_name' => $_POST['employee_name'],
                        'position' => $_POST['position'],
                        'date_of_joining' => $_POST['date_of_joining'],
                        'basic_salary' => $_POST['basic_salary'],
                        'allowances' => $_POST['allowances']
                    ];
                    
                    if (addEmployee($pdo, $data)) {
                        $message = 'Employee added successfully!';
                    } else {
                        $error = 'Failed to add employee.';
                    }
                } catch (Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
                
            case 'add_attendance':
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
                    } else {
                        $error = 'Failed to add attendance record.';
                    }
                } catch (Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
                
            case 'generate_payroll':
                try {
                    $employee_id = $_POST['employee_id'];
                    $pay_period_start = $_POST['pay_period_start'];
                    $pay_period_end = $_POST['pay_period_end'];
                    
                    // Process dynamic packages
                    $packages = [];
                    if (!empty($_POST['package_names']) && !empty($_POST['package_amounts'])) {
                        for ($i = 0; $i < count($_POST['package_names']); $i++) {
                            $name = trim($_POST['package_names'][$i]);
                            $amount = floatval($_POST['package_amounts'][$i]);
                            if (!empty($name) && $amount > 0) {
                                $packages[] = [
                                    'name' => $name,
                                    'amount' => $amount
                                ];
                            }
                        }
                    }
                    
                    // Get adjustments from form
                    $adjustments = [
                        'packages' => $packages,
                        'bonus_pay' => $_POST['bonus_pay'] ?? 0,
                        'cash_advance' => $_POST['cash_advance'] ?? 0,
                        'uniforms' => $_POST['uniforms'] ?? 0,
                        'tools' => $_POST['tools'] ?? 0,
                        'late_penalty' => $_POST['late_penalty'] ?? 0,
                        'miscellaneous' => $_POST['miscellaneous'] ?? 0
                    ];
                    
                    $payroll_data = calculatePayroll($pdo, $employee_id, $pay_period_start, $pay_period_end, $adjustments);
                    
                    if ($payroll_data) {
                        // Debug information - remove after testing
                        $debug_info = "Salary Calculation Details:<br>";
                        $debug_info .= "Daily Rate: ₱" . number_format($payroll_data['daily_rate'], 2) . "<br>";
                        $debug_info .= "Hourly Rate: ₱" . number_format($payroll_data['hourly_rate'], 2) . "<br>";
                        $debug_info .= "Total Hours Worked: " . number_format($payroll_data['total_hours_worked'], 2) . " hours<br>";
                        $debug_info .= "Working Days Present: " . $payroll_data['working_days_present'] . "<br>";
                        $debug_info .= "Total Working Days: " . $payroll_data['total_working_days'] . "<br>";
                        $debug_info .= "Basic Salary (Hour-based): ₱" . number_format($payroll_data['basic_salary'], 2) . "<br>";
                        
                        if (createPayroll($pdo, $payroll_data)) {
                            $message = 'Payroll generated successfully!<br><small>' . $debug_info . '</small>';
                        } else {
                            $error = 'Failed to create payroll record.';
                        }
                    } else {
                        $error = 'Failed to calculate payroll data.';
                    }
                } catch (Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
                
            case 'delete_employee':
                try {
                    $employee_id = $_POST['employee_id'];
                    if (deleteEmployee($pdo, $employee_id)) {
                        $message = 'Employee deleted/deactivated successfully!';
                    } else {
                        $error = 'Failed to delete employee.';
                    }
                } catch (Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
                
            case 'delete_payroll':
                try {
                    $payroll_id = $_POST['payroll_id'];
                    if (deletePayroll($pdo, $payroll_id)) {
                        $message = 'Payroll record deleted successfully!';
                    } else {
                        $error = 'Failed to delete payroll record. Only draft records can be deleted.';
                    }
                } catch (Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get employees and payroll records
$employees = getAllEmployees($pdo);
$payroll_records = getPayrollRecords($pdo, null, 20);
$current_period = getCurrentPayPeriod();

// Handle active tab after form submission
$active_tab = $_POST['active_tab'] ?? 'payroll';

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-money-check-alt me-2"></i>Payroll Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-user-plus"></i> Add Employee
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Payroll Dashboard -->
<style>
.dashboard-card {
    height: 120px;
}
.dashboard-card .card-body {
    height: 100%;
    display: flex;
    align-items: center;
    padding: 1rem;
}
.dashboard-card .card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.dashboard-card .card-title {
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 8px !important;
    opacity: 0.9;
}
.dashboard-card .card-value {
    font-size: 1.75rem;
    font-weight: bold;
    line-height: 1.1;
    margin: 0;
}
.dashboard-card .card-icon {
    margin-left: 1rem;
    opacity: 0.8;
}
</style>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white dashboard-card">
            <div class="card-body">
                <div class="card-content">
                    <h5 class="card-title">Total Employees</h5>
                    <div class="card-value"><?php echo count($employees); ?></div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white dashboard-card">
            <div class="card-body">
                <div class="card-content">
                    <h5 class="card-title">Current Period</h5>
                    <div class="card-value"><?php echo date('M d', strtotime($current_period['start'])) . ' - ' . date('M d', strtotime($current_period['end'])); ?></div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-calendar-alt fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white dashboard-card">
            <div class="card-body">
                <div class="card-content">
                    <h5 class="card-title">Pending Payrolls</h5>
                    <div class="card-value"><?php echo count(array_filter($payroll_records, function($p) { return $p['status'] === 'draft'; })); ?></div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white dashboard-card">
            <div class="card-body">
                <div class="card-content">
                    <h5 class="card-title">This Month</h5>
                    <div class="card-value"><?php echo formatCurrency(array_sum(array_column($payroll_records, 'net_salary'))); ?></div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs" id="payrollTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $active_tab === 'payroll' ? 'active' : ''; ?>" id="payroll-tab" data-bs-toggle="tab" data-bs-target="#payroll" type="button" role="tab">
            <i class="fas fa-money-check-alt me-1"></i>Payroll Records
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $active_tab === 'employees' ? 'active' : ''; ?>" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab">
            <i class="fas fa-users me-1"></i>Employees
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $active_tab === 'attendance' ? 'active' : ''; ?>" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab">
            <i class="fas fa-calendar-check me-1"></i>Attendance
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $active_tab === 'generate' ? 'active' : ''; ?>" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate" type="button" role="tab">
            <i class="fas fa-calculator me-1"></i>Generate Payroll
        </button>
    </li>
</ul>

<div class="tab-content" id="payrollTabContent">
    <!-- Payroll Records Tab -->
    <div class="tab-pane fade <?php echo $active_tab === 'payroll' ? 'show active' : ''; ?>" id="payroll" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Payroll Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Position</th>
                                <th>Period</th>
                                <th>Hours Worked</th>
                                <th>Gross Salary</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payroll_records as $record): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($record['employee_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($record['position']); ?></td>
                                <td>
                                    <?php echo date('M d', strtotime($record['pay_period_start'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($record['pay_period_end'])); ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($record['total_hours_worked'] ?? 0, 1); ?> hrs</strong><br>
                                    <small class="text-muted"><?php echo $record['working_days_present'] ?? 0; ?> days</small>
                                </td>
                                <td><?php echo formatCurrency($record['gross_salary']); ?></td>
                                <td><?php echo formatCurrency($record['total_deductions']); ?></td>
                                <td><strong><?php echo formatCurrency($record['net_salary']); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($record['status']) {
                                        case 'draft': $status_class = 'warning'; break;
                                        case 'approved': $status_class = 'success'; break;
                                        case 'paid': $status_class = 'primary'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="payroll_slip.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Salary Slip">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    <?php if ($record['status'] === 'draft' && hasRole(ROLE_ADMIN)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="approvePayroll(<?php echo $record['id']; ?>)" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePayroll(<?php echo $record['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Tab -->
    <div class="tab-pane fade <?php echo $active_tab === 'employees' ? 'show active' : ''; ?>" id="employees" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Employee List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Date Joined</th>
                                <th>Basic Salary</th>
                                <th>Allowances</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($employee['employee_code']); ?></td>
                                <td><strong><?php echo htmlspecialchars($employee['employee_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($employee['date_of_joining'])); ?></td>
                                <td><?php echo formatCurrency($employee['basic_salary']); ?></td>
                                <td><?php echo formatCurrency($employee['allowances']); ?></td>
                                <td>
                                    <a href="employee_attendance.php?employee_id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-outline-info" title="View Attendance">
                                        <i class="fas fa-calendar-check"></i>
                                    </a>
                                    <?php if (hasRole(ROLE_ADMIN)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(<?php echo $employee['id']; ?>)" title="Delete Employee">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Tab -->
    <div class="tab-pane fade <?php echo $active_tab === 'attendance' ? 'show active' : ''; ?>" id="attendance" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Quick Attendance Entry</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                    <i class="fas fa-plus"></i> Add Attendance
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted">Use the "Add Attendance" button to record employee attendance for any date.</p>
                        <p><strong>Today's Date:</strong> <?php echo date('F d, Y'); ?></p>
                        <p><strong>Current Pay Period:</strong> <?php echo date('M d', strtotime($current_period['start'])) . ' - ' . date('M d, Y', strtotime($current_period['end'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Attendance Status Options:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> Present - Full day attendance</li>
                                    <li><i class="fas fa-times text-danger"></i> Absent - No attendance</li>
                                    <li><i class="fas fa-clock text-warning"></i> Late - Arrived late</li>
                                    <li><i class="fas fa-adjust text-info"></i> Half Day - Partial attendance</li>
                                    <li><i class="fas fa-plus text-primary"></i> Overtime - Extra hours worked</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Payroll Tab -->
    <div class="tab-pane fade <?php echo $active_tab === 'generate' ? 'show active' : ''; ?>" id="generate" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Generate Payroll</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate_payroll">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Select Employee</label>
                                <select class="form-select" id="employee_id" name="employee_id" required>
                                    <option value="">Choose an employee...</option>
                                    <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['employee_code'] . ' - ' . $employee['employee_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pay_period_start" class="form-label">Period Start</label>
                                        <input type="date" class="form-control" id="pay_period_start" name="pay_period_start" value="<?php echo $current_period['start']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pay_period_end" class="form-label">Period End</label>
                                        <input type="date" class="form-control" id="pay_period_end" name="pay_period_end" value="<?php echo $current_period['end']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Additional Income</h6>
                            <div class="mb-3">
                                <label for="bonus_pay" class="form-label">Bonus Pay</label>
                                <input type="number" class="form-control" id="bonus_pay" name="bonus_pay" step="0.01" value="0">
                            </div>
                            
                            <h6>Package Salaries</h6>
                            <div id="packages-container">
                                <!-- Empty container - packages will be added dynamically -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPackage()">
                                <i class="fas fa-plus"></i> Add Package
                            </button>
                        </div>
                        <div class="col-md-6">
                            <h6>Deductions</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cash_advance" class="form-label">Cash Advance</label>
                                        <input type="number" class="form-control" id="cash_advance" name="cash_advance" step="0.01" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="uniforms" class="form-label">Uniforms</label>
                                        <input type="number" class="form-control" id="uniforms" name="uniforms" step="0.01" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tools" class="form-label">Tools</label>
                                        <input type="number" class="form-control" id="tools" name="tools" step="0.01" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="miscellaneous" class="form-label">Miscellaneous</label>
                                        <input type="number" class="form-control" id="miscellaneous" name="miscellaneous" step="0.01" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="late_penalty" class="form-label">Late Penalty (per instance)</label>
                                <input type="number" class="form-control" id="late_penalty" name="late_penalty" step="0.01" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-calculator me-2"></i>Generate Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_employee">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_name" class="form-label">Employee Name</label>
                                <input type="text" class="form-control" id="employee_name" name="employee_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_of_joining" class="form-label">Date of Joining</label>
                                <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="basic_salary" class="form-label">Basic Salary (Daily Rate)</label>
                                <input type="number" class="form-control" id="basic_salary" name="basic_salary" step="0.01" required>
                                <div class="form-text">Salary will be calculated based on actual hours worked (Daily Rate ÷ 8 = Hourly Rate)</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="allowances" class="form-label">Allowances</label>
                        <input type="number" class="form-control" id="allowances" name="allowances" step="0.01" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Attendance Modal -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Add Attendance Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_attendance">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="att_employee_id" class="form-label">Employee</label>
                        <select class="form-select" id="att_employee_id" name="employee_id" required>
                            <option value="">Choose an employee...</option>
                            <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['employee_code'] . ' - ' . $employee['employee_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="attendance_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="time_in" class="form-label">Time In</label>
                                <input type="time" class="form-control" id="time_in" name="time_in">
                                <div class="form-text">Hours will be calculated automatically</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="time_out" class="form-label">Time Out</label>
                                <input type="time" class="form-control" id="time_out" name="time_out">
                                <div class="invalid-feedback">Time out must be after time in</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="att_status" class="form-label">Status</label>
                        <select class="form-select" id="att_status" name="status" required>
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
                                <input type="number" class="form-control" id="hours_worked" name="hours_worked" step="0.25" value="8" readonly>
                                <div class="form-text">Auto-calculated from time in/out (1hr lunch break deducted if >6hrs)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="overtime_hours" class="form-label">Overtime Hours</label>
                                <input type="number" class="form-control" id="overtime_hours" name="overtime_hours" step="0.25" value="0" readonly>
                                <div class="form-text">Auto-calculated (hours over 8)</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="att_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="att_notes" name="notes" rows="2"></textarea>
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

<!-- Hidden forms for delete operations -->
<form id="deleteEmployeeForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_employee">
    <input type="hidden" name="employee_id" id="deleteEmployeeId">
    <input type="hidden" name="active_tab" value="employees">
</form>

<form id="deletePayrollForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_payroll">
    <input type="hidden" name="payroll_id" id="deletePayrollId">
</form>

<script>
function approvePayroll(payrollId) {
    if (confirm('Are you sure you want to approve this payroll record?')) {
        // You can implement this via AJAX or a form submission
        window.location.href = `payroll_approve.php?id=${payrollId}`;
    }
}

function deleteEmployee(employeeId) {
    if (confirm('Are you sure you want to delete this employee?\n\nNote: If the employee has payroll or attendance records, they will be deactivated instead of deleted.')) {
        document.getElementById('deleteEmployeeId').value = employeeId;
        document.getElementById('deleteEmployeeForm').submit();
    }
}

function deletePayroll(payrollId) {
    if (confirm('Are you sure you want to delete this payroll record?\n\nThis action cannot be undone. Only draft payroll records can be deleted.')) {
        document.getElementById('deletePayrollId').value = payrollId;
        document.getElementById('deletePayrollForm').submit();
    }
}

// Package management functions
function addPackage() {
    const container = document.getElementById('packages-container');
    const newRow = document.createElement('div');
    newRow.className = 'package-row mb-2';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="form-control" name="package_names[]" placeholder="Package Name">
            </div>
            <div class="col-md-5">
                <input type="number" class="form-control" name="package_amounts[]" placeholder="Amount" step="0.01" value="0">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePackage(this)">×</button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
}

function removePackage(button) {
    const row = button.closest('.package-row');
    const container = document.getElementById('packages-container');
    
    // Always allow removing packages since we start with none
    row.remove();
}

// Auto-calculate hours based on time in/out
function calculateHoursWorked() {
    const timeIn = document.getElementById('time_in').value;
    const timeOut = document.getElementById('time_out').value;
    const hoursWorkedInput = document.getElementById('hours_worked');
    const overtimeHoursInput = document.getElementById('overtime_hours');
    const statusSelect = document.getElementById('att_status');
    
    if (timeIn && timeOut) {
        const timeInDate = new Date('1970-01-01T' + timeIn + ':00');
        const timeOutDate = new Date('1970-01-01T' + timeOut + ':00');
        
        if (timeOutDate > timeInDate) {
            const diffMs = timeOutDate - timeInDate;
            const diffHours = diffMs / (1000 * 60 * 60);
            
            // Subtract 1 hour for lunch break if working more than 6 hours
            let adjustedHours = diffHours;
            if (diffHours > 6) {
                adjustedHours = diffHours - 1; // 1 hour lunch break
            }
            
            const roundedHours = Math.round(adjustedHours * 4) / 4; // Round to nearest 0.25
            
            hoursWorkedInput.value = roundedHours.toFixed(2);
            
            // Calculate overtime (over 8 hours)
            const overtimeHours = Math.max(0, roundedHours - 8);
            overtimeHoursInput.value = overtimeHours.toFixed(2);
            
            // Auto-update status based on hours worked
            if (roundedHours >= 8) {
                if (overtimeHours > 0) {
                    statusSelect.value = 'overtime';
                } else {
                    statusSelect.value = 'present';
                }
            } else if (roundedHours >= 4) {
                statusSelect.value = 'half_day';
            } else if (roundedHours > 0) {
                statusSelect.value = 'late';
            }
            
            // Clear any validation errors
            document.getElementById('time_in').classList.remove('is-invalid');
            document.getElementById('time_out').classList.remove('is-invalid');
        } else {
            // Time out is before time in - show error
            document.getElementById('time_out').classList.add('is-invalid');
            hoursWorkedInput.value = '0';
            overtimeHoursInput.value = '0';
        }
    } else {
        // Reset values if either time is missing
        hoursWorkedInput.value = '8';
        overtimeHoursInput.value = '0';
    }
}

// Add event listeners for both time inputs
document.getElementById('time_in')?.addEventListener('change', calculateHoursWorked);
document.getElementById('time_out')?.addEventListener('change', calculateHoursWorked);

// Also calculate when time_in changes
document.getElementById('time_in')?.addEventListener('input', calculateHoursWorked);
document.getElementById('time_out')?.addEventListener('input', calculateHoursWorked);
</script>

<?php include 'includes/footer.php'; ?>
