# 🚀 DentoSys v2.0 Setup Guide

## Quick Start

### Prerequisites
- **XAMPP** (or similar): Apache + MySQL/MariaDB + PHP 7.4+
- **Web Browser**: Chrome, Firefox, Safari, or Edge
- **Git** (optional): For version control

### 1. Database Setup

#### Option A: Using PowerShell (Windows - Recommended)
```powershell
cd c:\xampp\htdocs\dentosys\database
.\import.ps1
```

#### Option B: Using Bash (Linux/Mac/WSL)
```bash
cd /path/to/dentosys/database
chmod +x import.sh
./import.sh
```

#### Option C: Manual Import
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database: `dentosys_db`
3. Import file: `database/dentosys_db.sql`

### 2. Configuration

#### Database Connection
Update `includes/db.php` if needed:
```php
$host = 'localhost';
$dbname = 'dentosys_db'; 
$username = 'root';
$password = 'your_mysql_password';
```

#### File Permissions
Ensure uploads directory is writable:
```bash
chmod 755 uploads/
```

### 3. Access the System

1. Start XAMPP services (Apache + MySQL)
2. Navigate to: `http://localhost/dentosys`
3. Login with default credentials:
   - **Email**: `admin@dentosys.local`
   - **Password**: `password`

---

## 🎯 What's New in v2.0

### ✨ Enhanced Features
- **Modern UI**: Figma-inspired design with glassmorphism effects
- **Responsive Design**: Mobile-first approach with CSS Grid
- **Interactive Dashboard**: Real-time KPIs and statistics
- **Enhanced Patient Management**: Card-based layout with advanced search

### 🆕 New Modules

#### 1. Prescriptions Management
- Digital prescription writing
- Drug interaction warnings
- Prescription history tracking
- Print-ready formatting

#### 2. Insurance Claims Processing
- Automated claim generation
- Status tracking (Pending → Approved → Paid)
- Integration with major insurance providers
- Claims history and reporting

#### 3. Enhanced Integrations
- **Payment Gateways**: Stripe, PayPal, Square
- **Email Services**: SendGrid, Mailgun, SMTP
- **SMS Services**: Twilio, Nexmo
- **Calendar Sync**: Google Calendar, Outlook

### 🔧 Technical Improvements
- **Modern CSS Framework**: CSS custom properties and modern layouts
- **Security Enhancements**: Role-based access control
- **Database Optimization**: Proper indexing and relationships
- **Code Organization**: Clean MVC structure

---

## 📱 Core Features

### 👥 Patient Management
- **Add/Edit Patients**: Comprehensive patient profiles
- **Medical History**: Complete dental records
- **Document Management**: File uploads and storage
- **Patient Search**: Advanced filtering and sorting

### 📅 Appointment System
- **Calendar View**: Monthly appointment overview
- **Booking System**: Easy appointment scheduling
- **Status Management**: Pending, confirmed, completed appointments
- **Conflict Detection**: Automatic scheduling conflict prevention

### 💰 Billing & Payments
- **Invoice Generation**: Professional invoice creation
- **Payment Tracking**: Multiple payment methods
- **Insurance Integration**: Automated claim processing
- **Financial Reporting**: Revenue and payment analytics

### 📋 Clinical Records
- **Treatment Notes**: Detailed clinical documentation
- **Prescription Management**: Digital prescription system
- **File Management**: X-rays, photos, documents
- **Medical History**: Comprehensive health records

### 📊 Reports & Analytics
- **Financial Reports**: Revenue, expenses, profitability
- **Operational Metrics**: Patient statistics, appointment analytics
- **Audit Logs**: System activity tracking
- **Custom Reports**: Flexible reporting system

### 💬 Communications
- **Patient Messaging**: Secure communication system
- **Email Templates**: Automated notifications
- **SMS Integration**: Appointment reminders
- **Feedback System**: Patient satisfaction tracking

### ⚙️ Settings & Administration
- **Clinic Information**: Practice details and configuration
- **User Management**: Staff accounts and roles
- **Integration Settings**: Third-party service configuration
- **System Preferences**: Customizable system settings

---

## 🛠️ Development Notes

### File Structure
```
dentosys/
├── auth/                 # Authentication system
├── pages/               # Main application pages
│   ├── dashboard.php    # Enhanced dashboard
│   ├── patients/        # Patient management
│   ├── appointments/    # Appointment system
│   ├── billing/         # Billing and payments
│   ├── records/         # Clinical records
│   ├── reports/         # Analytics and reports
│   ├── communications/  # Messaging system
│   └── settings/        # System configuration
├── includes/            # Core utilities
├── templates/           # Shared templates
├── assets/             # CSS, JS, images
└── database/           # Database files and scripts
```

### CSS Framework
The system uses a modern CSS framework with:
- **CSS Custom Properties**: For theming and consistency
- **CSS Grid & Flexbox**: For responsive layouts
- **Glassmorphism Effects**: Modern visual design
- **Component-Based Styles**: Reusable UI components

### Security Features
- **Role-Based Access Control**: Admin, Dentist, Staff, Receptionist roles
- **Session Management**: Secure session handling
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization

---

## 🔍 Troubleshooting

### Common Issues

#### Database Connection Error
```
Solution: Check MySQL service is running and credentials in includes/db.php
```

#### Login Not Working
```
Default credentials: admin@dentosys.local / password
Reset: Use test_db.php to verify database connection
```

#### File Upload Issues
```
Solution: Check uploads/ directory permissions (755 or 777)
Verify PHP upload limits in php.ini
```

#### Styling Issues
```
Solution: Clear browser cache and ensure CSS files are loading
Check console for JavaScript errors
```

### Database Reset
If you need to reset the database:
```bash
# Re-run the import script
cd database/
.\import.ps1
```

---

## 📞 Support

For technical support or questions:
1. Check the troubleshooting section above
2. Review the database and PHP error logs
3. Ensure all XAMPP services are running
4. Verify file permissions for uploads directory

---

## 🎉 Welcome to DentoSys v2.0!

Your comprehensive dental practice management solution is ready. The system includes everything needed to run a modern dental practice, from patient management to insurance claims processing.

**Happy dental practice management!** 🦷✨
