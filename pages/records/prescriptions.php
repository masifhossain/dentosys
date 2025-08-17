<?php
/*****************************************************************
 * pages/records/prescriptions.php
 * ---------------------------------------------------------------
 * List and manage patient prescriptions
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Create Prescription table if not exists ───────── */
$conn->query("
CREATE TABLE IF NOT EXISTS Prescription (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    dentist_id INT NOT NULL,
    appointment_id INT,
    medication_name VARCHAR(255) NOT NULL,
    dosage VARCHAR(100),
    frequency VARCHAR(100),
    duration VARCHAR(100),
    instructions TEXT,
    prescribed_date DATE NOT NULL,
    status ENUM('Active', 'Completed', 'Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES Patient(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (dentist_id) REFERENCES Dentist(dentist_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES Appointment(appointment_id) ON DELETE SET NULL
)
");

/* ───────── Handle status updates ───────── */
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'complete') {
        $conn->query("UPDATE Prescription SET status='Completed' WHERE prescription_id=$id");
        flash('Prescription marked as completed.');
    } elseif ($action === 'cancel') {
        $conn->query("UPDATE Prescription SET status='Cancelled' WHERE prescription_id=$id");
        flash('Prescription cancelled.');
    }
    redirect('prescriptions.php');
}

/* ───────── Filter handling ───────── */
$whereSQL = "WHERE 1=1";
if (!empty($_GET['patient'])) {
    $pid = intval($_GET['patient']);
    $whereSQL .= " AND p.patient_id = $pid";
}
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereSQL .= " AND pr.status = '$status'";
}

/* ───────── Fetch prescriptions ───────── */
$sql = "SELECT pr.*, 
               CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
               CONCAT(u.email) AS dentist_name
        FROM Prescription pr
        JOIN Patient p ON p.patient_id = pr.patient_id
        JOIN Dentist d ON d.dentist_id = pr.dentist_id
        JOIN UserTbl u ON u.user_id = d.user_id
        $whereSQL
        ORDER BY pr.prescribed_date DESC";
$prescriptions = $conn->query($sql);

/* ───────── Get patients for filter ───────── */
$patients = get_patients($conn);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
  <h2>Prescriptions</h2>
  <?= get_flash(); ?>

  <!-- Action buttons -->
  <div style="margin-bottom: 20px;">
    <a class="btn btn-primary" href="add_prescription.php">+ Add Prescription</a>
  </div>

  <!-- Filter form -->
  <form method="get" style="margin-bottom:20px; padding:15px; background:#f8f9fa; border-radius:8px;">
    <div style="display:flex; gap:15px; align-items:end; flex-wrap:wrap;">
      <div>
        <label>Patient:</label>
        <select name="patient" style="margin-top:5px;">
          <option value="">-- All Patients --</option>
          <?php while ($p = $patients->fetch_assoc()): ?>
            <option value="<?= $p['patient_id']; ?>"
              <?= (!empty($_GET['patient']) && $_GET['patient'] == $p['patient_id']) ? 'selected' : ''; ?>>
              <?= htmlspecialchars($p['name']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div>
        <label>Status:</label>
        <select name="status" style="margin-top:5px;">
          <option value="">-- All Status --</option>
          <?php foreach(['Active', 'Completed', 'Cancelled'] as $status): ?>
            <option value="<?= $status; ?>"
              <?= (!empty($_GET['status']) && $_GET['status'] === $status) ? 'selected' : ''; ?>>
              <?= $status; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="prescriptions.php" class="btn btn-outline">Reset</a>
      </div>
    </div>
  </form>

  <!-- Prescriptions table -->
  <div class="card">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Patient</th>
          <th>Medication</th>
          <th>Dosage</th>
          <th>Frequency</th>
          <th>Duration</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($prescriptions->num_rows === 0): ?>
          <tr><td colspan="8" class="text-center">No prescriptions found.</td></tr>
        <?php else: ?>
          <?php while ($rx = $prescriptions->fetch_assoc()): ?>
            <tr>
              <td><?= date('M d, Y', strtotime($rx['prescribed_date'])); ?></td>
              <td><?= htmlspecialchars($rx['patient_name']); ?></td>
              <td><strong><?= htmlspecialchars($rx['medication_name']); ?></strong></td>
              <td><?= htmlspecialchars($rx['dosage']); ?></td>
              <td><?= htmlspecialchars($rx['frequency']); ?></td>
              <td><?= htmlspecialchars($rx['duration']); ?></td>
              <td>
                <span class="badge badge-<?= strtolower($rx['status']) === 'active' ? 'success' : (strtolower($rx['status']) === 'completed' ? 'primary' : 'danger'); ?>">
                  <?= $rx['status']; ?>
                </span>
              </td>
              <td>
                <div style="display:flex; gap:5px;">
                  <?php if ($rx['status'] === 'Active'): ?>
                    <a href="?action=complete&id=<?= $rx['prescription_id']; ?>" 
                       class="btn btn-sm btn-success"
                       onclick="return confirm('Mark as completed?');">Complete</a>
                    <a href="?action=cancel&id=<?= $rx['prescription_id']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Cancel prescription?');">Cancel</a>
                  <?php endif; ?>
                  <a href="print_prescription.php?id=<?= $rx['prescription_id']; ?>" 
                     class="btn btn-sm btn-outline" target="_blank">Print</a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<style>
.badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}
.badge-success { background: #d1f2eb; color: #0f5132; }
.badge-primary { background: #cff4fc; color: #055160; }
.badge-danger { background: #f8d7da; color: #721c24; }
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
