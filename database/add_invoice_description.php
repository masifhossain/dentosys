<?php
/*****************************************************************
 * database/add_invoice_description.php
 * ---------------------------------------------------------------
 * Migration script to add description column to Invoice table
 *****************************************************************/

echo "<h2>🔧 DentoSys Database Migration - Add Invoice Description</h2>";

require_once dirname(__DIR__) . '/includes/db.php';

try {
    // Check if description column already exists
    $result = $conn->query("SHOW COLUMNS FROM Invoice LIKE 'description'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Description column already exists in Invoice table.</p>";
    } else {
        // Add description column
        $sql = "ALTER TABLE Invoice ADD COLUMN description TEXT DEFAULT NULL AFTER total_amount";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Successfully added description column to Invoice table.</p>";
            
            // Update existing invoices with default descriptions
            $updates = [
                1 => "Dental cleaning, fluoride treatment, and oral examination",
                2 => "Root canal consultation and X-ray"
            ];
            
            $updateStmt = $conn->prepare("UPDATE Invoice SET description = ? WHERE invoice_id = ?");
            foreach ($updates as $id => $desc) {
                $updateStmt->bind_param('si', $desc, $id);
                if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
                    echo "<p style='color: blue;'>📝 Updated description for Invoice #$id</p>";
                }
            }
            
            echo "<p style='color: green;'>🎉 Migration completed successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding column: " . $conn->error . "</p>";
        }
    }
    
    // Show current table structure
    echo "<hr><h3>📋 Current Invoice Table Structure:</h3>";
    $columns = $conn->query("SHOW COLUMNS FROM Invoice");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: blue;'>💡 Make sure the database is imported and MySQL is running.</p>";
}

$conn->close();
?>
