<?php
/**
 * Direct Payroll Tables Setup Script
 * Alternative migration approach using direct SQL execution
 */

require_once 'includes/config.php';

// Set execution time limit
set_time_limit(300);

$tables_created = [];
$errors = [];

try {
    // 1. Create employees table
    $sql_employees = "
    CREATE TABLE IF NOT EXISTS `employees` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_code` varchar(20) NOT NULL UNIQUE,
      `employee_name` varchar(100) NOT NULL,
      `position` varchar(100) NOT NULL,
      `date_of_joining` date NOT NULL,
      `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
      `package_salary_1500` decimal(10,2) DEFAULT 0.00,
      `package_salary_2500` decimal(10,2) DEFAULT 0.00,
      `package_salary_3500` decimal(10,2) DEFAULT 0.00,
      `allowances` decimal(10,2) DEFAULT 0.00,
      `is_active` tinyint(1) DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      INDEX `idx_employee_code` (`employee_code`),
      INDEX `idx_employee_name` (`employee_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql_employees);
    $tables_created[] = 'employees';
    
    // 2. Create employee_attendance table
    $sql_attendance = "
    CREATE TABLE IF NOT EXISTS `employee_attendance` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` int(11) NOT NULL,
      `attendance_date` date NOT NULL,
      `time_in` time DEFAULT NULL,
      `time_out` time DEFAULT NULL,
      `status` enum('present','absent','late','half_day','overtime') NOT NULL DEFAULT 'present',
      `hours_worked` decimal(4,2) DEFAULT 0.00,
      `overtime_hours` decimal(4,2) DEFAULT 0.00,
      `notes` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_employee_date` (`employee_id`, `attendance_date`),
      INDEX `idx_attendance_date` (`attendance_date`),
      INDEX `idx_employee_attendance` (`employee_id`, `attendance_date`),
      FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql_attendance);
    $tables_created[] = 'employee_attendance';
    
    // 3. Create payroll table
    $sql_payroll = "
    CREATE TABLE IF NOT EXISTS `payroll` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` int(11) NOT NULL,
      `pay_period_start` date NOT NULL,
      `pay_period_end` date NOT NULL,
      `total_working_days` int(11) DEFAULT 0,
      `working_days_present` int(11) DEFAULT 0,
      `leaves_taken` int(11) DEFAULT 0,
      `balance_leaves` int(11) DEFAULT 0,
      `basic_salary` decimal(10,2) DEFAULT 0.00,
      `package_salary_1500` decimal(10,2) DEFAULT 0.00,
      `package_salary_2500` decimal(10,2) DEFAULT 0.00,
      `package_salary_3500` decimal(10,2) DEFAULT 0.00,
      `allowances` decimal(10,2) DEFAULT 0.00,
      `overtime_pay` decimal(10,2) DEFAULT 0.00,
      `bonus_pay` decimal(10,2) DEFAULT 0.00,
      `cash_advance` decimal(10,2) DEFAULT 0.00,
      `uniforms` decimal(10,2) DEFAULT 0.00,
      `tools` decimal(10,2) DEFAULT 0.00,
      `lates` decimal(10,2) DEFAULT 0.00,
      `miscellaneous` decimal(10,2) DEFAULT 0.00,
      `gross_salary` decimal(10,2) DEFAULT 0.00,
      `total_deductions` decimal(10,2) DEFAULT 0.00,
      `net_salary` decimal(10,2) DEFAULT 0.00,
      `status` enum('draft','approved','paid') DEFAULT 'draft',
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` timestamp NULL DEFAULT NULL,
      `paid_at` timestamp NULL DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      INDEX `idx_employee_payroll` (`employee_id`),
      INDEX `idx_pay_period` (`pay_period_start`, `pay_period_end`),
      INDEX `idx_status` (`status`),
      FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
      FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql_payroll);
    $tables_created[] = 'payroll';
    
    // 4. Create payroll_deductions table
    $sql_deductions = "
    CREATE TABLE IF NOT EXISTS `payroll_deductions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `payroll_id` int(11) NOT NULL,
      `deduction_type` varchar(50) NOT NULL,
      `description` varchar(255) DEFAULT NULL,
      `amount` decimal(10,2) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      INDEX `idx_payroll_deductions` (`payroll_id`),
      FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql_deductions);
    $tables_created[] = 'payroll_deductions';
    
    // 5. Create employee_leaves table
    $sql_leaves = "
    CREATE TABLE IF NOT EXISTS `employee_leaves` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` int(11) NOT NULL,
      `leave_type` enum('sick','vacation','emergency','maternity','paternity','bereavement','other') NOT NULL,
      `start_date` date NOT NULL,
      `end_date` date NOT NULL,
      `days_count` int(11) NOT NULL,
      `reason` text DEFAULT NULL,
      `status` enum('pending','approved','rejected') DEFAULT 'pending',
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` timestamp NULL DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      INDEX `idx_employee_leaves` (`employee_id`),
      INDEX `idx_leave_dates` (`start_date`, `end_date`),
      INDEX `idx_leave_status` (`status`),
      FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql_leaves);
    $tables_created[] = 'employee_leaves';
    
    // 6. Create payroll_packages table for dynamic packages
    $sql_packages = "
    CREATE TABLE IF NOT EXISTS `payroll_packages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `payroll_id` int(11) NOT NULL,
      `package_name` varchar(100) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      INDEX `idx_payroll_packages` (`payroll_id`),
      FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql_packages);
    $tables_created[] = 'payroll_packages';
    
    // Remove package salary columns from employees table if they exist
    try {
        $pdo->exec("ALTER TABLE employees DROP COLUMN package_salary_1500");
    } catch (Exception $e) {
        // Column might not exist, ignore error
    }
    try {
        $pdo->exec("ALTER TABLE employees DROP COLUMN package_salary_2500");
    } catch (Exception $e) {
        // Column might not exist, ignore error
    }
    try {
        $pdo->exec("ALTER TABLE employees DROP COLUMN package_salary_3500");
    } catch (Exception $e) {
        // Column might not exist, ignore error
    }
    
    // Remove package salary columns from payroll table if they exist
    try {
        $pdo->exec("ALTER TABLE payroll DROP COLUMN package_salary_1500");
    } catch (Exception $e) {
        // Column might not exist, ignore error
    }
    try {
        $pdo->exec("ALTER TABLE payroll DROP COLUMN package_salary_2500");
    } catch (Exception $e) {
        // Column might not exist, ignore error
    }
    try {
        $pdo->exec("ALTER TABLE payroll DROP COLUMN package_salary_3500");
    } catch (Exception $e) {
        // Column might not exist, ignore error
    }
    
    // Insert sample data
    // Check if employees already exist
    $check = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    
    if ($check == 0) {
        // Insert sample employees
        $sample_employees = [
            ['EMP-001', 'Prudencio Garcia', 'PV Solar Installer', '2025-01-01', 400.00, 0.00],
            ['EMP-002', 'John Doe', 'Technician', '2025-02-01', 450.00, 200.00],
            ['EMP-003', 'Jane Smith', 'Sales Representative', '2025-03-01', 500.00, 300.00]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO employees (employee_code, employee_name, position, date_of_joining, basic_salary, allowances) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($sample_employees as $emp) {
            $stmt->execute($emp);
        }
        
        $tables_created[] = 'sample_employees_inserted';
        
        // Insert sample attendance
        $sample_attendance = [
            [1, '2025-09-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00],
            [1, '2025-09-02', '08:00:00', '17:00:00', 'present', 8.00, 0.00],
            [1, '2025-09-03', '08:30:00', '17:00:00', 'late', 7.50, 0.00],
            [2, '2025-09-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00],
            [2, '2025-09-02', '08:00:00', '17:00:00', 'present', 8.00, 0.00],
            [3, '2025-09-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00],
            [3, '2025-09-02', null, null, 'absent', 0.00, 0.00]
        ];
        
        $stmt_att = $pdo->prepare("INSERT INTO employee_attendance (employee_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sample_attendance as $att) {
            $stmt_att->execute($att);
        }
        
        $tables_created[] = 'sample_attendance_inserted';
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Setup - 4NSOLAR ELECTRICZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-database me-2"></i>Payroll System Setup</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($errors)): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Setup Completed Successfully!</h5>
                                <p class="mb-0">The payroll system has been set up successfully.</p>
                            </div>
                            
                            <h6>Tables Created/Updated:</h6>
                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($tables_created as $table): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-check text-success me-2"></i><?php echo htmlspecialchars($table); ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Setup Errors</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="payroll.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right me-2"></i>Go to Payroll System
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
