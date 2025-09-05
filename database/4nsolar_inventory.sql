-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2025 at 04:48 AM
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
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_joining` date NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `package_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_status` enum('active','inactive','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `first_name`, `last_name`, `email`, `phone`, `address`, `date_of_joining`, `basic_salary`, `package_salary`, `allowances`, `position`, `department`, `employment_status`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'EMP001', 'Juan', 'Dela Cruz', 'juan@4nsolar.com', '+63-999-123-4567', NULL, '2024-01-15', 15000.00, 18000.00, 2000.00, 'Solar Installer', 'Operations', 'terminated', '2025-09-03 03:54:53', '2025-09-03 04:20:25', NULL),
(2, 'EMP002', 'Maria', 'Santos', 'maria@4nsolar.com', '+63-999-234-5678', NULL, '2024-02-01', 25000.00, 30000.00, 3000.00, 'Project Manager', 'Projects', 'terminated', '2025-09-03 03:54:53', '2025-09-03 04:20:28', NULL),
(3, 'EMP003', 'Pedro', 'Garcia', 'pedro@4nsolar.com', '+63-999-345-6789', NULL, '2024-03-10', 20000.00, 24000.00, 2500.00, 'Electrician', 'Operations', 'terminated', '2025-09-03 03:54:53', '2025-09-03 04:20:30', NULL),
(4, 'EMP004', 'Ana', 'Rodriguez', 'ana@4nsolar.com', '+63-999-456-7890', NULL, '2024-01-20', 22000.00, 26000.00, 2200.00, 'Sales Representative', 'Sales', 'terminated', '2025-09-03 03:54:53', '2025-09-03 04:20:32', NULL),
(5, '20250009', 'Mohammad Rasheed', 'Heding', 'rasheed121099@gmail.com', '09063272815', 'Brgy Sinunuc Zamboanga City', '2025-08-21', 400.00, 0.00, 0.00, 'IT Specialist', 'Administration', 'active', '2025-09-03 04:21:55', '2025-09-03 04:21:55', 1),
(6, '2025-001', 'Liddy Lou', 'Orsuga', 'liddylouorsuga506@gmail.com', '09263925674', 'Southcom Village, Zamboanga City', '2025-08-12', 400.00, 0.00, 0.00, 'HR Admin', 'HR', 'active', '2025-09-03 05:34:53', '2025-09-03 06:07:34', 9),
(7, '2025-004', 'Oliver ', 'Arapan', '', '09367912127', '\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\nTalon-talon,Zamboanga City', '2025-06-15', 400.00, 3500.00, 0.00, 'PV Solar Installer', 'Operations', 'active', '2025-09-04 01:31:40', '2025-09-04 01:31:40', 1),
(8, '2025-008', 'Ma.Donna', 'Pingoy', 'dp507747@gmail.com', '09535839943', 'San Roque,Zamboanga City', '2025-08-18', 250.00, 0.00, 0.00, 'Sales Consultant', 'Sales', 'active', '2025-09-04 01:34:20', '2025-09-04 01:34:20', 1),
(15, '2025-005', 'Ar-Jay', 'Ventura', 'altopelec@gmail.com', '09708580154', 'Tumaga,Zamboanga City', '2025-08-06', 400.00, 3500.00, 0.00, 'PV Solar Installer', 'Operations', 'active', '2025-09-04 01:44:14', '2025-09-04 01:45:22', 1),
(16, '2025-002', 'Ma.Donna', 'Jaime', 'DonnaClaudeline@facebook.com', '09530452875', 'Luyahan,Zamboanga City', '2025-08-12', 250.00, 0.00, 0.00, 'Sales Consultant', 'Sales', 'active', '2025-09-04 01:47:37', '2025-09-04 01:47:37', 1),
(17, '2025-006', 'Rembranth', 'Dollete', 'rem@gmail.com', '09976689251', 'San Roque, Zamboanga City', '2025-08-11', 300.00, 3500.00, 0.00, 'Pv Solar Installer', 'Operations', 'active', '2025-09-05 01:15:12', '2025-09-05 01:15:32', 9),
(18, '2025-007', 'Aris', 'Ho', 'aris@gmail.com', '09556426035', 'Mampang, Zamboanga City', '2025-08-11', 400.00, 3500.00, 0.00, 'Pv Solar Installer', 'Operations', 'active', '2025-09-05 01:19:03', '2025-09-05 01:19:03', 9),
(19, '2025-003', 'Prudencio', 'Garcia', 'prudencio@gmail.com', '09356270602', 'Baliwasan, Zamboanga City', '2024-01-01', 400.00, 3500.00, 0.00, 'Pv Solar Installer', 'Operations', 'active', '2025-09-05 01:24:49', '2025-09-05 01:24:49', 9);

-- --------------------------------------------------------

--
-- Table structure for table `employee_attendance`
--

CREATE TABLE `employee_attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `working_days_attended` int(11) NOT NULL,
  `leaves_taken` int(11) DEFAULT 0,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `late_instances` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_attendance`
--

INSERT INTO `employee_attendance` (`id`, `employee_id`, `period_id`, `working_days_attended`, `leaves_taken`, `overtime_hours`, `late_instances`, `notes`, `created_at`, `updated_at`) VALUES
(1, 6, 4, 15, 0, 0.00, 0, '', '2025-09-05 01:27:25', '2025-09-05 01:27:25');

-- --------------------------------------------------------

--
-- Table structure for table `employee_leave_balances`
--

CREATE TABLE `employee_leave_balances` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('annual','sick','emergency','maternity','paternity') NOT NULL,
  `total_allocated` int(11) NOT NULL,
  `used_leaves` int(11) DEFAULT 0,
  `balance_leaves` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_leave_balances`
--

