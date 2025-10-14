# Labs Management System - Implementation Guide

## Overview

The Labs Management System provides a comprehensive solution for managing laboratory reservations, timetables, and issues across the Faculty of Geomatics.

## Features by Role

### Common Features (All Roles)

- View 4 labs (Lab 01–Lab 04) as interactive cards
- Each lab card displays:
  - Current status (Available / In Use / Maintenance)
  - Lab capacity
  - Lab description
  - "View Timetable" button
- View weekly timetables for each lab
- Report issues with equipment or facilities

### For Students

- **Request Lab Use**: Submit requests for lab usage with:
  - Selected date and time slot
  - Purpose of use
  - Expected duration
- **View Requests**: Track all reservation requests with status (pending/approved/rejected)
- **Approved Reservations**: See list of approved lab bookings
- **Cancel Requests**: Cancel pending requests

### For Lecturers

- **Request Lab Reservation**: Book labs for practicals or sessions with:
  - Date and time selection
  - Subject/course information
  - Expected number of students
  - Purpose of session
- **View Timetables**: Access schedule for all labs
- **Equipment Status**: Check availability of lab equipment
- **Report Issues**: Submit detailed issue reports with:
  - Lab selection
  - Computer number (if applicable)
  - Description and screenshot
- **Track Reservations**: View all personal reservations

### For Staff

- **Approve/Deny Requests**: Review and process lab reservation requests from students and lecturers
- **View All Reservations**: Monitor all lab bookings across all labs
- **Manage Issues**: View and assign issue reports
- **Lab Status Monitoring**: Track current status of all laboratories

### For Admin

- **Full Lab Management**:
  - Add, edit, or remove labs
  - Change lab status (available/in use/maintenance)
  - Set lab capacity
- **Approve/Deny Reservations**: Process all reservation requests
- **Upload/Edit Timetables**:
  - Bulk upload via CSV/Excel
  - Manual entry and editing
  - Schedule management
- **View Maintenance Alerts**: Monitor all reported issues
- **Assign Issues**: Delegate problems to appropriate staff
- **Analytics Dashboard**: View comprehensive statistics
- **Export Reports**: Generate reports for reservations and issues

## Database Tables

### labs

- id (Primary Key)
- name (Lab 01, Lab 02, etc.)
- description
- capacity (number of seats)
- status (available/in_use/maintenance)
- created_at

### lab_reservations

- id (Primary Key)
- lab_id (Foreign Key -> labs.id)
- user_id (Foreign Key -> users.id)
- reservation_date
- start_time
- end_time
- purpose
- status (pending/approved/rejected/completed)
- approved_by (Foreign Key -> users.id)
- request_date
- approved_date
- notes

### lab_timetables

- id (Primary Key)
- lab_id (Foreign Key -> labs.id)
- day_of_week (Monday-Friday)
- start_time
- end_time
- lecturer_id (Foreign Key -> users.id)
- subject
- semester
- batch
- created_at

### issue_reports

- id (Primary Key)
- user_id (Foreign Key -> users.id)
- lab_id (Foreign Key -> labs.id)
- computer_number
- description
- screenshot
- status (pending/in_progress/fixed)
- assigned_to (Foreign Key -> users.id)
- created_at
- updated_at
- resolved_at

## File Structure

```
labs/
├── index.php                 # Router to role-specific dashboards
├── admin-dashboard.php       # Admin interface
├── staff-dashboard.php       # Staff interface
├── lecturer-dashboard.php    # Lecturer interface
├── student-dashboard.php     # Student interface
└── php/
    ├── labs_api.php          # Main API handler
    ├── get_lab_details.php   # Lab information
    ├── get_timetable.php     # Timetable data
    ├── reservation_api.php   # Reservation management
    ├── issue_api.php         # Issue report handling
    └── update_lab_status.php # Status management
```

## API Endpoints

### Reservations

- `POST /labs/php/labs_api.php?action=submit_reservation` - Submit new reservation
- `POST /labs/php/labs_api.php?action=approve_reservation` - Approve request (staff/admin)
- `POST /labs/php/labs_api.php?action=reject_reservation` - Reject request (staff/admin)
- `POST /labs/php/labs_api.php?action=cancel_reservation` - Cancel own request
- `GET /labs/php/labs_api.php?action=get_reservation_details&id={id}` - Get details

### Timetables

- `GET /labs/php/labs_api.php?action=get_timetable&lab_id={id}` - Get lab timetable
- `POST /labs/php/labs_api.php?action=upload_timetable` - Upload timetable (admin)
- `POST /labs/php/labs_api.php?action=add_timetable_entry` - Add single entry (admin)
- `POST /labs/php/labs_api.php?action=edit_timetable_entry` - Edit entry (admin)
- `DELETE /labs/php/labs_api.php?action=delete_timetable_entry` - Delete entry (admin)

### Labs

- `GET /labs/php/labs_api.php?action=get_labs` - Get all labs
- `GET /labs/php/labs_api.php?action=get_lab_details&id={id}` - Get lab details
- `POST /labs/php/labs_api.php?action=add_lab` - Add new lab (admin)
- `POST /labs/php/labs_api.php?action=edit_lab` - Edit lab (admin)
- `POST /labs/php/labs_api.php?action=change_lab_status` - Update status (admin)

### Issues

- `POST /labs/php/labs_api.php?action=report_issue` - Report new issue
- `GET /labs/php/labs_api.php?action=get_issue_details&id={id}` - Get issue details
- `POST /labs/php/labs_api.php?action=assign_issue` - Assign issue (admin/staff)
- `POST /labs/php/labs_api.php?action=update_issue_status` - Update status (admin/staff)

## JavaScript Functions

### Core Functions (labs.js)

- `handleLabRequest()` - Process lab reservation form
- `handleIssueReport()` - Process issue report form
- `viewTimetable(labId)` - Display lab timetable
- `viewReservationDetails(id)` - Show reservation details
- `filterReservations()` - Filter reservations table
- `validateTimeRange()` - Validate start/end times

### Admin Functions (admin-labs.js)

- `handleLabManagement()` - Add/edit labs
- `handleTimetableUpload()` - Process timetable file
- `approveReservation(id)` - Approve request
- `rejectReservation(id)` - Reject request
- `changeLabStatus(labId)` - Update lab status
- `assignIssue(id)` - Assign issue to staff
- `exportReport()` - Generate reports

### Staff Functions (staff-labs.js)

- `approveReservation(id)` - Approve request
- `rejectReservation(id)` - Reject request
- `viewIssueDetails(id)` - View issue details
- `exportReservations()` - Export data

## CSS Styling

The system uses a custom CSS file (`css/labs.css`) with styles for:

- Lab cards with status indicators
- Timetable grid layout
- Reservation forms
- Issue report interface
- Status badges and indicators
- Responsive design for all screen sizes

## Security Features

- CSRF token validation on all forms
- Role-based access control
- SQL injection prevention via prepared statements
- XSS prevention via input sanitization
- Session management and timeout
- File upload validation (screenshots)

## Notifications

Users receive notifications for:

- Reservation approval/rejection
- Upcoming reserved lab sessions
- Issue report updates
- Timetable changes

## Mobile Responsiveness

All lab management interfaces are fully responsive and work on:

- Desktop computers
- Tablets
- Mobile phones

## Future Enhancements

Possible future additions:

- QR code scanning for lab check-in
- Equipment tracking within labs
- Automatic conflict detection
- Email notifications
- Calendar integration
- Usage analytics and reporting
- Recurring reservations
- Lab usage heatmaps
