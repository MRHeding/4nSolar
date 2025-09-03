-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2025 at 02:18 AM
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
(26, 'Canadian Solar', 'MONO 550w (CS6W-550MS)', 1, '550w', 4400.00, 4400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55bb655a9b.jpg', NULL, '2025-09-01 08:39:18', '2025-09-01 08:39:57', 1, 1),
(27, 'Canadian Solar', 'MONO 555w (CS6L-555MS)', 1, '555w', 4400.00, 4400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55c8c4d739.jpg', NULL, '2025-09-01 08:41:35', '2025-09-01 08:42:52', 1, 1),
(28, 'Canadian Solar', 'MONO 580w (CS6W-580TB-AG)', 1, '580w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55d1277ecb.jpg', NULL, '2025-09-01 08:45:06', '2025-09-01 08:45:06', 1, 1),
(29, 'Canadian Solar', 'MONO 585w (CS6W-585TB-AG)', 1, '585w', 4100.00, 4100.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55d64d1b99.jpg', NULL, '2025-09-01 08:46:28', '2025-09-01 08:46:28', 1, 1),
(30, 'Canadian Solar', 'MONO 600w (CS6.1-72TB600)', 1, '600w', 4200.00, 4200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b55dfe7d0ad.jpg', NULL, '2025-09-01 08:49:02', '2025-09-01 08:49:02', 1, 1),
(31, 'Canadian Solar', 'MONO 605w (CS6.1-72TB605)', 1, '605w', 4300.00, 4300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6679bb2663.jpg', NULL, '2025-09-02 03:42:02', '2025-09-02 03:42:19', 1, 1),
(32, 'Canadian Solar', 'MONO 610w (CS6.1-72TB-610)', 1, '610w', 4400.00, 4400.00, 0.00, 6, 0, 0, '', 'images/products/product_68b667e54b564.jpg', NULL, '2025-09-02 03:43:33', '2025-09-02 03:43:33', 1, 1),
(33, 'Canadian Solar', 'MONO 615w (CS6.2-66TB-615)', 1, '615w', 4500.00, 4500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6684e50bec.jpg', NULL, '2025-09-02 03:45:18', '2025-09-02 03:45:18', 1, 1),
(34, 'Canadian Solar', 'MONO 620w (CS6.2-66TB-620)', 1, '620w', 4600.00, 4600.00, 0.00, 6, 0, 0, '', 'images/products/product_68b668d4a4eea.jpg', NULL, '2025-09-02 03:47:32', '2025-09-02 03:47:32', 1, 1),
(35, 'Canadian Solar', 'MONO 650w (CS7N-650wMB-AG)', 1, '650w', 4500.00, 4500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68562be9f1.jpg', NULL, '2025-09-02 05:49:22', '2025-09-02 05:49:22', 1, 1),
(36, 'Canadian Solar', 'MONO 700w (CS7N-700TB-AG)', 1, '700w', 4800.00, 4800.00, 0.00, 6, 0, 0, '', 'images/products/product_68b685c801086.jpg', NULL, '2025-09-02 05:51:04', '2025-09-02 05:51:04', 1, 1),
(37, 'OSDA', 'MONO 455w (ODA455-30V-MH)', 1, '445w', 3200.00, 3200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68690a379c.jpg', NULL, '2025-09-02 05:54:24', '2025-09-02 05:54:24', 1, 1),
(38, 'OSDA', 'MONO 500w (ODA500-33V-MH)', 1, '500w', 3500.00, 3500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b686c48d8f0.jpg', NULL, '2025-09-02 05:55:16', '2025-09-02 05:55:16', 1, 1),
(39, 'OSDA', 'MONO 550w (ODA550-36V-MHD)', 1, '550w', 3650.00, 3650.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68707dbc50.jpg', NULL, '2025-09-02 05:56:23', '2025-09-02 05:56:23', 1, 1),
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
(51, 'DEYE', '3.6kw (SUN-3.6k-SG04LP1-EU)', 2, '3.6kw', 39610.00, 39610.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68e759001e.jpg', NULL, '2025-09-02 06:28:05', '2025-09-02 06:28:05', 1, 1),
(52, 'DEYE', '5kw (SUN-5k-SG04LP1-EU-P)', 2, '5kw', 45268.00, 45268.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ebb2c6a1.jpg', NULL, '2025-09-02 06:29:15', '2025-09-02 06:29:15', 1, 1),
(53, 'DEYE', '5kw (SUN-5k-SG04LP1-EU-SM2)', 2, '5kw', 41732.00, 41732.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ef614a2c.jpg', NULL, '2025-09-02 06:30:14', '2025-09-02 06:30:14', 1, 1),
(54, 'DEYE', '6kw (SUN-6k-SG04LP1-EU)', 2, '6kw', 42300.00, 42300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68f2e65d10.jpg', NULL, '2025-09-02 06:31:10', '2025-09-02 06:31:10', 1, 1),
(55, 'DEYE', '8kw (SUN-8k-SG04LP1-EU)', 2, '8kw', 42300.00, 42300.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68f768dd36.jpg', NULL, '2025-09-02 06:32:22', '2025-09-02 06:32:22', 1, 1),
(56, 'DEYE', '8kw (SUN-8k-SG05LP1-SM2)', 2, '8kw', 58200.00, 58200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68fae52409.jpg', NULL, '2025-09-02 06:33:18', '2025-09-02 06:33:18', 1, 1),
(57, 'DEYE', '10kw (SUN-10k-SG04LP1-EU-AM3)', 2, '10kw', 58200.00, 58200.00, 0.00, 6, 0, 0, '', 'images/products/product_68b68ff3f3218.jpg', NULL, '2025-09-02 06:34:27', '2025-09-02 06:34:27', 1, 1),
(58, 'DEYE', '12kw (SUN-12k-SG04LP1-EU-AM3)', 2, '12kw', 84500.00, 84500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6903e0c30b.jpg', NULL, '2025-09-02 06:35:26', '2025-09-02 06:35:42', 1, 1),
(59, 'DEYE', '16kw (SUN-16k-SG04LP1-EU)', 2, '16kw', 109000.00, 109000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69074839d7.jpg', NULL, '2025-09-02 06:36:36', '2025-09-02 06:36:36', 1, 1),
(60, 'DEYE', '12kw (SUN-12K-SG04LP3-EU)', 9, '12kw', 94500.00, 94500.00, 0.00, 6, 0, 0, '', 'images/products/product_68b691d5d5f8b.jpg', NULL, '2025-09-02 06:42:29', '2025-09-02 06:42:29', 1, 1),
(61, 'DEYE', '20kw (SUN-20K-SG01HP3-EU-AM2)', 9, '20kw', 149000.00, 149000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69241e2389.jpg', NULL, '2025-09-02 06:44:17', '2025-09-02 06:44:17', 1, 1),
(62, 'DEYE', '30kw (SUN-30K-SG01HP3-EU-AM2)', 9, '30kw', 229000.00, 229000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6927262bbc.jpg', NULL, '2025-09-02 06:45:06', '2025-09-02 06:45:06', 1, 1),
(63, 'DEYE', '50kw (SUN-50K-SG01HP3-EU-BM4)', 9, '50kw', 279000.00, 279000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b692aa8143f.jpg', NULL, '2025-09-02 06:46:02', '2025-09-02 06:46:02', 1, 1),
(64, 'Eastron', 'Eastron Smart Meter (w/ 3CT)', 6, '(w/ 3CT)', 11000.00, 11000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b693af922dc.jpg', NULL, '2025-09-02 06:50:23', '2025-09-02 06:50:23', 1, 1),
(65, 'SRNE', '6kw (HESP48DS100-H)', 9, '6kw', 35000.00, 35000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69a460f8ce.jpg', NULL, '2025-09-02 07:18:30', '2025-09-02 07:18:30', 1, 1),
(66, 'SRNE', '12kw (HESP4812DS200-H)', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69a84474f4.jpg', NULL, '2025-09-02 07:19:32', '2025-09-02 07:19:32', 1, 1),
(67, 'SRNE', '12kw (HESP1203SH3) HV', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69eb22f264.jpg', NULL, '2025-09-02 07:37:22', '2025-09-02 07:37:22', 1, 1),
(68, 'SRNE', '12kw (HESP4812DSH3) LV', 9, '12kw', 69000.00, 69000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b69ef86b1d6.jpg', NULL, '2025-09-02 07:38:32', '2025-09-02 07:38:32', 1, 1);

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
(5, 'Jahzeel', 'Ms. Jahzeel', 'jahzeel@gmail.com', '09063272815', 'NCR Metro Manila Philippines', '2025-08-27 06:01:45', '2025-08-27 06:01:45', 1),
(6, 'ALLTOPELEC', 'Sir Victor', 'altopelec@gmail.com', '0218452245', 'Cagayan De Oro', '2025-08-28 00:47:42', '2025-08-28 03:39:29', 1);

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
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

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
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_sales`
--
ALTER TABLE `pos_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `solar_projects`
--
ALTER TABLE `solar_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `solar_project_items`
--
ALTER TABLE `solar_project_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

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
