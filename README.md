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

3. **Configure Database Connection**
   - Open `includes/config.php`
   - Update database credentials if needed (default: root with no password)

4. **Access the System**
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

4. **Suppliers**
   - Manage supplier information
   - Contact details
   - Link suppliers to inventory items

5. **User Management** (Admin/HR only)
   - Create/edit users
   - Assign roles
   - Change passwords
   - Activate/deactivate accounts

6. **Reports**
   - Inventory statistics
   - Project analytics
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

### Database Structure

- **users**: System users with role-based access
- **suppliers**: Equipment suppliers
- **categories**: Product categories
- **inventory_items**: Solar equipment inventory
- **solar_projects**: Customer projects/quotes
- **solar_project_items**: Items included in projects
- **stock_movements**: Inventory tracking history

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

### Support
For technical support or customization requests, please refer to the code comments and documentation within each file.

---

© 2025 4NSOLAR ELECTRICZ Inventory Management System
