<?php
/*****************************************************************
 * auth/login.php — styled version with clinic logo
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

/* ───────── Process login POST ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $pass  = $_POST['password'];

    $res = $conn->query("SELECT * FROM UserTbl WHERE email='$email' AND is_active=1 LIMIT 1");
    if ($row = $res->fetch_assoc() and password_verify($pass, $row['password_hash'])) {
        /* successful login */
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['role']    = $row['role_id'];
        flash('Welcome back!');
        redirect('/dentosys/index.php');
    } else {
        flash('Invalid credentials.', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>DentoSys · Login</title>
  <link rel="stylesheet" href="/dentosys/assets/css/style.css">
  <style>
    body{display:flex;justify-content:center;align-items:center;height:100vh;background:#e9f3f7;font-family:Arial,Helvetica,sans-serif;margin:0}
    .login-card{background:#fff;border:1px solid #ccc;border-radius:8px;padding:30px 40px;width:340px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
    .login-card img{max-width:160px;margin:0 auto 20px;display:block}
    .login-card h2{margin:0 0 18px;text-align:center;font-weight:normal;color:#333}
    .login-card label{display:block;font-size:14px;margin-top:10px}
    .login-card input{width:100%;padding:8px 10px;margin-top:4px;border:1px solid #bbb;border-radius:4px}
    .login-card button{margin-top:18px;width:100%;padding:10px;border:none;border-radius:4px;background:#0077aa;color:#fff;font-size:15px;cursor:pointer}
    .login-card button:hover{background:#005f88}
    .flash{margin-bottom:10px;font-size:13px;padding:8px;border-radius:4px}
    .flash.error{background:#fce4e4;color:#c00;border:1px solid #f6bcbc}
    .flash.success{background:#e0f7e9;color:#117a32;border:1px solid #b4e4c7}
    .small-link{text-align:center;margin-top:12px;font-size:13px}
  </style>
</head>
<body>
  <div class="login-card">
    <img src="/dentosys/assets/images/DentoSys_Logo.png" alt="DentoSys logo">
    <h2>Sign&nbsp;In</h2>

    <?php if ($msg = get_flash()) echo $msg; ?>

    <form method="post">
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button type="submit">Login</button>
    </form>

    <div class="small-link">
      New here? <a href="register.php">Create an account</a>
    </div>
  </div>
</body>
</html>