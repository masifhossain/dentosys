<?php
require_once 'includes/db.php';

echo "<h2>🔧 DentoSys Password Reset</h2>";

// Hash the default password
$newPassword = 'password';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    // Update the admin user password
    $stmt = $conn->prepare("UPDATE usertbl SET password_hash = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $adminEmail);
    
    $adminEmail = 'admin@dentosys.local';
    $result = $stmt->execute();
    
    if ($result && $stmt->affected_rows > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Admin password successfully updated!</p>";
        
        // Test the new password
        $stmt2 = $conn->prepare("SELECT password_hash FROM usertbl WHERE email = ?");
        $stmt2->bind_param("s", $adminEmail);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $row = $result2->fetch_assoc();
        
        if ($row && password_verify($newPassword, $row['password_hash'])) {
            echo "<p style='color: green;'>✅ Password verification test PASSED!</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification test FAILED!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Failed to update password. No rows affected.</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎯 Updated Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@dentosys.local</li>";
    echo "<li><strong>Password:</strong> password</li>";
    echo "<li><strong>Role:</strong> Admin</li>";
    echo "</ul>";
    
    echo "<p><a href='auth/login.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Login Now</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
