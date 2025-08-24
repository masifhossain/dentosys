-- Update AuditLog table to support enhanced audit logging
-- Run this script to add new columns for better audit tracking

USE dentosys;

-- First, let's add the new columns to the AuditLog table
ALTER TABLE AuditLog 
ADD COLUMN IF NOT EXISTS action_type VARCHAR(50) AFTER action,
ADD COLUMN IF NOT EXISTS table_name VARCHAR(100) AFTER action_type,
ADD COLUMN IF NOT EXISTS record_id INT AFTER table_name,
ADD COLUMN IF NOT EXISTS details TEXT AFTER record_id,
ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) AFTER details,
ADD COLUMN IF NOT EXISTS user_agent TEXT AFTER ip_address,
ADD COLUMN IF NOT EXISTS session_id VARCHAR(255) AFTER user_agent,
ADD COLUMN IF NOT EXISTS severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'LOW' AFTER session_id;

-- Add index for better performance on common queries
CREATE INDEX IF NOT EXISTS idx_audit_timestamp ON AuditLog(timestamp);
CREATE INDEX IF NOT EXISTS idx_audit_user_id ON AuditLog(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_action_type ON AuditLog(action_type);
CREATE INDEX IF NOT EXISTS idx_audit_table_name ON AuditLog(table_name);
CREATE INDEX IF NOT EXISTS idx_audit_severity ON AuditLog(severity);

-- Insert some sample enhanced audit log entries
INSERT INTO AuditLog (user_id, action, action_type, table_name, record_id, details, ip_address, user_agent, session_id, severity, timestamp) VALUES
(1, 'User created new patient record', 'CREATE', 'Patient', 1, 'Created patient: John Doe, DOB: 1990-01-01', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_123456', 'MEDIUM', NOW() - INTERVAL 5 DAY),
(1, 'Modified appointment status', 'UPDATE', 'Appointment', 5, 'Changed status from Pending to Completed', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_123456', 'LOW', NOW() - INTERVAL 4 DAY),
(2, 'Deleted invoice record', 'DELETE', 'Invoice', 12, 'Deleted invoice #INV-2025-001 for $250.00', '192.168.1.100', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 'sess_789012', 'HIGH', NOW() - INTERVAL 3 DAY),
(1, 'Failed login attempt', 'LOGIN_FAILED', 'UserTbl', NULL, 'Invalid password for admin@dentosys.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NULL, 'MEDIUM', NOW() - INTERVAL 2 DAY),
(3, 'Exported financial report', 'EXPORT', 'Report', NULL, 'Financial report exported for date range: 2025-01-01 to 2025-08-22', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_345678', 'LOW', NOW() - INTERVAL 1 DAY),
(1, 'System backup initiated', 'SYSTEM', 'System', NULL, 'Automated database backup started', NULL, NULL, NULL, 'MEDIUM', NOW() - INTERVAL 6 HOUR),
(2, 'Password changed', 'SECURITY', 'UserTbl', 2, 'User changed their password', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_456789', 'MEDIUM', NOW() - INTERVAL 2 HOUR);
