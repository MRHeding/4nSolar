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

$payroll_id = $_GET['id'] ?? null;
if (!$payroll_id) {
    header('Location: payroll.php');
    exit();
}

$payroll = getPayrollById($pdo, $payroll_id);
if (!$payroll) {
    header('Location: payroll.php');
    exit();
}

$page_title = 'Salary Slip - ' . $payroll['employee_name'];

// Check for print parameter
$is_print = isset($_GET['print']);

if (!$is_print) {
    include 'includes/header.php';
}
?>

<?php if (!$is_print): ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-file-alt me-2"></i>Salary Slip</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-primary" onclick="window.print();">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="payroll.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Payroll
            </a>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
<?php endif; ?>

<div class="card" <?php echo $is_print ? 'style="box-shadow: none; border: 1px solid #000;"' : ''; ?>>
    <div class="card-body">
        <!-- Header -->
        <div class="text-center mb-4" style="border-bottom: 2px solid #000; padding-bottom: 15px;">
            <img src="images/logo.png" alt="4NSOLAR ELECTRICZ" style="height: 60px; margin-bottom: 10px;">
            <h3 style="margin: 0; font-weight: bold; color: #1e40af;">4NSOLAR ELECTRICZ</h3>
            <p style="margin: 0; font-size: 14px;">Management System</p>
        </div>

        <!-- Salary Slip Title -->
        <div class="text-center mb-4">
            <h4 style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">Salary Slip</h4>
        </div>

        <!-- Period Information -->
        <div class="row mb-3">
            <div class="col-12 text-center">
                <strong>Period: <?php echo date('F j', strtotime($payroll['pay_period_start'])) . ' - ' . date('j', strtotime($payroll['pay_period_end'])); ?></strong>
            </div>
        </div>

        <!-- Employee Information Table -->
        <table class="table table-bordered" style="font-size: 14px; margin-bottom: 20px;">
            <tr>
                <td style="background-color: #f8f9fa; font-weight: bold; width: 20%;">Employee Name</td>
                <td style="width: 30%;"><?php echo htmlspecialchars($payroll['employee_name']); ?></td>
                <td style="background-color: #f8f9fa; font-weight: bold; width: 20%;">Date of Joining</td>
                <td style="width: 30%;"><?php echo date('F j, Y', strtotime($payroll['pay_period_start'])); ?></td>
            </tr>
            <tr>
                <td style="background-color: #f8f9fa; font-weight: bold;">Employee Code</td>
                <td><?php echo htmlspecialchars($payroll['employee_code']); ?></td>
                <td style="background-color: #f8f9fa; font-weight: bold;">Total Working Days</td>
                <td><?php echo $payroll['total_working_days']; ?></td>
            </tr>
            <tr>
                <td style="background-color: #f8f9fa; font-weight: bold;">Position</td>
                <td><?php echo htmlspecialchars($payroll['position']); ?></td>
                <td style="background-color: #f8f9fa; font-weight: bold;">No. of Working Days</td>
                <td><?php echo $payroll['working_days_present']; ?></td>
            </tr>
            <tr>
                <td style="background-color: #f8f9fa; font-weight: bold;">Basic Salary</td>
                <td><?php echo formatCurrency($payroll['basic_salary']); ?></td>
                <td style="background-color: #f8f9fa; font-weight: bold;">Leaves</td>
                <td><?php echo $payroll['leaves_taken']; ?></td>
            </tr>
            <tr>
                <td style="background-color: #f8f9fa; font-weight: bold;">Package Total</td>
                <td><?php 
                    $package_total = 0;
                    if (!empty($payroll['packages'])) {
                        foreach ($payroll['packages'] as $package) {
                            $package_total += $package['amount'];
                        }
                    }
                    echo formatCurrency($package_total); 
                ?></td>
                <td style="background-color: #f8f9fa; font-weight: bold;">Leaves Taken</td>
                <td><?php echo $payroll['leaves_taken']; ?></td>
            </tr>
            <tr>
                <td style="background-color: #f8f9fa; font-weight: bold;">Allowances</td>
                <td><?php echo formatCurrency($payroll['allowances']); ?></td>
                <td style="background-color: #f8f9fa; font-weight: bold;">Balance Leaves</td>
                <td><?php echo $payroll['balance_leaves']; ?></td>
            </tr>
        </table>

        <!-- Income and Deductions Table -->
        <div class="row">
            <!-- Income Column -->
            <div class="col-md-6">
                <table class="table table-bordered" style="font-size: 14px;">
                    <thead>
                        <tr style="background-color: #1e40af; color: white;">
                            <th colspan="2" class="text-center">Income</th>
                        </tr>
                        <tr style="background-color: #f8f9fa;">
                            <th style="width: 60%;">Particulars</th>
                            <th style="width: 40%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td class="text-end"><?php echo number_format($payroll['basic_salary'], 2); ?></td>
                        </tr>
                        <?php if (!empty($payroll['packages'])): ?>
                        <?php foreach ($payroll['packages'] as $package): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                            <td class="text-end"><?php echo number_format($package['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td>Package Salary</td>
                            <td class="text-end">0.00</td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Overtime Pay</td>
                            <td class="text-end"><?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Bonus Pay</td>
                            <td class="text-end"><?php echo number_format($payroll['bonus_pay'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Allowances</td>
                            <td class="text-end"><?php echo number_format($payroll['allowances'], 2); ?></td>
                        </tr>
                        <tr style="background-color: #f8f9fa; font-weight: bold;">
                            <td>Total</td>
                            <td class="text-end"><?php echo number_format($payroll['gross_salary'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Deductions Column -->
            <div class="col-md-6">
                <table class="table table-bordered" style="font-size: 14px;">
                    <thead>
                        <tr style="background-color: #dc3545; color: white;">
                            <th colspan="2" class="text-center">Deductions</th>
                        </tr>
                        <tr style="background-color: #f8f9fa;">
                            <th style="width: 60%;">Particulars</th>
                            <th style="width: 40%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Cash Advance</td>
                            <td class="text-end"><?php echo number_format($payroll['cash_advance'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Uniforms</td>
                            <td class="text-end"><?php echo number_format($payroll['uniforms'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Tools</td>
                            <td class="text-end"><?php echo number_format($payroll['tools'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Lates</td>
                            <td class="text-end"><?php echo number_format($payroll['lates'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Miscellaneous</td>
                            <td class="text-end"><?php echo number_format($payroll['miscellaneous'], 2); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="text-end">0</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="text-end">0</td>
                        </tr>
                        <tr style="background-color: #f8f9fa; font-weight: bold;">
                            <td>Total</td>
                            <td class="text-end"><?php echo number_format($payroll['total_deductions'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Table -->
        <table class="table table-bordered" style="font-size: 14px; margin-top: 20px;">
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold; width: 70%;">Total Income</td>
                <td class="text-end" style="font-weight: bold;"><?php echo CURRENCY_SYMBOL . ' ' . number_format($payroll['gross_salary'], 2); ?></td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold;">Less Deductions</td>
                <td class="text-end" style="font-weight: bold;"><?php echo CURRENCY_SYMBOL . ' ' . number_format($payroll['total_deductions'], 2); ?></td>
            </tr>
            <tr style="background-color: #198754; color: white;">
                <td style="font-weight: bold; font-size: 16px;">Net Salary</td>
                <td class="text-end" style="font-weight: bold; font-size: 16px;"><?php echo CURRENCY_SYMBOL . ' ' . number_format($payroll['net_salary'], 2); ?></td>
            </tr>
        </table>

        <!-- Signatures -->
        <div class="row mt-5">
            <div class="col-md-6 text-center">
                <div style="border-top: 1px solid #000; margin-top: 60px; padding-top: 10px;">
                    <strong><?php echo htmlspecialchars($payroll['employee_name']); ?></strong><br>
                    <small>Employee Signature</small>
                </div>
            </div>
            <div class="col-md-6 text-center">
                <div style="border-top: 1px solid #000; margin-top: 60px; padding-top: 10px;">
                    <strong>Novie G. Moharada</strong><br>
                    <small>Managers Approval Signature</small>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4" style="font-size: 12px; color: #666;">
            <p>Generated on <?php echo date('F j, Y \a\t g:i A'); ?> | Status: <?php echo ucfirst($payroll['status']); ?></p>
            <?php if ($payroll['approved_at']): ?>
            <p>Approved on <?php echo date('F j, Y \a\t g:i A', strtotime($payroll['approved_at'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$is_print): ?>
    </div>
</div>

<style>
@media print {
    .btn-toolbar, .card-header, nav, .sidebar, .border-bottom { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #000 !important; box-shadow: none !important; }
    body { font-size: 12px !important; }
    .table { font-size: 11px !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
<?php else: ?>

<style>
body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border: 1px solid #000; padding: 8px; }
.text-center { text-align: center; }
.text-end { text-align: right; }
.card { border: 1px solid #000; padding: 20px; }
</style>

<script>
window.onload = function() {
    window.print();
    window.onafterprint = function() {
        window.close();
    };
};
</script>

<?php endif; ?>
