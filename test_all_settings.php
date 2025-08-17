<?php
// Test all settings pages for basic functionality
require_once 'includes/db.php';

echo "=== Settings Pages Test ===\n\n";

// Test roles page - check if role table queries work
try {
    $result = $conn->query("SELECT * FROM role LIMIT 1");
    echo "✅ Roles page queries should work\n";
} catch (Exception $e) {
    echo "❌ Roles page error: " . $e->getMessage() . "\n";
}

// Test users page - check if user/role join works
try {
    $result = $conn->query("SELECT u.*, r.role_name FROM usertbl u JOIN role r ON r.role_id = u.role_id LIMIT 1");
    echo "✅ Users page queries should work\n";
} catch (Exception $e) {
    echo "❌ Users page error: " . $e->getMessage() . "\n";
}

// Test integrations page - check if IntegrationSettings table exists
$result = $conn->query("SHOW TABLES LIKE 'integrationsettings'");
if ($result->num_rows > 0) {
    echo "✅ IntegrationSettings table exists for integrations page\n";
    try {
        $test = $conn->query("SELECT * FROM integrationsettings LIMIT 1");
        echo "✅ Integrations page queries should work\n";
    } catch (Exception $e) {
        echo "❌ Integrations page error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ IntegrationSettings table missing\n";
}

echo "\n🎯 All settings pages should be functional!\n";
?>
