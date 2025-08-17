<?php
require_once 'includes/db.php';

echo "<h2>🔍 DentoSys User Credentials Check</h2>";

try {
    // First, let's see the table structure
    echo "<h3>📋 Table Structure:</h3>";
    $result = $conn->query("DESCRIBE usertbl");
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th style='padding: 8px;'>Field</th><th style='padding: 8px;'>Type</th><th style='padding: 8px;'>Null</th><th style='padding: 8px;'>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Now let's get all users
    echo "<h3>👥 All Users:</h3>";
    $result = $conn->query("SELECT u.*, r.role_name FROM usertbl u LEFT JOIN role r ON u.role_id = r.role_id ORDER BY u.role_id");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    if (!empty($users)) {
        foreach (array_keys($users[0]) as $column) {
            echo "<th style='padding: 10px;'>" . htmlspecialchars($column) . "</th>";
        }
    }
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        foreach ($user as $key => $value) {
            if ($key === 'password_hash') {
                echo "<td style='padding: 8px; font-family: monospace; font-size: 12px;'>" . substr(htmlspecialchars($value), 0, 20) . "...</td>";
            } else {
                echo "<td style='padding: 8px;'>" . htmlspecialchars($value) . "</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Test password verification
    echo "<h3>🔐 Password Verification Test</h3>";
    
    $testEmail = 'admin@dentosys.local';
    $testPassword = 'password';
    
    $stmt = $conn->prepare("SELECT password_hash FROM usertbl WHERE email = ?");
    $stmt->bind_param("s", $testEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $storedPassword = $row ? $row['password_hash'] : null;
    
    if ($storedPassword) {
        echo "<p><strong>Email:</strong> $testEmail</p>";
        echo "<p><strong>Test Password:</strong> $testPassword</p>";
        echo "<p><strong>Stored Hash:</strong> " . substr($storedPassword, 0, 30) . "...</p>";
        
        if (password_verify($testPassword, $storedPassword)) {
            echo "<p style='color: green; font-weight: bold;'>✅ Password verification SUCCESSFUL!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Password verification FAILED!</p>";
            
            // Try direct comparison (in case password isn't hashed)
            if ($testPassword === $storedPassword) {
                echo "<p style='color: orange;'>⚠️ Password stored as plain text (not hashed)</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ No user found with email: $testEmail</p>";
    }
    
    echo "<hr>";
    echo "<h3>💡 Login Instructions</h3>";
    echo "<p>Use these credentials to login:</p>";
    echo "<ul>";
    foreach ($users as $user) {
        if ($user['role'] === 'Admin') {
            echo "<li><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</li>";
            echo "<li><strong>Password:</strong> password (try this first)</li>";
        }
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
