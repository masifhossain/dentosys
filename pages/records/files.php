<?php
/*****************************************************************
 * pages/records/files.php
 * ---------------------------------------------------------------
 * Upload and list patient files (X-rays, PDFs, photos, etc.)
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* --------- Configuration --------- */
$UPLOAD_DIR = BASE_PATH . '/uploads';            // ensure this exists & writable
if (!is_dir($UPLOAD_DIR)) { mkdir($UPLOAD_DIR, 0777, true); }

/* --------- Dropdown: patients --------- */
$patients = get_patients($conn);

/* --------------------------------------------------------------
 * 1. Handle file upload
 * ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $patient_id = intval($_POST['patient_id']);
    $file       = $_FILES['file'];

    if ($patient_id === 0 || $file['error'] !== UPLOAD_ERR_OK) {
        flash('Please choose a patient and a valid file.');
        redirect("files.php?patient=$patient_id");
    }

    /* create safe unique filename */
    $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safe  = uniqid('f_', true) . ($ext ? ".$ext" : '');
    $dest  = $UPLOAD_DIR . '/' . $safe;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('Could not save file (check folder permissions).');
        redirect("files.php?patient=$patient_id");
    }

    /* store DB record */
    $stmt = $conn->prepare(
      "INSERT INTO FileUploads (patient_id, uploaded_by, filename, stored_path)
       VALUES (?,?,?,?)"
    );
    $uploader = $_SESSION['user_id'];
    $stmt->bind_param('iiss', $patient_id, $uploader, $file['name'], $safe);
    $stmt->execute();

    flash('File uploaded.');
    redirect("files.php?patient=$patient_id");
}

/* --------------------------------------------------------------
 * 2. Retrieve list
 * ------------------------------------------------------------ */
$where = '';
$patientFilter = '';
if (!empty($_GET['patient'])) {
    $pid   = intval($_GET['patient']);
    $where = "WHERE f.patient_id = $pid";
    $patientFilter = "patient=$pid";
}
$sql = "SELECT f.*, CONCAT(p.first_name,' ',p.last_name) AS pname
        FROM FileUploads f
        JOIN Patient p ON p.patient_id = f.patient_id
        $where
        ORDER BY f.uploaded_at DESC";
$list = $conn->query($sql);

/* --------------------------------------------------------------
 * 3. Page output
 * ------------------------------------------------------------ */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Patient Files &amp; X-rays</h2>
  <?= get_flash(); ?>

  <!-- Upload form -->
  <form method="post" enctype="multipart/form-data" style="margin-bottom:14px;">
    <label>Patient:
      <select name="patient_id" required>
        <option value="">Select patient</option>
        <?php while ($p = $patients->fetch_assoc()): ?>
          <option value="<?= $p['patient_id']; ?>"
            <?= (!empty($_GET['patient']) && $_GET['patient']==$p['patient_id'])?'selected':''; ?>>
            <?= htmlspecialchars($p['name']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </label>
    <input type="file" name="file" required>
    <button type="submit">Upload</button>
  </form>

  <!-- File list -->
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Patient</th>
        <th>File&nbsp;Name</th>
        <th>Date</th>
        <th>Download</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($list->num_rows === 0): ?>
      <tr><td colspan="5">No files found.</td></tr>
    <?php else: $i=1; while ($f=$list->fetch_assoc()): ?>
      <tr>
        <td><?= $i++; ?></td>
        <td><?= htmlspecialchars($f['pname']); ?></td>
        <td><?= htmlspecialchars($f['filename']); ?></td>
        <td><?= $f['uploaded_at']; ?></td>
        <td>
          <a class="btn ok" target="_blank"
             href="/uploads/<?= urlencode($f['stored_path']); ?>">
             Open
          </a>
        </td>
      </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>