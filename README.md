# 4nSolar Management System

[![System Status](https://img.shields.io/badge/status-active-brightgreen.svg)]()
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)]()
[![Database](https://img.shields.io/badge/database-mysql-orange.svg)]()
[![License](https://img.shields.io/badge/license-proprietary-red.svg)]()

A comprehensive solar equipment inventory and project management system designed for 4nSolar ELECTRICZ. This system provides complete business management capabilities including inventory tracking, project quotations, point-of-sale functionality, and comprehensive reporting.

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

4. **System Access**
   - URL: `http://localhost/4nsolarSystem`
   - **Default Admin Login:**
     - Username: `admin`
     - Password: `admin123`
   
5. **System Testing**
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

### 🏗️ Solar Project Management
- **Project Lifecycle**: From quote to completion tracking
- **Dynamic Pricing**: Automatic calculations with discounts
- **Inventory Integration**: Real-time availability checking
- **Professional Quotes**: Printable project proposals
- **Status Tracking**: Quote → Approved → Completed workflow
- **Inventory Allocation**: Automatic stock deduction on approval

### 🛒 Point of Sale (POS) System
- **Retail Sales Processing**: Complete transaction management
- **Multiple Payment Methods**: Cash, Credit Card, Bank Transfer, Check
- **Receipt Generation**: Professional printed receipts
- **Sales History**: Complete transaction tracking
- **Real-time Inventory**: Automatic stock updates
- **Customer Management**: Optional customer information capture
- **Discount Support**: Item-level and percentage discounts

### 🏢 Business Management
- **Supplier Management**: Contact details and relationship tracking
- **User Administration**: Staff accounts with role assignments
- **Category Management**: Flexible product categorization
- **Reporting Suite**: Comprehensive business analytics

### 📱 User Experience
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Modern UI**: Clean interface with Tailwind CSS
- **Print Support**: Professional document generation
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
│   ├── header.php           # Common page header
│   └── footer.php           # Common page footer
├── pages/                   # Page components
├── *.php                    # Main application files
├── database.sql             # Database schema
├── pos_tables.sql           # POS system tables
├── system_comprehensive_test.php  # Test suite
└── README.md               # This file
```

### 🗄️ Database Schema

#### Core Tables
- **`users`** - System users with role-based access
- **`suppliers`** - Equipment suppliers and contact information
- **`categories`** - Product categories for organization
- **`inventory_items`** - Solar equipment and products
- **`stock_movements`** - Complete inventory audit trail

#### Project Management
- **`solar_projects`** - Customer projects and quotes
- **`solar_project_items`** - Items included in each project

#### Point of Sale
- **`pos_sales`** - Retail transaction records
- **`pos_sale_items`** - Individual items in each sale

### 🔧 Core Functions

#### Authentication System (`includes/auth.php`)
- `login()` - User authentication
- `logout()` - Session termination
- `createUser()` - New user registration
- `checkRole()` - Permission validation

#### Inventory Management (`includes/inventory.php`)
- `getInventoryItems()` - Retrieve inventory with filters
- `addInventoryItem()` - Create new inventory items
- `updateStock()` - Stock level management
- `getStockMovements()` - Movement history
- `getLowStockItems()` - Alert system

#### Project Management (`includes/projects.php`)
- `createSolarProject()` - New project creation
- `addProjectItem()` - Add items to projects
- `updateProjectTotals()` - Calculate pricing
- `deductProjectInventory()` - Inventory allocation
- `checkProjectInventoryAvailability()` - Stock validation

#### POS System (`includes/pos.php`)
- `createPOSSale()` - New sale transaction
- `addPOSSaleItem()` - Add items to sale
- `completePOSSale()` - Process payment
- `generateReceiptNumber()` - Unique receipt IDs
- `getPOSStats()` - Sales analytics

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
| Role | Dashboard | Inventory | Projects | POS | Users | Suppliers |
|------|-----------|-----------|----------|-----|-------|-----------|
| **Admin** | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full | ✅ Full |
| **HR** | ✅ View | ✅ Full | ✅ Full | ✅ View | ✅ Full | ✅ Full |
| **Sales** | ✅ View | 👁️ View | ✅ Create/Edit | ✅ Full | ❌ None | 👁️ View |

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

### Version 2.1 (Latest)
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

### Planned Features
- 🔮 **Wire Management Module**: Cable and wire inventory
- 🔮 **Advanced Reporting**: Custom report builder
- 🔮 **API Integration**: Third-party system connections
- 🔮 **Mobile App**: Dedicated mobile application

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
- **Version**: 2.1
- **Last Updated**: September 2025
- **Test Suite**: Comprehensive validation included

---

*This system is actively maintained and continuously improved based on business requirements and user feedback.*
