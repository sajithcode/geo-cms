# 🎓 Student Issue Reporting System - Complete Guide

## Overview

The Student Issue Reporting System allows students to report technical issues with lab computers and equipment. This document outlines the complete implementation and features.

---

## ✅ Implemented Features

### 1. **Report Issue Form**

Students can report issues through a comprehensive modal form with the following fields:

#### Required Fields:

- **Computer Serial Number**

  - Text input with autocomplete/datalist functionality
  - Example format: `LAB01-PC08`
  - Fetches registered computers from the database
  - Shows lab name and computer name as hints

- **Lab Selection**

  - Dropdown menu showing available labs (Lab 01 - Lab 04)
  - Dynamically populated from the `labs` table
  - Required field validation

- **Issue Category**

  - Dropdown with predefined categories:
    - 🖥️ Hardware
    - 💾 Software
    - 🌐 Network
    - 📋 Other

- **Description**
  - Multiline textarea (5 rows)
  - Detailed information about the issue
  - Placeholder text for guidance
  - Character validation for minimum content

#### Optional Field:

- **File Upload**
  - Supports image files (JPG, PNG) and PDF
  - Maximum file size: 5MB
  - For uploading screenshots or photos of the issue

### 2. **Reports Table**

Students can view all their submitted reports in a comprehensive table:

#### Table Columns:

1. **Report ID** - Unique identifier (bold text)
2. **Computer Serial No.** - The affected computer
3. **Lab** - Lab location name
4. **Category** - Issue type badge
5. **Status** - Color-coded status badge:
   - 🟡 **Pending** (Yellow)
   - 🟠 **In Progress** (Orange)
   - 🟢 **Resolved** (Green)
6. **Reported Date** - Formatted as DD/MM/YYYY HH:mm
7. **Actions** - View button to see full details

### 3. **Statistics Dashboard**

Four stat cards showing:

- 📋 **Total Reports** - All reports submitted
- 🟡 **Pending** - Awaiting action
- 🟠 **In Progress** - Being worked on
- 🟢 **Resolved** - Completed issues

### 4. **Filtering & Search**

- **Status Filter**: Dropdown to filter by status (All/Pending/In Progress/Resolved)
- **Search Box**: Real-time search across all report data
- Dynamic table filtering without page reload

### 5. **Report Details Modal**

- Click "View" on any report to see full details
- Loads data asynchronously via AJAX
- Shows all information including:
  - Full description
  - Uploaded files (if any)
  - Assignment information
  - Status history
  - Remarks from staff/technicians

### 6. **Empty State**

When students have no reports:

- Friendly empty state message
- Large icon (🚨)
- Helpful text guiding them to submit first report
- Quick action button to open report modal

---

## 🔒 Security Features

1. **Authentication Check**

   - Requires user login
   - Validates user role = 'student'
   - Redirects unauthorized users

2. **Data Isolation**

   - Students can ONLY view their own reports
   - Query filtered by `reported_by = user_id`
   - Cannot access other students' data

3. **CSRF Protection**

   - CSRF token included in all forms
   - Token validation on server-side

4. **File Upload Security**
   - File type validation
   - Size limit enforcement
   - Secure file naming

---

## 📊 Database Schema

### Tables Used:

#### 1. `issue_reports`

```sql
- id (Primary Key)
- report_id (Unique identifier)
- computer_serial_no (VARCHAR)
- lab_id (Foreign Key to labs)
- issue_category (ENUM)
- description (TEXT)
- file_path (VARCHAR)
- status (ENUM: pending/in_progress/resolved)
- reported_by (Foreign Key to users)
- assigned_to (Foreign Key to users)
- resolved_by (Foreign Key to users)
- remarks (TEXT)
- reported_date (DATETIME)
- updated_date (DATETIME)
- resolved_date (DATETIME)
```

#### 2. `computers`

```sql
- id (Primary Key)
- serial_no (UNIQUE)
- lab_id (Foreign Key to labs)
- computer_name (VARCHAR)
- status (ENUM: active/maintenance/inactive)
- created_at (DATETIME)
```

#### 3. `labs`

```sql
- id (Primary Key)
- name (VARCHAR)
- Other lab-related fields
```

---

## 🎨 User Interface

### Design Elements:

- **Responsive Layout**: Works on desktop and mobile
- **Clean Dashboard**: Card-based statistics
- **Modal Forms**: Non-intrusive form submission
- **Color-Coded Status**: Easy visual identification
- **Icon Usage**: Emojis for quick recognition
- **Loading States**: Disabled buttons during submission
- **Success/Error Notifications**: User feedback for actions

