-- SQL script to add customer information and solar project details tables

-- Create table for customer information
CREATE TABLE `quote_customer_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_method_email` tinyint(1) DEFAULT 0,
  `contact_method_phone` tinyint(1) DEFAULT 0,
  `contact_method_sms` tinyint(1) DEFAULT 0,
  `account_creation_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`),
  CONSTRAINT `quote_customer_info_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create table for solar project details
CREATE TABLE `quote_solar_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `net_metering` enum('yes','no') DEFAULT NULL,
  `confirmed` enum('yes','no') DEFAULT NULL,
  `client_signature` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`),
  CONSTRAINT `quote_solar_details_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
