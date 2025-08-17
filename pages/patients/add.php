<?php
/*****************************************************************
 * pages/patients/add.php
 * ---------------------------------------------------------------
 * Add a new patient record.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Handle form submission ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and escape inputs
    $fn   = $conn->real_escape_string(trim($_POST['first_name']));
    $ln   = $conn->real_escape_string(trim($_POST['last_name']));
    $dob  = $conn->real_escape_string($_POST['dob']);
    $eml  = $conn->real_escape_string(trim($_POST['email']));
    $ph   = $conn->real_escape_string(trim($_POST['phone']));
    $addr = $conn->real_escape_string(trim($_POST['address']));

    // Basic validation
    if ($fn === '' || $ln === '') {
        flash('First and last name are required.');
    } else {
        $stmt = $conn->prepare(
          "INSERT INTO patient (first_name, last_name, dob, email, phone, address)
           VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param('ssssss', $fn, $ln, $dob, $eml, $ph, $addr);

        if ($stmt->execute()) {
            flash('Patient added.');
            redirect('list.php');
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
  <h2>Add Patient</h2>
  <?= get_flash(); ?>

  <form method="post" style="max-width:420px">
    <label>First Name:<br>
      <input type="text" name="first_name" required>
    </label><br><br>

    <label>Last Name:<br>
      <input type="text" name="last_name" required>
    </label><br><br>

    <label>Date of Birth:<br>
      <input type="date" name="dob" required>
    </label><br><br>

    <label>Email:<br>
      <input type="email" name="email">
    </label><br><br>

    <label>Phone:<br>
      <input type="text" name="phone">
    </label><br><br>

    <label>Address:<br>
      <textarea name="address" rows="3" style="width:100%;"></textarea>
    </label><br><br>

    <button type="submit">Save Patient</button>
    <a href="list.php">Cancel</a>
  </form>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>