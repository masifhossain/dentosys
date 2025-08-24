<?php
/*****************************************************************
 * pages/records/prescriptions.php
 * ---------------------------------------------------------------
 * List and manage patient prescriptions
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_staff();

/* ───────── Handle status updates ───────── */
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'complete') {
        $conn->query("UPDATE Prescriptions SET status='Completed' WHERE prescription_id=$id");
        flash('Prescription marked as completed.');
    } elseif ($action === 'cancel') {
        $conn->query("UPDATE Prescriptions SET status='Cancelled' WHERE prescription_id=$id");
        flash('Prescription cancelled.');
    }
    redirect('prescriptions.php');
}

/* ───────── Filter handling ───────── */
$whereSQL = "WHERE 1=1";

// Add dentist filtering - dentists can only see their own prescriptions
if (is_dentist()) {
    $dentistId = get_current_dentist_id();
    if ($dentistId) {
        $whereSQL .= " AND pr.dentist_id = $dentistId";
    } else {
        $whereSQL .= " AND 1=0"; // No dentist ID found, show nothing
    }
}

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
               CASE 
                   WHEN TRIM(CONCAT(u.first_name, ' ', u.last_name)) != '' 
                   THEN CONCAT(u.first_name, ' ', u.last_name)
                   ELSE CONCAT('Dr. ', SUBSTRING_INDEX(u.email, '@', 1))
               END AS dentist_name
        FROM Prescriptions pr
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

