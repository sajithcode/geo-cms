# Labs Management System - Complete Implementation Guide

## Overview

The Labs Management System is a comprehensive module for managing laboratory facilities, reservations, timetables, and maintenance in an educational institution. It provides role-based access and functionality for Students, Lecturers, Staff, and Administrators.

## Features by Role

### 👨‍🎓 Students

- **View Available Labs**: See all 4 labs with current status (Available/In Use/Maintenance)
- **Lab Details**: View capacity, description, and current status of each lab
- **Request Lab Use**: Submit reservation requests with date, time, and purpose
- **View Timetable**: Check scheduled sessions for each lab
- **Track Reservations**: View pending, approved, and rejected reservation requests
- **Upcoming Reservations**: Quick view of approved upcoming sessions
- **Cancel Requests**: Cancel pending reservation requests

### 👨‍🏫 Lecturers

- **All Student Features**: Access to all student-level functionality
- **Request Lab Reservations**: Reserve labs for practicals or teaching sessions
- **View Teaching Schedule**: See assigned timetable entries
- **Report Issues**: Submit equipment and facility issue reports
- **View Equipment Status**: Check lab equipment condition and availability
- **Track Issue Reports**: Monitor submitted issue reports and their status

### 👨‍💼 Staff

- **Approve/Reject Reservations**: Review and process lab reservation requests
- **View All Reservations**: Monitor all reservation requests from students and lecturers
- **Filter Reservations**: Sort by status, lab, or requester
- **View Timetables**: Access lab schedules
- **View Issues**: Monitor reported lab issues
- **Lab Status Monitoring**: Track real-time lab availability

### 👨‍💼 Administrators

- **Complete Control**: All staff features plus administrative capabilities
- **Lab Management**: Add, edit, and configure laboratory facilities
- **Update Lab Status**: Change lab status (Available/In Use/Maintenance)
- **Upload Timetables**: Bulk upload timetables via CSV/Excel files
- **Assign Issues**: Assign reported issues to staff members
- **Update Issue Status**: Change issue status (Pending/In Progress/Fixed)
- **Analytics Dashboard**: View comprehensive statistics and insights
- **Export Reports**: Generate and export lab usage reports
- **Maintenance Alerts**: Monitor and respond to maintenance requirements

## File Structure

```
geo-cms/
├── labs/
│   ├── index.php                      # Main entry point with role routing
│   ├── admin-dashboard.php            # Admin dashboard
│   ├── staff-dashboard.php            # Staff dashboard
│   ├── lecturer-dashboard.php         # Lecturer dashboard
│   ├── student-dashboard.php          # Student dashboard
│   └── php/
│       └── labs_api.php               # Backend API for all operations
├── css/
│   └── labs.css                       # Labs system styles
├── js/
│   ├── labs.js                        # Common labs functionality
│   ├── admin-labs.js                  # Admin-specific JavaScript
│   └── staff-labs.js                  # Staff-specific JavaScript
└── labs_system_setup.sql              # Database setup and sample data

```

## Database Schema

### Tables

#### 1. `labs`

Stores laboratory information.

```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR 50) - Lab name (e.g., "Lab 01")
- description (TEXT) - Lab description
- capacity (INT, DEFAULT 30) - Maximum capacity
- status (ENUM) - 'available', 'in_use', 'maintenance'
- created_at (TIMESTAMP)
```

#### 2. `lab_reservations`

Manages lab reservation requests.

```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- lab_id (INT, FOREIGN KEY -> labs.id)
- user_id (INT, FOREIGN KEY -> users.id)
- reservation_date (DATE)
- start_time (TIME)
- end_time (TIME)
- purpose (TEXT)
- status (ENUM) - 'pending', 'approved', 'rejected', 'completed'
- approved_by (INT, FOREIGN KEY -> users.id, NULLABLE)
- request_date (TIMESTAMP)
- approved_date (TIMESTAMP, NULLABLE)
- notes (TEXT, NULLABLE)
```

