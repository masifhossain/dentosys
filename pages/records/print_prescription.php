<?php
/*****************************************************************
 * pages/records/print_prescription.php
 * ---------------------------------------------------------------
 * Print-friendly prescription format
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    flash('Invalid prescription ID.');
    redirect('prescriptions.php');
}

/* ───────── Fetch prescription details ───────── */
$sql = "SELECT pr.*, 
               CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
               p.dob, p.phone, p.address,
               CONCAT(u.email) AS dentist_name,
               d.specialty
        FROM Prescription pr
        JOIN Patient p ON p.patient_id = pr.patient_id
        JOIN Dentist d ON d.dentist_id = pr.dentist_id
        JOIN UserTbl u ON u.user_id = d.user_id
        WHERE pr.prescription_id = $id LIMIT 1";

$result = $conn->query($sql);
$prescription = $result->fetch_assoc();

if (!$prescription) {
    flash('Prescription not found.');
    redirect('prescriptions.php');
}

/* ───────── Get clinic info ───────── */
$clinic = $conn->query("SELECT * FROM ClinicInfo LIMIT 1")->fetch_assoc();
if (!$clinic) {
    // Default clinic info if table doesn't exist
    $clinic = [
        'clinic_name' => 'DentoSys Dental Clinic',
        'address' => '123 Main Street, City, State 12345',
        'phone' => '(555) 123-4567',
        'email' => 'info@dentosys.local'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - <?= htmlspecialchars($prescription['patient_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', serif;
            line-height: 1.5;
            color: #000;
            background: #fff;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .clinic-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .clinic-info {
            font-size: 14px;
            color: #666;
        }
        .prescription-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }
        .patient-info, .prescription-details {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
            flex-shrink: 0;
        }
        .medication-box {
            border: 1px solid #000;
            padding: 15px;
            margin: 20px 0;
            background: #f9f9f9;
        }
        .medication-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .sig {
            margin: 15px 0;
            padding: 10px;
            background: #fff;
            border: 1px dashed #666;
        }
        .sig-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
        .signature-area {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 40px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
        .print-button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="print-button">🖨️ Print Prescription</button>
        <a href="prescriptions.php" style="margin-left: 10px;">← Back to Prescriptions</a>
    </div>

    <div class="header">
        <div class="clinic-name"><?= htmlspecialchars($clinic['clinic_name']); ?></div>
        <div class="clinic-info">
            <?= htmlspecialchars($clinic['address']); ?><br>
            Phone: <?= htmlspecialchars($clinic['phone']); ?> | Email: <?= htmlspecialchars($clinic['email']); ?>
        </div>
    </div>

    <div class="prescription-title">PRESCRIPTION</div>

    <div class="patient-info">
        <div class="section-title">Patient Information</div>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span><?= htmlspecialchars($prescription['patient_name']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date of Birth:</span>
            <span><?= htmlspecialchars($prescription['dob']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span><?= htmlspecialchars($prescription['phone']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span><?= date('F d, Y', strtotime($prescription['prescribed_date'])); ?></span>
        </div>
    </div>

    <div class="prescription-details">
        <div class="section-title">Prescription Details</div>
        
        <div class="medication-box">
            <div class="medication-name">℞ <?= htmlspecialchars($prescription['medication_name']); ?></div>
            
            <?php if ($prescription['dosage']): ?>
                <div><strong>Dosage:</strong> <?= htmlspecialchars($prescription['dosage']); ?></div>
            <?php endif; ?>
            
            <?php if ($prescription['frequency']): ?>
                <div><strong>Frequency:</strong> <?= htmlspecialchars($prescription['frequency']); ?></div>
            <?php endif; ?>
            
            <?php if ($prescription['duration']): ?>
                <div><strong>Duration:</strong> <?= htmlspecialchars($prescription['duration']); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($prescription['instructions']): ?>
            <div class="sig">
                <div class="sig-label">Special Instructions:</div>
                <div><?= nl2br(htmlspecialchars($prescription['instructions'])); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div>
            <div><strong>Prescribing Dentist:</strong></div>
            <div><?= htmlspecialchars($prescription['dentist_name']); ?></div>
            <?php if ($prescription['specialty']): ?>
                <div><em><?= htmlspecialchars($prescription['specialty']); ?></em></div>
            <?php endif; ?>
        </div>
        
        <div class="signature-area">
            <div class="signature-line"></div>
            <div>Doctor's Signature</div>
        </div>
    </div>

    <div style="margin-top: 30px; font-size: 12px; text-align: center; color: #666;">
        Generated on <?= date('F d, Y \a\t g:i A'); ?> | Prescription ID: #<?= $prescription['prescription_id']; ?>
    </div>
</body>
</html>
