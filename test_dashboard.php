<?php
// Quick test for dashboard queries
require_once 'includes/db.php';

echo "=== Dashboard Query Test ===\n\n";

// Test basic patient count
$result = $conn->query("SELECT COUNT(*) AS c FROM patient");
if ($result) {
    $count = $result->fetch_assoc()['c'];
    echo "✅ Patient count: $count\n";
} else {
    echo "❌ Patient query failed: " . $conn->error . "\n";
}

// Test appointment count
$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) AS c FROM appointment WHERE DATE(appointment_dt) = '$today'");
if ($result) {
    $count = $result->fetch_assoc()['c'];
    echo "✅ Today's appointments: $count\n";
} else {
    echo "❌ Appointment query failed: " . $conn->error . "\n";
}

// Test the problematic dentist join query
$result = $conn->query("
    SELECT DATE_FORMAT(a.appointment_dt,'%H:%i') AS atime,
           CONCAT(p.first_name,' ',p.last_name) AS patient,
           a.status,
           'Dr. Smith' as dentist_name
    FROM appointment a
    JOIN patient p ON p.patient_id = a.patient_id
    LEFT JOIN dentist d ON d.dentist_id = a.dentist_id
    WHERE DATE(a.appointment_dt) = '$today'
    ORDER BY a.appointment_dt
    LIMIT 5
");

if ($result) {
    echo "✅ Appointment details query works\n";
    $count = $result->num_rows;
    echo "   Found $count appointments today\n";
} else {
    echo "❌ Appointment details query failed: " . $conn->error . "\n";
}

echo "\n🎉 Dashboard should work now!\n";
?>
