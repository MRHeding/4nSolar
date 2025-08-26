-- 4NSOLAR ELECTRICZ Inventory Management System Database

-- Create the database
CREATE DATABASE IF NOT EXISTS 4nsolar_inventory;
USE 4nsolar_inventory;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'hr', 'sales') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Suppliers table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Product categories for solar equipment
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Inventory items table
CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    category_id INT,
    size_specification VARCHAR(100),
    base_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    supplier_id INT,
    stock_quantity INT DEFAULT 0,
    minimum_stock INT DEFAULT 10,
    description TEXT,
    specifications JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Solar project quotes/builds table
CREATE TABLE solar_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(100) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    customer_address TEXT,
    system_size_kw DECIMAL(8,2),
    total_base_cost DECIMAL(12,2) DEFAULT 0,
    total_selling_price DECIMAL(12,2) DEFAULT 0,
    total_discount DECIMAL(12,2) DEFAULT 0,
    final_amount DECIMAL(12,2) DEFAULT 0,
    project_status ENUM('draft', 'quoted', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Solar project items (components used in each project)
CREATE TABLE solar_project_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_base_price DECIMAL(10,2) NOT NULL,
    unit_selling_price DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (project_id) REFERENCES solar_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
);

-- Stock movements table for tracking inventory changes
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_item_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    reference_type ENUM('purchase', 'sale', 'project', 'adjustment', 'return') NOT NULL,
    reference_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Solar Panels', 'Photovoltaic solar panels of various types and capacities'),
('Inverters', 'DC to AC power inverters for solar systems'),
('Batteries', 'Energy storage systems and batteries'),
('Mounting Systems', 'Roof and ground mounting hardware'),
('Cables & Wiring', 'DC and AC cables, connectors, and wiring components'),
('Monitoring Systems', 'System monitoring and control equipment'),
('Safety Equipment', 'Fuses, breakers, and safety devices'),
('Tools & Accessories', 'Installation tools and miscellaneous accessories');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, role, full_name) VALUES
('admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyUBwNZ/OQJGnOGGYe0LiL4eASBJu', 'admin@4nsolar.com', 'admin', 'System Administrator');

-- Insert sample suppliers
INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES
('SunPower Technologies', 'John Smith', 'john@sunpower.com', '+1-555-0101', '123 Solar Street, Energy City, CA 90210'),
('Canadian Solar Inc.', 'Sarah Johnson', 'sarah@canadiansolar.com', '+1-555-0102', '456 Panel Avenue, Solar Valley, TX 75001'),
('Enphase Energy', 'Mike Davis', 'mike@enphase.com', '+1-555-0103', '789 Inverter Road, Power Town, FL 33101'),
('Tesla Energy', 'Emma Wilson', 'emma@tesla.com', '+1-555-0104', '321 Battery Lane, Electric City, NV 89101');

-- Insert sample inventory items
INSERT INTO inventory_items (brand, model, category_id, size_specification, base_price, selling_price, discount_percentage, supplier_id, stock_quantity, minimum_stock, description) VALUES
('SunPower', 'SPR-X22-370', 1, '370W', 250.00, 320.00, 0, 1, 50, 10, 'High-efficiency monocrystalline solar panel'),
('Canadian Solar', 'CS3K-300P', 1, '300W', 180.00, 230.00, 5, 2, 75, 15, 'Polycrystalline solar panel with reliable performance'),
('Enphase', 'IQ7PLUS-72-2-US', 2, '290W', 150.00, 195.00, 0, 3, 30, 5, 'Microinverter for residential solar systems'),
('Tesla', 'Powerwall 2', 3, '13.5kWh', 6500.00, 8200.00, 0, 4, 10, 2, 'Lithium-ion battery storage system'),
('IronRidge', 'XR-1000-168A', 4, '168"', 85.00, 110.00, 0, 1, 100, 20, 'Aluminum rail for roof mounting systems');
