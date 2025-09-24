# 4nSolar Management System

[![System Status](https://img.shields.io/badge/status-active-brightgreen.svg)]()
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)]()
[![Database](https://img.shields.io/badge/database-mysql-orange.svg)]()
[![License](https://img.shields.io/badge/license-proprietary-red.svg)]()

A comprehensive solar equipment inventory and project management system designed for 4nSolar ELECTRICZ. This system provides complete business management capabilities including inventory tracking with serial number management, project quotations, point-of-sale functionality with quotation import, payroll management, installment payment tracking, employee attendance, and comprehensive reporting with advanced analytics.

## 🚀 Quick Start

### Prerequisites
- **XAMPP** (Apache + MySQL + PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Safari, Edge)
- **Minimum 512MB RAM** for PHP
- **100MB+ disk space**

### Installation Steps

1. **Start XAMPP Services**
   ```
   Start Apache and MySQL from XAMPP Control Panel
   ```

2. **Database Setup**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Import `database.sql` to create tables and default data
   - Alternative: Run SQL commands directly in phpMyAdmin

3. **POS System Setup (Optional)**
   - Run: `http://localhost/4nsolarSystem/setup_pos.php`
   - Or execute SQL from `pos_tables.sql` in phpMyAdmin
   - Enables complete retail sales functionality

4. **Payroll System Setup (Optional)**
   - Run: `http://localhost/4nsolarSystem/setup_payroll_tables.php`
   - Or execute SQL from `database/payroll_system.sql` in phpMyAdmin
   - Enables employee management and payroll processing

5. **Installment System Setup (Optional)**
   - Run: `http://localhost/4nsolarSystem/setup_installment_system.php`
   - Or execute SQL from `database/installment_system.sql` in phpMyAdmin
   - Enables payment plan management for projects

6. **System Access**
   - URL: `http://localhost/4nsolarSystem`
   - **Default Admin Login:**
     - Username: `admin`
     - Password: `admin123`
   
7. **System Testing**
   - Run comprehensive tests: `http://localhost/4nsolarSystem/system_comprehensive_test.php`
   - Validates all system components and integrations

## 🎯 Core Features

### 🔐 Authentication & Security
- **Multi-Role System**: Admin, HR, Sales with granular permissions
- **Secure Authentication**: Password hashing, session management
- **Access Control**: Role-based feature restrictions
- **Security Protection**: SQL injection and XSS prevention

### 📊 Dashboard & Analytics
- **Real-time Overview**: Key metrics and system health
- **Low Stock Alerts**: Automated inventory monitoring
- **Project Analytics**: Revenue tracking and status distribution
- **Quick Actions**: Fast access to common tasks

### 📦 Advanced Inventory Management
- **Complete CRUD Operations**: Add, edit, delete, view inventory items
- **Stock Tracking**: Real-time quantity monitoring with movement history
- **Multi-level Organization**: Categories, brands, suppliers, specifications
- **Image Management**: Product photos with automatic fallback
- **Stock Movements**: Complete audit trail of all inventory changes
- **Low Stock Alerts**: Configurable minimum stock thresholds
- **CSV Export**: Bulk data export capabilities
- **Advanced Filtering**: Category, brand, stock status, and serialized item filters
- **Quick Stock Adjustment**: Streamlined + and - buttons for rapid stock changes
- **Serial Number Management**: Unique serial number generation and tracking
- **Serial Status Tracking**: Available, reserved, sold, damaged, returned statuses
- **Automatic Serial Generation**: Auto-generate serials when stock increases
- **Serial Number Validation**: Prevent duplicate serial numbers
- **Serialized Item Filter**: Quick filter to view only items with serial number tracking

### 🏗️ Solar Project Management
- **Project Lifecycle**: From quote to completion tracking
- **Dynamic Pricing**: Automatic calculations with discounts
- **Inventory Integration**: Real-time availability checking
- **Enhanced Professional Quotes**: Printable project proposals with visual discount indicators
- **Cross-System Print Consistency**: Unified discount display across quotations and POS receipts
- **Status Tracking**: Quote → Under Review → Approved → Completed workflow
- **Automatic Conversion**: Approved quotes automatically become projects
- **Battery Backup Planning**: Capacity planning and specifications
- **Installation Status**: Track various installation phases
- **Inventory Allocation**: Automatic stock deduction on approval

### 🛒 Point of Sale (POS) System
- **Retail Sales Processing**: Complete transaction management
- **Multiple Payment Methods**: Cash, Credit Card, Bank Transfer, Check
- **Professional Receipt Generation**: Clean print layouts with optimized margins
- **Enhanced Discount Display**: Visual indicators showing original price, discount percentage, and final price
- **Sales History**: Complete transaction tracking
- **Real-time Inventory**: Automatic stock updates
- **Customer Management**: Optional customer information capture
- **Discount Support**: Item-level and percentage discounts with visual feedback
- **Quotation Import**: Import items from quotations directly to POS sales
- **Customer Information Transfer**: Automatic customer name and phone import from quotations
- **Serial Number Selection**: Select specific serial numbers for serialized items
- **Serial Number Tracking**: Track which serials are sold in each transaction

### 🏢 Business Management
- **Supplier Management**: Contact details and relationship tracking
- **User Administration**: Staff accounts with role assignments
- **Category Management**: Flexible product categorization
- **Reporting Suite**: Comprehensive business analytics

### 💼 Human Resources & Payroll
- **Employee Management**: Complete employee records and profiles
- **Attendance Tracking**: Daily time in/out with overtime calculation
- **Payroll Processing**: Automated salary calculations with deductions
- **Multiple Salary Packages**: Support for 1500, 2500, 3500 salary tiers
- **Deduction Management**: Cash advances, uniforms, tools, late penalties
- **Payroll Reports**: Detailed payslips and earnings statements
- **Leave Management**: Track leaves and balance calculations
- **Custom Deductions**: Dynamic deduction fields for flexible payroll management
- **Manual Employee Codes**: User-defined employee codes with duplicate validation
- **Real-time Validation**: Instant employee code availability checking

### 💳 Payment & Finance
- **Installment System**: Flexible payment plans for solar projects
- **Payment Tracking**: Monitor installment schedules and payments
- **Multiple Payment Methods**: Cash, check, bank transfer, digital wallets
- **Late Fee Management**: Automated late fee calculations
- **Revenue Analysis**: Comprehensive financial reporting and analytics
- **Payment History**: Complete transaction audit trails

### 📱 User Experience
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Modern UI**: Clean interface with Tailwind CSS
- **Streamlined Interface**: Simplified inventory view with focused action buttons
- **Quick Stock Adjustment**: Rapid + and - buttons for efficient stock management
- **Professional Print Support**: Enhanced document generation with clean layouts
- **Visual Discount Indicators**: Clear discount display across all print formats
- **Advanced Filtering**: Intuitive filter system with visual indicators
- **Fast Performance**: Optimized database queries
- **Intuitive Navigation**: User-friendly menu system

## 🏗️ System Architecture

### 📂 Directory Structure
```
4nsolarSystem/
├── assets/                    # Static resources
│   ├── css/                  # Stylesheets
│   └── js/                   # JavaScript files
├── images/                   # System images
│   ├── products/            # Product images
│   ├── logo.png             # Company logo
│   └── no-image.svg         # Default image fallback
├── includes/                # Core system files
│   ├── auth.php             # Authentication functions
│   ├── config.php           # Database configuration
│   ├── inventory.php        # Inventory management
│   ├── projects.php         # Solar project functions
│   ├── suppliers.php        # Supplier & category management
│   ├── pos.php              # Point of sale functions
│   ├── payroll.php          # Payroll and HR functions
│   ├── installments.php     # Installment payment system
│   ├── header.php           # Common page header
│   └── footer.php           # Common page footer
├── database/                # Database scripts
│   ├── 4nsolar_inventory.sql   # Main database schema
│   ├── payroll_system.sql      # Payroll system tables
│   ├── installment_system.sql  # Installment payment tables
│   └── add_battery_backup_field.sql # Battery capacity feature
├── *.php                    # Main application files
├── payroll.php              # Payroll management interface
├── employee_attendance.php  # Attendance tracking
├── revenue_analysis.php     # Financial analytics
├── setup_payroll_tables.php # Payroll system installer
├── setup_installment_system.php # Installment system installer
├── QUOTATION_TO_PROJECT_GUIDE.md # Feature documentation
├── UNDER_REVIEW_STATUS_SETUP.md # Status workflow guide
├── WIRE_MANAGEMENT_GUIDE.md     # Wire inventory guide
└── README.md               # This file
```

### 🗄️ Database Schema

#### Core Tables
- **`users`** - System users with role-based access
- **`suppliers`** - Equipment suppliers and contact information
- **`categories`** - Product categories for organization
- **`inventory_items`** - Solar equipment and products with serial number settings
- **`stock_movements`** - Complete inventory audit trail
- **`inventory_serials`** - Serial number tracking and status management

#### Project Management
- **`solar_projects`** - Customer projects and quotes
- **`solar_project_items`** - Items included in each project

#### Point of Sale
- **`pos_sales`** - Retail transaction records with customer information
- **`pos_sale_items`** - Individual items in each sale with serial number tracking

#### Human Resources & Payroll
- **`employees`** - Employee profiles and details with manual employee codes
- **`employee_attendance`** - Daily attendance records
- **`payroll`** - Payroll calculations and records
- **`payroll_deductions`** - Custom deduction tracking

#### Payment & Finance
- **`installment_plans`** - Payment plan configurations
- **`installment_payments`** - Individual payment records
- **`installment_transactions`** - Payment transaction history

#### Enhanced Features
- **`quote_solar_details`** - Battery backup capacity and installation status
- **`quotations`** - Enhanced with "under_review" status workflow

### 🔧 Core Functions

#### Authentication System (`includes/auth.php`)
- `login()` - User authentication
- `logout()` - Session termination
- `createUser()` - New user registration
- `checkRole()` - Permission validation

#### Inventory Management (`includes/inventory.php`)
- `getInventoryItems()` - Retrieve inventory with filters
- `addInventoryItem()` - Create new inventory items
- `updateStock()` - Stock level management with automatic serial generation
- `getStockMovements()` - Movement history
- `getLowStockItems()` - Alert system
- `getSerializedItems()` - Filter items with serial number tracking
- `generateSerialNumbers()` - Create unique serial numbers for items
- `getAvailableSerials()` - Retrieve available serial numbers
- `reserveSpecificSerialsForQuote()` - Reserve serials for quotations
- `releaseSpecificSerials()` - Release reserved serial numbers

#### Project Management (`includes/projects.php`)
- `createSolarProject()` - New project creation
- `addProjectItem()` - Add items to projects
- `updateProjectTotals()` - Calculate pricing
- `deductProjectInventory()` - Inventory allocation
- `checkProjectInventoryAvailability()` - Stock validation

#### POS System (`includes/pos.php`)
- `createPOSSale()` - New sale transaction
- `addPOSSaleItemWithSerials()` - Add items to sale with serial number selection
- `completePOSSaleWithSerials()` - Process payment with serial tracking
- `generateReceiptNumber()` - Unique receipt IDs
- `getPOSStats()` - Sales analytics
- `importQuotationToPOS()` - Import quotation items to POS sale
- `getQuotationForPOS()` - Retrieve quotation data for import

#### Payroll System (`includes/payroll.php`)
- `addEmployee()` - Create employee records with manual employee codes
- `addAttendance()` - Record daily attendance
- `calculatePayroll()` - Process salary calculations with custom deductions
- `getEmployeeAttendance()` - Retrieve attendance history
- `getPayrollDeductions()` - Retrieve custom deduction records

#### Installment System (`includes/installments.php`)
- `createInstallmentPlan()` - Setup payment plans
- `recordInstallmentPayment()` - Process payments
- `calculateLateFees()` - Late payment penalties
- `getInstallmentSchedule()` - Payment schedules

## 🛠️ Configuration & Customization

### Environment Configuration
```php
// Database settings in includes/config.php
$host = 'localhost';
$dbname = '4nsolar';
$username = 'root';
$password = '';
```

### User Roles & Permissions
| Role | Dashboard | Inventory | Projects | POS | Payroll | Installments | Users | Suppliers |
|------|-----------|-----------|----------|-----|---------|-------------|-------|-----------|
| **Admin** | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full |
| **HR** | ✅ View | ✅ Full | ✅ Full | ✅ View | ✅ Full | ✅ Full | ✅ Full | ✅ Full |
| **Sales** | ✅ View | 👁️ View | ✅ Create/Edit | ✅ Full | ❌ None | ✅ View | ❌ None | 👁️ View |

### Print & Document Features
- **Professional Print Layouts**: Clean, margin-optimized print formats
- **Visual Discount Indicators**: Clear display of discounts across all documents
- **Cross-System Consistency**: Unified discount display in POS receipts and quotations
- **Browser Print Optimization**: Removed localhost URLs and headers for professional output
- **Enhanced Receipt Design**: Improved POS receipt layout with proper spacing
- **Quotation Print Enhancement**: Visual discount indicators in project quotes

### Customization Options
- **Company Branding**: Update logos and company information
- **Product Categories**: Add industry-specific categories
- **User Roles**: Extend permission system
- **Report Templates**: Customize quote and receipt layouts
- **UI Themes**: Modify Tailwind CSS classes
- **Receipt Customization**: Adjust POS receipt format

## 🧪 Testing & Quality Assurance

### Comprehensive Test Suite
Run the complete system test: `http://localhost/4nsolarSystem/system_comprehensive_test.php`

#### Test Coverage
- ✅ **Database Connection**: Connectivity and table validation
- ✅ **Authentication System**: Login, logout, session management
- ✅ **Inventory Management**: CRUD operations, stock tracking
- ✅ **Project Management**: Creation, updates, status changes
- ✅ **Inventory-Project Integration**: Stock allocation and restoration
- ✅ **Data Validation**: Input sanitization and error handling
- ✅ **Stock Movements**: Audit trail functionality
- ✅ **Reporting Functions**: Statistics and currency formatting

#### System Health Monitoring
- 🟢 **EXCELLENT** (95%+): System functioning optimally
- 🟡 **GOOD** (85-94%): Minor issues present
- 🟠 **FAIR** (70-84%): Some issues need attention
- 🔴 **POOR** (<70%): Critical issues require immediate attention

### Test Data Cleanup
The test suite automatically:
- Creates temporary test data
- Validates all system functions
- Cleans up test data completely
- Provides detailed success/failure reports

## 📈 Business Intelligence & Reporting

### Dashboard Analytics
- **Real-time Metrics**: Total inventory value, project counts
- **Revenue Tracking**: Monthly and project-based revenue
- **Stock Alerts**: Low inventory notifications
- **Performance Indicators**: System health and activity

### Export Capabilities
- **Inventory Reports**: CSV export of all inventory data
- **Project Analytics**: Status distribution and revenue analysis
- **POS Reports**: Sales history and transaction details
- **Stock Movements**: Complete audit trail exports

## 🔄 Recent Updates & Changelog

### Version 3.3 (Latest - December 2024)
- ✅ **Serialized Item Filter**: Quick filter to view only items with serial number tracking
- ✅ **Streamlined Stock Management**: Removed Update Stock button from view page for cleaner interface
- ✅ **Enhanced Quick Stock Adjustment**: Improved + and - buttons for rapid stock changes
- ✅ **Advanced Inventory Filtering**: Category, brand, stock status, and serialized item filters
- ✅ **Improved User Experience**: Cleaner inventory view with focused action buttons

### Version 3.2 (December 2024)
- ✅ **Serial Number Management System**: Complete serial number tracking and management
- ✅ **Quotation Import to POS**: Import quotation items directly to POS sales
- ✅ **Customer Information Transfer**: Automatic customer data import from quotations
- ✅ **Serial Number Selection**: Select specific serials for serialized items in POS
- ✅ **Custom Payroll Deductions**: Dynamic deduction fields for flexible payroll
- ✅ **Manual Employee Codes**: User-defined employee codes with validation
- ✅ **Enhanced Inventory Tracking**: Serial number status management (available, reserved, sold, damaged, returned)
- ✅ **Automatic Serial Generation**: Auto-generate serials when stock increases
- ✅ **Duplicate Prevention**: Robust serial number duplicate prevention system
- ✅ **Comprehensive Testing**: Complete system validation and error detection

### Version 3.1 (December 2024)
- ✅ **Enhanced Print Functionality**: Improved quotation and receipt printing
- ✅ **Discount Display System**: Visual discount indicators in all print formats
- ✅ **Professional Receipt Layout**: Clean print layouts with proper margins
- ✅ **Cross-System Consistency**: Unified discount display across POS and quotations
- ✅ **Print Optimization**: Removed browser headers/footers for professional output

### Version 3.0 (September 2025)
- ✅ **Payroll Management System**: Complete HR and payroll functionality
- ✅ **Employee Attendance Tracking**: Time tracking with overtime calculations
- ✅ **Installment Payment System**: Flexible payment plans for projects
- ✅ **Battery Backup Planning**: Solar system capacity planning features
- ✅ **Under Review Status**: Enhanced quotation workflow management
- ✅ **Automatic Quote-to-Project**: Seamless conversion workflow
- ✅ **Revenue Analysis Tools**: Advanced financial reporting

### Version 2.1
- ✅ **Comprehensive Testing Suite**: Complete system validation
- ✅ **Enhanced Error Handling**: Improved user feedback
- ✅ **Performance Optimization**: Faster database queries
- ✅ **Security Improvements**: Enhanced input validation

### Version 2.0
- ✅ **Point of Sale (POS) System**: Complete retail functionality
- ✅ **Receipt Generation**: Professional printed receipts
- ✅ **Multiple Payment Methods**: Cash, card, transfer, check
- ✅ **Enhanced Database Schema**: POS tables and relationships

### Version 1.5
- ✅ **Image Management**: Product photos with SVG fallback
- ✅ **Stock Movement Tracking**: Complete inventory audit trail
- ✅ **Role-based Permissions**: Enhanced security model
- ✅ **Responsive Design**: Mobile-friendly interface

### 🔢 Serial Number Management System
- **Unique Serial Generation**: Automatic generation with customizable prefixes and formats
- **Status Tracking**: Available, reserved, sold, damaged, returned statuses
- **POS Integration**: Select specific serials when selling serialized items
- **Quotation Integration**: Reserve serials when creating quotations
- **Inventory Integration**: Auto-generate serials when stock increases
- **Duplicate Prevention**: Robust system to prevent duplicate serial numbers
- **Audit Trail**: Complete tracking of serial number movements and status changes

### 🔄 Quotation to POS Integration
- **Seamless Import**: Import quotation items directly to POS sales
- **Customer Data Transfer**: Automatic import of customer name and phone number
- **Price Preservation**: Maintain original quotation prices in POS
- **Stock Validation**: Check availability before import
- **Error Handling**: Graceful handling of out-of-stock items
- **Success Feedback**: Clear confirmation of imported items and customer data

### 🔍 Advanced Inventory Filtering System
- **Multi-Level Filtering**: Category, brand, stock status, and serialized item filters
- **Serialized Item Filter**: Quick access to items with serial number tracking
- **Combined Filtering**: Use multiple filters simultaneously for precise results
- **Visual Filter Indicators**: Clear badges showing active filters
- **Quick Filter Dropdown**: Easy access to common filter combinations
- **Filter Persistence**: Maintains filter state during navigation
- **Export Filtered Results**: Export only filtered inventory data

### Planned Features
- 🔮 **Wire Management Module**: Cable and wire inventory (in development)
- 🔮 **Advanced Reporting Dashboard**: Custom report builder
- 🔮 **API Integration**: Third-party system connections
- 🔮 **Mobile App**: Dedicated mobile application
- 🔮 **Automated Backups**: Database backup scheduling

## 🆘 Support & Troubleshooting

### Common Issues
1. **Database Connection Failed**
   - Verify XAMPP MySQL is running
   - Check database credentials in `includes/config.php`
   - Ensure database `4nsolar` exists

2. **Permission Denied Errors**
   - Check file permissions for `images/products/` folder
   - Ensure PHP has write access to required directories

3. **Login Issues**
   - Verify user exists in database
   - Reset password using admin account
   - Check session configuration

### System Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher
- **Memory**: Minimum 512MB PHP memory limit
- **Storage**: 100MB+ available disk space

### Development & Customization
For custom development, modification requests, or technical support:
- Review code documentation in each PHP file
- Use the comprehensive test suite for validation
- Follow existing code patterns and security practices
- Test all changes with the system test suite

---

## 📄 License & Copyright

**Proprietary Software** - © 2025 4nSolar ELECTRICZ  
All rights reserved. This software is licensed for use by 4nSolar ELECTRICZ and authorized personnel only.

### Contact Information
- **System**: 4nSolar Management System
- **Version**: 3.3
- **Last Updated**: December 2024
- **Latest Features**: Serialized Item Filter, Streamlined Stock Management, Enhanced Quick Stock Adjustment, Advanced Inventory Filtering
- **Previous Features**: Serial Number Management, Quotation Import to POS, Customer Information Transfer, Custom Payroll Deductions
- **Test Suite**: Comprehensive validation included with 94.44% success rate

---

*This system is actively maintained and continuously improved based on business requirements and user feedback.*