INSERT INTO `employee_leave_balances` (`id`, `employee_id`, `leave_type`, `total_allocated`, `used_leaves`, `balance_leaves`, `year`, `created_at`, `updated_at`) VALUES
(1, 1, 'annual', 15, 0, 15, '2025', '2025-09-03 03:54:53', '2025-09-03 03:54:53'),
(2, 2, 'annual', 15, 0, 15, '2025', '2025-09-03 03:54:53', '2025-09-03 03:54:53'),
(3, 3, 'annual', 15, 0, 15, '2025', '2025-09-03 03:54:53', '2025-09-03 03:54:53'),
(4, 4, 'annual', 15, 0, 15, '2025', '2025-09-03 03:54:53', '2025-09-03 03:54:53'),
(8, 1, 'sick', 10, 0, 10, '2025', '2025-09-03 03:54:53', '2025-09-03 03:54:53'),
(9, 2, 'sick', 10, 0, 10, '2025', '2025-09-03 03:54:53', '2025-09-03 03:54:53'),
(10, 3, 'sick', 10, 0, 10, '2025', '2025-09-03 03:54:53', '2025-09-03 03:54:53'),
(11, 4, 'sick', 10, 0, 10, '2025', '2025-09-03 03:54:53', '2025-09-03 03:54:53'),
(15, 5, 'annual', 15, 0, 15, '2025', '2025-09-03 04:21:55', '2025-09-03 04:21:55'),
(16, 5, 'sick', 10, 0, 10, '2025', '2025-09-03 04:21:55', '2025-09-03 04:21:55'),
(17, 5, 'emergency', 5, 0, 5, '2025', '2025-09-03 04:21:55', '2025-09-03 04:21:55'),
(18, 6, 'annual', 15, 0, 15, '2025', '2025-09-03 05:34:53', '2025-09-03 05:34:53'),
(19, 6, 'sick', 10, 0, 10, '2025', '2025-09-03 05:34:53', '2025-09-03 05:34:53'),
(20, 6, 'emergency', 5, 0, 5, '2025', '2025-09-03 05:34:53', '2025-09-03 05:34:53'),
(21, 7, 'annual', 15, 0, 15, '2025', '2025-09-04 01:31:40', '2025-09-04 01:31:40'),
(22, 7, 'sick', 10, 0, 10, '2025', '2025-09-04 01:31:40', '2025-09-04 01:31:40'),
(23, 7, 'emergency', 5, 0, 5, '2025', '2025-09-04 01:31:40', '2025-09-04 01:31:40'),
(24, 8, 'annual', 15, 0, 15, '2025', '2025-09-04 01:34:20', '2025-09-04 01:34:20'),
(25, 8, 'sick', 10, 0, 10, '2025', '2025-09-04 01:34:20', '2025-09-04 01:34:20'),
(26, 8, 'emergency', 5, 0, 5, '2025', '2025-09-04 01:34:20', '2025-09-04 01:34:20'),
(27, 15, 'annual', 15, 0, 15, '2025', '2025-09-04 01:44:14', '2025-09-04 01:44:14'),
(28, 15, 'sick', 10, 0, 10, '2025', '2025-09-04 01:44:14', '2025-09-04 01:44:14'),
(29, 15, 'emergency', 5, 0, 5, '2025', '2025-09-04 01:44:14', '2025-09-04 01:44:14'),
(30, 16, 'annual', 15, 0, 15, '2025', '2025-09-04 01:47:37', '2025-09-04 01:47:37'),
(31, 16, 'sick', 10, 0, 10, '2025', '2025-09-04 01:47:37', '2025-09-04 01:47:37'),
(32, 16, 'emergency', 5, 0, 5, '2025', '2025-09-04 01:47:37', '2025-09-04 01:47:37'),
(33, 17, 'annual', 15, 0, 15, '2025', '2025-09-05 01:15:12', '2025-09-05 01:15:12'),
(34, 17, 'sick', 10, 0, 10, '2025', '2025-09-05 01:15:12', '2025-09-05 01:15:12'),
(35, 17, 'emergency', 5, 0, 5, '2025', '2025-09-05 01:15:12', '2025-09-05 01:15:12'),
(36, 18, 'annual', 15, 0, 15, '2025', '2025-09-05 01:19:03', '2025-09-05 01:19:03'),
(37, 18, 'sick', 10, 0, 10, '2025', '2025-09-05 01:19:03', '2025-09-05 01:19:03'),
(38, 18, 'emergency', 5, 0, 5, '2025', '2025-09-05 01:19:03', '2025-09-05 01:19:03'),
(39, 19, 'annual', 15, 0, 15, '2025', '2025-09-05 01:24:49', '2025-09-05 01:24:49'),
(40, 19, 'sick', 10, 0, 10, '2025', '2025-09-05 01:24:49', '2025-09-05 01:24:49'),
(41, 19, 'emergency', 5, 0, 5, '2025', '2025-09-05 01:24:49', '2025-09-05 01:24:49');

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
  `is_active` tinyint(1) DEFAULT 1
) ;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `brand`, `model`, `category_id`, `size_specification`, `base_price`, `selling_price`, `discount_percentage`, `supplier_id`, `stock_quantity`, `minimum_stock`, `description`, `image_path`, `specifications`, `created_at`, `updated_at`, `created_by`, `is_active`) VALUES
(22, 'Canadian Solar', 'MONO 375w', 1, '375w', 3900.00, 3900.00, 0.00, 6, 0, 0, '', 'images/products/product_68b5592fcb891.jpg', NULL, '2025-09-01 08:28:31', '2025-09-02 07:13:12', 1, 1),
(23, 'Canadian Solar', 'MONO 410w (CS6-410MS)', 1, '410w', 4000.00, 4000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55986364b4.jpg', NULL, '2025-09-01 08:29:58', '2025-09-01 08:29:58', 1, 1),
(24, 'Canadian Solar', 'MONO 455w (CS6L-455MS)', 1, '455w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b559e773356.jpg', NULL, '2025-09-01 08:31:23', '2025-09-01 08:31:35', 1, 1),
(25, 'Canadian Solar', 'MONO 545w (CS6W-545MS)', 1, '545w', 4200.00, 4200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55b6116f30.jpg', NULL, '2025-09-01 08:37:53', '2025-09-01 08:40:08', 1, 1),
(26, 'Canadian Solar', 'MONO 550w (CS6W-550MS)', 1, '550w', 4400.00, 4400.00, 0.00, 6, 33, 0, '', 'images/products/product_68b55bb655a9b.jpg', NULL, '2025-09-01 08:39:18', '2025-09-03 04:58:40', 1, 1),
(27, 'Canadian Solar', 'MONO 555w (CS6L-555MS)', 1, '555w', 4400.00, 4400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55c8c4d739.jpg', NULL, '2025-09-01 08:41:35', '2025-09-01 08:42:52', 1, 1),
(28, 'Canadian Solar', 'MONO 580w (CS6W-580TB-AG)', 1, '580w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55d1277ecb.jpg', NULL, '2025-09-01 08:45:06', '2025-09-01 08:45:06', 1, 1),
(29, 'Canadian Solar', 'MONO 585w (CS6W-585TB-AG)', 1, '585w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55d64d1b99.jpg', NULL, '2025-09-01 08:46:28', '2025-09-01 08:46:28', 1, 1),
(30, 'Canadian Solar', 'MONO 600w (CS6.1-72TB600)', 1, '600w', 4200.00, 4200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55dfe7d0ad.jpg', NULL, '2025-09-01 08:49:02', '2025-09-01 08:49:02', 1, 1),
(31, 'Canadian Solar', 'MONO 605w (CS6.1-72TB605)', 1, '605w', 4300.00, 4300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6679bb2663.jpg', NULL, '2025-09-02 03:42:02', '2025-09-02 03:42:19', 1, 1),
(32, 'Canadian Solar', 'MONO 610w (CS6.1-72TB-610)', 1, '610w', 4400.00, 4400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b667e54b564.jpg', NULL, '2025-09-02 03:43:33', '2025-09-02 03:43:33', 1, 1),
(33, 'Canadian Solar', 'MONO 615w (CS6.2-66TB-615)', 1, '615w', 4500.00, 4500.00, 0.00, 6, 2, 0, '', 'images/products/product_68b6684e50bec.jpg', NULL, '2025-09-02 03:45:18', '2025-09-03 04:58:54', 1, 1),
(34, 'Canadian Solar', 'MONO 620w (CS6.2-66TB-620)', 1, '620w', 4600.00, 4600.00, 0.00, 6, 0, 0, '', 'images/products/product_68b668d4a4eea.jpg', NULL, '2025-09-02 03:47:32', '2025-09-02 03:47:32', 1, 1),
(35, 'Canadian Solar', 'MONO 650w (CS7N-650wMB-AG)', 1, '650w', 4500.00, 4500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68562be9f1.jpg', NULL, '2025-09-02 05:49:22', '2025-09-02 05:49:22', 1, 1),
(36, 'Canadian Solar', 'MONO 700w (CS7N-700TB-AG)', 1, '700w', 4800.00, 4800.00, 0.00, 6, 0, 0, '', 'images/products/product_68b685c801086.jpg', NULL, '2025-09-02 05:51:04', '2025-09-02 05:51:04', 1, 1),
(37, 'OSDA', 'MONO 455w (ODA455-30V-MH)', 1, '445w', 3200.00, 3200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68690a379c.jpg', NULL, '2025-09-02 05:54:24', '2025-09-02 05:54:24', 1, 1),
(38, 'OSDA', 'MONO 500w (ODA500-33V-MH)', 1, '500w', 3500.00, 3500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b686c48d8f0.jpg', NULL, '2025-09-02 05:55:16', '2025-09-02 05:55:16', 1, 1),
(39, 'OSDA', 'MONO 550w (ODA550-36V-MHD)', 1, '550w', 3650.00, 4300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68707dbc50.jpg', NULL, '2025-09-02 05:56:23', '2025-09-04 03:24:04', 1, 1),
(40, 'OSDA', 'MONO 580w (ODA580-36VMHD)', 1, '580w', 3700.00, 3700.00, 0.00, 6, 0, 0, '', 'images/products/product_68b687510d884.jpg', NULL, '2025-09-02 05:57:25', '2025-09-02 05:57:37', 1, 1),
(41, 'OSDA', 'MONO 590w (ODA590-36VMHD)', 1, '590w', 3800.00, 3800.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6878bd32d4.jpg', NULL, '2025-09-02 05:58:35', '2025-09-02 05:58:35', 1, 1),
(42, 'OSDA', 'MONO 610w (ODA610-33VMHDRz)', 1, '610w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b687d040151.jpg', NULL, '2025-09-02 05:59:44', '2025-09-02 05:59:44', 1, 1),
(43, 'OSDA', 'MONO 620w (ODA620-33VMHDRz)', 1, '620w', 4200.00, 4200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b688161eb98.jpg', NULL, '2025-09-02 06:00:54', '2025-09-02 06:00:54', 1, 1),
(44, 'OSDA', 'MONO 700w (ODA700-33V-MHD)', 1, '700w', 4700.00, 4700.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68841e8842.jpg', NULL, '2025-09-02 06:01:29', '2025-09-02 06:01:37', 1, 1),
(45, 'SUNRI', '20w', 1, '20w', 900.00, 900.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68970884c9.jpg', NULL, '2025-09-02 06:06:31', '2025-09-02 06:06:40', 1, 1),
(46, 'SUNRI', '100w', 1, '100w', 1700.00, 1700.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6899db8796.jpg', NULL, '2025-09-02 06:07:25', '2025-09-02 06:07:25', 1, 1),
(47, 'SUNRI', '150w', 1, '150w', 2400.00, 2400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b689c21416a.jpg', NULL, '2025-09-02 06:08:02', '2025-09-02 06:08:02', 1, 1),
(48, 'SUNRI', '200w', 1, '200w', 2700.00, 2700.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68a7259c1d.jpg', NULL, '2025-09-02 06:08:51', '2025-09-02 06:10:58', 1, 1),
(49, 'SUNRI', '340w', 1, '340w', 3300.00, 3300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68a9c346b1.jpg', NULL, '2025-09-02 06:11:40', '2025-09-02 06:11:40', 1, 1),
(50, 'SUNRI', '350w', 1, '350w', 3400.00, 3400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ac7bad32.jpg', NULL, '2025-09-02 06:12:23', '2025-09-02 06:12:23', 1, 1),
(51, 'DEYE', '3.6kw (SUN-3.6k-SG04LP1-EU)', 9, '3.6kw', 39610.00, 39610.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68e759001e.jpg', NULL, '2025-09-02 06:28:05', '2025-09-03 02:15:35', 1, 1),
(52, 'DEYE', '5kw (SUN-5k-SG04LP1-EU-P)', 9, '5kw', 45268.00, 45268.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ebb2c6a1.jpg', NULL, '2025-09-02 06:29:15', '2025-09-03 02:19:21', 1, 1),
(53, 'DEYE', '5kw (SUN-5k-SG04LP1-EU-SM2)', 9, '5kw', 41732.00, 41732.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ef614a2c.jpg', NULL, '2025-09-02 06:30:14', '2025-09-03 02:19:34', 1, 1),
(54, 'DEYE', '6kw (SUN-6k-SG04LP1-EU)', 9, '6kw', 42300.00, 42300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68f2e65d10.jpg', NULL, '2025-09-02 06:31:10', '2025-09-03 02:19:50', 1, 1),
(55, 'DEYE', '8kw (SUN-8k-SG04LP1-EU)', 9, '8kw', 42300.00, 42300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68f768dd36.jpg', NULL, '2025-09-02 06:32:22', '2025-09-03 02:20:04', 1, 1),
(56, 'DEYE', '8kw (SUN-8k-SG05LP1-SM2)', 9, '8kw', 58200.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68fae52409.jpg', NULL, '2025-09-02 06:33:18', '2025-09-04 03:21:51', 1, 1),
(57, 'DEYE', '10kw (SUN-10k-SG04LP1-EU-AM3)', 9, '10kw', 58200.00, 58200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ff3f3218.jpg', NULL, '2025-09-02 06:34:27', '2025-09-03 02:14:25', 1, 1),
(58, 'DEYE', '12kw (SUN-12k-SG04LP1-EU-AM3)', 9, '12kw', 84500.00, 84500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6903e0c30b.jpg', NULL, '2025-09-02 06:35:26', '2025-09-03 02:14:38', 1, 1),
(59, 'DEYE', '16kw (SUN-16k-SG04LP1-EU)', 9, '16kw', 109000.00, 115000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69074839d7.jpg', NULL, '2025-09-02 06:36:36', '2025-09-04 09:49:22', 1, 1),
(60, 'DEYE', '12kw (SUN-12K-SG04LP3-EU)', 9, '12kw', 94500.00, 94500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b691d5d5f8b.jpg', NULL, '2025-09-02 06:42:29', '2025-09-02 06:42:29', 1, 1),
(61, 'DEYE', '20kw (SUN-20K-SG01HP3-EU-AM2)', 9, '20kw', 149000.00, 149000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69241e2389.jpg', NULL, '2025-09-02 06:44:17', '2025-09-02 06:44:17', 1, 1),
(62, 'DEYE', '30kw (SUN-30K-SG01HP3-EU-AM2)', 9, '30kw', 229000.00, 229000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6927262bbc.jpg', NULL, '2025-09-02 06:45:06', '2025-09-02 06:45:06', 1, 1),
(63, 'DEYE', '50kw (SUN-50K-SG01HP3-EU-BM4)', 9, '50kw', 279000.00, 279000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b692aa8143f.jpg', NULL, '2025-09-02 06:46:02', '2025-09-02 06:46:02', 1, 1),
(64, 'Eastron', 'Eastron Smart Meter (w/ 3CT)', 6, '(w/ 3CT)', 11000.00, 11000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b693af922dc.jpg', NULL, '2025-09-02 06:50:23', '2025-09-02 06:50:23', 1, 1),
(65, 'SRNE', '6kw (HESP48DS100-H)', 9, '6kw', 35000.00, 35000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69a460f8ce.jpg', NULL, '2025-09-02 07:18:30', '2025-09-02 07:18:30', 1, 1),
(66, 'SRNE', '12kw (HESP4812DS200-H)', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69a84474f4.jpg', NULL, '2025-09-02 07:19:32', '2025-09-02 07:19:32', 1, 1),
(67, 'SRNE', '12kw (HESP1203SH3) HV', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69eb22f264.jpg', NULL, '2025-09-02 07:37:22', '2025-09-02 07:37:22', 1, 1),
(68, 'SRNE', '12kw (HESP4812DSH3) LV', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69ef86b1d6.jpg', NULL, '2025-09-02 07:38:32', '2025-09-02 07:38:32', 1, 1),
(71, 'FEE0 ', 'ATS 63A', 7, '63A', 2000.00, 2500.00, 0.00, 6, 3, 1, '', 'images/products/product_68b7c6b27bd0a.png', NULL, '2025-09-03 04:40:18', '2025-09-04 09:00:52', 9, 1),
(72, 'OSDA', 'ODA590-36VMHD', 1, '590w', 3000.00, 3800.00, 0.00, 6, 14, 10, '', 'images/products/product_68b7cc01c94dc.jpg', NULL, '2025-09-03 05:02:57', '2025-09-03 05:02:57', 9, 1),
(73, 'LABOR', 'Labor Fee', 1, 'Per KW', 0.00, 0.00, 0.00, NULL, 9999, 0, 'Labor fee calculation item', NULL, NULL, '2025-09-04 00:56:42', '2025-09-04 00:56:42', NULL, 1),
(74, 'FEE0 ', 'Breaker DC 16A', 7, '16A', 250.00, 450.00, 0.00, 5, 4, 5, 'DC', NULL, NULL, '2025-09-04 01:11:42', '2025-09-04 04:03:46', 1, 1),
(75, 'Terminal Lugs', 'SC35-8', 7, '35-8', 40.00, 55.00, 0.00, 5, 44, 10, 'No brand', NULL, NULL, '2025-09-04 01:25:40', '2025-09-04 05:41:43', 9, 1),
(76, 'FEE0 ', 'Breaker  63 DC 40A ', 7, '40A', 400.00, 650.00, 0.00, 5, 4, 10, '', NULL, NULL, '2025-09-04 01:29:27', '2025-09-04 03:38:05', 9, 1),
(77, 'FEE0 ', ' Breaker DC 20A ', 7, '20A', 400.00, 650.00, 0.00, 5, 7, 10, '', NULL, NULL, '2025-09-04 01:30:58', '2025-09-04 03:31:37', 9, 1),
(79, 'FEE0 ', 'Breaker DC 125A', 7, '125A', 950.00, 1500.00, 0.00, 5, 5, 10, '', NULL, NULL, '2025-09-04 01:34:44', '2025-09-04 03:32:52', 9, 1),
(80, 'FEE0 ', 'Breaker AC 32A', 7, 'AC 32A', 200.00, 650.00, 0.00, 5, 4, 10, '', NULL, NULL, '2025-09-04 01:36:48', '2025-09-04 03:37:33', 9, 1),
(81, 'FEE0 ', 'Breaker AC C63', 7, 'C63', 200.00, 650.00, 0.00, 5, 6, 10, '', NULL, NULL, '2025-09-04 01:39:28', '2025-09-04 03:36:49', 9, 1),
(82, 'FEE0 ', 'BREAKER AC 16A', 7, 'C16', 200.00, 650.00, 0.00, 5, 4, 10, '', NULL, NULL, '2025-09-04 01:40:31', '2025-09-04 04:02:39', 9, 1),
(83, 'FEE0 ', 'Breaker DC 80A', 7, '80A', 950.00, 1500.00, 0.00, 5, 1, 5, '', NULL, NULL, '2025-09-04 01:42:13', '2025-09-04 03:38:30', 9, 1),
(85, 'Ingelec', '8ways', 7, '8ways', 280.00, 750.00, 0.00, 5, 1, 2, '', NULL, NULL, '2025-09-04 01:51:24', '2025-09-04 01:51:24', 9, 1),
(86, 'Ingelec', '9ways', 7, '9ways', 280.00, 750.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 01:52:17', '2025-09-04 01:52:17', 9, 1),
(87, 'Ingelec', 'Breaker box 12ways', 7, '12ways', 350.00, 850.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 01:55:01', '2025-09-04 04:05:20', 9, 1),
(88, 'Ingelec', '13ways', 7, '13ways', 350.00, 750.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 01:59:12', '2025-09-04 01:59:12', 9, 1),
(89, 'Ingelec', 'Breaker box 16ways', 7, '16ways', 450.00, 1000.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 02:05:53', '2025-09-04 04:06:00', 9, 1),
(90, 'Ingelec', '18ways', 7, '18ways', 550.00, 1200.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 02:08:28', '2025-09-04 02:08:28', 9, 1),
(91, 'SASSIN', '4ways', 7, '4ways', 450.00, 1000.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 02:20:42', '2025-09-04 02:20:42', 9, 1),
(92, 'SASSIN', '8ways', 7, '8ways', 550.00, 1100.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 02:27:08', '2025-09-04 02:27:08', 9, 1),
(93, 'SASSIN', '12ways', 7, '12ways', 660.00, 1300.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 02:31:27', '2025-09-04 02:31:27', 9, 1),
(94, 'SASSIN', '18ways', 7, '18ways', 950.00, 1500.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 02:34:53', '2025-09-04 02:34:53', 9, 1),
(95, 'Ingelec', '18ways', 7, '18ways', 550.00, 1100.00, 0.00, 5, 0, 1, '', NULL, NULL, '2025-09-04 02:35:37', '2025-09-04 02:35:37', 9, 1),
(96, 'Alltopelec', '51.2V 100Ah 5kwh', 3, '100Ah 5Kw', 33000.00, 42000.00, 5.00, 6, 0, 5, '', NULL, NULL, '2025-09-04 02:40:27', '2025-09-04 02:40:27', 9, 1),
(97, 'Alltopelec', '51.2V 200Ah 5kwh', 3, '200Ah 10kwh', 68000.00, 79000.00, 5.00, 6, 0, 5, '', NULL, NULL, '2025-09-04 02:41:50', '2025-09-04 02:41:50', 9, 1),
(98, 'Alltopelec', '51.2V 314Ah 15kwh', 3, '300Ah 15kwh', 80000.00, 95000.00, 5.00, 5, 0, 5, '', NULL, NULL, '2025-09-04 02:44:53', '2025-09-04 02:44:53', 9, 1),
(99, 'Alltopelec', '51.2V 400Ah 200kwh', 3, '400Ah 200kwh', 115000.00, 135000.00, 5.00, 5, 0, 5, '', NULL, NULL, '2025-09-04 02:47:21', '2025-09-04 02:47:21', 9, 1),
(100, 'Arrow', '50x50', 5, '50x50', 300.00, 750.00, 0.00, 5, 3, 5, '', NULL, NULL, '2025-09-04 02:55:19', '2025-09-04 04:00:56', 9, 1),
(101, 'Railings', 'Railings', 8, '2.4', 570.00, 690.00, 0.00, 5, 32, 10, '', NULL, NULL, '2025-09-04 02:57:49', '2025-09-04 02:58:14', 9, 1),
(102, 'Arrow', '80x80', 5, '80x80', 600.00, 1200.00, 0.00, 5, 4, 5, '', NULL, NULL, '2025-09-04 03:03:49', '2025-09-04 03:03:49', 9, 1),
(103, 'FEE0 ', 'DC SPD Breaker', 7, 'DC SPD', 830.00, 1500.00, 0.00, 5, 6, 6, '', NULL, NULL, '2025-09-04 03:06:52', '2025-09-04 09:03:29', 9, 1),
(104, 'FEE0 ', 'MCCB 200A', 7, '200A', 2500.00, 2800.00, 0.00, 5, 1, 2, '', NULL, NULL, '2025-09-04 03:09:08', '2025-09-04 04:04:18', 9, 1),
(105, 'L Foot', 'L Foot', 8, 'L Foot', 80.00, 150.00, 0.00, 5, 20, 10, '', NULL, NULL, '2025-09-04 03:20:21', '2025-09-04 03:20:21', 9, 1),
(107, 'LV Topsun', '300Ah', 3, '300Ah 15kwh', 100000.00, 110000.00, 0.00, 5, 0, 10, '', NULL, NULL, '2025-09-04 03:26:35', '2025-09-04 03:26:35', 9, 1),
(108, 'FEE0 ', 'AC SPD Breaker', 7, 'AC SPD', 1400.00, 3200.00, 0.00, 5, 0, 5, '', NULL, NULL, '2025-09-04 03:44:30', '2025-09-04 09:02:27', 9, 1),
(110, 'FEE0 ', 'ATS 2P 125A', 7, 'ATS 125A', 1400.00, 2800.00, 0.00, 5, 3, 3, '', NULL, NULL, '2025-09-04 03:59:00', '2025-09-04 09:55:45', 9, 1),
(112, 'Mid clamp', 'Mid Clamp', 8, 'Mid Clamp', 40.00, 65.00, 0.00, 5, 6, 10, '', NULL, NULL, '2025-09-04 04:08:18', '2025-09-04 04:08:18', 9, 1),
(113, 'End Clamp', 'End Clamp', 8, 'End clamp', 40.00, 65.00, 0.00, 5, 12, 10, '', NULL, NULL, '2025-09-04 04:09:05', '2025-09-04 04:09:05', 9, 1),
(114, 'Leader', 'Pv Cable 6.0mm', 5, '6.0', 100.00, 140.00, 0.00, 5, 88, 10, '', NULL, NULL, '2025-09-04 05:39:36', '2025-09-04 05:41:43', 9, 1),
(115, 'FEE0', 'Battery Cable Red', 5, '35mm', 100.00, 450.00, 0.00, 5, 0, 100, '', NULL, NULL, '2025-09-04 08:56:22', '2025-09-04 08:56:22', 9, 1),
(116, 'FEE0', 'Battery Cable Black', 5, '35mm', 100.00, 450.00, 0.00, 5, 0, 100, '', NULL, NULL, '2025-09-04 08:57:04', '2025-09-04 08:57:04', 9, 1),
(117, 'HDPE PIPE', 'AD34.5', 5, '5mm', 0.10, 0.00, 0.00, 5, 0, 100, '', NULL, NULL, '2025-09-04 09:06:09', '2025-09-04 09:06:09', 9, 1),
(118, 'KOTEN BREAKER', 'AC MCCB BREAKER', 7, '200A ', 2800.00, 4500.00, 0.00, 9, 1, 10, '', NULL, NULL, '2025-09-04 09:57:37', '2025-09-04 11:02:37', 1, 1),
(119, 'PHILFLEX THHN', 'AC WIRE #2', 5, '#2 38MM', 250.00, 450.00, 0.00, 9, 50, 10, 'AC WIRES', NULL, NULL, '2025-09-04 11:05:11', '2025-09-04 11:05:55', 1, 1),
(120, 'METAL ENCLOSURE ', '40X50X20', 7, '400mm x 500 x 200', 4100.00, 5100.00, 0.00, 5, 1, 10, '', NULL, NULL, '2025-09-04 11:10:20', '2025-09-05 02:10:31', 1, 1),
(121, 'DIN RAIL', 'ALLUMINUM RAIL', 4, '1 meter', 80.00, 480.00, 0.00, 9, 10, 10, '', NULL, NULL, '2025-09-04 11:12:32', '2025-09-04 11:12:32', 1, 1),
(122, 'METAL ENCLOSURE', '6X15X18(200X400X500)', 7, '6X15X18(200X400X500)', 2650.00, 4500.00, 0.00, 9, 1, 10, '', NULL, NULL, '2025-09-05 02:09:21', '2025-09-05 02:11:06', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `id` int(11) NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_working_days` int(11) NOT NULL,
  `status` enum('draft','finalized','paid') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_periods`
--

INSERT INTO `payroll_periods` (`id`, `period_name`, `start_date`, `end_date`, `total_working_days`, `status`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'September 2025', '2025-09-01', '2025-09-30', 22, 'draft', '2025-09-03 03:54:53', '2025-09-03 03:54:53', NULL),
(2, 'September 1 - 6', '2025-09-01', '2025-09-06', 6, 'draft', '2025-09-03 03:56:45', '2025-09-03 03:56:45', 1),
(3, 'September 9 - 13', '2025-09-08', '2025-09-13', 6, 'draft', '2025-09-03 04:22:29', '2025-09-03 04:22:29', 1),
(4, 'SALARY 15', '2025-09-01', '2025-09-15', 15, 'draft', '2025-09-03 05:35:42', '2025-09-03 05:35:42', 9),
(5, 'SALARY 15', '2025-09-01', '2025-09-15', 13, 'draft', '2025-09-05 01:28:40', '2025-09-05 01:28:40', 9);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `basic_salary_amount` decimal(10,2) NOT NULL,
  `project_salary_base` decimal(10,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `bonus_pay` decimal(10,2) DEFAULT 0.00,
  `allowances_amount` decimal(10,2) DEFAULT 0.00,
  `total_income` decimal(10,2) NOT NULL,
  `cash_advance` decimal(10,2) DEFAULT 0.00,
  `uniforms` decimal(10,2) DEFAULT 0.00,
  `tools` decimal(10,2) DEFAULT 0.00,
  `motor_loan` decimal(10,2) DEFAULT 0.00 COMMENT 'Motor loan deduction amount',
  `cellphone_loan` decimal(10,2) DEFAULT 0.00 COMMENT 'Cellphone loan deduction amount',
  `lates_deduction` decimal(10,2) DEFAULT 0.00,
  `misc_deductions` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_rate` decimal(8,2) DEFAULT 62.50,
  `working_days_attended` int(11) NOT NULL,
  `status` enum('draft','approved','paid') DEFAULT 'draft',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`id`, `employee_id`, `period_id`, `basic_salary_amount`, `project_salary_base`, `overtime_pay`, `bonus_pay`, `allowances_amount`, `total_income`, `cash_advance`, `uniforms`, `tools`, `motor_loan`, `cellphone_loan`, `lates_deduction`, `misc_deductions`, `total_deductions`, `net_salary`, `overtime_hours`, `overtime_rate`, `working_days_attended`, `status`, `payment_date`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 1, 1, 15000.00, 18000.00, 0.00, 0.00, 2000.00, 35000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 35000.00, 0.00, 62.50, 22, 'draft', NULL, '2025-09-03 03:55:02', '2025-09-03 03:55:02', 1),
(2, 2, 1, 25000.00, 30000.00, 0.00, 0.00, 3000.00, 58000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 58000.00, 0.00, 62.50, 22, 'draft', NULL, '2025-09-03 03:55:02', '2025-09-03 03:55:02', 1),
(3, 3, 1, 20000.00, 24000.00, 0.00, 0.00, 2500.00, 46500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 46500.00, 0.00, 62.50, 22, 'draft', NULL, '2025-09-03 03:55:02', '2025-09-03 03:55:02', 1),
(4, 4, 1, 22000.00, 26000.00, 0.00, 0.00, 2200.00, 50200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 50200.00, 0.00, 62.50, 22, 'draft', NULL, '2025-09-03 03:55:02', '2025-09-03 03:55:02', 1),
(5, 1, 2, 15000.00, 18000.00, 0.00, 0.00, 2000.00, 35000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 35000.00, 0.00, 62.50, 6, 'draft', NULL, '2025-09-03 03:56:47', '2025-09-03 03:56:47', 1),
(6, 2, 2, 25000.00, 30000.00, 0.00, 0.00, 3000.00, 58000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 58000.00, 0.00, 62.50, 6, 'draft', NULL, '2025-09-03 03:56:47', '2025-09-03 03:56:47', 1),
(7, 3, 2, 20000.00, 24000.00, 0.00, 0.00, 2500.00, 46500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 46500.00, 0.00, 62.50, 6, 'draft', NULL, '2025-09-03 03:56:47', '2025-09-03 03:56:47', 1),
(8, 4, 2, 22000.00, 26000.00, 0.00, 0.00, 2200.00, 50200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 50200.00, 0.00, 62.50, 6, 'draft', NULL, '2025-09-03 03:56:47', '2025-09-03 03:56:47', 1),
(9, 5, 3, 2400.00, 0.00, 0.00, 0.00, 0.00, 2400.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2400.00, 0.00, 62.50, 6, 'draft', NULL, '2025-09-03 04:22:33', '2025-09-03 05:28:43', 1),
(10, 5, 4, 6000.00, 0.00, 0.00, 0.00, 0.00, 6000.00, 0.00, 500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 5500.00, 0.00, 62.50, 15, 'draft', NULL, '2025-09-03 05:35:47', '2025-09-03 07:27:15', 9),
(11, 6, 4, 6000.00, 0.00, 687.50, 0.00, 0.00, 6687.50, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 6687.50, 11.00, 62.50, 15, 'draft', NULL, '2025-09-03 05:35:47', '2025-09-03 07:56:14', 9),
(12, 5, 5, 5200.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9),
(13, 6, 5, 5200.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9),
(14, 7, 5, 5200.00, 3500.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9),
(15, 8, 5, 5200.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9),
(16, 15, 5, 5200.00, 3500.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9),
(17, 16, 5, 5200.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9),
(18, 17, 5, 5200.00, 3500.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9),
(19, 18, 5, 5200.00, 3500.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9),
(20, 19, 5, 5200.00, 3500.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 8700.00, 0.00, 62.50, 13, 'draft', NULL, '2025-09-05 01:29:02', '2025-09-05 01:29:02', 9);

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
(38, 'RCP20250904-2146', 'Ajiv Talal', '09977593722', 1790.00, 0.00, 1790.00, 'cash', 1790.00, 0.00, 'completed', NULL, 9, '2025-09-04 05:40:45', '2025-09-04 05:41:43');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pos_sale_items`
--

INSERT INTO `pos_sale_items` (`id`, `sale_id`, `inventory_item_id`, `quantity`, `unit_price`, `discount_percentage`, `discount_amount`, `total_amount`, `created_at`) VALUES
(20, 36, 71, 1, 2500.00, 0.00, 0.00, 2500.00, '2025-09-03 04:40:41'),
(21, 37, 71, 1, 2500.00, 0.00, 0.00, 2500.00, '2025-09-04 02:14:24'),
(22, 38, 114, 12, 140.00, 0.00, 0.00, 1680.00, '2025-09-04 05:40:58'),
(23, 38, 75, 2, 55.00, 0.00, 0.00, 110.00, '2025-09-04 05:41:24');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quote_number` varchar(50) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `proposal_name` varchar(255) DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `total_discount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','sent','accepted','rejected','expired') DEFAULT 'draft',
  `valid_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quote_number`, `customer_name`, `customer_phone`, `proposal_name`, `subtotal`, `total_discount`, `total_amount`, `status`, `valid_until`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(9, 'QTE20250904-3549', 'Juan Dela Cruz', '09918195482', '32kw Solar Panel', 0.00, 0.00, 0.00, 'draft', NULL, NULL, 1, '2025-09-04 00:56:42', '2025-09-05 02:38:53'),
(10, 'QTE20250904-0481', 'Gucela', '019872', '8kw Supply and installation', 99800.00, 190.00, 99610.00, 'draft', NULL, NULL, 1, '2025-09-04 00:59:38', '2025-09-04 02:06:12'),
(11, 'QTE20250904-0782', 'Missuara 8kw', '09173031588', 'Supply and Installation of 8kw to pangutaran', 269370.00, 0.00, 269370.00, 'draft', NULL, NULL, 1, '2025-09-04 03:19:46', '2025-09-04 04:04:31'),
(13, 'QTE20250904-7312', 'Thai Alamia', '09173081539', 'Additional 16kw upgrade', 177980.00, 0.00, 177980.00, 'draft', NULL, NULL, 1, '2025-09-04 09:49:31', '2025-09-05 02:40:50');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quote_items`
--

INSERT INTO `quote_items` (`id`, `quote_id`, `inventory_item_id`, `quantity`, `unit_price`, `discount_percentage`, `discount_amount`, `total_amount`, `created_at`) VALUES
(11, 10, 73, 12000, 8.00, 0.00, 0.00, 96000.00, '2025-09-04 00:59:38'),
(12, 10, 41, 1, 3800.00, 5.00, 190.00, 3610.00, '2025-09-04 01:02:15'),
(14, 11, 73, 8000, 8.00, 0.00, 0.00, 64000.00, '2025-09-04 03:19:46'),
(16, 11, 39, 1, 4300.00, 0.00, 0.00, 4300.00, '2025-09-04 03:24:26'),
(17, 11, 107, 1, 110000.00, 0.00, 0.00, 110000.00, '2025-09-04 03:26:55'),
(18, 11, 77, 2, 650.00, 0.00, 0.00, 1300.00, '2025-09-04 03:32:04'),
(19, 11, 56, 1, 69000.00, 0.00, 0.00, 69000.00, '2025-09-04 03:38:53'),
(20, 11, 108, 1, 3200.00, 0.00, 0.00, 3200.00, '2025-09-04 03:50:58'),
(21, 11, 103, 1, 1500.00, 0.00, 0.00, 1500.00, '2025-09-04 03:51:05'),
(22, 11, 81, 3, 650.00, 0.00, 0.00, 1950.00, '2025-09-04 03:54:12'),
(23, 11, 71, 2, 2500.00, 0.00, 0.00, 5000.00, '2025-09-04 03:55:23'),
(24, 11, 102, 3, 1200.00, 0.00, 0.00, 3600.00, '2025-09-04 04:00:01'),
(25, 11, 101, 8, 690.00, 0.00, 0.00, 5520.00, '2025-09-04 04:04:31'),
(27, 13, 73, 4000, 10.00, 0.00, 0.00, 40000.00, '2025-09-04 09:49:31'),
(28, 13, 59, 1, 115000.00, 0.00, 0.00, 115000.00, '2025-09-04 09:49:39'),
(29, 13, 118, 2, 4500.00, 0.00, 0.00, 9000.00, '2025-09-04 09:57:56'),
(30, 13, 119, 20, 450.00, 0.00, 0.00, 9000.00, '2025-09-04 11:07:09'),
(32, 13, 121, 1, 480.00, 0.00, 0.00, 480.00, '2025-09-04 11:12:47'),
(33, 13, 122, 1, 4500.00, 0.00, 0.00, 4500.00, '2025-09-05 02:11:23');

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
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `total_amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(32, 75, 'out', 2, 46, 44, '', 38, 'POS sale - RCP20250904-2146', '2025-09-04 05:41:43', 9);

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
(9, 'CENTURY LIGHT CENTER', 'SOON', 'EMAIL@EMAIL.COM', '(062) 991 3528', 'Gov. ALvarez St. Zamboanga City', '2025-09-04 11:02:10', '2025-09-04 11:02:10', 1);

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
(1, 'admin', '$2y$10$AkjxIDoeDfwa6DKC5ZRg7ey7SKXspi1QYEGQ8TreL7B07TS2O0KlK', 'admin@4nsolar.com', 'admin', 'System Administrator', '2025-08-26 02:04:18', '2025-08-26 02:13:28', 1),
(8, '4nsolar_sales', '$2y$10$iyeR8lt3Wc5s/taRBdDZOOyycXLYUvz8Oj9a6UxgZc5FW2hHTPtUu', 'dp507747@gmail.com', 'sales', 'Ma. Donna Pingoy', '2025-08-28 01:23:15', '2025-08-28 01:23:24', 1),
(9, '4nsolar_hr', '$2y$10$yqP6TipLIsdgxEbEB98ri.nGVbS/bz8WJME2LP7OXcuONHce1Ju2K', 'liddylouorsuga506@gmail.com', 'hr', 'Liddy Lou Orsuga', '2025-08-28 01:44:52', '2025-08-28 01:44:52', 1);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `employee_attendance`
--
ALTER TABLE `employee_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `period_id` (`period_id`);

--
-- Indexes for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_leave_year` (`employee_id`,`leave_type`,`year`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_period` (`employee_id`,`period_id`),
  ADD KEY `period_id` (`period_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_payroll_motor_loan` (`motor_loan`),
  ADD KEY `idx_payroll_cellphone_loan` (`cellphone_loan`);

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
  ADD KEY `idx_quotations_created_at` (`created_at`);

--
-- Indexes for table `quote_items`
--
ALTER TABLE `quote_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quote_items_quote_id` (`quote_id`),
  ADD KEY `idx_quote_items_inventory_item_id` (`inventory_item_id`);

--
-- Indexes for table `solar_projects`
--
ALTER TABLE `solar_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `employee_attendance`
--
ALTER TABLE `employee_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `pos_sales`
--
ALTER TABLE `pos_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `quote_items`
--
ALTER TABLE `quote_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `solar_projects`
--
ALTER TABLE `solar_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `solar_project_items`
--
ALTER TABLE `solar_project_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `employee_attendance`
--
ALTER TABLE `employee_attendance`
  ADD CONSTRAINT `employee_attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `employee_attendance_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`);

--
-- Constraints for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  ADD CONSTRAINT `employee_leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD CONSTRAINT `payroll_periods_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`),
  ADD CONSTRAINT `payroll_records_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quote_items`
--
ALTER TABLE `quote_items`
  ADD CONSTRAINT `quote_items_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quote_items_ibfk_2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `solar_projects`
--
ALTER TABLE `solar_projects`
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
