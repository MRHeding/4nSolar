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

$page_title = 'Payroll Slips';

// Handle form submissions
$message = '';
$error = '';

// Get parameters
$payroll_id = $_GET['id'] ?? null;
$employee_id = $_GET['employee_id'] ?? null;
$pay_period = $_GET['pay_period'] ?? null;
$action = $_GET['action'] ?? 'list';

// Initialize variables
$payroll = null;
$payroll_records = [];

// Handle delete action
if ($action === 'delete' && $payroll_id) {
    try {
        $delete_sql = "DELETE FROM payroll WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$payroll_id]);
        
        if ($delete_stmt->rowCount() > 0) {
            $message = "Payroll slip deleted successfully.";
        } else {
            $error = "Payroll slip not found or already deleted.";
        }
    } catch (Exception $e) {
        $error = "Error deleting payroll slip: " . $e->getMessage();
    }
}

// Get payroll records
if ($payroll_id && $action !== 'delete') {
    $payroll = getPayrollById($pdo, $payroll_id);
    if (!$payroll) {
        header('Location: payroll_slip.php');
        exit();
    }
} elseif ($employee_id && $pay_period) {
    // Get payroll for specific employee and period
    $sql = "SELECT p.*, e.employee_name, e.employee_code, e.position 
            FROM payroll p 
            JOIN employees e ON p.employee_id = e.id 
            WHERE p.employee_id = ? AND p.pay_period_start = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id, $pay_period]);
    $payroll = $stmt->fetch();
} else {
    // Get all payroll records
    $payroll_records = getPayrollRecords($pdo, null, 100);
}

// Get employees for dropdown
$employees = getAllEmployees($pdo);

    include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-file-invoice me-2"></i>Payroll Slips</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="payroll.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Payroll
            </a>
            <?php if ($payroll): ?>
            <button type="button" class="btn btn-primary" onclick="printPaySlip()">
                <i class="fas fa-print"></i> Print Slip
            </button>
            <button type="button" class="btn btn-success" onclick="downloadPDF()">
                <i class="fas fa-download"></i> Download PDF
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

<?php if ($payroll): ?>
<!-- Pay Slip Display -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card" id="paySlip">
            <div class="card-header bg-primary text-white">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>PAY SLIP</h4>
                    </div>
                    <div class="col-md-6 text-end">
                        <small>Pay Period: <?php echo date('M d', strtotime($payroll['pay_period_start'])); ?> - <?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?></small>
                    </div>
            </div>
            </div>
            <div class="card-body">
                <!-- Company Header -->
                <div class="text-center mb-4">
                    <h3 class="text-primary">4NSOLAR ELECTRICZ</h3>
                    <p class="text-muted">Solar Installation & Electrical Services</p>
                    <hr>
        </div>

                <!-- Employee Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary">Employee Information</h6>
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
                        <h6 class="text-primary">Pay Period Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Pay Period:</strong></td>
                                <td><?php echo date('M d', strtotime($payroll['pay_period_start'])); ?> - <?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?></td>
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

                <!-- Salary Breakdown -->
        <div class="row">
            <div class="col-md-6">
                        <h6 class="text-success">Earnings</h6>
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

            <div class="col-md-6">
                        <h6 class="text-danger">Deductions</h6>
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

                <!-- Net Pay -->
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
                
                <!-- Status and Notes -->
                <div class="row mt-4">
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
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Generated</h6>
                        <p class="mb-0"><?php echo date('M d, Y g:i A', strtotime($payroll['created_at'])); ?></p>
                    </div>
                </div>
                
                <?php if ($payroll['notes']): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary">Notes</h6>
                        <p class="text-muted"><?php echo htmlspecialchars($payroll['notes']); ?></p>
                </div>
            </div>
                <?php endif; ?>
                
                <!-- Signature Section -->
                <div class="row mt-5">
                    <div class="col-md-6">
                        <div class="text-center">
                            <div class="signature-line mb-2" style="border-bottom: 1px solid #000; width: 200px; margin: 0 auto;"></div>
                            <p class="mb-1"><strong>Mr. Novie G. Mohadsa</strong></p>
                            <p class="text-muted small">Operations Manager</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center">
                            <div class="signature-line mb-2" style="border-bottom: 1px solid #000; width: 200px; margin: 0 auto;"></div>
                            <p class="mb-1"><strong>Ms. Liddy Lou Orsuga</strong></p>
                            <p class="text-muted small">HR Manager</p>
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>
        </div>

<?php else: ?>
<!-- Pay Slip List -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Pay Slip Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Employee</th>
                                <th>Pay Period</th>
                                <th>Gross Salary</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payroll_records as $record): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['employee_code']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($record['employee_name']); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M d', strtotime($record['pay_period_start'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($record['pay_period_end'])); ?>
                                </td>
                                <td>₱<?php echo number_format($record['gross_salary'], 2); ?></td>
                                <td>₱<?php echo number_format($record['total_deductions'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($record['net_salary'], 2); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($record['status']) {
                                        case 'draft': $status_class = 'secondary'; break;
                                        case 'approved': $status_class = 'warning'; break;
                                        case 'paid': $status_class = 'success'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?id=<?php echo $record['id']; ?>" class="btn btn-outline-primary" title="View Slip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?id=<?php echo $record['id']; ?>&print=1" class="btn btn-outline-success" title="Print" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="?id=<?php echo $record['id']; ?>&pdf=1" class="btn btn-outline-info" title="Download PDF">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Delete Slip" onclick="deletePaySlip(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['employee_name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
<?php endif; ?>

<script>
function printPaySlip() {
    window.print();
}

function downloadPDF() {
    // This would typically use a PDF generation library like TCPDF or mPDF
    // For now, we'll just redirect to a PDF generation endpoint
    window.open('?id=<?php echo $payroll_id; ?>&pdf=1', '_blank');
}

function deletePaySlip(payrollId, employeeName) {
    if (confirm('Are you sure you want to delete the payroll slip for ' + employeeName + '?\n\nThis action cannot be undone.')) {
        window.location.href = '?id=' + payrollId + '&action=delete';
    }
}

// Auto-print if print parameter is set
<?php if (isset($_GET['print']) && $_GET['print'] == '1'): ?>
window.onload = function() {
    window.print();
};
<?php endif; ?>
</script>

<style>
@media print {
    /* Hide navigation elements */
    .navbar,
    .sidebar,
    nav {
        display: none !important;
    }
    
    /* Hide page header and buttons */
    .btn-toolbar, 
    .alert, 
    .d-flex.justify-content-between {
        display: none !important;
    }
    
    /* Hide the card header with PAY SLIP title */
    .card-header {
        display: none !important;
    }
    
    /* Hide the 4NSOLAR ELECTRICZ business header */
    .text-center.mb-4 {
        display: none !important;
    }
    
    /* Ensure main content is visible and takes full width */
    .main-content,
    #main-content,
    .container-fluid,
    .row,
    .col-md-8,
    .col-md-12 {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Show the payroll slip content */
    .card-body {
        display: block !important;
        width: 100% !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        width: 100% !important;
    }
    
    body {
        font-size: 12px;
        margin: 0 !important;
        padding: 10px !important;
    }
    
    .table {
        font-size: 11px;
    }
    
    /* Signature section styling for print */
    .signature-line {
        border-bottom: 1px solid #000 !important;
        width: 200px !important;
        margin: 0 auto !important;
        height: 30px !important;
    }
    
    /* Ensure signature section is visible in print */
    .row.mt-5 {
        margin-top: 30px !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>