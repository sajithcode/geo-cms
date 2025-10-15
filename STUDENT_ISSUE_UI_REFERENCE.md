# 🎓 Student Issue Reporting - UI Reference

## Quick Visual Guide

### 📋 Main Dashboard Layout

```
┌──────────────────────────────────────────────────────────────┐
│  🚨 Issue Reporting                    [➕ Report Issue]      │
│  Report technical issues with lab computers or equipment      │
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │  📋      │  │  🟡      │  │  🟠      │  │  🟢      │    │
│  │   12     │  │   5      │  │   4      │  │   3      │    │
│  │Total Reps│  │ Pending  │  │InProgress│  │ Resolved │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘    │
│                                                                │
└──────────────────────────────────────────────────────────────┘
```

---

## 🆕 Report Issue Form

When clicking "Report Issue" button, a modal appears:

```
┌─────────────────────────────────────────────────────────┐
│  Report Technical Issue                            [×]   │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Computer Serial Number *                                │
│  ┌──────────────────────────────────────────────────┐  │
│  │ LAB01-PC08                                    ▼  │  │
│  └──────────────────────────────────────────────────┘  │
│  Start typing to see available computers                │
│                                                          │
│  Lab *                                                   │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Select Lab                                    ▼  │  │
│  │ - Lab 01                                         │  │
│  │ - Lab 02                                         │  │
│  │ - Lab 03                                         │  │
│  │ - Lab 04                                         │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  Issue Category *                                        │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Select Category                               ▼  │  │
│  │ - 🖥️ Hardware                                    │  │
│  │ - 💾 Software                                    │  │
│  │ - 🌐 Network                                     │  │
│  │ - 📋 Other                                       │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  Description *                                           │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Please provide detailed information about the    │  │
│  │ issue...                                          │  │
│  │                                                   │  │
│  │                                                   │  │
│  └──────────────────────────────────────────────────┘  │
│  Be specific about the problem to help with faster      │
│  resolution                                              │
│                                                          │
│  Upload Screenshot/Photo (Optional)                      │
│  ┌──────────────────────────────────────────────────┐  │
│  │ [Choose File] No file chosen                     │  │
│  └──────────────────────────────────────────────────┘  │
│  Accepted formats: Images (JPG, PNG) or PDF. Max: 5MB   │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                      [Cancel]  [Submit Report]          │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 Reports Table

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│  My Issue Reports                                                                 │
│  ┌─────────────────┬─────────────┐                                              │
│  │ All Status   ▼  │ Search...   │                                              │
│  └─────────────────┴─────────────┘                                              │
├──────────────────────────────────────────────────────────────────────────────────┤
│ Report ID    │ Computer    │ Lab    │ Category  │ Status       │ Date      │ Action│
│              │ Serial No.  │        │           │              │           │       │
├──────────────┼─────────────┼────────┼───────────┼──────────────┼───────────┼───────┤
│ ISS-20251015 │ LAB01-PC08  │ Lab 01 │ Hardware  │ 🟡 Pending   │15/10/2025 │[View] │
│    -0123     │             │        │           │              │ 09:30     │       │
├──────────────┼─────────────┼────────┼───────────┼──────────────┼───────────┼───────┤
│ ISS-20251014 │ LAB02-PC05  │ Lab 02 │ Software  │ 🟠 In Progress│14/10/2025│[View] │
│    -0456     │             │        │           │              │ 14:20     │       │
├──────────────┼─────────────┼────────┼───────────┼──────────────┼───────────┼───────┤
│ ISS-20251012 │ LAB01-PC03  │ Lab 01 │ Network   │ 🟢 Resolved  │12/10/2025 │[View] │
│    -0789     │             │        │           │              │ 10:15     │       │
└──────────────┴─────────────┴────────┴───────────┴──────────────┴───────────┴───────┘
```

---

## 🔍 Report Details Modal

When clicking "View" on a report:

```
┌─────────────────────────────────────────────────────────┐
│  Report Details                                    [×]   │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Report ID: ISS-20251015-0123                           │
│  Status: 🟡 Pending                                      │
│                                                          │
│  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━    │
│                                                          │
│  📍 Location Information                                 │
│  Computer: LAB01-PC08                                    │
│  Lab: Lab 01                                             │
│                                                          │
│  🔖 Issue Details                                        │
│  Category: Hardware                                      │
│  Description:                                            │
│  The computer monitor is flickering and showing         │
│  vertical lines on the display. The issue started       │
│  this morning and has been getting worse.               │
│                                                          │
│  📎 Attachments                                          │
│  [📷 flickering_screen.jpg]                             │
│                                                          │
│  👤 Reporter Information                                 │
│  Reported By: John Doe (ST001)                          │
│  Reported Date: 15/10/2025 09:30 AM                     │
│                                                          │
│  🔧 Assignment                                           │
│  Assigned To: Not yet assigned                          │
│                                                          │
│  📝 Staff Remarks                                        │
│  No remarks yet                                          │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                                      [Close]             │
└─────────────────────────────────────────────────────────┘
```