<main class="prescriptions-page">
  <div class="page-header">
    <div class="header-content">
      <h1 class="page-title">
        <i class="icon-prescription"></i>
        Prescriptions
      </h1>
      <p class="page-subtitle">Manage patient prescriptions and medication tracking</p>
    </div>
    <div class="header-actions">
      <?php if (!is_receptionist()): ?>
      <a class="btn btn-primary btn-add" href="add_prescription.php">
        <i class="icon-plus"></i>
        Add Prescription
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?= get_flash(); ?>

  <!-- Filter Section -->
  <div class="filter-card">
    <form method="get" class="filter-form">
      <div class="filter-grid">
        <div class="filter-group">
          <label class="filter-label">
            <i class="icon-user"></i>
            Patient
          </label>
          <select name="patient" class="filter-select">
            <option value="">-- All Patients --</option>
            <?php $patients->data_seek(0); while ($p = $patients->fetch_assoc()): ?>
              <option value="<?= $p['patient_id']; ?>"
                <?= (!empty($_GET['patient']) && $_GET['patient'] == $p['patient_id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($p['name']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="filter-group">
          <label class="filter-label">
            <i class="icon-status"></i>
            Status
          </label>
          <select name="status" class="filter-select">
            <option value="">-- All Status --</option>
            <?php foreach(['Active', 'Completed', 'Cancelled'] as $status): ?>
              <option value="<?= $status; ?>"
                <?= (!empty($_GET['status']) && $_GET['status'] === $status) ? 'selected' : ''; ?>>
                <?= $status; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn btn-filter">
            <i class="icon-filter"></i>
            Filter
          </button>
          <a href="prescriptions.php" class="btn btn-reset">
            <i class="icon-reset"></i>
            Reset
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Prescriptions Grid -->
  <div class="prescriptions-container">
    <?php if ($prescriptions->num_rows === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">
          <i class="icon-prescription-large"></i>
        </div>
        <h3>No prescriptions found</h3>
        <p>Start by adding a new prescription for your patients</p>
        <?php if (!is_receptionist()): ?>
        <a href="add_prescription.php" class="btn btn-primary">
          <i class="icon-plus"></i>
          Add First Prescription
        </a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="prescriptions-grid">
        <?php while ($rx = $prescriptions->fetch_assoc()): ?>
          <div class="prescription-card" data-status="<?= strtolower($rx['status']); ?>">
            <div class="card-header">
              <div class="prescription-date">
                <span class="date-day"><?= date('d', strtotime($rx['prescribed_date'])); ?></span>
                <span class="date-month"><?= date('M Y', strtotime($rx['prescribed_date'])); ?></span>
              </div>
              <div class="prescription-status">
                <span class="status-badge status-<?= strtolower($rx['status']); ?>">
                  <?= $rx['status']; ?>
                </span>
              </div>
            </div>

            <div class="card-body">
              <div class="patient-info">
                <h3 class="patient-name"><?= htmlspecialchars($rx['patient_name']); ?></h3>
              </div>

              <div class="medication-info">
                <h4 class="medication-name">
                  <i class="icon-pill"></i>
                  <?= htmlspecialchars($rx['medication_name']); ?>
                </h4>
                
                <div class="medication-details">
                  <div class="detail-item">
                    <span class="detail-label">Dosage:</span>
                    <span class="detail-value"><?= htmlspecialchars($rx['dosage']); ?></span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Frequency:</span>
                    <span class="detail-value"><?= htmlspecialchars($rx['frequency']); ?></span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value"><?= htmlspecialchars($rx['duration']); ?></span>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-actions">
              <?php if ($rx['status'] === 'Active'): ?>
                <a href="?action=complete&id=<?= $rx['prescription_id']; ?>" 
                   class="btn btn-sm btn-success"
                   onclick="return confirm('Mark as completed?');">
                  <i class="icon-check"></i>
                  Complete
                </a>
                <a href="?action=cancel&id=<?= $rx['prescription_id']; ?>" 
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Cancel prescription?');">
                  <i class="icon-cancel"></i>
                  Cancel
                </a>
              <?php endif; ?>
              <a href="print_prescription.php?id=<?= $rx['prescription_id']; ?>" 
                 class="btn btn-sm btn-outline" target="_blank">
                <i class="icon-print"></i>
                Print
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<style>
/* Prescriptions Page Styles */
.prescriptions-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 2rem;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.header-content h1.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-subtitle {
  color: #7f8c8d;
  font-size: 1.1rem;
  margin: 0.5rem 0 0 0;
}

.icon-prescription::before {
  content: "💊";
  font-size: 2rem;
}

.header-actions .btn-add {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.header-actions .btn-add:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.icon-plus::before { content: "+"; }

/* Filter Card */
.filter-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.filter-grid {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 2rem;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-label {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.icon-user::before { content: "👤"; }
.icon-status::before { content: "📊"; }

.filter-select {
  padding: 0.75rem 1rem;
  border: 2px solid #e9ecef;
  border-radius: 10px;
  font-size: 1rem;
  background: white;
  transition: all 0.3s ease;
}

.filter-select:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
  display: flex;
  gap: 1rem;
}

.btn-filter {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-filter:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
}

.btn-reset {
  background: #f8f9fa;
  color: #6c757d;
  border: 2px solid #e9ecef;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-reset:hover {
  background: #e9ecef;
  color: #495057;
}

.icon-filter::before { content: "🔍"; }
.icon-reset::before { content: "↻"; }

/* Prescriptions Container */
.prescriptions-container {
  margin-top: 2rem;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.icon-prescription-large::before {
  content: "💊";
}

.empty-state h3 {
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #7f8c8d;
  margin-bottom: 2rem;
}

/* Prescriptions Grid */
.prescriptions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 2rem;
}

/* Prescription Card */
.prescription-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.prescription-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.prescription-card[data-status="active"] {
  border-left: 4px solid #28a745;
}

.prescription-card[data-status="completed"] {
  border-left: 4px solid #007bff;
}

.prescription-card[data-status="cancelled"] {
  border-left: 4px solid #dc3545;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.prescription-date {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.date-day {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1;
}

.date-month {
  font-size: 0.875rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-active {
  background: #d4edda;
  color: #155724;
}

.status-completed {
  background: #cce5ff;
  color: #004085;
}

.status-cancelled {
  background: #f8d7da;
  color: #721c24;
}

.card-body {
  padding: 1.5rem;
}

.patient-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0 0 1rem 0;
}

.medication-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #667eea;
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.icon-pill::before { content: "💊"; }

.medication-details {
  display: grid;
  gap: 0.75rem;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f3f4;
}

.detail-label {
  font-weight: 600;
  color: #6c757d;
  font-size: 0.875rem;
}

.detail-value {
  font-weight: 500;
  color: #2c3e50;
}

.card-actions {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover {
  background: #218838;
  transform: translateY(-1px);
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
  transform: translateY(-1px);
}

.btn-outline {
  background: white;
  color: #6c757d;
  border: 2px solid #e9ecef;
}

.btn-outline:hover {
  background: #f8f9fa;
  color: #495057;
  transform: translateY(-1px);
}

.icon-check::before { content: "✓"; }
.icon-cancel::before { content: "✕"; }
.icon-print::before { content: "🖨️"; }

/* Responsive Design */
@media (max-width: 768px) {
  .prescriptions-page {
    padding: 1rem;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .filter-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .filter-actions {
    justify-content: center;
  }
  
  .prescriptions-grid {
    grid-template-columns: 1fr;
  }
  
  .card-actions {
    justify-content: center;
  }
}

/* Loading Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.prescription-card {
  animation: fadeIn 0.5s ease forwards;
}

.prescription-card:nth-child(1) { animation-delay: 0.1s; }
.prescription-card:nth-child(2) { animation-delay: 0.2s; }
.prescription-card:nth-child(3) { animation-delay: 0.3s; }
.prescription-card:nth-child(4) { animation-delay: 0.4s; }
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
