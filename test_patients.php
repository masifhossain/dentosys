<?php
// Test patient list queries
require_once 'includes/db.php';

echo "=== Patient List Query Test ===\n\n";

// Test basic patient query
$sql = "SELECT *, 
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age,
        'Recently added' AS joined_date
        FROM patient 
        ORDER BY last_name, first_name";

$result = $conn->query($sql);
if ($result) {
    echo "✅ Main patient query works\n";
    echo "   Found " . $result->num_rows . " patients\n";
    
    // Show first patient as sample
    if ($row = $result->fetch_assoc()) {
        echo "   Sample: " . $row['first_name'] . " " . $row['last_name'] . " (Age: " . $row['age'] . ")\n";
    }
} else {
    echo "❌ Patient query failed: " . $conn->error . "\n";
}

// Test statistics queries
$total_patients = $conn->query("SELECT COUNT(*) as c FROM patient");
if ($total_patients) {
    $count = $total_patients->fetch_assoc()['c'];
    echo "✅ Total patients count: $count\n";
} else {
    echo "❌ Total patients query failed: " . $conn->error . "\n";
}

// Test birthday query
$birthdays = $conn->query("SELECT COUNT(*) as c FROM patient WHERE MONTH(dob) = MONTH(CURDATE())");
if ($birthdays) {
    $count = $birthdays->fetch_assoc()['c'];
    echo "✅ Birthdays this month: $count\n";
} else {
    echo "❌ Birthday query failed: " . $conn->error . "\n";
}

echo "\n🎉 Patient list should work now!\n";
?>
