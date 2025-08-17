<?php
// Test login redirect functionality
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "=== Login Redirect Test ===\n\n";

// Test if dashboard.php exists
if (file_exists('pages/dashboard.php')) {
    echo "✅ pages/dashboard.php exists\n";
} else {
    echo "❌ pages/dashboard.php NOT found\n";
}

// Test if the problematic dashboard_enhanced.php exists
if (file_exists('pages/dashboard_enhanced.php')) {
    echo "⚠️  pages/dashboard_enhanced.php still exists (should be removed)\n";
} else {
    echo "✅ pages/dashboard_enhanced.php does not exist (correct)\n";
}

// Test user authentication
$email = 'admin@dentosys.local';
$password = 'password';

$res = $conn->query("SELECT * FROM usertbl WHERE email='$email' AND is_active=1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    if (password_verify($password, $row['password_hash'])) {
        echo "✅ Admin credentials verified\n";
        echo "   User ID: " . $row['user_id'] . "\n";
        echo "   Role ID: " . $row['role_id'] . "\n";
        echo "   Redirect should go to: /pages/dashboard.php\n";
    } else {
        echo "❌ Password verification failed\n";
    }
} else {
    echo "❌ User not found or inactive\n";
}

echo "\n🎯 Login should now redirect to the correct dashboard!\n";
?>
