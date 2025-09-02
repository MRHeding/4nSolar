# 4NSOLAR ELECTRICZ - Inventory Management System

## Setup Instructions

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

### Installation Steps

1. **Start XAMPP Services**
   - Start Apache and MySQL services from XAMPP Control Panel

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `database.sql` file to create the database and tables
   - Or run the SQL commands directly in phpMyAdmin

4. **Setup POS System (Optional)**
   - Run the POS setup script: `http://localhost/4nSolar/setup_pos.php`
   - Or execute the SQL commands from `pos_tables.sql` in phpMyAdmin
   - This enables the Point of Sale functionality for retail sales

5. **Access the System**
   - Navigate to: http://localhost/4nSolar
   - Default login: 
     - Username: `admin`
     - Password: `admin123`

### Features

#### User Roles & Permissions
- **Admin**: Full system access
- **HR**: Can manage users, inventory, suppliers, and projects
- **Sales**: Can view and create projects, view inventory

#### Core Modules

1. **Dashboard**
   - Overview statistics
   - Low stock alerts
   - Recent projects
   - Quick actions

2. **Inventory Management**
   - Add/edit/delete inventory items
   - Track stock levels
   - Set minimum stock alerts
   - Categorize products
   - Stock movement history

3. **Solar Projects**
   - Create project quotes
   - Add items to projects
   - Calculate totals with discounts
   - Track project status
   - Print professional quotes

4. **Point of Sale (POS)**
   - Process retail sales transactions
   - Add items with quantity and discounts
   - Multiple payment methods (cash, credit card, bank transfer, check)
   - Print professional receipts
   - Sales history and reporting
   - Real-time inventory updates
   - Customer information tracking

5. **Suppliers**
   - Manage supplier information
   - Contact details
   - Link suppliers to inventory items

6. **User Management** (Admin/HR only)
   - Create/edit users
   - Assign roles
   - Change passwords
   - Activate/deactivate accounts

7. **Reports**
   - Inventory statistics
   - Project analytics
   - POS sales reports
   - Monthly trends
   - Low stock reports
   - Export capabilities

#### Key Features
- Responsive design with Tailwind CSS
- Role-based access control
- Professional quote printing
- Stock level monitoring
- Comprehensive reporting
- CSV export functionality
- Print capabilities
- Point of Sale (POS) system for retail sales
- Real-time inventory updates
- Multiple payment method support
- Receipt printing and history tracking

### Database Structure

- **users**: System users with role-based access
- **suppliers**: Equipment suppliers
- **categories**: Product categories
- **inventory_items**: Solar equipment inventory
- **solar_projects**: Customer projects/quotes
- **solar_project_items**: Items included in projects
- **stock_movements**: Inventory tracking history
- **pos_sales**: Point of sale transactions
- **pos_sale_items**: Items sold in POS transactions

### Security Features
- Password hashing
- Session management
- Role-based permissions
- SQL injection prevention
- XSS protection

### Default Data
The system comes with:
- Sample inventory categories
- Demo suppliers
- Admin user account
- Sample inventory items

### Customization
- Company branding in quotes
- Additional product categories
- Custom user roles
- Report modifications
- UI theme adjustments
- POS receipt customization
- Wire management integration (planned)

### Recent Updates
- **Point of Sale (POS) System**: Complete retail sales functionality with payment processing, receipt printing, and sales history
- **Enhanced Database**: Added POS tables for sales transactions and items
- **Wire Management**: Placeholder files added for future wire/cable inventory management features
- **Image Management**: Replaced base64 image data with SVG format for better performance

### Support
For technical support or customization requests, please refer to the code comments and documentation within each file.

---

© 2025 4NSOLAR ELECTRICZ Inventory Management System
