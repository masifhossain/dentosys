<?php
session_start();

// Display flash messages logic - only show staff-related messages
$flash_message = '';
if (!empty($_SESSION['flash'])) {
    $flash_type = $_SESSION['flash_type'] ?? 'info';
    $flash_context = $_SESSION['flash_context'] ?? '';
    
    // Only show flash messages that are for staff portal or have no context
    if ($flash_context === 'staff' || $flash_context === '') {
        $flash_message = "<div class='flash-message flash-{$flash_type}'>{$_SESSION['flash']}</div>";
    }
    
    // Clear flash messages after displaying
    unset($_SESSION['flash'], $_SESSION['flash_type'], $_SESSION['flash_context']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal - DentoSys Dental Clinic</title>
    <link rel="icon" type="image/png" href="../assets/images/DentoSys_Logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 500px;
        }

        .login-form-section {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-branding {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-branding::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="rgba(255,255,255,0.1)"><circle cx="20" cy="20" r="2"/><circle cx="80" cy="20" r="2"/><circle cx="20" cy="80" r="2"/><circle cx="80" cy="80" r="2"/><circle cx="50" cy="50" r="3"/></svg>');
            background-size: 50px 50px;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
        }

        .brand-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .staff-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .form-subtitle {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            background: #f8f9fa;
            transform: translateX(-3px);
        }

        .flash-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .flash-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .flash-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .flash-info {
            background: #eef;
            color: #33c;
            border: 1px solid #ccf;
        }

        .portal-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
            position: relative;
            z-index: 2;
        }

        .portal-info h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .portal-info ul {
            list-style: none;
            line-height: 1.6;
        }

        .portal-info li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .portal-info li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #fff;
            font-weight: bold;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .login-branding {
                display: none;
            }

            .login-form-section {
                padding: 2rem;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .login-button {
            background: #ccc;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form-section">
            <div class="form-header">
                <h1 class="form-title">Staff Portal</h1>
                <p class="form-subtitle">Access your professional dashboard</p>
            </div>

            <?php echo $flash_message; ?>

            <form method="POST" action="staff_login_process.php" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required placeholder="Enter your staff email">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required placeholder="Enter your password">
                </div>

                <button type="submit" class="login-button">
                    🔐 Access Staff Portal
                </button>
            </form>

            <div class="back-link">
                <a href="../index.php">← Back to Home</a>
            </div>
        </div>

        <div class="login-branding">
            <img src="../assets/images/DentoSys_Logo.png" alt="DentoSys Logo" class="brand-logo">
            <h2 class="brand-title">DentoSys</h2>
            <p class="brand-subtitle">Professional Dental Management</p>
            
            <div class="staff-badge">
                👨‍⚕️ Staff Access Only
            </div>

            <div class="portal-info">
                <h3>Staff Portal Features</h3>
                <ul>
                    <li>Patient Management</li>
                    <li>Appointment Scheduling</li>
                    <li>Clinical Records</li>
                    <li>Billing & Invoices</li>
                    <li>Reports & Analytics</li>
                    <li>System Administration</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.body.classList.add('loading');
        });

        // Auto-focus on email field
        document.getElementById('email').focus();
    </script>
</body>
</html>
