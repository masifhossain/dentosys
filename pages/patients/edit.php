<?php
/*****************************************************************
 * pages/patients/edit.php
 * ---------------------------------------------------------------
 * Edit an existing patient record.
 *   URL: edit.php?id=123
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Check & fetch patient ───────── */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    flash('Invalid patient ID.');
    redirect('list.php');
}

$stmt = $conn->prepare("SELECT * FROM Patient WHERE patient_id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    flash('Patient not found.');
    redirect('list.php');
}

/* ───────── Handle form submission ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn   = $conn->real_escape_string(trim($_POST['first_name']));
    $ln   = $conn->real_escape_string(trim($_POST['last_name']));
    $dob  = $conn->real_escape_string($_POST['dob']);
    $eml  = $conn->real_escape_string(trim($_POST['email']));
    $ph   = $conn->real_escape_string(trim($_POST['phone']));
    $addr = $conn->real_escape_string(trim($_POST['address']));

    if ($fn === '' || $ln === '') {
        flash('First and last name are required.');
    } else {
        $upd = $conn->prepare(
          "UPDATE Patient SET first_name=?, last_name=?, dob=?, email=?, phone=?, address=?
           WHERE patient_id=? LIMIT 1"
        );
        $upd->bind_param('ssssssi', $fn, $ln, $dob, $eml, $ph, $addr, $id);
        if ($upd->execute()) {
            flash('Patient updated.');
            redirect("view.php?id=$id");
        } else {
            flash('DB error: '.$conn->error);
        }
    }
}

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Edit Patient</h2>
  <?= get_flash(); ?>

  <form method="post" style="max-width:420px">
    <label>First Name:<br>
      <input type="text" name="first_name"
             value="<?= htmlspecialchars($patient['first_name']); ?>" required>
    </label><br><br>

    <label>Last Name:<br>
      <input type="text" name="last_name"
             value="<?= htmlspecialchars($patient['last_name']); ?>" required>
    </label><br><br>

    <label>Date of Birth:<br>
      <input type="date" name="dob"
             value="<?= $patient['dob']; ?>" required>
    </label><br><br>

    <label>Email:<br>
      <input type="email" name="email"
             value="<?= htmlspecialchars($patient['email']); ?>">
    </label><br><br>

    <label>Phone:<br>
      <input type="text" name="phone"
             value="<?= htmlspecialchars($patient['phone']); ?>">
    </label><br><br>

    <label>Address:<br>
      <textarea name="address" rows="3" style="width:100%;"><?= htmlspecialchars($patient['address']); ?></textarea>
    </label><br><br>

    <button type="submit">Save Changes</button>
    <a href="view.php?id=<?= $id; ?>">Cancel</a>
  </form>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>