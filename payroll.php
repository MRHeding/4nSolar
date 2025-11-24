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

// For Reports view: filter to only show most recent payroll per employee
$filtered_payroll_records = [];
if ($view === 'reports') {
	// Group payroll records by employee and keep only the most recent one
	$latest_by_employee = [];
	foreach ($payroll_records as $rec) {
		$eid = $rec['employee_id'];
		$created_at = strtotime($rec['created_at'] ?? '1970-01-01');
		
		// Keep the most recently created payroll for each employee
		if (!isset($latest_by_employee[$eid]) || $created_at > strtotime($latest_by_employee[$eid]['created_at'] ?? '1970-01-01')) {
			$latest_by_employee[$eid] = $rec;
		}
	}
	$filtered_payroll_records = array_values($latest_by_employee);
} else {
	$filtered_payroll_records = $payroll_records;
}

// Build per-employee totals for Reports view (using filtered records for reports)
$employee_totals = [];
$records_for_summary = ($view === 'reports') ? $filtered_payroll_records : $payroll_records;

foreach ($records_for_summary as $rec) {
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
        return '<i class="fas fa-sort text-gray-400"></i>';
    }
    return $current_order === 'ASC' ? '<i class="fas fa-sort-up text-blue-600"></i>' : '<i class="fas fa-sort-down text-blue-600"></i>';
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center flex-wrap gap-4 py-4 mb-6 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="fas fa-money-bill-wave"></i>
        <span>Payroll Management</span>
    </h1>
    <div class="flex flex-wrap gap-2">
        <div class="flex gap-2 border border-gray-300 dark:border-gray-600 rounded-lg p-1">
            <a href="?view=employees" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $view === 'employees' ? 'bg-solar-blue text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <i class="fas fa-users mr-2"></i> Employees
            </a>
            <a href="?view=payroll" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $view === 'payroll' ? 'bg-solar-blue text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <i class="fas fa-calculator mr-2"></i> Payroll
            </a>
            <a href="?view=reports" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $view === 'reports' ? 'bg-solar-blue text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <i class="fas fa-chart-bar mr-2"></i> Reports
            </a>
        </div>
        <div class="flex gap-2">
            <a href="employee_attendance.php" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <i class="fas fa-calendar-check mr-2"></i> Attendance
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert-success mb-4 flex items-center justify-between" id="successAlert">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <button type="button" class="text-green-700 hover:text-green-900 ml-4" onclick="this.parentElement.style.display='none'">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <script>
        setTimeout(function() {
            const alert = document.getElementById('successAlert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert-error mb-4 flex items-center justify-between">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <button type="button" class="text-red-700 hover:text-red-900 ml-4" onclick="this.parentElement.style.display='none'">
            <i class="fas fa-times"></i>
        </button>
    </div>
<?php endif; ?>

<?php if ($view === 'employees'): ?>
<!-- Employees Management -->
<div class="space-y-6 mb-6">
    <!-- Quick Stats Section -->
        <div class="card">
        <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
            <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                <i class="fas fa-chart-pie mr-2"></i>Quick Stats
            </h5>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php
            $total_employees = count($employees);
            $active_employees = count(array_filter($employees, function($e) { return $e['is_active']; }));
            $total_salary = array_sum(array_column($employees, 'basic_salary'));
            $avg_salary = $total_employees > 0 ? $total_salary / $total_employees : 0;
            ?>
            <div class="bg-blue-600 text-white rounded-lg p-6 text-center">
                <div class="mb-2"><i class="fas fa-users text-2xl opacity-90"></i></div>
                <h4 class="text-3xl font-bold mb-2"><?php echo $total_employees; ?></h4>
                <small class="text-blue-100 text-sm">Total Employees</small>
            </div>
            <div class="bg-green-600 text-white rounded-lg p-6 text-center">
                <div class="mb-2"><i class="fas fa-user-check text-2xl opacity-90"></i></div>
                <h4 class="text-3xl font-bold mb-2"><?php echo $active_employees; ?></h4>
                <small class="text-green-100 text-sm">Active</small>
            </div>
            <div class="bg-cyan-600 text-white rounded-lg p-6 text-center">
                <div class="mb-2"><i class="fas fa-wallet text-2xl opacity-90"></i></div>
                <h4 class="text-3xl font-bold mb-2">₱<?php echo number_format($avg_salary, 2); ?></h4>
                <small class="text-cyan-100 text-sm">Average Salary</small>
            </div>
        </div>
    </div>
    
    <!-- Employee List Section -->
    <div class="card">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
            <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                <i class="fas fa-users mr-2"></i>Employee List
            </h5>
            <button type="button" class="btn-primary" onclick="openModal('addEmployeeModal')">
                <i class="fas fa-plus mr-2"></i> Add Employee
                </button>
            </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-800 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            <a href="<?php echo getSortUrl('employee_code', $sort_by, $sort_order); ?>" class="flex items-center justify-between text-white hover:text-gray-300">
                                <span>Code</span>
                                <span class="ml-2"><?php echo getSortIcon('employee_code', $sort_by, $sort_order); ?></span>
                                    </a>
                                </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            <a href="<?php echo getSortUrl('employee_name', $sort_by, $sort_order); ?>" class="flex items-center justify-between text-white hover:text-gray-300">
                                <span>Name</span>
                                <span class="ml-2"><?php echo getSortIcon('employee_name', $sort_by, $sort_order); ?></span>
                                    </a>
                                </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            <a href="<?php echo getSortUrl('position', $sort_by, $sort_order); ?>" class="flex items-center justify-between text-white hover:text-gray-300">
                                <span>Position</span>
                                <span class="ml-2"><?php echo getSortIcon('position', $sort_by, $sort_order); ?></span>
                                    </a>
                                </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            <a href="<?php echo getSortUrl('date_of_joining', $sort_by, $sort_order); ?>" class="flex items-center justify-between text-white hover:text-gray-300">
                                <span>Date Joined</span>
                                <span class="ml-2"><?php echo getSortIcon('date_of_joining', $sort_by, $sort_order); ?></span>
                                    </a>
                                </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            <a href="<?php echo getSortUrl('basic_salary', $sort_by, $sort_order); ?>" class="flex items-center justify-between text-white hover:text-gray-300">
                                <span>Basic Salary</span>
                                <span class="ml-2"><?php echo getSortIcon('basic_salary', $sort_by, $sort_order); ?></span>
                                    </a>
                                </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            <a href="<?php echo getSortUrl('allowances', $sort_by, $sort_order); ?>" class="flex items-center justify-between text-white hover:text-gray-300">
                                <span>Allowances</span>
                                <span class="ml-2"><?php echo getSortIcon('allowances', $sort_by, $sort_order); ?></span>
                                    </a>
                                </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($employees as $employee): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($employee['employee_code']); ?></span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($employee['employee_name']); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($employee['position']); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                            <?php echo date('M d, Y', strtotime($employee['date_of_joining'])); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                            ₱<?php echo number_format($employee['basic_salary'], 2); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                            ₱<?php echo number_format($employee['allowances'], 2); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $employee['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'; ?>">
                                        <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <button type="button" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                <a href="employee_attendance.php?employee_id=<?php echo $employee['id']; ?>" class="p-2 text-cyan-600 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 rounded transition-colors" title="Attendance">
                                            <i class="fas fa-calendar-check"></i>
                                        </a>
                                <button type="button" class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded transition-colors" onclick="generatePayroll(<?php echo $employee['id']; ?>)" title="Generate Payroll">
                                            <i class="fas fa-calculator"></i>
                                        </button>
                                        <?php if (hasRole(ROLE_ADMIN)): ?>
                                <button type="button" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors" onclick="deleteEmployee(<?php echo $employee['id']; ?>)" title="Delete">
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

<?php elseif ($view === 'payroll'): ?>
<!-- Payroll Management -->
<div class="mb-6">
        <div class="card">
        <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
            <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                <i class="fas fa-calculator mr-2"></i>Payroll Records
            </h5>
            </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-800 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Pay Period</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Working Days</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Present Days</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Gross Salary</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Deductions</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Net Salary</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($payroll_records as $payroll): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-4 py-4">
                            <div class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($payroll['employee_code']); ?></div>
                            <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($payroll['employee_name']); ?></div>
                                </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                    <?php echo date('M d', strtotime($payroll['pay_period_start'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?>
                                </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo $payroll['total_working_days']; ?></td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo $payroll['working_days_present']; ?></td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">₱<?php echo number_format($payroll['gross_salary'], 2); ?></td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">₱<?php echo number_format($payroll['total_deductions'], 2); ?></td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="font-bold text-gray-900 dark:text-white">₱<?php echo number_format($payroll['net_salary'], 2); ?></span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                                    <?php
                                    $status_class = '';
                            $status_bg = '';
                                    switch ($payroll['status']) {
                                case 'draft': 
                                    $status_class = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'; 
                                    break;
                                case 'approved': 
                                    $status_class = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'; 
                                    break;
                                case 'paid': 
                                    $status_class = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'; 
                                    break;
                            }
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo ucfirst($payroll['status']); ?>
                                    </span>
                                </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <button type="button" class="p-2 text-cyan-600 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 rounded transition-colors" onclick="viewPayroll(<?php echo $payroll['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($payroll['status'] === 'draft'): ?>
                                <button type="button" class="p-2 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded transition-colors" onclick="approvePayroll(<?php echo $payroll['id']; ?>)" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php elseif ($payroll['status'] === 'approved'): ?>
                                <button type="button" class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded transition-colors" onclick="markPaid(<?php echo $payroll['id']; ?>)" title="Mark as Paid">
                                            <i class="fas fa-money-bill"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (($payroll['status'] === 'draft' || $payroll['status'] === 'approved') && hasRole(ROLE_ADMIN)): ?>
                                <button type="button" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors" onclick="deletePayroll(<?php echo $payroll['id']; ?>)" title="Delete">
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

<?php elseif ($view === 'reports'): ?>
<!-- Reports -->
<div>
        <div class="card">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
            <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                <i class="fas fa-chart-bar mr-2"></i>Payroll Reports
            </h5>
                <div class="no-print">
                <button type="button" class="px-4 py-2 border border-blue-500 rounded-lg text-sm font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" onclick="window.print()">
                    <i class="fas fa-print mr-2"></i> Print Report
                    </button>
                </div>
            </div>
        <div class="space-y-6">
            <div class="alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                    <strong>Note:</strong> This report shows only the most recently generated payroll for each employee, not historical data.
                </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-600 text-white rounded-lg p-6 text-center">
                    <h3 class="text-3xl font-bold mb-2">₱<?php echo number_format(array_sum(array_column($filtered_payroll_records, 'gross_salary')), 2); ?></h3>
                    <p class="text-blue-100">Total Gross Pay (Latest Payrolls)</p>
                            </div>
                <div class="bg-red-600 text-white rounded-lg p-6 text-center">
                    <h3 class="text-3xl font-bold mb-2">₱<?php echo number_format(array_sum(array_column($filtered_payroll_records, 'total_deductions')), 2); ?></h3>
                    <p class="text-red-100">Total Deductions (Latest Payrolls)</p>
                        </div>
                <div class="bg-green-600 text-white rounded-lg p-6 text-center">
                    <h3 class="text-3xl font-bold mb-2">₱<?php echo number_format(array_sum(array_column($filtered_payroll_records, 'net_salary')), 2); ?></h3>
                    <p class="text-green-100">Total Net Pay (Latest Payrolls)</p>
                    </div>
                </div>
                
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="card">
                    <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                        <h6 class="font-semibold text-gray-800 dark:text-white">Payroll Status Distribution (Latest)</h6>
                            </div>
                    <div class="space-y-4">
                                <?php
                                $status_counts = array_count_values(array_column($filtered_payroll_records, 'status'));
                                $total_records = count($filtered_payroll_records);
                                ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Draft</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo $status_counts['draft'] ?? 0; ?></span>
                                    </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-5">
                                <div class="bg-gray-600 h-5 rounded-full" style="width: <?php echo $total_records > 0 ? (($status_counts['draft'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Approved</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo $status_counts['approved'] ?? 0; ?></span>
                                    </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-5">
                                <div class="bg-yellow-500 h-5 rounded-full" style="width: <?php echo $total_records > 0 ? (($status_counts['approved'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Paid</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo $status_counts['paid'] ?? 0; ?></span>
                                    </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-5">
                                <div class="bg-green-500 h-5 rounded-full" style="width: <?php echo $total_records > 0 ? (($status_counts['paid'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                    <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                        <h6 class="font-semibold text-gray-800 dark:text-white">Quick Actions</h6>
                            </div>
                    <div class="space-y-2">
                        <a href="?view=payroll&export=excel" class="block w-full px-4 py-2 border border-green-500 rounded-lg text-sm font-medium text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors text-center">
                            <i class="fas fa-file-excel mr-2"></i> Export to Excel
                        </a>
                        <a href="?view=payroll&export=pdf" class="block w-full px-4 py-2 border border-red-500 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-center">
                            <i class="fas fa-file-pdf mr-2"></i> Export to PDF
                        </a>
                        </div>
                    </div>
                </div>

                <!-- Employee Payout Summary -->
            <div class="card">
                <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h6 class="font-semibold text-gray-800 dark:text-white flex items-center">
                        <i class="fas fa-users mr-2"></i>Employee Payout Summary (Latest Payroll Only)
                    </h6>
                    </div>
                <div>
                        <?php if (empty($employee_totals)): ?>
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">No employee payout data available.</div>
                        <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-800 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Employee</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Records</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Working Days</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Present Days</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Total Gross</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Total Deductions</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Total Net</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Avg Net</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Last Period</th>
                                    </tr>
                                </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php foreach ($employee_totals as $tot): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($tot['employee_code']); ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($tot['employee_name']); ?></div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500"><?php echo htmlspecialchars($tot['position']); ?></div>
                                        </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white"><?php echo (int)$tot['records']; ?></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white"><?php echo (int)$tot['total_working_days']; ?></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white"><?php echo number_format((float)$tot['present_days'], 1); ?></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white">₱<?php echo number_format($tot['gross_total'], 2); ?></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white">₱<?php echo number_format($tot['deductions_total'], 2); ?></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <span class="font-bold text-gray-900 dark:text-white">₱<?php echo number_format($tot['net_total'], 2); ?></span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-gray-900 dark:text-white">₱<?php echo number_format($tot['records'] > 0 ? ($tot['net_total'] / $tot['records']) : 0, 2); ?></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white"><?php echo !empty($tot['last_period_end']) ? date('M d, Y', strtotime($tot['last_period_end'])) : '-'; ?></td>
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
<?php endif; ?>

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('addEmployeeModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>Add New Employee
                </h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="closeModal('addEmployeeModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_employee">
                <div class="space-y-4">
                    <div>
                        <label for="employee_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Employee Code</label>
                        <input type="text" class="form-input" id="employee_code" name="employee_code" value="<?php echo generateEmployeeCode($pdo); ?>" required>
                    </div>
                    <div>
                        <label for="employee_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                        <input type="text" class="form-input" id="employee_name" name="employee_name" required>
                    </div>
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                        <input type="text" class="form-input" id="position" name="position" required>
                    </div>
                    <div>
                        <label for="date_of_joining" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Joining</label>
                        <input type="date" class="form-input" id="date_of_joining" name="date_of_joining" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="basic_salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Basic Salary</label>
                            <input type="number" class="form-input" id="basic_salary" name="basic_salary" step="0.01" required>
                            </div>
                        <div>
                            <label for="allowances" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Allowances</label>
                            <input type="number" class="form-input" id="allowances" name="allowances" step="0.01" value="0">
                        </div>
                            </div>
                        </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="btn-secondary" onclick="closeModal('addEmployeeModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div id="editEmployeeModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('editEmployeeModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-user-edit mr-2"></i>Edit Employee
                </h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="closeModal('editEmployeeModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_employee">
                <input type="hidden" name="employee_id" id="edit_employee_id">
                <div class="space-y-4">
                    <div>
                        <label for="edit_employee_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                        <input type="text" class="form-input" id="edit_employee_name" name="employee_name" required>
                    </div>
                    <div>
                        <label for="edit_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                        <input type="text" class="form-input" id="edit_position" name="position" required>
                    </div>
                    <div>
                        <label for="edit_date_of_joining" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Joining</label>
                        <input type="date" class="form-input" id="edit_date_of_joining" name="date_of_joining" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_basic_salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Basic Salary</label>
                            <input type="number" class="form-input" id="edit_basic_salary" name="basic_salary" step="0.01" required>
                            </div>
                        <div>
                            <label for="edit_allowances" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Allowances</label>
                            <input type="number" class="form-input" id="edit_allowances" name="allowances" step="0.01">
                        </div>
                            </div>
                        </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="btn-secondary" onclick="closeModal('editEmployeeModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate Payroll Modal -->
<div id="generatePayrollModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('generatePayrollModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-calculator mr-2"></i>Generate Payroll
                </h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="closeModal('generatePayrollModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="generate_payroll">
                <div class="space-y-4">
                    <div class="alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Attendance Required:</strong> Employee must have attendance records for the selected pay period to generate payroll.
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <label for="payroll_employee_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Employee</label>
                                <select class="form-select" id="payroll_employee_id" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['employee_code'] . ' - ' . $employee['employee_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <div id="attendance-status" class="mt-2"></div>
                            </div>
                        <div>
                            <label for="pay_period_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" class="form-input" id="pay_period_start" name="pay_period_start" value="<?php echo $current_period['start']; ?>" required>
                        </div>
                        <div>
                            <label for="pay_period_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                            <input type="date" class="form-input" id="pay_period_end" name="pay_period_end" value="<?php echo $current_period['end']; ?>" required>
                        </div>
                    </div>
                    
                    <!-- Dynamic Package System -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"><strong>Package Salaries</strong></label>
                            <button type="button" class="btn-success text-sm px-3 py-1" onclick="addPackage()">
                                <i class="fas fa-plus mr-1"></i> Add Package
                            </button>
                        </div>
                        <div id="packages-container">
                            <div id="no-packages-message" class="text-gray-500 dark:text-gray-400 text-center py-3" style="display: none;">
                                <i class="fas fa-info-circle mr-2"></i>
                                No packages added. Click "Add Package" to add custom packages, or leave blank if not needed.
                            </div>
                        </div>
                        <small class="text-gray-500 dark:text-gray-400">Add multiple packages with custom names and amounts (optional - leave blank if not needed)</small>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="bonus_pay" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bonus Pay</label>
                            <input type="number" class="form-input" id="bonus_pay" name="bonus_pay" step="0.01" value="0">
                            </div>
                        <div>
                            <label for="late_penalty" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Late Penalty per Day</label>
                            <input type="number" class="form-input" id="late_penalty" name="late_penalty" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="cash_advance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cash Advance</label>
                            <input type="number" class="form-input" id="cash_advance" name="cash_advance" step="0.01" value="0">
                            </div>
                        <div>
                            <label for="uniforms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uniforms</label>
                            <input type="number" class="form-input" id="uniforms" name="uniforms" step="0.01" value="0">
                        </div>
                        <div>
                            <label for="tools" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tools</label>
                            <input type="number" class="form-input" id="tools" name="tools" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <div>
                        <label for="miscellaneous" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Miscellaneous</label>
                        <input type="number" class="form-input" id="miscellaneous" name="miscellaneous" step="0.01" value="0">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="btn-secondary" onclick="closeModal('generatePayrollModal')">Cancel</button>
                    <button type="submit" class="btn-primary" id="generatePayrollBtn" onclick="disableSubmitButton(this)">
                        <span class="btn-text">Generate Payroll</span>
                        <span class="btn-loading hidden">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Generating...
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
// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                closeModal(modal.id);
            }
        });
    }
});

function editEmployee(employee) {
    document.getElementById('edit_employee_id').value = employee.id;
    document.getElementById('edit_employee_name').value = employee.employee_name;
    document.getElementById('edit_position').value = employee.position;
    document.getElementById('edit_date_of_joining').value = employee.date_of_joining;
    document.getElementById('edit_basic_salary').value = employee.basic_salary;
    document.getElementById('edit_allowances').value = employee.allowances;
    
    openModal('editEmployeeModal');
}

function deleteEmployee(employeeId) {
    if (confirm('Are you sure you want to delete this employee?\n\nThis action cannot be undone.')) {
        document.getElementById('delete_employee_id').value = employeeId;
        document.getElementById('deleteEmployeeForm').submit();
    }
}

function generatePayroll(employeeId) {
    document.getElementById('payroll_employee_id').value = employeeId;
    openModal('generatePayrollModal');
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
    window.open('payroll_details.php?id=' + payrollId, '_blank');
}

// Dynamic Package Management
let packageCount = 0;

function addPackage() {
    packageCount++;
    const container = document.getElementById('packages-container');
    const noPackagesMessage = document.getElementById('no-packages-message');
    
    if (noPackagesMessage) {
        noPackagesMessage.style.display = 'none';
    }
    
    const packageDiv = document.createElement('div');
    packageDiv.className = 'package-item mb-3 p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50';
    packageDiv.id = `package-${packageCount}`;
    
    packageDiv.innerHTML = `
        <div class="grid grid-cols-12 gap-4 items-end">
            <div class="col-span-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Package Name</label>
                <input type="text" class="form-input" name="packages[${packageCount}][name]" placeholder="e.g., Solar Installation, Maintenance, etc.">
            </div>
            <div class="col-span-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                <input type="number" class="form-input" name="packages[${packageCount}][amount]" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="col-span-2">
                <button type="button" class="btn-danger w-full text-sm px-3 py-2" onclick="removePackage(${packageCount})">
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
        
        const remainingPackages = document.querySelectorAll('.package-item');
        const noPackagesMessage = document.getElementById('no-packages-message');
        
        if (remainingPackages.length === 0 && noPackagesMessage) {
            noPackagesMessage.style.display = 'block';
        }
    }
}

// Initialize packages container when modal opens
document.getElementById('generatePayrollModal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('fixed')) {
        // Clear existing packages when modal opens
        const packagesContainer = document.getElementById('packages-container');
        if (packagesContainer && !packagesContainer.querySelector('.package-item')) {
            packagesContainer.innerHTML = `
                <div id="no-packages-message" class="text-gray-500 dark:text-gray-400 text-center py-3">
                    <i class="fas fa-info-circle mr-2"></i>
            No packages added. Click "Add Package" to add custom packages, or leave blank if not needed.
        </div>
    `;
    packageCount = 0;
        }
    }
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
    const attendanceStatus = document.getElementById('attendance-status');
    if (!attendanceStatus) {
        const statusDiv = document.createElement('div');
        statusDiv.id = 'attendance-status';
        statusDiv.className = 'mt-3';
        document.getElementById('payroll_employee_id').parentNode.appendChild(statusDiv);
    }
    
    document.getElementById('attendance-status').innerHTML = `
        <div class="alert-info mt-3">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Checking attendance records...
        </div>
    `;
    
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
                <div class="alert-success mt-3">
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>Attendance Found:</strong> ${data.total_records} records found (${data.present_days} present, ${data.absent_days} absent)
                </div>
            `;
        } else {
            document.getElementById('attendance-status').innerHTML = `
                <div class="alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>No Attendance Records:</strong> Employee has no attendance records for this period. 
                    <a href="employee_attendance.php?employee_id=${employeeId}" target="_blank" class="underline ml-1">
                        Add attendance records first
                    </a>
                </div>
            `;
        }
    })
    .catch(error => {
        document.getElementById('attendance-status').innerHTML = `
            <div class="alert-error mt-3">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error checking attendance records
            </div>
        `;
    });
}

// Disable submit button to prevent double submission
function disableSubmitButton(button) {
    const btnText = button.querySelector('.btn-text');
    const btnLoading = button.querySelector('.btn-loading');
    
    button.disabled = true;
    if (btnText) btnText.classList.add('hidden');
    if (btnLoading) btnLoading.classList.remove('hidden');
    
    button.closest('form').submit();
}
</script>

<style>
.package-item {
    transition: background-color 0.2s;
}

.package-item:hover {
    background-color: #f3f4f6;
}

.dark .package-item:hover {
    background-color: #374151;
}

/* Print styles */
@media print {
	.no-print, .no-print * { display: none !important; }
    button, a, nav, header, footer { display: none !important; }
	.card { box-shadow: none !important; border: 0 !important; }
    table thead { 
        -webkit-print-color-adjust: exact; 
        print-color-adjust: exact; 
    }
}
</style>

<?php include 'includes/footer.php'; ?>

<?php include 'includes/footer.php'; ?>
