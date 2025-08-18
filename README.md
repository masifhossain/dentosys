# 🦷 DentoSys v2.0 - Enhanced Dental Practice Management System

DentoSys is a comprehensive web-based dental practice management system designed to digitize and streamline dental operations. Built with modern PHP, enhanced CSS framework, and MariaDB database, DentoSys provides a professional solution for managing all aspects of a dental practice.

## ✨ Enhanced Features v2.0

### 🎨 **Modern User Interface**
* **Enhanced Dashboard** - Beautiful card-based layout with KPIs, quick actions, and organized sections
* **Responsive Design** - Optimized for desktop, tablet, and mobile devices
* **Professional Styling** - Modern CSS framework with hover effects and smooth transitions

### 👥 **Comprehensive User Management**
* **Role-Based Access Control** - Admin, Dentist, Receptionist, and Patient roles
* **Staff Account Creation** - Admin can create dentist and receptionist accounts
* **User Profile Management** - Complete profiles with specializations and contact details
* **Secure Authentication** - Password hashing and session management

### 📋 **Patient Management**
* **Enhanced Patient List** - Modern card-based layout with search and filtering
* **Complete Patient Profiles** - Medical history, contact information, and treatment records
* **Patient Registration** - Streamlined patient onboarding process

### 📅 **Appointment System**
* **Calendar Interface** - Visual appointment scheduling and management
* **Booking System** - Easy appointment booking with time slot management
* **Appointment Status** - Pending approvals and confirmation system

### 💰 **Billing & Financial Management**
* **Invoice Generation** - Professional invoice creation and management
* **Payment Processing** - Payment tracking and receipt generation
* **Insurance Claims** - Insurance claim submission and tracking

### 📊 **Reports & Analytics**
* **Financial Reports** - Revenue tracking and financial summaries
* **Operational Reports** - Appointment statistics and practice metrics
* **Audit Logs** - System activity tracking and compliance

### ⚙️ **Settings & Administration**
* **Settings Hub** - Centralized admin control panel
* **Clinic Information** - Practice details and configuration
* **System Integrations** - Third-party service connections
* **User Role Management** - Permission and access control

## 🛠️ Technologies Used

* **Backend:** PHP 8.2+ with modern practices
* **Frontend:** Enhanced CSS Framework, HTML5, JavaScript
* **Database:** MariaDB/MySQL with optimized schema
* **Architecture:** MVC pattern with role-based access control

## 🚀 Quick Start Guide

### Prerequisites

* **PHP:** Version 8.0 or higher
* **MariaDB/MySQL:** Version 10.4 or higher
* **Web Server:** Apache/Nginx or PHP built-in server

### 📦 Installation Steps

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/masifhossain/dentosys
   cd dentosys
   ```

2. **Database Setup:**
   ```bash
   # Using PowerShell (Recommended)
   .\database\import.ps1
   
   # Or manually
   mysql -u root -p
   CREATE DATABASE dentosys_db;
   USE dentosys_db;
   SOURCE database/dentosys_db.sql;
   ```

3. **Configure Database Connection:**
   Edit `includes/db.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'dentosys_db');
   ```

4. **Start the Application:**
   ```bash
   # PHP Development Server
   php -S localhost:8000
   
   # Or use XAMPP/WAMP
   # Place in htdocs and visit http://localhost/dentosys
   ```

5. **Access the System:**
   - **URL:** http://localhost:8000
   - **Admin Login:** admin@dentosys.local / password

## 👤 Default User Accounts

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| **Admin** | admin@dentosys.local | password | Full system access |
| **Dentist** | s.williams@dentosys.local | password | Clinical + Patient management |
| **Dentist** | j.chen@dentosys.local | password | Clinical + Patient management |
| **Receptionist** | reception@dentosys.local | password | Appointments + Basic patient info |

## 📁 Project Structure

```
dentosys/
├── assets/                 # CSS, JS, Images
│   ├── css/
│   │   ├── framework.css   # Enhanced CSS framework
│   │   └── style.css       # Additional styling
│   ├── js/
│   └── images/
├── auth/                   # Authentication
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── database/               # Database files
│   ├── dentosys_db.sql     # Database schema
│   └── import.ps1          # Import script
├── includes/               # Core includes
│   ├── db.php              # Database connection
│   ├── functions.php       # Helper functions
│   └── auth_middleware.php # Authentication
├── pages/                  # Application pages
│   ├── dashboard.php       # Enhanced dashboard
│   ├── patients/           # Patient management
│   ├── appointments/       # Appointment system
│   ├── billing/            # Billing & payments
│   ├── records/            # Clinical records
│   ├── communications/     # Messages & feedback
│   ├── reports/            # Analytics & reports
│   └── settings/           # Admin settings
├── templates/              # UI templates
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
└── uploads/                # File uploads
```

## 🔧 Administration

### Creating Staff Accounts
1. Login as Admin
2. Navigate to **Settings → Users**
3. Click **"Add New User"**
4. Fill in user details and assign role (Dentist/Receptionist)
5. User can login with provided credentials

### System Configuration
- **Clinic Information:** Settings → Clinic Info
- **User Roles:** Settings → Roles
- **Integrations:** Settings → Integrations

## 📱 Features Overview

### Dashboard
- **KPI Cards:** Patient count, appointments, revenue tracking
- **Quick Actions:** Direct access to common tasks
- **Today's Schedule:** Current day appointments
- **Recent Patients:** Latest patient registrations

### Patient Management
- **Enhanced List View:** Card-based layout with search/filter
- **Complete Profiles:** Medical history, contact details
- **Treatment History:** Comprehensive treatment records

### Appointments
- **Calendar View:** Visual scheduling interface
- **Booking System:** Time slot management
- **Status Tracking:** Pending, confirmed, completed appointments

## 🛡️ Security Features

- **Password Hashing:** Secure bcrypt password storage
- **Role-Based Access:** Granular permission system
- **Session Management:** Secure session handling
- **SQL Injection Protection:** Prepared statements

## 📋 Requirements

- **Server:** Apache/Nginx or PHP built-in server
- **PHP:** 8.0+ with MySQLi extension
- **Database:** MySQL 5.7+ or MariaDB 10.4+
- **Storage:** 100MB+ for application files
- **Memory:** 512MB+ PHP memory limit recommended

## 🔗 Additional Resources

- **Setup Guide:** [SETUP_GUIDE.md](SETUP_GUIDE.md)
- **Login Instructions:** [LOGIN_GUIDE.md](LOGIN_GUIDE.md)
- **Design Assets:** [designs/](designs/) folder

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit changes (`git commit -am 'Add new feature'`)
4. Push to branch (`git push origin feature/new-feature`)
5. Create a Pull Request

---

**DentoSys v2.0** - Professional Dental Practice Management System  
*Built with ❤️ for dental professionals*

