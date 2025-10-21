<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'Reset Payroll System';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reset_payroll') {
        try {
            $pdo->beginTransaction();
            
            // Clear all payroll-related tables in the correct order (respecting foreign keys)
            
            // 1. Clear payroll deductions first (has foreign key to payroll)
            $pdo->exec("DELETE FROM payroll_deductions");
            
            // 2. Clear payroll records
            $pdo->exec("DELETE FROM payroll");
            
            // 3. Clear employee attendance records
            $pdo->exec("DELETE FROM employee_attendance");
            
            // 4. Clear employee leave records
            $pdo->exec("DELETE FROM employee_leaves");
            
            // 5. Reset employees table (keep structure but clear data)
            $pdo->exec("DELETE FROM employees");
            
            // 6. Reset auto-increment counters
            $pdo->exec("ALTER TABLE employees AUTO_INCREMENT = 1");
            $pdo->exec("ALTER TABLE employee_attendance AUTO_INCREMENT = 1");
            $pdo->exec("ALTER TABLE payroll AUTO_INCREMENT = 1");
            $pdo->exec("ALTER TABLE payroll_deductions AUTO_INCREMENT = 1");
            $pdo->exec("ALTER TABLE employee_leaves AUTO_INCREMENT = 1");
            
            $pdo->commit();
            
            $message = 'Payroll system has been completely reset! All records have been cleared and the system is now fresh.';
            
        } catch (Exception $e) {
            // Only rollback if there's an active transaction
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Error resetting payroll system: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-trash-alt me-2"></i>Reset Payroll System</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="payroll.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Payroll
        </a>
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

<!-- Warning Card -->
<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <h6><i class="fas fa-warning me-2"></i>Warning: This action will permanently delete ALL payroll data!</h6>
            <p class="mb-0">This includes:</p>
            <ul class="mb-0">
                <li><strong>All Employee Records</strong> - Employee profiles, codes, and information</li>
                <li><strong>All Attendance Records</strong> - Time in/out, hours worked, status</li>
                <li><strong>All Payroll Records</strong> - Salary calculations, deductions, payments</li>
                <li><strong>All Leave Records</strong> - Employee leave applications and approvals</li>
                <li><strong>All Deduction Records</strong> - Custom payroll deductions</li>
            </ul>
        </div>
        
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i>What will be preserved:</h6>
            <ul class="mb-0">
                <li>System users and login credentials</li>
                <li>Inventory and project data</li>
                <li>All other system functionality</li>
            </ul>
        </div>
    </div>
</div>

<!-- Current Data Summary -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-database me-2"></i>Current Payroll Data Summary</h5>
    </div>
    <div class="card-body">
        <?php
        try {
            // Get counts of current data
            $employee_count = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
            $attendance_count = $pdo->query("SELECT COUNT(*) FROM employee_attendance")->fetchColumn();
            $payroll_count = $pdo->query("SELECT COUNT(*) FROM payroll")->fetchColumn();
            $leaves_count = $pdo->query("SELECT COUNT(*) FROM employee_leaves")->fetchColumn();
            $deductions_count = $pdo->query("SELECT COUNT(*) FROM payroll_deductions")->fetchColumn();
        ?>
        <div class="row">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4><?php echo $employee_count; ?></h4>
                        <small>Employees</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4><?php echo $attendance_count; ?></h4>
                        <small>Attendance Records</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4><?php echo $payroll_count; ?></h4>
                        <small>Payroll Records</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4><?php echo $leaves_count; ?></h4>
                        <small>Leave Records</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h4><?php echo $deductions_count; ?></h4>
                        <small>Deduction Records</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4><?php echo $employee_count + $attendance_count + $payroll_count + $leaves_count + $deductions_count; ?></h4>
                        <small>Total Records</small>
                    </div>
                </div>
            </div>
        </div>
        <?php
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error loading data summary: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</div>

<!-- Reset Form -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-broom me-2"></i>Reset Payroll System</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="resetForm">
            <input type="hidden" name="action" value="reset_payroll">
            
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Final Warning</h6>
                <p class="mb-3">This action cannot be undone. All payroll data will be permanently deleted.</p>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmReset" required>
                    <label class="form-check-label" for="confirmReset">
                        <strong>I understand that this action will permanently delete ALL payroll data and cannot be undone.</strong>
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmBackup" required>
                    <label class="form-check-label" for="confirmBackup">
                        <strong>I have backed up any important data before proceeding.</strong>
                    </label>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="payroll.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger" id="resetButton" disabled>
                    <i class="fas fa-trash-alt"></i> Reset Payroll System
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Enable reset button only when both checkboxes are checked
document.getElementById('confirmReset').addEventListener('change', toggleResetButton);
document.getElementById('confirmBackup').addEventListener('change', toggleResetButton);

function toggleResetButton() {
    const confirmReset = document.getElementById('confirmReset').checked;
    const confirmBackup = document.getElementById('confirmBackup').checked;
    const resetButton = document.getElementById('resetButton');
    
    if (confirmReset && confirmBackup) {
        resetButton.disabled = false;
        resetButton.classList.remove('btn-danger');
        resetButton.classList.add('btn-danger');
    } else {
        resetButton.disabled = true;
    }
}

// Add confirmation dialog before submitting
document.getElementById('resetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const confirmMessage = 'Are you absolutely sure you want to reset the payroll system?\n\n' +
                          'This will permanently delete ALL payroll data including:\n' +
                          '• All employee records\n' +
                          '• All attendance records\n' +
                          '• All payroll records\n' +
                          '• All leave records\n' +
                          '• All deduction records\n\n' +
                          'This action CANNOT be undone!\n\n' +
                          'Type "RESET" to confirm:';
    
    const userInput = prompt(confirmMessage);
    
    if (userInput === 'RESET') {
        // Show loading state
        const resetButton = document.getElementById('resetButton');
        resetButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
        resetButton.disabled = true;
        
        // Submit the form
        this.submit();
    }
});
</script>

<style>
.card.border-danger {
    border-width: 2px;
}

.alert-warning {
    border-left: 4px solid #ffc107;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}

.alert-danger {
    border-left: 4px solid #dc3545;
}

#resetButton:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<?php include 'includes/footer.php'; ?>
