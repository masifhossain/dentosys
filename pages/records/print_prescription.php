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
               CASE 
                   WHEN TRIM(CONCAT(u.first_name, ' ', u.last_name)) != '' 
                   THEN CONCAT(u.first_name, ' ', u.last_name)
                   ELSE CONCAT('Dr. ', SUBSTRING_INDEX(u.email, '@', 1))
               END AS dentist_name,
               d.specialty,
               u.email AS dentist_email
        FROM Prescriptions pr
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
$clinic_result = $conn->query("SELECT * FROM ClinicInfo LIMIT 1");
if ($clinic_result && $clinic_result->num_rows > 0) {
    $clinic = $clinic_result->fetch_assoc();
    // Ensure all required fields exist with defaults
    $clinic = array_merge([
        'clinic_name' => 'DentoSys Dental Clinic',
        'address' => '',
        'phone' => '+1-555-0123',
        'email' => 'info@dentosys.local'
    ], $clinic);
} else {
    // Default clinic info if table doesn't exist or is empty
    $clinic = [
        'clinic_name' => 'DentoSys Dental Clinic',
        'address' => '123 Main Street, City, State 12345',
        'phone' => '+1-555-0123',
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
            border: 2px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            background: linear-gradient(135deg, #f8f9ff 0%, #e6f2ff 100%);
            border-radius: 8px;
            position: relative;
        }
        .medication-box::before {
            content: "℞";
            position: absolute;
            top: -15px;
            left: 20px;
            background: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 50%;
            font-size: 18px;
            font-weight: bold;
        }
        .medication-name {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0 15px 0;
            color: #007bff;
            padding-left: 40px;
        }
        .medication-details {
            padding-left: 40px;
        }
        .medication-details div {
            margin-bottom: 8px;
            font-size: 14px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .back-link {
            color: #6c757d;
            text-decoration: none;
            margin-left: 15px;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .back-link:hover {
            background: #f8f9fa;
            color: #495057;
            border-color: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="print-button">
            🖨️ Print Prescription
        </button>
        <a href="prescriptions.php" class="back-link">
            ← Back to Prescriptions
        </a>
    </div>

    <div class="header">
        <div class="clinic-name"><?= htmlspecialchars($clinic['clinic_name'] ?? 'DentoSys Dental Clinic'); ?></div>
        <div class="clinic-info">
            <?php if (!empty($clinic['address'])): ?>
                <?= htmlspecialchars($clinic['address']); ?><br>
            <?php endif; ?>
            Phone: <?= htmlspecialchars($clinic['phone'] ?? '+1-555-0123'); ?> | Email: <?= htmlspecialchars($clinic['email'] ?? 'info@dentosys.local'); ?>
        </div>
    </div>

    <div class="prescription-title">PRESCRIPTION</div>

    <div class="patient-info">
        <div class="section-title">Patient Information</div>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span><?= htmlspecialchars($prescription['patient_name']); ?></span>
        </div>
        <?php if (!empty($prescription['dob'])): ?>
        <div class="info-row">
            <span class="info-label">Date of Birth:</span>
            <span><?= date('F d, Y', strtotime($prescription['dob'])); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($prescription['phone'])): ?>
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span><?= htmlspecialchars($prescription['phone']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span><?= date('F d, Y', strtotime($prescription['prescribed_date'])); ?></span>
        </div>
    </div>

    <div class="prescription-details">
        <div class="section-title">Prescription Details</div>
        
        <div class="medication-box">
            <div class="medication-name"><?= htmlspecialchars($prescription['medication_name']); ?></div>
            
            <div class="medication-details">
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
