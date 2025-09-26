-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2025 at 06:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `4nsolar_inventory`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_next_serial_number` (`p_item_id` INT) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
            DECLARE v_next INT;
            SELECT next_serial_number INTO v_next 
            FROM inventory_items 
            WHERE id = p_item_id;
            RETURN IFNULL(v_next, 1);
        END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `available_serials`
-- (See below for the actual view)
--
CREATE TABLE `available_serials` (
`inventory_item_id` int(11)
,`brand` varchar(100)
,`model` varchar(100)
,`category_id` int(11)
,`category_name` varchar(50)
,`size_specification` varchar(100)
,`selling_price` decimal(10,2)
,`available_serials_count` bigint(21)
,`available_serial_numbers` mediumtext
);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `is_active`) VALUES
(1, 'Solar Panels', 'Photovoltaic solar panels of various types and capacities', '2025-08-26 02:04:18', 1),
(2, 'Inverters', 'DC to AC power inverters for solar systems', '2025-08-26 02:04:18', 1),
(3, 'Batteries', 'Energy storage systems and batteries', '2025-08-26 02:04:18', 1),
(4, 'Mounting Systems', 'Roof and ground mounting hardware', '2025-08-26 02:04:18', 1),
(5, 'Cables & Wiring', 'DC and AC cables, connectors, and wiring components', '2025-08-26 02:04:18', 1),
(6, 'Monitoring Systems', 'System monitoring and control equipment', '2025-08-26 02:04:18', 1),
(7, 'Safety Equipment', 'Fuses, breakers, and safety devices', '2025-08-26 02:04:18', 1),
(8, 'Tools & Accessories', 'Installation tools and miscellaneous accessories', '2025-08-26 02:04:18', 1),
(9, 'Hybrid Inverter', 'Hybrid inverters that can work with both solar panels and batteries, providing grid-tie and backup functionality', '2025-09-02 06:40:54', 1);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_code` varchar(20) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `date_of_joining` date NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `employee_name`, `position`, `date_of_joining`, `basic_salary`, `allowances`, `is_active`, `created_at`, `updated_at`) VALUES
(21, '2025-001', 'Liddy Lou Orsuga', 'HR Admin', '2025-08-12', 400.00, 0.00, 1, '2025-09-20 02:23:17', '2025-09-20 02:23:17'),
(22, '2025-008', 'Ma.Donna E. Pingoy', 'Sales Consultant', '2025-08-18', 350.00, 0.00, 1, '2025-09-20 02:31:40', '2025-09-20 02:31:40'),
(23, '2025-009', 'Moh.Rasheed M. Heding', 'It Specialist', '2025-08-21', 400.00, 0.00, 1, '2025-09-20 02:33:19', '2025-09-20 02:33:19'),
(24, '2025-003', 'Prudencio Garcia', 'PV Solar Installer', '2024-01-01', 400.00, 0.00, 1, '2025-09-20 02:34:34', '2025-09-20 02:34:34'),
(25, '2025-004', 'Oliver S. Arapan', 'PV Solar Installer', '2025-06-15', 400.00, 0.00, 1, '2025-09-20 02:35:41', '2025-09-20 02:35:41'),
(26, '2025-005', 'Ar-Jay Ventura', 'PV Solar Installer', '2025-08-06', 400.00, 0.00, 1, '2025-09-20 02:36:11', '2025-09-20 02:36:11'),
(27, '2025-006', 'Rembranth Dollete', 'PV Solar Installer', '2025-08-11', 300.00, 0.00, 1, '2025-09-20 02:36:55', '2025-09-20 02:36:55'),
(28, '2025-007', 'Aris Ho', 'PV Solar Installer', '2025-08-11', 400.00, 0.00, 1, '2025-09-20 02:37:23', '2025-09-20 02:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `employee_attendance`
--

CREATE TABLE `employee_attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half_day','overtime') NOT NULL DEFAULT 'present',
  `hours_worked` decimal(4,2) DEFAULT 0.00,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_leaves`
--

CREATE TABLE `employee_leaves` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `installment_payments`
--

CREATE TABLE `installment_payments` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `installment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `due_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `late_fee_applied` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('cash','check','bank_transfer','gcash','paymaya','card','other') DEFAULT 'cash',
  `reference_number` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','paid','partial','overdue','waived') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `installment_payments`
--

INSERT INTO `installment_payments` (`id`, `plan_id`, `installment_number`, `due_date`, `due_amount`, `paid_amount`, `payment_date`, `late_fee_applied`, `payment_method`, `reference_number`, `receipt_number`, `status`, `notes`, `paid_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-10-16', 11772.89, 11772.89, '2025-09-16', 0.00, 'cash', 'PAY-QTE20250909-9751-I1-20250916-7609', 'RCP-20250916-0001', 'paid', '', 1, '2025-09-16 07:19:57', '2025-09-16 07:34:46'),
(2, 1, 2, '2025-11-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(3, 1, 3, '2025-12-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(4, 1, 4, '2026-01-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(5, 1, 5, '2026-02-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(6, 1, 6, '2026-03-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(7, 1, 7, '2026-04-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(8, 1, 8, '2026-05-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(9, 1, 9, '2026-06-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(10, 1, 10, '2026-07-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(11, 1, 11, '2026-08-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(12, 1, 12, '2026-09-16', 11772.89, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(13, 2, 1, '2025-10-17', 13753.06, 13753.06, '2025-09-17', 0.00, 'cash', 'PAY-QTE20250917-6989-I1-20250917-7173', 'RCP-20250917-0001', 'paid', '', 1, '2025-09-17 01:29:19', '2025-09-17 01:29:48'),
(14, 2, 2, '2025-11-17', 13753.06, 13753.06, '2025-09-17', 0.00, 'cash', 'PAY-QTE20250917-6989-I2-20250917-5331', 'RCP-20250917-0002', 'paid', '', 1, '2025-09-17 01:29:19', '2025-09-17 01:34:08'),
(15, 2, 3, '2025-12-17', 13753.06, 13753.06, '2025-09-17', 0.00, 'cash', 'PAY-QTE20250917-6989-I3-20250917-7835', 'RCP-20250917-0003', 'paid', '', 1, '2025-09-17 01:29:19', '2025-09-17 01:34:12'),
(16, 2, 4, '2026-01-17', 13753.06, 13753.06, '2025-09-17', 0.00, 'cash', 'PAY-QTE20250917-6989-I4-20250917-0681', 'RCP-20250917-0004', 'paid', '', 1, '2025-09-17 01:29:19', '2025-09-17 01:34:15'),
(17, 2, 5, '2026-02-17', 13753.06, 13753.06, '2025-09-17', 0.00, 'cash', 'PAY-QTE20250917-6989-I5-20250917-1997', 'RCP-20250917-0005', 'paid', '', 1, '2025-09-17 01:29:19', '2025-09-17 01:34:24'),
(18, 2, 6, '2026-03-17', 13753.06, 13753.06, '2025-09-17', 0.00, 'cash', 'PAY-QTE20250917-6989-I6-20250917-9005', 'RCP-20250917-0006', 'paid', '', 1, '2025-09-17 01:29:19', '2025-09-17 01:34:40'),
(19, 3, 1, '2025-10-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(20, 3, 2, '2025-11-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(21, 3, 3, '2025-12-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(22, 3, 4, '2026-01-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(23, 3, 5, '2026-02-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(24, 3, 6, '2026-03-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(25, 3, 7, '2026-04-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(26, 3, 8, '2026-05-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(27, 3, 9, '2026-06-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(28, 3, 10, '2026-07-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(29, 3, 11, '2026-08-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59'),
(30, 3, 12, '2026-09-17', 16867.88, 0.00, NULL, 0.00, 'cash', NULL, NULL, 'pending', NULL, NULL, '2025-09-17 05:11:59', '2025-09-17 05:11:59');

-- --------------------------------------------------------

--
-- Table structure for table `installment_plans`
--

CREATE TABLE `installment_plans` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL DEFAULT 'Payment Plan',
  `total_amount` decimal(15,2) NOT NULL,
  `down_payment` decimal(15,2) DEFAULT 0.00,
  `installment_amount` decimal(15,2) NOT NULL,
  `number_of_installments` int(11) NOT NULL,
  `payment_frequency` enum('weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
  `interest_rate` decimal(5,2) DEFAULT 0.00,
  `late_fee_amount` decimal(10,2) DEFAULT 0.00,
  `late_fee_type` enum('fixed','percentage') DEFAULT 'fixed',
  `start_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','completed','cancelled','suspended') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `installment_plans`
--

INSERT INTO `installment_plans` (`id`, `quotation_id`, `plan_name`, `total_amount`, `down_payment`, `installment_amount`, `number_of_installments`, `payment_frequency`, `interest_rate`, `late_fee_amount`, `late_fee_type`, `start_date`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 17, 'Payment Plan for Andres Bonifacio', 174225.00, 34845.00, 11772.89, 12, 'monthly', 2.50, 500.00, 'fixed', '2025-10-16', '', 'active', 1, '2025-09-16 07:19:57', '2025-09-16 07:19:57'),
(2, 20, 'Installment Plan for Juan Dela Cruz', 102400.00, 20480.00, 13753.06, 6, 'monthly', 2.50, 500.00, 'fixed', '2025-10-17', '', 'completed', 1, '2025-09-17 01:29:19', '2025-09-17 01:34:40'),
(3, 18, 'Payment Plan for Pazlor Lim', 249700.00, 50000.00, 16867.88, 12, 'monthly', 2.50, 500.00, 'fixed', '2025-10-17', '', 'active', 1, '2025-09-17 05:11:59', '2025-09-17 05:11:59');

-- --------------------------------------------------------

--
-- Table structure for table `installment_settings`
--

CREATE TABLE `installment_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `installment_settings`
--

INSERT INTO `installment_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'default_interest_rate', '2.5', 'Default annual interest rate percentage', NULL, '2025-09-16 07:18:53'),
(2, 'default_late_fee', '500.00', 'Default late fee amount in PHP', NULL, '2025-09-16 07:18:53'),
(3, 'late_fee_type', 'fixed', 'Default late fee type (fixed or percentage)', NULL, '2025-09-16 07:18:53'),
(4, 'grace_period_days', '5', 'Days after due date before late fee applies', NULL, '2025-09-16 07:18:53'),
(5, 'min_down_payment_percent', '20', 'Minimum down payment percentage required', NULL, '2025-09-16 07:18:53'),
(6, 'max_installment_months', '36', 'Maximum number of installment months allowed', NULL, '2025-09-16 07:18:53'),
(7, 'auto_generate_receipts', '1', 'Automatically generate receipt numbers for payments', NULL, '2025-09-16 07:18:53'),
(8, 'payment_reminder_days', '3', 'Days before due date to send payment reminders', NULL, '2025-09-16 07:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `installment_transactions`
--

CREATE TABLE `installment_transactions` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `transaction_type` enum('payment','late_fee','adjustment','refund') DEFAULT 'payment',
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `installment_transactions`
--

INSERT INTO `installment_transactions` (`id`, `payment_id`, `transaction_type`, `amount`, `description`, `transaction_date`, `processed_by`, `reference_number`, `receipt_path`) VALUES
(1, 1, 'payment', 11772.89, 'Payment for installment #1 - Cash', '2025-09-16 15:34:46', 1, 'PAY-QTE20250909-9751-I1-20250916-7609', NULL),
(2, 13, 'payment', 13753.06, 'Payment for installment #1 - Cash', '2025-09-17 09:29:48', 1, 'PAY-QTE20250917-6989-I1-20250917-7173', NULL),
(3, 14, 'payment', 13753.06, 'Payment for installment #2 - Cash', '2025-09-17 09:34:08', 1, 'PAY-QTE20250917-6989-I2-20250917-5331', NULL),
(4, 15, 'payment', 13753.06, 'Payment for installment #3 - Cash', '2025-09-17 09:34:12', 1, 'PAY-QTE20250917-6989-I3-20250917-7835', NULL),
(5, 16, 'payment', 13753.06, 'Payment for installment #4 - Cash', '2025-09-17 09:34:15', 1, 'PAY-QTE20250917-6989-I4-20250917-0681', NULL),
(6, 17, 'payment', 13753.06, 'Payment for installment #5 - Cash', '2025-09-17 09:34:24', 1, 'PAY-QTE20250917-6989-I5-20250917-1997', NULL),
(7, 18, 'payment', 13753.06, 'Payment for installment #6 - Cash', '2025-09-17 09:34:40', 1, 'PAY-QTE20250917-6989-I6-20250917-9005', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `inventory`
-- (See below for the actual view)
--
CREATE TABLE `inventory` (
`id` int(11)
,`brand` varchar(100)
,`model` varchar(100)
,`category_id` int(11)
,`size_specification` varchar(100)
,`base_price` decimal(10,2)
,`selling_price` decimal(10,2)
,`discount_percentage` decimal(5,2)
,`supplier_id` int(11)
,`stock_quantity` int(11)
,`minimum_stock` int(11)
,`description` text
,`image_path` varchar(255)
,`specifications` longtext
,`created_at` timestamp
,`updated_at` timestamp
,`created_by` int(11)
,`is_active` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `size_specification` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `minimum_stock` int(11) DEFAULT 10,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `generate_serials` tinyint(1) DEFAULT 0 COMMENT 'Whether to generate serial numbers for this item',
  `serial_prefix` varchar(20) DEFAULT NULL COMMENT 'Prefix for serial numbers (e.g., INV, BAT, PAN)',
  `serial_format` varchar(50) DEFAULT 'YYYY-NNNNNN' COMMENT 'Format for serial numbers',
  `next_serial_number` int(11) DEFAULT 1 COMMENT 'Next serial number to generate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `brand`, `model`, `category_id`, `size_specification`, `base_price`, `selling_price`, `discount_percentage`, `supplier_id`, `stock_quantity`, `minimum_stock`, `description`, `image_path`, `specifications`, `created_at`, `updated_at`, `created_by`, `is_active`, `generate_serials`, `serial_prefix`, `serial_format`, `next_serial_number`) VALUES
(22, 'Canadian Solar', 'MONO 375w', 1, '375w', 3900.00, 3900.00, 0.00, 6, 2, 0, '', 'images/products/product_68b5592fcb891.jpg', NULL, '2025-09-01 08:28:31', '2025-09-20 08:34:02', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 7),
(23, 'Canadian Solar', 'MONO 410w (CS6-410MS)', 1, '410w', 4000.00, 4000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55986364b4.jpg', NULL, '2025-09-01 08:29:58', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(24, 'Canadian Solar', 'MONO 455w (CS6L-455MS)', 1, '455w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b559e773356.jpg', NULL, '2025-09-01 08:31:23', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(25, 'Canadian Solar', 'MONO 545w (CS6W-545MS)', 1, '545w', 4200.00, 4200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55b6116f30.jpg', NULL, '2025-09-01 08:37:53', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(26, 'Canadian Solar', 'MONO 550w (CS6W-550MS)', 1, '550w', 4400.00, 4400.00, 0.00, 6, 12, 0, '', 'images/products/product_68b55bb655a9b.jpg', NULL, '2025-09-01 08:39:18', '2025-09-20 08:32:56', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 3),
(27, 'Canadian Solar', 'MONO 555w (CS6L-555MS)', 1, '555w', 4400.00, 4400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55c8c4d739.jpg', NULL, '2025-09-01 08:41:35', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(28, 'Canadian Solar', 'MONO 580w (CS6W-580TB-AG)', 1, '580w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55d1277ecb.jpg', NULL, '2025-09-01 08:45:06', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(29, 'Canadian Solar', 'MONO 585w (CS6W-585TB-AG)', 1, '585w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55d64d1b99.jpg', NULL, '2025-09-01 08:46:28', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(30, 'Canadian Solar', 'MONO 600w (CS6.1-72TB600)', 1, '600w', 4200.00, 4800.00, 0.00, 6, 10, 10, '', 'images/products/product_68b55dfe7d0ad.jpg', NULL, '2025-09-01 08:49:02', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(31, 'Canadian Solar', 'MONO 605w (CS6.1-72TB605)', 1, '605w', 4300.00, 4300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6679bb2663.jpg', NULL, '2025-09-02 03:42:02', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(32, 'Canadian Solar', 'MONO 610w (CS6.1-72TB-610)', 1, '610w', 4400.00, 4400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b667e54b564.jpg', NULL, '2025-09-02 03:43:33', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(33, 'Canadian Solar', 'MONO 615w (CS6.2-66TB-615)', 1, '615w', 4500.00, 4500.00, 0.00, 6, 2, 0, '', 'images/products/product_68b6684e50bec.jpg', NULL, '2025-09-02 03:45:18', '2025-09-22 03:08:15', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 19),
(34, 'Canadian Solar', 'MONO 620w (CS6.2-66TB-620)', 1, '620w', 4600.00, 4600.00, 0.00, 6, 0, 0, '', 'images/products/product_68b668d4a4eea.jpg', NULL, '2025-09-02 03:47:32', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(35, 'Canadian Solar', 'MONO 650w (CS7N-650wMB-AG)', 1, '650w', 4500.00, 4500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68562be9f1.jpg', NULL, '2025-09-02 05:49:22', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(36, 'Canadian Solar', 'MONO 700w (CS7N-700TB-AG)', 1, '700w', 4800.00, 4800.00, 0.00, 6, 0, 0, '', 'images/products/product_68b685c801086.jpg', NULL, '2025-09-02 05:51:04', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(37, 'OSDA', 'MONO 455w (ODA455-30V-MH)', 1, '445w', 3200.00, 3200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68690a379c.jpg', NULL, '2025-09-02 05:54:24', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(38, 'OSDA', 'MONO 500w (ODA500-33V-MH)', 1, '500w', 3500.00, 3500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b686c48d8f0.jpg', NULL, '2025-09-02 05:55:16', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(39, 'OSDA', 'MONO 550w (ODA550-36V-MHD)', 1, '550w', 3650.00, 4300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68707dbc50.jpg', NULL, '2025-09-02 05:56:23', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(40, 'OSDA', 'MONO 580w (ODA580-36VMHD)', 1, '580w', 3700.00, 3700.00, 0.00, 6, 0, 0, '', 'images/products/product_68b687510d884.jpg', NULL, '2025-09-02 05:57:25', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(41, 'OSDA', 'MONO 590w (ODA590-36VMHD)', 1, '590w', 3800.00, 4800.00, 0.00, 6, 14, 0, '', 'images/products/product_68b6878bd32d4.jpg', NULL, '2025-09-02 05:58:35', '2025-09-20 03:06:59', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(42, 'OSDA', 'MONO 610w (ODA610-33VMHDRz)', 1, '610w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b687d040151.jpg', NULL, '2025-09-02 05:59:44', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(43, 'OSDA', 'MONO 620w (ODA620-33VMHDRz)', 1, '620w', 4200.00, 4200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b688161eb98.jpg', NULL, '2025-09-02 06:00:54', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(44, 'OSDA', 'MONO 700w (ODA700-33V-MHD)', 1, '700w', 4700.00, 4700.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68841e8842.jpg', NULL, '2025-09-02 06:01:29', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(45, 'SUNRI', '20w', 1, '20w', 900.00, 900.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68970884c9.jpg', NULL, '2025-09-02 06:06:31', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(46, 'SUNRI', '100w', 1, '100w', 1700.00, 1700.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6899db8796.jpg', NULL, '2025-09-02 06:07:25', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(47, 'SUNRI', '150w', 1, '150w', 2400.00, 2400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b689c21416a.jpg', NULL, '2025-09-02 06:08:02', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(48, 'SUNRI', '200w', 1, '200w', 2700.00, 2700.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68a7259c1d.jpg', NULL, '2025-09-02 06:08:51', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(49, 'SUNRI', '340w', 1, '340w', 3300.00, 3300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68a9c346b1.jpg', NULL, '2025-09-02 06:11:40', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(50, 'SUNRI', '350w', 1, '350w', 3400.00, 3400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ac7bad32.jpg', NULL, '2025-09-02 06:12:23', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(51, 'DEYE', '3.6kw (SUN-3.6k-SG04LP1-EU)', 9, '3.6kw', 39610.00, 39610.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68e759001e.jpg', NULL, '2025-09-02 06:28:05', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(52, 'DEYE', '5kw (SUN-5k-SG04LP1-EU-P)', 9, '5kw', 45268.00, 45268.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ebb2c6a1.jpg', NULL, '2025-09-02 06:29:15', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(53, 'DEYE', '5kw (SUN-5k-SG04LP1-EU-SM2)', 9, '5kw', 41732.00, 41732.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ef614a2c.jpg', NULL, '2025-09-02 06:30:14', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(54, 'DEYE', '6kw (SUN-6k-SG04LP1-EU)', 9, '6kw', 42300.00, 42300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68f2e65d10.jpg', NULL, '2025-09-02 06:31:10', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(55, 'DEYE', '8kw (SUN-8k-SG04LP1-EU)', 9, '8kw', 42300.00, 42300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68f768dd36.jpg', NULL, '2025-09-02 06:32:22', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(56, 'DEYE', '8kw (SUN-8k-SG05LP1-SM2)', 9, '8kw', 58200.00, 69000.00, 0.00, 6, 1, 0, '', 'images/products/product_68b68fae52409.jpg', NULL, '2025-09-02 06:33:18', '2025-09-22 03:06:57', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 3),
(57, 'DEYE', '10kw (SUN-10k-SG04LP1-EU-AM3)', 9, '10kw', 58200.00, 58200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ff3f3218.jpg', NULL, '2025-09-02 06:34:27', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(58, 'DEYE', '12kw (SUN-12k-SG04LP1-EU-AM3)', 9, '12kw', 84500.00, 95000.00, 0.00, 6, 10, 0, '', 'images/products/product_68b6903e0c30b.jpg', NULL, '2025-09-02 06:35:26', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(59, 'DEYE', '16kw (SUN-16k-SG04LP1-EU)', 9, '16kw', 109000.00, 115000.00, 0.00, 6, 1, 0, '', 'images/products/product_68b69074839d7.jpg', NULL, '2025-09-02 06:36:36', '2025-09-22 02:38:54', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 2),
(60, 'DEYE', '12kw (SUN-12K-SG04LP3-EU)', 9, '12kw', 94500.00, 94500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b691d5d5f8b.jpg', NULL, '2025-09-02 06:42:29', '2025-09-19 09:00:16', 1, 0, 1, 'HYB', 'YYYY-NNNNNN', 1),
(61, 'DEYE', '20kw (SUN-20K-SG01HP3-EU-AM2)', 9, '20kw', 149000.00, 149000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69241e2389.jpg', NULL, '2025-09-02 06:44:17', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(62, 'DEYE', '30kw (SUN-30K-SG01HP3-EU-AM2)', 9, '30kw', 229000.00, 229000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6927262bbc.jpg', NULL, '2025-09-02 06:45:06', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(63, 'DEYE', '50kw (SUN-50K-SG01HP3-EU-BM4)', 9, '50kw', 279000.00, 279000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b692aa8143f.jpg', NULL, '2025-09-02 06:46:02', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(64, 'Eastron', 'Eastron Smart Meter (w/ 3CT)', 6, '(w/ 3CT)', 11000.00, 11000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b693af922dc.jpg', NULL, '2025-09-02 06:50:23', '2025-09-02 06:50:23', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(65, 'SRNE', '6kw (HESP48DS100-H)', 9, '6kw', 35000.00, 35000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69a460f8ce.jpg', NULL, '2025-09-02 07:18:30', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(66, 'SRNE', '12kw (HESP4812DS200-H)', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69a84474f4.jpg', NULL, '2025-09-02 07:19:32', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(67, 'SRNE', '12kw (HESP1203SH3) HV', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69eb22f264.jpg', NULL, '2025-09-02 07:37:22', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(68, 'SRNE', '12kw (HESP4812DSH3) LV', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69ef86b1d6.jpg', NULL, '2025-09-02 07:38:32', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(71, 'FEE0 ', 'ATS 63A', 7, '63A', 2000.00, 2500.00, 0.00, 6, 1, 1, '', 'images/products/product_68b7c6b27bd0a.png', NULL, '2025-09-03 04:40:18', '2025-09-17 08:10:55', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(72, 'OSDA', 'ODA590-36VMHD', 1, '590w', 3000.00, 3800.00, 0.00, 6, 14, 10, '', 'images/products/product_68b7cc01c94dc.jpg', NULL, '2025-09-03 05:02:57', '2025-09-19 09:00:16', 9, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(73, 'LABOR', 'Labor Fee', 1, 'Per KW', 0.00, 0.00, 0.00, 6, 2147474047, 0, 'Labor fee calculation item', 'images/products/product_68ce190e82804.jpg', NULL, '2025-09-04 00:56:42', '2025-09-20 08:27:39', NULL, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(74, 'FEE0 ', 'Breaker DC 16A', 7, '16A', 250.00, 450.00, 0.00, 5, 6, 10, 'DC', 'images/products/product_68ca7e56596ce.jpg', NULL, '2025-09-04 01:11:42', '2025-09-17 09:24:38', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(75, 'Terminal Lugs', 'SC35-8', 7, '35-8', 40.00, 55.00, 0.00, 5, 14, 10, 'No brand', 'images/products/product_68ba88a68da77.jpg', NULL, '2025-09-04 01:25:40', '2025-09-17 07:57:25', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(76, 'FEE0 ', 'Breaker  63 DC 40A ', 7, '40A', 400.00, 650.00, 0.00, 5, 3, 10, '', 'images/products/product_68ca754b15913.jpg', NULL, '2025-09-04 01:29:27', '2025-09-17 08:46:03', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(77, 'FEE0 ', ' Breaker DC 20A ', 7, '20A', 400.00, 650.00, 0.00, 5, 7, 10, '', 'images/products/product_68ca73d45f1eb.jpg', NULL, '2025-09-04 01:30:58', '2025-09-17 08:39:48', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(79, 'FEE0 ', 'Breaker DC 125A', 7, '125A', 950.00, 1500.00, 0.00, 5, 3, 10, '', 'images/products/product_68ca74d0ce436.jpg', NULL, '2025-09-04 01:34:44', '2025-09-20 07:10:55', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(80, 'FEE0 ', 'Breaker AC 32A', 7, 'AC 32A', 200.00, 650.00, 0.00, 5, 5, 10, '', 'images/products/product_68ca774556cf6.jpg', NULL, '2025-09-04 01:36:48', '2025-09-17 08:54:29', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(81, 'FEE0 ', 'Breaker AC C63', 7, 'C63', 450.00, 650.00, 0.00, 5, 7, 10, '', 'images/products/product_68ca778687350.jpg', NULL, '2025-09-04 01:39:28', '2025-09-17 08:55:34', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(82, 'FEE0 ', 'BREAKER AC 16A', 7, 'C16', 200.00, 650.00, 0.00, 5, 3, 10, '', 'images/products/product_68ca75e8cf35c.jpg', NULL, '2025-09-04 01:40:31', '2025-09-17 08:48:40', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(83, 'FEE0 ', 'Breaker DC 80A', 7, '80A', 950.00, 1500.00, 0.00, 5, 1, 5, '', 'images/products/product_68cbad879fce7.jpg', NULL, '2025-09-04 01:42:13', '2025-09-18 06:58:15', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(85, 'Ingelec', '8ways', 7, '8ways', 280.00, 750.00, 0.00, 5, 1, 2, '', 'images/products/product_68cbb55505579.jpg', NULL, '2025-09-04 01:51:24', '2025-09-18 07:31:33', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(86, 'Ingelec', '9ways', 7, '9ways', 280.00, 750.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbb2881f6b6.jpg', NULL, '2025-09-04 01:52:17', '2025-09-18 07:19:36', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(87, 'Ingelec', 'Breaker box 12ways', 7, '12ways', 350.00, 850.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbb31c68356.jpg', NULL, '2025-09-04 01:55:01', '2025-09-18 07:22:04', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(88, 'Ingelec', '13ways', 7, '13ways', 350.00, 750.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbb40a2f769.jpg', NULL, '2025-09-04 01:59:12', '2025-09-18 07:26:02', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(89, 'Ingelec', 'Breaker box 16ways', 7, '16ways', 450.00, 1000.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbb475d2bb1.jpg', NULL, '2025-09-04 02:05:53', '2025-09-18 07:27:49', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(90, 'Ingelec', '18ways', 7, '18ways', 550.00, 1200.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbb5c817cc2.jpg', NULL, '2025-09-04 02:08:28', '2025-09-18 07:33:28', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(91, 'SASSIN', '4ways', 7, '4ways', 450.00, 1000.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbc972be3b4.jpg', NULL, '2025-09-04 02:20:42', '2025-09-18 08:57:22', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(92, 'SASSIN', '8ways', 7, '8ways', 550.00, 1100.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbcd3659cc6.jpg', NULL, '2025-09-04 02:27:08', '2025-09-18 09:13:26', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(93, 'SASSIN', '12ways', 7, '12ways', 660.00, 1300.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbc75a49d5b.jpg', NULL, '2025-09-04 02:31:27', '2025-09-18 08:48:26', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(94, 'SASSIN', '18ways', 7, '18ways', 950.00, 1500.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbc82e6d3bc.jpg', NULL, '2025-09-04 02:34:53', '2025-09-18 08:51:58', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(95, 'Ingelec', '18ways', 7, '18ways', 550.00, 1100.00, 0.00, 5, 0, 1, '', 'images/products/product_68cbb11d1a9a1.jpg', NULL, '2025-09-04 02:35:37', '2025-09-18 07:13:33', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(96, 'Alltopelec', '51.2V 100Ah 5kwh', 3, '100Ah 5Kw', 33000.00, 42000.00, 5.00, 6, 0, 5, '', 'images/products/product_68ba87247855a.jpg', NULL, '2025-09-04 02:40:27', '2025-09-19 09:36:12', 9, 0, 1, 'BAT', 'PREFIX-YYYY-NNNNNN', 16),
(97, 'Alltopelec', '51.2V 200Ah 5kwh', 3, '200Ah 10kwh', 68000.00, 79000.00, 5.00, 6, 0, 5, '', 'images/products/product_68ba878e8af6b.png', NULL, '2025-09-04 02:41:50', '2025-09-19 09:53:42', 9, 0, 1, 'BAT', 'YYYY-NNNNNN', 3),
(98, 'Alltopelec', '51.2V 314Ah 15kwh', 3, '314Ah 15kwh', 80000.00, 95000.00, 5.00, 5, 4, 5, '', 'images/products/product_68ba87ef41818.png', NULL, '2025-09-04 02:44:53', '2025-09-22 03:01:05', 9, 1, 1, 'BAT', 'YYYY-NNNNNN', 5),
(99, 'Alltopelec', '51.2V 400Ah 200kwh', 3, '400Ah 200kwh', 115000.00, 135000.00, 5.00, 5, 0, 5, '', 'images/products/product_68ba887bdb097.png', NULL, '2025-09-04 02:47:21', '2025-09-19 09:00:16', 9, 1, 1, 'BAT', 'YYYY-NNNNNN', 1),
(100, 'Arrow Cable Trray', '50x50', 5, '50x50', 300.00, 750.00, 0.00, 5, 1, 5, '', 'images/products/product_68c7a0d63b511.png', NULL, '2025-09-04 02:55:19', '2025-09-20 08:27:39', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(101, 'Railings', 'Railings', 8, '2.4', 570.00, 690.00, 0.00, 5, 28, 10, '', 'images/products/product_68bfb7905d837.jpg', NULL, '2025-09-04 02:57:49', '2025-09-18 01:06:35', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(102, 'Arrow Cable Trray', '80x80', 5, '80x80', 900.00, 1200.00, 0.00, 5, 4, 5, '', 'images/products/product_68c7a0e1b3a24.png', NULL, '2025-09-04 03:03:49', '2025-09-15 05:15:13', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(103, 'SPD DC BREAKER', '600V DC', 7, 'DC SPD', 880.00, 1500.00, 0.00, 5, 6, 6, '', 'images/products/product_68ca829a7bf91.jpg', NULL, '2025-09-04 03:06:52', '2025-09-17 09:42:50', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(104, 'FEE0 ', 'MCCB 200A', 7, '200A', 2500.00, 2800.00, 0.00, 5, 1, 2, '', 'images/products/product_68cbaf81e47ae.jpg', NULL, '2025-09-04 03:09:08', '2025-09-18 07:06:41', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(105, 'L Foot', 'L Foot', 8, 'L Foot', 80.00, 150.00, 0.00, 5, 12, 10, '', 'images/products/product_68bfb762d17f2.jpg', NULL, '2025-09-04 03:20:21', '2025-09-18 01:07:01', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(107, 'LV Topsun', '300Ah', 3, '300Ah 15kwh', 100000.00, 110000.00, 0.00, 5, 0, 10, '', 'images/products/product_68cbbbe9f1dce.jpg', NULL, '2025-09-04 03:26:35', '2025-09-19 09:00:16', 9, 1, 1, 'BAT', 'YYYY-NNNNNN', 1),
(108, 'SPD AC BREAKER', '220V - 385V', 7, 'AC SPD', 430.00, 850.00, 0.00, 5, 0, 5, '', 'images/products/product_68ca83437687a.jpg', NULL, '2025-09-04 03:44:30', '2025-09-20 07:19:25', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(110, 'FEE0 ', 'ATS 2P 125A', 7, 'ATS 125A', 1400.00, 2800.00, 0.00, 5, 3, 3, '', 'images/products/product_68ca7ecd896b5.jpg', NULL, '2025-09-04 03:59:00', '2025-09-17 09:26:37', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(112, 'Mid clamp', 'Mid Clamp', 8, 'Mid Clamp', 45.00, 75.00, 0.00, 5, 2, 10, '', 'images/products/product_68cbc08b9c34f.jpg', NULL, '2025-09-04 04:08:18', '2025-09-18 08:19:23', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(113, 'End Clamp', 'End Clamp', 8, 'End clamp', 45.00, 65.00, 0.00, 5, 12, 10, '', 'images/products/product_68bfb73cac550.jpg', NULL, '2025-09-04 04:09:05', '2025-09-17 06:52:32', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(114, 'Solar pv wire 6mm', 'twin core 6.0mm', 5, '6.0', 110.00, 140.00, 0.00, 5, 57, 10, '', 'images/products/product_68c7a1e93780f.png', NULL, '2025-09-04 05:39:36', '2025-09-18 09:04:04', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(115, 'BATTERY CABLE RED 35MM', 'Battery Cable Red', 5, '35mm', 350.00, 450.00, 0.00, 5, 0, 100, '', 'images/products/product_68cba159ba53f.jpg', NULL, '2025-09-04 08:56:22', '2025-09-18 09:02:29', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(116, 'BATTERY CABLE  BLACK 35MM', 'Battery Cable Black', 5, '35mm', 350.00, 450.00, 0.00, 5, 0, 100, '', 'images/products/product_68cba1bd8b872.jpg', NULL, '2025-09-04 08:57:04', '2025-09-18 09:01:59', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(117, 'HDPE HOSE ', 'AD34.5', 5, '5mm', 45.00, 85.00, 0.00, 5, 100, 100, '', 'images/products/product_68cba24cde53f.jpg', NULL, '2025-09-04 09:06:09', '2025-09-18 06:10:20', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(118, 'KOTEN BREAKER', 'AC MCCB BREAKER', 7, '200A ', 2800.00, 4500.00, 0.00, 9, 1, 10, '', 'images/products/product_68cbbac20ee38.jpg', NULL, '2025-09-04 09:57:37', '2025-09-18 07:54:42', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(119, 'PHILFLEX THHN', 'AC WIRE #2', 5, '#2 38MM', 250.00, 450.00, 0.00, 9, 50, 10, 'AC WIRES', 'images/products/product_68cba394b76dc.jpg', NULL, '2025-09-04 11:05:11', '2025-09-18 06:15:48', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(120, 'METAL ENCLOSURE ', '40X50X20', 7, '400mm x 500 x 200', 2900.00, 3900.00, 0.00, 5, 1, 10, '', 'images/products/product_68cbbe8aebfe9.jpg', NULL, '2025-09-04 11:10:20', '2025-09-18 08:10:50', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(121, 'DIN RAIL', 'ALLUMINUM RAIL', 4, '1 meter', 80.00, 480.00, 0.00, 9, 10, 10, '', 'images/products/product_68ccc41b42b94.jpg', NULL, '2025-09-04 11:12:32', '2025-09-19 02:46:51', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(122, 'METAL ENCLOSURE', '6X15X18(200X400X500)', 7, '6X15X18(200X400X500)', 2650.00, 4500.00, 0.00, 9, 1, 10, '', 'images/products/product_68cbbf3ce79b8.jpg', NULL, '2025-09-05 02:09:21', '2025-09-18 08:13:48', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(123, 'Canadian Solar', 'MONO 590w (CS6L-455MS)', 1, '590W', 0.00, 4500.00, 0.00, 5, 8, 0, '', 'images/products/product_68cb630b8846c.jpg', NULL, '2025-09-08 05:52:41', '2025-09-22 03:01:58', 9, 1, 1, 'CANADIAN', 'YYYY-NNNNNN', 67),
(124, 'Canadian Solar', 'MONO 610w (CS6L-610MS)', 1, '610W', 4500.00, 5500.00, 5.00, 5, 5, 10, '', 'images/products/product_68ca7aa5aaa75.jpg', NULL, '2025-09-08 06:12:45', '2025-09-19 09:00:16', 9, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(126, 'Alltopelec', '25,6 5Kw 200Ah', 3, '200Ah', 0.00, 37000.00, 0.00, 5, 5, 5, '', 'images/products/product_68c79f70d999d.jpg', NULL, '2025-09-09 07:20:27', '2025-09-22 04:11:39', 1, 1, 1, 'ATE', 'YYYY-NNNNNN', 1),
(127, 'FEEO', 'MCCB DC 200A', 7, '200Ah', 0.00, 3950.00, 0.00, 5, 1, 5, '', 'images/products/product_68cbac9d2eb97.jpg', NULL, '2025-09-09 07:26:46', '2025-09-18 06:54:21', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(128, 'Koten', 'MCCB AC 225A', 7, '225A', 2850.00, 3500.00, 0.00, 5, 1, 0, '', 'images/products/product_68cbb9ec7ed3f.jpg', NULL, '2025-09-09 07:28:45', '2025-09-18 07:51:08', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(129, 'Canadian Solar', 'Solar Panel 610w', 1, '610w', 0.00, 0.00, 0.00, 5, 5, 10, '', 'images/products/product_68ccb8bd69cc6.jpg', NULL, '2025-09-09 07:29:41', '2025-09-19 09:00:16', 1, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(130, 'SRNE', '24V', 3, '24v', 0.00, 0.00, 0.00, 5, 1, 3, '', 'images/products/product_68ccbb43827ff.jpg', NULL, '2025-09-09 07:34:30', '2025-09-19 09:00:16', 1, 1, 1, 'BAT', 'YYYY-NNNNNN', 1),
(131, 'Dim Rail', 'Dim Rail', 8, 'Dim Rail', 0.00, 0.00, 0.00, 5, 0, 10, '', 'images/products/product_68cbab847fd3f.jpg', NULL, '2025-09-09 07:35:39', '2025-09-18 06:49:40', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(132, 'SNAT', '1kw 12v/24v', 2, '24v', 4500.00, 0.00, 5.00, 5, 1, 3, '', 'images/products/product_68ccb428f01d9.jpg', NULL, '2025-09-09 07:39:37', '2025-09-19 09:00:16', 1, 1, 1, 'INV', 'YYYY-NNNNNN', 1),
(133, 'SNAT', '2kw 12l 24V', 2, '2KW', 11500.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68ccb4d0d0f0c.jpg', NULL, '2025-09-09 07:41:09', '2025-09-19 09:00:16', 1, 1, 1, 'INV', 'YYYY-NNNNNN', 1),
(134, 'SNAT', '3KW 12/ 24v/ 48v', 2, '3kw', 12500.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68ccb5c5aa420.jpg', NULL, '2025-09-09 07:42:34', '2025-09-19 09:00:16', 1, 1, 1, 'INV', 'YYYY-NNNNNN', 1),
(135, 'SNAT', '4KW 24V/48V ', 2, '', 22000.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68ccb6539cc71.jpg', NULL, '2025-09-09 07:46:38', '2025-09-19 09:00:16', 1, 1, 1, 'INV', 'YYYY-NNNNNN', 1),
(137, 'SNAT', '5KW/24V/48V', 2, '5kw', 25000.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68ccb72476ba0.jpg', NULL, '2025-09-09 07:50:41', '2025-09-19 09:00:16', 1, 1, 1, 'INV', 'YYYY-NNNNNN', 1),
(138, 'SRNE', '3KW/24V', 9, '3kw', 19000.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68ccbda7ee92c.jpg', NULL, '2025-09-09 07:53:31', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(139, 'SRNE', '3KW 24V(HF2430S60-100V)', 9, '3kw', 19000.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68ccbbf62825c.jpg', NULL, '2025-09-09 07:57:33', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(140, 'SRNE', '5KW 48V(MF4850S80-H)', 9, '5kw', 29000.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68ccbe2753559.jpg', NULL, '2025-09-09 08:00:22', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(141, 'SRNE', '8KW 48V(ASF4880S180-H)', 9, '8kw', 68000.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68ccbf93b6c64.jpg', NULL, '2025-09-09 08:02:30', '2025-09-19 09:00:16', 1, 1, 1, 'HYB', 'YYYY-NNNNNN', 1),
(143, 'CABLE TRAY ', '80X80', 8, '2Mtrs', 600.00, 900.00, 0.00, 9, 0, 3, '', 'images/products/product_68c7a0eb8220b.png', NULL, '2025-09-09 08:09:11', '2025-09-15 05:15:23', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(144, 'SOLAR PV CABLES', '4m (RED)', 5, '1X4 mm', 40.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68cba484dc0dc.jpg', NULL, '2025-09-09 08:13:03', '2025-09-18 09:03:40', 1, 0, 0, NULL, 'YYYY-NNNNNN', 1),
(145, 'SOLAR PV CABLES', '4mm (black)', 5, '1x4mm', 40.00, 0.00, 0.00, 5, 0, 3, '', 'images/products/product_68cba4bf45698.jpg', NULL, '2025-09-09 08:15:21', '2025-09-18 09:03:21', 1, 0, 0, NULL, 'YYYY-NNNNNN', 1),
(146, 'TRINA SOLAR', '590W', 1, '590W', 0.00, 0.00, 0.00, 9, 0, 10, '', 'images/products/product_68ccc1826e8df.jpg', NULL, '2025-09-12 02:26:28', '2025-09-19 09:00:16', 9, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(147, 'TRINA SOLAR', '600W', 1, '600W', 0.00, 0.00, 0.00, 9, 0, 10, '', 'images/products/product_68ccc1c4a32d2.jpg', NULL, '2025-09-12 02:27:10', '2025-09-19 09:00:16', 9, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(148, 'TRINA SOLAR', '700w', 1, '700w', 0.00, 0.00, 0.00, 5, 0, 10, '', 'images/products/product_68ccc1e2aeeb3.jpg', NULL, '2025-09-12 03:19:31', '2025-09-19 09:00:16', 9, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(149, 'TRINA SOLAR', '580w', 1, '580w', 0.00, 0.00, 0.00, 9, 0, 10, '', 'images/products/product_68cbcf4367248.jpg', NULL, '2025-09-12 03:20:33', '2025-09-19 09:00:16', 9, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(150, 'TRINA SOLAR', '550w', 1, '550w', 0.00, 0.00, 0.00, 9, 0, 10, '', 'images/products/product_68cbce4a7a1cc.jpg', NULL, '2025-09-12 03:28:43', '2025-09-19 09:00:16', 9, 1, 1, 'PAN', 'YYYY-NNNNNN', 1),
(151, 'CST ', '51.2V 324Ah', 3, '324Ah', 0.00, 0.00, 0.00, 9, 0, 10, '', 'images/products/product_68cb610cc55eb.jpg', NULL, '2025-09-12 05:42:31', '2025-09-19 09:00:16', 9, 1, 1, 'BAT', 'YYYY-NNNNNN', 1),
(152, 'CST ', '51.2V 300Ah', 3, '300Ah', 0.00, 0.00, 0.00, 9, 0, 10, '', 'images/products/product_68cb63e99cd22.jpg', NULL, '2025-09-12 05:49:14', '2025-09-19 09:00:16', 9, 1, 1, 'BAT', 'YYYY-NNNNNN', 1),
(153, 'CST ', '51.2V 100Ah', 3, '100Ah', 0.00, 0.00, 0.00, 9, 0, 10, '', 'images/products/product_68cb75d5526e5.jpg', NULL, '2025-09-12 05:50:53', '2025-09-19 09:00:16', 9, 1, 1, 'BAT', 'YYYY-NNNNNN', 1),
(154, 'CST ', '24V 100Ah', 3, '100Ah', 0.00, 0.00, 0.00, 9, 0, 10, '', 'images/products/product_68ccb7d297e52.jpg', NULL, '2025-09-12 05:51:42', '2025-09-19 09:00:16', 9, 1, 1, 'BAT', 'YYYY-NNNNNN', 1),
(158, 'MC4 Y CONNECTOR ', 'PV 004', 5, 'NONE', 380.00, 500.00, 0.00, 5, 6, 10, '', 'images/products/product_68c7a6204a3ee.jfif', NULL, '2025-09-15 05:37:36', '2025-09-17 08:20:55', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(159, 'DIN RAIL', 'ALLUMINUM 1METER', 8, '1 METER', 160.00, 480.00, 0.00, 5, 4, 10, '', 'images/products/product_68ccb9cf7625e.jpg', NULL, '2025-09-15 06:29:17', '2025-09-19 02:02:55', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(161, 'PVC 1 1/2', 'ELBOW', 8, '1 1/2', 68.00, 82.00, 0.00, 5, 7, 10, '', 'images/products/product_68cbc2d0c6314.jpg', NULL, '2025-09-15 06:42:46', '2025-09-18 08:29:04', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(162, 'PVC 1 1/2', 'PIPE', 8, '1 1/2', 260.00, 350.00, 0.00, 5, 4, 10, '', 'images/products/product_68cbc3644fb3e.jpg', NULL, '2025-09-15 06:45:05', '2025-09-18 08:31:32', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(163, 'Metal Enclosure ', '300X400X200mm Breaker Box', 8, '300X400X200mm', 1900.00, 0.00, 0.00, 5, 0, 5, '', 'images/products/product_68cbbd8a3f003.jpg', NULL, '2025-09-15 06:49:50', '2025-09-18 08:06:34', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(164, 'Metal Enclosure 250X300X200mm', '250X300X200mm BREAKER', 8, '250X300X200mm', 1400.00, 2500.00, 0.00, 5, 0, 5, '', 'images/products/product_68cbc0038be8f.jpg', NULL, '2025-09-15 06:50:39', '2025-09-18 08:17:07', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(165, 'IP65', 'Junction Box', 8, '100*100*70mm', 120.00, 0.00, 0.00, 5, 0, 5, '', 'images/products/product_68cbb735b165b.jpg', NULL, '2025-09-15 06:51:51', '2025-09-18 07:39:33', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(166, 'TERMINAL LUGS', '(SC35-10)', 7, '35-10M', 30.00, 55.00, 0.00, 5, 40, 10, '', 'images/products/product_68ca81b01c591.jpg', NULL, '2025-09-15 06:52:32', '2025-09-17 09:38:56', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(167, 'IP65', 'Junction Box', 8, '150*150*70', 130.00, 0.00, 0.00, 9, 0, 5, '', 'images/products/product_68cbb80293b76.jpg', NULL, '2025-09-15 06:52:55', '2025-09-18 07:42:58', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(168, 'IP65', 'Junction Box', 8, '200*200*80mm', 240.00, 0.00, 0.00, 5, 0, 5, '', 'images/products/product_68cbb8faaa084.jpg', NULL, '2025-09-15 06:54:13', '2025-09-18 07:47:06', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(169, 'NYLONE ROPE', 'NONE', 8, '1/2', 24.00, 50.00, 0.00, 5, 40, 10, '', 'images/products/product_68ccc26e5521b.jpg', NULL, '2025-09-15 06:54:22', '2025-09-19 02:39:42', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(170, 'MC4 Crimper', 'Crimper', 8, '0', 480.00, 0.00, 0.00, 5, 0, 10, '', 'images/products/product_68cbbce457d3e.jpg', NULL, '2025-09-15 06:56:47', '2025-09-18 08:03:48', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(171, 'Cable Tie', 'Tie', 8, '3x150', 55.00, 0.00, 0.00, 5, 0, 10, '', 'images/products/product_68cb5f8ff09e5.jpg', NULL, '2025-09-15 06:58:42', '2025-09-18 01:25:35', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(172, 'ELECTRICAL TAPE', 'BLACK', 8, 'NONE', 42.00, 100.00, 0.00, 5, 5, 10, '', 'images/products/product_68cb90844897e.jpg', NULL, '2025-09-15 06:58:57', '2025-09-18 04:54:28', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(173, 'Cable Tie', 'Cable Tie', 8, '4x200', 100.00, 0.00, 0.00, 5, 0, 10, '', 'images/products/product_68cb605ccb6b8.jpg', NULL, '2025-09-15 07:00:02', '2025-09-18 01:29:00', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(174, 'ELECTRICAL TAPE', 'RED', 8, 'CM', 28.00, 100.00, 0.00, 5, 5, 10, '', 'images/products/product_68cbb6a1c0c08.jpg', NULL, '2025-09-15 07:02:59', '2025-09-18 07:37:05', 1, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(175, 'MC4 Connector', 'MC4 Connector', 8, '0', 60.00, 75.00, 0.00, 5, 16, 20, '', 'images/products/product_68cbbc65c3fa3.jpg', NULL, '2025-09-16 05:29:16', '2025-09-18 08:01:41', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(176, 'Stranded TW #6/7', 'Tw #6', 5, '#6/7', 100.00, 125.00, 0.00, 9, 200, 10, '', 'images/products/product_68cba5b9077ee.jpg', NULL, '2025-09-17 06:16:38', '2025-09-18 06:24:57', 8, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(177, 'Stranded TW #8/7', 'tw 8', 5, '#8', 62.00, 95.00, 0.00, 9, 30, 10, '', 'images/products/product_68cba67bc23e3.jpg', NULL, '2025-09-17 06:17:27', '2025-09-18 06:28:11', 8, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(178, 'One solar ', 'one solar', 2, '40A', 4600.00, 4600.00, 1.00, 5, 0, 3, '', 'images/products/product_68cbc19909da5.jpg', NULL, '2025-09-17 08:17:27', '2025-09-19 09:00:16', 9, 1, 1, 'INV', 'YYYY-NNNNNN', 1),
(179, 'One solar ', 'One solar', 2, '60A', 9000.00, 9000.00, 0.00, 5, 1, 3, '', 'images/products/product_68cbc1fe92202.jpg', NULL, '2025-09-17 08:19:03', '2025-09-19 09:00:16', 9, 1, 1, 'INV', 'YYYY-NNNNNN', 1),
(180, 'FEE0 ', '63A DC breaker', 7, 'DC 63A', 450.00, 650.00, 0.00, 5, 2, 10, '', 'images/products/product_68ca7446c4e26.jpg', NULL, '2025-09-17 08:27:35', '2025-09-18 03:19:37', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(181, 'Koten Nema 3R PW400CS 3pole with Ground Enclosure only', 'Koten', 7, 'Nema 3 bolt on/ Nema 3 plug in', 7800.00, 8100.00, 0.00, 10, 0, 10, '', 'images/products/product_68cb943de2f3f.jpg', NULL, '2025-09-18 05:10:21', '2025-09-19 03:43:37', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(182, 'Koten Pw400Cs 3pole 300ampere', 'Koten', 7, '3 Pole', 16717.00, 19000.00, 0.00, 10, 0, 10, '', 'images/products/product_68cb9675122eb.jpg', NULL, '2025-09-18 05:19:49', '2025-09-19 03:43:20', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(183, 'Drop wire ', 'Drop wire', 5, 'size # 4', 60.00, 90.00, 0.00, 9, 0, 10, '', 'images/products/product_68cb97921493f.jpg', NULL, '2025-09-18 05:24:34', '2025-09-18 06:31:06', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(184, 'THHN  WIRE #6', 'THHN WIRE', 5, '#6', 109.50, 139.50, 0.00, 9, 0, 10, '', 'images/products/product_68cb98e30169d.jpg', NULL, '2025-09-18 05:30:11', '2025-09-18 06:31:46', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(185, 'HIMEL MCB 63A', 'HIMEL', 7, 'MCB BREAKER', 220.00, 250.00, 0.00, 9, 0, 10, '', 'images/products/product_68cb9a098c9c2.jpg', NULL, '2025-09-18 05:35:05', '2025-09-18 06:32:30', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(188, 'THHN  WIRE #8', 'THHN WIRE', 5, '#8', 68.75, 98.75, 0.00, 9, 0, 10, '', 'images/products/product_68cb9f158f007.jpg', NULL, '2025-09-18 05:56:37', '2025-09-18 06:33:13', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(189, 'THHN WIRE #4', 'THHN WIRE', 5, '#4', 172.00, 197.00, 0.00, 9, 0, 10, '', 'images/products/product_68cba02658f04.jpg', NULL, '2025-09-18 06:01:10', '2025-09-18 06:33:36', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(190, 'PV WIRE 4MM', 'PV WIRE ', 5, '', 80.00, 100.00, 0.00, 9, 0, 10, '', 'images/products/product_68cbcc5aa182c.png', NULL, '2025-09-18 09:06:33', '2025-09-18 09:09:46', 9, 1, 0, NULL, 'YYYY-NNNNNN', 1),
(191, 'Alltopelec', '51.2V 100Ah 5kwh', 3, '100Ah 5Kw', 33000.00, 42000.00, 0.00, 6, 4, 1, '', 'images/products/product_68cd24bc4479d.jpg', NULL, '2025-09-19 09:39:08', '2025-09-22 04:03:13', 1, 1, 1, 'Alltopelec 51.2V 100', 'PREFIX-YYYY-NNNNNN', 15),
(192, 'Alltopelec', '51.2V 200Ah 5kwh', 3, '200Ah 10kwh', 68000.00, 79000.00, 0.00, 6, 1, 1, '', 'images/products/product_68cd2897a0e8b.png', NULL, '2025-09-19 09:55:35', '2025-09-20 02:55:45', 1, 0, 1, 'BAT', 'PREFIX-YYYY-NNNNNN', 5),
(193, 'Alltopelec', '51.2V 200Ah 5kwh', 3, '200Ah 10kwh', 68000.00, 79000.00, 0.00, 6, 0, 1, '', 'images/products/product_68ce1859c25d0.png', NULL, '2025-09-20 02:58:33', '2025-09-22 03:09:57', 1, 1, 1, 'BAT', 'PREFIX-YYYY-NNNNNN', 7);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_serials`
--

CREATE TABLE `inventory_serials` (
  `id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `status` enum('available','sold','reserved','damaged','returned') DEFAULT 'available',
  `sale_id` int(11) DEFAULT NULL COMMENT 'POS sale ID when sold',
  `quote_id` int(11) DEFAULT NULL COMMENT 'Quote ID when reserved',
  `project_id` int(11) DEFAULT NULL COMMENT 'Project ID when used in project',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_serials`
--

INSERT INTO `inventory_serials` (`id`, `inventory_item_id`, `serial_number`, `status`, `sale_id`, `quote_id`, `project_id`, `notes`, `created_at`, `updated_at`, `created_by`) VALUES
(25, 193, 'BAT-2025-000001', 'reserved', NULL, 31, NULL, NULL, '2025-09-20 02:59:27', '2025-09-22 03:10:49', 1),
(26, 193, 'BAT-2025-000002', 'available', NULL, NULL, NULL, NULL, '2025-09-20 02:59:27', '2025-09-20 02:59:27', 1),
(27, 193, 'BAT-2025-000003', 'available', NULL, NULL, NULL, NULL, '2025-09-20 02:59:27', '2025-09-20 02:59:27', 1),
(28, 193, 'BAT-2025-000004', 'available', NULL, NULL, NULL, NULL, '2025-09-20 02:59:27', '2025-09-20 02:59:27', 1),
(29, 193, 'BAT-2025-000005', 'available', NULL, NULL, NULL, NULL, '2025-09-20 02:59:27', '2025-09-20 02:59:27', 1),
(31, 26, 'PAN-2025-000001', 'available', NULL, NULL, NULL, NULL, '2025-09-20 08:32:30', '2025-09-20 08:32:30', 1),
(32, 26, 'PAN-2025-000002', 'available', NULL, NULL, NULL, NULL, '2025-09-20 08:32:56', '2025-09-20 08:32:56', 1),
(34, 22, 'PAN-2025-000003', 'available', NULL, NULL, NULL, NULL, '2025-09-20 08:33:31', '2025-09-20 08:33:31', 1),
(35, 22, 'PAN-2025-000004', 'available', NULL, NULL, NULL, NULL, '2025-09-20 08:33:31', '2025-09-20 08:33:31', 1),
(36, 22, 'PAN-2025-000005', 'available', NULL, NULL, NULL, NULL, '2025-09-20 08:33:46', '2025-09-20 08:33:46', 1),
(37, 22, 'PAN-2025-000006', 'available', NULL, NULL, NULL, NULL, '2025-09-20 08:34:02', '2025-09-20 08:34:02', 1),
(38, 22, 'PAN-2025-000007', 'available', NULL, NULL, NULL, NULL, '2025-09-20 08:34:02', '2025-09-20 08:34:02', 1),
(39, 191, 'Alltopelec 51.2V 100-2025-000001', 'reserved', NULL, 27, NULL, NULL, '2025-09-20 10:13:15', '2025-09-20 10:13:36', 1),
(40, 191, 'Alltopelec 51.2V 100-2025-000002', 'available', NULL, NULL, NULL, NULL, '2025-09-20 10:13:15', '2025-09-20 10:13:15', 1),
(41, 191, 'Alltopelec 51.2V 100-2025-000003', 'available', NULL, NULL, NULL, NULL, '2025-09-20 10:13:15', '2025-09-20 10:13:15', 1),
(42, 191, 'Alltopelec 51.2V 100-2025-000004', 'available', NULL, NULL, NULL, NULL, '2025-09-20 10:13:16', '2025-09-20 10:13:16', 1),
(43, 191, 'Alltopelec 51.2V 100-2025-000005', 'available', NULL, NULL, NULL, NULL, '2025-09-20 10:13:16', '2025-09-20 10:13:16', 1),
(44, 191, 'Alltopelec 51.2V 100-2025-000006', 'available', NULL, NULL, NULL, NULL, '2025-09-20 10:13:16', '2025-09-20 10:13:16', 1),
(45, 123, 'CANADIAN-2025-000001', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:28:44', '2025-09-22 02:36:27', 1),
(46, 123, 'CANADIAN-2025-000002', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:28:44', '2025-09-22 02:36:27', 1),
(47, 123, 'CANADIAN-2025-000003', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:28:44', '2025-09-22 02:36:27', 1),
(48, 123, 'CANADIAN-2025-000004', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:28:44', '2025-09-22 02:36:27', 1),
(49, 123, 'CANADIAN-2025-000005', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:28:44', '2025-09-22 02:36:27', 1),
(50, 123, 'CANADIAN-2025-000006', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:28:44', '2025-09-22 02:36:27', 1),
(51, 123, 'CANADIAN-2025-000007', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:28:44', '2025-09-22 02:36:27', 1),
(52, 123, 'CANADIAN-2025-000008', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:28:44', '2025-09-22 02:36:27', 1),
(53, 123, 'CANADIAN-2025-000009', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(54, 123, 'CANADIAN-2025-000010', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(55, 123, 'CANADIAN-2025-000011', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(56, 123, 'CANADIAN-2025-000012', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(57, 123, 'CANADIAN-2025-000013', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(58, 123, 'CANADIAN-2025-000014', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(59, 123, 'CANADIAN-2025-000015', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(60, 123, 'CANADIAN-2025-000016', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(61, 123, 'CANADIAN-2025-000017', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(62, 123, 'CANADIAN-2025-000018', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(63, 123, 'CANADIAN-2025-000019', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(64, 123, 'CANADIAN-2025-000020', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(65, 123, 'CANADIAN-2025-000021', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(66, 123, 'CANADIAN-2025-000022', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(67, 123, 'CANADIAN-2025-000023', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(68, 123, 'CANADIAN-2025-000024', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(69, 123, 'CANADIAN-2025-000025', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(70, 123, 'CANADIAN-2025-000026', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(71, 123, 'CANADIAN-2025-000027', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(72, 123, 'CANADIAN-2025-000028', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(73, 123, 'CANADIAN-2025-000029', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(74, 123, 'CANADIAN-2025-000030', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 02:36:27', 9),
(75, 123, 'CANADIAN-2025-000031', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(76, 123, 'CANADIAN-2025-000032', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(77, 123, 'CANADIAN-2025-000033', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(78, 123, 'CANADIAN-2025-000034', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(79, 123, 'CANADIAN-2025-000035', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(80, 123, 'CANADIAN-2025-000036', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(81, 123, 'CANADIAN-2025-000037', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(82, 123, 'CANADIAN-2025-000038', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(83, 123, 'CANADIAN-2025-000039', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(84, 123, 'CANADIAN-2025-000040', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(85, 123, 'CANADIAN-2025-000041', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(86, 123, 'CANADIAN-2025-000042', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(87, 123, 'CANADIAN-2025-000043', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(88, 123, 'CANADIAN-2025-000044', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(89, 123, 'CANADIAN-2025-000045', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(90, 123, 'CANADIAN-2025-000046', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:32:27', '2025-09-22 03:00:16', 9),
(91, 59, 'HYB-2025-000001', 'reserved', NULL, 29, NULL, NULL, '2025-09-22 02:38:54', '2025-09-22 02:39:12', 9),
(92, 56, 'HYB-2025-000002', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:55:12', '2025-09-22 02:55:38', 9),
(93, 123, 'CANADIAN-2025-000047', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 03:00:16', 9),
(94, 123, 'CANADIAN-2025-000048', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 03:00:16', 9),
(95, 123, 'CANADIAN-2025-000049', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 03:00:16', 9),
(96, 123, 'CANADIAN-2025-000050', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 03:00:16', 9),
(97, 123, 'CANADIAN-2025-000051', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(98, 123, 'CANADIAN-2025-000052', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(99, 123, 'CANADIAN-2025-000053', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(100, 123, 'CANADIAN-2025-000054', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(101, 123, 'CANADIAN-2025-000055', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(102, 123, 'CANADIAN-2025-000056', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(103, 123, 'CANADIAN-2025-000057', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(104, 123, 'CANADIAN-2025-000058', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(105, 123, 'CANADIAN-2025-000059', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(106, 123, 'CANADIAN-2025-000060', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(107, 123, 'CANADIAN-2025-000061', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(108, 123, 'CANADIAN-2025-000062', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(109, 123, 'CANADIAN-2025-000063', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(110, 123, 'CANADIAN-2025-000064', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(111, 123, 'CANADIAN-2025-000065', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(112, 123, 'CANADIAN-2025-000066', 'available', NULL, NULL, NULL, NULL, '2025-09-22 02:59:27', '2025-09-22 02:59:27', 9),
(113, 98, 'BAT-2025-000006', 'reserved', NULL, 30, NULL, NULL, '2025-09-22 03:01:05', '2025-09-22 03:01:26', 9),
(114, 98, 'BAT-2025-000007', 'available', NULL, NULL, NULL, NULL, '2025-09-22 03:01:05', '2025-09-22 03:01:05', 9),
(115, 98, 'BAT-2025-000008', 'available', NULL, NULL, NULL, NULL, '2025-09-22 03:01:05', '2025-09-22 03:01:05', 9),
(116, 98, 'BAT-2025-000009', 'available', NULL, NULL, NULL, NULL, '2025-09-22 03:01:05', '2025-09-22 03:01:05', 9),
(117, 56, 'HYB-2025-000003', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:06:57', '2025-09-22 03:07:16', 9),
(118, 33, 'PAN-2025-000008', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(119, 33, 'PAN-2025-000009', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(120, 33, 'PAN-2025-000010', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(121, 33, 'PAN-2025-000011', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(122, 33, 'PAN-2025-000012', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(123, 33, 'PAN-2025-000013', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(124, 33, 'PAN-2025-000014', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(125, 33, 'PAN-2025-000015', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(126, 33, 'PAN-2025-000016', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(127, 33, 'PAN-2025-000017', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(128, 33, 'PAN-2025-000018', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(129, 33, 'PAN-2025-000019', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(130, 33, 'PAN-2025-000020', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(131, 33, 'PAN-2025-000021', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(132, 33, 'PAN-2025-000022', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(133, 33, 'PAN-2025-000023', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(134, 33, 'PAN-2025-000024', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(135, 33, 'PAN-2025-000025', 'reserved', NULL, 31, NULL, NULL, '2025-09-22 03:08:08', '2025-09-22 03:09:07', 9),
(136, 193, 'BAT-2025-000010', 'available', NULL, NULL, NULL, NULL, '2025-09-22 03:09:51', '2025-09-22 03:09:51', 9),
(137, 191, 'Alltopelec 51.2V 100-2025-000007', 'available', NULL, NULL, NULL, NULL, '2025-09-22 04:01:01', '2025-09-22 04:01:01', 1),
(138, 191, 'Alltopelec 51.2V 100-2025-000008', 'available', NULL, NULL, NULL, NULL, '2025-09-22 04:01:01', '2025-09-22 04:01:01', 1),
(139, 191, 'Alltopelec 51.2V 100-2025-000009', 'available', NULL, NULL, NULL, NULL, '2025-09-22 04:01:01', '2025-09-22 04:01:01', 1),
(140, 191, 'Alltopelec 51.2V 100-2025-000010', 'available', NULL, NULL, NULL, NULL, '2025-09-22 04:01:01', '2025-09-22 04:01:01', 1),
(141, 191, 'Alltopelec 51.2V 100-2025-000011', 'available', NULL, NULL, NULL, NULL, '2025-09-22 04:03:13', '2025-09-22 04:03:13', 1),
(142, 191, 'Alltopelec 51.2V 100-2025-000012', 'available', NULL, NULL, NULL, NULL, '2025-09-22 04:03:13', '2025-09-22 04:03:13', 1),
(143, 191, 'Alltopelec 51.2V 100-2025-000013', 'available', NULL, NULL, NULL, NULL, '2025-09-22 04:03:13', '2025-09-22 04:03:13', 1),
(144, 191, 'Alltopelec 51.2V 100-2025-000014', 'available', NULL, NULL, NULL, NULL, '2025-09-22 04:03:13', '2025-09-22 04:03:13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `total_working_days` int(11) DEFAULT 0,
  `working_days_present` int(11) DEFAULT 0,
  `leaves_taken` int(11) DEFAULT 0,
  `balance_leaves` int(11) DEFAULT 0,
  `basic_salary` decimal(10,2) DEFAULT 0.00,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deductions`
--

CREATE TABLE `payroll_deductions` (
  `id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `deduction_type` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_packages`
--

CREATE TABLE `payroll_packages` (
  `id` int(11) NOT NULL,
  `payroll_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_sales`
--

CREATE TABLE `pos_sales` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `total_discount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('cash','credit_card','debit_card','bank_transfer','check') DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pos_sales`
--

INSERT INTO `pos_sales` (`id`, `receipt_number`, `customer_name`, `customer_phone`, `subtotal`, `total_discount`, `total_amount`, `payment_method`, `amount_paid`, `change_amount`, `status`, `notes`, `created_by`, `created_at`, `completed_at`) VALUES
(36, 'RCP20250903-1214', 'Juddin A. Pantassan', '', 2500.00, 0.00, 2500.00, 'cash', 2500.00, 0.00, 'completed', NULL, 9, '2025-09-03 04:40:31', '2025-09-03 04:42:48'),
(37, 'RCP20250904-9206', 'HAJJI BUDS', '09452382752', 2500.00, 0.00, 2500.00, 'cash', 2500.00, 0.00, 'completed', NULL, 1, '2025-09-04 02:14:11', '2025-09-04 02:14:56'),
(38, 'RCP20250904-2146', 'Ajiv Talal', '09977593722', 1790.00, 0.00, 1790.00, 'cash', 1790.00, 0.00, 'completed', NULL, 9, '2025-09-04 05:40:45', '2025-09-04 05:41:43'),
(46, 'RCP20250908-2619', '', '', 18000.00, 0.00, 18000.00, 'cash', 18000.00, 0.00, 'completed', NULL, 9, '2025-09-08 05:53:47', '2025-09-08 05:54:16'),
(47, 'RCP20250910-0185', 'Vincent', '', 2800.00, 399.28, 2400.72, 'cash', 2400.72, 0.00, 'completed', NULL, 9, '2025-09-10 04:24:36', '2025-09-10 04:27:44'),
(48, 'RCP20250911-4592', 'Vincent', '', 1400.00, 199.92, 1200.08, 'cash', 1200.08, 0.00, 'completed', NULL, 9, '2025-09-10 04:24:36', '2025-09-10 04:27:44'),
(49, 'RCP20250916-4796', 'Buhari', '', 120.00, 25.00, 95.00, 'cash', 95.00, 0.00, 'completed', NULL, 9, '2025-09-16 05:20:22', '2025-09-16 05:22:14'),
(53, 'RCP20250916-5335', '', '', 375.00, 0.00, 375.00, 'cash', 375.00, 0.00, 'completed', NULL, 9, '2025-09-16 07:21:12', '2025-09-16 07:21:39'),
(55, 'RCP20250918-5317', 'Ustad Muks', '09', 10930.00, 199.76, 10730.24, 'cash', 10730.24, 0.00, 'completed', NULL, 9, '2025-09-18 00:56:34', '2025-09-18 00:59:46'),
(63, 'RCP20250918-3025', 'ABUSABYR', '09', 4300.00, 0.00, 4300.00, 'cash', 4300.00, 0.00, 'completed', NULL, 9, '2025-09-18 08:38:45', '2025-09-18 08:39:13'),
(82, 'RCP20250920-9269', '', '', 3000.00, 600.00, 2400.00, 'cash', 2400.00, 0.00, 'completed', NULL, 1, '2025-09-20 06:56:24', '2025-09-20 07:10:55'),
(95, 'RCP20250920-7355', 'Thai Alamia', '09173081539', 264648.00, 10389.93, 254258.08, NULL, 0.00, 0.00, 'pending', NULL, 1, '2025-09-20 10:20:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pos_sale_items`
--

CREATE TABLE `pos_sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `serial_numbers` text DEFAULT NULL COMMENT 'JSON array of serial numbers used in this sale item',
  `serial_count` int(11) DEFAULT 0 COMMENT 'Number of serial numbers used'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pos_sale_items`
--

INSERT INTO `pos_sale_items` (`id`, `sale_id`, `inventory_item_id`, `quantity`, `unit_price`, `discount_percentage`, `discount_amount`, `total_amount`, `created_at`, `serial_numbers`, `serial_count`) VALUES
(20, 36, 71, 1, 2500.00, 0.00, 0.00, 2500.00, '2025-09-03 04:40:41', NULL, 0),
(21, 37, 71, 1, 2500.00, 0.00, 0.00, 2500.00, '2025-09-04 02:14:24', NULL, 0),
(22, 38, 114, 12, 140.00, 0.00, 0.00, 1680.00, '2025-09-04 05:40:58', NULL, 0),
(23, 38, 75, 2, 55.00, 0.00, 0.00, 110.00, '2025-09-04 05:41:24', NULL, 0),
(25, 46, 123, 4, 4500.00, 0.00, 0.00, 18000.00, '2025-09-08 05:54:12', NULL, 0),
(26, 47, 114, 20, 140.00, 14.26, 399.28, 2400.72, '2025-09-10 04:27:14', NULL, 0),
(27, 48, 114, 10, 140.00, 14.28, 199.92, 1200.08, '2025-09-11 02:13:06', NULL, 0),
(28, 49, 114, 1, 120.00, 20.83, 25.00, 95.00, '2025-09-16 05:22:07', NULL, 0),
(32, 53, 175, 5, 75.00, 0.00, 0.00, 375.00, '2025-09-16 07:21:30', NULL, 0),
(33, 55, 26, 2, 4400.00, 2.27, 199.76, 8600.24, '2025-09-18 00:57:59', NULL, 0),
(34, 55, 101, 2, 690.00, 0.00, 0.00, 1380.00, '2025-09-18 00:58:35', NULL, 0),
(35, 55, 105, 4, 150.00, 0.00, 0.00, 600.00, '2025-09-18 00:58:57', NULL, 0),
(36, 55, 112, 2, 75.00, 0.00, 0.00, 150.00, '2025-09-18 00:59:14', NULL, 0),
(37, 59, 26, 2, 4400.00, 2.27, 199.76, 8600.24, '2025-09-18 01:22:36', NULL, 0),
(38, 63, 39, 1, 4300.00, 0.00, 0.00, 4300.00, '2025-09-18 08:39:05', NULL, 0),
(39, 68, 96, 1, 42000.00, 0.00, 0.00, 42000.00, '2025-09-19 09:09:37', '[\"BAT-2025-000001\"]', 1),
(40, 70, 96, 1, 42000.00, 0.00, 0.00, 42000.00, '2025-09-19 09:19:51', '[\"BAT-2025-000002\"]', 1),
(41, 71, 96, 2, 42000.00, 0.00, 0.00, 84000.00, '2025-09-19 09:32:28', '[\"BAT-2025-000012\"]', 1),
(42, 73, 96, 2, 42000.00, 0.00, 0.00, 84000.00, '2025-09-19 09:34:14', '[\"BAT-2025-000013\",\"BAT-2025-000014\"]', 2),
(89, 82, 79, 2, 1500.00, 20.00, 600.00, 2400.00, '2025-09-20 06:59:56', '[]', 0),
(90, 83, 73, 3200, 25.00, 0.00, 0.00, 80000.00, '2025-09-20 07:56:06', NULL, 0),
(91, 83, 100, 1, 300.00, 0.00, 0.00, 300.00, '2025-09-20 07:56:06', NULL, 0),
(92, 84, 73, 3200, 25.00, 0.00, 0.00, 80000.00, '2025-09-20 08:04:11', NULL, 0),
(93, 84, 100, 1, 750.00, 20.00, 150.00, 600.00, '2025-09-20 08:04:11', NULL, 0),
(98, 88, 73, 3200, 25.00, 0.00, 0.00, 80000.00, '2025-09-20 08:27:30', NULL, 0),
(99, 88, 100, 1, 750.00, 20.00, 150.00, 600.00, '2025-09-20 08:27:30', NULL, 0),
(100, 89, 73, 12000, 8.00, 10.00, 9600.00, 86400.00, '2025-09-20 08:32:30', NULL, 0),
(101, 89, 41, 1, 3800.00, 5.00, 190.00, 3610.00, '2025-09-20 08:32:30', NULL, 0),
(102, 90, 73, 8000, 8.00, 0.00, 0.00, 64000.00, '2025-09-20 08:32:56', NULL, 0),
(103, 90, 77, 2, 650.00, 0.00, 0.00, 1300.00, '2025-09-20 08:32:56', NULL, 0),
(104, 90, 103, 1, 1500.00, 0.00, 0.00, 1500.00, '2025-09-20 08:32:56', NULL, 0),
(105, 90, 81, 3, 650.00, 0.00, 0.00, 1950.00, '2025-09-20 08:32:56', NULL, 0),
(106, 90, 71, 2, 2500.00, 0.00, 0.00, 5000.00, '2025-09-20 08:32:56', NULL, 0),
(107, 90, 102, 3, 1200.00, 0.00, 0.00, 3600.00, '2025-09-20 08:32:56', NULL, 0),
(108, 90, 101, 8, 690.00, 0.00, 0.00, 5520.00, '2025-09-20 08:32:56', NULL, 0),
(109, 91, 73, 8000, 8.00, 0.00, 0.00, 64000.00, '2025-09-20 08:33:31', NULL, 0),
(110, 91, 77, 2, 650.00, 0.00, 0.00, 1300.00, '2025-09-20 08:33:31', NULL, 0),
(111, 91, 103, 1, 1500.00, 0.00, 0.00, 1500.00, '2025-09-20 08:33:31', NULL, 0),
(112, 91, 81, 3, 650.00, 0.00, 0.00, 1950.00, '2025-09-20 08:33:31', NULL, 0),
(113, 91, 71, 2, 2500.00, 0.00, 0.00, 5000.00, '2025-09-20 08:33:31', NULL, 0),
(114, 91, 102, 3, 1200.00, 0.00, 0.00, 3600.00, '2025-09-20 08:33:31', NULL, 0),
(115, 91, 101, 8, 690.00, 0.00, 0.00, 5520.00, '2025-09-20 08:33:31', NULL, 0),
(116, 92, 73, 8000, 8.00, 0.00, 0.00, 64000.00, '2025-09-20 08:34:02', NULL, 0),
(117, 92, 77, 2, 650.00, 0.00, 0.00, 1300.00, '2025-09-20 08:34:02', NULL, 0),
(118, 92, 103, 1, 1500.00, 0.00, 0.00, 1500.00, '2025-09-20 08:34:02', NULL, 0),
(119, 92, 81, 3, 650.00, 0.00, 0.00, 1950.00, '2025-09-20 08:34:02', NULL, 0),
(120, 92, 71, 2, 2500.00, 0.00, 0.00, 5000.00, '2025-09-20 08:34:02', NULL, 0),
(121, 92, 102, 3, 1200.00, 0.00, 0.00, 3600.00, '2025-09-20 08:34:02', NULL, 0),
(122, 92, 101, 8, 690.00, 0.00, 0.00, 5520.00, '2025-09-20 08:34:02', NULL, 0),
(126, 95, 73, 5000, 6.00, 0.00, 0.00, 30000.00, '2025-09-20 10:20:08', NULL, 0),
(127, 95, 180, 2, 650.00, 0.00, 0.00, 1300.00, '2025-09-20 10:20:08', NULL, 0),
(128, 95, 77, 1, 650.00, 0.00, 0.00, 650.00, '2025-09-20 10:20:08', NULL, 0),
(129, 95, 103, 3, 1500.00, 0.00, 0.00, 4500.00, '2025-09-20 10:20:08', NULL, 0),
(130, 95, 175, 10, 75.00, 0.00, 0.00, 750.00, '2025-09-20 10:20:08', NULL, 0),
(131, 95, 114, 100, 140.00, 7.00, 980.00, 13020.00, '2025-09-20 10:20:08', NULL, 0),
(132, 95, 113, 16, 65.00, 0.00, 0.00, 1040.00, '2025-09-20 10:20:08', NULL, 0),
(133, 95, 112, 68, 75.00, 0.00, 0.00, 5100.00, '2025-09-20 10:20:08', NULL, 0),
(134, 95, 105, 105, 150.00, 0.00, 0.00, 15750.00, '2025-09-20 10:20:08', NULL, 0),
(135, 95, 101, 34, 690.00, 0.00, 0.00, 23460.00, '2025-09-20 10:20:08', NULL, 0),
(136, 95, 172, 4, 42.00, 0.00, 0.00, 168.00, '2025-09-20 10:20:08', NULL, 0),
(137, 95, 117, 50, 85.00, 29.41, 1249.93, 3000.08, '2025-09-20 10:20:08', NULL, 0),
(138, 95, 41, 34, 4800.00, 5.00, 8160.00, 155040.00, '2025-09-20 10:20:08', NULL, 0),
(139, 95, 159, 1, 480.00, 0.00, 0.00, 480.00, '2025-09-20 10:20:08', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quote_number` varchar(50) NOT NULL,
  `project_number` varchar(20) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `proposal_name` varchar(255) DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `total_discount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','sent','under_review','accepted','rejected','expired') DEFAULT 'draft',
  `valid_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `project_id` int(11) DEFAULT NULL COMMENT 'ID of the solar project created from this quotation',
  `has_installment_plan` tinyint(1) DEFAULT 0,
  `payment_terms` text DEFAULT NULL,
  `installment_status` enum('none','pending','active','completed','default') DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quote_number`, `project_number`, `customer_name`, `customer_phone`, `proposal_name`, `subtotal`, `total_discount`, `total_amount`, `status`, `valid_until`, `notes`, `created_by`, `created_at`, `updated_at`, `project_id`, `has_installment_plan`, `payment_terms`, `installment_status`) VALUES
(10, 'QTE20250904-0481', NULL, 'Gucela', '019872', '8kw Supply and installation', 99800.00, 9790.00, 90010.00, 'sent', NULL, NULL, 1, '2025-09-04 00:59:38', '2025-09-20 08:32:30', NULL, 0, NULL, 'none'),
(11, 'QTE20250904-0782', NULL, 'Missuara 8kw', '09173031588', 'Supply and Installation of 8kw to pangutaran', 269370.00, 0.00, 269370.00, 'draft', NULL, NULL, 1, '2025-09-04 03:19:46', '2025-09-20 08:34:02', NULL, 0, NULL, 'none'),
(13, 'QTE20250904-7312', NULL, 'Thai Alamia', '09173081539', 'Additional 16kw upgrade', 177980.00, 2925.00, 175055.00, 'draft', NULL, NULL, 1, '2025-09-04 09:49:31', '2025-09-05 08:07:04', NULL, 0, NULL, 'none'),
(18, 'QTE20250912-0659', 'PRJ-202509-0001', 'Pazlor Lim', '09750646424', '', 411576.00, 39999.60, 371576.40, 'draft', NULL, NULL, 9, '2025-09-12 04:00:13', '2025-09-17 09:45:03', NULL, 1, NULL, 'active'),
(24, 'QTE20250918-8805', NULL, 'Thai Alamia', '09173081539', 'Upgarade of Solar Panel Installation', 266348.00, 10389.93, 255958.08, 'draft', NULL, NULL, 9, '2025-09-18 03:18:18', '2025-09-18 04:01:20', NULL, 0, NULL, 'none'),
(27, 'QTE20250920-2471', 'PRJ-202509-0002', 'Andres Bonifacio', '09918195482', 'Testing', 122750.00, 150.00, 122600.00, 'draft', NULL, NULL, 1, '2025-09-20 02:56:55', '2025-09-20 10:13:36', NULL, 0, NULL, 'none'),
(28, 'QTE20250922-8696', NULL, 'Walk in customer ', '09', '', 2390.00, 0.00, 2390.00, 'draft', NULL, NULL, 9, '2025-09-22 02:01:39', '2025-09-22 02:02:31', NULL, 0, NULL, 'none'),
(29, 'QTE20250922-0750', NULL, 'KAP BALLAJO ', '09', '', 250000.00, 0.00, 250000.00, 'draft', NULL, NULL, 9, '2025-09-22 02:26:29', '2025-09-22 02:39:12', NULL, 0, NULL, 'none'),
(30, 'QTE20250922-1008', NULL, 'SHIK MOHAMOUD', '09', '', 254000.00, 0.00, 254000.00, 'draft', NULL, NULL, 9, '2025-09-22 02:53:13', '2025-09-22 03:01:26', NULL, 0, NULL, 'none'),
(31, 'QTE20250922-4405', NULL, 'PNP RAINBOW IPIL', '09', '', 229000.00, 0.00, 229000.00, 'draft', NULL, NULL, 9, '2025-09-22 03:05:37', '2025-09-22 03:10:49', NULL, 0, NULL, 'none'),
(33, 'QTE20250922-3419', NULL, 'PNP RAINBOW MERCEDES', '09', '', 218000.00, 0.00, 218000.00, 'draft', NULL, NULL, 9, '2025-09-22 03:19:45', '2025-09-22 03:23:36', NULL, 0, NULL, 'none'),
(34, 'QTE20250922-1169', NULL, 'PNP RAINBOW OWNER', '09', '', 141800.00, 0.00, 141800.00, 'draft', NULL, NULL, 9, '2025-09-22 03:26:47', '2025-09-22 03:28:32', NULL, 0, NULL, 'none');

-- --------------------------------------------------------

--
-- Table structure for table `quote_customer_info`
--

CREATE TABLE `quote_customer_info` (
  `id` int(11) NOT NULL,
  `quote_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_method_email` tinyint(1) DEFAULT 0,
  `contact_method_phone` tinyint(1) DEFAULT 0,
  `contact_method_sms` tinyint(1) DEFAULT 0,
  `account_creation_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quote_customer_info`
--

INSERT INTO `quote_customer_info` (`id`, `quote_id`, `full_name`, `phone_number`, `address`, `contact_method_email`, `contact_method_phone`, `contact_method_sms`, `account_creation_date`, `created_at`, `updated_at`) VALUES
(3, 18, 'Pazlor Lim', '09750646424', 'Villa Sta. Maria, Zamboanga City', 0, 1, 0, '2025-09-10', '2025-09-12 04:00:13', '2025-09-12 04:02:15'),
(9, 24, 'Thai Alamia', '09173081539', 'Tetuan Near UZ College', 0, 1, 0, '2025-09-18', '2025-09-18 03:18:18', '2025-09-18 03:18:18'),
(12, 27, 'Andrés Bonifacio y de Castro', '09918195482', 'Talon, ZC', 0, 1, 0, '2025-09-20', '2025-09-20 02:56:55', '2025-09-20 02:56:55'),
(13, 28, '', '', '', 0, 0, 0, '0000-00-00', '2025-09-22 02:01:39', '2025-09-22 02:01:39'),
(14, 29, 'KAP BALLAJO', '09', 'LAMITAN, BARANGAY BALAS', 0, 1, 0, '0000-00-00', '2025-09-22 02:26:29', '2025-09-22 02:26:29'),
(15, 30, 'SHEIK MOHAMOUD', '09569662311', 'STA.MARIA FAIRVIEW', 0, 1, 0, '2025-08-15', '2025-09-22 02:53:13', '2025-09-22 03:32:18'),
(16, 31, 'PNP RAINBOW', '09', 'IPIL Zamboanga Sibugay', 0, 1, 0, '0000-00-00', '2025-09-22 03:05:37', '2025-09-22 03:05:37'),
(18, 33, 'PNP RAINBOW MERCEDES', '09', 'MERCEDES, ZAMBOANGA CITY', 0, 1, 0, '0000-00-00', '2025-09-22 03:19:45', '2025-09-22 03:19:45'),
(19, 34, 'PNP RAINBOW OWNER', '09', '', 0, 1, 0, '0000-00-00', '2025-09-22 03:26:47', '2025-09-22 03:26:47');

-- --------------------------------------------------------

--
-- Table structure for table `quote_items`
--

CREATE TABLE `quote_items` (
  `id` int(11) NOT NULL,
  `quote_id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `serial_numbers` text DEFAULT NULL COMMENT 'JSON array of serial numbers reserved for this quote item',
  `serial_count` int(11) DEFAULT 0 COMMENT 'Number of serial numbers reserved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quote_items`
--

INSERT INTO `quote_items` (`id`, `quote_id`, `inventory_item_id`, `quantity`, `unit_price`, `discount_percentage`, `discount_amount`, `total_amount`, `created_at`, `serial_numbers`, `serial_count`) VALUES
(11, 10, 73, 12000, 8.00, 10.00, 9600.00, 86400.00, '2025-09-04 00:59:38', NULL, 0),
(12, 10, 41, 1, 3800.00, 5.00, 190.00, 3610.00, '2025-09-04 01:02:15', NULL, 0),
(14, 11, 73, 8000, 8.00, 0.00, 0.00, 64000.00, '2025-09-04 03:19:46', NULL, 0),
(16, 11, 39, 1, 4300.00, 0.00, 0.00, 4300.00, '2025-09-04 03:24:26', NULL, 0),
(17, 11, 107, 1, 110000.00, 0.00, 0.00, 110000.00, '2025-09-04 03:26:55', NULL, 0),
(18, 11, 77, 2, 650.00, 0.00, 0.00, 1300.00, '2025-09-04 03:32:04', NULL, 0),
(19, 11, 56, 1, 69000.00, 0.00, 0.00, 69000.00, '2025-09-04 03:38:53', NULL, 0),
(20, 11, 108, 1, 3200.00, 0.00, 0.00, 3200.00, '2025-09-04 03:50:58', NULL, 0),
(21, 11, 103, 1, 1500.00, 0.00, 0.00, 1500.00, '2025-09-04 03:51:05', NULL, 0),
(22, 11, 81, 3, 650.00, 0.00, 0.00, 1950.00, '2025-09-04 03:54:12', NULL, 0),
(23, 11, 71, 2, 2500.00, 0.00, 0.00, 5000.00, '2025-09-04 03:55:23', NULL, 0),
(24, 11, 102, 3, 1200.00, 0.00, 0.00, 3600.00, '2025-09-04 04:00:01', NULL, 0),
(25, 11, 101, 8, 690.00, 0.00, 0.00, 5520.00, '2025-09-04 04:04:31', NULL, 0),
(27, 13, 73, 4000, 10.00, 0.00, 0.00, 40000.00, '2025-09-04 09:49:31', NULL, 0),
(28, 13, 59, 1, 115000.00, 0.00, 0.00, 115000.00, '2025-09-04 09:49:39', NULL, 0),
(29, 13, 118, 2, 4500.00, 0.00, 0.00, 9000.00, '2025-09-04 09:57:56', NULL, 0),
(30, 13, 119, 20, 450.00, 20.00, 1800.00, 7200.00, '2025-09-04 11:07:09', NULL, 0),
(32, 13, 121, 1, 480.00, 0.00, 0.00, 480.00, '2025-09-04 11:12:47', NULL, 0),
(33, 13, 122, 1, 4500.00, 25.00, 1125.00, 3375.00, '2025-09-05 02:11:23', NULL, 0),
(47, 18, 73, 24000, 5.00, 33.33, 39999.60, 80000.40, '2025-09-12 04:00:14', NULL, 0),
(48, 18, 58, 2, 84500.00, 0.00, 0.00, 169000.00, '2025-09-15 01:10:16', NULL, 0),
(49, 18, 116, 3, 350.00, 0.00, 0.00, 1050.00, '2025-09-15 05:22:39', NULL, 0),
(50, 18, 115, 3, 350.00, 0.00, 0.00, 1050.00, '2025-09-15 05:23:05', NULL, 0),
(56, 18, 114, 200, 110.00, 0.00, 0.00, 22000.00, '2025-09-17 06:09:26', NULL, 0),
(58, 18, 166, 40, 30.00, 0.00, 0.00, 1200.00, '2025-09-17 06:14:04', NULL, 0),
(59, 18, 169, 40, 24.00, 0.00, 0.00, 960.00, '2025-09-17 06:14:27', NULL, 0),
(60, 18, 128, 2, 2850.00, 0.00, 0.00, 5700.00, '2025-09-17 06:14:57', NULL, 0),
(61, 18, 177, 30, 62.00, 0.00, 0.00, 1860.00, '2025-09-17 06:19:11', NULL, 0),
(62, 18, 176, 190, 100.00, 0.00, 0.00, 19000.00, '2025-09-17 06:19:32', NULL, 0),
(63, 18, 174, 5, 28.00, 0.00, 0.00, 140.00, '2025-09-17 06:21:55', NULL, 0),
(64, 18, 172, 5, 42.00, 0.00, 0.00, 210.00, '2025-09-17 06:22:09', NULL, 0),
(65, 18, 158, 4, 380.00, 0.00, 0.00, 1520.00, '2025-09-17 06:22:44', NULL, 0),
(66, 18, 143, 8, 600.00, 0.00, 0.00, 4800.00, '2025-09-17 06:23:48', NULL, 0),
(67, 18, 103, 4, 880.00, 0.00, 0.00, 3520.00, '2025-09-17 06:26:27', NULL, 0),
(68, 18, 108, 2, 430.00, 0.00, 0.00, 860.00, '2025-09-17 06:26:54', NULL, 0),
(69, 18, 175, 8, 60.00, 0.00, 0.00, 480.00, '2025-09-17 06:27:16', NULL, 0),
(70, 18, 104, 1, 2500.00, 0.00, 0.00, 2500.00, '2025-09-17 06:46:45', NULL, 0),
(71, 18, 120, 1, 2900.00, 0.00, 0.00, 2900.00, '2025-09-17 06:49:44', NULL, 0),
(72, 18, 101, 48, 570.00, 0.00, 0.00, 27360.00, '2025-09-17 06:50:28', NULL, 0),
(73, 18, 105, 112, 80.00, 0.00, 0.00, 8960.00, '2025-09-17 06:51:39', NULL, 0),
(74, 18, 113, 20, 45.00, 0.00, 0.00, 900.00, '2025-09-17 06:52:53', NULL, 0),
(75, 18, 164, 1, 1400.00, 0.00, 0.00, 1400.00, '2025-09-17 07:00:20', NULL, 0),
(76, 18, 112, 96, 45.00, 0.00, 0.00, 4320.00, '2025-09-17 07:01:09', NULL, 0),
(77, 18, 110, 1, 2800.00, 0.00, 0.00, 2800.00, '2025-09-17 07:01:40', NULL, 0),
(78, 18, 117, 50, 45.00, 0.00, 0.00, 2250.00, '2025-09-17 07:02:52', NULL, 0),
(79, 18, 159, 1, 480.00, 0.00, 0.00, 480.00, '2025-09-17 07:04:12', NULL, 0),
(80, 18, 161, 7, 68.00, 0.00, 0.00, 476.00, '2025-09-17 07:04:47', NULL, 0),
(81, 18, 162, 8, 260.00, 0.00, 0.00, 2080.00, '2025-09-17 07:05:01', NULL, 0),
(82, 18, 81, 4, 450.00, 0.00, 0.00, 1800.00, '2025-09-17 07:07:56', NULL, 0),
(84, 24, 73, 5000, 6.00, 0.00, 0.00, 30000.00, '2025-09-18 03:18:18', NULL, 0),
(86, 24, 180, 2, 650.00, 0.00, 0.00, 1300.00, '2025-09-18 03:19:52', NULL, 0),
(87, 24, 77, 1, 650.00, 0.00, 0.00, 650.00, '2025-09-18 03:20:45', NULL, 0),
(88, 24, 103, 3, 1500.00, 0.00, 0.00, 4500.00, '2025-09-18 03:21:07', NULL, 0),
(89, 24, 108, 2, 850.00, 0.00, 0.00, 1700.00, '2025-09-18 03:21:35', NULL, 0),
(90, 24, 175, 10, 75.00, 0.00, 0.00, 750.00, '2025-09-18 03:21:54', NULL, 0),
(91, 24, 114, 100, 140.00, 7.00, 980.00, 13020.00, '2025-09-18 03:22:11', NULL, 0),
(92, 24, 113, 16, 65.00, 0.00, 0.00, 1040.00, '2025-09-18 03:22:46', NULL, 0),
(93, 24, 112, 68, 75.00, 0.00, 0.00, 5100.00, '2025-09-18 03:22:59', NULL, 0),
(94, 24, 105, 105, 150.00, 0.00, 0.00, 15750.00, '2025-09-18 03:23:28', NULL, 0),
(95, 24, 101, 34, 690.00, 0.00, 0.00, 23460.00, '2025-09-18 03:23:46', NULL, 0),
(96, 24, 172, 4, 42.00, 0.00, 0.00, 168.00, '2025-09-18 03:24:17', NULL, 0),
(97, 24, 117, 50, 85.00, 29.41, 1249.93, 3000.08, '2025-09-18 03:52:21', NULL, 0),
(98, 24, 41, 34, 4800.00, 5.00, 8160.00, 155040.00, '2025-09-18 03:54:57', NULL, 0),
(99, 24, 159, 1, 480.00, 0.00, 0.00, 480.00, '2025-09-18 04:00:15', NULL, 0),
(110, 27, 73, 3200, 25.00, 0.00, 0.00, 80000.00, '2025-09-20 02:56:55', NULL, 0),
(113, 27, 100, 1, 750.00, 20.00, 150.00, 600.00, '2025-09-20 08:02:54', NULL, 0),
(114, 27, 191, 1, 42000.00, 0.00, 0.00, 42000.00, '2025-09-20 10:13:36', '[\"Alltopelec 51.2V 100-2025-000001\"]', 1),
(115, 28, 101, 2, 690.00, 0.00, 0.00, 1380.00, '2025-09-22 02:01:56', NULL, 0),
(116, 28, 112, 2, 75.00, 0.00, 0.00, 150.00, '2025-09-22 02:02:06', NULL, 0),
(117, 28, 113, 4, 65.00, 0.00, 0.00, 260.00, '2025-09-22 02:02:18', NULL, 0),
(118, 28, 105, 4, 150.00, 0.00, 0.00, 600.00, '2025-09-22 02:02:31', NULL, 0),
(120, 29, 123, 30, 4500.00, 0.00, 0.00, 135000.00, '2025-09-22 02:36:27', '[\"CANADIAN-2025-000001\",\"CANADIAN-2025-000002\",\"CANADIAN-2025-000003\",\"CANADIAN-2025-000004\",\"CANADIAN-2025-000005\",\"CANADIAN-2025-000006\",\"CANADIAN-2025-000007\",\"CANADIAN-2025-000008\",\"CANADIAN-2025-000009\",\"CANADIAN-2025-000010\",\"CANADIAN-2025-000011\",\"CANADIAN-2025-000012\",\"CANADIAN-2025-000013\",\"CANADIAN-2025-000014\",\"CANADIAN-2025-000015\",\"CANADIAN-2025-000016\",\"CANADIAN-2025-000017\",\"CANADIAN-2025-000018\",\"CANADIAN-2025-000019\",\"CANADIAN-2025-000020\",\"CANADIAN-2025-000021\",\"CANADIAN-2025-000022\",\"CANADIAN-2025-000023\",\"CANADIAN-2025-000024\",\"CANADIAN-2025-000025\",\"CANADIAN-2025-000026\",\"CANADIAN-2025-000027\",\"CANADIAN-2025-000028\",\"CANADIAN-2025-000029\",\"CANADIAN-2025-000030\"]', 30),
(121, 29, 59, 1, 115000.00, 0.00, 0.00, 115000.00, '2025-09-22 02:39:12', '[\"HYB-2025-000001\"]', 1),
(122, 30, 56, 1, 69000.00, 0.00, 0.00, 69000.00, '2025-09-22 02:55:38', '[\"HYB-2025-000002\"]', 1),
(123, 30, 123, 20, 4500.00, 0.00, 0.00, 90000.00, '2025-09-22 03:00:16', '[\"CANADIAN-2025-000031\",\"CANADIAN-2025-000032\",\"CANADIAN-2025-000033\",\"CANADIAN-2025-000034\",\"CANADIAN-2025-000035\",\"CANADIAN-2025-000036\",\"CANADIAN-2025-000037\",\"CANADIAN-2025-000038\",\"CANADIAN-2025-000039\",\"CANADIAN-2025-000040\",\"CANADIAN-2025-000041\",\"CANADIAN-2025-000042\",\"CANADIAN-2025-000043\",\"CANADIAN-2025-000044\",\"CANADIAN-2025-000045\",\"CANADIAN-2025-000046\",\"CANADIAN-2025-000047\",\"CANADIAN-2025-000048\",\"CANADIAN-2025-000049\",\"CANADIAN-2025-000050\"]', 20),
(124, 30, 98, 1, 95000.00, 0.00, 0.00, 95000.00, '2025-09-22 03:01:26', '[\"BAT-2025-000006\"]', 1),
(125, 31, 56, 1, 69000.00, 0.00, 0.00, 69000.00, '2025-09-22 03:07:16', '[\"HYB-2025-000003\"]', 1),
(126, 31, 33, 18, 4500.00, 0.00, 0.00, 81000.00, '2025-09-22 03:09:07', '[\"PAN-2025-000008\",\"PAN-2025-000009\",\"PAN-2025-000010\",\"PAN-2025-000011\",\"PAN-2025-000012\",\"PAN-2025-000013\",\"PAN-2025-000014\",\"PAN-2025-000015\",\"PAN-2025-000016\",\"PAN-2025-000017\",\"PAN-2025-000018\",\"PAN-2025-000019\",\"PAN-2025-000020\",\"PAN-2025-000021\",\"PAN-2025-000022\",\"PAN-2025-000023\",\"PAN-2025-000024\",\"PAN-2025-000025\"]', 18),
(127, 31, 193, 1, 79000.00, 0.00, 0.00, 79000.00, '2025-09-22 03:10:49', '[\"BAT-2025-000001\"]', 1),
(128, 33, 58, 1, 95000.00, 0.00, 0.00, 95000.00, '2025-09-22 03:20:33', NULL, 0),
(129, 33, 33, 18, 4500.00, 0.00, 0.00, 81000.00, '2025-09-22 03:22:48', NULL, 0),
(130, 33, 191, 1, 42000.00, 0.00, 0.00, 42000.00, '2025-09-22 03:23:13', NULL, 0),
(131, 34, 54, 1, 42300.00, 0.00, 0.00, 42300.00, '2025-09-22 03:27:49', NULL, 0),
(132, 34, 33, 1, 4500.00, 0.00, 0.00, 4500.00, '2025-09-22 03:28:18', NULL, 0),
(133, 34, 98, 1, 95000.00, 0.00, 0.00, 95000.00, '2025-09-22 03:28:32', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `quote_solar_details`
--

CREATE TABLE `quote_solar_details` (
  `id` int(11) NOT NULL,
  `quote_id` int(11) NOT NULL,
  `system_type_grid_tie` tinyint(1) DEFAULT 0,
  `system_type_off_grid` tinyint(1) DEFAULT 0,
  `system_type_hybrid` tinyint(1) DEFAULT 0,
  `system_size_kw` decimal(8,2) DEFAULT NULL,
  `installation_type_rooftop` tinyint(1) DEFAULT 0,
  `installation_type_ground_mounted` tinyint(1) DEFAULT 0,
  `installation_type_carport` tinyint(1) DEFAULT 0,
  `panel_brand_model` varchar(255) DEFAULT NULL,
  `inverter_brand_model` varchar(255) DEFAULT NULL,
  `estimated_installation_date` date DEFAULT NULL,
  `installation_status_planned` tinyint(1) DEFAULT 0,
  `installation_status_in_progress` tinyint(1) DEFAULT 0,
  `installation_status_completed` tinyint(1) DEFAULT 0,
  `installation_status_maintenance` tinyint(1) DEFAULT 0,
  `battery_backup_capacity` enum('yes','no') DEFAULT NULL,
  `battery_capacity_value` varchar(100) DEFAULT NULL,
  `net_metering` enum('yes','no') DEFAULT NULL,
  `confirmed` enum('yes','no') DEFAULT NULL,
  `client_signature` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quote_solar_details`
--

INSERT INTO `quote_solar_details` (`id`, `quote_id`, `system_type_grid_tie`, `system_type_off_grid`, `system_type_hybrid`, `system_size_kw`, `installation_type_rooftop`, `installation_type_ground_mounted`, `installation_type_carport`, `panel_brand_model`, `inverter_brand_model`, `estimated_installation_date`, `installation_status_planned`, `installation_status_in_progress`, `installation_status_completed`, `installation_status_maintenance`, `battery_backup_capacity`, `battery_capacity_value`, `net_metering`, `confirmed`, `client_signature`, `remarks`, `created_at`, `updated_at`) VALUES
(3, 18, 0, 0, 1, 24.00, 1, 0, 0, 'TRINA SOLAR 600W- 48pcs', 'DEYE 12kw 2pcs', '2025-09-10', 1, 0, 0, 0, 'yes', '3 pcs 300Ah', 'no', 'yes', '', '', '2025-09-12 04:00:14', '2025-09-12 04:02:15'),
(9, 24, 0, 0, 1, 60.00, 1, 0, 0, '600 - 34', 'Deye', '2025-09-22', 1, 0, 0, 0, 'yes', '', 'no', 'yes', '', '', '2025-09-18 03:18:18', '2025-09-18 03:18:18'),
(12, 27, 0, 1, 1, 3200.00, 1, 0, 0, 'Canadian', 'Deye', '2025-09-26', 1, 0, 0, 0, 'no', '', 'no', 'no', '', '', '2025-09-20 02:56:55', '2025-09-20 02:56:55'),
(13, 28, 0, 0, 0, 0.00, 0, 0, 0, '', '', '0000-00-00', 0, 0, 0, 0, NULL, '', NULL, NULL, '', '', '2025-09-22 02:01:39', '2025-09-22 02:01:39'),
(14, 29, 0, 0, 1, 16.00, 1, 0, 0, '', '', '0000-00-00', 0, 0, 0, 0, NULL, '', NULL, NULL, '', '', '2025-09-22 02:26:29', '2025-09-22 02:26:29'),
(15, 30, 0, 0, 1, 8.00, 1, 0, 0, '', '', '0000-00-00', 0, 0, 0, 0, NULL, '', NULL, NULL, '', 'Temporary Battery installed for checking', '2025-09-22 02:53:13', '2025-09-22 03:32:18'),
(16, 31, 0, 0, 1, 0.00, 1, 0, 0, '', '', '0000-00-00', 0, 0, 0, 0, NULL, '', NULL, NULL, '', '', '2025-09-22 03:05:37', '2025-09-22 03:05:37'),
(18, 33, 0, 0, 1, 0.00, 1, 0, 0, '', '', '0000-00-00', 0, 0, 0, 0, NULL, '', NULL, NULL, '', '', '2025-09-22 03:19:45', '2025-09-22 03:19:45'),
(19, 34, 0, 0, 1, 0.00, 1, 0, 0, '', '', '0000-00-00', 0, 0, 0, 0, NULL, '', NULL, NULL, '', '', '2025-09-22 03:26:47', '2025-09-22 03:26:47');

-- --------------------------------------------------------

--
-- Table structure for table `solar_projects`
--

CREATE TABLE `solar_projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(100) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `system_size_kw` decimal(8,2) DEFAULT NULL,
  `total_base_cost` decimal(12,2) DEFAULT 0.00,
  `total_selling_price` decimal(12,2) DEFAULT 0.00,
  `total_discount` decimal(12,2) DEFAULT 0.00,
  `final_amount` decimal(12,2) DEFAULT 0.00,
  `project_status` enum('draft','quoted','approved','in_progress','completed','cancelled') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `quote_id` int(11) DEFAULT NULL COMMENT 'ID of the quotation that created this project'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `solar_projects`
--

INSERT INTO `solar_projects` (`id`, `project_name`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `remarks`, `system_size_kw`, `total_base_cost`, `total_selling_price`, `total_discount`, `final_amount`, `project_status`, `created_at`, `updated_at`, `created_by`, `quote_id`) VALUES
(19, '', 'Pazlor Lim', '', '09750646424', '', 'Converted from quotation QTE20250912-0659', 0.00, 0.00, 80000.00, 0.00, 80000.00, 'approved', '2025-09-12 04:02:55', '2025-09-12 04:02:55', 9, 18);

-- --------------------------------------------------------

--
-- Table structure for table `solar_project_items`
--

CREATE TABLE `solar_project_items` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_base_price` decimal(10,2) NOT NULL,
  `unit_selling_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `serial_numbers` text DEFAULT NULL COMMENT 'JSON array of serial numbers used in this project item',
  `serial_count` int(11) DEFAULT 0 COMMENT 'Number of serial numbers used'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `solar_project_items`
--

INSERT INTO `solar_project_items` (`id`, `project_id`, `inventory_item_id`, `quantity`, `unit_base_price`, `unit_selling_price`, `discount_amount`, `total_amount`, `serial_numbers`, `serial_count`) VALUES
(42, 19, 73, 16000, 0.00, 5.00, 0.00, 80000.00, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `reference_type` enum('purchase','sale','project','adjustment','return') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `inventory_item_id`, `movement_type`, `quantity`, `previous_stock`, `new_stock`, `reference_type`, `reference_id`, `notes`, `created_at`, `created_by`) VALUES
(29, 71, 'out', 1, 5, 4, '', 36, 'POS sale - RCP20250903-1214', '2025-09-03 04:42:48', 9),
(30, 71, 'out', 1, 4, 3, '', 37, 'POS sale - RCP20250904-9206', '2025-09-04 02:14:56', 1),
(31, 114, 'out', 12, 100, 88, '', 38, 'POS sale - RCP20250904-2146', '2025-09-04 05:41:43', 9),
(32, 75, 'out', 2, 46, 44, '', 38, 'POS sale - RCP20250904-2146', '2025-09-04 05:41:43', 9),
(33, 123, 'out', 4, 14, 10, '', 46, 'POS sale - RCP20250908-2619', '2025-09-08 05:54:16', 9),
(34, 114, 'out', 20, 88, 68, '', 47, 'POS sale - RCP20250910-0185', '2025-09-10 04:27:44', 9),
(35, 114, 'out', 10, 68, 58, '', 48, 'POS sale - RCP20250911-4592', '2025-09-11 02:13:42', 9),
(36, 114, 'out', 1, 58, 57, '', 49, 'POS sale - RCP20250916-4796', '2025-09-16 05:22:14', 9),
(37, 175, 'out', 5, 16, 11, '', 53, 'POS sale - RCP20250916-5335', '2025-09-16 07:21:39', 9),
(38, 73, 'out', 3200, 9999, 6799, '', 22, 'Inventory deducted for approved quotation', '2025-09-17 02:00:48', 1),
(39, 126, 'out', 1, 6, 5, '', 22, 'Inventory deducted for approved quotation', '2025-09-17 02:00:48', 1),
(40, 26, 'out', 2, 14, 12, '', 55, 'POS sale - RCP20250918-5317', '2025-09-18 00:59:46', 9),
(41, 101, 'out', 2, 32, 30, '', 55, 'POS sale - RCP20250918-5317', '2025-09-18 00:59:46', 9),
(42, 105, 'out', 4, 20, 16, '', 55, 'POS sale - RCP20250918-5317', '2025-09-18 00:59:46', 9),
(43, 112, 'out', 2, 6, 4, '', 55, 'POS sale - RCP20250918-5317', '2025-09-18 00:59:46', 9),
(44, 26, 'out', 2, 14, 12, '', 59, 'POS sale - RCP20250918-1469', '2025-09-18 01:24:04', 9),
(45, 39, 'out', 1, 1, 0, '', 63, 'POS sale - RCP20250918-3025', '2025-09-18 08:39:13', 9),
(46, 96, 'out', 1, 5, 5, 'sale', 70, 'Item added to POS sale (serials reserved)', '2025-09-19 09:19:51', 1),
(47, 96, 'out', 1, 5, 4, 'sale', 70, 'POS sale (serials) - RCP20250919-0236', '2025-09-19 09:19:56', 1),
(48, 96, 'out', 1, 4, 4, 'sale', 71, 'Item added to POS sale (serials reserved)', '2025-09-19 09:32:28', 1),
(49, 96, 'out', 1, 4, 4, 'sale', 71, 'Item added to POS sale (serials reserved)', '2025-09-19 09:32:53', 1),
(50, 96, 'out', 2, 4, 2, 'sale', 71, 'POS sale (serials) - RCP20250919-9967', '2025-09-19 09:33:10', 1),
(51, 96, 'out', 2, 2, 2, 'sale', 73, 'Item added to POS sale (serials reserved)', '2025-09-19 09:34:14', 1),
(52, 96, 'out', 2, 2, 0, 'sale', 73, 'POS sale (serials) - RCP20250919-6957', '2025-09-19 09:34:25', 1),
(53, 73, 'out', 3200, 6799, 3599, 'sale', 25, 'Inventory deducted for approved quotation', '2025-09-19 09:44:13', 1),
(54, 97, 'out', 2, 2, 0, 'sale', 25, 'Inventory deducted for approved quotation', '2025-09-19 09:44:13', 1),
(55, 73, 'out', 3200, 3599, 399, 'sale', 26, 'Inventory deducted for approved quotation', '2025-09-20 01:54:51', 1),
(56, 192, 'out', 1, 2, 1, 'sale', 26, 'Inventory deducted for approved quotation', '2025-09-20 01:54:51', 1),
(61, 73, 'out', 3200, 2147483647, 2147480447, 'sale', 27, 'Inventory deducted for approved quotation', '2025-09-20 03:01:43', 1),
(62, 193, 'out', 1, 5, 4, 'sale', 27, 'Inventory deducted for approved quotation', '2025-09-20 03:01:43', 1),
(63, 73, 'in', 3200, 2147480447, 2147483647, 'return', 27, 'Inventory restored - quote status reverted', '2025-09-20 03:02:06', 1),
(64, 193, 'in', 1, 4, 5, 'return', 27, 'Inventory restored - quote status reverted', '2025-09-20 03:02:06', 1),
(65, 73, 'out', 12000, 2147483647, 2147471647, 'sale', 10, 'Inventory deducted for approved quotation', '2025-09-20 03:06:59', 1),
(66, 41, 'out', 1, 14, 13, 'sale', 10, 'Inventory deducted for approved quotation', '2025-09-20 03:06:59', 1),
(67, 73, 'in', 12000, 2147471647, 2147483647, 'return', 10, 'Inventory restored - quote status reverted', '2025-09-20 03:06:59', 1),
(68, 41, 'in', 1, 13, 14, 'return', 10, 'Inventory restored - quote status reverted', '2025-09-20 03:06:59', 1),
(73, 79, 'out', 2, 5, 3, 'sale', 82, 'POS sale - RCP20250920-9269', '2025-09-20 07:10:55', 1),
(74, 191, 'in', 1, 4, 5, 'adjustment', NULL, '', '2025-09-20 07:14:49', 1),
(75, 98, 'in', 4, 0, 4, 'adjustment', NULL, '', '2025-09-20 07:16:41', 1),
(90, 73, 'out', 3200, 2147483647, 2147480447, 'sale', 83, 'POS sale - RCP20250920-3178', '2025-09-20 07:56:13', 1),
(91, 100, 'out', 1, 3, 2, 'sale', 83, 'POS sale - RCP20250920-3178', '2025-09-20 07:56:13', 1),
(92, 100, 'in', 1, 2, 3, 'adjustment', NULL, '', '2025-09-20 08:02:16', 1),
(93, 73, 'out', 3200, 2147480447, 2147477247, 'sale', 84, 'POS sale - RCP20250920-0908', '2025-09-20 08:04:19', 1),
(94, 100, 'out', 1, 3, 2, 'sale', 84, 'POS sale - RCP20250920-0908', '2025-09-20 08:04:19', 1),
(95, 73, 'out', 3200, 2147477247, 2147474047, 'sale', 88, 'POS sale - RCP20250920-8303', '2025-09-20 08:27:39', 1),
(96, 100, 'out', 1, 2, 1, 'sale', 88, 'POS sale - RCP20250920-8303', '2025-09-20 08:27:39', 1),
(97, 22, 'in', 1, 0, 1, 'adjustment', NULL, 'Test adjustment', '2025-09-20 08:32:57', NULL),
(98, 22, 'in', 1, 1, 2, 'adjustment', NULL, 'Test adjustment', '2025-09-20 08:33:31', 1),
(99, 22, 'in', 1, 2, 3, 'adjustment', NULL, 'Test adjustment', '2025-09-20 08:33:46', 1),
(100, 22, 'out', 1, 3, 2, 'adjustment', NULL, 'Test revert', '2025-09-20 08:33:46', 1),
(101, 22, 'in', 1, 2, 3, 'adjustment', NULL, 'Test adjustment', '2025-09-20 08:34:02', 1),
(102, 22, 'out', 1, 3, 2, 'adjustment', NULL, 'Test revert', '2025-09-20 08:34:02', 1),
(108, 191, 'out', 4, 5, 1, 'adjustment', NULL, 'Testing if the update stock is working', '2025-09-22 04:00:24', 1),
(109, 191, 'in', 4, 1, 5, 'adjustment', NULL, 'Testing if the update stock is working', '2025-09-22 04:01:01', 1),
(110, 191, 'out', 1, 5, 4, 'adjustment', NULL, 'Testing if the update stock is working - deduction', '2025-09-22 04:01:40', 1);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `created_at`, `updated_at`, `is_active`) VALUES
(5, 'GREEWAT HYBRID SOLUTIONS', 'Ms. Jahzeel', 'jahzeel@gmail.com', '09063272815', 'NCR Metro Manila Philippines', '2025-08-27 06:01:45', '2025-09-04 01:12:43', 1),
(6, 'ALLTOPELEC', 'Sir Victor', 'altopelec@gmail.com', '0218452245', 'Cagayan De Oro', '2025-08-28 00:47:42', '2025-08-28 03:39:29', 1),
(9, 'CENTURY LIGHT CENTER', 'SOON', 'EMAIL@EMAIL.COM', '(062) 991 3528', 'Gov. ALvarez St. Zamboanga City', '2025-09-04 11:02:10', '2025-09-04 11:02:10', 1),
(10, 'HOME DEPOT', '', '', '', '', '2025-09-19 03:42:20', '2025-09-19 08:12:05', 0),
(11, 'HOME STYLE ', '', 'homestyledepot@yahoo.com', '955 28 28', 'VETERANCE AVE', '2025-09-19 03:45:09', '2025-09-19 03:51:56', 0),
(12, 'PHONE PATCH MARKETING ', '0962-9920280 SALES', '', '', 'Lapurisima st. Brgy.Zone 2 Zamboanga city', '2025-09-19 03:48:58', '2025-09-19 03:48:58', 1),
(13, 'HOME STYLE ', '', 'homestyledepot@yahoo.com', '955 28 28', 'VETERANCE AVE', '2025-09-19 03:51:41', '2025-09-19 05:37:22', 0),
(14, 'HOME DEPOT', '', '', '', '', '2025-09-20 01:50:17', '2025-09-20 01:50:17', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','hr','sales') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `full_name`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'admin', '$2y$10$AkjxIDoeDfwa6DKC5ZRg7ey7SKXspi1QYEGQ8TreL7B07TS2O0KlK', 'admin@4nsolar.com', 'admin', 'Sir Novie G. Mohadsa', '2025-08-26 02:04:18', '2025-09-20 08:59:37', 1),
(8, '4nsolar_sales', '$2y$10$iyeR8lt3Wc5s/taRBdDZOOyycXLYUvz8Oj9a6UxgZc5FW2hHTPtUu', 'dp507747@gmail.com', 'sales', 'Ma. Donna Pingoy', '2025-08-28 01:23:15', '2025-08-28 01:23:24', 1),
(9, '4nsolar_hr', '$2y$10$yqP6TipLIsdgxEbEB98ri.nGVbS/bz8WJME2LP7OXcuONHce1Ju2K', 'liddylouorsuga506@gmail.com', 'hr', 'Liddy Lou Orsuga', '2025-08-28 01:44:52', '2025-08-28 01:44:52', 1);

-- --------------------------------------------------------

--
-- Structure for view `available_serials`
--
DROP TABLE IF EXISTS `available_serials`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `available_serials`  AS SELECT `i`.`id` AS `inventory_item_id`, `i`.`brand` AS `brand`, `i`.`model` AS `model`, `i`.`category_id` AS `category_id`, `c`.`name` AS `category_name`, `i`.`size_specification` AS `size_specification`, `i`.`selling_price` AS `selling_price`, count(`s`.`id`) AS `available_serials_count`, group_concat(`s`.`serial_number` order by `s`.`serial_number` ASC separator ', ') AS `available_serial_numbers` FROM ((`inventory_items` `i` left join `categories` `c` on(`i`.`category_id` = `c`.`id`)) left join `inventory_serials` `s` on(`i`.`id` = `s`.`inventory_item_id` and `s`.`status` = 'available')) WHERE `i`.`is_active` = 1 AND `i`.`generate_serials` = 1 GROUP BY `i`.`id`, `i`.`brand`, `i`.`model`, `i`.`category_id`, `c`.`name`, `i`.`size_specification`, `i`.`selling_price` HAVING `available_serials_count` > 0 ;

-- --------------------------------------------------------

--
-- Structure for view `inventory`
--
DROP TABLE IF EXISTS `inventory`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `inventory`  AS SELECT `inventory_items`.`id` AS `id`, `inventory_items`.`brand` AS `brand`, `inventory_items`.`model` AS `model`, `inventory_items`.`category_id` AS `category_id`, `inventory_items`.`size_specification` AS `size_specification`, `inventory_items`.`base_price` AS `base_price`, `inventory_items`.`selling_price` AS `selling_price`, `inventory_items`.`discount_percentage` AS `discount_percentage`, `inventory_items`.`supplier_id` AS `supplier_id`, `inventory_items`.`stock_quantity` AS `stock_quantity`, `inventory_items`.`minimum_stock` AS `minimum_stock`, `inventory_items`.`description` AS `description`, `inventory_items`.`image_path` AS `image_path`, `inventory_items`.`specifications` AS `specifications`, `inventory_items`.`created_at` AS `created_at`, `inventory_items`.`updated_at` AS `updated_at`, `inventory_items`.`created_by` AS `created_by`, `inventory_items`.`is_active` AS `is_active` FROM `inventory_items` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `idx_employee_code` (`employee_code`),
  ADD KEY `idx_employee_name` (`employee_name`);

--
-- Indexes for table `employee_attendance`
--
ALTER TABLE `employee_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_date` (`employee_id`,`attendance_date`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_employee_attendance` (`employee_id`,`attendance_date`);

--
-- Indexes for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_leaves` (`employee_id`),
  ADD KEY `idx_leave_dates` (`start_date`,`end_date`),
  ADD KEY `idx_leave_status` (`status`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `installment_payments`
--
ALTER TABLE `installment_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_installment` (`plan_id`,`installment_number`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `paid_by` (`paid_by`),
  ADD KEY `idx_installment_due_date` (`due_date`),
  ADD KEY `idx_installment_status` (`status`);

--
-- Indexes for table `installment_plans`
--
ALTER TABLE `installment_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_plan_status` (`status`);

--
-- Indexes for table `installment_settings`
--
ALTER TABLE `installment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `installment_transactions`
--
ALTER TABLE `installment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_serials`
--
ALTER TABLE `inventory_serials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `inventory_item_id` (`inventory_item_id`),
  ADD KEY `status` (`status`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `quote_id` (`quote_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_inventory_serials_item_status` (`inventory_item_id`,`status`),
  ADD KEY `idx_inventory_serials_available` (`status`,`inventory_item_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_payroll` (`employee_id`),
  ADD KEY `idx_pay_period` (`pay_period_start`,`pay_period_end`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payroll_deductions` (`payroll_id`);

--
-- Indexes for table `payroll_packages`
--
ALTER TABLE `payroll_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payroll_packages` (`payroll_id`);

--
-- Indexes for table `pos_sales`
--
ALTER TABLE `pos_sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `idx_receipt_number` (`receipt_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_pos_sales_created_by` (`created_by`),
  ADD KEY `idx_pos_sales_customer` (`customer_name`,`customer_phone`),
  ADD KEY `idx_pos_sales_payment` (`payment_method`,`status`);

--
-- Indexes for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_id` (`sale_id`),
  ADD KEY `idx_inventory_item_id` (`inventory_item_id`),
  ADD KEY `idx_pos_sale_items_composite` (`sale_id`,`inventory_item_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quote_number` (`quote_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_quotations_quote_number` (`quote_number`),
  ADD KEY `idx_quotations_status` (`status`),
  ADD KEY `idx_quotations_created_at` (`created_at`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_project_number` (`project_number`),
  ADD KEY `idx_quotation_installment` (`has_installment_plan`);

--
-- Indexes for table `quote_customer_info`
--
ALTER TABLE `quote_customer_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quote_id` (`quote_id`);

--
-- Indexes for table `quote_items`
--
ALTER TABLE `quote_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quote_items_quote_id` (`quote_id`),
  ADD KEY `idx_quote_items_inventory_item_id` (`inventory_item_id`);

--
-- Indexes for table `quote_solar_details`
--
ALTER TABLE `quote_solar_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quote_id` (`quote_id`);

--
-- Indexes for table `solar_projects`
--
ALTER TABLE `solar_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_quote_id` (`quote_id`);

--
-- Indexes for table `solar_project_items`
--
ALTER TABLE `solar_project_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `inventory_item_id` (`inventory_item_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_item_id` (`inventory_item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `employee_attendance`
--
ALTER TABLE `employee_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `installment_payments`
--
ALTER TABLE `installment_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `installment_plans`
--
ALTER TABLE `installment_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `installment_settings`
--
ALTER TABLE `installment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `installment_transactions`
--
ALTER TABLE `installment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT for table `inventory_serials`
--
ALTER TABLE `inventory_serials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payroll_packages`
--
ALTER TABLE `payroll_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pos_sales`
--
ALTER TABLE `pos_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `quote_customer_info`
--
ALTER TABLE `quote_customer_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `quote_items`
--
ALTER TABLE `quote_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `quote_solar_details`
--
ALTER TABLE `quote_solar_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `solar_projects`
--
ALTER TABLE `solar_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `solar_project_items`
--
ALTER TABLE `solar_project_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_attendance`
--
ALTER TABLE `employee_attendance`
  ADD CONSTRAINT `employee_attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  ADD CONSTRAINT `employee_leaves_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_leaves_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory_serials`
--
ALTER TABLE `inventory_serials`
  ADD CONSTRAINT `fk_inventory_serials_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_serials_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventory_serials_project` FOREIGN KEY (`project_id`) REFERENCES `solar_projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_serials_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_serials_sale` FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD CONSTRAINT `payroll_deductions_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_packages`
--
ALTER TABLE `payroll_packages`
  ADD CONSTRAINT `payroll_packages_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `fk_quotations_project_id` FOREIGN KEY (`project_id`) REFERENCES `solar_projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quote_customer_info`
--
ALTER TABLE `quote_customer_info`
  ADD CONSTRAINT `quote_customer_info_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quote_items`
--
ALTER TABLE `quote_items`
  ADD CONSTRAINT `quote_items_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quote_items_ibfk_2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quote_solar_details`
--
ALTER TABLE `quote_solar_details`
  ADD CONSTRAINT `quote_solar_details_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `solar_projects`
--
ALTER TABLE `solar_projects`
  ADD CONSTRAINT `fk_solar_projects_quote_id` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `solar_projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `solar_project_items`
--
ALTER TABLE `solar_project_items`
  ADD CONSTRAINT `solar_project_items_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `solar_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solar_project_items_ibfk_2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
