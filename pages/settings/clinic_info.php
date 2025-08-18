<?php
/*****************************************************************
 * pages/settings/clinic_info.php
 * ---------------------------------------------------------------
 * Manage basic clinic profile (name, address, phone, logo).
 * Admin-only access.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Allow admins only ───────── */
if (!is_admin()) {
    flash('Clinic settings are restricted to administrators.');
    redirect('/dentosys/index.php');
}

/* ───────── Ensure settings row exists (singleton pattern) ───────── */
$conn->query("INSERT IGNORE INTO clinicinfo (id) VALUES (1)");

/* ───────── Handle POST update ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $conn->real_escape_string(trim($_POST['clinic_name']));
    $addr    = $conn->real_escape_string(trim($_POST['clinic_address']));
    $phone   = $conn->real_escape_string(trim($_POST['phone']));
    $logoCol = '';       // SQL fragment if logo uploaded

    /* Handle optional logo upload */
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext  = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $safe = 'logo_' . uniqid() . ($ext ? ".$ext" : '');
        $dest = BASE_PATH . '/uploads/' . $safe;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            $logoCol = ", logo_path = '$safe'";
        } else {
            flash('Logo upload failed (check permissions).');
        }
    }

    $sql = "UPDATE clinicinfo
            SET clinic_name    = '$name',
                clinic_address = '$addr',
                phone          = '$phone'
                $logoCol
            WHERE id = 1";
    if ($conn->query($sql)) {
        flash('Clinic info saved.');
    } else {
        flash('DB error: ' . $conn->error);
    }
    redirect('clinic_info.php');
}

/* ───────── Fetch current info ───────── */
$info = $conn->query("SELECT * FROM clinicinfo WHERE id = 1")->fetch_assoc();

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Clinic Information</h2>
  <?= get_flash(); ?>

  <form method="post" enctype="multipart/form-data" style="max-width:520px">
    <label>Clinic Name:<br>
      <input type="text" name="clinic_name"
             value="<?= htmlspecialchars($info['clinic_name'] ?? ''); ?>" required>
    </label><br><br>

    <label>Clinic Address:<br>
      <textarea name="clinic_address" rows="3" style="width:100%;"
                required><?= htmlspecialchars($info['clinic_address'] ?? ''); ?></textarea>
    </label><br><br>

    <label>Phone Number:<br>
      <input type="text" name="phone"
             value="<?= htmlspecialchars($info['phone'] ?? ''); ?>">
    </label><br><br>

    <label>Logo (optional – JPG/PNG, max 500 KB):<br>
      <input type="file" name="logo" accept=".jpg,.jpeg,.png">
    </label><br><br>

    <?php if (!empty($info['logo_path'])): ?>
      <p>Current logo:<br>
         <img src="/uploads/<?= urlencode($info['logo_path']); ?>"
              alt="logo" style="max-height:80px;">
      </p>
    <?php endif; ?>

    <button type="submit">Save Changes</button>
  </form>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>