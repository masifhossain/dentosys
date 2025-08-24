<?php
/*****************************************************************
 * auth/register.php — Patient Registration Only
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

/* ───────── Process Patient Registration ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = $conn->real_escape_string(trim($_POST['email']));
    $pass1     = $_POST['password'];
    $pass2     = $_POST['password2'];
    $firstName = $conn->real_escape_string(trim($_POST['first_name']));
    $lastName  = $conn->real_escape_string(trim($_POST['last_name']));
    $phone     = $conn->real_escape_string(trim($_POST['phone']));
    $dob       = $_POST['dob'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Invalid e-mail address.');
    } elseif ($pass1 !== $pass2) {
        flash('Passwords do not match.');
    } elseif (strlen($pass1) < 6) {
        flash('Password must be at least 6 characters.');
    } elseif (empty($firstName) || empty($lastName)) {
        flash('First and last name are required.');
    } else {
        $exists = $conn->query("SELECT 1 FROM usertbl WHERE email='$email' LIMIT 1")->num_rows;
        if ($exists) {
            flash('E-mail already registered.');
        } else {
            $hash = password_hash($pass1, PASSWORD_BCRYPT);
            
            // Create user account with Patient role (role_id = 4)
            $stmt = $conn->prepare("INSERT INTO usertbl (email, password_hash, role_id) VALUES (?, ?, 4)");
            $stmt->bind_param('ss', $email, $hash);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Create patient record
                $stmt2 = $conn->prepare("INSERT INTO patient (first_name, last_name, email, phone, dob) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param('sssss', $firstName, $lastName, $email, $phone, $dob);
                
                if ($stmt2->execute()) {
                    flash('Patient account created successfully! Please log in.');
                    redirect('login.php');
                } else {
                    // Rollback user creation if patient creation fails
                    $conn->query("DELETE FROM usertbl WHERE user_id = $user_id");
                    flash('Error creating patient profile. Please try again.');
                }
            } else {
                flash('Database error: ' . $conn->error);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>DentoSys · Patient Registration</title>
  <link rel="stylesheet" href="../assets/css/framework.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --figma-primary: #0066CC;
      --figma-primary-dark: #0052A3;
      --figma-text-primary: #1F2937;
      --figma-text-secondary: #6B7280;
      --figma-font-heading: 'Poppins', sans-serif;
      --figma-font-body: 'Inter', sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
      font-family: var(--figma-font-body);
      padding: 20px;
    }
    
    .register-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 48px 40px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: relative;
      overflow: hidden;
    }
    
    .register-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #0066CC 0%, #10B981 100%);
    }
    
    .logo-section {
      text-align: center;
      margin-bottom: 32px;
    }
    
    .logo-section img {
      max-width: 180px;
      margin-bottom: 16px;
      filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    }
    
    .welcome-text {
      font-family: var(--figma-font-heading);
      font-size: 28px;
      font-weight: 600;
      color: var(--figma-text-primary);
      margin: 0 0 8px;
    }
    
    .welcome-subtitle {
      color: var(--figma-text-secondary);
      font-size: 16px;
      margin: 0 0 32px;
    }
    
    .form-row {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
    }
    
    .form-group {
      margin-bottom: 24px;
      flex: 1;
    }
    
    .form-label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: var(--figma-text-primary);
      margin-bottom: 8px;
    }
    
    .form-input {
      width: 100%;
      padding: 22px 24px;
      font-size: 18px;
      border: 2px solid #E5E7EB;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.9);
      transition: all 0.3s ease;
      font-family: var(--figma-font-body);
      box-sizing: border-box;
      min-height: 70px;
      display: block;
    }
    
    .form-input:focus {
      outline: none;
      border-color: var(--figma-primary);
      background: white;
      box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
      transform: translateY(-1px);
    }
    
    .register-btn {
      width: 100%;
      padding: 22px 24px;
      font-size: 18px;
      font-weight: 600;
      background: linear-gradient(135deg, var(--figma-primary) 0%, var(--figma-primary-dark) 100%);
      color: white;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 8px;
      position: relative;
      overflow: hidden;
      min-height: 70px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-sizing: border-box;
    }
    
    .register-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s ease;
    }
    
    .register-btn:hover::before {
      left: 100%;
    }
    
    .register-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 102, 204, 0.3);
    }
    
    .register-btn:active {
      transform: translateY(0);
    }
    
    .flash {
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-size: 14px;
      font-weight: 500;
      position: relative;
      animation: slideDown 0.3s ease;
    }
    
    .flash {
      background: rgba(239, 68, 68, 0.1);
      color: #991B1B;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .login-link {
      text-align: center;
      margin-top: 24px;
      padding-top: 24px;
      border-top: 1px solid #F3F4F6;
      color: var(--figma-text-secondary);
      font-size: 14px;
    }
    
    .login-link a {
      color: var(--figma-primary);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }
    
    .login-link a:hover {
      color: var(--figma-primary-dark);
    }
    
    .patient-info {
      background: rgba(59, 130, 246, 0.1);
      border: 1px solid rgba(59, 130, 246, 0.2);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
      font-size: 13px;
      color: #1E40AF;
    }
    
    .patient-info h4 {
      margin: 0 0 8px;
      font-size: 14px;
      font-weight: 600;
    }
    
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .floating-shapes {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: -1;
    }
    
    .floating-shapes div {
      position: absolute;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      animation: float 6s ease-in-out infinite;
    }
    
    .floating-shapes div:nth-child(1) {
      width: 80px;
      height: 80px;
      top: 20%;
      left: 10%;
      animation-delay: 0s;
    }
    
    .floating-shapes div:nth-child(2) {
      width: 120px;
      height: 120px;
      top: 70%;
      right: 10%;
      animation-delay: 2s;
    }
    
    .floating-shapes div:nth-child(3) {
      width: 60px;
      height: 60px;
      top: 40%;
      right: 20%;
      animation-delay: 4s;
    }
    
    @keyframes float {
      0%, 100% {
        transform: translateY(0px);
      }
      50% {
        transform: translateY(-20px);
      }
    }
  </style>
</head>
<body>
  <div class="floating-shapes">
    <div></div>
    <div></div>
    <div></div>
  </div>
  
  <div class="register-container">
    <div class="logo-section">
      <img src="/dentosys/assets/images/DentoSys_Logo.png" alt="DentoSys logo">
      <h1 class="welcome-text">Join DentoSys</h1>
      <p class="welcome-subtitle">Create your patient account</p>
    </div>
    
    <?= get_flash(); ?>
    
    <div class="patient-info">
      <h4>🦷 Patient Registration</h4>
      <strong>Note:</strong> This registration is for patients only. Staff accounts are created by administrators.
    </div>
    
    <form method="post">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name</label>
          <input type="text" name="first_name" class="form-input" 
                 placeholder="Enter your first name" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Last Name</label>
          <input type="text" name="last_name" class="form-input" 
                 placeholder="Enter your last name" required>
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-input" 
               placeholder="Enter your email address" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" class="form-input" 
               placeholder="Enter your phone number">
      </div>
      
      <div class="form-group">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="dob" class="form-input" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" 
               placeholder="Create a password (min. 6 characters)" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password2" class="form-input" 
               placeholder="Confirm your password" required>
      </div>
      
      <button type="submit" class="register-btn">
        Create Patient Account
      </button>
    </form>
    
    <div class="login-link">
      Already have an account? <a href="/dentosys/auth/patient_portal.php">Log in</a>
    </div>
  </div>
  
  <script>
    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
      const inputs = document.querySelectorAll('.form-input');
      
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
          this.parentElement.style.transform = 'scale(1)';
        });
      });
    });
  </script>
</body>
</html>