#### 3. `lab_timetables`

Stores regular scheduled lab sessions.

```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- lab_id (INT, FOREIGN KEY -> labs.id)
- day_of_week (ENUM) - 'monday' to 'friday'
- start_time (TIME)
- end_time (TIME)
- lecturer_id (INT, FOREIGN KEY -> users.id, NULLABLE)
- subject (VARCHAR 100)
- semester (VARCHAR 20)
- batch (VARCHAR 50)
- created_at (TIMESTAMP)
```

#### 4. `issue_reports`

Tracks equipment and facility issues.

```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT, FOREIGN KEY -> users.id)
- lab_id (INT, FOREIGN KEY -> labs.id, NULLABLE)
- computer_number (VARCHAR 10, NULLABLE)
- description (TEXT)
- screenshot (VARCHAR 255, NULLABLE)
- status (ENUM) - 'pending', 'in_progress', 'fixed'
- assigned_to (INT, FOREIGN KEY -> users.id, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- resolved_at (TIMESTAMP, NULLABLE)
```

## API Endpoints

All API requests go through `labs/php/labs_api.php` with action parameter.

### Lab Management (Admin Only)

#### Manage Lab

- **Action**: `manage_lab`
- **Method**: POST
- **Parameters**:
  - `lab_id` (optional for edit)
  - `name`
  - `description`
  - `capacity`
  - `status`
  - `csrf_token`

#### Update Lab Status

- **Action**: `update_lab_status`
- **Method**: POST
- **Parameters**:
  - `lab_id`
  - `status` (available/in_use/maintenance)
  - `csrf_token`

### Reservation Management

#### Submit Reservation

- **Action**: `submit_reservation`
- **Method**: POST
- **Parameters**:
  - `lab_id`
  - `reservation_date`
  - `start_time`
  - `end_time`
  - `purpose`
  - `csrf_token`

#### Approve Reservation (Admin/Staff)

- **Action**: `approve_reservation`
- **Method**: POST
- **Parameters**:
  - `reservation_id`
  - `notes` (optional)
  - `csrf_token`

#### Reject Reservation (Admin/Staff)

- **Action**: `reject_reservation`
- **Method**: POST
- **Parameters**:
  - `reservation_id`
  - `reason`
  - `csrf_token`

#### Cancel Reservation

- **Action**: `cancel_reservation`
- **Method**: POST
- **Parameters**:
  - `reservation_id`
  - `csrf_token`

#### Get Reservation Details

- **Action**: `get_reservation_details`
- **Method**: GET
- **Parameters**:
  - `reservation_id`

### Issue Management

#### Report Issue

- **Action**: `report_issue`
- **Method**: POST
- **Parameters**:
  - `lab_id` (optional)
  - `computer_number` (optional)
  - `description`
  - `csrf_token`

#### Assign Issue (Admin/Staff)

- **Action**: `assign_issue`
- **Method**: POST
- **Parameters**:
  - `issue_id`
  - `assigned_to`
  - `csrf_token`

#### Update Issue Status (Admin/Staff)

- **Action**: `update_issue_status`
- **Method**: POST
- **Parameters**:
  - `issue_id`
  - `status` (pending/in_progress/fixed)
  - `csrf_token`

#### Get Issue Details

- **Action**: `get_issue_details`
- **Method**: GET
- **Parameters**:
  - `issue_id`

### Timetable Management

#### Get Timetable

- **Action**: `get_timetable`
- **Method**: GET
- **Parameters**:
  - `lab_id`

#### Upload Timetable (Admin Only)

- **Action**: `upload_timetable`
- **Method**: POST (multipart/form-data)
- **Parameters**:
  - `lab_id`
  - `timetable_file` (CSV/Excel)
  - `csrf_token`

### System Management

#### Refresh Lab Status (Admin/Staff)

- **Action**: `refresh_lab_status`
- **Method**: GET
- **Description**: Auto-updates lab status based on current reservations

