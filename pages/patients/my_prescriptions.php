<?php
/*****************************************************************
 * pages/patients/my_prescriptions.php
 * ---------------------------------------------------------------
 * Patient portal - View their own prescriptions
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_patient(); // Only patients can access their prescriptions

// Resolve patient_id from the logged-in user account (avoids missing session patient_id)
$user_id = $_SESSION['user_id'];
$patient_q = $conn->query("\n    SELECT p.patient_id\n    FROM patient p\n    JOIN usertbl u ON u.email = p.email\n    WHERE u.user_id = $user_id\n    LIMIT 1\n");
$patient_row = $patient_q ? $patient_q->fetch_assoc() : null;
if (!$patient_row) {
    flash('Patient information not found.');
    redirect('/dentosys/auth/logout.php');
}
$patient_id = (int)$patient_row['patient_id'];

/* ───────── Query patient's prescriptions ───────── */
$sql = "SELECT p.*, 
         CONCAT(pt.first_name,' ',pt.last_name) AS patient_name,
         CONCAT('Dr. ', SUBSTRING_INDEX(u.email, '@', 1)) AS dentist_name,
         den.specialty
     FROM Prescriptions p
     JOIN Patient pt ON pt.patient_id = p.patient_id
     LEFT JOIN Dentist den ON den.dentist_id = p.dentist_id
     LEFT JOIN UserTbl u ON u.user_id = den.user_id
     WHERE p.patient_id = ?
     ORDER BY p.prescribed_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$prescriptions = $stmt->get_result();

/* ───────── HTML ───────── */
$pageTitle = 'My Prescriptions';
include BASE_PATH . '/templates/header.php';
?>

<div class="main-wrapper patient-page full-width">
    <?php include BASE_PATH . '/templates/sidebar.php'; ?>
    <main class="content">
        <div class="page-container">
            <header class="content-header">
                <h1>💊 My Prescriptions</h1>
                <p class="subtitle">View your prescriptions history</p>
            </header>

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
                                <?= htmlspecialchars($presc['dentist_name'] ?? ''); ?>
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
    </div>
    </main>
</div>

<style>
/* Remove inline styles as they're now in shared CSS */
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
