-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 07:59 AM
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
(41, 'OSDA', 'MONO 590w (ODA590-36VMHD)', 1, '590w', 3800.00, 3800.00, 0.00, 6, 14, 0, '', 'images/products/product_68b6878bd32d4.jpg', NULL, '2025-09-02 05:58:35', '2025-09-08 05:49:09', 1, 1),
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
(58, 'DEYE', '12kw (SUN-12k-SG04LP1-EU-AM3)', 9, '12kw', 84500.00, 95000.00, 0.00, 6, 0, 0, '', 'images/products/product_68b6903e0c30b.jpg', NULL, '2025-09-02 06:35:26', '2025-09-15 00:58:29', 1, 1),
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
(75, 'Terminal Lugs', 'SC35-8', 7, '35-8', 40.00, 55.00, 0.00, 5, 44, 10, 'No brand', 'images/products/product_68ba88a68da77.jpg', NULL, '2025-09-04 01:25:40', '2025-09-05 06:52:22', 9, 1),
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
(96, 'Alltopelec', '51.2V 100Ah 5kwh', 3, '100Ah 5Kw', 33000.00, 42000.00, 5.00, 6, 0, 5, '', 'images/products/product_68ba87247855a.jpg', NULL, '2025-09-04 02:40:27', '2025-09-05 06:45:56', 9, 1),
(97, 'Alltopelec', '51.2V 200Ah 5kwh', 3, '200Ah 10kwh', 68000.00, 79000.00, 5.00, 6, 0, 5, '', 'images/products/product_68ba878e8af6b.png', NULL, '2025-09-04 02:41:50', '2025-09-05 06:47:42', 9, 1),
(98, 'Alltopelec', '51.2V 314Ah 15kwh', 3, '300Ah 15kwh', 80000.00, 95000.00, 5.00, 5, 0, 5, '', 'images/products/product_68ba87ef41818.png', NULL, '2025-09-04 02:44:53', '2025-09-05 06:49:19', 9, 1),
(99, 'Alltopelec', '51.2V 400Ah 200kwh', 3, '400Ah 200kwh', 115000.00, 135000.00, 5.00, 5, 0, 5, '', 'images/products/product_68ba887bdb097.png', NULL, '2025-09-04 02:47:21', '2025-09-05 06:51:39', 9, 1),
(100, 'Arrow Cable Trray', '50x50', 5, '50x50', 300.00, 750.00, 0.00, 5, 3, 5, '', 'images/products/product_68c7a0d63b511.png', NULL, '2025-09-04 02:55:19', '2025-09-15 05:15:02', 9, 1),
(101, 'Railings', 'Railings', 8, '2.4', 570.00, 690.00, 0.00, 5, 32, 10, '', 'images/products/product_68bfb7905d837.jpg', NULL, '2025-09-04 02:57:49', '2025-09-09 05:13:52', 9, 1),
(102, 'Arrow Cable Trray', '80x80', 5, '80x80', 900.00, 1200.00, 0.00, 5, 4, 5, '', 'images/products/product_68c7a0e1b3a24.png', NULL, '2025-09-04 03:03:49', '2025-09-15 05:15:13', 9, 1),
(103, 'FEE0 ', 'DC SPD Breaker', 7, 'DC SPD', 830.00, 1500.00, 0.00, 5, 6, 6, '', NULL, NULL, '2025-09-04 03:06:52', '2025-09-04 09:03:29', 9, 1),
(104, 'FEE0 ', 'MCCB 200A', 7, '200A', 2500.00, 2800.00, 0.00, 5, 1, 2, '', NULL, NULL, '2025-09-04 03:09:08', '2025-09-04 04:04:18', 9, 1),
(105, 'L Foot', 'L Foot', 8, 'L Foot', 80.00, 150.00, 0.00, 5, 20, 10, '', 'images/products/product_68bfb762d17f2.jpg', NULL, '2025-09-04 03:20:21', '2025-09-09 05:13:06', 9, 1),
(107, 'LV Topsun', '300Ah', 3, '300Ah 15kwh', 100000.00, 110000.00, 0.00, 5, 0, 10, '', NULL, NULL, '2025-09-04 03:26:35', '2025-09-04 03:26:35', 9, 1),
(108, 'FEE0 ', 'AC SPD Breaker', 7, 'AC SPD', 1400.00, 3200.00, 0.00, 5, 0, 5, '', NULL, NULL, '2025-09-04 03:44:30', '2025-09-04 09:02:27', 9, 1),
(110, 'FEE0 ', 'ATS 2P 125A', 7, 'ATS 125A', 1400.00, 2800.00, 0.00, 5, 3, 3, '', NULL, NULL, '2025-09-04 03:59:00', '2025-09-04 09:55:45', 9, 1),
(112, 'Mid clamp', 'Mid Clamp', 8, 'Mid Clamp', 40.00, 65.00, 0.00, 5, 6, 10, '', NULL, NULL, '2025-09-04 04:08:18', '2025-09-04 04:08:18', 9, 1),
(113, 'End Clamp', 'End Clamp', 8, 'End clamp', 40.00, 65.00, 0.00, 5, 12, 10, '', 'images/products/product_68bfb73cac550.jpg', NULL, '2025-09-04 04:09:05', '2025-09-09 05:12:28', 9, 1),
(114, 'Leader pv wire', 'twin core 6.0mm', 5, '6.0', 100.00, 120.00, 0.00, 5, 57, 10, '', 'images/products/product_68c7a1e93780f.png', NULL, '2025-09-04 05:39:36', '2025-09-16 05:22:14', 9, 1),
(115, 'FEE0', 'Battery Cable Red', 5, '35mm', 350.00, 450.00, 0.00, 5, 0, 100, '', NULL, NULL, '2025-09-04 08:56:22', '2025-09-15 05:21:31', 9, 1),
(116, 'FEE0', 'Battery Cable Black', 5, '35mm', 350.00, 450.00, 0.00, 5, 0, 100, '', NULL, NULL, '2025-09-04 08:57:04', '2025-09-15 05:20:49', 9, 1),
(117, 'HDPE PIPE', 'AD34.5', 5, '5mm', 0.10, 0.00, 0.00, 5, 0, 100, '', NULL, NULL, '2025-09-04 09:06:09', '2025-09-04 09:06:09', 9, 1),
(118, 'KOTEN BREAKER', 'AC MCCB BREAKER', 7, '200A ', 2800.00, 4500.00, 0.00, 9, 1, 10, '', NULL, NULL, '2025-09-04 09:57:37', '2025-09-04 11:02:37', 1, 1),
(119, 'PHILFLEX THHN', 'AC WIRE #2', 5, '#2 38MM', 250.00, 450.00, 0.00, 9, 50, 10, 'AC WIRES', NULL, NULL, '2025-09-04 11:05:11', '2025-09-04 11:05:55', 1, 1),
(120, 'METAL ENCLOSURE ', '40X50X20', 7, '400mm x 500 x 200', 2900.00, 3900.00, 0.00, 5, 1, 10, '', NULL, NULL, '2025-09-04 11:10:20', '2025-09-15 05:42:41', 1, 1),
(121, 'DIN RAIL', 'ALLUMINUM RAIL', 4, '1 meter', 80.00, 480.00, 0.00, 9, 10, 10, '', NULL, NULL, '2025-09-04 11:12:32', '2025-09-04 11:12:32', 1, 1),
(122, 'METAL ENCLOSURE', '6X15X18(200X400X500)', 7, '6X15X18(200X400X500)', 2650.00, 4500.00, 0.00, 9, 1, 10, '', NULL, NULL, '2025-09-05 02:09:21', '2025-09-05 02:11:06', 1, 1),
(123, 'Canadian Solar', 'MONO 590w (CS6L-455MS)', 1, '590W', 0.00, 4500.00, 0.00, 5, 10, 20, '', NULL, NULL, '2025-09-08 05:52:41', '2025-09-08 05:54:16', 9, 1),
(124, 'Canadian Solar', 'MONO 610w (CS6L-610MS)', 1, '610W', 4500.00, 5500.00, 5.00, 5, 5, 10, '', NULL, NULL, '2025-09-08 06:12:45', '2025-09-08 06:12:45', 9, 1),
(126, 'Alltopelec', '25,6 5Kw 200Ah', 3, '200Ah', 0.00, 37000.00, 0.00, 5, 5, 5, '', 'images/products/product_68c79f70d999d.jpg', NULL, '2025-09-09 07:20:27', '2025-09-15 05:09:04', 1, 1),
(127, 'FEEO', 'MCCB DC 200A', 7, '200Ah', 0.00, 3950.00, 0.00, 5, 1, 5, '', NULL, NULL, '2025-09-09 07:26:46', '2025-09-09 08:18:34', 1, 1),
(128, 'Koten', 'MCCB AC 225A', 7, '225A', 2850.00, 3500.00, 0.00, 5, 2, 0, '', NULL, NULL, '2025-09-09 07:28:45', '2025-09-15 04:39:48', 1, 1),
(129, 'Canadian Solar', 'Solar Panel 610w', 1, '610w', 0.00, 0.00, 0.00, 5, 5, 10, '', NULL, NULL, '2025-09-09 07:29:41', '2025-09-09 07:29:41', 1, 1),
(130, 'SRNE', '24V', 3, '24v', 0.00, 0.00, 0.00, 5, 1, 3, '', NULL, NULL, '2025-09-09 07:34:30', '2025-09-09 07:34:30', 1, 1),
(131, 'Dim Rail', 'Dim Rail', 8, 'Dim Rail', 0.00, 0.00, 0.00, 5, 0, 10, '', NULL, NULL, '2025-09-09 07:35:39', '2025-09-09 07:35:39', 1, 1),
(132, 'SNAT', '1kw 12v/24v', 2, '24v', 4500.00, 0.00, 5.00, 5, 1, 3, '', NULL, NULL, '2025-09-09 07:39:37', '2025-09-09 07:39:37', 1, 1),
(133, 'SNAT', '2kw 12l 24V', 2, '2KW', 11500.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 07:41:09', '2025-09-09 07:41:09', 1, 1),
(134, 'SNAT', '3KW 12/ 24v/ 48v', 2, '3kw', 12500.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 07:42:34', '2025-09-09 07:42:34', 1, 1),
(135, 'SNAT', '4KW 24V/48V ', 2, '', 22000.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 07:46:38', '2025-09-09 07:46:38', 1, 1),
(137, 'SNAT', '5KW/24V/48V', 2, '5kw', 25000.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 07:50:41', '2025-09-09 07:50:41', 1, 1),
(138, 'SRNE', '3KW/24V', 9, '3kw', 19000.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 07:53:31', '2025-09-09 07:53:31', 1, 1),
(139, 'SRNE', '3KW 24V(HF2430S60-100V)', 9, '3kw', 19000.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 07:57:33', '2025-09-09 07:57:33', 1, 1),
(140, 'SRNE', '5KW 48V(MF4850S80-H)', 9, '5kw', 29000.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 08:00:22', '2025-09-09 08:00:22', 1, 1),
(141, 'SRNE', '8KW 48V(ASF4880S180-H)', 9, '8kw', 68000.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 08:02:30', '2025-09-09 08:02:30', 1, 1),
(143, 'CABLE TRAY ', '80X80', 8, '2Mtrs', 600.00, 900.00, 0.00, 9, 0, 3, '', 'images/products/product_68c7a0eb8220b.png', NULL, '2025-09-09 08:09:11', '2025-09-15 05:15:23', 1, 1),
(144, 'SOLAR PV CABLES', '4m (RED)', 5, '1X4 mm', 40.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 08:13:03', '2025-09-09 08:13:03', 1, 1),
(145, 'SOLAR PV CABLES', '4mm (black)', 5, '1x4mm', 40.00, 0.00, 0.00, 5, 0, 3, '', NULL, NULL, '2025-09-09 08:15:21', '2025-09-09 08:15:21', 1, 1),
(146, 'TRINA SOLAR', '590W', 1, '590W', 0.00, 0.00, 0.00, 9, 0, 10, '', NULL, NULL, '2025-09-12 02:26:28', '2025-09-12 02:26:28', 9, 1),
(147, 'TRINA SOLAR', '600W', 1, '600W', 0.00, 0.00, 0.00, 9, 0, 10, '', NULL, NULL, '2025-09-12 02:27:10', '2025-09-12 02:27:10', 9, 1),
(148, 'TRINA SOLAR', '700w', 1, '700w', 0.00, 0.00, 0.00, 5, 0, 10, '', NULL, NULL, '2025-09-12 03:19:31', '2025-09-12 03:19:31', 9, 1),
(149, 'TRINA SOLAR', '580w', 1, '580w', 0.00, 0.00, 0.00, 9, 0, 10, '', NULL, NULL, '2025-09-12 03:20:33', '2025-09-12 03:20:33', 9, 1),
(150, 'TRINA SOLAR', '550w', 1, '550w', 0.00, 0.00, 0.00, 9, 0, 10, '', NULL, NULL, '2025-09-12 03:28:43', '2025-09-12 03:28:43', 9, 1),
(151, 'CST ', '51.2V 324Ah', 3, '324Ah', 0.00, 0.00, 0.00, 9, 0, 10, '', NULL, NULL, '2025-09-12 05:42:31', '2025-09-12 05:42:31', 9, 1),
(152, 'CST ', '51.2V 300Ah', 3, '300Ah', 0.00, 0.00, 0.00, 9, 0, 10, '', NULL, NULL, '2025-09-12 05:49:14', '2025-09-12 05:49:14', 9, 1),
(153, 'CST ', '51.2V 100Ah', 3, '100Ah', 0.00, 0.00, 0.00, 9, 0, 10, '', NULL, NULL, '2025-09-12 05:50:53', '2025-09-12 05:50:53', 9, 1),
(154, 'CST ', '24V 100Ah', 3, '100Ah', 0.00, 0.00, 0.00, 9, 0, 10, '', NULL, NULL, '2025-09-12 05:51:42', '2025-09-12 05:51:42', 9, 1),
(158, 'MC4 Y CONNECTOR ', 'PV 004', 5, 'NONE', 380.00, 500.00, 0.00, 5, 8, 10, '', 'images/products/product_68c7a6204a3ee.jfif', NULL, '2025-09-15 05:37:36', '2025-09-15 05:37:36', 1, 1),
(159, 'DIN RAIL', 'ALLUMINUM 1METER', 8, '1 METER', 160.00, 480.00, 0.00, 5, 1, 10, '', NULL, NULL, '2025-09-15 06:29:17', '2025-09-15 06:29:17', 1, 1),
(161, 'PVC 1 1/2', 'ELBOW', 8, '1 1/2', 68.00, 82.00, 0.00, 5, 7, 10, '', NULL, NULL, '2025-09-15 06:42:46', '2025-09-15 06:42:46', 1, 1),
(162, 'PVC 1 1/2', 'PIPE', 8, '1 1/2', 260.00, 350.00, 0.00, 5, 4, 10, '', NULL, NULL, '2025-09-15 06:45:05', '2025-09-15 06:45:05', 1, 1),
(163, 'Metal Enclosure', 'Breaker Box', 8, '300*400*200mm', 1900.00, 0.00, 0.00, 5, 0, 5, '', NULL, NULL, '2025-09-15 06:49:50', '2025-09-15 06:49:50', 9, 1),
(164, 'Metal Enclosure', 'Breaker Box', 8, '400*500*200mm', 2900.00, 0.00, 0.00, 5, 0, 5, '', NULL, NULL, '2025-09-15 06:50:39', '2025-09-15 06:50:39', 9, 1),
(165, 'IP65', 'Junction Box', 8, '100*100*70mm', 120.00, 0.00, 0.00, 5, 0, 5, '', NULL, NULL, '2025-09-15 06:51:51', '2025-09-15 06:51:51', 9, 1),
(166, 'TERMINAL LUGS', '(SC35-10)', 7, '35-10M', 40.00, 65.00, 0.00, 5, 40, 10, '', NULL, NULL, '2025-09-15 06:52:32', '2025-09-15 06:52:32', 1, 1),
(167, 'IP65', 'Junction Box', 8, '150*150*70', 130.00, 0.00, 0.00, 9, 0, 5, '', NULL, NULL, '2025-09-15 06:52:55', '2025-09-15 06:52:55', 9, 1),
(168, 'IP65', 'Junction Box', 8, '200*200*80mm', 240.00, 0.00, 0.00, 5, 0, 5, '', NULL, NULL, '2025-09-15 06:54:13', '2025-09-15 06:54:13', 9, 1),
(169, 'NYLONE ROPE', 'NONE', 8, '1/2', 24.00, 50.00, 0.00, 5, 40, 10, '', NULL, NULL, '2025-09-15 06:54:22', '2025-09-15 06:54:22', 1, 1),
(170, 'MC4 Crimper', 'Crimper', 8, '0', 480.00, 0.00, 0.00, 5, 0, 10, '', NULL, NULL, '2025-09-15 06:56:47', '2025-09-15 06:56:47', 9, 1),
(171, 'Cable Tie', 'Tie', 8, '3x150', 55.00, 0.00, 0.00, 5, 0, 10, '', NULL, NULL, '2025-09-15 06:58:42', '2025-09-15 06:58:42', 9, 1),
(172, 'ELECTRICAL TAPE', 'BLACK', 8, 'NONE', 42.00, 100.00, 0.00, 5, 5, 10, '', NULL, NULL, '2025-09-15 06:58:57', '2025-09-15 06:58:57', 1, 1),
(173, 'Cable Tie', 'Cable Tie', 8, '4x200', 100.00, 0.00, 0.00, 5, 0, 10, '', NULL, NULL, '2025-09-15 07:00:02', '2025-09-15 07:00:02', 9, 1),
(174, 'ELECTRICAL TAPE', 'RED', 8, 'CM', 28.00, 100.00, 0.00, 5, 5, 10, '', NULL, NULL, '2025-09-15 07:02:59', '2025-09-15 07:02:59', 1, 1),
(175, 'MC4 Connector', 'MC4 Connector', 8, '0', 60.00, 75.00, 0.00, 5, 16, 20, '', NULL, NULL, '2025-09-16 05:29:16', '2025-09-16 05:29:16', 9, 1);

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
(39, 'RCP20250908-1893', '', '', 0.00, 0.00, 0.00, NULL, 0.00, 0.00, 'pending', NULL, 9, '2025-09-08 05:45:46', NULL),
(40, 'RCP20250908-3037', '', '', 17600.00, 0.00, 17600.00, NULL, 0.00, 0.00, 'pending', NULL, 9, '2025-09-08 05:47:56', NULL),
(41, 'RCP20250908-5261', '', '', 0.00, 0.00, 0.00, NULL, 0.00, 0.00, 'pending', NULL, 9, '2025-09-08 05:49:14', NULL),
(42, 'RCP20250908-5306', '', '', 0.00, 0.00, 0.00, NULL, 0.00, 0.00, 'pending', NULL, 9, '2025-09-08 05:52:57', NULL),
(43, 'RCP20250908-5169', '', '', 0.00, 0.00, 0.00, NULL, 0.00, 0.00, 'pending', NULL, 9, '2025-09-08 05:53:02', NULL),
(44, 'RCP20250908-8672', '', '', 0.00, 0.00, 0.00, NULL, 0.00, 0.00, 'pending', NULL, 9, '2025-09-08 05:53:30', NULL),
(46, 'RCP20250908-2619', '', '', 18000.00, 0.00, 18000.00, 'cash', 18000.00, 0.00, 'completed', NULL, 9, '2025-09-08 05:53:47', '2025-09-08 05:54:16'),
(47, 'RCP20250910-0185', 'Vincent', '', 2800.00, 399.28, 2400.72, 'cash', 2400.72, 0.00, 'completed', NULL, 9, '2025-09-10 04:24:36', '2025-09-10 04:27:44'),
(48, 'RCP20250911-4592', 'Vincent', '', 1400.00, 199.92, 1200.08, 'cash', 1200.08, 0.00, 'completed', NULL, 9, '2025-09-10 04:24:36', '2025-09-10 04:27:44'),
(49, 'RCP20250916-4796', 'Buhari', '', 120.00, 25.00, 95.00, 'cash', 95.00, 0.00, 'completed', NULL, 9, '2025-09-16 05:20:22', '2025-09-16 05:22:14'),
(50, 'RCP20250916-2017', 'Buhari', '', 0.00, 0.00, 0.00, NULL, 0.00, 0.00, 'pending', NULL, 9, '2025-09-16 05:23:17', NULL);

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
(23, 38, 75, 2, 55.00, 0.00, 0.00, 110.00, '2025-09-04 05:41:24'),
(24, 40, 26, 4, 4400.00, 0.00, 0.00, 17600.00, '2025-09-08 05:48:15'),
(25, 46, 123, 4, 4500.00, 0.00, 0.00, 18000.00, '2025-09-08 05:54:12'),
(26, 47, 114, 20, 140.00, 14.26, 399.28, 2400.72, '2025-09-10 04:27:14'),
(27, 48, 114, 10, 140.00, 14.28, 199.92, 1200.08, '2025-09-11 02:13:06'),
(28, 49, 114, 1, 120.00, 20.83, 25.00, 95.00, '2025-09-16 05:22:07');

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
  `project_id` int(11) DEFAULT NULL COMMENT 'ID of the solar project created from this quotation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quote_number`, `project_number`, `customer_name`, `customer_phone`, `proposal_name`, `subtotal`, `total_discount`, `total_amount`, `status`, `valid_until`, `notes`, `created_by`, `created_at`, `updated_at`, `project_id`) VALUES
(10, 'QTE20250904-0481', NULL, 'Gucela', '019872', '8kw Supply and installation', 99800.00, 9790.00, 90010.00, 'draft', NULL, NULL, 1, '2025-09-04 00:59:38', '2025-09-05 08:01:54', NULL),
(11, 'QTE20250904-0782', NULL, 'Missuara 8kw', '09173031588', 'Supply and Installation of 8kw to pangutaran', 269370.00, 0.00, 269370.00, 'draft', NULL, NULL, 1, '2025-09-04 03:19:46', '2025-09-04 04:04:31', NULL),
(13, 'QTE20250904-7312', NULL, 'Thai Alamia', '09173081539', 'Additional 16kw upgrade', 177980.00, 2925.00, 175055.00, 'draft', NULL, NULL, 1, '2025-09-04 09:49:31', '2025-09-05 08:07:04', NULL),
(14, 'QTE20250905-8708', NULL, 'Testing', '09918195482', '32kw Solar Panel', 1920000.00, 0.00, 1920000.00, 'draft', NULL, NULL, 1, '2025-09-05 03:04:54', '2025-09-09 02:05:36', NULL),
(17, 'QTE20250909-9751', NULL, 'Andres Bonifacio', '09918195482', '32kw Solar Panel', 174225.00, 0.00, 174225.00, 'accepted', NULL, NULL, 1, '2025-09-09 03:50:15', '2025-09-11 09:19:51', 17),
(18, 'QTE20250912-0659', 'PRJ-202509-0001', 'Pazlor Lim', '09750646424', '', 289700.00, 40000.00, 249700.00, 'accepted', NULL, NULL, 9, '2025-09-12 04:00:13', '2025-09-15 05:23:05', NULL);

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
(2, 17, 'Andrés Bonifacio y de Castro', '09918195482', 'Zambo', 1, 1, 0, '2025-09-09', '2025-09-09 03:50:15', '2025-09-09 03:50:15'),
(3, 18, 'Pazlor Lim', '09750646424', 'Villa Sta. Maria, Zamboanga City', 0, 1, 0, '2025-09-10', '2025-09-12 04:00:13', '2025-09-12 04:02:15');

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
(11, 10, 73, 12000, 8.00, 10.00, 9600.00, 86400.00, '2025-09-04 00:59:38'),
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
(30, 13, 119, 20, 450.00, 20.00, 1800.00, 7200.00, '2025-09-04 11:07:09'),
(32, 13, 121, 1, 480.00, 0.00, 0.00, 480.00, '2025-09-04 11:12:47'),
(33, 13, 122, 1, 4500.00, 25.00, 1125.00, 3375.00, '2025-09-05 02:11:23'),
(35, 14, 73, 32000, 60.00, 0.00, 0.00, 1920000.00, '2025-09-05 03:04:54'),
(38, 17, 73, 3200, 40.00, 0.00, 0.00, 128000.00, '2025-09-09 03:50:15'),
(39, 17, 126, 1, 37000.00, 0.00, 0.00, 37000.00, '2025-09-11 09:00:23'),
(40, 17, 26, 1, 4400.00, 0.00, 0.00, 4400.00, '2025-09-11 09:00:34'),
(41, 17, 71, 1, 2500.00, 0.00, 0.00, 2500.00, '2025-09-11 09:00:43'),
(42, 17, 119, 1, 450.00, 0.00, 0.00, 450.00, '2025-09-11 09:00:56'),
(43, 17, 121, 1, 480.00, 0.00, 0.00, 480.00, '2025-09-11 09:01:07'),
(44, 17, 80, 1, 650.00, 0.00, 0.00, 650.00, '2025-09-11 09:01:17'),
(45, 17, 75, 1, 55.00, 0.00, 0.00, 55.00, '2025-09-11 09:01:24'),
(46, 17, 101, 1, 690.00, 0.00, 0.00, 690.00, '2025-09-11 09:01:38'),
(47, 18, 73, 24000, 5.00, 33.33, 40000.00, 80000.00, '2025-09-12 04:00:14'),
(48, 18, 58, 2, 84500.00, 0.00, 0.00, 169000.00, '2025-09-15 01:10:16'),
(49, 18, 116, 1, 350.00, 0.00, 0.00, 350.00, '2025-09-15 05:22:39'),
(50, 18, 115, 1, 350.00, 0.00, 0.00, 350.00, '2025-09-15 05:23:05');

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
(2, 17, 0, 0, 1, 3200.00, 1, 0, 0, 'Canadian', 'Deye', '2025-09-11', 1, 0, 0, 0, 'yes', '300AH', 'yes', 'yes', '', '', '2025-09-09 03:50:15', '2025-09-09 03:50:15'),
(3, 18, 0, 0, 1, 24.00, 1, 0, 0, 'TRINA SOLAR 600W- 48pcs', 'DEYE 12kw 2pcs', '2025-09-10', 1, 0, 0, 0, 'yes', '3 pcs 300Ah', 'no', 'yes', '', '', '2025-09-12 04:00:14', '2025-09-12 04:02:15');

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
(17, '32kw Solar Panel', 'Andres Bonifacio', '', '09918195482', '', 'Converted from quotation QTE20250909-9751', 0.55, 46225.00, 174225.00, 0.00, 174225.00, 'approved', '2025-09-11 09:19:51', '2025-09-11 09:19:51', 1, 17),
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
  `total_amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `solar_project_items`
--

INSERT INTO `solar_project_items` (`id`, `project_id`, `inventory_item_id`, `quantity`, `unit_base_price`, `unit_selling_price`, `discount_amount`, `total_amount`) VALUES
(32, 17, 73, 3200, 0.00, 40.00, 0.00, 128000.00),
(33, 17, 126, 1, 37000.00, 37000.00, 0.00, 37000.00),
(34, 17, 26, 1, 4400.00, 4400.00, 0.00, 4400.00),
(35, 17, 71, 1, 2500.00, 2500.00, 0.00, 2500.00),
(36, 17, 119, 1, 450.00, 450.00, 0.00, 450.00),
(37, 17, 121, 1, 480.00, 480.00, 0.00, 480.00),
(38, 17, 80, 1, 650.00, 650.00, 0.00, 650.00),
(39, 17, 75, 1, 55.00, 55.00, 0.00, 55.00),
(40, 17, 101, 1, 690.00, 690.00, 0.00, 690.00),
(42, 19, 73, 16000, 0.00, 5.00, 0.00, 80000.00);

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
(36, 114, 'out', 1, 58, 57, '', 49, 'POS sale - RCP20250916-4796', '2025-09-16 05:22:14', 9);

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
  ADD KEY `idx_project_number` (`project_number`);

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
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_sales`
--
ALTER TABLE `pos_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `pos_sale_items`
--
ALTER TABLE `pos_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `quote_customer_info`
--
ALTER TABLE `quote_customer_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quote_items`
--
ALTER TABLE `quote_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `quote_solar_details`
--
ALTER TABLE `quote_solar_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `solar_projects`
--
ALTER TABLE `solar_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `solar_project_items`
--
ALTER TABLE `solar_project_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

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
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

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
