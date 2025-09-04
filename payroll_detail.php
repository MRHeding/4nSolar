<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in and has HR or admin role
if (!isLoggedIn() || !hasPermission(['admin', 'hr'])) {
    header('Location: login.php');
    exit;
}

$payroll_id = $_GET['id'] ?? 0;

// Get payroll record details
try {
    $stmt = $pdo->prepare("
        SELECT pr.*, e.employee_code, e.first_name, e.last_name, e.email, e.phone, 
               e.position, e.department, e.date_of_joining, e.basic_salary, e.package_salary, e.allowances,
               pp.period_name, pp.start_date, pp.end_date, pp.total_working_days
        FROM payroll_records pr
        JOIN employees e ON pr.employee_id = e.id
        JOIN payroll_periods pp ON pr.period_id = pp.id
        WHERE pr.id = ?
    ");
    $stmt->execute([$payroll_id]);
    $payroll = $stmt->fetch();
    
    if (!$payroll) {
        header('Location: payroll.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching payroll record: " . $e->getMessage());
}

// Get employee leave balances
try {
    $stmt = $pdo->prepare("
        SELECT leave_type, total_allocated, used_leaves, balance_leaves 
        FROM employee_leave_balances 
        WHERE employee_id = ? AND year = YEAR(CURDATE())
    ");
    $stmt->execute([$payroll['employee_id']]);
    $leave_balances = $stmt->fetchAll();
} catch (PDOException $e) {
    $leave_balances = [];
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['update_payroll'])) {
        try {
            // Use fixed daily rate of ₱400
            $daily_rate = 400.00; // Fixed daily rate
            $recalculated_basic_salary = $daily_rate * $_POST['working_days_attended'];
            
            // Recalculate overtime pay
            $recalculated_overtime_pay = $_POST['overtime_hours'] * 62.50;
            
            // Use recalculated values
            $basic_salary_amount = $recalculated_basic_salary;
            $overtime_pay = $recalculated_overtime_pay;
            
            // Recalculate totals
            $total_income = $basic_salary_amount + $_POST['project_salary_base'] + 
                           $overtime_pay + $_POST['bonus_pay'] + $_POST['allowances_amount'];
            
            $total_deductions = $_POST['cash_advance'] + $_POST['uniforms'] + $_POST['tools'] + 
                               $_POST['motor_loan'] + $_POST['cellphone_loan'] + 
                               $_POST['lates_deduction'] + $_POST['misc_deductions'];
            
            $net_salary = $total_income - $total_deductions;
            
            $stmt = $pdo->prepare("
                UPDATE payroll_records SET 
                basic_salary_amount = ?, project_salary_base = ?, overtime_pay = ?, bonus_pay = ?, 
                allowances_amount = ?, cash_advance = ?, uniforms = ?, tools = ?, motor_loan = ?, 
                cellphone_loan = ?, lates_deduction = ?, misc_deductions = ?, overtime_hours = ?, 
                working_days_attended = ?, total_income = ?, total_deductions = ?, net_salary = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $basic_salary_amount, $_POST['project_salary_base'], $overtime_pay,
                $_POST['bonus_pay'], $_POST['allowances_amount'], $_POST['cash_advance'],
                $_POST['uniforms'], $_POST['tools'], $_POST['motor_loan'], $_POST['cellphone_loan'],
                $_POST['lates_deduction'], $_POST['misc_deductions'], $_POST['overtime_hours'], 
                $_POST['working_days_attended'], $total_income, $total_deductions, $net_salary, $payroll_id
            ]);
            
            $success = "Payroll record updated successfully!";
            
            // Refresh the payroll data
            $stmt = $pdo->prepare("
                SELECT pr.*, e.employee_code, e.first_name, e.last_name, e.email, e.phone, 
                       e.position, e.department, e.date_of_joining, e.basic_salary, e.package_salary, e.allowances,
                       pp.period_name, pp.start_date, pp.end_date, pp.total_working_days
                FROM payroll_records pr
                JOIN employees e ON pr.employee_id = e.id
                JOIN payroll_periods pp ON pr.period_id = pp.id
                WHERE pr.id = ?
            ");
            $stmt->execute([$payroll_id]);
            $payroll = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error updating payroll: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-file-invoice-dollar"></i> Payroll Details</h2>
                <div>
                    <a href="payroll_slip.php?id=<?= $payroll['id'] ?>" class="btn btn-info">
                        <i class="fas fa-print"></i> Print Payslip
                    </a>
                    <a href="payroll.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Payroll
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

            <div class="row">
                <!-- Employee Information -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Employee Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Employee Code:</strong> <?= htmlspecialchars($payroll['employee_code']) ?></p>
                            <p><strong>Name:</strong> <?= htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']) ?></p>
                            <p><strong>Position:</strong> <?= htmlspecialchars($payroll['position']) ?></p>
                            <p><strong>Department:</strong> <?= htmlspecialchars($payroll['department']) ?></p>
                            <p><strong>Date of Joining:</strong> <?= date('M j, Y', strtotime($payroll['date_of_joining'])) ?></p>
                            <p><strong>Basic Salary:</strong> <?= formatCurrency($payroll['basic_salary']) ?></p>
                            <p><strong>Package Salary:</strong> <?= formatCurrency($payroll['package_salary']) ?></p>
                            <p><strong>Allowances:</strong> <?= formatCurrency($payroll['allowances']) ?></p>
                        </div>
                    </div>

                    <!-- Leave Balances -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Leave Balances</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($leave_balances as $leave): ?>
                                <div class="mb-2">
                                    <strong><?= ucfirst($leave['leave_type']) ?> Leave:</strong><br>
                                    <small>Total: <?= $leave['total_allocated'] ?> | Used: <?= $leave['used_leaves'] ?> | Balance: <?= $leave['balance_leaves'] ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Payroll Details -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Payroll Details - <?= htmlspecialchars($payroll['period_name']) ?></h5>
                            <small class="text-muted">
                                Period: <?= date('M j, Y', strtotime($payroll['start_date'])) ?> - <?= date('M j, Y', strtotime($payroll['end_date'])) ?>
                            </small>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Attendance Information</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Total Working Days</label>
                                            <input type="number" class="form-control" value="<?= $payroll['total_working_days'] ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Working Days Attended</label>
                                            <input type="number" class="form-control" name="working_days_attended" id="working_days_attended"
                                                   value="<?= $payroll['working_days_attended'] ?>" min="0" max="<?= $payroll['total_working_days'] ?>" 
                                                   onchange="calculateBasicSalary()">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Overtime Hours</label>
                                            <input type="number" class="form-control" name="overtime_hours" id="overtime_hours"
                                                   value="<?= $payroll['overtime_hours'] ?>" step="0.25" min="0" 
                                                   onchange="calculateOvertimePay()">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-success">Income Components</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Basic Salary (Based on Days Worked)</label>
                                            <input type="number" class="form-control" name="basic_salary_amount" id="basic_salary_amount"
                                                   value="<?= $payroll['basic_salary_amount'] ?>" step="0.01" min="0" readonly>
                                            <small class="text-muted">Auto-calculated: ₱400/day × days attended</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Project Salary Base</label>
                                            <input type="number" class="form-control" name="project_salary_base" 
                                                   value="<?= $payroll['project_salary_base'] ?>" step="0.01" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Overtime Pay (₱62.5/hour)</label>
                                            <input type="number" class="form-control" name="overtime_pay" id="overtime_pay"
                                                   value="<?= $payroll['overtime_pay'] ?>" step="0.01" min="0" readonly>
                                            <small class="text-muted">Auto-calculated: Hours × ₱62.5</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Bonus Pay</label>
                                            <input type="number" class="form-control" name="bonus_pay" 
                                                   value="<?= $payroll['bonus_pay'] ?>" step="0.01" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Allowances</label>
                                            <input type="number" class="form-control" name="allowances_amount" 
                                                   value="<?= $payroll['allowances_amount'] ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-danger">Deductions</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Cash Advance</label>
                                            <input type="number" class="form-control" name="cash_advance" 
                                                   value="<?= $payroll['cash_advance'] ?>" step="0.01" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Uniforms</label>
                                            <input type="number" class="form-control" name="uniforms" 
                                                   value="<?= $payroll['uniforms'] ?>" step="0.01" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Tools</label>
                                            <input type="number" class="form-control" name="tools" 
                                                   value="<?= $payroll['tools'] ?>" step="0.01" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Motor Loan</label>
                                            <input type="number" class="form-control" name="motor_loan" 
                                                   value="<?= $payroll['motor_loan'] ?? 0 ?>" step="0.01" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Cellphone Loan</label>
                                            <input type="number" class="form-control" name="cellphone_loan" 
                                                   value="<?= $payroll['cellphone_loan'] ?? 0 ?>" step="0.01" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Lates Deduction</label>
                                            <input type="number" class="form-control" name="lates_deduction" 
                                                   value="<?= $payroll['lates_deduction'] ?>" step="0.01" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Miscellaneous Deductions</label>
                                            <input type="number" class="form-control" name="misc_deductions" 
                                                   value="<?= $payroll['misc_deductions'] ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-info">Summary</h6>
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <p><strong>Total Income:</strong> <?= formatCurrency($payroll['total_income']) ?></p>
                                                <p><strong>Total Deductions:</strong> <?= formatCurrency($payroll['total_deductions']) ?></p>
                                                <hr>
                                                <h5><strong>Net Salary:</strong> <?= formatCurrency($payroll['net_salary']) ?></h5>
                                                
                                                <p class="mb-1"><small class="text-muted">Status:</small></p>
                                                <span class="badge bg-<?= $payroll['status'] === 'paid' ? 'success' : ($payroll['status'] === 'approved' ? 'info' : 'warning') ?>">
                                                    <?= ucfirst($payroll['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" name="update_payroll" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Payroll
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fixed rates for calculations
const fixedDailyRate = 400.00; // Fixed daily rate of ₱400
const overtimeRate = 62.5;

function calculateBasicSalary() {
    const workingDaysAttended = parseFloat(document.getElementById('working_days_attended').value) || 0;
    const basicSalaryAmount = fixedDailyRate * workingDaysAttended;
    
    document.getElementById('basic_salary_amount').value = basicSalaryAmount.toFixed(2);
    updateTotals();
}

function calculateOvertimePay() {
    const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
    const overtimePay = overtimeHours * overtimeRate;
    
    document.getElementById('overtime_pay').value = overtimePay.toFixed(2);
    updateTotals();
}

function updateTotals() {
    // Get all income values
    const basicSalary = parseFloat(document.querySelector('input[name="basic_salary_amount"]').value) || 0;
    const projectSalary = parseFloat(document.querySelector('input[name="project_salary_base"]').value) || 0;
    const overtimePay = parseFloat(document.querySelector('input[name="overtime_pay"]').value) || 0;
    const bonusPay = parseFloat(document.querySelector('input[name="bonus_pay"]').value) || 0;
    const allowances = parseFloat(document.querySelector('input[name="allowances_amount"]').value) || 0;
    
    // Get all deduction values
    const cashAdvance = parseFloat(document.querySelector('input[name="cash_advance"]').value) || 0;
    const uniforms = parseFloat(document.querySelector('input[name="uniforms"]').value) || 0;
    const tools = parseFloat(document.querySelector('input[name="tools"]').value) || 0;
    const motorLoan = parseFloat(document.querySelector('input[name="motor_loan"]').value) || 0;
    const cellphoneLoan = parseFloat(document.querySelector('input[name="cellphone_loan"]').value) || 0;
    const latesDeduction = parseFloat(document.querySelector('input[name="lates_deduction"]').value) || 0;
    const miscDeductions = parseFloat(document.querySelector('input[name="misc_deductions"]').value) || 0;
    
    // Calculate totals
    const totalIncome = basicSalary + projectSalary + overtimePay + bonusPay + allowances;
    const totalDeductions = cashAdvance + uniforms + tools + motorLoan + cellphoneLoan + latesDeduction + miscDeductions;
    const netSalary = totalIncome - totalDeductions;
    
    // Update the summary card (if you want to show live updates)
    const summaryCard = document.querySelector('.card.bg-light .card-body');
    if (summaryCard) {
        summaryCard.innerHTML = `
            <p><strong>Total Income:</strong> ₱${totalIncome.toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
            <p><strong>Total Deductions:</strong> ₱${totalDeductions.toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
            <hr>
            <h5><strong>Net Salary:</strong> ₱${netSalary.toLocaleString('en-PH', {minimumFractionDigits: 2})}</h5>
            
            <p class="mb-1"><small class="text-muted">Status:</small></p>
            <span class="badge bg-<?= $payroll['status'] === 'paid' ? 'success' : ($payroll['status'] === 'approved' ? 'info' : 'warning') ?>">
                <?= ucfirst($payroll['status']) ?>
            </span>
        `;
    }
}

// Add event listeners to all input fields for real-time calculation
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[type="number"]:not([readonly])');
    inputs.forEach(input => {
        input.addEventListener('input', updateTotals);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
