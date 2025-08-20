<?php
/*****************************************************************
 * pages/patients/my_prescriptions.php
 * ---------------------------------------------------------------
 * Patient portal - View their own prescriptions
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

/* ───────── Query patient's prescriptions ───────── */
$sql = "SELECT p.*, 
               CONCAT(pt.first_name,' ',pt.last_name) AS patient_name,
               CONCAT(d.first_name,' ',d.last_name) AS dentist_name,
               d.specialty
        FROM Prescriptions p
        JOIN Patient pt ON pt.patient_id = p.patient_id
        LEFT JOIN Dentist den ON den.dentist_id = p.dentist_id
        LEFT JOIN UserTbl u ON u.user_id = den.user_id
        LEFT JOIN (SELECT user_id, 
                         SUBSTRING_INDEX(email, '@', 1) as first_name,
                         'Dr.' as last_name
                   FROM UserTbl) d ON d.user_id = u.user_id
        WHERE p.patient_id = ?
        ORDER BY p.prescribed_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$prescriptions = $stmt->get_result();

/* ───────── HTML ───────── */
include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
    <div class="page-header">
        <h2>💊 My Prescriptions</h2>
    </div>

    <?= get_flash(); ?>

    <div class="prescriptions-container">
        <?php if ($prescriptions->num_rows === 0): ?>
            <div class="no-data">
                <div class="no-data-icon">💊</div>
                <h3>No Prescriptions</h3>
                <p>You don't have any prescriptions yet.</p>
            </div>
        <?php else: ?>
            <div class="prescriptions-grid">
                <?php while ($presc = $prescriptions->fetch_assoc()): ?>
                    <div class="prescription-card">
                        <div class="prescription-header">
                            <div class="prescription-date">
                                <?= date('M j, Y', strtotime($presc['prescribed_date'])); ?>
                            </div>
                            <div class="prescription-doctor">
                                <?= htmlspecialchars($presc['dentist_name'] ?? 'Dr. ' . ($presc['first_name'] ?? 'Unknown')); ?>
                            </div>
                        </div>
                        
                        <div class="medication-name">
                            <?= htmlspecialchars($presc['medication_name']); ?>
                        </div>
                        
                        <div class="dosage-info">
                            <strong>Dosage:</strong> <?= htmlspecialchars($presc['dosage']); ?>
                        </div>
                        
                        <div class="frequency-info">
                            <strong>Frequency:</strong> <?= htmlspecialchars($presc['frequency']); ?>
                        </div>
                        
                        <div class="duration-info">
                            <strong>Duration:</strong> <?= htmlspecialchars($presc['duration']); ?>
                        </div>
                        
                        <?php if (!empty($presc['instructions'])): ?>
                            <div class="instructions">
                                <strong>Instructions:</strong>
                                <p><?= nl2br(htmlspecialchars($presc['instructions'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="prescription-footer">
                            <small>Prescription ID: #<?= $presc['prescription_id']; ?></small>
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

.prescriptions-container {
    margin-top: 20px;
}

.prescriptions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.prescription-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border-left: 4px solid #3498db;
}

.prescription-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.prescription-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ecf0f1;
}

.prescription-date {
    font-weight: bold;
    color: #2c3e50;
}

.prescription-doctor {
    color: #3498db;
    font-weight: 600;
}

.medication-name {
    font-size: 18px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 15px;
}

.dosage-info,
.frequency-info,
.duration-info {
    margin-bottom: 10px;
    color: #2c3e50;
}

.instructions {
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #3498db;
}

.instructions p {
    margin: 5px 0 0 0;
    line-height: 1.5;
}

.prescription-footer {
    margin-top: 15px;
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
    .prescriptions-grid {
        grid-template-columns: 1fr;
    }
    
    .prescription-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
