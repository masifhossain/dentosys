-- Add user_id column to Patient table to link patients with user accounts
-- This allows patients created by staff to have login accounts

-- Add user_id column to Patient table
ALTER TABLE Patient 
ADD COLUMN user_id INT(11) NULL AFTER patient_id,
ADD CONSTRAINT fk_patient_user FOREIGN KEY (user_id) REFERENCES UserTbl(user_id) ON DELETE SET NULL;

-- Create index for performance
CREATE INDEX idx_patient_user_id ON Patient(user_id);

-- Update existing patients to have user accounts (if any exist with matching emails)
UPDATE Patient p 
JOIN UserTbl u ON p.email = u.email AND u.role_id = 4 
SET p.user_id = u.user_id 
WHERE p.user_id IS NULL AND p.email IS NOT NULL AND p.email != '';

-- Note: This script should be run after the initial database setup
-- It adds the missing link between Patient records and UserTbl for login functionality
