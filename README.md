# DentoSys - Dental Practice Management System

DentoSys is a comprehensive web-based dental practice management system designed to streamline clinic operations, enhance patient care, and improve practice efficiency. Built with modern web technologies, it provides an intuitive interface for both dental practitioners and patients.

## 🦷 Features

### For Dental Practitioners
- **Patient Management**: Complete patient records, medical history, and contact information
- **Appointment Scheduling**: Interactive calendar with appointment booking, rescheduling, and cancellation
- **Clinical Records**: Digital patient charts, treatment notes, and prescription management
- **Billing & Invoicing**: Automated invoice generation, payment tracking, and insurance claim processing
- **Reports & Analytics**: Financial reports, operational metrics, and audit logs
- **User Management**: Role-based access control for different staff members
- **Clinic Settings**: Customizable clinic information and system configurations

### For Patients
- **Patient Portal**: Secure login to access personal health information
- **Online Appointments**: Book, view, and manage appointments online
- **Medical Records**: Access to treatment history and prescriptions
- **Billing Information**: View invoices, payment history, and outstanding balances
- **Profile Management**: Update contact information and emergency contacts

## 🛠 Technology Stack

- **Backend**: PHP 8.x
- **Database**: MariaDB/MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Web Server**: Apache/Nginx (XAMPP compatible)
- **Architecture**: MVC-inspired structure with modular components

## 📋 System Requirements

- PHP 8.0 or higher
- MariaDB 10.4+ or MySQL 8.0+
- Apache 2.4+ or Nginx 1.18+
- Web browser with JavaScript enabled
- Minimum 512MB RAM
- 1GB disk space

## 🚀 Installation

### Prerequisites
1. Install XAMPP (recommended) or configure LAMP/WAMP stack
2. Ensure PHP extensions are enabled: `mysqli`, `pdo_mysql`, `session`

### Setup Instructions

1. **Clone the repository**:
   ```bash
   git clone https://github.com/masifhossain/dentosys.git
   cd dentosys
   ```

2. **Database Setup**:
   - Start Apache and MySQL services in XAMPP
   - Access phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `dentosys_db`
   - Import the database schema:
     ```sql
     source database/dentosys_db.sql
     ```

3. **Configuration**:
   - Copy the project to your web server directory (e.g., `htdocs` for XAMPP)
   - Update database connection settings in `includes/db.php`:
     ```php
     $host = 'localhost';
     $dbname = 'dentosys_db';
     $username = 'root';
     $password = '';
     ```

4. **Permissions**:
   - Ensure the `uploads/` directory is writable
   - Set appropriate file permissions for security

5. **Access the Application**:
   - Navigate to `http://localhost/dentosys`
   - Use the default admin credentials to log in

## 🔐 Default Login Credentials

### Staff Login
- **Admin**: admin@dentosys.com / admin123
- **Dentist**: dentist@dentosys.com / dentist123
- **Receptionist**: reception@dentosys.com / reception123

### Patient Portal
- Patients must register through the registration page or be created by staff

## 📁 Project Structure

```
dentosys/
├── assets/                 # Static assets (CSS, images, JS)
│   ├── css/               # Stylesheets
│   └── images/            # Images and logos
├── auth/                  # Authentication modules
│   ├── login.php          # Staff login
│   ├── patient_portal.php # Patient login portal
│   └── register.php       # Patient registration
├── database/              # Database files and scripts
│   ├── dentosys_db.sql    # Database schema
│   └── *.php              # Database utility scripts
├── includes/              # Core PHP includes
│   ├── db.php             # Database connection
│   ├── functions.php      # Common functions
│   └── auth_middleware.php # Authentication middleware
├── pages/                 # Application pages
│   ├── dashboard.php      # Main dashboard
│   ├── appointments/      # Appointment management
│   ├── billing/           # Billing and invoicing
│   ├── patients/          # Patient management
│   ├── records/           # Clinical records
│   ├── reports/           # Reports and analytics
│   └── settings/          # System settings
├── templates/             # Common templates
│   ├── header.php         # Page header
│   ├── sidebar.php        # Navigation sidebar
│   └── footer.php         # Page footer
├── uploads/               # File uploads directory
├── index.php              # Application entry point
└── landing.php            # Landing page
```

