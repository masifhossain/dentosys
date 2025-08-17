<?php
require_once 'includes/db.php';

echo "=== Database Schema Check ===\n\n";

// Check Dentist table
echo "DENTIST TABLE:\n";
$result = $conn->query("DESCRIBE dentist");
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nPATIENT TABLE:\n";
$result = $conn->query("DESCRIBE patient");
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nAPPOINTMENT TABLE:\n";
$result = $conn->query("DESCRIBE appointment");
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
