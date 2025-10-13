# Labs Management System Documentation

## Overview

The Labs Management System is a comprehensive solution for managing laboratory reservations, timetables, and maintenance within the GEO-CMS platform. It provides role-based access for Students, Lecturers, and Administrators.

## Features

### 🎓 Student Features

- View available labs with real-time status
- Request lab usage for personal projects
- Track reservation status (pending, approved, rejected)
- View lab timetables and upcoming bookings
- Cancel pending reservations

### 👨‍🏫 Lecturer Features

- All student features
- Reserve labs for classes and practicals
- Report lab issues and equipment problems
- View equipment status and safety guidelines
- Enhanced reservation details for academic use

### 🔧 Admin/Staff Features

- Complete lab management (add, edit, status changes)
- Approve or reject reservation requests
- Bulk operations for reservations
- Upload and manage lab timetables
- Issue tracking and assignment
- Generate utilization reports
- Comprehensive dashboard with analytics

## Database Schema

### Tables Created

1. **labs** - Laboratory information
2. **lab_reservations** - Booking requests and approvals
3. **lab_timetables** - Regular class schedules
4. **lab_issues** - Maintenance and problem reports

### Key Relationships

- Labs → Reservations (1:many)
- Labs → Timetables (1:many)
- Labs → Issues (1:many)
- Users → Reservations (1:many)
- Users → Issues (1:many)

## File Structure

```
labs/
├── index.php                  # Main entry point (role-based redirect)
├── student-dashboard.php      # Student interface
├── lecturer-dashboard.php     # Lecturer interface
├── admin-dashboard.php        # Admin/Staff interface
├── php/
│   ├── labs_api.php          # Main API endpoint
│   ├── migrate_labs.php      # Database migration script
│   └── complete_setup.php    # Setup completion script
└── README.md                 # This documentation
```

## API Endpoints

### Student/Lecturer Actions

- `submit_reservation` - Submit a lab reservation request
- `cancel_reservation` - Cancel own pending reservation
- `get_timetable` - View lab schedule and bookings
- `get_reservation_details` - View detailed reservation info
- `refresh_lab_status` - Get updated lab availability
- `report_issue` - Report lab problems

### Admin/Staff Actions

- `approve_reservation` - Approve pending requests
- `reject_reservation` - Reject requests with reason
- `manage_lab` - Add/edit lab information
- `update_lab_status` - Change lab availability
- `assign_issue` - Assign issues to staff
- `update_issue_status` - Update issue resolution
- `upload_timetable` - Import schedules from CSV/Excel

## Installation & Setup

### 1. Database Setup

```bash
cd /path/to/geo-cms/labs/php
php complete_setup.php
```

### 2. Verify Installation

The setup script will:

- Create all necessary tables
- Add sample lab data
- Insert example timetables
- Verify table structure

### 3. Access the System

- Navigate to `/labs/` from the sidebar
- Users are automatically redirected based on their role

## Sample Data

### Labs Created

- **Lab 01**: Computer Lab 01 - GIS Software Lab (30 students)
- **Lab 02**: Computer Lab 02 - Programming Lab (25 students)
- **Lab 03**: Computer Lab 03 - Surveying Software Lab (30 students)
- **Lab 04**: Computer Lab 04 - Research Lab (20 students)

### Sample Timetable

- **Monday 09:00-11:00**: Programming Fundamentals (CS101) - Lab 01
- **Tuesday 10:00-12:00**: Circuit Analysis (EE101) - Lab 02
- **Wednesday 14:00-16:00**: Database Systems (CS201) - Lab 01
- **Thursday 13:00-15:00**: Digital Electronics (EE201) - Lab 02
- **Friday 09:00-12:00**: Organic Chemistry Lab (CH301) - Lab 03

## Role-Based Access

### Navigation Flow

```
User Login → Sidebar → Labs Section → Role-Based Dashboard
```

### Permission Matrix

| Feature             | Student | Lecturer | Admin/Staff |
| ------------------- | ------- | -------- | ----------- |
| View Labs           | ✅      | ✅       | ✅          |
| Request Reservation | ✅      | ✅       | ✅          |
| View Timetables     | ✅      | ✅       | ✅          |
| Report Issues       | ❌      | ✅       | ✅          |
| Approve Requests    | ❌      | ❌       | ✅          |
| Manage Labs         | ❌      | ❌       | ✅          |
| Upload Timetables   | ❌      | ❌       | ✅          |

## Technical Implementation

### Frontend Technologies

- **HTML5**: Semantic structure with role-based dashboards
- **CSS3**: Responsive grid layouts and modern styling
- **JavaScript**: Dynamic interactions and AJAX API calls
- **Bootstrap-like**: Custom component library

### Backend Technologies

- **PHP 7.4+**: Server-side logic and API endpoints
- **MySQL 5.7+**: Relational database with foreign keys
- **PDO**: Secure database connections and queries
- **CSRF Protection**: Token-based security

### Key Features

- **Real-time Status**: Dynamic lab availability checking
- **Conflict Detection**: Prevents double-booking
- **Responsive Design**: Works on all device sizes
- **Role Security**: Permission-based access control
- **Data Validation**: Client and server-side validation

## Customization

### Adding New Lab Types

1. Update the `labs` table with new entries
2. Add specialized equipment lists
3. Configure capacity and safety guidelines

### Extending Timetables

1. Use the CSV upload feature for bulk imports
2. Support for recurring schedules
3. Integration with academic calendar

### Issue Management

1. Customizable issue types and priorities
2. Assignment workflows
3. Resolution tracking and reporting

## Troubleshooting

### Common Issues

1. **Migration Errors**

   - Ensure database permissions are correct
   - Check for existing table conflicts
   - Verify MySQL version compatibility

2. **Permission Denied**

   - Confirm user roles in the database
   - Check session authentication
   - Verify CSRF tokens

3. **JavaScript Errors**
   - Ensure all JS files are loaded
   - Check browser console for errors
   - Verify AJAX endpoint URLs

### Support

For technical support or feature requests, refer to the main GEO-CMS documentation or contact the development team.

## Future Enhancements

### Planned Features

- 📧 Email notifications for reservations
- 📱 Mobile app integration
- 📊 Advanced analytics and reporting
- 🔄 Integration with calendar systems
- 🎯 Equipment-specific reservations
- 📋 Maintenance scheduling
- 🔐 QR code access controls

### Version History

- **v1.0** - Initial release with core functionality
- **v1.1** - Enhanced issue tracking (planned)
- **v1.2** - Advanced reporting (planned)

---

_Last updated: October 12, 2025_
