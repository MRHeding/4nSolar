-- Payroll System Database Structure
-- Created for 4NSOLAR ELECTRICZ Management System

-- Table structure for employees
CREATE TABLE `employees` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for employee attendance
CREATE TABLE `employee_attendance` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for payroll records
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `total_working_days` int(11) DEFAULT 0,
  `working_days_present` int(11) DEFAULT 0,
  `leaves_taken` int(11) DEFAULT 0,
  `balance_leaves` int(11) DEFAULT 0,
  
  -- Salary Components
  `basic_salary` decimal(10,2) DEFAULT 0.00,
  `package_salary_1500` decimal(10,2) DEFAULT 0.00,
  `package_salary_2500` decimal(10,2) DEFAULT 0.00,
  `package_salary_3500` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `bonus_pay` decimal(10,2) DEFAULT 0.00,
  
  -- Deductions
  `cash_advance` decimal(10,2) DEFAULT 0.00,
  `uniforms` decimal(10,2) DEFAULT 0.00,
  `tools` decimal(10,2) DEFAULT 0.00,
  `lates` decimal(10,2) DEFAULT 0.00,
  `miscellaneous` decimal(10,2) DEFAULT 0.00,
  
  -- Totals
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for payroll deduction items (for flexible deductions)
CREATE TABLE `payroll_deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_id` int(11) NOT NULL,
  `deduction_type` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  INDEX `idx_payroll_deductions` (`payroll_id`),
  FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for leave management
CREATE TABLE `employee_leaves` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample employees
INSERT INTO `employees` (`employee_code`, `employee_name`, `position`, `date_of_joining`, `basic_salary`, `package_salary_1500`, `package_salary_2500`, `package_salary_3500`, `allowances`) VALUES
('EMP-001', 'Prudencio Garcia', 'PV Solar Installer', '2025-01-01', 400.00, 1500.00, 2500.00, 3500.00, 0.00),
('EMP-002', 'John Doe', 'Technician', '2025-02-01', 450.00, 1500.00, 2500.00, 3500.00, 200.00),
('EMP-003', 'Jane Smith', 'Sales Representative', '2025-03-01', 500.00, 1500.00, 2500.00, 3500.00, 300.00);

-- Insert sample attendance for current month
INSERT INTO `employee_attendance` (`employee_id`, `attendance_date`, `time_in`, `time_out`, `status`, `hours_worked`) VALUES
(1, '2025-09-01', '08:00:00', '17:00:00', 'present', 8.00),
(1, '2025-09-02', '08:00:00', '17:00:00', 'present', 8.00),
(1, '2025-09-03', '08:30:00', '17:00:00', 'late', 7.50),
(2, '2025-09-01', '08:00:00', '17:00:00', 'present', 8.00),
(2, '2025-09-02', '08:00:00', '17:00:00', 'present', 8.00),
(3, '2025-09-01', '08:00:00', '17:00:00', 'present', 8.00),
(3, '2025-09-02', NULL, NULL, 'absent', 0.00);
