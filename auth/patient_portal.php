<?php
session_start();

// Display flash messages logic - only show patient-related messages
$flash_message = '';
if (!empty($_SESSION['flash'])) {
    $flash_type = $_SESSION['flash_type'] ?? 'info';
    $flash_context = $_SESSION['flash_context'] ?? '';
    
    // Only show flash messages that are for patient portal or have no context
    if ($flash_context === 'patient' || $flash_context === '') {
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
    <title>Patient Portal - DentoSys Dental Clinic</title>
    <link rel="icon" type="image/png" href="../assets/images/DentoSys_Logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .portal-container {
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

        .portal-form-section {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .portal-branding {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .portal-branding::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="rgba(255,255,255,0.1)"><circle cx="25" cy="25" r="3"/><circle cx="75" cy="25" r="3"/><circle cx="25" cy="75" r="3"/><circle cx="75" cy="75" r="3"/><circle cx="50" cy="50" r="5"/></svg>');
            background-size: 40px 40px;
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
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

        .patient-badge {
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

        .form-tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 0.25rem;
            margin-bottom: 2rem;
        }

        .tab-button {
            flex: 1;
            padding: 0.75rem;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #7f8c8d;
        }

        .tab-button.active {
            background: white;
            color: #4facfe;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            border-color: #4facfe;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }

        .action-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
        }

        .action-button:active {
            transform: translateY(0);
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: #4facfe;
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

        .registration-note {
            background: #e6f7ff;
            border: 1px solid #91d5ff;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
            color: #096dd9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .portal-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .portal-branding {
                display: none;
            }

            .portal-form-section {
                padding: 2rem;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .action-button {
            background: #ccc;
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="portal-form-section">
            <div class="form-header">
                <h1 class="form-title">Patient Portal</h1>
                <p class="form-subtitle">Manage your dental care online</p>
            </div>

            <?php echo $flash_message; ?>

            <div class="form-tabs">
                <button type="button" class="tab-button active" onclick="switchTab('login')">Login</button>
                <button type="button" class="tab-button" onclick="switchTab('register')">Register</button>
            </div>

            <!-- Login Form -->
            <div class="form-section active" id="loginSection">
                <form method="POST" action="patient_login_process.php" id="loginForm">
                    <div class="form-group">
                        <label for="login_email" class="form-label">Email Address</label>
                        <input type="email" id="login_email" name="email" class="form-input" required placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="login_password" class="form-label">Password</label>
                        <input type="password" id="login_password" name="password" class="form-input" required placeholder="Enter your password">
                    </div>

                    <button type="submit" class="action-button">
                        🔐 Access Patient Portal
                    </button>
                </form>
            </div>

            <!-- Registration Form -->
            <div class="form-section" id="registerSection">
                <form method="POST" action="patient_register_process.php" id="registerForm">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" required placeholder="Enter your first name">
                    </div>

                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" required placeholder="Enter your last name">
                    </div>

                    <div class="form-group">
                        <label for="register_email" class="form-label">Email Address</label>
                        <input type="email" id="register_email" name="email" class="form-input" required placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-input" required placeholder="Enter your phone number">
                    </div>

                    <div class="form-group">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="register_password" class="form-label">Password</label>
                        <input type="password" id="register_password" name="password" class="form-input" required placeholder="Create a password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required placeholder="Confirm your password">
                    </div>

                    <button type="submit" class="action-button">
                        ✨ Create Patient Account
                    </button>
                </form>

                <div class="registration-note">
                    <p><strong>New Patient Registration</strong><br>
                    Create your account to access appointments, records, and billing information.</p>
                </div>
            </div>

            <div class="back-link">
                <a href="../index.php">← Back to Home</a>
            </div>
        </div>

        <div class="portal-branding">
            <img src="../assets/images/DentoSys_Logo.png" alt="DentoSys Logo" class="brand-logo">
            <h2 class="brand-title">DentoSys</h2>
            <p class="brand-subtitle">Your Personal Dental Portal</p>
            
            <div class="patient-badge">
                👤 Patient Access
            </div>

            <div class="portal-info">
                <h3>Patient Portal Features</h3>
                <ul>
                    <li>Book Appointments</li>
                    <li>View Medical Records</li>
                    <li>Check Prescriptions</li>
                    <li>Manage Billing</li>
                    <li>Update Profile</li>
                    <li>24/7 Access</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Update form sections
            document.querySelectorAll('.form-section').forEach(section => section.classList.remove('active'));
            document.getElementById(tab + 'Section').classList.add('active');

            // Focus on first input
            setTimeout(() => {
                const activeSection = document.getElementById(tab + 'Section');
                const firstInput = activeSection.querySelector('input');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        // Form submission with loading state
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                document.body.classList.add('loading');
            });
        });

        // Password confirmation validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('register_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
                document.getElementById('confirm_password').focus();
                document.body.classList.remove('loading');
            }
        });

        // Auto-focus on email field
        document.getElementById('login_email').focus();
    </script>
</body>
</html>
