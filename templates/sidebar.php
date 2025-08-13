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
    <a <?= $active('/dashboard.php');   ?> href="/pages/dashboard.php">Dashboard</a>
    <a <?= $active('/patients');        ?> href="/pages/patients/list.php">Patients</a>
    <a <?= $active('/appointments');    ?> href="/pages/appointments/calendar.php">Appointments</a>
    <a <?= $active('/records');         ?> href="/pages/records/list.php">Clinical&nbsp;Records</a>

    <?php if ($roleID !== 3): /* Receptionist hidden */ ?>
      <a <?= $active('/reports');       ?> href="/pages/reports/financial.php">Reports</a>
      <a <?= $active('/communications');?> href="/pages/communications/templates.php">Communications</a>
    <?php endif; ?>

    <?php if ($roleID === 1): /* Admin only */ ?>
      <a <?= $active('/settings');      ?> href="/pages/settings/clinic_info.php">Settings</a>
    <?php endif; ?>

    <hr style="border-color:#226;">
    <a href="/auth/logout.php">Logout</a>
  </nav>
</aside>