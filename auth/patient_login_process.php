<?php
session_start();
require_once '../includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['flash'] = 'Please enter both email and password.';
        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_context'] = 'patient';
        header('Location: patient_portal.php');
        exit();
    }
    
    try {
        // Check if user exists and is a patient (role_id = 4)
        $stmt = $conn->prepare("
            SELECT user_id, email, password_hash, role_id, is_active 
            FROM UserTbl 
            WHERE email = ? AND role_id = 4 AND is_active = 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['flash'] = 'Invalid email or password. Only patients can access this portal.';
            $_SESSION['flash_type'] = 'error';
            $_SESSION['flash_context'] = 'patient';
            header('Location: patient_portal.php');
            exit();
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $_SESSION['flash'] = 'Invalid email or password.';
            $_SESSION['flash_type'] = 'error';
            $_SESSION['flash_context'] = 'patient';
            header('Location: patient_portal.php');
            exit();
        }
        
        // Get patient details
        $patient_stmt = $conn->prepare("
            SELECT first_name, last_name 
            FROM Patient 
            WHERE email = ?
        ");
        $patient_stmt->bind_param("s", $email);
        $patient_stmt->execute();
        $patient_result = $patient_stmt->get_result();
        $patient = $patient_result->fetch_assoc();
        
        $first_name = $patient['first_name'] ?? 'Patient';
        $last_name = $patient['last_name'] ?? '';
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['email'] = $user['email']; // For compatibility
        $_SESSION['user_name'] = trim($first_name . ' ' . $last_name);
        $_SESSION['username'] = trim($first_name . ' ' . $last_name); // For compatibility
        $_SESSION['user_role'] = $user['role_id'];
        $_SESSION['role'] = $user['role_id']; // For compatibility
        $_SESSION['role_name'] = 'Patient';
        $_SESSION['login_time'] = time();
        
        // Log successful login
        try {
            $log_stmt = $conn->prepare("
                INSERT INTO AuditLog (user_id, action, details, timestamp) 
                VALUES (?, 'Login', 'Patient portal login successful', NOW())
            ");
            $log_stmt->bind_param("i", $user['user_id']);
            $log_stmt->execute();
        } catch (Exception $e) {
            // Continue even if logging fails
            error_log("Login logging failed: " . $e->getMessage());
        }
        
        // Set success message
        $_SESSION['flash'] = 'Welcome back, ' . $first_name . '! You have successfully logged into the patient portal.';
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_context'] = 'patient';
        
        // Redirect to patient dashboard
        header('Location: ../pages/patients/dashboard.php');
        exit();
        
    } catch (Exception $e) {
        error_log("Patient login error: " . $e->getMessage());
        $_SESSION['flash'] = 'A system error occurred. Please try again or contact support.';
        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_context'] = 'patient';
        header('Location: patient_portal.php');
        exit();
    }
} else {
    // Invalid request method
    $_SESSION['flash'] = 'Invalid request method.';
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_context'] = 'patient';
    header('Location: patient_portal.php');
    exit();
}
?>
