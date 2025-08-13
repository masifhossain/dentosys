<?php
/*****************************************************************
 * pages/help.php
 * ---------------------------------------------------------------
 * Lightweight Help-&-Support centre
 *  • Static knowledge-base links
 *  • “Contact Support” ticket form
 *
 *  Optional ticket table (run once if you haven’t):
 *    CREATE TABLE SupportTicket (
 *      ticket_id  INT AUTO_INCREMENT PRIMARY KEY,
 *      user_id    INT NULL,
 *      subject    VARCHAR(120) NOT NULL,
 *      message    TEXT NOT NULL,
 *      status     ENUM('Open','Closed') DEFAULT 'Open',
 *      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *      FOREIGN KEY (user_id) REFERENCES UserTbl(user_id)
 *        ON DELETE SET NULL
 *    ) ENGINE=InnoDB;
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';   // up 1 level
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Handle “Contact Support” submission ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $conn->real_escape_string(trim($_POST['subject']));
    $msg     = $conn->real_escape_string(trim($_POST['message']));

    if ($subject === '' || $msg === '') {
        flash('Subject and message are required.');
    } else {
        $uid = $_SESSION['user_id'];
        $stmt = $conn->prepare(
          "INSERT INTO SupportTicket (user_id, subject, message)
           VALUES (?,?,?)"
        );
        $stmt->bind_param('iss', $uid, $subject, $msg);
        $stmt->execute();

        flash('Support ticket submitted. We’ll get back to you ASAP.');
    }
    redirect('help.php');
}

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Help &amp; Support</h2>
  <?= get_flash(); ?>

  <!-- Simple knowledge-base “tiles” -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:26px;">
    <?php
    $kb = [
      ['Patients','pages/patients/list.php','Add, view & manage patients'],
      ['Appointments','pages/appointments/calendar.php','Schedule & track visits'],
      ['Clinical Records','pages/records/list.php','Treatment notes & files'],
      ['Billing','pages/billing/invoices.php','Invoices & payments'],
      ['Reports','pages/reports/financial.php','Financial & audit logs'],
      ['Settings','pages/settings/clinic_info.php','Configure clinic details'],
    ];
    foreach ($kb as $item): ?>
      <a class="card" href="/<?= $item[1]; ?>" style="text-decoration:none;color:#000;">
        <h3><?= $item[0]; ?></h3>
        <p style="font-size:12px;"><?= $item[2]; ?></p>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Contact Support form -->
  <h3>Contact Support</h3>
  <form method="post" style="max-width:520px;">
    <label>Subject:<br>
      <input type="text" name="subject" required style="width:100%;">
    </label><br><br>

    <label>Message:<br>
      <textarea name="message" rows="5" required
                style="width:100%;"></textarea>
    </label><br><br>

    <button type="submit">Submit Ticket</button>
  </form>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>