<?php
/*****************************************************************
 * pages/patients/my_appointments.php
 * Patient Appointments View
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_patient(); // Only patients can access their appointments

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

<div class="main-wrapper patient-page full-width">
  <?php include BASE_PATH . '/templates/sidebar.php'; ?>
  
  <main class="content">
    <div class="page-container">
      <header class="content-header">
        <h1>📅 My Appointments</h1>
        <p class="subtitle">View your past and upcoming appointments</p>
      </header>

      <?= get_flash(); ?>

      <div class="appointments-container">
        <div class="appointments-header">
          <h2>Your Appointments</h2>
          <a href="/dentosys/pages/patients/book_appointment.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Book New Appointment
          </a>
        </div>

        <?php if ($appointments_query->num_rows === 0): ?>
          <div class="no-data">
            <div class="no-data-icon">🗓️</div>
            <h3>No Appointments</h3>
            <p>You don't have any appointments scheduled. Ready to book one?</p>
            <a href="/dentosys/pages/patients/book_appointment.php" class="btn btn-primary">Book an Appointment</a>
          </div>
        <?php else: ?>
          <div class="appointments-grid">
            <?php while ($appointment = $appointments_query->fetch_assoc()): ?>
              <div class="appointment-card">
                <div class="appointment-date">
                  <span class="month"><?= date('M', strtotime($appointment['appointment_dt'])); ?></span>
                  <span class="day"><?= date('d', strtotime($appointment['appointment_dt'])); ?></span>
                  <span class="year"><?= date('Y', strtotime($appointment['appointment_dt'])); ?></span>
                </div>
                <div class="appointment-details">
                  <div class="appointment-time">
                    <i class="far fa-clock"></i> <?= date('g:i A', strtotime($appointment['appointment_dt'])); ?>
                  </div>
                  <div class="appointment-doctor">
                    <i class="fas fa-user-md"></i> <?= htmlspecialchars($appointment['dentist_email']); ?>
                  </div>
                  <?php if (!empty($appointment['notes'])): ?>
                    <div class="appointment-reason">
                      <i class="fas fa-info-circle"></i> <?= htmlspecialchars($appointment['notes']); ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="appointment-status status-<?= strtolower(str_replace(' ', '-', $appointment['status'])); ?>">
                  <?= htmlspecialchars($appointment['status']); ?>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<style>
/* Patient page full-width layout */
.patient-page.full-width .content {
  padding: 0;
  width: 100%;
}

.patient-page .page-container {
  width: 100%;
  padding: 2rem;
  margin: 0;
}

.patient-page .content-header {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: white;
  padding: 2.5rem 2rem;
  margin-bottom: 2rem;
  border-radius: 0;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.patient-page .content-header h1 {
  font-size: 2.25rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.patient-page .content-header .subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  margin: 0;
}

/* Appointments Section */
.appointments-container {
  width: 100%;
}

.appointments-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.appointments-header h2 {
  font-size: 1.75rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

/* Appointments Grid */
.appointments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 1.5rem;
}

.appointment-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  align-items: center;
  padding: 1.5rem;
  gap: 1.5rem;
}

.appointment-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

/* Date Section */
.appointment-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
  color: #4338ca;
  border-radius: 12px;
  padding: 1rem;
  font-weight: 700;
  min-width: 80px;
  text-align: center;
}

.appointment-date .month {
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.appointment-date .day {
  font-size: 2rem;
  line-height: 1;
  margin: 0.25rem 0;
}

.appointment-date .year {
  font-size: 0.875rem;
  color: #6366f1;
  opacity: 0.8;
}

/* Details Section */
.appointment-details {
  flex: 1;
}

.appointment-details div {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #475569;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.appointment-details div:last-child {
  margin-bottom: 0;
}

.appointment-details i {
  color: #94a3b8;
  width: 16px;
  text-align: center;
}

/* Status Badge */
.appointment-status {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: capitalize;
  white-space: nowrap;
}

.status-approved {
  background-color: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.status-pending {
  background-color: #fef3c7;
  color: #854d0e;
  border: 1px solid #fde68a;
}

.status-complete,
.status-completed {
  background-color: #dbeafe;
  color: #1e40af;
  border: 1px solid #bfdbfe;
}

.status-cancelled {
  background-color: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

/* No Data State */
.no-data {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  border: 1px dashed #cbd5e1;
}

.no-data-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.no-data h3 {
  font-size: 1.5rem;
  color: #1e293b;
  margin: 0 0 0.5rem;
}

.no-data p {
  color: #64748b;
  margin: 0 0 1.5rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  line-height: 1;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all .2s ease;
}

.btn-primary {
  color: #fff;
  background-color: #4f46e5;
  border-color: #4f46e5;
}

.btn-primary:hover {
  background-color: #4338ca;
  border-color: #4338ca;
  transform: translateY(-1px);
}

/* Flash Messages */
.flash {
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
  border: 1px solid transparent;
  border-radius: .5rem;
}

.flash.success {
  color: #0f5132;
  background-color: #d1e7dd;
  border-color: #badbcc;
}

.flash.error {
  color: #842029;
  background-color: #f8d7da;
  border-color: #f5c2c7;
}

.flash.info {
  color: #055160;
  background-color: #cff4fc;
  border-color: #b6effb;
}

/* Responsive Design */
@media (max-width: 768px) {
  .appointments-grid {
    grid-template-columns: 1fr;
  }
  
  .appointments-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .appointment-card {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .appointment-date {
    align-self: center;
  }
  
  .patient-page .page-container {
    padding: 1rem;
  }
  
  .patient-page .content-header {
    padding: 2rem 1rem;
  }
}

@media (max-width: 480px) {
  .appointments-grid {
    gap: 1rem;
  }
  
  .appointment-card {
    padding: 1rem;
  }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
