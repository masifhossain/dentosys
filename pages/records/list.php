<?php
/*****************************************************************
 * pages/records/list.php
 * ---------------------------------------------------------------
 * Lists all treatment notes (clinical records) with optional
 * patient-filter and quick link to “Add Note”.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_staff(); // Only staff can access clinical records

/* --------------------------------------------------------------
 * 1. Patient dropdown (for filter) - role-based filtering
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
} else {
    $patients = get_patients($conn);     // helper in functions.php - all patients for admin/reception
}

/* --------------------------------------------------------------
 * 2. Build WHERE clause for patient filter and role-based access
 * ------------------------------------------------------------ */
$whereClauses = [];

// Apply role-based filtering for dentists
if (is_dentist()) {
    $current_dentist_id = get_current_dentist_id();
    if ($current_dentist_id) {
        $whereClauses[] = "a.dentist_id = $current_dentist_id";
    } else {
        // If dentist not found, show no records
        $whereClauses[] = "1 = 0";
    }
}

// Apply patient filter if selected
if (!empty($_GET['patient'])) {
    $pid = intval($_GET['patient']);
    $whereClauses[] = "a.patient_id = $pid";
}

$whereSQL = $whereClauses ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

/* --------------------------------------------------------------
 * 3. Retrieve treatment notes (JOIN with Patient for name)
 * ------------------------------------------------------------ */
$sql = "SELECT t.treatment_id,
               DATE(a.appointment_dt) AS day,
               CONCAT(p.first_name,' ',p.last_name) AS patient,
               t.type, t.description, t.cost
        FROM Treatment t
        JOIN Appointment a ON a.appointment_id = t.appointment_id
        JOIN Patient p     ON p.patient_id      = a.patient_id
        $whereSQL
        ORDER BY a.appointment_dt DESC";
$notes = $conn->query($sql);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
    .records-main {
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

    .filters-panel {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: 1fr auto auto auto auto;
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-select {
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        transition: all 0.2s ease;
        min-width: 200px;
    }

    .filter-select:focus {
        border-color: #059669;
        background: white;
        outline: none;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
    }

    .btn-primary, .btn-secondary, .btn-filter {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 2px solid transparent;
        cursor: pointer;
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

    .btn-filter {
        background: #f3f4f6;
        color: #6b7280;
        border-color: #e5e7eb;
    }

    .btn-filter:hover {
        background: #e5e7eb;
        border-color: #d1d5db;
    }

    .records-table-container {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px -12px rgba(0,0,0,0.15);
        border: 1px solid #e2e8f0;
    }

    .records-table {
        width: 100%;
        border-collapse: collapse;
    }

    .records-table th {
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
        font-weight: 700;
        padding: 1rem 1.25rem;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        text-align: left;
    }

    .records-table th:last-child {
        text-align: right;
    }

    .records-table td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.875rem;
        vertical-align: top;
    }

    .records-table td:last-child {
        text-align: right;
        font-weight: 600;
        color: #059669;
    }

    .records-table tbody tr:hover {
        background: #f8fafc;
    }

    .records-table tbody tr:last-child td {
        border-bottom: none;
    }

    .record-id {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #e0f2fe, #b3e5fc);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #0277bd;
        font-size: 0.875rem;
    }

    .patient-info {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .treatment-type {
        display: inline-block;
        background: #e0f2fe;
        color: #0277bd;
        padding: 0.25rem 0.75rem;
        border-radius: 16px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .treatment-description {
        color: #64748b;
        line-height: 1.5;
        margin-top: 0.5rem;
    }

    .date-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
        border: 1px solid #e2e8f0;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #64748b;
    }

    .empty-state .icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.6;
    }

    .empty-state h3 {
        margin: 0 0 0.5rem;
        color: #374151;
        font-size: 1.25rem;
    }

    .empty-state p {
        margin: 0 0 1.5rem;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    @media (max-width: 768px) {
        .records-main { padding: 0 1rem 2rem; }
        .page-header { margin: 0 -1rem 1.5rem; padding: 1.5rem 1rem 2rem; }
        .header-content { flex-direction: column; align-items: stretch; text-align: center; }
        .filters-grid { grid-template-columns: 1fr; gap: 1rem; }
        .header-actions { justify-content: center; }
        .records-table { font-size: 0.8rem; }
        .records-table th, .records-table td { padding: 0.75rem 0.5rem; }
    }
</style>

<main class="records-main">
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <div class="icon-wrapper">📋</div>
                <div>
                    <h1>Treatment Records</h1>
                    <p class="subtitle">Manage and track patient clinical notes</p>
                </div>
            </div>
            <div class="header-actions">
                <?php if (!is_receptionist()): ?>
                <a class="btn-primary" href="add_note.php">
                    <span>📝</span>
                    Add Note
                </a>
                <?php endif; ?>
                <a class="btn-secondary" href="prescriptions.php">
                    <span>💊</span>
                    Prescriptions
                </a>
            </div>
        </div>
    </div>

    <?= get_flash(); ?>

    <div class="filters-panel">
        <form method="get" class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">
                    <span>👤</span>
                    Filter by Patient
                </label>
                <select name="patient" class="filter-select">
                    <option value="">All Patients</option>
                    <?php while ($p = $patients->fetch_assoc()): ?>
                        <option value="<?= $p['patient_id']; ?>"
                            <?= (!empty($_GET['patient']) && $_GET['patient'] == $p['patient_id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
            <a class="btn-secondary" href="list.php">Reset</a>
        </form>
    </div>

    <div class="records-table-container">
        <?php if ($notes->num_rows === 0): ?>
            <div class="empty-state">
                <div class="icon">📋</div>
                <h3>No Treatment Records Found</h3>
                <p>
                    <?php if (is_receptionist()): ?>
                        No treatment notes match your current filter criteria.
                    <?php else: ?>
                        No treatment notes match your current filter criteria. Try adjusting your search or add a new treatment note.
                    <?php endif; ?>
                </p>
                <?php if (!is_receptionist()): ?>
                <a href="add_note.php" class="btn-primary">
                    <span>📝</span>
                    Add First Note
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="records-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Treatment Details</th>
                        <th>Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; while ($n = $notes->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="record-id"><?= $i++; ?></div>
                            </td>
                            <td>
                                <div class="date-badge"><?= date('M j, Y', strtotime($n['day'])); ?></div>
                            </td>
                            <td>
                                <div class="patient-info"><?= htmlspecialchars($n['patient']); ?></div>
                            </td>
                            <td>
                                <div class="treatment-type"><?= htmlspecialchars($n['type']); ?></div>
                                <div class="treatment-description"><?= nl2br(htmlspecialchars($n['description'])); ?></div>
                            </td>
                            <td>$<?= number_format($n['cost'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>