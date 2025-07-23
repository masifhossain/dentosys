<?php
/*****************************************************************
 * pages/patients/list.php
 * ---------------------------------------------------------------
 * Displays all patients, with simple search and “Add New” link.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Simple search by name / email / phone ───────── */
$where = '';
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $esc = $conn->real_escape_string($search);
    $where = "WHERE CONCAT(first_name,' ',last_name) LIKE '%$esc%'
              OR email LIKE '%$esc%'
              OR phone LIKE '%$esc%'";
}

$sql = "SELECT * FROM Patient $where ORDER BY last_name, first_name";
$patients = $conn->query($sql);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Patients</h2>
  <?= get_flash(); ?>

  <!-- Search + add button -->
  <form method="get" style="margin-bottom:10px;">
    <input type="text" name="q" placeholder="Search name / email / phone"
           value="<?= htmlspecialchars($search); ?>" size="30">
    <button type="submit">Search</button>
    <a class="btn" href="list.php">Reset</a>
    <a class="btn" style="float:right;" href="add.php">+ Add New Patient</a>
  </form>

  <!-- Patients table -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>DOB</th>
        <th>Email</th>
        <th>Phone</th>
        <th style="width:110px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($patients->num_rows === 0): ?>
      <tr><td colspan="6">No patients found.</td></tr>
    <?php else: $i = 1; while ($p = $patients->fetch_assoc()): ?>
      <tr>
        <td><?= $i++; ?></td>
        <td><?= htmlspecialchars($p['first_name'].' '.$p['last_name']); ?></td>
        <td><?= $p['dob']; ?></td>
        <td><?= htmlspecialchars($p['email']); ?></td>
        <td><?= htmlspecialchars($p['phone']); ?></td>
        <td>
          <a class="btn ok" href="view.php?id=<?= $p['patient_id']; ?>">View</a>
          <a class="btn" href="edit.php?id=<?= $p['patient_id']; ?>">Edit</a>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>