### Accessibility:

- Proper form labels
- Required field indicators (\*)
- Helper text for complex fields
- Keyboard navigation support
- Screen reader friendly

---

## 🔄 Workflow

### For Students:

1. **Login** to the system as a student
2. **Navigate** to Issues section from sidebar
3. **View Dashboard** showing current statistics
4. **Click "Report Issue"** button
5. **Fill Form**:
   - Enter or select computer serial number
   - Choose lab from dropdown
   - Select issue category
   - Describe the problem in detail
   - Optionally upload screenshot/photo
6. **Submit Report**
7. **View Confirmation** with Report ID
8. **Track Status** in reports table
9. **View Details** anytime by clicking on report

### Report Lifecycle:

1. **Pending** 🟡 - Submitted, awaiting assignment
2. **In Progress** 🟠 - Assigned to technician, being worked on
3. **Resolved** 🟢 - Issue fixed and closed

---

## 📁 File Structure

```
geo-cms/
├── issues/
│   ├── student-dashboard.php      # Main student interface
│   ├── php/
│   │   ├── submit_issue.php       # Form submission handler
│   │   ├── get_issue_details.php  # Fetch report details
│   │   └── ...other API endpoints
│   └── ...
├── css/
│   ├── style.css                  # Global styles
│   ├── dashboard.css              # Dashboard-specific styles
│   └── inventory.css              # Form and table styles
├── js/
│   └── script.js                  # Modal and utility functions
├── includes/
│   ├── sidebar.php                # Navigation sidebar
│   └── header.php                 # Page header
└── php/
    └── config.php                 # Database and utilities
```

---

## 🚀 Features Summary

### ✅ What's Working:

1. ✅ Computer Serial Number input with autocomplete
2. ✅ Lab selection dropdown (Lab 01-04)
3. ✅ Issue category selection (Hardware/Software/Network/Other)
4. ✅ Multi-line description field
5. ✅ Optional file upload (images/PDF)
6. ✅ Submit button with loading state
7. ✅ Reports table with all required columns
8. ✅ Status color coding (🟡 🟠 🟢)
9. ✅ Filter by status
10. ✅ Search functionality
11. ✅ View report details
12. ✅ Empty state message
13. ✅ Statistics cards
14. ✅ Data isolation (students see only their reports)
15. ✅ CSRF protection
16. ✅ Responsive design

---

## 🔧 Technical Details

### Frontend:

- **HTML5**: Semantic markup
- **CSS3**: Modern styling with flexbox/grid
- **JavaScript (ES6+)**: Async/await, Fetch API
- **No external dependencies**: Pure vanilla JS

### Backend:

- **PHP 7.4+**: Server-side processing
- **PDO**: Secure database interactions
- **Prepared Statements**: SQL injection prevention
- **Session Management**: User authentication

### Database:

- **MySQL/MariaDB**: Relational database
- **InnoDB Engine**: Transaction support
- **Foreign Keys**: Referential integrity
- **Indexes**: Optimized queries

---

## 📝 Notes

### Student Limitations:

- Students can **ONLY** create and view their own reports
- Students **CANNOT** assign technicians
- Students **CANNOT** change status
- Students **CANNOT** add remarks (staff/technician only)
- Students **CANNOT** delete reports
- Students **CAN** view all details of their reports

### Data Validation:

- All required fields validated client-side
- Server-side validation for security
- File type and size restrictions
- Serial number format validation

### Error Handling:

- Database errors logged
- User-friendly error messages
- Graceful fallbacks for missing data
- Validation feedback on forms

---

## 🎯 Success Criteria - All Met! ✅

✅ Computer Serial Number field with autocomplete
✅ Lab Selection dropdown (Lab 01-04)
✅ Issue Category selection
✅ Description field (multiline)
✅ File Upload (optional)
✅ Submit button functionality
✅ Table showing: Report ID, Computer Serial No., Lab, Status, Reported Date
✅ Status colors: 🟡 Pending | 🟠 In Progress | 🟢 Resolved
✅ Students can only view their own reports
✅ Clean, production-ready code (no debug output)

---

## 📞 Support

For technical issues or questions:

1. Check database schema is properly installed
2. Verify `computers` and `labs` tables have data
3. Ensure user has 'student' role
4. Check error logs in browser console and server logs

---

**Last Updated**: October 15, 2025
**Status**: Production Ready ✅
