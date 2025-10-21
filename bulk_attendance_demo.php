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

$page_title = 'Bulk Attendance Demo';

// Get employee ID 21 (Liddy Lou Orsuga)
$employee_id = 21;
$employee = getEmployeeById($pdo, $employee_id);

if (!$employee) {
    header('Location: payroll.php');
    exit();
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-calendar-plus me-2"></i>Bulk Attendance Entry Demo</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="employee_attendance.php?employee_id=<?php echo $employee_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-calendar-check"></i> View Attendance
            </a>
            <a href="payroll.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Payroll
            </a>
        </div>
    </div>
</div>

<!-- Employee Info -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Employee Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Employee Code:</strong><br>
                <?php echo htmlspecialchars($employee['employee_code']); ?>
            </div>
            <div class="col-md-3">
                <strong>Name:</strong><br>
                <?php echo htmlspecialchars($employee['employee_name']); ?>
            </div>
            <div class="col-md-3">
                <strong>Position:</strong><br>
                <?php echo htmlspecialchars($employee['position']); ?>
            </div>
            <div class="col-md-3">
                <strong>Date Joined:</strong><br>
                <?php echo date('M d, Y', strtotime($employee['date_of_joining'])); ?>
            </div>
        </div>
    </div>
</div>

<!-- Instructions -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>How to Use Bulk Attendance Entry</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Step 1: Access Bulk Entry</h6>
                <p>Click the "Bulk Entry (15 Days)" button in the attendance page to open the bulk attendance modal.</p>
                
                <h6 class="text-primary">Step 2: Set Date Range</h6>
                <p>Select the start and end dates for the attendance period. The system will generate up to 15 working days (excluding Sundays).</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">Step 3: Enter Time Data</h6>
                <p>For each day, enter:</p>
                <ul>
                    <li><strong>Time In:</strong> When the employee started work</li>
                    <li><strong>Time Out:</strong> When the employee finished work</li>
                    <li><strong>Status:</strong> Present, Absent, Late, Half Day, or Overtime</li>
                    <li><strong>Notes:</strong> Any additional comments</li>
                </ul>
            </div>
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Automatic Calculations:</strong> The system will automatically calculate hours worked based on time in/out. 
            You can also manually adjust the status and hours if needed.
        </div>
    </div>
</div>

<!-- Demo Features -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Time Tracking</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i>Automatic hour calculation</li>
                    <li><i class="fas fa-check text-success me-2"></i>Time in/out validation</li>
                    <li><i class="fas fa-check text-success me-2"></i>Overtime tracking</li>
                    <li><i class="fas fa-check text-success me-2"></i>Status-based defaults</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Bulk Entry</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i>Up to 15 working days</li>
                    <li><i class="fas fa-check text-success me-2"></i>Skip Sundays automatically</li>
                    <li><i class="fas fa-check text-success me-2"></i>Batch save all records</li>
                    <li><i class="fas fa-check text-success me-2"></i>Error handling & validation</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Smart Features</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i>Auto-status detection</li>
                    <li><i class="fas fa-check text-success me-2"></i>Default time suggestions</li>
                    <li><i class="fas fa-check text-success me-2"></i>Real-time calculations</li>
                    <li><i class="fas fa-check text-success me-2"></i>Responsive design</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Quick Start -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Quick Start</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h6>Ready to try the bulk attendance feature?</h6>
                <p>Click the button below to go directly to the attendance page for Liddy Lou Orsuga and try the bulk entry feature.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="employee_attendance.php?employee_id=<?php echo $employee_id; ?>" class="btn btn-success btn-lg">
                    <i class="fas fa-calendar-plus"></i> Try Bulk Entry
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
