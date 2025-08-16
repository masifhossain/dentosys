# DentoSys

DentoSys is a web-based application designed to help dental businesses digitize and streamline their operations. Built with PHP, HTML, JavaScripy (maybe) and CSS, and utilizing a MariaDB database, DentoSys aims to provide an efficient solution for managing various aspects of a dental practice.

## Features

* **Patient Management:** Securely store and manage patient information.

* **Appointment Scheduling:** Efficiently schedule, view, and modify patient appointments.

* **Treatment Records:** Keep detailed records of treatments and procedures.

* **Billing & Invoicing:** Generate and manage invoices for services rendered.

* **Reporting:** Basic reporting functionalities to track business performance.

* **User Authentication:** Secure login for different roles within the practice.

## Technologies Used

* **Backend:** PHP

* **Frontend:** HTML, CSS, JS (maybe)

* **Database:** MariaDB

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

You will need the following installed on your system:

* **PHP:** Version 7.4 or higher.

* **MariaDB:** Version 10.x or higher.

### Manual Installation Steps

1.  **Clone the Repository:**

    ```
    git clone https://github.com/masifhossain/dentosys
    cd DentoSys
    ```

2.  **Database Setup:**

    * Log in to your MariaDB server using `mariadb -u root -p`.

    * Create a new database for DentoSys:

        ```
        CREATE DATABASE dentosys_db;
        ```

    * Import the initial database schema (and any seed data) from the `database/schema.sql` file (or similar, depending on your project structure):

        ```
        mariadb -u your_db_user -p dentosys_db < database/dentosys_db.sql
        ```

3.  **Configure Database Connection:**

    * Locate the database configuration file (`includes/db.php`).

    * Update the credentials to match your MariaDB setup:

        ```
        // Example 
        define('DB_HOST', 'localhost');
        define('DB_USER', 'your_db_user');
        define('DB_PASS', 'your_db_password');
        define('DB_NAME', 'dentosys_db');
        ```

4.  **Run the PHP Development Server:**

    * Navigate to the root directory of the `DentoSys` project in your terminal.

    * Start the PHP built-in web server:

        ```
        php -S localhost:8000
        ```

5.  **Access the Application:**

    * Open your web browser and navigate to `http://localhost:8000`.

