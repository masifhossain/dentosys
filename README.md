# 🦷 DentoSys v2.0 - Enhanced Dental Practice Management System

> **Complete Setup & User Guide** - Everything you need to install, configure, and use DentoSys is in this single comprehensive guide.

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
   - **Alternative:** http://localhost/dentosys (if using XAMPP)
   - **Admin Login:** admin@dentosys.local / Password

## 🔐 Login & Access

### **Multiple Access Methods:**
- **PHP Built-in Server:** http://localhost:8000
- **XAMPP Apache:** http://localhost/dentosys  
- **Direct File Access:** Navigate to project folder

### **Troubleshooting Login Issues:**
- **Clear browser cache** and try again
- **Check PHP server is running:** Look for server startup message
- **Verify database connection:** Check `includes/db.php` configuration
- **Reset passwords:** Run `php reset_password.php` to reset all user passwords to "Password"

### **Starting the Server:**
```powershell
# Start PHP development server
php -S localhost:8000

# Reset all user passwords to "Password"
php reset_password.php

# Import database if needed
database/import.ps1
```

## 👤 Default User Accounts

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| **Admin** | admin@dentosys.local | Password | Full system access |
| **Dentist** | s.williams@dentosys.local | Password | Clinical + Patient management |
| **Dentist** | j.chen@dentosys.local | Password | Clinical + Patient management |
| **Receptionist** | reception@dentosys.local | Password | Appointments + Basic patient info |

### 🔧 Password Reset Utility

DentoSys includes a convenient password reset utility that updates all user accounts simultaneously:

```powershell
php reset_password.php
```

**Features:**
- ✅ Updates all 4 user accounts at once
- ✅ Sets password to "Password" for consistency
- ✅ Includes verification testing
- ✅ Provides detailed success/failure feedback
- ✅ Shows login information after completion

**When to Use:**
- After fresh database import
- When users forget passwords
- For testing and development
- To standardize passwords across accounts

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

## 🎯 Getting Started After Login

### **Admin Portal Capabilities:**
- ✅ **Enhanced Dashboard** - Beautiful KPI cards, today's schedule, recent patients
- ✅ **Patient Management** - Modern card-based patient list with advanced search and filtering  
- ✅ **User Management** - Create dentist and receptionist accounts with role assignment
- ✅ **Appointment System** - Full calendar view and booking management
- ✅ **Billing & Payments** - Invoice generation, payment tracking, insurance claims
- ✅ **Clinical Records** - Medical notes, prescriptions, treatment files
- ✅ **Communications** - Patient feedback and message templates
- ✅ **Reports & Analytics** - Financial, operational, and audit reports
- ✅ **Settings Hub** - Clinic info, user roles, and system integrations

### **Quick Start Workflow:**
1. **Login** with admin credentials
2. **Explore Dashboard** - Review system overview and KPIs
3. **Create Staff Accounts** - Visit Settings → Users to add dentists and receptionists
4. **Add Patients** - Use the enhanced patient management system
5. **Schedule Appointments** - Book appointments using the calendar interface
6. **Configure Settings** - Set up clinic information and preferences

## 🔧 Advanced Administration

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

- **Design Assets:** [designs/](designs/) folder with Figma mockups
- **Database Schema:** [database/dentosys_db.sql](database/dentosys_db.sql)
- **Import Scripts:** [database/import.ps1](database/import.ps1) for easy setup

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

