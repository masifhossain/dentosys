<?php
/*****************************************************************
 * pages/patients/book_appointment.php
 * Patient Appointment Booking
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

<div class="main-wrapper">
  <?php include BASE_PATH . '/templates/sidebar.php'; ?>
  
  <main class="content">
    <header class="content-header">
      <h1>➕ Book Appointment</h1>
      <p>Schedule your next dental visit</p>
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
  </main>
</div>

<style>
.appointment-form {
  max-width: 600px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #0066CC;
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

.form-actions {
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 12px;
}

.btn {
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #0066CC;
  color: white;
}

.btn-primary:hover {
  background: #0052A3;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.guidelines {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.guideline {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #0066CC;
}

.guideline .icon {
  font-size: 24px;
  line-height: 1;
}

.guideline strong {
  display: block;
  margin-bottom: 4px;
  color: #1f2937;
}

.guideline p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.mt-4 {
  margin-top: 2rem;
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
