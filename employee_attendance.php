<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in and has HR or admin role
if (!isLoggedIn() || !hasPermission(['admin', 'hr'])) {
    header('Location: login.php');
    exit;
}

$employee_id = $_GET['id'] ?? 0;

// Get employee details
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header('Location: employees.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching employee: " . $e->getMessage());
}

// Get current payroll period
try {
    $stmt = $pdo->query("SELECT * FROM payroll_periods WHERE status = 'draft' ORDER BY id DESC LIMIT 1");
    $current_period = $stmt->fetch();
} catch (PDOException $e) {
    $current_period = null;
}

// Handle form submissions
if ($_POST && $current_period) {
    if (isset($_POST['update_attendance'])) {
        try {
            // Check if attendance record exists
            $check_stmt = $pdo->prepare("SELECT id FROM employee_attendance WHERE employee_id = ? AND period_id = ?");
            $check_stmt->execute([$employee_id, $current_period['id']]);
            $existing = $check_stmt->fetch();
            
            if ($existing) {
                // Update existing record
                $stmt = $pdo->prepare("
                    UPDATE employee_attendance SET 
                    working_days_attended = ?, leaves_taken = ?, overtime_hours = ?, late_instances = ?, notes = ?
                    WHERE employee_id = ? AND period_id = ?
                ");
                $stmt->execute([
                    $_POST['working_days_attended'], $_POST['leaves_taken'], 
                    $_POST['overtime_hours'], $_POST['late_instances'], $_POST['notes'],
                    $employee_id, $current_period['id']
                ]);
            } else {
                // Insert new record
                $stmt = $pdo->prepare("
                    INSERT INTO employee_attendance 
                    (employee_id, period_id, working_days_attended, leaves_taken, overtime_hours, late_instances, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $employee_id, $current_period['id'], $_POST['working_days_attended'], 
                    $_POST['leaves_taken'], $_POST['overtime_hours'], $_POST['late_instances'], $_POST['notes']
                ]);
            }
            
            $success = "Attendance updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating attendance: " . $e->getMessage();
        }
    }
}

// Get attendance record for current period
$attendance = null;
if ($current_period) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM employee_attendance WHERE employee_id = ? AND period_id = ?");
        $stmt->execute([$employee_id, $current_period['id']]);
        $attendance = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error fetching attendance: " . $e->getMessage();
    }
}

// Get employee leave balances
try {
    $stmt = $pdo->prepare("
        SELECT leave_type, total_allocated, used_leaves, balance_leaves 
        FROM employee_leave_balances 
        WHERE employee_id = ? AND year = YEAR(CURDATE())
    ");
    $stmt->execute([$employee_id]);
    $leave_balances = $stmt->fetchAll();
} catch (PDOException $e) {
    $leave_balances = [];
}

// Get attendance history
try {
    $stmt = $pdo->prepare("
        SELECT ea.*, pp.period_name, pp.start_date, pp.end_date, pp.total_working_days
        FROM employee_attendance ea
        JOIN payroll_periods pp ON ea.period_id = pp.id
        WHERE ea.employee_id = ?
        ORDER BY pp.start_date DESC
    ");
    $stmt->execute([$employee_id]);
    $attendance_history = $stmt->fetchAll();
} catch (PDOException $e) {
    $attendance_history = [];
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar-check"></i> Employee Attendance</h2>
                <a href="employees.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Employees
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Employee Information -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Employee Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Employee Code:</strong> <?= htmlspecialchars($employee['employee_code']) ?></p>
                            <p><strong>Name:</strong> <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></p>
                            <p><strong>Position:</strong> <?= htmlspecialchars($employee['position']) ?></p>
                            <p><strong>Department:</strong> <?= htmlspecialchars($employee['department']) ?></p>
                            <p><strong>Date of Joining:</strong> <?= date('M j, Y', strtotime($employee['date_of_joining'])) ?></p>
                        </div>
                    </div>

                    <!-- Leave Balances -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Leave Balances (<?= date('Y') ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($leave_balances as $leave): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= ucfirst($leave['leave_type']) ?> Leave</strong>
                                        <span><?= $leave['balance_leaves'] ?>/<?= $leave['total_allocated'] ?></span>
                                    </div>
                                    <div class="progress mt-1" style="height: 8px;">
                                        <div class="progress-bar" style="width: <?= ($leave['balance_leaves'] / $leave['total_allocated']) * 100 ?>%"></div>
                                    </div>
                                    <small class="text-muted">Used: <?= $leave['used_leaves'] ?> days</small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Attendance Form -->
                <div class="col-lg-8">
                    <?php if ($current_period): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Current Period Attendance - <?= htmlspecialchars($current_period['period_name']) ?></h5>
                                <small class="text-muted">
                                    <?= date('M j, Y', strtotime($current_period['start_date'])) ?> - 
                                    <?= date('M j, Y', strtotime($current_period['end_date'])) ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Total Working Days</label>
                                                <input type="number" class="form-control" value="<?= $current_period['total_working_days'] ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Working Days Attended</label>
                                                <input type="number" class="form-control" name="working_days_attended" 
                                                       value="<?= $attendance ? $attendance['working_days_attended'] : $current_period['total_working_days'] ?>" 
                                                       min="0" max="<?= $current_period['total_working_days'] ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Leaves Taken</label>
                                                <input type="number" class="form-control" name="leaves_taken" 
                                                       value="<?= $attendance ? $attendance['leaves_taken'] : 0 ?>" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Late Instances</label>
                                                <input type="number" class="form-control" name="late_instances" 
                                                       value="<?= $attendance ? $attendance['late_instances'] : 0 ?>" min="0">
                                                <small class="text-muted">₱100 deduction per late instance</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Overtime Hours</label>
                                        <input type="number" class="form-control" name="overtime_hours" 
                                               value="<?= $attendance ? $attendance['overtime_hours'] : 0 ?>" 
                                               step="0.25" min="0">
                                        <small class="text-muted">Overtime rate: ₱62.5 per hour</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="3"><?= $attendance ? htmlspecialchars($attendance['notes']) : '' ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_attendance" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Attendance
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            No active payroll period found. Please create a new payroll period to manage attendance.
                        </div>
                    <?php endif; ?>

                    <!-- Attendance History -->
                    <?php if (!empty($attendance_history)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Attendance History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Period</th>
                                                <th>Total Days</th>
                                                <th>Days Attended</th>
                                                <th>Leaves</th>
                                                <th>Overtime</th>
                                                <th>Lates</th>
                                                <th>Attendance %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendance_history as $record): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($record['period_name']) ?></strong><br>
                                                        <small class="text-muted">
                                                            <?= date('M j', strtotime($record['start_date'])) ?> - 
                                                            <?= date('M j, Y', strtotime($record['end_date'])) ?>
                                                        </small>
                                                    </td>
                                                    <td><?= $record['total_working_days'] ?></td>
                                                    <td><?= $record['working_days_attended'] ?></td>
                                                    <td><?= $record['leaves_taken'] ?></td>
                                                    <td><?= $record['overtime_hours'] ?>h</td>
                                                    <td><?= $record['late_instances'] ?></td>
                                                    <td>
                                                        <?php 
                                                        $attendance_percentage = ($record['working_days_attended'] / $record['total_working_days']) * 100;
                                                        $badge_class = $attendance_percentage >= 95 ? 'success' : ($attendance_percentage >= 90 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="badge bg-<?= $badge_class ?>">
                                                            <?= number_format($attendance_percentage, 1) ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