## 🔒 Security Features

- **Role-Based Access Control (RBAC)**: Different permission levels for admin, dentist, receptionist, and patient roles
- **Session Management**: Secure session handling with timeout and validation
- **SQL Injection Protection**: Prepared statements and input validation
- **CSRF Protection**: Token-based protection for form submissions
- **Audit Logging**: Comprehensive activity logging for compliance
- **Data Encryption**: Sensitive data encryption in transit and at rest

## 📊 User Roles & Permissions

### Admin (Role ID: 1)
- Full system access and configuration
- User management and role assignment
- Financial reports and audit logs
- System settings and integrations

### Dentist (Role ID: 2)
- Patient records and treatment notes
- Appointment management
- Prescription writing
- Clinical reports

### Receptionist (Role ID: 3)
- Appointment scheduling
- Patient registration and basic info
- Billing and payment processing
- Front desk operations

### Patient (Role ID: 4)
- Personal profile management
- Appointment booking and viewing
- Medical records access (read-only)
- Billing information viewing

## 🔧 Configuration

### Database Configuration
Update `includes/db.php` with your database credentials:
```php
$host = 'localhost';
$dbname = 'dentosys_db';
$username = 'your_username';
$password = 'your_password';
```

### Application Settings
Modify system settings through the admin panel:
- Clinic information and contact details
- Appointment scheduling rules
- Billing and payment options
- User notification preferences

## 🧪 Testing

### Manual Testing
1. Test user authentication for all roles
2. Verify appointment booking and management
3. Check patient record creation and updates
4. Test billing and invoice generation
5. Validate report generation

### Database Testing
- Run database integrity checks
- Test backup and restore procedures
- Verify audit log functionality

## 🚀 Deployment

### Production Checklist
- [ ] Update database credentials
- [ ] Configure secure file permissions
- [ ] Enable HTTPS/SSL
- [ ] Set up regular database backups
- [ ] Configure error logging
- [ ] Test all critical functionalities

### Performance Optimization
- Enable PHP OPcache
- Configure database query optimization
- Implement caching strategies
- Optimize image and asset delivery

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write clear, descriptive commit messages
- Test thoroughly before submitting
- Update documentation as needed

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- Check the `designs/mockups/` directory for UI references
- Review database schema in `database/dentosys_db.sql`
- Examine code comments for implementation details

### Troubleshooting

**Common Issues:**
- **Database Connection Error**: Verify MySQL service is running and credentials are correct
- **Permission Denied**: Check file permissions on uploads directory
- **Session Issues**: Ensure session handling is properly configured
- **PHP Errors**: Check PHP error logs and enable error reporting during development

### Getting Help
- Check existing issues on GitHub
- Review the project documentation
- Contact the development team

## 🔄 Updates & Maintenance

### Regular Maintenance
- Database optimization and cleanup
- Security updates and patches
- Backup verification
- Performance monitoring

### Version History
- **v1.0.0**: Initial release with core functionality
- **v1.1.0**: Enhanced patient portal features
- **v1.2.0**: Improved billing and reporting system

## 🏥 About DentoSys

DentoSys was developed to address the specific needs of dental practices, combining modern web technologies with healthcare industry best practices. The system emphasizes security, usability, and compliance with healthcare data protection standards.

### Key Benefits
- **Improved Efficiency**: Streamlined workflows and automated processes
- **Better Patient Care**: Comprehensive patient records and communication tools
- **Financial Management**: Integrated billing and reporting capabilities
- **Scalability**: Designed to grow with your practice
- **Compliance**: Built with healthcare regulations in mind

---

**Created with ❤️ for dental practices worldwide**

For more information, visit our [project repository](https://github.com/masifhossain/dentosys) or contact our development team.