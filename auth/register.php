<?php
/*****************************************************************
 * auth/register.php — styled version with clinic logo
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

/* ───────── Required roles map (ensure table Role is seeded) ───────── */
$ROLE_MAP = [
  1 => 'Admin',
  2 => 'Dentist',
  3 => 'Receptionist'
];
/* hide Admin option if an Admin already exists */
$adminExists = $conn->query("SELECT 1 FROM usertbl WHERE role_id=1 LIMIT 1")->num_rows;
if ($adminExists) unset($ROLE_MAP[1]);

/* ───────── Process registration ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = $conn->real_escape_string(trim($_POST['email']));
    $pass1   = $_POST['password'];
    $pass2   = $_POST['password2'];
    $role_id = intval($_POST['role_id']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Invalid e-mail address.','error');
    } elseif ($pass1 !== $pass2) {
        flash('Passwords do not match.','error');
    } elseif (!isset($ROLE_MAP[$role_id])) {
        flash('Invalid role selected.','error');
    } else {
        $exists = $conn->query("SELECT 1 FROM usertbl WHERE email='$email' LIMIT 1")->num_rows;
        if ($exists) {
            flash('E-mail already registered.','error');
        } else {
            $hash = password_hash($pass1, PASSWORD_BCRYPT);
            $stmt = $conn->prepare(
              "INSERT INTO usertbl (email,password_hash,role_id) VALUES (?,?,?)"
            );
            $stmt->bind_param('ssi', $email, $hash, $role_id);
            if ($stmt->execute()) {
                flash('Account created — please log in.','success');
                redirect('login.php');
            } else {
                flash('Database error: '.$conn->error,'error');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>DentoSys · Register</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    body{display:flex;justify-content:center;align-items:center;height:100vh;background:#e9f3f7;font-family:Arial,Helvetica,sans-serif;margin:0}
    .card{background:#fff;border:1px solid #ccc;border-radius:8px;padding:28px 38px;width:380px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
    .card img{max-width:160px;margin:0 auto 18px;display:block}
    .card h2{margin:0 0 16px;text-align:center;font-weight:normal;color:#333}
    .card label{display:block;font-size:14px;margin-top:10px}
    .card input,.card select{width:100%;padding:8px 10px;margin-top:4px;border:1px solid #bbb;border-radius:4px}
    .card button{margin-top:18px;width:100%;padding:10px;border:none;border-radius:4px;background:#0077aa;color:#fff;font-size:15px;cursor:pointer}
    .card button:hover{background:#005f88}
    .small-link{text-align:center;margin-top:12px;font-size:13px}
    .flash{margin-bottom:10px;font-size:13px;padding:8px;border-radius:4px}
    .flash.error{background:#fce4e4;color:#c00;border:1px solid #f6bcbc}
    .flash.success{background:#e0f7e9;color:#117a32;border:1px solid #b4e4c7}
  </style>
</head>
<body>
  <div class="card">
    <img src="/assets/images/DentoSys_Logo.png" alt="DentoSys logo">
    <h2>Create&nbsp;Account</h2>

    <?php if ($msg = get_flash()) echo $msg; ?>

    <form method="post">
      <label>Email
        <input type="email" name="email" required>
      </label>

      <label>Password
        <input type="password" name="password" required>
      </label>

      <label>Repeat Password
        <input type="password" name="password2" required>
      </label>

      <label>Role
        <select name="role_id" required>
          <?php foreach ($ROLE_MAP as $id=>$name): ?>
            <option value="<?= $id; ?>"><?= $name; ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <button type="submit">Register</button>
    </form>

    <div class="small-link">
      Already have an account? <a href="login.php">Log in</a>
    </div>
  </div>
</body>
</html>