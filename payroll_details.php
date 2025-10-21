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

$page_title = 'Payroll Details';

// Get payroll ID
$payroll_id = $_GET['id'] ?? null;

if (!$payroll_id) {
    header('Location: payroll.php');
    exit();
}

// Get payroll details
$payroll = getPayrollById($pdo, $payroll_id);

if (!$payroll) {
    header('Location: payroll.php');
    exit();
}

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'approve_payroll':
            try {
                if (approvePayroll($pdo, $payroll_id, $_SESSION['user_id'])) {
                    $message = 'Payroll approved successfully!';
                    // Refresh payroll data
                    $payroll = getPayrollById($pdo, $payroll_id);
                } else {
                    $error = 'Failed to approve payroll.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
            
        case 'mark_paid':
            try {
                if (markPayrollAsPaid($pdo, $payroll_id)) {
                    $message = 'Payroll marked as paid!';
                    // Refresh payroll data
                    $payroll = getPayrollById($pdo, $payroll_id);
                } else {
                    $error = 'Failed to mark payroll as paid.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
            
        case 'update_payroll':
            try {
                // Update payroll record
                $sql = "UPDATE payroll SET 
                        basic_salary = ?, 
                        allowances = ?, 
                        overtime_pay = ?, 
                        bonus_pay = ?,
                        cash_advance = ?, 
                        uniforms = ?, 
                        tools = ?, 
                        lates = ?, 
                        miscellaneous = ?,
                        gross_salary = ?, 
                        total_deductions = ?, 
                        net_salary = ?,
                        notes = ?
                        WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $_POST['basic_salary'],
                    $_POST['allowances'],
                    $_POST['overtime_pay'],
                    $_POST['bonus_pay'],
                    $_POST['cash_advance'],
                    $_POST['uniforms'],
                    $_POST['tools'],
                    $_POST['lates'],
                    $_POST['miscellaneous'],
                    $_POST['gross_salary'],
                    $_POST['total_deductions'],
                    $_POST['net_salary'],
                    $_POST['notes'],
                    $payroll_id
                ]);
                
                if ($result) {
                    $message = 'Payroll updated successfully!';
                    // Refresh payroll data
                    $payroll = getPayrollById($pdo, $payroll_id);
                } else {
                    $error = 'Failed to update payroll.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
            break;
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-calculator me-2"></i>Payroll Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="payroll.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Payroll
            </a>
            <a href="payroll_slip.php?id=<?php echo $payroll_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-invoice"></i> View Pay Slip
            </a>
        </div>
        <div class="btn-group">
            <?php if ($payroll['status'] === 'draft'): ?>
            <button type="button" class="btn btn-warning" onclick="approvePayroll()">
                <i class="fas fa-check"></i> Approve
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editPayrollModal">
                <i class="fas fa-edit"></i> Edit
            </button>
            <?php elseif ($payroll['status'] === 'approved'): ?>
            <button type="button" class="btn btn-success" onclick="markPaid()">
                <i class="fas fa-money-bill"></i> Mark as Paid
            </button>
            <?php endif; ?>
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

<!-- Payroll Information -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Payroll Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Employee Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Employee Code:</strong></td>
                                <td><?php echo htmlspecialchars($payroll['employee_code']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($payroll['employee_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Position:</strong></td>
                                <td><?php echo htmlspecialchars($payroll['position']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Pay Period</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Start Date:</strong></td>
                                <td><?php echo date('M d, Y', strtotime($payroll['pay_period_start'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>End Date:</strong></td>
                                <td><?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Working Days:</strong></td>
                                <td><?php echo $payroll['total_working_days']; ?> days</td>
                            </tr>
                            <tr>
                                <td><strong>Present Days:</strong></td>
                                <td><?php echo $payroll['working_days_present']; ?> days</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Status</h6>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        switch ($payroll['status']) {
                            case 'draft':
                                $status_class = 'secondary';
                                $status_text = 'Draft';
                                break;
                            case 'approved':
                                $status_class = 'warning';
                                $status_text = 'Approved';
                                break;
                            case 'paid':
                                $status_class = 'success';
                                $status_text = 'Paid';
                                break;
                        }
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?> fs-6"><?php echo $status_text; ?></span>
                        
                        <?php if ($payroll['approved_at']): ?>
                        <br><small class="text-muted">Approved on: <?php echo date('M d, Y g:i A', strtotime($payroll['approved_at'])); ?></small>
                        <?php endif; ?>
                        
                        <?php if ($payroll['paid_at']): ?>
                        <br><small class="text-muted">Paid on: <?php echo date('M d, Y g:i A', strtotime($payroll['paid_at'])); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Generated</h6>
                        <p class="mb-0"><?php echo date('M d, Y g:i A', strtotime($payroll['created_at'])); ?></p>
                        <?php if ($payroll['notes']): ?>
                        <br><strong>Notes:</strong><br>
                        <p class="text-muted"><?php echo htmlspecialchars($payroll['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="payroll_slip.php?id=<?php echo $payroll_id; ?>&print=1" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="fas fa-print"></i> Print Pay Slip
                    </a>
                    <a href="payroll_slip.php?id=<?php echo $payroll_id; ?>&pdf=1" class="btn btn-outline-danger btn-sm" target="_blank">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                    <a href="employee_attendance.php?employee_id=<?php echo $payroll['employee_id']; ?>&month=<?php echo date('Y-m', strtotime($payroll['pay_period_start'])); ?>" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-calendar-check"></i> View Attendance
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Salary Breakdown -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 text-success"><i class="fas fa-plus-circle me-2"></i>Earnings</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Basic Salary</td>
                        <td class="text-end">₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                    </tr>
                    <?php if (!empty($payroll['packages'])): ?>
                        <?php foreach ($payroll['packages'] as $package): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                            <td class="text-end">₱<?php echo number_format($package['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($payroll['allowances'] > 0): ?>
                    <tr>
                        <td>Allowances</td>
                        <td class="text-end">₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['overtime_pay'] > 0): ?>
                    <tr>
                        <td>Overtime Pay</td>
                        <td class="text-end">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['bonus_pay'] > 0): ?>
                    <tr>
                        <td>Bonus Pay</td>
                        <td class="text-end">₱<?php echo number_format($payroll['bonus_pay'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="table-success">
                        <td><strong>Total Earnings</strong></td>
                        <td class="text-end"><strong>₱<?php echo number_format($payroll['gross_salary'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 text-danger"><i class="fas fa-minus-circle me-2"></i>Deductions</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <?php if ($payroll['cash_advance'] > 0): ?>
                    <tr>
                        <td>Cash Advance</td>
                        <td class="text-end">₱<?php echo number_format($payroll['cash_advance'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['uniforms'] > 0): ?>
                    <tr>
                        <td>Uniforms</td>
                        <td class="text-end">₱<?php echo number_format($payroll['uniforms'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['tools'] > 0): ?>
                    <tr>
                        <td>Tools</td>
                        <td class="text-end">₱<?php echo number_format($payroll['tools'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['lates'] > 0): ?>
                    <tr>
                        <td>Late Penalties</td>
                        <td class="text-end">₱<?php echo number_format($payroll['lates'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['miscellaneous'] > 0): ?>
                    <tr>
                        <td>Miscellaneous</td>
                        <td class="text-end">₱<?php echo number_format($payroll['miscellaneous'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="table-danger">
                        <td><strong>Total Deductions</strong></td>
                        <td class="text-end"><strong>₱<?php echo number_format($payroll['total_deductions'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Net Pay Summary -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4>NET PAY</h4>
                <h2>₱<?php echo number_format($payroll['net_salary'], 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payroll Modal -->
<div class="modal fade" id="editPayrollModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Payroll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_payroll">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success">Earnings</h6>
                            <div class="mb-3">
                                <label for="basic_salary" class="form-label">Basic Salary</label>
                                <input type="number" class="form-control" id="basic_salary" name="basic_salary" step="0.01" value="<?php echo $payroll['basic_salary']; ?>" onchange="calculateTotals()">
                            </div>
                            <div class="mb-3">
                                <label for="allowances" class="form-label">Allowances</label>
                                <input type="number" class="form-control" id="allowances" name="allowances" step="0.01" value="<?php echo $payroll['allowances']; ?>" onchange="calculateTotals()">
                            </div>
                            <div class="mb-3">
                                <label for="overtime_pay" class="form-label">Overtime Pay</label>
                                <input type="number" class="form-control" id="overtime_pay" name="overtime_pay" step="0.01" value="<?php echo $payroll['overtime_pay']; ?>" onchange="calculateTotals()">
                            </div>
                            <div class="mb-3">
                                <label for="bonus_pay" class="form-label">Bonus Pay</label>
                                <input type="number" class="form-control" id="bonus_pay" name="bonus_pay" step="0.01" value="<?php echo $payroll['bonus_pay']; ?>" onchange="calculateTotals()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger">Deductions</h6>
                            <div class="mb-3">
                                <label for="cash_advance" class="form-label">Cash Advance</label>
                                <input type="number" class="form-control" id="cash_advance" name="cash_advance" step="0.01" value="<?php echo $payroll['cash_advance']; ?>" onchange="calculateTotals()">
                            </div>
                            <div class="mb-3">
                                <label for="uniforms" class="form-label">Uniforms</label>
                                <input type="number" class="form-control" id="uniforms" name="uniforms" step="0.01" value="<?php echo $payroll['uniforms']; ?>" onchange="calculateTotals()">
                            </div>
                            <div class="mb-3">
                                <label for="tools" class="form-label">Tools</label>
                                <input type="number" class="form-control" id="tools" name="tools" step="0.01" value="<?php echo $payroll['tools']; ?>" onchange="calculateTotals()">
                            </div>
                            <div class="mb-3">
                                <label for="lates" class="form-label">Late Penalties</label>
                                <input type="number" class="form-control" id="lates" name="lates" step="0.01" value="<?php echo $payroll['lates']; ?>" onchange="calculateTotals()">
                            </div>
                            <div class="mb-3">
                                <label for="miscellaneous" class="form-label">Miscellaneous</label>
                                <input type="number" class="form-control" id="miscellaneous" name="miscellaneous" step="0.01" value="<?php echo $payroll['miscellaneous']; ?>" onchange="calculateTotals()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gross_salary" class="form-label">Gross Salary</label>
                                <input type="number" class="form-control" id="gross_salary" name="gross_salary" step="0.01" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="total_deductions" class="form-label">Total Deductions</label>
                                <input type="number" class="form-control" id="total_deductions" name="total_deductions" step="0.01" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="net_salary" class="form-label">Net Salary</label>
                        <input type="number" class="form-control" id="net_salary" name="net_salary" step="0.01" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($payroll['notes']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Payroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="approvePayrollForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="approve_payroll">
</form>

<form id="markPaidForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="mark_paid">
</form>

<script>
function approvePayroll() {
    if (confirm('Are you sure you want to approve this payroll?')) {
        document.getElementById('approvePayrollForm').submit();
    }
}

function markPaid() {
    if (confirm('Are you sure you want to mark this payroll as paid?')) {
        document.getElementById('markPaidForm').submit();
    }
}

function calculateTotals() {
    // Calculate gross salary
    const basicSalary = parseFloat(document.getElementById('basic_salary').value) || 0;
    const allowances = parseFloat(document.getElementById('allowances').value) || 0;
    const overtimePay = parseFloat(document.getElementById('overtime_pay').value) || 0;
    const bonusPay = parseFloat(document.getElementById('bonus_pay').value) || 0;
    
    const grossSalary = basicSalary + allowances + overtimePay + bonusPay;
    document.getElementById('gross_salary').value = grossSalary.toFixed(2);
    
    // Calculate total deductions
    const cashAdvance = parseFloat(document.getElementById('cash_advance').value) || 0;
    const uniforms = parseFloat(document.getElementById('uniforms').value) || 0;
    const tools = parseFloat(document.getElementById('tools').value) || 0;
    const lates = parseFloat(document.getElementById('lates').value) || 0;
    const miscellaneous = parseFloat(document.getElementById('miscellaneous').value) || 0;
    
    const totalDeductions = cashAdvance + uniforms + tools + lates + miscellaneous;
    document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
    
    // Calculate net salary
    const netSalary = grossSalary - totalDeductions;
    document.getElementById('net_salary').value = netSalary.toFixed(2);
}

// Initialize calculations when modal opens
document.getElementById('editPayrollModal').addEventListener('shown.bs.modal', function() {
    calculateTotals();
});
</script>

<?php include 'includes/footer.php'; ?>