---

## 📭 Empty State

When a student has no reports:

```
┌─────────────────────────────────────────────────────────┐
│                                                          │
│                         🚨                               │
│                                                          │
│                   No Reports Yet                         │
│                                                          │
│     You haven't reported any issues. Click "Report      │
│     Issue" to submit your first report.                 │
│                                                          │
│              [Report Your First Issue]                   │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

---

## 🎨 Status Color Guide

### Status Badges:

| Status      | Color  | Badge          | Meaning                             |
| ----------- | ------ | -------------- | ----------------------------------- |
| Pending     | Yellow | 🟡 Pending     | Just submitted, awaiting assignment |
| In Progress | Orange | 🟠 In Progress | Assigned to technician, being fixed |
| Resolved    | Green  | 🟢 Resolved    | Issue has been fixed and closed     |

---

## 📱 Responsive Design

### Desktop View:

- Full sidebar navigation
- Wide form modals
- Multi-column stats cards
- Full-width table

### Tablet View:

- Collapsible sidebar
- Adjusted modal width
- 2-column stats cards
- Scrollable table

### Mobile View:

- Hidden sidebar (toggle button)
- Full-screen modals
- Stacked stats cards
- Horizontally scrollable table

---

## ⌨️ Keyboard Shortcuts

| Key     | Action                                      |
| ------- | ------------------------------------------- |
| `Esc`   | Close modal                                 |
| `Tab`   | Navigate form fields                        |
| `Enter` | Submit form (when focused on submit button) |

---

## 🖱️ Interactive Elements

### Buttons:

- **Primary** (Blue): Submit Report, Report Your First Issue
- **Secondary** (Gray): Cancel, Close
- **Outline** (White/Blue): View

### Form Controls:

- **Text Input**: Computer serial number
- **Dropdown/Select**: Lab, Issue Category
- **Textarea**: Description (5 rows)
- **File Input**: Upload screenshot/photo
- **Datalist**: Autocomplete for computer serial numbers

### Filters:

- **Status Dropdown**: Filter reports by status
- **Search Input**: Real-time text search

---

## 🔔 Notifications

### Success Message:

```
┌──────────────────────────────────────────────────┐
│ ✅ Issue reported successfully!                   │
│    Report ID: ISS-20251015-0123                   │
└──────────────────────────────────────────────────┘
```

### Error Message:

```
┌──────────────────────────────────────────────────┐
│ ❌ Error: Please fill in all required fields      │
└──────────────────────────────────────────────────┘
```

### Loading State:

```
[Submitting...]  ← Button shows loading state
```

---

## 📋 Form Validation

### Client-Side:

✅ Required fields marked with \*
✅ Real-time validation feedback
✅ File type and size checking
✅ Format validation

### Server-Side:

✅ CSRF token verification
✅ Data sanitization
✅ Database constraints
✅ Business logic validation

---

## 🚀 User Flow

```
1. Student logs in
      ↓
2. Navigates to Issues section
      ↓
3. Sees dashboard with stats
      ↓
4. Clicks "Report Issue" button
      ↓
5. Modal opens with form
      ↓
6. Fills required fields:
   - Computer Serial Number
   - Lab Selection
   - Issue Category
   - Description
   - (Optional) File Upload
      ↓
7. Clicks "Submit Report"
      ↓
8. Form validates
      ↓
9. Request sent to server
      ↓
10. Success notification shows
      ↓
11. Modal closes
      ↓
12. Page refreshes
      ↓
13. New report appears in table
      ↓
14. Can click "View" to see details
```

---

## 📊 Example Serial Numbers

Pre-populated in the system:

- `LAB01-PC01` through `LAB01-PC05`
- `LAB02-PC01` through `LAB02-PC05`
- `LAB03-PC01` through `LAB03-PC05`
- `LAB04-PC01` through `LAB04-PC05`

Format: `LAB[NUMBER]-PC[NUMBER]`

---

**Last Updated**: October 15, 2025
**Status**: Production Ready ✅
