<?php
/*****************************************************************
 * pages/patients/book_appointment.php
 * Patient Appointment Booking
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_patient(); // Only patients can book appointments

$user_id = $_SESSION['user_id'];

// Get patient ID
$patient_query = $conn->query("
    SELECT p.patient_id, p.first_name, p.last_name
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

// Get available dentists
$dentists_query = $conn->query("
    SELECT d.dentist_id, d.specialty, u.email
    FROM dentist d
    JOIN usertbl u ON u.user_id = d.user_id
    WHERE u.is_active = 1
    ORDER BY d.specialty, u.email
");

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dentist_id = intval($_POST['dentist_id']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $notes = $conn->real_escape_string(trim($_POST['notes']));
    
    // Combine date and time
    $appointment_dt = $appointment_date . ' ' . $appointment_time;
    
    // Validate future date
    if (strtotime($appointment_dt) <= time()) {
        flash('Please select a future date and time.');
    } else {
        // Check if dentist is available (no conflicting appointments)
        $conflict_check = $conn->query("
            SELECT 1 FROM appointment 
            WHERE dentist_id = $dentist_id 
            AND appointment_dt = '$appointment_dt'
            AND status IN ('Pending', 'Approved', 'Scheduled')
            LIMIT 1
        ");
        
        if ($conflict_check->num_rows > 0) {
            flash('Sorry, that time slot is already booked. Please choose another time.');
        } else {
            $insert_query = "INSERT INTO appointment (patient_id, dentist_id, appointment_dt, status, notes) 
                           VALUES ({$patient['patient_id']}, $dentist_id, '$appointment_dt', 'Pending', '$notes')";
            
            if ($conn->query($insert_query)) {
                flash('Appointment request submitted successfully! We will contact you to confirm.');
                redirect('/dentosys/pages/patients/my_appointments.php');
            } else {
                flash('Error booking appointment: ' . $conn->error);
            }
        }
    }
}

$pageTitle = 'Book Appointment';
include BASE_PATH . '/templates/header.php';
?>

<div class="main-wrapper patient-page full-width">
  <?php include BASE_PATH . '/templates/sidebar.php'; ?>
  
  <main class="content">
    <div class="page-container">
      <header class="content-header">
        <h1>➕ Book Appointment</h1>
        <p class="subtitle">Schedule your next dental visit</p>
      </header>

    <?= get_flash(); ?>

    <div class="card">
      <div class="card-header">
        <h3>Appointment Details</h3>
        <p>Please fill in your preferred appointment details</p>
      </div>
      
      <div class="card-body">
        <form method="post" class="appointment-form">
          <div class="form-row">
            <div class="form-group">
              <label for="patient_name">Patient Name</label>
              <input type="text" id="patient_name" 
                     value="<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>" 
                     disabled style="background: #f5f5f5;">
            </div>
            
            <div class="form-group">
              <label for="dentist_id">Preferred Dentist</label>
              <select id="dentist_id" name="dentist_id" required>
                <option value="">Select a dentist...</option>
                <?php while ($dentist = $dentists_query->fetch_assoc()): ?>
                  <option value="<?= $dentist['dentist_id'] ?>">
                    <?= htmlspecialchars($dentist['email']) ?> 
                    <?php if ($dentist['specialty']): ?>
                      - <?= htmlspecialchars($dentist['specialty']) ?>
                    <?php endif; ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="appointment_date">Preferred Date</label>
              <input type="date" id="appointment_date" name="appointment_date" 
                     min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
            
            <div class="form-group">
              <label for="appointment_time">Preferred Time</label>
              <select id="appointment_time" name="appointment_time" required>
                <option value="">Select time...</option>
                <option value="09:00:00">9:00 AM</option>
                <option value="09:30:00">9:30 AM</option>
                <option value="10:00:00">10:00 AM</option>
                <option value="10:30:00">10:30 AM</option>
                <option value="11:00:00">11:00 AM</option>
                <option value="11:30:00">11:30 AM</option>
                <option value="14:00:00">2:00 PM</option>
                <option value="14:30:00">2:30 PM</option>
                <option value="15:00:00">3:00 PM</option>
                <option value="15:30:00">3:30 PM</option>
                <option value="16:00:00">4:00 PM</option>
                <option value="16:30:00">4:30 PM</option>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label for="notes">Reason for Visit / Additional Notes</label>
            <textarea id="notes" name="notes" rows="4" 
                      placeholder="Please describe the reason for your visit or any specific concerns..."></textarea>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Request Appointment</button>
            <a href="/dentosys/pages/patients/my_appointments.php" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
    
    <div class="card mt-4">
      <div class="card-header">
        <h3>📋 Appointment Guidelines</h3>
      </div>
      
      <div class="card-body">
        <div class="guidelines">
          <div class="guideline">
            <span class="icon">⏰</span>
            <div>
              <strong>Booking Notice</strong>
              <p>Please book at least 24 hours in advance</p>
            </div>
          </div>
          
          <div class="guideline">
            <span class="icon">✅</span>
            <div>
              <strong>Confirmation</strong>
              <p>We will contact you to confirm your appointment</p>
            </div>
          </div>
          
          <div class="guideline">
            <span class="icon">📞</span>
            <div>
              <strong>Changes</strong>
              <p>To reschedule or cancel, please call us at least 2 hours before</p>
            </div>
          </div>
        </div>
      </div>
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

.patient-page .card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
  width: 100%;
}

.patient-page .card-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  background-color: #f8fafc;
}

.patient-page .card-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
  color: #1e293b;
}

.patient-page .card-header p {
  margin: 0;
  color: #64748b;
}

.patient-page .card-body {
  padding: 2rem;
}

.appointment-form {
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-actions {
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.guidelines {
  display: grid;
  gap: 1rem;
}

.guideline {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border-left: 4px solid #4f46e5;
}

.guideline .icon {
  font-size: 1.5rem;
  line-height: 1;
}

.guideline strong {
  display: block;
  margin-bottom: 0.25rem;
  color: #1e293b;
}

.guideline p {
  margin: 0;
  color: #64748b;
}

.mt-4 {
  margin-top: 2rem;
}

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

.btn-secondary {
  color: #374151;
  background-color: #f9fafb;
  border-color: #d1d5db;
}

.btn-secondary:hover {
  background-color: #f3f4f6;
  border-color: #9ca3af;
}

/* Responsive */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .patient-page .page-container {
    padding: 1rem;
  }
  
  .patient-page .content-header {
    padding: 2rem 1rem;
  }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
