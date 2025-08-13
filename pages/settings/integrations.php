<?php
/*****************************************************************
 * pages/settings/integrations.php
 * ---------------------------------------------------------------
 * Manage external integrations (REST API + Payment Gateway creds).
 * Admin-only access.
 *
 * Expected DB table (run once if you haven’t already):
 *
 *   CREATE TABLE Integrations (
 *     id                 INT PRIMARY KEY,          -- always 1
 *     api_base_url       VARCHAR(255),
 *     api_key            VARCHAR(255),
 *     payment_provider   VARCHAR(60),
 *     payment_pub_key    VARCHAR(255),
 *     payment_secret_key VARCHAR(255)
 *   ) ENGINE=InnoDB;
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Admin-only ───────── */
if (!is_admin()) {
    flash('Integrations are restricted to administrators.');
    redirect('/index.php');
}

/* ───────── Ensure singleton row exists ───────── */
$conn->query("INSERT IGNORE INTO Integrations (id) VALUES (1)");

/* ───────── Handle POST ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_url  = $conn->real_escape_string(trim($_POST['api_base_url']));
    $api_key  = $conn->real_escape_string(trim($_POST['api_key']));
    $provider = $conn->real_escape_string(trim($_POST['payment_provider']));
    $pubKey   = $conn->real_escape_string(trim($_POST['payment_pub_key']));
    $secKey   = $conn->real_escape_string(trim($_POST['payment_secret_key']));

    $stmt = $conn->prepare(
      "UPDATE Integrations
       SET api_base_url = ?, api_key = ?, payment_provider = ?,
           payment_pub_key = ?, payment_secret_key = ?
       WHERE id = 1"
    );
    $stmt->bind_param('sssss', $api_url, $api_key, $provider, $pubKey, $secKey);

    if ($stmt->execute()) {
        flash('Integration settings saved.');
    } else {
        flash('DB error: ' . $conn->error);
    }
    redirect('integrations.php');
}

/* ───────── Fetch current settings ───────── */
$cfg = $conn->query("SELECT * FROM Integrations WHERE id = 1")->fetch_assoc();

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Integrations</h2>
  <?= get_flash(); ?>

  <form method="post" style="max-width:560px">
    <!-- API section -->
    <fieldset style="border:1px solid #ddd;padding:12px;">
      <legend><strong>External API</strong></legend>

      <label>Base&nbsp;URL:<br>
        <input type="url" name="api_base_url" style="width:100%;"
               value="<?= htmlspecialchars($cfg['api_base_url'] ?? ''); ?>">
      </label><br><br>

      <label>API&nbsp;Key / Token:<br>
        <input type="text" name="api_key" style="width:100%;"
               value="<?= htmlspecialchars($cfg['api_key'] ?? ''); ?>">
      </label>
    </fieldset>

    <br>

    <!-- Payment section -->
    <fieldset style="border:1px solid #ddd;padding:12px;">
      <legend><strong>Payment Gateway</strong></legend>

      <label>Provider (e.g., Stripe, Square):<br>
        <input type="text" name="payment_provider" style="width:100%;"
               value="<?= htmlspecialchars($cfg['payment_provider'] ?? ''); ?>">
      </label><br><br>

      <label>Public&nbsp;Key:<br>
        <input type="text" name="payment_pub_key" style="width:100%;"
               value="<?= htmlspecialchars($cfg['payment_pub_key'] ?? ''); ?>">
      </label><br><br>

      <label>Secret&nbsp;Key:<br>
        <input type="text" name="payment_secret_key" style="width:100%;"
               value="<?= htmlspecialchars($cfg['payment_secret_key'] ?? ''); ?>">
      </label>
    </fieldset>

    <br>
    <button type="submit">Save Changes</button>
  </form>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>