## Installation Steps

### 1. Database Setup

```bash
# Import the main database schema
mysql -u username -p database_name < database.sql

# Import labs system setup
mysql -u username -p database_name < labs_system_setup.sql
```

### 2. File Permissions

Ensure the `uploads/` directory has write permissions for file uploads (if implemented).

### 3. Configuration

Update `php/config.php` with your database credentials if not already configured.

### 4. Access URLs

- Admin: `http://yoursite.com/geo-cms/labs/` (with admin login)
- Staff: `http://yoursite.com/geo-cms/labs/` (with staff login)
- Lecturer: `http://yoursite.com/geo-cms/labs/` (with lecturer login)
- Student: `http://yoursite.com/geo-cms/labs/` (with student login)

## Usage Examples

### Student Requesting a Lab

1. Navigate to Labs section from sidebar
2. View available labs (Lab 01-04)
3. Click "Request Lab Use" or "Request" button on a specific lab
4. Fill in the form:
   - Select lab
   - Choose date
   - Set start and end time
   - Describe purpose
5. Submit request
6. Track status in "My Reservation Requests" section

### Staff Approving Reservations

1. Navigate to Labs section
2. View "Reservation Requests Management" table
3. Filter by status, lab, or search by requester
4. Click "View" to see details
5. Click "Approve" or "Reject"
6. Add optional notes or rejection reason
7. Confirm action

### Admin Managing Labs

1. Navigate to Labs section (Admin Dashboard)
2. View Analytics Dashboard with statistics
3. **Add New Lab**:
   - Click "➕ Add Lab"
   - Fill in name, description, capacity, status
   - Save
4. **Edit Lab**:
   - Click "✏️ Edit" on lab card
   - Update details
   - Save
5. **Change Status**:
   - Click "🔄 Change Status"
   - Select new status
   - Confirm

### Lecturer Reporting Issues

1. Navigate to Labs section
2. Click "🚨 Report Issue"
3. Fill in the form:
   - Select lab
   - Enter computer number (if applicable)
   - Describe issue in detail
4. Submit report
5. Track status in "My Recent Issue Reports"

## Features to Implement (Future)

1. **Real-time Notifications**: Push notifications for approval/rejection
2. **Email Notifications**: Automatic email alerts
3. **Equipment Tracking**: Detailed inventory of lab equipment
4. **Maintenance Scheduling**: Automated maintenance schedules
5. **Usage Analytics**: Detailed usage reports and visualizations
6. **Calendar Integration**: Export to Google Calendar/Outlook
7. **QR Code Check-in**: Quick lab check-in via QR codes
8. **Mobile App**: Dedicated mobile application
9. **Automated Status Updates**: Auto-change lab status based on reservations
10. **Conflict Resolution**: Smart detection of scheduling conflicts

## Security Considerations

- **CSRF Protection**: All forms use CSRF tokens
- **Role-based Access**: Strict permission checks on all actions
- **SQL Injection Prevention**: Prepared statements used throughout
- **XSS Protection**: All user input is sanitized
- **Session Management**: Secure session handling via `config.php`

## Troubleshooting

### Common Issues

1. **"Reservation not found" error**

   - Check if reservation ID exists
   - Verify user has permission to view/modify

2. **Time slot already reserved**

   - Check for overlapping reservations
   - Use different time slot

3. **Cannot upload timetable**

   - Verify file format (CSV/Excel only)
   - Check file size limits
   - Ensure proper CSV structure

4. **Lab status not updating**
   - Run `refresh_lab_status` API call
   - Check database triggers
   - Verify reservation times

## Support

For issues or questions:

1. Check this documentation
2. Review error logs in browser console
3. Check PHP error logs
4. Contact system administrator

## Version History

- **v1.0** (Current): Initial implementation with full CRUD operations, role-based access, and responsive design

---

**Note**: This system requires users to be logged in and have appropriate role permissions. Ensure all users are properly registered with correct roles in the `users` table.
