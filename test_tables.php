<?php
require_once 'includes/db.php';

echo "=== Available Database Tables ===\n\n";

$result = $conn->query("SHOW TABLES");
echo "Tables in dentosys_db:\n";
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "\n";
}

echo "\n=== Checking for clinic/settings tables ===\n";
$tables = ['clinicinfo', 'clinic_info', 'settings', 'clinic_settings'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ $table exists\n";
    } else {
        echo "❌ $table does not exist\n";
    }
}
?>
