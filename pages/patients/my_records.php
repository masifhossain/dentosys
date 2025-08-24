<?php
/*****************************************************************
 * pages/patients/my_records.php
 * ---------------------------------------------------------------
 * Patient portal - View their own clinical records (read-only)
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_patient(); // Only patients can access their records

$user_id = $_SESSION['user_id'];

// Resolve patient_id from logged-in user
$patient_query = $conn->query("\n    SELECT p.patient_id, p.first_name, p.last_name\n    FROM patient p\n    JOIN usertbl u ON u.email = p.email\n    WHERE u.user_id = $user_id\n    LIMIT 1\n");

$patient = $patient_query->fetch_assoc();
if (!$patient) {
        flash('Patient information not found.');
        redirect('/dentosys/');
}
$patient_id = (int)$patient['patient_id'];

/* ───────── Query patient's clinical records using Treatment + Appointment ───────── */
$records_sql = "\n    SELECT t.*, a.appointment_dt, d.user_id AS dentist_user_id, u.email AS dentist_email\n    FROM Treatment t\n    JOIN Appointment a ON a.appointment_id = t.appointment_id\n    JOIN Dentist d ON d.dentist_id = a.dentist_id\n    JOIN UserTbl u ON u.user_id = d.user_id\n    WHERE a.patient_id = $patient_id\n    ORDER BY a.appointment_dt DESC, t.treatment_id DESC\n";
$records = $conn->query($records_sql);

/* ───────── HTML ───────── */
$pageTitle = 'My Clinical Records';
include BASE_PATH . '/templates/header.php';
?>

<div class="main-wrapper">
    <?php include BASE_PATH . '/templates/sidebar.php'; ?>

    <main class="content">
        <header class="content-header">
            <h1>📋 My Clinical Records</h1>
            <p>View treatments recorded from your appointments</p>
        </header>

        <?= get_flash(); ?>

        <div class="records-container">
            <?php if ($records->num_rows === 0): ?>
                <div class="no-data">
                    <div class="no-data-icon">📋</div>
                    <h3>No Clinical Records</h3>
                    <p>You don't have any recorded treatments yet.</p>
                </div>
            <?php else: ?>
                <div class="records-timeline">
                    <?php while ($rec = $records->fetch_assoc()): ?>
                        <div class="record-card">
                            <div class="record-header">
                                <div class="visit-date">
                                    <div class="date-day"><?= date('j', strtotime($rec['appointment_dt'])); ?></div>
                                    <div class="date-month"><?= date('M Y', strtotime($rec['appointment_dt'])); ?></div>
                                </div>
                                <div class="record-info">
                                    <div class="treatment-type">
                                        <?= htmlspecialchars($rec['type'] ?? 'Treatment'); ?>
                                    </div>
                                    <div class="dentist-name">
                                        <?= htmlspecialchars($rec['dentist_email']); ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($rec['description'])): ?>
                                <div class="record-section treatment">
                                    <h4>Description</h4>
                                    <p><?= nl2br(htmlspecialchars($rec['description'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($rec['prescription'])): ?>
                                <div class="record-section plan">
                                    <h4>Prescription/Plan</h4>
                                    <p><?= nl2br(htmlspecialchars($rec['prescription'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($rec['cost'])): ?>
                                <div class="record-section">
                                    <h4>Cost</h4>
                                    <p>$<?= number_format($rec['cost'], 2); ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="record-footer">
                                <small>Treatment ID: #<?= $rec['treatment_id']; ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
/* Layout: center content to the right of the sidebar */
.main-wrapper { width: 100%; }
main.content { width: 100%; max-width: none; margin: 0; display: flex; flex-direction: column; align-items: center; min-height: calc(100vh - 60px); }
.content-header { width: 100%; display: flex; flex-direction: column; align-items: center; text-align: center; }

.records-container { width: 100%; }
.records-timeline { width: 100%; max-width: 900px; margin: 0 auto; }

.record-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    position: relative;
}

.record-card:hover { transform: translateY(-2px); box-shadow: 0 10px 18px rgba(0,0,0,0.10); }

.record-card::before {
    content: '';
    position: absolute;
    left: -5px;
    top: 0; bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-radius: 2px;
}

.record-header { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #ecf0f1; }

.visit-date { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 15px; border-radius: 12px; text-align: center; min-width: 80px; }
.date-day { font-size: 24px; font-weight: bold; line-height: 1; }
.date-month { font-size: 12px; margin-top: 2px; opacity: 0.9; }

.record-info { flex: 1; }
.treatment-type { font-size: 18px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
.dentist-name { color: #3498db; font-weight: 600; }

.record-section { margin-bottom: 16px; }
.record-section h4 { color: #2c3e50; margin-bottom: 8px; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.record-section p { color: #34495e; line-height: 1.6; margin: 0; }
.record-section.plan { background: #f8f9ff; padding: 15px; border-radius: 8px; border-left: 4px solid #8e44ad; }
.record-section.treatment { background: #f0fff4; padding: 15px; border-radius: 8px; border-left: 4px solid #27ae60; }

.record-footer { margin-top: 12px; padding-top: 12px; border-top: 1px solid #ecf0f1; text-align: right; color: #7f8c8d; }

.no-data { text-align: center; padding: 60px 20px; color: #7f8c8d; }
.no-data-icon { font-size: 48px; margin-bottom: 20px; }
.no-data h3 { color: #2c3e50; margin-bottom: 10px; }

@media (max-width: 768px) {
    .record-header { flex-direction: column; text-align: center; gap: 15px; }
    .visit-date { min-width: auto; }
    .record-card { padding: 20px; }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
