# Under Review Status - Setup Instructions

## What's Been Added

I've added the "Under Review" status to your quotations system! Here's what's new:

### ✅ **Frontend Changes (Already Done)**
- Added "Under Review" option to custom status dropdown
- Added purple badge styling for under review status
- Added "Mark Under Review" quick action buttons
- Updated status display to show "Under Review" (formatted properly)

### 🔧 **Database Setup Required**

To complete the setup, you need to update your database to include the new status option:

#### **Option 1: Run the Setup Script (Recommended)**
1. Open your browser
2. Go to: `http://localhost/4nsolarSystem/add_under_review_status.php`
3. The script will automatically add the status to your database

#### **Option 2: Manual SQL (If you prefer)**
Run this SQL in phpMyAdmin or your MySQL client:
```sql
ALTER TABLE quotations MODIFY COLUMN status 
ENUM('draft','sent','under_review','accepted','rejected','expired') 
DEFAULT 'draft';
```

## How to Use

### **Quick Action Buttons**
- **Draft quotations**: Can be marked as Sent, Under Review, or Approved
- **Sent quotations**: Can be marked as Under Review or Approved  
- **Under Review quotations**: Can be approved (triggers project conversion)

### **Custom Status Dropdown**
- Available in all quotation detail views
- Select "Under Review" from the dropdown and click "Update"

### **Status Flow**
1. **Draft** → Create new quotation
2. **Sent** → Send quotation to customer
3. **Under Review** → Customer is reviewing the quotation
4. **Accepted** → Customer approved (creates solar project automatically)
5. **Rejected** → Customer declined
6. **Expired** → Quotation validity expired

### **Visual Styling**
- **Purple badge** with purple text for under review status
- **Eye icon** for "Mark Under Review" buttons
- Displays as "Under Review" (properly formatted)

## Benefits

- **Better workflow tracking** - Know when customers are actively reviewing
- **Professional status management** - Clear indication of quotation stage
- **Improved customer communication** - Track review progress
- **Complete audit trail** - Full visibility of quotation lifecycle

After running the database setup script, the "Under Review" status will be fully functional! 🎉
