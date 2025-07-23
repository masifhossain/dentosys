<?php
/*****************************************************************
 * pages/settings/users.php
 * ---------------------------------------------------------------
 * Admin panel for managing staff / user accounts.
 *  • List users
 *  • Add user
 *  • Change role or activate / deactivate
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Admin-only gate ───────── */
if (!is_admin()) {
    flash('User management is restricted to administrators.');
    redirect('/dentosys/index.php');
}

/* --------------------------------------------------------------
 * 1. Fetch roles once for dropdowns
 * ------------------------------------------------------------ */
$rolesDDL = $conn->query("SELECT role_id, role_name FROM Role ORDER BY role_name");
$roleMap  = [];
while ($r = $rolesDDL->fetch_assoc()) {
    $roleMap[$r['role_id']] = $r['role_name'];
}

/* --------------------------------------------------------------
 * 2. Handle actions
 * ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---- Add new user ---- */
    if ($_POST['action'] === 'add') {
        $email   = $conn->real_escape_string(trim($_POST['email']));
        $pass    = $_POST['password'];
        $role_id = intval($_POST['role_id']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
            flash('Valid email and password are required.');
            redirect('users.php');
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
          "INSERT INTO UserTbl (email, password_hash, role_id)
           VALUES (?,?,?)"
        );
        $stmt->bind_param('ssi', $email, $hash, $role_id);

        if ($stmt->execute()) {
            flash('User added.');
        } else {
            flash('DB error: ' . $conn->error);
        }
        redirect('users.php');
    }

    /* ---- Update role or active flag ---- */
    if ($_POST['action'] === 'edit') {
        $uid     = intval($_POST['user_id']);
        $role_id = intval($_POST['role_id']);
        $active  = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $conn->prepare(
          "UPDATE UserTbl SET role_id = ?, is_active = ? WHERE user_id = ? LIMIT 1"
        );
        $stmt->bind_param('iii', $role_id, $active, $uid);
        $stmt->execute();
        flash('User updated.');
        redirect('users.php');
    }

    /* ---- Reset password ---- */
    if ($_POST['action'] === 'reset_pw') {
        $uid     = intval($_POST['user_id']);
        $newPass = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $conn->query(
          "UPDATE UserTbl SET password_hash = '$newPass' WHERE user_id = $uid LIMIT 1"
        );
        flash('Password reset.');
        redirect('users.php');
    }
}

/* --------------------------------------------------------------
 * 3. Fetch users list
 * ------------------------------------------------------------ */
$users = $conn->query(
  "SELECT u.*, r.role_name
   FROM UserTbl u
   JOIN Role r ON r.role_id = u.role_id
   ORDER BY u.user_id"
);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>User Management</h2>
  <?= get_flash(); ?>

  <!-- ADD USER -->
  <details style="margin-bottom:18px;">
    <summary style="cursor:pointer;font-weight:bold;">+ Add User</summary>
    <form method="post" style="margin-top:10px;max-width:480px;">
      <input type="hidden" name="action" value="add">

      <label>Email:<br>
        <input type="email" name="email" required style="width:100%;">
      </label><br><br>

      <label>Initial Password:<br>
        <input type="password" name="password" required style="width:100%;">
      </label><br><br>

      <label>Role:
        <select name="role_id" required>
          <?php foreach ($roleMap as $rid=>$rname): ?>
            <option value="<?= $rid; ?>"><?= htmlspecialchars($rname); ?></option>
          <?php endforeach; ?>
        </select>
      </label><br><br>

      <button type="submit">Create User</button>
    </form>
  </details>

  <!-- USERS TABLE -->
  <table>
    <thead>
      <tr>
        <th>#</th><th>Email</th><th>Role</th><th>Active</th>
        <th style="width:220px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($users->num_rows === 0): ?>
      <tr><td colspan="5">No users found.</td></tr>
    <?php else: $i=1; while ($u = $users->fetch_assoc()): ?>
      <tr>
        <td><?= $i++; ?></td>
        <td><?= htmlspecialchars($u['email']); ?></td>
        <!-- inline edit form -->
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" value="<?= $u['user_id']; ?>">
            <select name="role_id">
              <?php foreach ($roleMap as $rid=>$rname): ?>
                <option value="<?= $rid; ?>" <?= $rid==$u['role_id']?'selected':''; ?>>
                  <?= htmlspecialchars($rname); ?>
                </option>
              <?php endforeach; ?>
            </select>
        </td>
        <td style="text-align:center;">
            <input type="checkbox" name="is_active" value="1"
              <?= $u['is_active']?'checked':''; ?>>
        </td>
        <td>
            <button type="submit" style="padding:2px 6px;">Save</button>
          </form>

          <!-- password reset -->
          <form method="post" style="display:inline;margin-left:6px;">
            <input type="hidden" name="action" value="reset_pw">
            <input type="hidden" name="user_id" value="<?= $u['user_id']; ?>">
            <input type="password" name="new_password"
                   placeholder="New password" required>
            <button type="submit" style="padding:2px 6px;">Reset PW</button>
          </form>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>