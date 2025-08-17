<?php
/*****************************************************************
 * auth/login_enhanced.php — Modern Figma-inspired login
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

/* ───────── Process login POST ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $pass  = $_POST['password'];

    $res = $conn->query("SELECT * FROM usertbl WHERE email='$email' AND is_active=1 LIMIT 1");
    if ($row = $res->fetch_assoc() and password_verify($pass, $row['password_hash'])) {
        /* successful login */
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['role']    = $row['role_id'];
        $_SESSION['username'] = $row['username'] ?? $row['email'];
        flash('Welcome back!');
        redirect('/pages/dashboard.php');
    } else {
        flash('Invalid credentials.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>DentoSys · Sign In</title>
  <link rel="stylesheet" href="/assets/css/framework.css">
  <link rel="stylesheet" href="/assets/css/figma-enhanced.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
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
    
    .login-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 48px 40px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: relative;
      overflow: hidden;
    }
    
    .login-container::before {
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
    
    .form-group {
      margin-bottom: 24px;
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
      padding: 16px;
      font-size: 16px;
      border: 2px solid #E5E7EB;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.8);
      transition: all 0.3s ease;
      font-family: var(--figma-font-body);
      box-sizing: border-box;
    }
    
    .form-input:focus {
      outline: none;
      border-color: var(--figma-primary);
      background: white;
      box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
      transform: translateY(-1px);
    }
    
    .login-btn {
      width: 100%;
      padding: 16px;
      font-size: 16px;
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
    }
    
    .login-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s ease;
    }
    
    .login-btn:hover::before {
      left: 100%;
    }
    
    .login-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 102, 204, 0.3);
    }
    
    .login-btn:active {
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
    
    .flash.error {
      background: rgba(239, 68, 68, 0.1);
      color: #991B1B;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .flash.success {
      background: rgba(16, 185, 129, 0.1);
      color: #065F46;
      border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .register-link {
      text-align: center;
      margin-top: 24px;
      padding-top: 24px;
      border-top: 1px solid #F3F4F6;
      color: var(--figma-text-secondary);
      font-size: 14px;
    }
    
    .register-link a {
      color: var(--figma-primary);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s ease;
    }
    
    .register-link a:hover {
      color: var(--figma-primary-dark);
    }
    
    .demo-credentials {
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.2);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
      font-size: 13px;
      color: #065F46;
    }
    
    .demo-credentials h4 {
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
    
    @media (max-width: 480px) {
      .login-container {
        padding: 32px 24px;
        margin: 20px;
      }
      
      .welcome-text {
        font-size: 24px;
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
  
  <div class="login-container">
    <div class="logo-section">
      <img src="/assets/images/DentoSys_Logo.png" alt="DentoSys logo">
      <h1 class="welcome-text">Welcome Back</h1>
      <p class="welcome-subtitle">Sign in to your dental practice</p>
    </div>
    
    <?= get_flash(); ?>
    
    <div class="demo-credentials">
      <h4>🚀 Demo Credentials</h4>
      <strong>Email:</strong> admin@dentosys.local<br>
      <strong>Password:</strong> password
    </div>
    
    <form method="post">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-input" 
               placeholder="Enter your email" 
               value="admin@dentosys.local" required>
      </div>
      
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" 
               placeholder="Enter your password" 
               value="password" required>
      </div>
      
      <button type="submit" class="login-btn">
        Sign In to DentoSys
      </button>
    </form>
    
    <div class="register-link">
      Don't have an account? <a href="register.php">Create one here</a>
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
      
      // Auto-fill demo credentials on load
      const emailInput = document.querySelector('input[name="email"]');
      const passwordInput = document.querySelector('input[name="password"]');
      
      if (emailInput && !emailInput.value) {
        emailInput.value = 'admin@dentosys.local';
      }
      if (passwordInput && !passwordInput.value) {
        passwordInput.value = 'password';
      }
    });
  </script>
</body>
</html>
