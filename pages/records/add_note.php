<?php
/*****************************************************************
 * pages/records/add_note.php
 * ---------------------------------------------------------------
 * Add a new treatment note (and optional prescription) for a patient.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Only Admins and Dentists can add treatment notes
if (!is_admin() && !is_dentist()) {
    flash('Access denied. Receptionists have view-only access to treatment records.', 'error');
    redirect('list.php');
}

/* --------------------------------------------------------------
 * 1. Dropdown data - apply role-based filtering
 * ------------------------------------------------------------ */
if (is_dentist()) {
    // For dentists, only show patients they have appointments with
    $patient_ids = get_dentist_patient_ids();
    if (empty($patient_ids)) {
        $patients = [];  // No patients if no appointments
    } else {
        $patient_ids_str = implode(',', $patient_ids);
        $patients = $conn->query(
            "SELECT patient_id, CONCAT(first_name, ' ', last_name) AS name
             FROM Patient 
             WHERE patient_id IN ($patient_ids_str)
             ORDER BY last_name, first_name"
        );
    }
    
    // For dentists, only show themselves in dentist dropdown
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
    $patients = get_patients($conn);
    $dentists = get_dentists($conn);
}

/* --------------------------------------------------------------
 * 2. Handle POST
 * ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Sanitize */
    $patient_id = intval($_POST['patient_id']);
    $dentist_id = intval($_POST['dentist_id']);
    $type       = $conn->real_escape_string(trim($_POST['type']));
    $desc       = $conn->real_escape_string(trim($_POST['description']));
    $cost       = floatval($_POST['cost']);
    $presc      = $conn->real_escape_string(trim($_POST['prescription']));

    /* Basic validation */
    if ($patient_id === 0 || $dentist_id === 0 || $type === '') {
        flash('Patient, dentist, and treatment type are required.');
        redirect('add_note.php');
    }

    /* ----------------------------------------------------------
     * 2a. Ensure there is an appointment today linking patient & dentist
     * -------------------------------------------------------- */
    $today = date('Y-m-d');
    $apptQ = $conn->query(
        "SELECT appointment_id FROM Appointment
         WHERE patient_id = $patient_id
           AND dentist_id  = $dentist_id
           AND DATE(appointment_dt) = '$today'
         LIMIT 1"
    );

    if ($row = $apptQ->fetch_assoc()) {
        $appointment_id = $row['appointment_id'];
    } else {
        /* Create a default “Complete” appointment entry */
        $conn->query(
            "INSERT INTO Appointment
             (patient_id, dentist_id, appointment_dt, status, notes)
             VALUES ($patient_id, $dentist_id, NOW(), 'Complete',
                     'Auto-created for treatment note')"
        );
        $appointment_id = $conn->insert_id;
    }

    /* ----------------------------------------------------------
     * 2b. Insert treatment record
     * -------------------------------------------------------- */
    $stmt = $conn->prepare(
      "INSERT INTO Treatment
       (appointment_id, type, description, cost, prescription)
       VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param('issds',
        $appointment_id,    // i
        $type,              // s
        $desc,              // s
        $cost,              // d
        $presc              // s
    );

    if ($stmt->execute()) {
        flash('Treatment note added.');
        redirect('list.php');
    } else {
        flash('Database error: ' . $conn->error);
    }
}

