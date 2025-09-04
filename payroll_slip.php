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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Slip - <?= htmlspecialchars($payroll['employee_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            body { font-size: 12px; }
        }
        .payslip-header {
            border-bottom: 3px solid #0066cc;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }
        .company-logo {
            max-height: 60px;
        }
        .payslip-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .net-salary {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Print Button -->
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Payslip
            </button>
            <a href="payroll_detail.php?id=<?= $payroll['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
        </div>

        <!-- Payslip Content -->
        <div class="payslip-header text-center">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <img src="images/logo.png" alt="4NSOLAR ELECTRICZ" class="company-logo">
                </div>
                <div class="col-md-6">
                    <h2 class="mb-1">4NSOLAR ELECTRICZ</h2>
                    <p class="mb-0">Solar Energy Solutions</p>
                    <small class="text-muted">Payroll Slip</small>
                </div>
                <div class="col-md-3 text-end">
                    <strong>Period: <?= htmlspecialchars($payroll['period_name']) ?></strong><br>
                    <small><?= date('M j, Y', strtotime($payroll['start_date'])) ?> - <?= date('M j, Y', strtotime($payroll['end_date'])) ?></small>
                </div>
            </div>
        </div>

        <!-- Employee Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Employee Information</h5>
                <table class="table table-sm payslip-table">
                    <tr><th>Employee Code:</th><td><?= htmlspecialchars($payroll['employee_code']) ?></td></tr>
                    <tr><th>Employee Name:</th><td><?= htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']) ?></td></tr>
                    <tr><th>Position:</th><td><?= htmlspecialchars($payroll['position']) ?></td></tr>
                    <tr><th>Department:</th><td><?= htmlspecialchars($payroll['department']) ?></td></tr>
                    <tr><th>Date of Joining:</th><td><?= date('M j, Y', strtotime($payroll['date_of_joining'])) ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Salary Information</h5>
                <table class="table table-sm payslip-table">
                    <tr><th>Basic Salary:</th><td><?= formatCurrency($payroll['basic_salary']) ?></td></tr>
                    <tr><th>Package Salary:</th><td><?= formatCurrency($payroll['package_salary']) ?></td></tr>
                    <tr><th>Allowances:</th><td><?= formatCurrency($payroll['allowances']) ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Attendance and Leave Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Attendance Information</h5>
                <table class="table table-sm payslip-table">
                    <tr><th>Total Working Days:</th><td><?= $payroll['total_working_days'] ?> days</td></tr>
                    <tr><th>Working Days Attended:</th><td><?= $payroll['working_days_attended'] ?> days</td></tr>
                    <tr><th>Leaves Taken:</th><td><?= $payroll['total_working_days'] - $payroll['working_days_attended'] ?> days</td></tr>
                    <tr><th>Overtime Hours:</th><td><?= $payroll['overtime_hours'] ?> hours</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Leave Balances</h5>
                <table class="table table-sm payslip-table">
                    <?php foreach ($leave_balances as $leave): ?>
                        <tr>
                            <th><?= ucfirst($leave['leave_type']) ?> Leave:</th>
                            <td><?= $leave['balance_leaves'] ?> / <?= $leave['total_allocated'] ?> days</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Income Details -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Income</h5>
                <table class="table table-sm payslip-table">
                    <thead>
                        <tr><th>Particulars</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Basic Salary (₱400/day × <?= $payroll['working_days_attended'] ?> days)</td><td class="text-end"><?= formatCurrency($payroll['basic_salary_amount']) ?></td></tr>
                        <tr><td>Project Salary Base</td><td class="text-end"><?= formatCurrency($payroll['project_salary_base']) ?></td></tr>
                        <tr><td>Overtime Pay (<?= $payroll['overtime_hours'] ?> hrs × ₱62.5)</td><td class="text-end"><?= formatCurrency($payroll['overtime_pay']) ?></td></tr>
                        <?php if ($payroll['bonus_pay'] > 0): ?>
                            <tr><td>Bonus Pay</td><td class="text-end"><?= formatCurrency($payroll['bonus_pay']) ?></td></tr>
                        <?php endif; ?>
                        <tr><td>Allowances</td><td class="text-end"><?= formatCurrency($payroll['allowances_amount']) ?></td></tr>
                        <tr class="total-row"><td><strong>Total Income</strong></td><td class="text-end"><strong><?= formatCurrency($payroll['total_income']) ?></strong></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Deductions</h5>
                <table class="table table-sm payslip-table">
                    <thead>
                        <tr><th>Particulars</th><th class="text-end">Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($payroll['cash_advance'] > 0): ?>
                            <tr><td>Cash Advance</td><td class="text-end"><?= formatCurrency($payroll['cash_advance']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($payroll['uniforms'] > 0): ?>
                            <tr><td>Uniforms</td><td class="text-end"><?= formatCurrency($payroll['uniforms']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($payroll['tools'] > 0): ?>
                            <tr><td>Tools</td><td class="text-end"><?= formatCurrency($payroll['tools']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($payroll['motor_loan'] > 0): ?>
                            <tr><td>Motor Loan</td><td class="text-end"><?= formatCurrency($payroll['motor_loan']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($payroll['cellphone_loan'] > 0): ?>
                            <tr><td>Cellphone Loan</td><td class="text-end"><?= formatCurrency($payroll['cellphone_loan']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($payroll['lates_deduction'] > 0): ?>
                            <tr><td>Lates</td><td class="text-end"><?= formatCurrency($payroll['lates_deduction']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($payroll['misc_deductions'] > 0): ?>
                            <tr><td>Miscellaneous</td><td class="text-end"><?= formatCurrency($payroll['misc_deductions']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($payroll['total_deductions'] == 0): ?>
                            <tr><td>No Deductions</td><td class="text-end">₱0.00</td></tr>
                        <?php endif; ?>
                        <tr class="total-row"><td><strong>Total Deductions</strong></td><td class="text-end"><strong><?= formatCurrency($payroll['total_deductions']) ?></strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Net Salary -->
        <div class="row mb-4">
            <div class="col-12">
                <table class="table table-sm payslip-table">
                    <tr class="net-salary">
                        <td><h4 class="mb-0">Net Salary</h4></td>
                        <td class="text-end"><h4 class="mb-0"><?= formatCurrency($payroll['net_salary']) ?></h4></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="border-top pt-3 mt-4">
                    <p class="mb-0"><strong>Employee Signature</strong></p>
                    <small class="text-muted">Date: _________________</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border-top pt-3 mt-4">
                    <p class="mb-0"><strong>HR/Authorized Signature</strong></p>
                    <small class="text-muted">Date: <?= date('M j, Y') ?></small>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                Generated on <?= date('F j, Y \a\t g:i A') ?> | 
                This is a computer-generated document and does not require a signature.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
