<?php
/**
 * update_audit_database.php
 * Script to update the AuditLog table structure for enhanced audit logging
 */

require_once '../includes/db.php';

echo "Updating AuditLog table structure...\n";

try {
    // Add new columns if they don't exist
    $alterQueries = [
        "ALTER TABLE AuditLog ADD COLUMN IF NOT EXISTS action_type VARCHAR(50) AFTER action",
        "ALTER TABLE AuditLog ADD COLUMN IF NOT EXISTS table_name VARCHAR(100) AFTER action_type",
        "ALTER TABLE AuditLog ADD COLUMN IF NOT EXISTS record_id INT AFTER table_name",
        "ALTER TABLE AuditLog ADD COLUMN IF NOT EXISTS details TEXT AFTER record_id",
        "ALTER TABLE AuditLog ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) AFTER details",
        "ALTER TABLE AuditLog ADD COLUMN IF NOT EXISTS user_agent TEXT AFTER ip_address",
        "ALTER TABLE AuditLog ADD COLUMN IF NOT EXISTS session_id VARCHAR(255) AFTER user_agent",
        "ALTER TABLE AuditLog ADD COLUMN IF NOT EXISTS severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'LOW' AFTER session_id"
    ];

    foreach ($alterQueries as $query) {
        $conn->query($query);
    }

    // Add indexes for performance
    $indexQueries = [
        "CREATE INDEX IF NOT EXISTS idx_audit_timestamp ON AuditLog(timestamp)",
        "CREATE INDEX IF NOT EXISTS idx_audit_user_id ON AuditLog(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_audit_action_type ON AuditLog(action_type)",
        "CREATE INDEX IF NOT EXISTS idx_audit_table_name ON AuditLog(table_name)",
        "CREATE INDEX IF NOT EXISTS idx_audit_severity ON AuditLog(severity)"
    ];

    foreach ($indexQueries as $query) {
        $conn->query($query);
    }

    // Insert sample data
    $sampleData = [
        [1, 'User created new patient record', 'CREATE', 'Patient', 1, 'Created patient: John Doe, DOB: 1990-01-01', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_123456', 'MEDIUM'],
        [1, 'Modified appointment status', 'UPDATE', 'Appointment', 5, 'Changed status from Pending to Completed', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_123456', 'LOW'],
        [1, 'Deleted invoice record', 'DELETE', 'Invoice', 12, 'Deleted invoice #INV-2025-001 for $250.00', '192.168.1.100', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 'sess_789012', 'HIGH'],
        [1, 'Failed login attempt', 'LOGIN_FAILED', 'UserTbl', null, 'Invalid password for admin@dentosys.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', null, 'MEDIUM'],
        [1, 'Exported financial report', 'EXPORT', 'Report', null, 'Financial report exported for date range: 2025-01-01 to 2025-08-22', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_345678', 'LOW'],
        [null, 'System backup initiated', 'SYSTEM', 'System', null, 'Automated database backup started', null, null, null, 'MEDIUM'],
        [1, 'Password changed', 'SECURITY', 'UserTbl', 1, 'User changed their password', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_456789', 'MEDIUM']
    ];

    $stmt = $conn->prepare("
        INSERT INTO AuditLog 
        (user_id, action, action_type, table_name, record_id, details, ip_address, user_agent, session_id, severity, timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
    ");

    $days = [5, 4, 3, 2, 1, 0, 0];
    for ($i = 0; $i < count($sampleData); $i++) {
        $data = $sampleData[$i];
        $stmt->bind_param(
            "ississssssi",
            $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $days[$i]
        );
        $stmt->execute();
    }

    echo "✅ AuditLog table updated successfully!\n";
    echo "✅ Sample data inserted!\n";
    echo "✅ Indexes created for better performance!\n";
    echo "\nEnhanced audit logging is now available with the following features:\n";
    echo "- Action types (CREATE, UPDATE, DELETE, LOGIN, EXPORT, etc.)\n";
    echo "- Severity levels (LOW, MEDIUM, HIGH, CRITICAL)\n";
    echo "- Table and record tracking\n";
    echo "- IP address and session tracking\n";
    echo "- Detailed descriptions\n";
    echo "- Advanced filtering and search\n";

} catch (Exception $e) {
    echo "❌ Error updating database: " . $e->getMessage() . "\n";
}
?>
