<?php
/*****************************************************************
 * pages/appointments/book.php
 * ---------------------------------------------------------------
 * Beautiful appointment booking interface with modern design
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // 2 levels up
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Restrict appointment booking to admin and reception staff only
if (is_dentist()) {
    flash('Appointment booking is restricted to administrative staff. Please contact reception to book appointments.');
    redirect('/dentosys/pages/appointments/calendar.php');
}

/* ───────── Dropdown data ───────── */
$patients = get_patients($conn);

// Apply role-based filtering for dentists
if (is_dentist()) {
    $current_dentist_id = get_current_dentist_id();
    if ($current_dentist_id) {
        $dentists = $conn->query(
            "SELECT d.dentist_id,
                    CONCAT(u.email,' (',
                           IFNULL(d.specialty,'General'),
                           ')') AS name
             FROM Dentist d
             JOIN UserTbl u ON u.user_id = d.user_id
             WHERE d.dentist_id = $current_dentist_id
             ORDER BY name"
        );
    } else {
        $dentists = null;
    }
} else {
    $dentists = get_dentists($conn);
}

/* ───────── Handle form POST ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $dentist_id = intval($_POST['dentist_id']);
    $dt         = $conn->real_escape_string($_POST['appt_dt']);  // yyyy-mm-ddTHH:MM
    $status     = 'Pending';
    $notes      = $conn->real_escape_string($_POST['notes']);

    $sql = "INSERT INTO Appointment
            (patient_id, dentist_id, appointment_dt, status, notes)
            VALUES ($patient_id, $dentist_id, '$dt', '$status', '$notes')";

    if ($conn->query($sql)) {
        flash('Appointment booked successfully.', 'success');
        redirect('calendar.php');
    } else {
        flash('Error: ' . $conn->error, 'error');
    }
}

/* ───────── HTML Output ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
    /* Modern Appointment Booking Styles */
    .booking-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 0; /* Remove extra padding to avoid right-side whitespace */
        margin: 0;
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow-x: hidden; /* Prevent any accidental horizontal overflow */
    }

    .booking-wrapper {
        max-width: 1400px;
        width: 100%;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        display: grid;
        grid-template-columns: 1fr 350px;
        grid-template-rows: auto 1fr;
        grid-template-areas: 
            "header header"
            "form sidebar";
        column-gap: 0;
        overflow: hidden;
        /* Guard rails: never exceed viewport width */
        max-width: min(1400px, 100vw);
    }

    .booking-header {
        grid-area: header;
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 3rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .booking-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="rgba(255,255,255,0.1)"><circle cx="25" cy="25" r="3"/><circle cx="75" cy="25" r="3"/><circle cx="25" cy="75" r="3"/><circle cx="75" cy="75" r="3"/><circle cx="50" cy="50" r="5"/></svg>');
        background-size: 40px 40px;
        animation: pulse 15s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.6; }
    }

    .booking-header h1 {
        font-size: 2.5rem;
        margin: 0 0 0.75rem 0;
        font-weight: 700;
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }

    .booking-header p {
        font-size: 1.2rem;
        margin: 0;
        opacity: 0.95;
        font-weight: 400;
        position: relative;
        z-index: 2;
    }

    .booking-form {
        grid-area: form;
        padding: 3rem;
        background: white;
    }
    }

    .booking-header p {
        margin: 1rem 0 0 0;
        font-size: 1.2rem;
        opacity: 0.9;
        position: relative;
        z-index: 2;
    }

    .booking-form {
        grid-area: form;
        padding: 3rem;
        background: white;
    }

    .booking-sidebar {
        grid-area: sidebar;
        background: #f8f9fa;
        padding: 3rem 2.5rem;
        border-left: 1px solid #e9ecef;
        display: flex;
        flex-direction: column;
        gap: 2rem;
        position: relative;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 0.75rem;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
        margin-top: 0.5rem;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.75rem;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f8f9fa;
        color: #2c3e50;
        font-family: inherit;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #4facfe;
        background: white;
        box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        transform: translateY(-2px);
    }

    .form-select {
        cursor: pointer;
        background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%234facfe" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 12,15 18,9"></polyline></svg>');
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1rem;
        appearance: none;
        padding-right: 3rem;
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
        font-family: inherit;
    }

    .appointment-time-helper {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 0.5rem;
        font-size: 0.9rem;
        color: #1976d2;
    }

    .form-actions {
        display: flex;
        gap: 0.85rem;
        justify-content: flex-start;
        margin-top: 1.75rem;
        padding-top: 1.25rem;
        border-top: 1px solid #edf0f2;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        border: none;
        padding: 1.25rem 3rem;
        border-radius: 25px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-width: 200px;
    }

    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn-primary:hover::before {
        left: 100%;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(79, 172, 254, 0.4);
    }

    .btn-secondary {
        background: white;
        color: #6c757d;
        border: 2px solid #e9ecef;
        padding: 1.25rem 2rem;
        border-radius: 25px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-secondary:hover {
        border-color: #4facfe;
        color: #4facfe;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .flash-message {
        margin: -3rem -3rem 3rem -3rem;
        padding: 1.5rem 3rem;
        font-weight: 500;
        border-radius: 0;
    }

    .flash-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
    }

    .flash-error {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        color: white;
    }

    .flash-info {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        color: white;
    }

    .booking-tips {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
    }

    .clinic-hours {
        background: white;
        border-radius: 15px;
        padding: 1.5rem; /* reduce padding to give more space for text */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
        overflow: hidden; /* ensure children respect rounded corners */
        font-size: 0.85rem; /* further reduce font size */
    }

    .clinic-hours h3 {
        color: #2c3e50;
        margin-bottom: 1rem;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .hours-grid {
        display: grid;
        gap: 0.75rem;
    }

    .hours-day {
        display: grid;
        grid-template-columns: max-content 1fr; /* day takes what it needs, time gets remaining */
        align-items: center;
        gap: 0.5rem; /* reduce gap to save space */
        padding: 0.5rem 0.75rem; /* reduce vertical padding */
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 1em; /* inherit from clinic-hours */
        overflow: hidden; /* clip inside if extremely narrow */
    }

    .hours-day .day {
        font-weight: 600;
        color: #2c3e50;
    white-space: nowrap; /* keep day on one line */
    }

    .hours-day .time {
        color: #495057;
        white-space: nowrap;           /* no wrapping */
        overflow: hidden;              /* no overflow */
        text-overflow: clip;           /* just hide overflow if any (shouldn't be needed) */
        font-variant-numeric: tabular-nums;
        text-align: right;             /* align to the right edge */
        line-height: 1.25;
    }

    /* Slight extra reduction on very narrow viewports */
    @media (max-width: 360px) {
        .clinic-hours { 
            font-size: 0.8rem; 
            padding: 1rem; /* even tighter padding on small screens */
        }
        .hours-day {
            gap: 0.25rem;
            padding: 0.4rem 0.5rem;
        }
    }

    .hours-day.today {
        background: #e3f2fd;
        border: 1px solid #90caf9;
    }

    .emergency-info {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        color: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
    }

    .emergency-info h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem;
    }

    .emergency-info p {
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .booking-tips h3 {
        color: #2c3e50;
        margin-bottom: 1rem;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .booking-tips ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .booking-tips li {
        padding: 0.5rem 0;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .booking-tips li::before {
        content: "✓";
        background: #48bb78;
        color: white;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .patient-info-card {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 12px;
        padding: 1rem;
        margin-top: 0.5rem;
        display: none;
    }

    .patient-info-card.show {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 900px) {
        .booking-container {
            padding: 0; /* Match desktop to avoid overflow from padding */
        }

        .booking-wrapper {
            grid-template-columns: 1fr;
            grid-template-areas: 
                "header"
                "form"
                "sidebar";
            border-radius: 15px;
        }

        .booking-sidebar {
            border-left: none;
            border-top: 1px solid #e9ecef;
        }

        .form-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .booking-form,
        .booking-sidebar {
            padding: 2rem;
        }

        .booking-header {
            padding: 2.5rem 2rem;
        }

        .booking-header h1 {
            font-size: 2rem;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-primary,
        .btn-secondary {
            width: 100%;
        }
    }

    @media (max-width: 600px) {
        .booking-container {
            padding: 0; /* Keep zero padding on smallest screens */
        }

        .booking-header {
            padding: 2rem 1.5rem;
        }

        .booking-header h1 {
            font-size: 1.75rem;
            flex-direction: column;
            gap: 0.5rem;
        }

        .booking-form,
        .booking-sidebar {
            padding: 1.5rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 0.875rem 1rem;
        }
    }

    /* Loading animation */
    .btn-primary.loading {
        pointer-events: none;
        opacity: 0.7;
    }

    .btn-primary.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        margin: auto;
        border: 2px solid transparent;
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="booking-container">
    <div class="booking-wrapper">
        <div class="booking-header">
            <h1>📅 Book Appointment</h1>
            <p>Schedule your dental care with our expert team</p>
        </div>

        <div class="booking-form">
            <?php 
            $flash = get_flash();
            if ($flash): 
            ?>
                <div class="flash-message flash-<?php echo $_SESSION['flash_type'] ?? 'info'; ?>">
                    <?php echo $flash; ?>
                </div>
            <?php endif; ?>

            <form method="post" id="bookingForm">
                <div class="form-grid">
                    <!-- Patient Selection -->
                    <div class="form-group">
                        <label class="form-label" for="patient_id">
                            👤 Select Patient
                        </label>
                        <select name="patient_id" id="patient_id" class="form-select" required>
                            <option value="">Choose a patient...</option>
                            <?php while ($p = $patients->fetch_assoc()): ?>
                                <option value="<?= $p['patient_id']; ?>">
                                    <?= htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="patient-info-card" id="patientInfo">
                            <strong>Patient Information</strong>
                            <div id="patientDetails"></div>
                        </div>
                    </div>

                    <!-- Dentist Selection -->
                    <div class="form-group">
                        <label class="form-label" for="dentist_id">
                            👨‍⚕️ Select Dentist
                        </label>
                        <?php if (is_dentist()): ?>
                            <?php 
                            $current_dentist_id = get_current_dentist_id();
                            $current_dentist_name = '';
                            if ($dentists && $d = $dentists->fetch_assoc()) {
                                $current_dentist_name = $d['name'];
                            }
                            ?>
                            <input type="hidden" name="dentist_id" value="<?= $current_dentist_id; ?>">
                            <input type="text" class="form-select" value="Dr. <?= htmlspecialchars($current_dentist_name); ?>" readonly>
                            <small class="form-note">You can only book appointments for yourself</small>
                        <?php else: ?>
                            <select name="dentist_id" id="dentist_id" class="form-select" required>
                                <option value="">Choose a dentist...</option>
                                <?php if ($dentists): ?>
                                    <?php while ($d = $dentists->fetch_assoc()): ?>
                                        <option value="<?= $d['dentist_id']; ?>">
                                            Dr. <?= htmlspecialchars($d['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        <?php endif; ?>
                        </select>
                    </div>

                    <!-- Date and Time -->
                    <div class="form-group">
                        <label class="form-label" for="appt_dt">
                            🕐 Appointment Date & Time
                        </label>
                        <input type="datetime-local" name="appt_dt" id="appt_dt" class="form-input" required>
                        <div class="appointment-time-helper">
                            💡 Our clinic hours: Monday-Friday 8:00 AM - 6:00 PM, Saturday 9:00 AM - 2:00 PM
                        </div>
                    </div>

                    <!-- Appointment Type -->
                    <div class="form-group">
                        <label class="form-label" for="appointment_type">
                            🦷 Appointment Type
                        </label>
                        <select name="appointment_type" id="appointment_type" class="form-select">
                            <option value="">Select type (optional)...</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="Filling">Filling</option>
                            <option value="Root Canal">Root Canal</option>
                            <option value="Extraction">Extraction</option>
                            <option value="Orthodontics">Orthodontics</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Follow-up">Follow-up</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="form-group full-width">
                        <label class="form-label" for="notes">
                            📝 Additional Notes
                        </label>
                        <textarea name="notes" id="notes" class="form-textarea" 
                                  placeholder="Please describe your symptoms, concerns, or special requests..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="submitBtn">
                        📅 Schedule Appointment
                    </button>
                    <a href="calendar.php" class="btn-secondary">
                        ❌ Cancel
                    </a>
                </div>
            </form>
        </div>

        <div class="booking-sidebar">
            <div class="clinic-hours">
                <h3>🕐 Clinic Hours</h3>
                <div class="hours-grid">
                    <div class="hours-day">
                        <span class="day">Monday</span>
                        <span class="time">8:00 AM - 6:00 PM</span>
                    </div>
                    <div class="hours-day">
                        <span class="day">Tuesday</span>
                        <span class="time">8:00 AM - 6:00 PM</span>
                    </div>
                    <div class="hours-day">
                        <span class="day">Wednesday</span>
                        <span class="time">8:00 AM - 6:00 PM</span>
                    </div>
                    <div class="hours-day">
                        <span class="day">Thursday</span>
                        <span class="time">8:00 AM - 6:00 PM</span>
                    </div>
                    <div class="hours-day">
                        <span class="day">Friday</span>
                        <span class="time">8:00 AM - 6:00 PM</span>
                    </div>
                    <div class="hours-day">
                        <span class="day">Saturday</span>
                        <span class="time">9:00 AM - 2:00 PM</span>
                    </div>
                    <div class="hours-day">
                        <span class="day">Sunday</span>
                        <span class="time">Closed</span>
                    </div>
                </div>
            </div>

            <div class="booking-tips">
                <h3>💡 Booking Tips</h3>
                <ul>
                    <li>Arrive 15 minutes early for your appointment</li>
                    <li>Bring a valid ID and insurance card</li>
                    <li>List any current medications you're taking</li>
                    <li>Inform us of any allergies or medical conditions</li>
                    <li>Cancel or reschedule at least 24 hours in advance</li>
                </ul>
            </div>

            <div class="emergency-info">
                <h4>🚨 Emergency?</h4>
                <p>For dental emergencies after hours, call our emergency line: <strong>(555) 123-HELP</strong></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const submitBtn = document.getElementById('submitBtn');
    const patientSelect = document.getElementById('patient_id');
    const patientInfo = document.getElementById('patientInfo');
    const appointmentTime = document.getElementById('appt_dt');

    // Set minimum date to today
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    appointmentTime.min = minDateTime;

    // Form submission with loading state
    form.addEventListener('submit', function(e) {
        submitBtn.classList.add('loading');
        submitBtn.textContent = 'Scheduling...';
        submitBtn.disabled = true;
    });

    // Patient selection handler
    patientSelect.addEventListener('change', function() {
        if (this.value) {
            // In a real implementation, you would fetch patient details via AJAX
            patientInfo.classList.add('show');
            document.getElementById('patientDetails').innerHTML = 
                '<p>Patient selected successfully. Appointment will be scheduled for this patient.</p>';
        } else {
            patientInfo.classList.remove('show');
        }
    });

    // Enhanced form validation
    const inputs = form.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#48bb78';
            }
        });

        input.addEventListener('input', function() {
            if (this.value) {
                this.style.borderColor = '#48bb78';
            }
        });
    });

    // Auto-focus on first input
    patientSelect.focus();
});
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>