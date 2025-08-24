<?php
/*****************************************************************
 * auth/staff_login_process.php
 * ---------------------------------------------------------------
 * Handles staff portal login authentication
 * Only allows Admin, Dentist, and Receptionist roles
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dentosys/auth/staff_login.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['flash'] = 'Please enter both email and password.';
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_context'] = 'staff';
    redirect('/dentosys/auth/staff_login.php');
}

// Query for staff users only (roles 1, 2, 3)
$stmt = $conn->prepare("
    SELECT user_id, email, password_hash, role_id, is_active 
    FROM UserTbl 
    WHERE email = ? AND role_id IN (1, 2, 3) AND is_active = 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = 'Invalid credentials or access denied. Staff portal is for authorized personnel only.';
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_context'] = 'staff';
    redirect('/dentosys/auth/staff_login.php');
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['flash'] = 'Invalid credentials. Please check your email and password.';
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_context'] = 'staff';
    redirect('/dentosys/auth/staff_login.php');
}

// Successful login - set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role_id'];

// Extract and cache first name from email
$emailPart = explode('@', $user['email'])[0];
$nameParts = explode('.', $emailPart);
$firstName = ucfirst($nameParts[0]);

// Handle special cases
if ($firstName === 'Admin') {
    $firstName = 'Administrator';
} elseif ($firstName === 'Reception') {
    $firstName = 'Receptionist';
} elseif ($firstName === 'S' && isset($nameParts[1])) {
    // Handle 's.williams' format
    $firstName = ucfirst($nameParts[1]);
} elseif ($firstName === 'J' && isset($nameParts[1])) {
    // Handle 'j.chen' format  
    $firstName = ucfirst($nameParts[1]);
}

$_SESSION['first_name'] = $firstName;

// Role-based redirect
switch ($user['role_id']) {
    case 1: // Admin
        $_SESSION['flash'] = 'Welcome back, Administrator!';
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_context'] = 'staff';
        redirect('/dentosys/pages/dashboard.php');
        break;
    case 2: // Dentist
        $_SESSION['flash'] = 'Welcome back, Doctor!';
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_context'] = 'staff';
        redirect('/dentosys/pages/dashboard.php');
        break;
    case 3: // Receptionist
        $_SESSION['flash'] = 'Welcome back!';
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_context'] = 'staff';
        redirect('/dentosys/pages/dashboard.php');
        break;
    default:
        $_SESSION['flash'] = 'Access denied. Invalid user role.';
        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_context'] = 'staff';
        redirect('/dentosys/auth/staff_login.php');
}
?>
