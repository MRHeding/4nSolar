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

<div class="flex justify-between items-center flex-wrap gap-4 py-4 mb-6 border-b border-gray-200 dark:border-gray-700">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="fas fa-calculator"></i>
        <span>Payroll Details</span>
    </h1>
    <div class="flex flex-wrap gap-2">
        <div class="flex gap-2">
            <a href="payroll.php" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Payroll
            </a>
            <a href="payroll_slip.php?id=<?php echo $payroll_id; ?>" class="px-4 py-2 border border-blue-500 dark:border-blue-600 rounded-lg text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                <i class="fas fa-file-invoice mr-2"></i> View Pay Slip
            </a>
        </div>
        <div class="flex gap-2">
            <?php if ($payroll['status'] === 'draft'): ?>
            <button type="button" class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm font-medium hover:bg-yellow-700 transition-colors" onclick="approvePayroll()">
                <i class="fas fa-check mr-2"></i> Approve
            </button>
            <button type="button" class="btn-primary" onclick="openModal('editPayrollModal')">
                <i class="fas fa-edit mr-2"></i> Edit
            </button>
            <?php elseif ($payroll['status'] === 'approved'): ?>
            <button type="button" class="btn-success" onclick="markPaid()">
                <i class="fas fa-money-bill mr-2"></i> Mark as Paid
            </button>
            <?php endif; ?>
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

