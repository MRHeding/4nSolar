<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in and has HR or admin role
if (!isLoggedIn() || !hasPermission(['admin', 'hr'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_employee'])) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO employees 
                (employee_code, first_name, last_name, email, phone, address, date_of_joining, 
                 basic_salary, package_salary, allowances, position, department, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['employee_code'], $_POST['first_name'], $_POST['last_name'],
                $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['date_of_joining'],
                $_POST['basic_salary'], $_POST['package_salary'], $_POST['allowances'],
                $_POST['position'], $_POST['department'], $_SESSION['user_id']
            ]);
            
            $employee_id = $pdo->lastInsertId();
            
            // Create default leave balances
            $leave_types = [
                ['annual', 15],
                ['sick', 10],
                ['emergency', 5]
            ];
            
            foreach ($leave_types as $leave) {
                $leave_stmt = $pdo->prepare("
                    INSERT INTO employee_leave_balances 
                    (employee_id, leave_type, total_allocated, used_leaves, balance_leaves, year) 
                    VALUES (?, ?, ?, 0, ?, YEAR(CURDATE()))
                ");
                $leave_stmt->execute([$employee_id, $leave[0], $leave[1], $leave[1]]);
            }
            
            $success = "Employee added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding employee: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_employee'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE employees SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, 
                basic_salary = ?, package_salary = ?, allowances = ?, position = ?, department = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['first_name'], $_POST['last_name'], $_POST['email'],
                $_POST['phone'], $_POST['address'], $_POST['basic_salary'],
                $_POST['package_salary'], $_POST['allowances'], $_POST['position'],
                $_POST['department'], $_POST['employee_id']
            ]);
            $success = "Employee updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating employee: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_employee'])) {
        try {
            $employee_id = $_POST['employee_id'];
            
            // Check if employee has payroll records
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM payroll_records WHERE employee_id = ?");
            $check_stmt->execute([$employee_id]);
            $payroll_count = $check_stmt->fetchColumn();
            
            if ($payroll_count > 0) {
                // Don't delete, just deactivate
                $stmt = $pdo->prepare("UPDATE employees SET employment_status = 'terminated' WHERE id = ?");
                $stmt->execute([$employee_id]);
                $success = "Employee has been deactivated (has existing payroll records).";
            } else {
                // Safe to delete completely
                // First delete leave balances
                $leave_stmt = $pdo->prepare("DELETE FROM employee_leave_balances WHERE employee_id = ?");
                $leave_stmt->execute([$employee_id]);
                
                // Delete attendance records
                $attendance_stmt = $pdo->prepare("DELETE FROM employee_attendance WHERE employee_id = ?");
                $attendance_stmt->execute([$employee_id]);
                
                // Finally delete employee
                $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->execute([$employee_id]);
                $success = "Employee deleted successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error deleting employee: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['reactivate_employee'])) {
        try {
            $employee_id = $_POST['employee_id'];
            $stmt = $pdo->prepare("UPDATE employees SET employment_status = 'active' WHERE id = ?");
            $stmt->execute([$employee_id]);
            $success = "Employee reactivated successfully!";
        } catch (PDOException $e) {
            $error = "Error reactivating employee: " . $e->getMessage();
        }
    }
}

// Get all employees with optional filter
$filter = $_GET['status'] ?? 'all';
try {
    if ($filter === 'active') {
        $stmt = $pdo->query("SELECT * FROM employees WHERE employment_status = 'active' ORDER BY employee_code");
    } elseif ($filter === 'terminated') {
        $stmt = $pdo->query("SELECT * FROM employees WHERE employment_status = 'terminated' ORDER BY employee_code");
    } else {
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY employee_code");
    }
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching employees: " . $e->getMessage();
    $employees = [];
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users"></i> Employee Management</h2>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal">
                        <i class="fas fa-plus"></i> Add Employee
                    </button>
                    <a href="payroll.php" class="btn btn-secondary">
                        <i class="fas fa-money-check-alt"></i> Back to Payroll
                    </a>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="employees.php?status=all" class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> All Employees
                    </a>
                    <a href="employees.php?status=active" class="btn btn-outline-success <?= $filter === 'active' ? 'active' : '' ?>">
                        <i class="fas fa-user-check"></i> Active Only
                    </a>
                    <a href="employees.php?status=terminated" class="btn btn-outline-danger <?= $filter === 'terminated' ? 'active' : '' ?>">
                        <i class="fas fa-user-times"></i> Terminated Only
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

            <!-- Employees Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Employees</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Employee Code</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Date Joined</th>
                                    <th>Basic Salary</th>
                                    <th>Package Salary</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($employee['employee_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                        <td><?= htmlspecialchars($employee['position']) ?></td>
                                        <td><?= htmlspecialchars($employee['department']) ?></td>
                                        <td><?= date('M j, Y', strtotime($employee['date_of_joining'])) ?></td>
                                        <td><?= formatCurrency($employee['basic_salary']) ?></td>
                                        <td><?= formatCurrency($employee['package_salary']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $employee['employment_status'] === 'active' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($employee['employment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editEmployee(<?= htmlspecialchars(json_encode($employee)) ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if ($employee['employment_status'] === 'active'): ?>
                                                <a href="employee_attendance.php?id=<?= $employee['id'] ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-calendar"></i> Attendance
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(<?= $employee['id'] ?>, '<?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="reactivateEmployee(<?= $employee['id'] ?>, '<?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>')">
                                                    <i class="fas fa-undo"></i> Reactivate
                                                </button>
                                            <?php endif; ?>
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
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalTitle">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="employeeForm">
                <div class="modal-body">
                    <input type="hidden" name="employee_id" id="employee_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_code" class="form-label">Employee Code *</label>
                                <input type="text" class="form-control" id="employee_code" name="employee_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_of_joining" class="form-label">Date of Joining *</label>
                                <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position *</label>
                                <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">Department *</label>
                                <select class="form-control" id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="Operations">Operations</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Projects">Projects</option>
                                    <option value="Administration">Administration</option>
                                    <option value="HR">Human Resources</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="basic_salary" class="form-label">Basic Salary *</label>
                                <input type="number" class="form-control" id="basic_salary" name="basic_salary" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="package_salary" class="form-label">Package Salary *</label>
                                <input type="number" class="form-control" id="package_salary" name="package_salary" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="allowances" class="form-label">Allowances</label>
                                <input type="number" class="form-control" id="allowances" name="allowances" 
                                       step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_employee" id="submitBtn" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="employee_id" id="deleteEmployeeId">
    <input type="hidden" name="delete_employee" value="1">
</form>

<!-- Hidden Reactivate Form -->
<form id="reactivateForm" method="POST" style="display: none;">
    <input type="hidden" name="employee_id" id="reactivateEmployeeId">
    <input type="hidden" name="reactivate_employee" value="1">
</form>

<script>
function editEmployee(employee) {
    document.getElementById('employeeModalTitle').textContent = 'Edit Employee';
    document.getElementById('employee_id').value = employee.id;
    document.getElementById('employee_code').value = employee.employee_code;
    document.getElementById('employee_code').readOnly = true;
    document.getElementById('first_name').value = employee.first_name;
    document.getElementById('last_name').value = employee.last_name;
    document.getElementById('email').value = employee.email || '';
    document.getElementById('phone').value = employee.phone || '';
    document.getElementById('address').value = employee.address || '';
    document.getElementById('date_of_joining').value = employee.date_of_joining;
    document.getElementById('position').value = employee.position;
    document.getElementById('department').value = employee.department;
    document.getElementById('basic_salary').value = employee.basic_salary;
    document.getElementById('package_salary').value = employee.package_salary;
    document.getElementById('allowances').value = employee.allowances;
    
    document.getElementById('submitBtn').textContent = 'Update Employee';
    document.getElementById('submitBtn').name = 'update_employee';
    
    new bootstrap.Modal(document.getElementById('employeeModal')).show();
}

function deleteEmployee(employeeId, employeeName) {
    if (confirm(`Are you sure you want to delete employee "${employeeName}"?\n\nNote: If the employee has payroll records, they will be deactivated instead of deleted.`)) {
        document.getElementById('deleteEmployeeId').value = employeeId;
        document.getElementById('deleteForm').submit();
    }
}

function reactivateEmployee(employeeId, employeeName) {
    if (confirm(`Are you sure you want to reactivate employee "${employeeName}"?`)) {
        document.getElementById('reactivateEmployeeId').value = employeeId;
        document.getElementById('reactivateForm').submit();
    }
}

// Reset modal when closed
document.getElementById('employeeModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('employeeForm').reset();
    document.getElementById('employeeModalTitle').textContent = 'Add New Employee';
    document.getElementById('employee_code').readOnly = false;
    document.getElementById('submitBtn').textContent = 'Add Employee';
    document.getElementById('submitBtn').name = 'add_employee';
});
</script>

<?php include 'includes/footer.php'; ?>
