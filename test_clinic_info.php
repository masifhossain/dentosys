<?php
// Test clinic info functionality
require_once 'includes/db.php';

echo "=== Clinic Info Test ===\n\n";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'clinicinfo'");
if ($result->num_rows > 0) {
    echo "✅ clinicinfo table exists\n";
    
    // Check table structure
    $result = $conn->query("DESCRIBE clinicinfo");
    echo "Table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Test insert/select
    $conn->query("INSERT IGNORE INTO clinicinfo (id) VALUES (1)");
    $info = $conn->query("SELECT * FROM clinicinfo WHERE id = 1")->fetch_assoc();
    
    if ($info) {
        echo "✅ Clinic info record exists\n";
        echo "  Clinic Name: " . ($info['clinic_name'] ?? 'N/A') . "\n";
        echo "  Phone: " . ($info['phone'] ?? 'N/A') . "\n";
    } else {
        echo "❌ No clinic info record found\n";
    }
    
} else {
    echo "❌ clinicinfo table does not exist\n";
}

echo "\n🎉 Settings should work now!\n";
?>
