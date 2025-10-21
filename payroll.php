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

// Handle success messages from redirects
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'payroll_generated':
            $message = 'Payroll generated successfully!';
            break;
        case 'payroll_approved':
            $message = 'Payroll approved successfully!';
            break;
        case 'payroll_paid':
            $message = 'Payroll marked as paid successfully!';
            break;
        case 'payroll_deleted':
            $message = 'Payroll deleted successfully!';
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_employee':
            try {
                $data = [
                    'employee_code' => $_POST['employee_code'],
                    'employee_name' => $_POST['employee_name'],
                    'position' => $_POST['position'],
                    'date_of_joining' => $_POST['date_of_joining'],
                    'basic_salary' => $_POST['basic_salary'],
                    'allowances' => $_POST['allowances'] ?? 0
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
            
        case 'update_employee':
            try {
                $data = [
                    'employee_name' => $_POST['employee_name'],
                    'position' => $_POST['position'],
                    'date_of_joining' => $_POST['date_of_joining'],
                    'basic_salary' => $_POST['basic_salary'],
                    'allowances' => $_POST['allowances'] ?? 0
                ];
                
                if (updateEmployee($pdo, $_POST['employee_id'], $data)) {
                    $message = 'Employee updated successfully!';
                } else {
                    $error = 'Failed to update employee.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
            
        case 'delete_employee':
            try {
                if (deleteEmployee($pdo, $_POST['employee_id'])) {
                    $message = 'Employee deleted successfully!';
                } else {
                    $error = 'Failed to delete employee.';
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
                
                // Validate that employee has attendance records
                if (!hasAttendanceRecords($pdo, $employee_id, $pay_period_start, $pay_period_end)) {
                    $error = 'Cannot generate payroll: Employee has no attendance records for the selected pay period. Please add attendance records first.';
                    break;
                }
                
                // Get attendance summary for additional validation
                $attendance_count = getAttendanceRecordsCount($pdo, $employee_id, $pay_period_start, $pay_period_end);
                if ($attendance_count['total_records'] == 0) {
                    $error = 'Cannot generate payroll: No attendance records found for the selected period.';
                    break;
                }
                
                // Get adjustments from form
                $adjustments = [
                    'packages' => [],
                    'bonus_pay' => $_POST['bonus_pay'] ?? 0,
                    'cash_advance' => $_POST['cash_advance'] ?? 0,
                    'uniforms' => $_POST['uniforms'] ?? 0,
                    'tools' => $_POST['tools'] ?? 0,
                    'miscellaneous' => $_POST['miscellaneous'] ?? 0,
                    'late_penalty' => $_POST['late_penalty'] ?? 0
                ];
                
                // Process dynamic package salaries
                if (!empty($_POST['packages'])) {
                    foreach ($_POST['packages'] as $package) {
                        if (!empty($package['name']) && !empty($package['amount']) && $package['amount'] > 0) {
                            $adjustments['packages'][] = [
                                'name' => $package['name'],
                                'amount' => floatval($package['amount'])
                            ];
                        }
                    }
                }
                
                // Process custom deductions
                if (!empty($_POST['custom_deductions'])) {
                    $adjustments['custom_deductions'] = [];
                    foreach ($_POST['custom_deductions'] as $deduction) {
                        if (!empty($deduction['name']) && $deduction['amount'] > 0) {
                            $adjustments['custom_deductions'][] = $deduction;
                        }
                    }
                }
                
                $payroll_data = calculatePayroll($pdo, $employee_id, $pay_period_start, $pay_period_end, $adjustments);
                
                if ($payroll_data && createPayroll($pdo, $payroll_data)) {
                    // Redirect to prevent duplicate submission
                    header("Location: payroll.php?view=payroll&message=payroll_generated");
                    exit();
                } else {
                    $error = 'Failed to generate payroll.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
            
        case 'approve_payroll':
            try {
                if (approvePayroll($pdo, $_POST['payroll_id'], $_SESSION['user_id'])) {
                    // Redirect to prevent duplicate submission
                    header("Location: payroll.php?view=payroll&message=payroll_approved");
                    exit();
                } else {
                    $error = 'Failed to approve payroll.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
            
        case 'mark_paid':
            try {
                if (markPayrollAsPaid($pdo, $_POST['payroll_id'])) {
                    // Redirect to prevent duplicate submission
                    header("Location: payroll.php?view=payroll&message=payroll_paid");
                    exit();
                } else {
                    $error = 'Failed to mark payroll as paid.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
            
        case 'delete_payroll':
            try {
                if (deletePayroll($pdo, $_POST['payroll_id'])) {
                    // Redirect to prevent duplicate submission
                    header("Location: payroll.php?view=payroll&message=payroll_deleted");
                    exit();
                } else {
                    $error = 'Failed to delete payroll.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
    }
}

// Get current view
$view = $_GET['view'] ?? 'employees';
$employee_id = $_GET['employee_id'] ?? null;
$payroll_id = $_GET['payroll_id'] ?? null;

// Get sorting parameters
$sort_by = $_GET['sort'] ?? 'employee_code';
$sort_order = $_GET['order'] ?? 'ASC';

// Get data based on current view
$employees = getAllEmployees($pdo, true, $sort_by, $sort_order);
$payroll_records = getPayrollRecords($pdo, $employee_id, 100);

// Build per-employee totals for Reports view
$employee_totals = [];
foreach ($payroll_records as $rec) {
	$eid = $rec['employee_id'];
	if (!isset($employee_totals[$eid])) {
		$employee_totals[$eid] = [
			'employee_id' => $eid,
			'employee_code' => $rec['employee_code'] ?? '',
			'employee_name' => $rec['employee_name'] ?? '',
			'position' => $rec['position'] ?? '',
			'records' => 0,
			'total_working_days' => 0,
			'present_days' => 0,
			'gross_total' => 0.0,
			'deductions_total' => 0.0,
			'net_total' => 0.0,
			'last_period_end' => $rec['pay_period_end'] ?? null
		];
	}
	$employee_totals[$eid]['records']++;
	$employee_totals[$eid]['total_working_days'] += (int)($rec['total_working_days'] ?? 0);
	$employee_totals[$eid]['present_days'] += (float)($rec['working_days_present'] ?? 0);
	$employee_totals[$eid]['gross_total'] += (float)($rec['gross_salary'] ?? 0);
	$employee_totals[$eid]['deductions_total'] += (float)($rec['total_deductions'] ?? 0);
	$employee_totals[$eid]['net_total'] += (float)($rec['net_salary'] ?? 0);
	// Track the most recent period end
	if (!empty($rec['pay_period_end'])) {
		$curr = strtotime($rec['pay_period_end']);
		$prev = !empty($employee_totals[$eid]['last_period_end']) ? strtotime($employee_totals[$eid]['last_period_end']) : 0;
		if ($curr > $prev) {
			$employee_totals[$eid]['last_period_end'] = $rec['pay_period_end'];
		}
	}
}

// Get current pay period
$current_period = getCurrentPayPeriod();

// Helper function to generate sort URLs
function getSortUrl($column, $current_sort, $current_order) {
    $new_order = ($current_sort === $column && $current_order === 'ASC') ? 'DESC' : 'ASC';
    return "?view=employees&sort=$column&order=$new_order";
}

// Helper function to get sort icon
function getSortIcon($column, $current_sort, $current_order) {
    if ($current_sort !== $column) {
        return '<i class="fas fa-sort text-muted"></i>';
    }
    return $current_order === 'ASC' ? '<i class="fas fa-sort-up text-primary"></i>' : '<i class="fas fa-sort-down text-primary"></i>';
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-money-bill-wave me-2"></i>Payroll Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?view=employees" class="btn btn-outline-primary <?php echo $view === 'employees' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Employees
            </a>
            <a href="?view=payroll" class="btn btn-outline-primary <?php echo $view === 'payroll' ? 'active' : ''; ?>">
                <i class="fas fa-calculator"></i> Payroll
            </a>
            <a href="?view=reports" class="btn btn-outline-primary <?php echo $view === 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </div>
        <div class="btn-group">
            <a href="employee_attendance.php" class="btn btn-outline-secondary">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a href="payroll_slip.php" class="btn btn-outline-success">
                <i class="fas fa-file-invoice"></i> Pay Slips
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <script>
        // Auto-dismiss success message after 5 seconds
        setTimeout(function() {
            const alert = document.getElementById('successAlert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($view === 'employees'): ?>
<!-- Employees Management -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Employee List</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-plus"></i> Add Employee
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <a href="<?php echo getSortUrl('employee_code', $sort_by, $sort_order); ?>" class="text-white text-decoration-none">
                                        Code <?php echo getSortIcon('employee_code', $sort_by, $sort_order); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortUrl('employee_name', $sort_by, $sort_order); ?>" class="text-white text-decoration-none">
                                        Name <?php echo getSortIcon('employee_name', $sort_by, $sort_order); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortUrl('position', $sort_by, $sort_order); ?>" class="text-white text-decoration-none">
                                        Position <?php echo getSortIcon('position', $sort_by, $sort_order); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortUrl('date_of_joining', $sort_by, $sort_order); ?>" class="text-white text-decoration-none">
                                        Date Joined <?php echo getSortIcon('date_of_joining', $sort_by, $sort_order); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortUrl('basic_salary', $sort_by, $sort_order); ?>" class="text-white text-decoration-none">
                                        Basic Salary <?php echo getSortIcon('basic_salary', $sort_by, $sort_order); ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortUrl('allowances', $sort_by, $sort_order); ?>" class="text-white text-decoration-none">
                                        Allowances <?php echo getSortIcon('allowances', $sort_by, $sort_order); ?>
                                    </a>
                                </th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($employee['employee_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($employee['employee_name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($employee['date_of_joining'])); ?></td>
                                <td>₱<?php echo number_format($employee['basic_salary'], 2); ?></td>
                                <td>₱<?php echo number_format($employee['allowances'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $employee['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="employee_attendance.php?employee_id=<?php echo $employee['id']; ?>" class="btn btn-outline-info" title="Attendance">
                                            <i class="fas fa-calendar-check"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-success" onclick="generatePayroll(<?php echo $employee['id']; ?>)" title="Generate Payroll">
                                            <i class="fas fa-calculator"></i>
                                        </button>
                                        <?php if (hasRole(ROLE_ADMIN)): ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteEmployee(<?php echo $employee['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Quick Stats</h5>
            </div>
            <div class="card-body">
                <?php
                $total_employees = count($employees);
                $active_employees = count(array_filter($employees, function($e) { return $e['is_active']; }));
                $total_salary = array_sum(array_column($employees, 'basic_salary'));
                $avg_salary = $total_employees > 0 ? $total_salary / $total_employees : 0;
                ?>
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h4><?php echo $total_employees; ?></h4>
                                <small>Total Employees</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h4><?php echo $active_employees; ?></h4>
                                <small>Active</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h4>₱<?php echo number_format($avg_salary, 2); ?></h4>
                                <small>Average Salary</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($view === 'payroll'): ?>
<!-- Payroll Management -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Payroll Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Employee</th>
                                <th>Pay Period</th>
                                <th>Working Days</th>
                                <th>Present Days</th>
                                <th>Gross Salary</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payroll_records as $payroll): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($payroll['employee_code']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($payroll['employee_name']); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M d', strtotime($payroll['pay_period_start'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?>
                                </td>
                                <td><?php echo $payroll['total_working_days']; ?></td>
                                <td><?php echo $payroll['working_days_present']; ?></td>
                                <td>₱<?php echo number_format($payroll['gross_salary'], 2); ?></td>
                                <td>₱<?php echo number_format($payroll['total_deductions'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($payroll['net_salary'], 2); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($payroll['status']) {
                                        case 'draft': $status_class = 'secondary'; break;
                                        case 'approved': $status_class = 'warning'; break;
                                        case 'paid': $status_class = 'success'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($payroll['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-info" onclick="viewPayroll(<?php echo $payroll['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($payroll['status'] === 'draft'): ?>
                                        <button type="button" class="btn btn-outline-warning" onclick="approvePayroll(<?php echo $payroll['id']; ?>)" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php elseif ($payroll['status'] === 'approved'): ?>
                                        <button type="button" class="btn btn-outline-success" onclick="markPaid(<?php echo $payroll['id']; ?>)" title="Mark as Paid">
                                            <i class="fas fa-money-bill"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($payroll['status'] === 'draft' && hasRole(ROLE_ADMIN)): ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="deletePayroll(<?php echo $payroll['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($view === 'reports'): ?>
<!-- Reports -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Payroll Reports</h5>
                <div class="no-print">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3>₱<?php echo number_format(array_sum(array_column($payroll_records, 'gross_salary')), 2); ?></h3>
                                <p>Total Gross Pay</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h3>₱<?php echo number_format(array_sum(array_column($payroll_records, 'total_deductions')), 2); ?></h3>
                                <p>Total Deductions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3>₱<?php echo number_format(array_sum(array_column($payroll_records, 'net_salary')), 2); ?></h3>
                                <p>Total Net Pay</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Payroll Status Distribution</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $status_counts = array_count_values(array_column($payroll_records, 'status'));
                                $total_records = count($payroll_records);
                                ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Draft</span>
                                        <span><?php echo $status_counts['draft'] ?? 0; ?></span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-secondary" style="width: <?php echo $total_records > 0 ? (($status_counts['draft'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Approved</span>
                                        <span><?php echo $status_counts['approved'] ?? 0; ?></span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-warning" style="width: <?php echo $total_records > 0 ? (($status_counts['approved'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Paid</span>
                                        <span><?php echo $status_counts['paid'] ?? 0; ?></span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $total_records > 0 ? (($status_counts['paid'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <a href="?view=payroll&export=excel" class="btn btn-outline-success btn-sm mb-2 w-100">
                                    <i class="fas fa-file-excel"></i> Export to Excel
                                </a>
                                <a href="?view=payroll&export=pdf" class="btn btn-outline-danger btn-sm mb-2 w-100">
                                    <i class="fas fa-file-pdf"></i> Export to PDF
                                </a>
                                <a href="payroll_slip.php" class="btn btn-outline-primary btn-sm mb-2 w-100">
                                    <i class="fas fa-file-invoice"></i> Generate Pay Slips
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Payout Summary -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Employee Payout Summary</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($employee_totals)): ?>
                            <div class="text-center text-muted">No employee payout data available.</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Employee</th>
                                        <th class="text-end">Records</th>
                                        <th class="text-end">Working Days</th>
                                        <th class="text-end">Present Days</th>
                                        <th class="text-end">Total Gross</th>
                                        <th class="text-end">Total Deductions</th>
                                        <th class="text-end">Total Net</th>
                                        <th class="text-end">Avg Net</th>
                                        <th>Last Period</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employee_totals as $tot): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($tot['employee_code']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($tot['employee_name']); ?></small><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($tot['position']); ?></small>
                                        </td>
                                        <td class="text-end"><?php echo (int)$tot['records']; ?></td>
                                        <td class="text-end"><?php echo (int)$tot['total_working_days']; ?></td>
                                        <td class="text-end"><?php echo number_format((float)$tot['present_days'], 1); ?></td>
                                        <td class="text-end">₱<?php echo number_format($tot['gross_total'], 2); ?></td>
                                        <td class="text-end">₱<?php echo number_format($tot['deductions_total'], 2); ?></td>
                                        <td class="text-end"><strong>₱<?php echo number_format($tot['net_total'], 2); ?></strong></td>
                                        <td class="text-end">₱<?php echo number_format($tot['records'] > 0 ? ($tot['net_total'] / $tot['records']) : 0, 2); ?></td>
                                        <td><?php echo !empty($tot['last_period_end']) ? date('M d, Y', strtotime($tot['last_period_end'])) : '-'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_employee">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="employee_code" class="form-label">Employee Code</label>
                        <input type="text" class="form-control" id="employee_code" name="employee_code" value="<?php echo generateEmployeeCode($pdo); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="employee_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="employee_name" name="employee_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" class="form-control" id="position" name="position" required>
                    </div>
                    <div class="mb-3">
                        <label for="date_of_joining" class="form-label">Date of Joining</label>
                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="basic_salary" class="form-label">Basic Salary</label>
                                <input type="number" class="form-control" id="basic_salary" name="basic_salary" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="allowances" class="form-label">Allowances</label>
                                <input type="number" class="form-control" id="allowances" name="allowances" step="0.01" value="0">
                            </div>
                        </div>
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

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_employee">
                <input type="hidden" name="employee_id" id="edit_employee_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_employee_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_employee_name" name="employee_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_position" class="form-label">Position</label>
                        <input type="text" class="form-control" id="edit_position" name="position" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_date_of_joining" class="form-label">Date of Joining</label>
                        <input type="date" class="form-control" id="edit_date_of_joining" name="date_of_joining" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_basic_salary" class="form-label">Basic Salary</label>
                                <input type="number" class="form-control" id="edit_basic_salary" name="basic_salary" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_allowances" class="form-label">Allowances</label>
                                <input type="number" class="form-control" id="edit_allowances" name="allowances" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate Payroll Modal -->
<div class="modal fade" id="generatePayrollModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Generate Payroll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="generate_payroll">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Attendance Required:</strong> Employee must have attendance records for the selected pay period to generate payroll.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payroll_employee_id" class="form-label">Employee</label>
                                <select class="form-select" id="payroll_employee_id" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['employee_code'] . ' - ' . $employee['employee_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="attendance-status"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="pay_period_start" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="pay_period_start" name="pay_period_start" value="<?php echo $current_period['start']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="pay_period_end" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="pay_period_end" name="pay_period_end" value="<?php echo $current_period['end']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dynamic Package System -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0"><strong>Package Salaries</strong></label>
                            <button type="button" class="btn btn-sm btn-success" onclick="addPackage()">
                                <i class="fas fa-plus"></i> Add Package
                            </button>
                        </div>
                        <div id="packages-container">
                            <!-- Packages will be added dynamically here -->
                            <div id="no-packages-message" class="text-muted text-center py-3" style="display: none;">
                                <i class="fas fa-info-circle me-2"></i>
                                No packages added. Click "Add Package" to add custom packages, or leave blank if not needed.
                            </div>
                        </div>
                        <small class="text-muted">Add multiple packages with custom names and amounts (optional - leave blank if not needed)</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bonus_pay" class="form-label">Bonus Pay</label>
                                <input type="number" class="form-control" id="bonus_pay" name="bonus_pay" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="late_penalty" class="form-label">Late Penalty per Day</label>
                                <input type="number" class="form-control" id="late_penalty" name="late_penalty" step="0.01" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cash_advance" class="form-label">Cash Advance</label>
                                <input type="number" class="form-control" id="cash_advance" name="cash_advance" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="uniforms" class="form-label">Uniforms</label>
                                <input type="number" class="form-control" id="uniforms" name="uniforms" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tools" class="form-label">Tools</label>
                                <input type="number" class="form-control" id="tools" name="tools" step="0.01" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="miscellaneous" class="form-label">Miscellaneous</label>
                        <input type="number" class="form-control" id="miscellaneous" name="miscellaneous" step="0.01" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="generatePayrollBtn" onclick="disableSubmitButton(this)">
                        <span class="btn-text">Generate Payroll</span>
                        <span class="btn-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin me-2"></i>Generating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="deleteEmployeeForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_employee">
    <input type="hidden" name="employee_id" id="delete_employee_id">
</form>

<form id="approvePayrollForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="approve_payroll">
    <input type="hidden" name="payroll_id" id="approve_payroll_id">
</form>

<form id="markPaidForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="mark_paid">
    <input type="hidden" name="payroll_id" id="mark_paid_id">
</form>

<form id="deletePayrollForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_payroll">
    <input type="hidden" name="payroll_id" id="delete_payroll_id">
</form>

<script>
function editEmployee(employee) {
    document.getElementById('edit_employee_id').value = employee.id;
    document.getElementById('edit_employee_name').value = employee.employee_name;
    document.getElementById('edit_position').value = employee.position;
    document.getElementById('edit_date_of_joining').value = employee.date_of_joining;
    document.getElementById('edit_basic_salary').value = employee.basic_salary;
    document.getElementById('edit_allowances').value = employee.allowances;
    
    new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
}

function deleteEmployee(employeeId) {
    if (confirm('Are you sure you want to delete this employee?\n\nThis action cannot be undone.')) {
        document.getElementById('delete_employee_id').value = employeeId;
        document.getElementById('deleteEmployeeForm').submit();
    }
}

function generatePayroll(employeeId) {
    document.getElementById('payroll_employee_id').value = employeeId;
    new bootstrap.Modal(document.getElementById('generatePayrollModal')).show();
}

function approvePayroll(payrollId) {
    if (confirm('Are you sure you want to approve this payroll?')) {
        document.getElementById('approve_payroll_id').value = payrollId;
        document.getElementById('approvePayrollForm').submit();
    }
}

function markPaid(payrollId) {
    if (confirm('Are you sure you want to mark this payroll as paid?')) {
        document.getElementById('mark_paid_id').value = payrollId;
        document.getElementById('markPaidForm').submit();
    }
}

function deletePayroll(payrollId) {
    if (confirm('Are you sure you want to delete this payroll?\n\nThis action cannot be undone.')) {
        document.getElementById('delete_payroll_id').value = payrollId;
        document.getElementById('deletePayrollForm').submit();
    }
}

function viewPayroll(payrollId) {
    // Open payroll details in a new window or modal
    window.open('payroll_details.php?id=' + payrollId, '_blank');
}

// Dynamic Package Management
let packageCount = 0;

function addPackage() {
    packageCount++;
    const container = document.getElementById('packages-container');
    const noPackagesMessage = document.getElementById('no-packages-message');
    
    // Hide the "no packages" message
    if (noPackagesMessage) {
        noPackagesMessage.style.display = 'none';
    }
    
    const packageDiv = document.createElement('div');
    packageDiv.className = 'package-item mb-3 p-3 border rounded';
    packageDiv.id = `package-${packageCount}`;
    
    packageDiv.innerHTML = `
        <div class="row">
            <div class="col-md-5">
                <label class="form-label">Package Name</label>
                <input type="text" class="form-control" name="packages[${packageCount}][name]" placeholder="e.g., Solar Installation, Maintenance, etc.">
            </div>
            <div class="col-md-5">
                <label class="form-label">Amount</label>
                <input type="number" class="form-control" name="packages[${packageCount}][amount]" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-danger" onclick="removePackage(${packageCount})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(packageDiv);
}

function removePackage(packageId) {
    const packageElement = document.getElementById(`package-${packageId}`);
    if (packageElement) {
        packageElement.remove();
        
        // Check if no packages remain
        const remainingPackages = document.querySelectorAll('.package-item');
        const noPackagesMessage = document.getElementById('no-packages-message');
        
        if (remainingPackages.length === 0 && noPackagesMessage) {
            noPackagesMessage.style.display = 'block';
        }
    }
}

// Initialize packages container when modal opens
document.getElementById('generatePayrollModal').addEventListener('shown.bs.modal', function() {
    // Clear existing packages
    document.getElementById('packages-container').innerHTML = `
        <div id="no-packages-message" class="text-muted text-center py-3">
            <i class="fas fa-info-circle me-2"></i>
            No packages added. Click "Add Package" to add custom packages, or leave blank if not needed.
        </div>
    `;
    packageCount = 0;
    
    // Don't add default package - let users add only if needed
});

// Check attendance records when employee is selected
document.getElementById('payroll_employee_id').addEventListener('change', function() {
    const employeeId = this.value;
    const startDate = document.getElementById('pay_period_start').value;
    const endDate = document.getElementById('pay_period_end').value;
    
    if (employeeId && startDate && endDate) {
        checkAttendanceRecords(employeeId, startDate, endDate);
    }
});

// Check attendance records when dates change
document.getElementById('pay_period_start').addEventListener('change', function() {
    const employeeId = document.getElementById('payroll_employee_id').value;
    const endDate = document.getElementById('pay_period_end').value;
    
    if (employeeId && this.value && endDate) {
        checkAttendanceRecords(employeeId, this.value, endDate);
    }
});

document.getElementById('pay_period_end').addEventListener('change', function() {
    const employeeId = document.getElementById('payroll_employee_id').value;
    const startDate = document.getElementById('pay_period_start').value;
    
    if (employeeId && startDate && this.value) {
        checkAttendanceRecords(employeeId, startDate, this.value);
    }
});

function checkAttendanceRecords(employeeId, startDate, endDate) {
    // Show loading indicator
    const attendanceStatus = document.getElementById('attendance-status');
    if (!attendanceStatus) {
        // Create attendance status element if it doesn't exist
        const statusDiv = document.createElement('div');
        statusDiv.id = 'attendance-status';
        statusDiv.className = 'mt-3';
        document.getElementById('payroll_employee_id').parentNode.appendChild(statusDiv);
    }
    
    document.getElementById('attendance-status').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-spinner fa-spin me-2"></i>
            Checking attendance records...
        </div>
    `;
    
    // Make AJAX request to check attendance
    fetch('check_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `employee_id=${employeeId}&start_date=${startDate}&end_date=${endDate}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.has_attendance) {
            document.getElementById('attendance-status').innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Attendance Found:</strong> ${data.total_records} records found (${data.present_days} present, ${data.absent_days} absent)
                </div>
            `;
        } else {
            document.getElementById('attendance-status').innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>No Attendance Records:</strong> Employee has no attendance records for this period. 
                    <a href="employee_attendance.php?employee_id=${employeeId}" target="_blank" class="alert-link">
                        Add attendance records first
                    </a>
                </div>
            `;
        }
    })
    .catch(error => {
        document.getElementById('attendance-status').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Error checking attendance records
            </div>
        `;
    });
}

// Disable submit button to prevent double submission
function disableSubmitButton(button) {
    const btnText = button.querySelector('.btn-text');
    const btnLoading = button.querySelector('.btn-loading');
    
    // Disable button and show loading state
    button.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline';
    
    // Submit the form
    button.closest('form').submit();
}
</script>

<style>
.package-item {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6 !important;
}

.package-item:hover {
    background-color: #e9ecef;
}

.package-item .btn-danger {
    margin-top: 25px;
}

/* Sortable table headers */
.table th a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    color: inherit;
    text-decoration: none;
}

.table th a:hover {
    color: #fff !important;
    text-decoration: none;
}

.table th a i {
    margin-left: 5px;
    font-size: 0.8em;
}
</style>

<style>
/* Print styles */
@media print {
	.no-print, .no-print * { display: none !important; }
	.btn, .btn-group, nav, header, footer { display: none !important; }
	.card { box-shadow: none !important; border: 0 !important; }
	.table thead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<?php include 'includes/footer.php'; ?>
