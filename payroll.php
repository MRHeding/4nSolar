<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in and has HR or admin role
if (!isLoggedIn() || !hasPermission(['admin', 'hr'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

// Get current payroll period or create new one
$current_period = null;
try {
    $stmt = $pdo->query("SELECT * FROM payroll_periods WHERE status = 'draft' ORDER BY id DESC LIMIT 1");
    $current_period = $stmt->fetch();
} catch (PDOException $e) {
    $error = "Error fetching payroll period: " . $e->getMessage();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['create_period'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO payroll_periods (period_name, start_date, end_date, total_working_days, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['period_name'],
                $_POST['start_date'],
                $_POST['end_date'],
                $_POST['total_working_days'],
                $_SESSION['user_id']
            ]);
            $success = "Payroll period created successfully!";
            
            // Refresh current period
            $stmt = $pdo->query("SELECT * FROM payroll_periods WHERE status = 'draft' ORDER BY id DESC LIMIT 1");
            $current_period = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error creating payroll period: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['generate_payroll']) && $current_period) {
        try {
            // Get all active employees
            $stmt = $pdo->query("SELECT * FROM employees WHERE employment_status = 'active'");
            $employees = $stmt->fetchAll();
            
            foreach ($employees as $employee) {
                // Check if payroll record already exists
                $check_stmt = $pdo->prepare("SELECT id FROM payroll_records WHERE employee_id = ? AND period_id = ?");
                $check_stmt->execute([$employee['id'], $current_period['id']]);
                
                if (!$check_stmt->fetch()) {
                    // Get attendance data
                    $attendance_stmt = $pdo->prepare("SELECT * FROM employee_attendance WHERE employee_id = ? AND period_id = ?");
                    $attendance_stmt->execute([$employee['id'], $current_period['id']]);
                    $attendance = $attendance_stmt->fetch();
                    
                    $working_days_attended = $attendance ? $attendance['working_days_attended'] : $current_period['total_working_days'];
                    $overtime_hours = $attendance ? $attendance['overtime_hours'] : 0;
                    $late_instances = $attendance ? $attendance['late_instances'] : 0;
                    
                    // Calculate salary components with fixed daily rate
                    $daily_rate = 400.00; // Fixed daily rate of ₱400
                    $basic_salary_amount = $daily_rate * $working_days_attended;
                    $overtime_pay = $overtime_hours * 62.50; // Fixed overtime rate
                    $lates_deduction = $late_instances * 100; // ₱100 per late instance
                    
                    $total_income = $basic_salary_amount + $employee['package_salary'] + $overtime_pay + $employee['allowances'];
                    $total_deductions = $lates_deduction; // Motor and cellphone loans will be 0 by default
                    $net_salary = $total_income - $total_deductions;
                    
                    // Insert payroll record
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO payroll_records 
                        (employee_id, period_id, basic_salary_amount, project_salary_base, overtime_pay, 
                         allowances_amount, total_income, lates_deduction, motor_loan, cellphone_loan,
                         total_deductions, net_salary, overtime_hours, working_days_attended, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insert_stmt->execute([
                        $employee['id'], $current_period['id'], $basic_salary_amount, $employee['package_salary'],
                        $overtime_pay, $employee['allowances'], $total_income, $lates_deduction, 0, 0,
                        $total_deductions, $net_salary, $overtime_hours, $working_days_attended, $_SESSION['user_id']
                    ]);
                }
            }
            $success = "Payroll generated successfully for all employees!";
        } catch (PDOException $e) {
            $error = "Error generating payroll: " . $e->getMessage();
        }
    }
}

// Get payroll records for current period
$payroll_records = [];
if ($current_period) {
    try {
        $stmt = $pdo->prepare("
            SELECT pr.*, e.employee_code, e.first_name, e.last_name, e.position, e.date_of_joining,
                   e.basic_salary, e.package_salary, e.allowances
            FROM payroll_records pr
            JOIN employees e ON pr.employee_id = e.id
            WHERE pr.period_id = ?
            ORDER BY e.employee_code
        ");
        $stmt->execute([$current_period['id']]);
        $payroll_records = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Error fetching payroll records: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-money-check-alt"></i> Payroll Management</h2>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#periodModal">
                        <i class="fas fa-plus"></i> New Period
                    </button>
                    <a href="employees.php" class="btn btn-secondary">
                        <i class="fas fa-users"></i> Manage Employees
                    </a>
                </div>
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

            <!-- Current Period Info -->
            <?php if ($current_period): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Current Payroll Period: <?= htmlspecialchars($current_period['period_name']) ?></h5>
                        <div>
                            <span class="badge bg-info">
                                <?= date('M j, Y', strtotime($current_period['start_date'])) ?> - 
                                <?= date('M j, Y', strtotime($current_period['end_date'])) ?>
                            </span>
                            <span class="badge bg-secondary"><?= $current_period['total_working_days'] ?> Working Days</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?= $current_period['status'] === 'draft' ? 'warning' : 'success' ?>">
                                        <?= ucfirst($current_period['status']) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6 text-end">
                                <?php if (empty($payroll_records)): ?>
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="generate_payroll" class="btn btn-success">
                                            <i class="fas fa-calculator"></i> Generate Payroll
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="payroll_report.php?period_id=<?= $current_period['id'] ?>" class="btn btn-info">
                                    <i class="fas fa-file-pdf"></i> Generate Report
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No active payroll period found. Please create a new payroll period to begin.
                </div>
            <?php endif; ?>

            <!-- Payroll Records Table -->
            <?php if (!empty($payroll_records)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Payroll Records - <?= htmlspecialchars($current_period['period_name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Employee Code</th>
                                        <th>Employee Name</th>
                                        <th>Position</th>
                                        <th>Basic Salary</th>
                                        <th>Total Income</th>
                                        <th>Total Deductions</th>
                                        <th>Net Salary</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payroll_records as $record): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($record['employee_code']) ?></td>
                                            <td><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></td>
                                            <td><?= htmlspecialchars($record['position']) ?></td>
                                            <td><?= formatCurrency($record['basic_salary_amount']) ?></td>
                                            <td><?= formatCurrency($record['total_income']) ?></td>
                                            <td><?= formatCurrency($record['total_deductions']) ?></td>
                                            <td><strong><?= formatCurrency($record['net_salary']) ?></strong></td>
                                            <td>
                                                <span class="badge bg-<?= $record['status'] === 'paid' ? 'success' : ($record['status'] === 'approved' ? 'info' : 'warning') ?>">
                                                    <?= ucfirst($record['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="payroll_detail.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="payroll_slip.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-print"></i> Slip
                                                </a>
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

<!-- New Period Modal -->
<div class="modal fade" id="periodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Payroll Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="period_name" class="form-label">Period Name</label>
                        <input type="text" class="form-control" id="period_name" name="period_name" 
                               placeholder="e.g., September 2025" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="total_working_days" class="form-label">Total Working Days</label>
                        <input type="number" class="form-control" id="total_working_days" name="total_working_days" 
                               min="1" max="31" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_period" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
