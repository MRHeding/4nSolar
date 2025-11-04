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
    } elseif ($_POST['action'] === 'delete_all_attendance') {
        try {
            $employee_id = $_POST['employee_id'];
            $month = $_POST['month'];
            
            // Delete all attendance records for the employee in the specified month
            $start_date = $month . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));
            
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?");
            $result = $stmt->execute([$employee_id, $start_date, $end_date]);
            
            if ($result) {
                $deleted_count = $stmt->rowCount();
                $message = "Successfully deleted $deleted_count attendance records for " . date('F Y', strtotime($start_date)) . "!";
                // Refresh the page to show updated records
                header("Location: employee_attendance.php?employee_id=$employee_id&month=$month");
                exit();
            } else {
                $error = 'Failed to delete attendance records.';
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

<div class="flex justify-between items-center flex-wrap gap-4 py-4 mb-6 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="fas fa-calendar-check"></i>
        <span>Employee Attendance</span>
    </h1>
    <div class="flex flex-wrap gap-2">
        <?php if ($employee): ?>
        <div class="flex gap-2">
            <button type="button" class="btn-primary" onclick="openModal('addAttendanceModal')">
                <i class="fas fa-plus mr-2"></i> Add Attendance
            </button>
            <button type="button" class="btn-success" onclick="openModal('bulkAttendanceModal')">
                <i class="fas fa-calendar-plus mr-2"></i> Bulk Entry (15 Days)
            </button>
            <?php if (!empty($attendance_records) && (hasRole(ROLE_ADMIN) || hasRole(ROLE_HR))): ?>
            <button type="button" class="btn-danger" onclick="deleteAllAttendance()" title="Delete all attendance records for this month">
                <i class="fas fa-trash-alt mr-2"></i> Delete All
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <a href="payroll.php" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Payroll
        </a>
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

<!-- Filter Controls -->
<div class="card mb-6">
    <div class="p-6">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <label for="employee_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Employee</label>
                <select class="form-select" id="employee_id" name="employee_id" onchange="this.form.submit()">
                    <option value="">Choose an employee...</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>" <?php echo ($employee_id == $emp['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($emp['employee_code'] . ' - ' . $emp['employee_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-3">
                <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Month</label>
                <input type="month" class="form-input" id="month" name="month" value="<?php echo $month; ?>" onchange="this.form.submit()">
            </div>
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="btn-primary flex-1">
                    <i class="fas fa-search mr-2"></i> View Attendance
                </button>
                <?php if ($employee_id): ?>
                <a href="?employee_id=<?php echo $employee_id; ?>&month=<?php echo $month; ?>&export=csv" class="btn-success px-4 py-2 rounded-lg text-sm font-medium text-white hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i> Export CSV
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($employee): ?>
<!-- Employee Info Card -->
<div class="card mb-6">
    <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-user mr-2"></i>Employee Information
        </h5>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <strong class="text-gray-700 dark:text-gray-300 block mb-1">Employee Code:</strong>
            <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($employee['employee_code']); ?></span>
        </div>
        <div>
            <strong class="text-gray-700 dark:text-gray-300 block mb-1">Name:</strong>
            <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($employee['employee_name']); ?></span>
        </div>
        <div>
            <strong class="text-gray-700 dark:text-gray-300 block mb-1">Position:</strong>
            <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($employee['position']); ?></span>
        </div>
        <div>
            <strong class="text-gray-700 dark:text-gray-300 block mb-1">Date Joined:</strong>
            <span class="text-gray-900 dark:text-white"><?php echo date('M d, Y', strtotime($employee['date_of_joining'])); ?></span>
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

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-green-600 text-white rounded-lg p-4 text-center">
        <h4 class="text-2xl font-bold mb-1"><?php echo $present_days; ?></h4>
        <small class="text-green-100 text-sm">Present Days</small>
    </div>
    <div class="bg-red-600 text-white rounded-lg p-4 text-center">
        <h4 class="text-2xl font-bold mb-1"><?php echo $absent_days; ?></h4>
        <small class="text-red-100 text-sm">Absent Days</small>
    </div>
    <div class="bg-yellow-600 text-white rounded-lg p-4 text-center">
        <h4 class="text-2xl font-bold mb-1"><?php echo $late_days; ?></h4>
        <small class="text-yellow-100 text-sm">Late Days</small>
    </div>
    <div class="bg-cyan-600 text-white rounded-lg p-4 text-center">
        <h4 class="text-2xl font-bold mb-1"><?php echo $half_days; ?></h4>
        <small class="text-cyan-100 text-sm">Half Days</small>
    </div>
    <div class="bg-blue-600 text-white rounded-lg p-4 text-center">
        <h4 class="text-2xl font-bold mb-1"><?php echo number_format($total_hours, 1); ?></h4>
        <small class="text-blue-100 text-sm">Total Hours</small>
    </div>
    <div class="bg-gray-600 text-white rounded-lg p-4 text-center">
        <h4 class="text-2xl font-bold mb-1"><?php echo number_format($overtime_hours, 1); ?></h4>
        <small class="text-gray-100 text-sm">Overtime Hours</small>
    </div>
</div>

<!-- Attendance Records -->
<div class="card">
    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
        <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-calendar-alt mr-2"></i>
            Attendance Records for <?php echo date('F Y', strtotime($month . '-01')); ?>
        </h5>
    </div>
    <div>
        <?php if (empty($attendance_records)): ?>
            <div class="text-center py-12">
                <i class="fas fa-calendar-times text-6xl text-gray-400 dark:text-gray-600 mb-4"></i>
                <h5 class="text-gray-500 dark:text-gray-400 mb-2">No attendance records found</h5>
                <p class="text-gray-400 dark:text-gray-500">Add attendance records using the "Add Attendance" button above.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-800 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Day</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Time In</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Time Out</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Hours Worked</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Overtime</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Notes</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($attendance_records as $record): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                <?php echo date('M d, Y', strtotime($record['attendance_date'])); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                <?php echo date('l', strtotime($record['attendance_date'])); ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="time-edit-container inline-block relative" data-attendance-id="<?php echo $record['id']; ?>" data-field="time_in">
                                    <span class="time-display inline-block px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white min-w-[80px]">
                                        <?php echo $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '-'; ?>
                                    </span>
                                    <div class="time-edit-form hidden absolute top-0 left-0 z-10 bg-white dark:bg-gray-800 border-2 border-blue-500 rounded-lg p-2 shadow-lg min-w-[200px]">
                                        <input type="time" class="form-input mb-2 w-full text-sm" 
                                               value="<?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : ''; ?>"
                                               data-original-value="<?php echo $record['time_in'] ? date('H:i', strtotime($record['time_in'])) : ''; ?>">
                                        <div class="flex gap-2">
                                            <button type="button" class="btn-success text-sm px-3 py-1 flex-1" onclick="saveTimeEdit(this)">
                                                <i class="fas fa-check mr-1"></i> Save
                                            </button>
                                            <button type="button" class="btn-secondary text-sm px-3 py-1 flex-1" onclick="cancelTimeEdit(this)">
                                                <i class="fas fa-times mr-1"></i> Cancel
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors opacity-70 hover:opacity-100 ml-1 edit-time-btn" onclick="editTime(this)" title="Edit Time">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="time-edit-container inline-block relative" data-attendance-id="<?php echo $record['id']; ?>" data-field="time_out">
                                    <span class="time-display inline-block px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white min-w-[80px]">
                                        <?php echo $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '-'; ?>
                                    </span>
                                    <div class="time-edit-form hidden absolute top-0 left-0 z-10 bg-white dark:bg-gray-800 border-2 border-blue-500 rounded-lg p-2 shadow-lg min-w-[200px]">
                                        <input type="time" class="form-input mb-2 w-full text-sm" 
                                               value="<?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : ''; ?>"
                                               data-original-value="<?php echo $record['time_out'] ? date('H:i', strtotime($record['time_out'])) : ''; ?>">
                                        <div class="flex gap-2">
                                            <button type="button" class="btn-success text-sm px-3 py-1 flex-1" onclick="saveTimeEdit(this)">
                                                <i class="fas fa-check mr-1"></i> Save
                                            </button>
                                            <button type="button" class="btn-secondary text-sm px-3 py-1 flex-1" onclick="cancelTimeEdit(this)">
                                                <i class="fas fa-times mr-1"></i> Cancel
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors opacity-70 hover:opacity-100 ml-1 edit-time-btn" onclick="editTime(this)" title="Edit Time">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                <?php echo number_format($record['hours_worked'], 2); ?> hrs
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                                <?php echo number_format($record['overtime_hours'], 2); ?> hrs
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php
                                $status_class = '';
                                $status_bg = '';
                                switch ($record['status']) {
                                    case 'present': 
                                        $status_class = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'; 
                                        break;
                                    case 'absent': 
                                        $status_class = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; 
                                        break;
                                    case 'late': 
                                        $status_class = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'; 
                                        break;
                                    case 'half_day': 
                                        $status_class = 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200'; 
                                        break;
                                    case 'overtime': 
                                        $status_class = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'; 
                                        break;
                                }
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-gray-900 dark:text-white">
                                <?php echo $record['notes'] ? htmlspecialchars($record['notes']) : '-'; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php if (hasRole(ROLE_ADMIN) || hasRole(ROLE_HR)): ?>
                                <button type="button" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors" onclick="deleteAttendance(<?php echo $record['id']; ?>)" title="Delete Record">
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
<div id="addAttendanceModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('addAttendanceModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-calendar-plus mr-2"></i>
                    Add Attendance for <?php echo htmlspecialchars($employee['employee_name']); ?>
                </h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="closeModal('addAttendanceModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_attendance">
                <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                <div class="space-y-4">
                    <div>
                        <label for="attendance_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                        <input type="date" class="form-input" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="time_in" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time In</label>
                            <input type="time" class="form-input" id="time_in" name="time_in">
                        </div>
                        <div>
                            <label for="time_out" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Out</label>
                            <input type="time" class="form-input" id="time_out" name="time_out">
                        </div>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="overtime">Overtime</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="hours_worked" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hours Worked</label>
                            <input type="number" class="form-input" id="hours_worked" name="hours_worked" step="0.25" value="8">
                        </div>
                        <div>
                            <label for="overtime_hours" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Overtime Hours</label>
                            <input type="number" class="form-input" id="overtime_hours" name="overtime_hours" step="0.25" value="0">
                        </div>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea class="form-input" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="btn-secondary" onclick="closeModal('addAttendanceModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Attendance Modal -->
<div id="bulkAttendanceModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('bulkAttendanceModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-[95%] w-full p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-calendar-plus mr-2"></i>
                    Bulk Attendance Entry for <?php echo htmlspecialchars($employee['employee_name']); ?>
                </h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="closeModal('bulkAttendanceModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="" id="bulkAttendanceForm">
                <input type="hidden" name="action" value="bulk_attendance">
                <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                <input type="hidden" name="start_date" id="bulk_start_date">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="bulk_period_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" class="form-input" id="bulk_period_start" value="<?php echo date('Y-m-01'); ?>" onchange="generateBulkDays()">
                        </div>
                        <div>
                            <label for="bulk_period_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                            <input type="date" class="form-input" id="bulk_period_end" value="<?php echo date('Y-m-t'); ?>" onchange="generateBulkDays()">
                        </div>
                    </div>
                    
                    <div class="alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Instructions:</strong> 
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li><strong>Flexible Entry:</strong> Enter time data for present days, leave blank for absent days</li>
                            <li><strong>Auto-Calculation:</strong> Hours calculated automatically based on time in/out</li>
                            <li><strong>Lunch Break:</strong> 1 hour automatically deducted for shifts longer than 5 hours</li>
                            <li><strong>8-Hour Cap:</strong> Regular work hours capped at 8.00 hours (unless "Overtime" status)</li>
                            <li><strong>Smart Validation:</strong> System will auto-correct "Present" records without time data to "Absent"</li>
                        </ul>
                    </div>
                    
                    <div class="overflow-auto border border-gray-300 dark:border-gray-600 rounded-lg" style="max-height: 500px;">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-800 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-white uppercase tracking-wider" style="width: 120px;">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-white uppercase tracking-wider" style="width: 100px;">Day</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-white uppercase tracking-wider" style="width: 100px;">Time In</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-white uppercase tracking-wider" style="width: 100px;">Time Out</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-white uppercase tracking-wider" style="width: 80px;">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-white uppercase tracking-wider" style="width: 80px;">Hours</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-white uppercase tracking-wider" style="width: 80px;">OT Hours</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-white uppercase tracking-wider" style="width: 200px;">Notes</th>
                                </tr>
                            </thead>
                            <tbody id="bulkAttendanceTable" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Days will be generated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="btn-secondary" onclick="closeModal('bulkAttendanceModal')">Cancel</button>
                    <button type="button" class="px-4 py-2 bg-cyan-600 text-white rounded-lg text-sm font-medium hover:bg-cyan-700 transition-colors" onclick="generateBulkDays()">
                        <i class="fas fa-refresh mr-2"></i> Regenerate Days
                    </button>
                    <button type="submit" class="btn-success" onclick="return validateBulkAttendance()">
                        <i class="fas fa-save mr-2"></i> Save All Attendance
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

<!-- Hidden form for delete all operations -->
<form id="deleteAllAttendanceForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_all_attendance">
    <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
    <input type="hidden" name="month" value="<?php echo $month; ?>">
</form>

<script>
// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Initialize bulk days when bulk modal opens
    if (modalId === 'bulkAttendanceModal') {
        setTimeout(() => {
            generateBulkDays();
            checkSundayDuty();
        }, 100);
    }
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

function deleteAttendance(attendanceId) {
    if (confirm('Are you sure you want to delete this attendance record?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteAttendanceId').value = attendanceId;
        document.getElementById('deleteAttendanceForm').submit();
    }
}

function deleteAllAttendance() {
    const employeeName = '<?php echo addslashes($employee['employee_name'] ?? ''); ?>';
    const monthName = '<?php echo date('F Y', strtotime($month . '-01')); ?>';
    const recordCount = <?php echo count($attendance_records); ?>;
    
    const confirmMessage = `Are you sure you want to delete ALL ${recordCount} attendance records for ${employeeName} in ${monthName}?\n\nThis action cannot be undone and will permanently remove all attendance data for this month.\n\nType "DELETE ALL" to confirm:`;
    
    const userInput = prompt(confirmMessage);
    
    if (userInput === 'DELETE ALL') {
        const deleteBtn = event.target;
        const originalText = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';
        
        document.getElementById('deleteAllAttendanceForm').submit();
    } else if (userInput !== null) {
        alert('Deletion cancelled. You must type "DELETE ALL" exactly to confirm.');
    }
}

// Function to round to nearest 0.25 increment
function roundToQuarter(value) {
    return Math.round(value * 4) / 4;
}

// Function to calculate hours and overtime
function calculateAttendanceHours() {
    const timeIn = document.getElementById('time_in')?.value;
    const timeOut = document.getElementById('time_out')?.value;
    const status = document.getElementById('status')?.value;
    
    if (timeIn && timeOut) {
        const timeInDate = new Date('1970-01-01T' + timeIn + ':00');
        const timeOutDate = new Date('1970-01-01T' + timeOut + ':00');
        
        if (timeOutDate > timeInDate) {
            const diffMs = timeOutDate - timeInDate;
            let totalHours = diffMs / (1000 * 60 * 60);
            
            // Account for lunch break (1 hour) if working more than 5 hours
            if (totalHours > 5) {
                totalHours = totalHours - 1;
            }
            
            // Calculate regular hours and overtime automatically
            let hoursWorked = totalHours;
            let overtimeHours = 0;
            
            // If total hours exceed 8.00, split into regular hours (8.00) and overtime
            if (totalHours > 8.00) {
                hoursWorked = 8.00;
                overtimeHours = totalHours - 8.00;
                overtimeHours = roundToQuarter(overtimeHours);
            }
            
            // Round hours worked to 2 decimal places
            hoursWorked = Math.round(hoursWorked * 100) / 100;
            
            // Update the form fields
            const hoursWorkedInput = document.getElementById('hours_worked');
            const overtimeHoursInput = document.getElementById('overtime_hours');
            if (hoursWorkedInput) hoursWorkedInput.value = hoursWorked.toFixed(2);
            if (overtimeHoursInput) overtimeHoursInput.value = overtimeHours.toFixed(2);
            
            // Auto-update status based on time_in and hours worked
            const timeInHour = parseInt(timeIn.split(':')[0]);
            const timeInMinute = parseInt(timeIn.split(':')[1]);
            const timeInMinutes = timeInHour * 60 + timeInMinute;
            const halfDayStart = 13 * 60;
            const halfDayEnd = 17 * 60 + 30;
            
            const statusSelect = document.getElementById('status');
            if (statusSelect) {
                if (totalHours >= 8) {
                    statusSelect.value = 'present';
                } else if (totalHours >= 4 && timeInMinutes >= halfDayStart && timeInMinutes <= halfDayEnd) {
                    statusSelect.value = 'half_day';
                } else if (totalHours >= 4) {
                    statusSelect.value = 'present';
                } else {
                    statusSelect.value = 'absent';
                }
            }
        }
    }
}

// Auto-calculate hours based on time in/out
document.getElementById('time_in')?.addEventListener('change', calculateAttendanceHours);
document.getElementById('time_out')?.addEventListener('change', calculateAttendanceHours);

// Set hours worked based on status
document.getElementById('status')?.addEventListener('change', function() {
    const hoursWorked = document.getElementById('hours_worked');
    const overtimeHours = document.getElementById('overtime_hours');
    const timeIn = document.getElementById('time_in')?.value;
    const timeOut = document.getElementById('time_out')?.value;
    
    switch (this.value) {
        case 'absent':
            if (hoursWorked) hoursWorked.value = '0';
            if (overtimeHours) overtimeHours.value = '0';
            break;
        case 'half_day':
            if (hoursWorked) hoursWorked.value = '4';
            if (overtimeHours) overtimeHours.value = '0';
            if (timeIn && timeOut) {
                calculateAttendanceHours();
            }
            break;
        case 'present':
        case 'late':
            if (hoursWorked) hoursWorked.value = '8';
            if (overtimeHours) overtimeHours.value = '0';
            if (timeIn && timeOut) {
                calculateAttendanceHours();
            }
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
        
        const row = document.createElement('tr');
        const sundayClass = isSunday ? 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500' : '';
        const sundayLabel = isSunday ? ' (Sunday)' : '';
        
        row.className = `hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors ${sundayClass}`;
        row.innerHTML = `
            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700">
                <input type="hidden" name="attendance[${dateStr}][date]" value="${dateStr}">
                ${dateStr}${sundayLabel}
            </td>
            <td class="px-3 py-2 text-sm text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700">${dayName}</td>
            <td class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                <input type="time" class="form-input text-sm w-full" 
                       name="attendance[${dateStr}][time_in]" 
                       onchange="calculateBulkHours('${dateStr}')">
            </td>
            <td class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                <input type="time" class="form-input text-sm w-full" 
                       name="attendance[${dateStr}][time_out]" 
                       onchange="calculateBulkHours('${dateStr}')">
            </td>
            <td class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                <select class="form-select text-sm w-full" 
                        name="attendance[${dateStr}][status]" 
                        onchange="updateBulkStatus('${dateStr}')">
                    <option value="absent">Absent</option>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="half_day">Half Day</option>
                    <option value="overtime">Overtime</option>
                </select>
            </td>
            <td class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                <input type="number" class="form-input text-sm w-full bg-gray-100 dark:bg-gray-700" 
                       name="attendance[${dateStr}][hours_worked]" 
                       step="0.25" value="0" readonly>
            </td>
            <td class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                <input type="number" class="form-input text-sm w-full" 
                       name="attendance[${dateStr}][overtime_hours]" 
                       step="0.25" value="0">
            </td>
            <td class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                <input type="text" class="form-input text-sm w-full" 
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
    const overtimeInput = document.querySelector(`input[name="attendance[${dateStr}][overtime_hours]"]`);
    const statusSelect = document.querySelector(`select[name="attendance[${dateStr}][status]"]`);
    
    if (timeIn && timeOut) {
        const timeInDate = new Date('1970-01-01T' + timeIn + ':00');
        const timeOutDate = new Date('1970-01-01T' + timeOut + ':00');
        
        if (timeOutDate > timeInDate) {
            const diffMs = timeOutDate - timeInDate;
            let totalHours = diffMs / (1000 * 60 * 60);
            
            if (totalHours > 5) {
                totalHours = totalHours - 1;
            }
            
            let hoursWorked = totalHours;
            let overtimeHours = 0;
            
            if (totalHours > 8.00) {
                hoursWorked = 8.00;
                overtimeHours = totalHours - 8.00;
                overtimeHours = roundToQuarter(overtimeHours);
            }
            
            hoursWorked = Math.round(hoursWorked * 100) / 100;
            
            hoursInput.value = hoursWorked.toFixed(2);
            if (overtimeInput) {
                overtimeInput.value = overtimeHours.toFixed(2);
            }
            
            const timeInHour = parseInt(timeIn.split(':')[0]);
            const timeInMinute = parseInt(timeIn.split(':')[1]);
            const timeInMinutes = timeInHour * 60 + timeInMinute;
            const halfDayStart = 13 * 60;
            const halfDayEnd = 17 * 60 + 30;
            
            if (totalHours >= 8) {
                statusSelect.value = 'present';
            } else if (totalHours >= 4 && timeInMinutes >= halfDayStart && timeInMinutes <= halfDayEnd) {
                statusSelect.value = 'half_day';
            } else if (totalHours >= 4) {
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
    const overtimeInput = document.querySelector(`input[name="attendance[${dateStr}][overtime_hours]"]`);
    const timeInInput = document.querySelector(`input[name="attendance[${dateStr}][time_in]"]`);
    const timeOutInput = document.querySelector(`input[name="attendance[${dateStr}][time_out]"]`);
    
    switch (statusSelect.value) {
        case 'absent':
            hoursInput.value = '0';
            if (overtimeInput) overtimeInput.value = '0';
            timeInInput.value = '';
            timeOutInput.value = '';
            break;
        case 'half_day':
            hoursInput.value = '4';
            if (overtimeInput) overtimeInput.value = '0';
            if (!timeInInput.value) timeInInput.value = '08:00';
            if (!timeOutInput.value) timeOutInput.value = '12:00';
            if (timeInInput.value && timeOutInput.value) {
                calculateBulkHours(dateStr);
            }
            break;
        case 'present':
        case 'late':
        case 'overtime':
            hoursInput.value = '8';
            if (overtimeInput) overtimeInput.value = '0';
            if (!timeInInput.value) timeInInput.value = '08:30';
            if (!timeOutInput.value) timeOutInput.value = '17:30';
            if (timeInInput.value && timeOutInput.value) {
                calculateBulkHours(dateStr);
            }
            break;
    }
}

// Add validation before form submission
function validateBulkAttendance() {
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
    
    if (!hasValidData && totalRecords > 0) {
        alert('Please enter at least one attendance record with time data or set status to absent.');
        return false;
    }
    
    if (totalRecords === 0) {
        return true;
    }
    
    if (emptyPresentCount > 0 && emptyPresentCount > totalRecords / 2) {
        const confirmMessage = `You have ${emptyPresentCount} days marked as "Present" but with no time data. These will be saved as "Absent". Continue?`;
        if (!confirm(confirmMessage)) {
            return false;
        }
    }
    
    return true;
}


// Check for Sunday duty and pre-populate
function checkSundayDuty() {
    const employeeId = <?php echo $employee_id ?: 'null'; ?>;
    if (!employeeId) return;
    
    const startDate = document.getElementById('bulk_period_start').value;
    const endDate = document.getElementById('bulk_period_end').value;
    
    if (!startDate || !endDate) return;
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    const currentDate = new Date(start);
    
    while (currentDate <= end) {
        if (currentDate.getDay() === 0) {
            const dateStr = currentDate.toISOString().split('T')[0];
            checkAndPopulateSundayDuty(employeeId, dateStr);
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }
}

// Check if employee was on duty on a specific Sunday and pre-populate
function checkAndPopulateSundayDuty(employeeId, dateStr) {
    fetch(`check_attendance.php?employee_id=${employeeId}&date=${dateStr}`)
        .then(response => response.json())
        .then(data => {
            if (data.wasOnDuty) {
                const timeInInput = document.querySelector(`input[name="attendance[${dateStr}][time_in]"]`);
                const timeOutInput = document.querySelector(`input[name="attendance[${dateStr}][time_out]"]`);
                const statusSelect = document.querySelector(`select[name="attendance[${dateStr}][status]"]`);
                const hoursInput = document.querySelector(`input[name="attendance[${dateStr}][hours_worked]"]`);
                
                if (timeInInput && timeOutInput && statusSelect && hoursInput) {
                    timeInInput.value = '08:00';
                    timeOutInput.value = '17:00';
                    statusSelect.value = 'present';
                    hoursInput.value = '8.00';
                    
                    const row = timeInInput.closest('tr');
                    if (row) {
                        row.classList.add('bg-cyan-50', 'dark:bg-cyan-900/20', 'border-l-4', 'border-cyan-500');
                        const dateCell = row.querySelector('td:first-child');
                        if (dateCell) {
                            const currentText = dateCell.textContent;
                            dateCell.innerHTML = currentText.replace('(Sunday)', '(Sunday - On Duty)');
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
    
    display.classList.add('hidden');
    editForm.classList.remove('hidden');
    button.classList.add('hidden');
    
    input.focus();
    input.select();
}

function cancelTimeEdit(button) {
    const container = button.closest('.time-edit-container');
    const display = container.querySelector('.time-display');
    const editForm = container.querySelector('.time-edit-form');
    const input = editForm.querySelector('input[type="time"]');
    const editBtn = container.querySelector('.edit-time-btn');
    
    input.value = input.getAttribute('data-original-value');
    
    editForm.classList.add('hidden');
    display.classList.remove('hidden');
    editBtn.classList.remove('hidden');
}

function saveTimeEdit(button) {
    const container = button.closest('.time-edit-container');
    const attendanceId = container.getAttribute('data-attendance-id');
    const field = container.getAttribute('data-field');
    const input = container.querySelector('input[type="time"]');
    const newValue = input.value;
    const originalValue = input.getAttribute('data-original-value');
    
    if (newValue === originalValue) {
        cancelTimeEdit(button);
        return;
    }
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
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
            const display = container.querySelector('.time-display');
            if (newValue) {
                const [hours, minutes] = newValue.split(':');
                const hour12 = hours % 12 || 12;
                const ampm = hours >= 12 ? 'PM' : 'AM';
                display.textContent = `${hour12}:${minutes} ${ampm}`;
            } else {
                display.textContent = '-';
            }
            
            input.setAttribute('data-original-value', newValue);
            
            container.querySelector('.time-edit-form').classList.add('hidden');
            display.classList.remove('hidden');
            container.querySelector('.edit-time-btn').classList.remove('hidden');
            
            showNotification('Time updated successfully!', 'success');
            
            recalculateHours(container);
        } else {
            showNotification('Failed to update time: ' + (data.message || 'Unknown error'), 'error');
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check mr-1"></i> Save';
        }
    })
    .catch(error => {
        console.error('Error updating time:', error);
        showNotification('Error updating time: ' + error.message, 'error');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check mr-1"></i> Save';
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
                const timeInDate = new Date('1970-01-01T' + timeIn + ':00');
                const timeOutDate = new Date('1970-01-01T' + timeOut + ':00');
                
                if (timeOutDate > timeInDate) {
                    const diffMs = timeOutDate - timeInDate;
                    let totalHours = diffMs / (1000 * 60 * 60);
                    
                    if (totalHours > 5) {
                        totalHours = totalHours - 1;
                    }
                    
                    let hoursWorked = totalHours;
                    let overtimeHours = 0;
                    
                    if (totalHours > 8.00) {
                        hoursWorked = 8.00;
                        overtimeHours = totalHours - 8.00;
                        overtimeHours = roundToQuarter(overtimeHours);
                    }
                    
                    hoursWorked = Math.round(hoursWorked * 100) / 100;
                    
                    const hoursCell = row.querySelector('td:nth-child(5)');
                    if (hoursCell) {
                        hoursCell.textContent = hoursWorked.toFixed(2) + ' hrs';
                    }
                    
                    const overtimeCell = row.querySelector('td:nth-child(6)');
                    if (overtimeCell) {
                        overtimeCell.textContent = overtimeHours.toFixed(2) + ' hrs';
                    }
                }
            }
        }
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 
                    type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 
                    'bg-blue-100 border-blue-400 text-blue-700';
    
    notification.className = `${bgColor} border px-4 py-3 rounded fixed top-5 right-5 z-50 min-w-[300px] shadow-lg`;
    notification.style.cssText = 'animation: slideInRight 0.3s ease-out;';
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                ${message}
            </div>
            <button type="button" class="ml-4 text-current opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transition = 'opacity 0.3s';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}
</script>

<style>
/* Time editing styles */
.time-edit-container:hover .edit-time-btn {
    opacity: 1 !important;
}

/* Animation for notifications */
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
