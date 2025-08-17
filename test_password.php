<?php
// Test script to check password hash
$hashes = [
    'admin@dentosys.local' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'masifhossain@dentosys.local' => '$2y$10$tJ98sTmpJVX2a.Y4sLWL0.l6LmUclQmKduk5p73l6Ky2PXVuf2dAS'
];

$common_passwords = [
    'password',
    'admin',
    'dentosys',
    '123456',
    'admin123',
    'secret',
    'password123',
    'masif123',
    'masif',
    'hossain'
];

foreach ($hashes as $email => $stored_hash) {
    echo "\nTesting passwords for $email:\n";
    $found = false;
    foreach ($common_passwords as $password) {
        if (password_verify($password, $stored_hash)) {
            echo "✅ MATCH FOUND: '$password'\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "❌ No common password matched for $email\n";
    }
}
?>
