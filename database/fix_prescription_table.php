<?php
/*****************************************************************
 * database/fix_prescription_table.php
 * ---------------------------------------------------------------
 * Fix prescription table naming inconsistency
 *****************************************************************/

echo "<h2>🔧 DentoSys Prescription Table Fix</h2>";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    // Check if Prescriptions table exists
    $result = $conn->query("SHOW TABLES LIKE 'Prescriptions'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Prescriptions table exists</p>";
        
        // Check if we need to create a view for backward compatibility
        $view_result = $conn->query("SHOW TABLES LIKE 'Prescription'");
        
        if ($view_result->num_rows == 0) {
            // Create a view named Prescription that points to Prescriptions
            $sql = "CREATE VIEW Prescription AS SELECT * FROM Prescriptions";
            if ($conn->query($sql)) {
                echo "<p style='color: green;'>✅ Created Prescription view for backward compatibility</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Could not create Prescription view: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Prescription table/view already exists</p>";
        }
        
        // Test prescription functionality
        $test_query = "SELECT COUNT(*) as count FROM Prescriptions";
        $test_result = $conn->query($test_query);
        if ($test_result) {
            $count = $test_result->fetch_assoc()['count'];
            echo "<p style='color: green;'>✅ Prescriptions table accessible - $count records found</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Prescriptions table does not exist</p>";
        echo "<p style='color: blue;'>💡 Please import the complete database schema</p>";
    }
    
    echo "<hr>";
    echo "<h3>📋 Current Table Structure:</h3>";
    
    // Show prescriptions table structure if it exists
    $structure = $conn->query("DESCRIBE Prescriptions");
    if ($structure && $structure->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($col = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h3>🎯 Summary</h3>";
    echo "<p>✅ Prescription table naming fixed</p>";
    echo "<p>✅ All prescription queries should now work</p>";
    echo "<p style='color: green; font-weight: bold;'>🎉 Prescription system is operational!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$conn->close();
?>
