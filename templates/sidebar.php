<?php
/**
 * templates/sidebar.php  (role-aware + user email / role display)
 * ---------------------------------------------------------------
 * Role IDs (default seed):
 *   1 = Admin  – full access
 *   2 = Dentist – no Settings
 *   3 = Receptionist – no Reports, Communications, Settings
 *
 * Requires: $conn from db.php  (already included before sidebar.php is loaded)
 */
if (!defined('BASE_PATH')) { exit; }   // safety

/* ---- 1.  Fetch current user’s email & role name -------------------- */
$userEmail = $roleName = '';
$roleID    = 0;
if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $qry = $conn->query(
        "SELECT u.email, r.role_name, r.role_id
         FROM UserTbl u
         JOIN Role   r ON r.role_id = u.role_id
         WHERE u.user_id = $uid LIMIT 1"
    );
    if ($row = $qry->fetch_assoc()) {
        $userEmail = $row['email'];
        $roleName  = $row['role_name'];
        $roleID    = (int) $row['role_id'];
    }
}

/* ---- 2.  Helper to bold the active link ---------------------------- */
$current = $_SERVER['REQUEST_URI'];
$active  = fn(string $sub) =>
    (strpos($current, $sub) !== false) ? 'style="font-weight:bold;"' : '';
?>
<aside class="sidebar">
  <h3 style="margin-top:0;">DentoSys</h3>

  <p style="font-size:12px;line-height:1.35em;">
    Logged&nbsp;in:<br>
    <strong><?= htmlspecialchars($userEmail); ?></strong><br>
    <em><?= htmlspecialchars($roleName); ?></em>
  </p>

  <nav>
    <?php if ($roleID === 4): /* Patient portal */ ?>
      <a <?= $active('/dashboard');       ?> href="/dentosys/pages/patients/my_profile.php">👤 My Profile</a>
      <a <?= $active('/appointments');    ?> href="/dentosys/pages/patients/my_appointments.php">📅 My Appointments</a>
      <a <?= $active('/records');         ?> href="/dentosys/pages/patients/my_records.php">📋 My Records</a>
      <a <?= $active('/prescriptions');   ?> href="/dentosys/pages/patients/my_prescriptions.php">💊 My Prescriptions</a>
      <a <?= $active('/billing');         ?> href="/dentosys/pages/patients/my_billing.php">💰 My Bills</a>
      <a <?= $active('/book');            ?> href="/dentosys/pages/patients/book_appointment.php">➕ Book Appointment</a>
    <?php else: /* Staff portal */ ?>
      <a <?= $active('/dashboard.php');   ?> href="/dentosys/pages/dashboard.php">🏠 Dashboard</a>
      <a <?= $active('/patients');        ?> href="/dentosys/pages/patients/list.php">🧑‍⚕️ Patients</a>
      <a <?= $active('/appointments');    ?> href="/dentosys/pages/appointments/calendar.php">📅 Appointments</a>
      <a <?= $active('/records');         ?> href="/dentosys/pages/records/list.php">📋 Clinical Records</a>
      <a <?= $active('/billing');         ?> href="/dentosys/pages/billing/invoices.php">💰 Billing</a>

      <?php if ($roleID !== 3): /* Receptionist hidden */ ?>
        <a <?= $active('/reports');       ?> href="/dentosys/pages/reports/financial.php">📊 Reports</a>
        <a <?= $active('/communications');?> href="/dentosys/pages/communications/templates.php">💬 Communications</a>
      <?php endif; ?>

      <?php if ($roleID === 1): /* Admin only */ ?>
        <a <?= $active('/settings');      ?> href="/dentosys/pages/settings/index.php">⚙️ Settings</a>
        <a <?= $active('/users');         ?> href="/dentosys/pages/settings/users.php">👥 Staff Management</a>
      <?php endif; ?>
    <?php endif; ?>

    <a <?= $active('/help');            ?> href="/dentosys/pages/help.php">❓ Help & Support</a>

    <hr style="border-color:#226;">
    <a href="/dentosys/auth/logout.php">🚪 Logout</a>
  </nav>
</aside>