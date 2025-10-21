<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

// Check if user has permission to access payroll
if (!hasPermission([ROLE_ADMIN, ROLE_HR])) {
    die('Access denied. You do not have permission to access this page.');
}

$page_title = 'Test Dynamic Packages';

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-cogs me-2"></i>Dynamic Package System Test</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="payroll.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Payroll
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Dynamic Package System Features</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-success">
            <h6><i class="fas fa-check-circle me-2"></i>✅ Dynamic Package System Successfully Implemented!</h6>
            <p class="mb-0">The payroll system now supports unlimited custom packages instead of fixed 1500, 2500, 3500 packages.</p>
        </div>
        
        <h6><strong>New Features:</strong></h6>
        <ul>
            <li><strong>✅ Add Multiple Packages:</strong> Users can add as many packages as needed</li>
            <li><strong>✅ Custom Package Names:</strong> Each package can have a custom name (e.g., "Solar Installation", "Maintenance", "Emergency Call")</li>
            <li><strong>✅ Custom Amounts:</strong> Each package can have any amount (not limited to 1500, 2500, 3500)</li>
            <li><strong>✅ Dynamic Interface:</strong> "Add Package" button to add new packages dynamically</li>
            <li><strong>✅ Remove Packages:</strong> Delete button to remove unwanted packages</li>
            <li><strong>✅ Real-time Updates:</strong> All payroll views (details, slip) show dynamic packages</li>
        </ul>
        
        <h6><strong>How to Use:</strong></h6>
        <ol>
            <li>Go to <strong>Payroll → Generate Payroll</strong></li>
            <li><strong>Optional:</strong> Click <strong>"Add Package"</strong> button to add packages (only if needed)</li>
            <li><strong>Optional:</strong> Enter custom package name (e.g., "Solar Panel Installation")</li>
            <li><strong>Optional:</strong> Enter custom amount (e.g., 5000.00)</li>
            <li>Add as many packages as needed, or leave blank if no packages</li>
            <li>Remove packages using the trash icon if needed</li>
            <li>Generate payroll with or without packages</li>
        </ol>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="text-primary"><i class="fas fa-plus-circle me-2"></i>Example Package Names:</h6>
                        <ul class="mb-0">
                            <li>Solar Panel Installation</li>
                            <li>Maintenance Work</li>
                            <li>Emergency Call</li>
                            <li>System Upgrade</li>
                            <li>Customer Support</li>
                            <li>Training Session</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="text-success"><i class="fas fa-dollar-sign me-2"></i>Example Package Amounts:</h6>
                        <ul class="mb-0">
                            <li>₱1,500.00 - Small Installation</li>
                            <li>₱2,500.00 - Medium Project</li>
                            <li>₱3,500.00 - Large Installation</li>
                            <li>₱5,000.00 - Premium Service</li>
                            <li>₱10,000.00 - Complex Project</li>
                            <li>Any custom amount</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-4">
            <h6><i class="fas fa-lightbulb me-2"></i>Benefits of Dynamic Packages:</h6>
            <ul class="mb-0">
                <li><strong>Flexibility:</strong> No more fixed package limits</li>
                <li><strong>Customization:</strong> Each project can have unique packages</li>
                <li><strong>Scalability:</strong> Add unlimited packages as business grows</li>
                <li><strong>User-Friendly:</strong> Simple add/remove interface</li>
                <li><strong>Professional:</strong> Custom package names for better tracking</li>
            </ul>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Ready to Test</h5>
    </div>
    <div class="card-body text-center">
        <p class="mb-3">The dynamic package system is now ready for use!</p>
        <a href="payroll.php" class="btn btn-primary btn-lg">
            <i class="fas fa-calculator me-2"></i>Go to Payroll System
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