/* --------------------------------------------------------------
 * 3. Page output
 * ------------------------------------------------------------ */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
    .add-note-main {
        padding: 0 2rem 3rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    .page-header {
        background: linear-gradient(135deg, #059669, #047857);
        margin: 0 -2rem 2rem;
        padding: 2rem 2rem 2.5rem;
        color: white;
        border-radius: 0 0 24px 24px;
        box-shadow: 0 8px 32px -8px rgba(5, 150, 105, 0.3);
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .title-section {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .icon-wrapper {
        width: 60px;
        height: 60px;
        background: rgba(255,255,255,0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        backdrop-filter: blur(10px);
    }

    .page-header h1 {
        margin: 0 0 0.25rem;
        font-size: 2.2rem;
        font-weight: 700;
        letter-spacing: -0.025em;
    }

    .subtitle {
        margin: 0;
        opacity: 0.9;
        font-size: 1rem;
    }

    .header-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px -12px rgba(0,0,0,0.15);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .form-header {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        padding: 2rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .form-header h2 {
        margin: 0 0 0.5rem;
        color: #1e293b;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .form-header p {
        margin: 0;
        color: #64748b;
        font-size: 0.95rem;
    }

    .form-body {
        padding: 2rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        transition: all 0.2s ease;
        font-family: inherit;
        box-sizing: border-box;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        border-color: #059669;
        background: white;
        outline: none;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        transform: translateY(-1px);
    }

    .form-select {
        cursor: pointer;
        background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 12,15 18,9"></polyline></svg>');
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1rem;
        appearance: none;
        padding-right: 3rem;
    }

    .form-textarea {
        resize: vertical;
        min-height: 100px;
        line-height: 1.5;
    }

    .form-textarea.large {
        min-height: 120px;
    }

    .cost-input-wrapper {
        position: relative;
    }

    .cost-input-wrapper::before {
        content: '$';
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-weight: 600;
        color: #059669;
        z-index: 1;
    }

    .cost-input {
        padding-left: 2.5rem !important;
    }

    .form-footer {
        background: #f8fafc;
        padding: 1.5rem 2rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn-primary, .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 2px solid transparent;
        cursor: pointer;
        min-width: 120px;
        justify-content: center;
    }

    .btn-primary {
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
        box-shadow: 0 4px 12px -4px rgba(5, 150, 105, 0.4);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px -4px rgba(5, 150, 105, 0.6);
    }

    .btn-secondary {
        background: white;
        color: #374151;
        border-color: #e5e7eb;
        box-shadow: 0 2px 8px -2px rgba(0,0,0,0.1);
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .input-helper {
        background: #e0f2fe;
        border: 1px solid #b3e5fc;
        border-radius: 8px;
        padding: 0.75rem;
        font-size: 0.8rem;
        color: #0277bd;
        margin-top: 0.5rem;
    }

    .input-helper strong {
        color: #01579b;
    }

    .required-indicator {
        color: #ef4444;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .add-note-main { padding: 0 1rem 2rem; }
        .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
        .header-content { flex-direction: column; align-items: stretch; text-align: center; }
        .form-grid { grid-template-columns: 1fr; gap: 1rem; }
        .form-body, .form-header, .form-footer { padding: 1.5rem; }
        .form-footer { flex-direction: column; }
        .btn-primary, .btn-secondary { width: 100%; }
    }
</style>

<main class="add-note-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">📝</div>
                <div>
                    <h1>Add Treatment Note</h1>
                    <p class="subtitle">Create a new clinical record entry</p>
                </div>
            </div>
            <div class="header-actions">
                <a class="btn-secondary" href="list.php">
                    <span>←</span>
                    Back to Records
                </a>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <div class="form-container">
        <div class="form-header">
            <h2>New Treatment Record</h2>
            <p>Enter the treatment details and patient information below</p>
        </div>

        <form method="post">
            <div class="form-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <span>👤</span>
                            Patient <span class="required-indicator">*</span>
                        </label>
                        <select name="patient_id" required class="form-select">
                            <option value="">Select a patient</option>
                            <?php while ($p = $patients->fetch_assoc()): ?>
                                <option value="<?= $p['patient_id']; ?>">
                                    <?= htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>👨‍⚕️</span>
                            Dentist <span class="required-indicator">*</span>
                        </label>
                        <select name="dentist_id" required class="form-select">
                            <option value="">Select a dentist</option>
                            <?php while ($d = $dentists->fetch_assoc()): ?>
                                <option value="<?= $d['dentist_id']; ?>">
                                    <?= htmlspecialchars($d['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>🏥</span>
                            Treatment Type <span class="required-indicator">*</span>
                        </label>
                        <input type="text" name="type" required class="form-input" placeholder="e.g., Cleaning, Filling, Extraction">
                        <div class="input-helper">
                            <strong>Examples:</strong> Dental Cleaning, Root Canal, Tooth Filling, Orthodontic Adjustment
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>💰</span>
                            Cost <span class="required-indicator">*</span>
                        </label>
                        <div class="cost-input-wrapper">
                            <input type="number" step="0.01" min="0" name="cost" value="0" required class="form-input cost-input">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">
                            <span>📋</span>
                            Treatment Description
                        </label>
                        <textarea name="description" class="form-textarea" placeholder="Detailed description of the treatment performed, observations, and any relevant notes..."></textarea>
                        <div class="input-helper">
                            Include any specific procedures, materials used, patient response, and follow-up instructions.
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">
                            <span>💊</span>
                            Prescription (Optional)
                        </label>
                        <textarea name="prescription" class="form-textarea" placeholder="Medication name, dosage, frequency, duration, and special instructions..."></textarea>
                        <div class="input-helper">
                            <strong>Example:</strong> Amoxicillin 500mg, 3 times daily for 7 days. Take with food to avoid stomach upset.
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-footer">
                <a href="list.php" class="btn-secondary">
                    <span>✖</span>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <span>💾</span>
                    Save Treatment Note
                </button>
            </div>
        </form>
    </div>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>