<!-- Payroll Information -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2">
        <div class="card">
            <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>Payroll Information
                </h5>
            </div>
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h6 class="text-blue-600 dark:text-blue-400 font-semibold mb-3">Employee Details</h6>
                        <table class="w-full">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-2 pr-4 font-semibold text-gray-700 dark:text-gray-300">Employee Code:</td>
                                <td class="py-2 text-gray-900 dark:text-white"><?php echo htmlspecialchars($payroll['employee_code']); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-2 pr-4 font-semibold text-gray-700 dark:text-gray-300">Name:</td>
                                <td class="py-2 text-gray-900 dark:text-white"><?php echo htmlspecialchars($payroll['employee_name']); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-2 pr-4 font-semibold text-gray-700 dark:text-gray-300">Position:</td>
                                <td class="py-2 text-gray-900 dark:text-white"><?php echo htmlspecialchars($payroll['position']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <h6 class="text-blue-600 dark:text-blue-400 font-semibold mb-3">Pay Period</h6>
                        <table class="w-full">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-2 pr-4 font-semibold text-gray-700 dark:text-gray-300">Start Date:</td>
                                <td class="py-2 text-gray-900 dark:text-white"><?php echo date('M d, Y', strtotime($payroll['pay_period_start'])); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-2 pr-4 font-semibold text-gray-700 dark:text-gray-300">End Date:</td>
                                <td class="py-2 text-gray-900 dark:text-white"><?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?></td>
                            </tr>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-2 pr-4 font-semibold text-gray-700 dark:text-gray-300">Working Days:</td>
                                <td class="py-2 text-gray-900 dark:text-white"><?php echo $payroll['total_working_days']; ?> days</td>
                            </tr>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-2 pr-4 font-semibold text-gray-700 dark:text-gray-300">Present Days:</td>
                                <td class="py-2 text-gray-900 dark:text-white"><?php echo $payroll['working_days_present']; ?> days</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h6 class="text-blue-600 dark:text-blue-400 font-semibold mb-3">Status</h6>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        switch ($payroll['status']) {
                            case 'draft':
                                $status_class = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                $status_text = 'Draft';
                                break;
                            case 'approved':
                                $status_class = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                                $status_text = 'Approved';
                                break;
                            case 'paid':
                                $status_class = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                $status_text = 'Paid';
                                break;
                        }
                        ?>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                        
                        <?php if ($payroll['approved_at']): ?>
                        <div class="mt-2">
                            <small class="text-gray-500 dark:text-gray-400">Approved on: <?php echo date('M d, Y g:i A', strtotime($payroll['approved_at'])); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($payroll['paid_at']): ?>
                        <div class="mt-2">
                            <small class="text-gray-500 dark:text-gray-400">Paid on: <?php echo date('M d, Y g:i A', strtotime($payroll['paid_at'])); ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h6 class="text-blue-600 dark:text-blue-400 font-semibold mb-3">Generated</h6>
                        <p class="text-gray-900 dark:text-white mb-3"><?php echo date('M d, Y g:i A', strtotime($payroll['created_at'])); ?></p>
                        <?php if ($payroll['notes']): ?>
                        <div class="mt-3">
                            <strong class="text-gray-700 dark:text-gray-300">Notes:</strong>
                            <p class="text-gray-600 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($payroll['notes']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div>
        <div class="card">
            <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                <h6 class="font-semibold text-gray-800 dark:text-white">Quick Actions</h6>
            </div>
            <div class="space-y-2">
                <a href="payroll_slip.php?id=<?php echo $payroll_id; ?>&print=1" class="block w-full px-4 py-2 border border-blue-500 rounded-lg text-sm font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-center" target="_blank">
                    <i class="fas fa-print mr-2"></i> Print Pay Slip
                </a>
                <a href="payroll_slip.php?id=<?php echo $payroll_id; ?>&pdf=1" class="block w-full px-4 py-2 border border-red-500 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-center" target="_blank">
                    <i class="fas fa-download mr-2"></i> Download PDF
                </a>
                <a href="employee_attendance.php?employee_id=<?php echo $payroll['employee_id']; ?>&month=<?php echo date('Y-m', strtotime($payroll['pay_period_start'])); ?>" class="block w-full px-4 py-2 border border-cyan-500 rounded-lg text-sm font-medium text-cyan-600 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition-colors text-center">
                    <i class="fas fa-calendar-check mr-2"></i> View Attendance
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Salary Breakdown -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="card">
        <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
            <h6 class="font-semibold text-green-600 dark:text-green-400 flex items-center">
                <i class="fas fa-plus-circle mr-2"></i>Earnings
            </h6>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Basic Salary</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                    </tr>
                    <?php if (!empty($payroll['packages'])): ?>
                        <?php foreach ($payroll['packages'] as $package): ?>
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white"><?php echo htmlspecialchars($package['package_name']); ?></td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($package['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($payroll['allowances'] > 0): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Allowances</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['overtime_pay'] > 0): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Overtime Pay</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['bonus_pay'] > 0): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Bonus Pay</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['bonus_pay'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="bg-green-50 dark:bg-green-900/20">
                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">Total Earnings</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">₱<?php echo number_format($payroll['gross_salary'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
            <h6 class="font-semibold text-red-600 dark:text-red-400 flex items-center">
                <i class="fas fa-minus-circle mr-2"></i>Deductions
            </h6>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if ($payroll['cash_advance'] > 0): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Cash Advance</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['cash_advance'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['uniforms'] > 0): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Uniforms</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['uniforms'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['tools'] > 0): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Tools</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['tools'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['lates'] > 0): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Late Penalties</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['lates'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payroll['miscellaneous'] > 0): ?>
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">Miscellaneous</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">₱<?php echo number_format($payroll['miscellaneous'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="bg-red-50 dark:bg-red-900/20">
                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">Total Deductions</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">₱<?php echo number_format($payroll['total_deductions'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Net Pay Summary -->
<div class="mb-6">
    <div class="bg-blue-600 text-white rounded-lg p-8 text-center">
        <h4 class="text-xl font-semibold mb-2">NET PAY</h4>
        <h2 class="text-4xl font-bold">₱<?php echo number_format($payroll['net_salary'], 2); ?></h2>
    </div>
</div>

<!-- Edit Payroll Modal -->
<div id="editPayrollModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black opacity-50" onclick="closeModal('editPayrollModal')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full p-6">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center">
                    <i class="fas fa-edit mr-2"></i>Edit Payroll
                </h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="closeModal('editPayrollModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_payroll">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h6 class="text-green-600 dark:text-green-400 font-semibold mb-4">Earnings</h6>
                            <div class="space-y-4">
                                <div>
                                    <label for="basic_salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Basic Salary</label>
                                    <input type="number" class="form-input" id="basic_salary" name="basic_salary" step="0.01" value="<?php echo $payroll['basic_salary']; ?>" onchange="calculateTotals()">
                                </div>
                                <div>
                                    <label for="allowances" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Allowances</label>
                                    <input type="number" class="form-input" id="allowances" name="allowances" step="0.01" value="<?php echo $payroll['allowances']; ?>" onchange="calculateTotals()">
                                </div>
                                <div>
                                    <label for="overtime_pay" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Overtime Pay</label>
                                    <input type="number" class="form-input" id="overtime_pay" name="overtime_pay" step="0.01" value="<?php echo $payroll['overtime_pay']; ?>" onchange="calculateTotals()">
                                </div>
                                <div>
                                    <label for="bonus_pay" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bonus Pay</label>
                                    <input type="number" class="form-input" id="bonus_pay" name="bonus_pay" step="0.01" value="<?php echo $payroll['bonus_pay']; ?>" onchange="calculateTotals()">
                                </div>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-red-600 dark:text-red-400 font-semibold mb-4">Deductions</h6>
                            <div class="space-y-4">
                                <div>
                                    <label for="cash_advance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cash Advance</label>
                                    <input type="number" class="form-input" id="cash_advance" name="cash_advance" step="0.01" value="<?php echo $payroll['cash_advance']; ?>" onchange="calculateTotals()">
                                </div>
                                <div>
                                    <label for="uniforms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uniforms</label>
                                    <input type="number" class="form-input" id="uniforms" name="uniforms" step="0.01" value="<?php echo $payroll['uniforms']; ?>" onchange="calculateTotals()">
                                </div>
                                <div>
                                    <label for="tools" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tools</label>
                                    <input type="number" class="form-input" id="tools" name="tools" step="0.01" value="<?php echo $payroll['tools']; ?>" onchange="calculateTotals()">
                                </div>
                                <div>
                                    <label for="lates" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Late Penalties</label>
                                    <input type="number" class="form-input" id="lates" name="lates" step="0.01" value="<?php echo $payroll['lates']; ?>" onchange="calculateTotals()">
                                </div>
                                <div>
                                    <label for="miscellaneous" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Miscellaneous</label>
                                    <input type="number" class="form-input" id="miscellaneous" name="miscellaneous" step="0.01" value="<?php echo $payroll['miscellaneous']; ?>" onchange="calculateTotals()">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <label for="gross_salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gross Salary</label>
                            <input type="number" class="form-input bg-gray-100 dark:bg-gray-700" id="gross_salary" name="gross_salary" step="0.01" readonly>
                        </div>
                        <div>
                            <label for="total_deductions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Deductions</label>
                            <input type="number" class="form-input bg-gray-100 dark:bg-gray-700" id="total_deductions" name="total_deductions" step="0.01" readonly>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label for="net_salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Net Salary</label>
                        <input type="number" class="form-input bg-gray-100 dark:bg-gray-700 font-bold text-lg" id="net_salary" name="net_salary" step="0.01" readonly>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea class="form-input" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($payroll['notes']); ?></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="btn-secondary" onclick="closeModal('editPayrollModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Payroll</button>
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
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editPayrollModal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('fixed')) {
                // Initialize calculations when modal is opened
                setTimeout(calculateTotals, 100);
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
