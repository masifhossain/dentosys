<?php
/*****************************************************************
 * pages/patients/my_appointments.php
 * Patient Appointments View
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 4) {
    flash('Access denied. Patients only.');
    redirect('/dentosys/auth/login.php');
}

$user_id = $_SESSION['user_id'];

// Get patient ID
$patient_query = $conn->query("
    SELECT p.patient_id
    FROM patient p
    JOIN usertbl u ON u.email = p.email
    WHERE u.user_id = $user_id
    LIMIT 1
");

$patient = $patient_query->fetch_assoc();
if (!$patient) {
    flash('Patient profile not found.');
    redirect('/dentosys/auth/logout.php');
}

$patient_id = $patient['patient_id'];

// Get appointments
$appointments_query = $conn->query("
    SELECT a.*, d.user_id as dentist_user_id, u.email as dentist_email
    FROM appointment a
    JOIN dentist d ON d.dentist_id = a.dentist_id
    JOIN usertbl u ON u.user_id = d.user_id
    WHERE a.patient_id = $patient_id
    ORDER BY a.appointment_dt DESC
");

$pageTitle = 'My Appointments';
include BASE_PATH . '/templates/header.php';
?>

<div class="main-wrapper">
  <?php include BASE_PATH . '/templates/sidebar.php'; ?>
  
  <main class="content">
    <header class="content-header">
      <h1>📅 My Appointments</h1>
      <p>View your past and upcoming appointments</p>
    </header>

    <?= get_flash(); ?>

    <div class="card">
      <div class="card-header">
        <h3>Appointment History</h3>
        <a href="/dentosys/pages/patients/book_appointment.php" class="btn btn-primary">
          ➕ Book New Appointment
        </a>
      </div>
      
      <div class="card-body">
        <?php if ($appointments_query->num_rows > 0): ?>
          <div class="appointments-list">
            <?php while ($appointment = $appointments_query->fetch_assoc()): ?>
              <div class="appointment-card status-<?= strtolower($appointment['status']) ?>">
                <div class="appointment-header">
                  <div class="appointment-date">
                    <span class="date"><?= date('M j, Y', strtotime($appointment['appointment_dt'])) ?></span>
                    <span class="time"><?= date('g:i A', strtotime($appointment['appointment_dt'])) ?></span>
                  </div>
                  <div class="appointment-status">
                    <span class="status-badge status-<?= strtolower($appointment['status']) ?>">
                      <?= $appointment['status'] ?>
                    </span>
                  </div>
                </div>
                
                <div class="appointment-details">
                  <p><strong>Dentist:</strong> <?= htmlspecialchars($appointment['dentist_email']) ?></p>
                  <?php if ($appointment['notes']): ?>
                    <p><strong>Notes:</strong> <?= htmlspecialchars($appointment['notes']) ?></p>
                  <?php endif; ?>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">📅</div>
            <h3>No Appointments Yet</h3>
            <p>You haven't booked any appointments yet.</p>
            <a href="/dentosys/pages/patients/book_appointment.php" class="btn btn-primary">
              Book Your First Appointment
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<style>
.appointments-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.appointment-card {
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  background: white;
  transition: all 0.3s ease;
}

.appointment-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.appointment-card.status-approved {
  border-left: 4px solid #10b981;
}

.appointment-card.status-pending {
  border-left: 4px solid #f59e0b;
}

.appointment-card.status-complete {
  border-left: 4px solid #0066CC;
}

.appointment-card.status-cancelled {
  border-left: 4px solid #ef4444;
}

.appointment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.appointment-date .date {
  font-weight: 600;
  font-size: 18px;
  color: #1f2937;
}

.appointment-date .time {
  display: block;
  color: #6b7280;
  font-size: 14px;
  margin-top: 2px;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.status-complete {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.appointment-details p {
  margin: 8px 0;
  color: #374151;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin-bottom: 8px;
  color: #1f2937;
}

.empty-state p {
  color: #6b7280;
  margin-bottom: 24px;
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
