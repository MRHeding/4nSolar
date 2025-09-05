# Quotation to Solar Project Conversion Feature

## Overview
This feature automatically converts approved quotations into solar projects, streamlining your workflow from quote to project execution.

## How It Works

### 1. Create a Quotation
- Go to the Quotations page from the sidebar
- Click "Create New Quotation"
- Fill in customer details and proposal name
- Add inventory items to the quotation

### 2. Approve the Quotation
- Open the quotation you want to convert
- Click the "Approve Quote" button (green button)
- The status will change to "accepted"
- **Automatically**: A solar project will be created with the same details

### 3. What Happens During Conversion

#### Project Creation
- A new solar project is created with:
  - Project name based on the quotation's proposal name
  - Customer information from the quotation
  - System size calculated from solar panels in the quote
  - All quote items copied as project items
  - Status set to "approved" (ready for execution)

#### Data Mapping
- **Customer Name**: Copied from quotation
- **Customer Phone**: Copied from quotation
- **Project Name**: Uses proposal name or creates one from quote number
- **System Size**: Automatically calculated from solar panel wattage
- **Items**: All quotation items with quantities, prices, and discounts

### 4. Find the Created Project
- Go to Projects page from the sidebar
- Look for the newly created project
- The project will have a remark: "Converted from quotation [QUOTE_NUMBER]"

## Database Enhancements (Optional)
To fully link quotations and projects, execute this SQL in phpMyAdmin:

```sql
-- Add project_id column to quotations table
ALTER TABLE quotations ADD COLUMN project_id INT NULL;
ALTER TABLE quotations ADD INDEX idx_project_id (project_id);

-- Add quote_id column to solar_projects table  
ALTER TABLE solar_projects ADD COLUMN quote_id INT NULL;
ALTER TABLE solar_projects ADD INDEX idx_quote_id (quote_id);
```

## Benefits
1. **Streamlined Workflow**: No need to manually re-enter data
2. **Accuracy**: Eliminates transcription errors
3. **Time Saving**: Instant conversion with one click
4. **Audit Trail**: Clear connection between quotes and projects
5. **Automatic Calculations**: System size automatically calculated

## Status Options
- **Draft**: Initial quotation state
- **Sent**: Quotation has been sent to customer
- **Under Review**: Customer is reviewing the quotation
- **Accepted**: ✅ **Triggers project conversion**
- **Rejected**: Customer declined the quotation
- **Expired**: Quotation validity period has passed

## Notes
- The feature works even if the database columns are not yet added
- Project conversion happens automatically upon approval
- Original quotation remains unchanged and linked to the project
- Solar panel wattage is automatically calculated for system sizing
