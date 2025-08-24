<?php
session_start();
require_once '../includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    }
    
    if (empty($dob)) {
        $errors[] = 'Date of birth is required.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($errors)) {
        $_SESSION['flash'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'error';
        header('Location: patient_portal.php');
        exit();
    }
    
    try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM UserTbl WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['flash'] = 'An account with this email address already exists. Please use the login form.';
            $_SESSION['flash_type'] = 'error';
            header('Location: patient_portal.php');
            exit();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Create user account (role_id 4 = Patient)
        $stmt = $conn->prepare("
            INSERT INTO UserTbl (email, password_hash, role_id, is_active, created_at) 
            VALUES (?, ?, 4, 1, NOW())
        ");
        $stmt->bind_param("ss", $email, $hashed_password);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user account");
        }
        
        $user_id = $conn->insert_id;
        
        // Create patient record
        $stmt = $conn->prepare("
            INSERT INTO Patient (first_name, last_name, email, phone, dob, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $dob);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create patient record");
        }
        
        // Log registration
        try {
            $log_stmt = $conn->prepare("
                INSERT INTO AuditLog (user_id, action, details, timestamp) 
                VALUES (?, 'Registration', 'New patient account created', NOW())
            ");
            $log_stmt->bind_param("i", $user_id);
            $log_stmt->execute();
        } catch (Exception $e) {
            // Continue even if logging fails
            error_log("Registration logging failed: " . $e->getMessage());
        }
        
        // Auto-login the new user
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['email'] = $email; // For compatibility
        $_SESSION['user_name'] = trim($first_name . ' ' . $last_name);
        $_SESSION['username'] = trim($first_name . ' ' . $last_name); // For compatibility
        $_SESSION['user_role'] = 4;
        $_SESSION['role'] = 4; // For compatibility
        $_SESSION['role_name'] = 'Patient';
        $_SESSION['login_time'] = time();
        
        // Set success message
        $_SESSION['flash'] = 'Welcome to DentoSys, ' . $first_name . '! Your patient account has been created successfully.';
        $_SESSION['flash_type'] = 'success';
        
        // Redirect to dashboard
        header('Location: ../pages/patients/dashboard.php');
        exit();
        
    } catch (Exception $e) {
        error_log("Patient registration error: " . $e->getMessage());
        
        // Handle duplicate entry error specifically
        if ($conn->errno == 1062) {
            $_SESSION['flash'] = 'An account with this email address already exists. Please use the login form.';
        } else {
            $_SESSION['flash'] = 'A system error occurred during registration. Please try again or contact support.';
        }
        
        $_SESSION['flash_type'] = 'error';
        header('Location: patient_portal.php');
        exit();
    }
} else {
    // Invalid request method
    $_SESSION['flash'] = 'Invalid request method.';
    $_SESSION['flash_type'] = 'error';
    header('Location: patient_portal.php');
    exit();
}
?>
