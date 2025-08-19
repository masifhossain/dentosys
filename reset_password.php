<?php
require_once 'includes/db.php';

echo "<h2>🔧 DentoSys Password Reset - All Users</h2>";

// Hash the new default password
$newPassword = 'Password';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Array of all user emails to update
$userEmails = [
    'admin@dentosys.local',
    's.williams@dentosys.local', 
    'j.chen@dentosys.local',
    'reception@dentosys.local'
];

try {
    $stmt = $conn->prepare("UPDATE usertbl SET password_hash = ? WHERE email = ?");
    
    foreach ($userEmails as $email) {
        $stmt->bind_param("ss", $hashedPassword, $email);
        $result = $stmt->execute();
        
        if ($result && $stmt->affected_rows > 0) {
            echo "<p style='color: green; font-weight: bold;'>✅ Password updated for: {$email}</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ User not found or no update needed: {$email}</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🔍 Password Verification Test</h3>";
    
    // Test the new passwords
    $stmt2 = $conn->prepare("SELECT email, password_hash FROM usertbl WHERE email IN (?, ?, ?, ?)");
    $stmt2->bind_param("ssss", $userEmails[0], $userEmails[1], $userEmails[2], $userEmails[3]);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    while ($row = $result2->fetch_assoc()) {
        if (password_verify($newPassword, $row['password_hash'])) {
            echo "<p style='color: green;'>✅ Password verification PASSED for: {$row['email']}</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification FAILED for: {$row['email']}</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>📋 Summary</h3>";
    echo "<p><strong>New password for all users:</strong> <code>{$newPassword}</code></p>";
    echo "<p><strong>Updated accounts:</strong> " . count($userEmails) . "</p>";
    echo "<p style='color: blue;'>🎉 Password reset complete! You can now login with the new password.</p>";
    
    echo "<hr>";
    echo "<h3>🎯 Login Information:</h3>";
    echo "<ul>";
    echo "<li><strong>URL:</strong> <a href='http://localhost:8000'>http://localhost:8000</a></li>";
    echo "<li><strong>Admin Email:</strong> admin@dentosys.local</li>";
    echo "<li><strong>Password:</strong> Password</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: blue;'>💡 Make sure the database is imported and MySQL is running.</p>";
}

$conn->close();
?>
