/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: dentosys_db
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB
--
-- DentoSys v2.0 - Complete Dental Practice Management System
-- ============================================================
-- Updated: August 17, 2025
-- 
-- NEW FEATURES INCLUDED:
-- =====================
-- ✅ Prescriptions Management System
--    - Complete medication tracking and management
--    - Status workflow (Active/Completed/Cancelled)
--    - Integration with appointments and patient records
--
-- ✅ Insurance Claims Processing
--    - Full claims lifecycle management
--    - Multiple insurance provider support
--    - Financial tracking and KPI reporting
--
-- ✅ Enhanced Integration Management
--    - Modern API management system
--    - Support for payment gateways, email, SMS, calendar sync
--    - Configuration management with JSON storage
--
-- ✅ Figma-Inspired UI Design System
--    - Modern responsive design framework
--    - Enhanced user experience components
--    - Professional dental practice interface
--
-- EXISTING CORE FEATURES:
-- ======================
-- • Patient Management • Appointment Scheduling • Billing & Invoicing
-- • Clinical Records • Communications • Reports & Analytics
-- • User Management • Role-Based Access Control • Audit Logging

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `Appointment`
--

DROP TABLE IF EXISTS `Appointment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Appointment` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) NOT NULL,
  `appointment_dt` datetime NOT NULL,
  `status` enum('Scheduled','Pending','Approved','Cancelled','Complete') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`),
  CONSTRAINT `Appointment_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`patient_id`),
  CONSTRAINT `Appointment_ibfk_2` FOREIGN KEY (`dentist_id`) REFERENCES `Dentist` (`dentist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Appointment`
--

LOCK TABLES `Appointment` WRITE;
/*!40000 ALTER TABLE `Appointment` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `Appointment` VALUES
(1,1,1,'2025-08-16 16:22:59','Approved','Annual check-up and clean.'),
(2,2,2,'2025-08-18 16:22:59','Approved','Orthodontic consultation.'),
(3,3,1,'2025-08-03 16:22:59','Complete','Filling for molar.'),
(4,1,1,'2025-08-20 16:22:59','Pending','Follow-up appointment request.'),
(5,4,2,'2025-08-11 16:22:59','Cancelled','Patient cancelled due to conflict.');
/*!40000 ALTER TABLE `Appointment` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `AuditLog`
--

DROP TABLE IF EXISTS `AuditLog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `AuditLog` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AuditLog`
--

LOCK TABLES `AuditLog` WRITE;
/*!40000 ALTER TABLE `AuditLog` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `AuditLog` VALUES
(1,1,'User logout','2025-08-13 06:10:16');
/*!40000 ALTER TABLE `AuditLog` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `Dentist`
--

DROP TABLE IF EXISTS `Dentist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Dentist` (
  `dentist_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`dentist_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `Dentist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `UserTbl` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Dentist`
--

LOCK TABLES `Dentist` WRITE;
/*!40000 ALTER TABLE `Dentist` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `Dentist` VALUES
(1,2,'General Dentistry'),
(2,3,'Orthodontics');
/*!40000 ALTER TABLE `Dentist` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `Feedback`
--

DROP TABLE IF EXISTS `Feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('New','Reviewed') DEFAULT 'New',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `Feedback_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Feedback`
--

LOCK TABLES `Feedback` WRITE;
/*!40000 ALTER TABLE `Feedback` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `Feedback` VALUES
(1,3,5,'Dr. Williams was fantastic. The procedure was painless and quick!','Reviewed','2025-08-13 06:22:59'),
(2,5,4,'The new online booking system is very convenient.','New','2025-08-13 06:22:59');
/*!40000 ALTER TABLE `Feedback` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `FileUploads`
--

DROP TABLE IF EXISTS `FileUploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `FileUploads` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `stored_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`file_id`),
  KEY `patient_id` (`patient_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `FileUploads_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`patient_id`),
  CONSTRAINT `FileUploads_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `UserTbl` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FileUploads`
--

LOCK TABLES `FileUploads` WRITE;
/*!40000 ALTER TABLE `FileUploads` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `FileUploads` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `Invoice`
--

DROP TABLE IF EXISTS `Invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Invoice` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `issued_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  PRIMARY KEY (`invoice_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `Invoice_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Invoice`
--

LOCK TABLES `Invoice` WRITE;
/*!40000 ALTER TABLE `Invoice` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `Invoice` VALUES
(1,3,'2025-08-03',250.00,'Paid'),
(2,1,'2025-07-13',150.00,'Unpaid');
/*!40000 ALTER TABLE `Invoice` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `MessageTemplate`
--

DROP TABLE IF EXISTS `MessageTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `MessageTemplate` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` enum('Email','SMS','Letter','Postcard','Flow') NOT NULL,
  `title` varchar(120) NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MessageTemplate`
--

LOCK TABLES `MessageTemplate` WRITE;
/*!40000 ALTER TABLE `MessageTemplate` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `MessageTemplate` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `Patient`
--

DROP TABLE IF EXISTS `Patient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Patient` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Patient`
--

LOCK TABLES `Patient` WRITE;
/*!40000 ALTER TABLE `Patient` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `Patient` VALUES
(1,'John','Smith','1985-05-20','john.smith@example.com','0412345678','123 Fake St, Sydney, NSW 2000','2025-08-13 06:22:59'),
(2,'Emily','Jones','1992-11-30','emily.j@example.com','0423456789','456 Sample Ave, Sydney, NSW 2000','2025-08-13 06:22:59'),
(3,'Michael','Brown','1978-01-15','michael.brown@example.com','0434567890','789 Test Rd, Sydney, NSW 2000','2025-08-13 06:22:59'),
(4,'Jessica','Davis','2001-07-22','jess.davis@example.com','0445678901','101 Example Blvd, Sydney, NSW 2000','2025-08-13 06:22:59'),
(5,'David','Wilson','1995-03-10','david.wilson@example.com','0456789012','210 Mockingbird Ln, Sydney, NSW 2000','2025-08-13 06:22:59');
/*!40000 ALTER TABLE `Patient` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `Payment`
--

DROP TABLE IF EXISTS `Payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Payment` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `paid_date` date NOT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `Payment_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `Invoice` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Payment`
--

LOCK TABLES `Payment` WRITE;
/*!40000 ALTER TABLE `Payment` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `Payment` VALUES
(1,1,250.00,'Credit Card',NULL,'2025-08-04');
/*!40000 ALTER TABLE `Payment` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `Role`
--

DROP TABLE IF EXISTS `Role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Role` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Role`
--

LOCK TABLES `Role` WRITE;
/*!40000 ALTER TABLE `Role` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `Role` VALUES
(1,'Admin'),
(2,'Dentist'),
(3,'Receptionist');
/*!40000 ALTER TABLE `Role` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `SupportTicket`
--

DROP TABLE IF EXISTS `SupportTicket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `SupportTicket` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(120) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Open','Closed') DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `SupportTicket_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `UserTbl` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SupportTicket`
--

LOCK TABLES `SupportTicket` WRITE;
/*!40000 ALTER TABLE `SupportTicket` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `SupportTicket` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `Treatment`
--

DROP TABLE IF EXISTS `Treatment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Treatment` (
  `treatment_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  PRIMARY KEY (`treatment_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `Treatment_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `Appointment` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Treatment`
--

LOCK TABLES `Treatment` WRITE;
/*!40000 ALTER TABLE `Treatment` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `Treatment` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `UserTbl`
--

DROP TABLE IF EXISTS `UserTbl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserTbl` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `UserTbl_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `Role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserTbl`
--

LOCK TABLES `UserTbl` WRITE;
/*!40000 ALTER TABLE `UserTbl` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `UserTbl` VALUES
(1,'admin@dentosys.local','$2y$10$E.V2i0eCg0VvN9aZ3uBwA.xY8QeX2s7z3R/u5i4o6k9q7j8L9m0O',1,1,'2025-08-13 06:22:59'),
(2,'s.williams@dentosys.local','$2y$10$F.A1b2c3D4e5F6g7H8i9j.kL/mNopQrStUvWxYzAbCdEfGhIjKlM',2,1,'2025-08-13 06:22:59'),
(3,'j.chen@dentosys.local','$2y$10$F.A1b2c3D4e5F6g7H8i9j.kL/mNopQrStUvWxYzAbCdEfGhIjKlM',2,1,'2025-08-13 06:22:59'),
(4,'reception@dentosys.local','$2y$10$G.h1i2j3k4l5m6n7o8p9q.rStUvWxYzAbCdEfGhIjKlMnOpQrStU',3,1,'2025-08-13 06:22:59');
/*!40000 ALTER TABLE `UserTbl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Prescriptions`
-- NEW: Prescription management system
--

DROP TABLE IF EXISTS `Prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Prescriptions` (
  `prescription_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `prescribed_date` datetime DEFAULT current_timestamp(),
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`prescription_id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `Prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`patient_id`),
  CONSTRAINT `Prescriptions_ibfk_2` FOREIGN KEY (`dentist_id`) REFERENCES `Dentist` (`dentist_id`),
  CONSTRAINT `Prescriptions_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `Appointment` (`appointment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Prescriptions`
--

LOCK TABLES `Prescriptions` WRITE;
/*!40000 ALTER TABLE `Prescriptions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `Prescriptions` VALUES
(1,1,1,1,'Amoxicillin 500mg','500mg','3 times daily','7 days','Take with food to avoid stomach upset. Complete full course.','Active','2025-08-17 10:00:00','2025-08-17 10:00:00','2025-08-17 10:00:00'),
(2,2,2,2,'Ibuprofen 400mg','400mg','2 times daily','5 days','For pain and inflammation relief. Take after meals.','Active','2025-08-17 11:30:00','2025-08-17 11:30:00','2025-08-17 11:30:00'),
(3,3,1,NULL,'Chlorhexidine Mouthwash 0.2%','10ml','Twice daily','14 days','Rinse for 30 seconds, do not swallow. Use after tooth extraction.','Completed','2025-08-15 14:15:00','2025-08-15 14:15:00','2025-08-17 09:00:00');
/*!40000 ALTER TABLE `Prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InsuranceClaims`
-- NEW: Insurance claims management system
--

DROP TABLE IF EXISTS `InsuranceClaims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `InsuranceClaims` (
  `claim_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `claim_number` varchar(50) NOT NULL,
  `insurance_provider` varchar(255) NOT NULL,
  `policy_number` varchar(100) NOT NULL,
  `claim_amount` decimal(10,2) NOT NULL,
  `approved_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Submitted','Under Review','Approved','Rejected','Paid') DEFAULT 'Submitted',
  `submission_date` datetime DEFAULT current_timestamp(),
  `response_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`claim_id`),
  UNIQUE KEY `claim_number` (`claim_number`),
  KEY `patient_id` (`patient_id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `InsuranceClaims_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`patient_id`),
  CONSTRAINT `InsuranceClaims_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `Invoice` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InsuranceClaims`
--

LOCK TABLES `InsuranceClaims` WRITE;
/*!40000 ALTER TABLE `InsuranceClaims` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `InsuranceClaims` VALUES
(1,1,1,'CLM-2025-001','MediBank Private','POL123456789',350.00,315.00,'Approved','2025-08-16 09:00:00','2025-08-17 14:30:00','Routine cleaning and check-up approved at 90% coverage','2025-08-16 09:00:00','2025-08-17 14:30:00'),
(2,2,2,'CLM-2025-002','Bupa Health Insurance','POL987654321',1200.00,NULL,'Under Review','2025-08-17 11:15:00',NULL,'Orthodontic consultation claim pending review','2025-08-17 11:15:00','2025-08-17 11:15:00'),
(3,3,3,'CLM-2025-003','HCF Health Insurance','POL456789123',800.00,600.00,'Paid','2025-08-15 10:30:00','2025-08-16 16:45:00','Emergency extraction approved and paid','2025-08-15 10:30:00','2025-08-16 16:45:00');
/*!40000 ALTER TABLE `InsuranceClaims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `IntegrationSettings`
-- NEW: Enhanced integration management system
--

DROP TABLE IF EXISTS `IntegrationSettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `IntegrationSettings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `integration_type` enum('payment_gateway','email_service','sms_service','calendar_sync','backup_service') NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `webhook_url` varchar(255) DEFAULT NULL,
  `is_active` boolean DEFAULT FALSE,
  `test_mode` boolean DEFAULT TRUE,
  `configuration` json DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `unique_integration` (`integration_type`, `provider_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `IntegrationSettings`
--

LOCK TABLES `IntegrationSettings` WRITE;
/*!40000 ALTER TABLE `IntegrationSettings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `IntegrationSettings` VALUES
(1,'payment_gateway','Stripe','sk_test_xxxxxxxxxxxxxxxxxxxx','','https://dentosys.local/webhook/stripe',1,1,'{"currency": "AUD", "capture_method": "automatic"}','2025-08-17 12:00:00','2025-08-17 12:00:00'),
(2,'email_service','SendGrid','SG.xxxxxxxxxxxxxxxxxxxx','','',1,0,'{"from_email": "noreply@dentosys.local", "from_name": "DentoSys Clinic"}','2025-08-17 12:15:00','2025-08-17 12:15:00'),
(3,'sms_service','Twilio','ACxxxxxxxxxxxxxxxxxxxx','xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx','',0,1,'{"phone_number": "+61412345678"}','2025-08-17 12:30:00','2025-08-17 12:30:00'),
(4,'backup_service','AWS S3','AKIAXXXXXXXXXXXXXXXXX','xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx','',1,0,'{"bucket_name": "dentosys-backups", "region": "ap-southeast-2"}','2025-08-17 12:45:00','2025-08-17 12:45:00');
/*!40000 ALTER TABLE `IntegrationSettings` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-08-17 17:40:00
-- Updated with new DentoSys v2.0 features:
-- - Prescriptions management system
-- - Insurance claims processing  
-- - Enhanced integrations with multiple providers
-- - Figma-inspired UI enhancements
-- Complete dental practice management solution
