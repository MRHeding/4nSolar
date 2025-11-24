# Invoice System Setup Guide

## Overview
The invoice system allows you to create professional invoices that can be generated from quotations or created manually with items from inventory.

## Setup Instructions

### 1. Database Setup
Run the SQL file to create the necessary tables:

```sql
-- Execute this file in phpMyAdmin or your MySQL client
database/add_invoice_system.sql
```

This will create:
- `invoices` table - stores invoice information
- `invoice_items` table - stores invoice line items

### 2. Features

#### Creating Invoices
- **From Quotation**: Select a quotation and automatically load customer details and items
- **Manual Entry**: Create invoices manually by adding items from inventory or entering custom items

#### Invoice Features
- Bill To / Ship To addresses
- Invoice date and due date
- P.O. Number tracking
- Tax calculation (configurable tax rate)
- Terms & Conditions
- Bank details
- Professional print layout matching the design specification

#### Item Management
- Add items from inventory with automatic price and description
- Add custom items manually
- Real-time calculation of subtotals, tax, and total
- Quantity and unit price fields

### 3. Access
- Navigate to **Invoices** from the sidebar menu
- Click "Create New Invoice" to start
- Select a quotation (optional) or create manually
- Fill in billing/shipping information
- Add items from inventory or enter manually
- Set tax rate and additional information
- Save and print

### 4. Print Invoice
- Click the print button on any invoice
- Professional layout with:
  - Company logo and information
  - Bill To / Ship To sections
  - Itemized list with quantities and prices
  - Tax calculation
  - Terms & Conditions
  - Bank details
  - Signature area

## File Structure
- `invoices.php` - Main invoice management page
- `print_invoice.php` - Print-friendly invoice view
- `includes/inventory.php` - Invoice functions (added)
- `database/add_invoice_system.sql` - Database schema

## Notes
- Invoice numbers are auto-generated (format: INT-XXX)
- Invoices can be linked to quotations for reference
- Items can be pulled from inventory or entered manually
- Print layout is optimized for A4 paper size

