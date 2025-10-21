-- Clean Payroll System - Complete Reset
-- This script will permanently delete ALL payroll-related data
-- Use with caution - this action cannot be undone!

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Clear all payroll-related tables in the correct order
-- 1. Clear payroll deductions first (has foreign key to payroll)
DELETE FROM payroll_deductions;

-- 2. Clear payroll records
DELETE FROM payroll;

-- 3. Clear employee attendance records
DELETE FROM employee_attendance;

-- 4. Clear employee leave records
DELETE FROM employee_leaves;

-- 5. Clear employees table
DELETE FROM employees;

-- Reset auto-increment counters
ALTER TABLE employees AUTO_INCREMENT = 1;
ALTER TABLE employee_attendance AUTO_INCREMENT = 1;
ALTER TABLE payroll AUTO_INCREMENT = 1;
ALTER TABLE payroll_deductions AUTO_INCREMENT = 1;
ALTER TABLE employee_leaves AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show completion message
SELECT 'Payroll system has been completely reset!' as message;
SELECT 'All payroll data has been cleared and the system is now fresh.' as status;
