<?php
/*****************************************************************
 * pages/patients/my_records.php
 * ---------------------------------------------------------------
 * Patient portal - View their own clinical records
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Only allow patients to view this page
if (($_SESSION['role'] ?? 0) !== 4) {
    flash('Access denied. Patients only.');
    redirect('/dentosys/index.php');
}

$patient_id = $_SESSION['patient_id'] ?? 0;
if (!$patient_id) {
    flash('Patient information not found.');
    redirect('/dentosys/auth/login.php');
}

/* ───────── Query patient's clinical records ───────── */
$sql = "SELECT cr.*, 
               CONCAT(pt.first_name,' ',pt.last_name) AS patient_name,
               CONCAT(d.first_name,' ',d.last_name) AS dentist_name,
               d.specialty
        FROM ClinicalRecord cr
        JOIN Patient pt ON pt.patient_id = cr.patient_id
        LEFT JOIN Dentist den ON den.dentist_id = cr.dentist_id
        LEFT JOIN UserTbl u ON u.user_id = den.user_id
        LEFT JOIN (SELECT user_id, 
                         SUBSTRING_INDEX(email, '@', 1) as first_name,
                         'Dr.' as last_name
                   FROM UserTbl) d ON d.user_id = u.user_id
        WHERE cr.patient_id = ?
        ORDER BY cr.visit_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$records = $stmt->get_result();

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
    <div class="page-header">
        <h2>📋 My Clinical Records</h2>
    </div>

    <?= get_flash(); ?>

    <div class="records-container">
        <?php if ($records->num_rows === 0): ?>
            <div class="no-data">
                <div class="no-data-icon">📋</div>
                <h3>No Clinical Records</h3>
                <p>You don't have any clinical records yet.</p>
            </div>
        <?php else: ?>
            <div class="records-timeline">
                <?php while ($record = $records->fetch_assoc()): ?>
                    <div class="record-card">
                        <div class="record-header">
                            <div class="visit-date">
                                <div class="date-day"><?= date('j', strtotime($record['visit_date'])); ?></div>
                                <div class="date-month"><?= date('M Y', strtotime($record['visit_date'])); ?></div>
                            </div>
                            <div class="record-info">
                                <div class="treatment-type">
                                    <?= htmlspecialchars($record['treatment_type'] ?? 'General Consultation'); ?>
                                </div>
                                <div class="dentist-name">
                                    <?= htmlspecialchars($record['dentist_name'] ?? 'Dr. ' . ($record['first_name'] ?? 'Unknown')); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($record['chief_complaint'])): ?>
                            <div class="record-section">
                                <h4>Chief Complaint</h4>
                                <p><?= nl2br(htmlspecialchars($record['chief_complaint'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($record['examination_findings'])): ?>
                            <div class="record-section">
                                <h4>Examination Findings</h4>
                                <p><?= nl2br(htmlspecialchars($record['examination_findings'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($record['diagnosis'])): ?>
                            <div class="record-section diagnosis">
                                <h4>Diagnosis</h4>
                                <p><?= nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($record['treatment_performed'])): ?>
                            <div class="record-section treatment">
                                <h4>Treatment Performed</h4>
                                <p><?= nl2br(htmlspecialchars($record['treatment_performed'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($record['treatment_plan'])): ?>
                            <div class="record-section plan">
                                <h4>Treatment Plan</h4>
                                <p><?= nl2br(htmlspecialchars($record['treatment_plan'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($record['notes'])): ?>
                            <div class="record-section notes">
                                <h4>Additional Notes</h4>
                                <p><?= nl2br(htmlspecialchars($record['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="record-footer">
                            <small>Record ID: #<?= $record['record_id']; ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.page-header {
    margin-bottom: 30px;
}

.records-container {
    margin-top: 20px;
}

.records-timeline {
    max-width: 800px;
    margin: 0 auto;
}

.record-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.record-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.record-card::before {
    content: '';
    position: absolute;
    left: -5px;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-radius: 2px;
}

.record-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #ecf0f1;
}

.visit-date {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    min-width: 80px;
}

.date-day {
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
}

.date-month {
    font-size: 12px;
    margin-top: 2px;
    opacity: 0.9;
}

.record-info {
    flex: 1;
}

.treatment-type {
    font-size: 18px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.dentist-name {
    color: #3498db;
    font-weight: 600;
}

.record-section {
    margin-bottom: 20px;
}

.record-section h4 {
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.record-section p {
    color: #34495e;
    line-height: 1.6;
    margin: 0;
}

.record-section.diagnosis {
    background: #fff5f5;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #e74c3c;
}

.record-section.treatment {
    background: #f0fff4;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #27ae60;
}

.record-section.plan {
    background: #f8f9ff;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #8e44ad;
}

.record-section.notes {
    background: #fffbf0;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #f39c12;
}

.record-footer {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ecf0f1;
    text-align: right;
    color: #7f8c8d;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.no-data-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.no-data h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .record-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .visit-date {
        min-width: auto;
    }
    
    .record-card {
        padding: 20px;
    }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
