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

$page_title = 'Payroll Reports';

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$employee_id = $_GET['employee_id'] ?? null;
$status = $_GET['status'] ?? null;
$export = $_GET['export'] ?? null;

// Get employees for dropdown
$employees = getAllEmployees($pdo);

// Build query for payroll records
$sql = "SELECT p.*, e.employee_name, e.employee_code, e.position 
        FROM payroll p 
        JOIN employees e ON p.employee_id = e.id 
        WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?";
$params = [$start_date, $end_date];

if ($employee_id) {
    $sql .= " AND p.employee_id = ?";
    $params[] = $employee_id;
}

if ($status) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY p.pay_period_end DESC, e.employee_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payroll_records = $stmt->fetchAll();

// Calculate summary statistics
$total_gross = array_sum(array_column($payroll_records, 'gross_salary'));
$total_deductions = array_sum(array_column($payroll_records, 'total_deductions'));
$total_net = array_sum(array_column($payroll_records, 'net_salary'));
$total_employees = count(array_unique(array_column($payroll_records, 'employee_id')));

// Status distribution
$status_counts = array_count_values(array_column($payroll_records, 'status'));

// Handle export
if ($export === 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="payroll_report_' . date('Y-m-d') . '.xls"');
    
    echo "Payroll Report - " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date)) . "\n\n";
    echo "Employee Code\tEmployee Name\tPosition\tPay Period\tGross Salary\tDeductions\tNet Pay\tStatus\n";
    
    foreach ($payroll_records as $record) {
        echo $record['employee_code'] . "\t";
        echo $record['employee_name'] . "\t";
        echo $record['position'] . "\t";
        echo date('M d', strtotime($record['pay_period_start'])) . " - " . date('M d, Y', strtotime($record['pay_period_end'])) . "\t";
        echo number_format($record['gross_salary'], 2) . "\t";
        echo number_format($record['total_deductions'], 2) . "\t";
        echo number_format($record['net_salary'], 2) . "\t";
        echo ucfirst($record['status']) . "\n";
    }
    exit();
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>Payroll Reports</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="payroll.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Payroll
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-outline-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>
    </div>
</div>

<!-- Filter Controls -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Options</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="employee_id" class="form-label">Employee</label>
                <select class="form-select" id="employee_id" name="employee_id">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $employee): ?>
                    <option value="<?php echo $employee['id']; ?>" <?php echo ($employee_id == $employee['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($employee['employee_code'] . ' - ' . $employee['employee_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?php echo ($status === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="approved" <?php echo ($status === 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="paid" <?php echo ($status === 'paid') ? 'selected' : ''; ?>>Paid</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Generate Report
                </button>
                <a href="?" class="btn btn-outline-secondary">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3>₱<?php echo number_format($total_gross, 2); ?></h3>
                <p class="mb-0">Total Gross Pay</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3>₱<?php echo number_format($total_deductions, 2); ?></h3>
                <p class="mb-0">Total Deductions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3>₱<?php echo number_format($total_net, 2); ?></h3>
                <p class="mb-0">Total Net Pay</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo $total_employees; ?></h3>
                <p class="mb-0">Employees</p>
            </div>
        </div>
    </div>
</div>

<!-- Status Distribution -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Payroll Status Distribution</h6>
            </div>
            <div class="card-body">
                <?php
                $total_records = count($payroll_records);
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Draft</span>
                        <span><?php echo $status_counts['draft'] ?? 0; ?></span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-secondary" style="width: <?php echo $total_records > 0 ? (($status_counts['draft'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Approved</span>
                        <span><?php echo $status_counts['approved'] ?? 0; ?></span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-warning" style="width: <?php echo $total_records > 0 ? (($status_counts['approved'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span>Paid</span>
                        <span><?php echo $status_counts['paid'] ?? 0; ?></span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $total_records > 0 ? (($status_counts['paid'] ?? 0) / $total_records) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-outline-success">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </a>
                    <a href="payroll_slip.php" class="btn btn-outline-primary">
                        <i class="fas fa-file-invoice"></i> Generate Pay Slips
                    </a>
                    <a href="payroll.php?view=reports" class="btn btn-outline-info">
                        <i class="fas fa-chart-pie"></i> View Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payroll Records Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-table me-2"></i>
            Payroll Records 
            <span class="badge bg-primary"><?php echo count($payroll_records); ?> records</span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($payroll_records)): ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No payroll records found</h5>
                <p class="text-muted">Try adjusting your filter criteria or generate some payroll records.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Employee</th>
                            <th>Pay Period</th>
                            <th>Working Days</th>
                            <th>Present Days</th>
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
                                <small><?php echo htmlspecialchars($record['employee_name']); ?></small><br>
                                <small class="text-muted"><?php echo htmlspecialchars($record['position']); ?></small>
                            </td>
                            <td>
                                <?php echo date('M d', strtotime($record['pay_period_start'])); ?> - 
                                <?php echo date('M d, Y', strtotime($record['pay_period_end'])); ?>
                            </td>
                            <td><?php echo $record['total_working_days']; ?></td>
                            <td><?php echo $record['working_days_present']; ?></td>
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
                                    <a href="payroll_details.php?id=<?php echo $record['id']; ?>" class="btn btn-outline-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="payroll_slip.php?id=<?php echo $record['id']; ?>" class="btn btn-outline-primary" title="View Pay Slip">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
