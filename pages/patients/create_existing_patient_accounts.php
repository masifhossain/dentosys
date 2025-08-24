<?php
/*****************************************************************
 * create_existing_patient_accounts.php
 * ---------------------------------------------------------------
 * Create user accounts for all existing patients using their
 * email addresses with "password" as the default password.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

// Require admin access
require_login();
require_admin();

$page_title = 'Create Patient Accounts';
include BASE_PATH . '/templates/header.php';

// Find all patients without user accounts but with email addresses
$stmt = $conn->prepare("
    SELECT p.patient_id, p.first_name, p.last_name, p.email
    FROM Patient p 
    WHERE p.user_id IS NULL 
    AND p.email IS NOT NULL 
    AND p.email != ''
    ORDER BY p.first_name, p.last_name
");
$stmt->execute();
$patients = $stmt->get_result();

$created = 0;
$linked = 0;
$errors = 0;
$processed_patients = [];
?>

<div class="page-container">
    <!-- Page Header -->
    <div class="page-header enhanced-header">
        <div class="header-content">
            <div class="header-icon">
                <div class="icon-circle">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 16C19.3137 16 22 13.3137 22 10C22 6.68629 19.3137 4 16 4C12.6863 4 10 6.68629 10 10C10 13.3137 12.6863 16 16 16Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M6 28C6 22.4772 10.4772 18 16 18C21.5228 18 26 22.4772 26 28H6Z" stroke="currentColor" stroke-width="2"/>
                        <circle cx="24" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M22 8H26M24 6V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            <div class="header-text">
                <h1>Create User Accounts for Existing Patients</h1>
                <p>Bulk creation of patient portal accounts with default credentials</p>
            </div>
        </div>
        <div class="header-stats">
            <div class="stat-card">
                <div class="stat-number"><?= $patients->num_rows ?></div>
                <div class="stat-label">Patients Found</div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <?php if ($patients->num_rows === 0): ?>
            <!-- No Patients Found -->
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="32" cy="32" r="30" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4"/>
                        <path d="M32 20V32L40 40" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="32" cy="32" r="3" fill="currentColor"/>
                    </svg>
                </div>
                <h3>All Set!</h3>
                <p>No patients found that need user accounts. All eligible patients already have portal access.</p>
                <div class="empty-actions">
                    <a href="../settings/patients.php" class="btn-primary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 3L3 7L7 11M3 7H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Back to Patient Management
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Processing Patients -->
            <div class="processing-section">
                <div class="section-header">
                    <h2>Processing Patient Accounts</h2>
                    <p>Creating portal access for patients with email addresses</p>
                </div>

                <div class="results-table-wrapper">
                    <table class="enhanced-table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($patient = $patients->fetch_assoc()): 
                                $processed_patients[] = $patient;
                                $status = '';
                                $details = '';
                                $status_class = '';
                                
                                try {
                                    // Check if user already exists with this email
                                    $checkStmt = $conn->prepare("SELECT user_id, role_id FROM UserTbl WHERE email = ?");
                                    $checkStmt->bind_param("s", $patient['email']);
                                    $checkStmt->execute();
                                    $existingUser = $checkStmt->get_result();
                                    
                                    if ($existingUser->num_rows > 0) {
                                        // User exists - link them if they're a patient
                                        $user = $existingUser->fetch_assoc();
                                        
                                        if ($user['role_id'] == 4) { // Patient role
                                            $linkStmt = $conn->prepare("UPDATE Patient SET user_id = ? WHERE patient_id = ?");
                                            $linkStmt->bind_param("ii", $user['user_id'], $patient['patient_id']);
                                            
                                            if ($linkStmt->execute()) {
                                                $status = 'LINKED';
                                                $details = 'Linked to existing patient account';
                                                $status_class = 'status-linked';
                                                $linked++;
                                            } else {
                                                $status = 'ERROR';
                                                $details = 'Failed to link to existing account';
                                                $status_class = 'status-error';
                                                $errors++;
                                            }
                                        } else {
                                            $status = 'SKIPPED';
                                            $details = 'Email belongs to staff account (role: ' . $user['role_id'] . ')';
                                            $status_class = 'status-warning';
                                            $errors++;
                                        }
                                    } else {
                                        // Create new user account with "password" as default
                                        $defaultPassword = "password";
                                        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
                                        
                                        // Create user account (role_id 4 = Patient)
                                        $createStmt = $conn->prepare("
                                            INSERT INTO UserTbl (email, password_hash, role_id, is_active, created_at) 
                                            VALUES (?, ?, 4, 1, NOW())
                                        ");
                                        $createStmt->bind_param("ss", $patient['email'], $hashedPassword);
                                        
                                        if ($createStmt->execute()) {
                                            $userId = $conn->insert_id;
                                            
                                            // Link patient to user account
                                            $linkStmt = $conn->prepare("UPDATE Patient SET user_id = ? WHERE patient_id = ?");
                                            $linkStmt->bind_param("ii", $userId, $patient['patient_id']);
                                            
                                            if ($linkStmt->execute()) {
                                                $status = 'CREATED';
                                                $details = 'New account created with password: <span class="password-highlight">password</span>';
                                                $status_class = 'status-success';
                                                $created++;
                                                
                                                // Log the account creation
                                                log_audit_action(
                                                    "Bulk patient account creation", 
                                                    'CREATE', 
                                                    'UserTbl', 
                                                    $userId,
                                                    "Created account for existing patient: {$patient['first_name']} {$patient['last_name']} ({$patient['email']}) with default password",
                                                    'LOW'
                                                );
                                            } else {
                                                // Rollback - delete the user account we just created
                                                $deleteStmt = $conn->prepare("DELETE FROM UserTbl WHERE user_id = ?");
                                                $deleteStmt->bind_param("i", $userId);
                                                $deleteStmt->execute();
                                                
                                                $status = 'ERROR';
                                                $details = 'Failed to link patient to new account';
                                                $status_class = 'status-error';
                                                $errors++;
                                            }
                                        } else {
                                            $status = 'ERROR';
                                            $details = 'Failed to create user account: ' . htmlspecialchars($conn->error);
                                            $status_class = 'status-error';
                                            $errors++;
                                        }
                                    }
                                } catch (Exception $e) {
                                    $status = 'ERROR';
                                    $details = 'Exception: ' . htmlspecialchars($e->getMessage());
                                    $status_class = 'status-error';
                                    $errors++;
                                }
                            ?>
                            <tr class="result-row">
                                <td class="patient-cell">
                                    <div class="patient-info">
                                        <div class="patient-avatar">
                                            <?= strtoupper(substr($patient['first_name'], 0, 1)) . strtoupper(substr($patient['last_name'], 0, 1)) ?>
                                        </div>
                                        <div class="patient-details">
                                            <div class="patient-name"><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></div>
                                            <div class="patient-id">ID: <?= $patient['patient_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="email-cell">
                                    <div class="email-wrapper">
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 3L7 7L12 3V2.5C12 2.22386 11.7761 2 11.5 2H2.5C2.22386 2 2 2.22386 2 2.5V3Z" fill="currentColor"/>
                                            <path d="M2 3V11.5C2 11.7761 2.22386 12 2.5 12H11.5C11.7761 12 12 11.7761 12 11.5V3L7 7L2 3Z" fill="currentColor"/>
                                        </svg>
                                        <?= htmlspecialchars($patient['email']) ?>
                                    </div>
                                </td>
                                <td class="status-cell">
                                    <span class="status-badge <?= $status_class ?>">
                                        <?php
                                        $icon = '';
                                        switch($status) {
                                            case 'CREATED':
                                                $icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M10 3L4.5 8.5L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                                break;
                                            case 'LINKED':
                                                $icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M7 5L9 3C9.55228 2.44772 9.55228 1.55228 9 1C8.44772 0.447715 7.55228 0.447715 7 1L5 3M5 7L3 9C2.44772 9.55228 2.44772 10.4477 3 11C3.55228 11.5523 4.44772 11.5523 5 11L7 9M5 5L7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
                                                break;
                                            case 'SKIPPED':
                                                $icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 1V11M1 6H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
                                                break;
                                            case 'ERROR':
                                                $icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M9 3L3 9M3 3L9 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
                                                break;
                                        }
                                        echo $icon . ' ' . $status;
                                        ?>
                                    </span>
                                </td>
                                <td class="details-cell">
                                    <div class="details-text"><?= $details ?></div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($patients->num_rows > 0): ?>
        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-header">
                <div class="summary-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 11H15M9 15H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L19.7071 9.70711C19.8946 9.89464 20 10.149 20 10.4142V19C20 20.1046 19.1046 21 18 21H17ZM17 21V11H13V7H7V19H17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3>Processing Summary</h3>
            </div>
            
            <div class="summary-grid">
                <div class="summary-card success">
                    <div class="summary-card-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16.25 6.25L8.125 14.375L3.75 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-number"><?= $created ?></div>
                        <div class="summary-card-label">New Accounts Created</div>
                    </div>
                </div>
                
                <div class="summary-card linked">
                    <div class="summary-card-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.25 8.75L13.75 6.25C14.6785 5.32153 14.6785 3.80312 13.75 2.87465C12.8215 1.94618 11.3031 1.94618 10.3746 2.87465L7.87461 5.37465M8.75 11.25L6.25 13.75C5.32153 14.6785 5.32153 16.1969 6.25 17.1254C7.17847 18.0538 8.69688 18.0538 9.62535 17.1254L12.1254 14.6254M8.75 8.75L11.25 11.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-number"><?= $linked ?></div>
                        <div class="summary-card-label">Existing Accounts Linked</div>
                    </div>
                </div>
                
                <div class="summary-card error">
                    <div class="summary-card-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-number"><?= $errors ?></div>
                        <div class="summary-card-label">Errors/Skipped</div>
                    </div>
                </div>
                
                <div class="summary-card total">
                    <div class="summary-card-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 18.125C14.6447 18.125 18.125 14.6447 18.125 10C18.125 5.35532 14.6447 1.875 10 1.875C5.35532 1.875 1.875 5.35532 1.875 10C1.875 14.6447 5.35532 18.125 10 18.125Z" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M10 6.25V10L12.5 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="summary-card-content">
                        <div class="summary-card-number"><?= $patients->num_rows ?></div>
                        <div class="summary-card-label">Total Processed</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($created > 0): ?>
            <!-- Login Information -->
            <div class="login-info-section">
                <div class="info-header">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 1L15.09 8.26L22 9L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9L8.91 8.26L12 1Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Login Information</h3>
                </div>
                
                <div class="login-details">
                    <p>All new patient accounts have been created with the following default credentials:</p>
                    
                    <div class="credential-grid">
                        <div class="credential-item">
                            <div class="credential-label">Default Password</div>
                            <div class="credential-value">
                                <code>password</code>
                                <button class="copy-btn" onclick="copyToClipboard('password')">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="2" y="2" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M6 2V1C6 0.447715 6.44772 0 7 0H12C12.5523 0 13 0.447715 13 1V6C13 6.55228 12.5523 7 12 7H11" stroke="currentColor" stroke-width="1.5"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="credential-item">
                            <div class="credential-label">Login Email</div>
                            <div class="credential-value">Their registered email address</div>
                        </div>
                        
                        <div class="credential-item">
                            <div class="credential-label">Portal URL</div>
                            <div class="credential-value">
                                <a href="../../auth/patient_portal.php" target="_blank">
                                    /dentosys/auth/patient_portal.php
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.5 1.5H10.5V3.5M10.5 1.5L6 6M4.5 2.5H2.5C1.94772 2.5 1.5 2.94772 1.5 3.5V9.5C1.5 10.0523 1.94772 10.5 2.5 10.5H8.5C9.05228 10.5 9.5 10.0523 9.5 9.5V7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="security-notice">
                        <div class="notice-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 1L10.5 2.5L13.5 2L14 5L16 7L14 9L13.5 12L10.5 11.5L8 13L5.5 11.5L2.5 12L2 9L0 7L2 5L2.5 2L5.5 2.5L8 1Z" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M6 8L7.5 9.5L10 6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <p><strong>Security Reminder:</strong> Patients should change their passwords after their first login for enhanced security.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Navigation Actions -->
    <div class="action-section">
        <div class="action-buttons">
            <a href="../settings/patients.php" class="btn-secondary">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 3L3 7L7 11M3 7H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to Patient Management
            </a>
            
            <a href="link_existing_patients.php" class="btn-primary">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 7L11 5C11.5523 4.44772 11.5523 3.55228 11 3C10.4477 2.44772 9.55228 2.44772 9 3L7 5M7 9L5 11C4.44772 11.5523 4.44772 12.4477 5 13C5.55228 13.5523 6.44772 13.5523 7 13L9 11M7 7L9 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Link Utility
            </a>
        </div>
    </div>
</div>

<?php
// Log this operation
if (isset($processed_patients) && count($processed_patients) > 0) {
    log_system_event(
        "Bulk patient account creation completed", 
        "Created: $created, Linked: $linked, Errors: $errors, Total processed: " . count($processed_patients)
    );
}
?>

<style>
/* Page Layout */
.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Enhanced Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    color: white;
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    pointer-events: none;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    z-index: 1;
}

.header-icon {
    flex-shrink: 0;
}

.icon-circle {
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.header-text h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
    font-weight: 700;
    color: white;
}

.header-text p {
    margin: 0;
    font-size: 16px;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 400;
}

.header-stats {
    margin-left: auto;
    position: relative;
    z-index: 1;
}

.stat-card {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    padding: 16px 20px;
    text-align: center;
    backdrop-filter: blur(10px);
    min-width: 120px;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: white;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 4px;
}

/* Content Wrapper */
.content-wrapper {
    margin-bottom: 32px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
    border: 2px dashed #e5e7eb;
}

.empty-icon {
    color: #9ca3af;
    margin-bottom: 24px;
}

.empty-state h3 {
    font-size: 24px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 12px 0;
}

.empty-state p {
    font-size: 16px;
    color: #6b7280;
    margin: 0 0 32px 0;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.empty-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
}

/* Processing Section */
.processing-section {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #f3f4f6;
}

.section-header {
    padding: 24px 28px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafbfc;
}

.section-header h2 {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 600;
    color: #374151;
}

.section-header p {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
}

/* Enhanced Table */
.results-table-wrapper {
    overflow-x: auto;
}

.enhanced-table {
    width: 100%;
    border-collapse: collapse;
}

.enhanced-table thead th {
    background: #f9fafb;
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.enhanced-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s ease;
}

.enhanced-table tbody tr:hover {
    background: #fafbfc;
}

.enhanced-table td {
    padding: 16px 20px;
    vertical-align: middle;
}

/* Patient Cell */
.patient-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.patient-avatar {
    width: 40px;
    height: 40px;
    background: #667eea;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.patient-name {
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.patient-id {
    font-size: 12px;
    color: #9ca3af;
}

/* Email Cell */
.email-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-size: 14px;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.status-linked {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.status-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.status-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* Details Cell */
.details-text {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.4;
}

.password-highlight {
    background: #f3f4f6;
    color: #374151;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Consolas', 'Monaco', monospace;
    font-weight: 600;
    font-size: 13px;
}

/* Summary Section */
.summary-section {
    background: white;
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #f3f4f6;
}

.summary-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}

.summary-icon {
    color: #667eea;
}

.summary-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #374151;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.summary-card {
    background: #fafbfc;
    border: 2px solid #f3f4f6;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 12px -1px rgba(0, 0, 0, 0.1);
}

.summary-card.success {
    border-color: #10b981;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}

.summary-card.linked {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);
}

.summary-card.error {
    border-color: #ef4444;
    background: linear-gradient(135deg, #fee2e2 0%, #fca5a5 100%);
}

.summary-card.total {
    border-color: #8b5cf6;
    background: linear-gradient(135deg, #ede9fe 0%, #c4b5fd 100%);
}

.summary-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.summary-card.success .summary-card-icon {
    background: #10b981;
}

.summary-card.linked .summary-card-icon {
    background: #3b82f6;
}

.summary-card.error .summary-card-icon {
    background: #ef4444;
}

.summary-card.total .summary-card-icon {
    background: #8b5cf6;
}

.summary-card-number {
    font-size: 24px;
    font-weight: 700;
    color: #374151;
    line-height: 1;
}

.summary-card-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
    margin-top: 4px;
}

/* Login Info Section */
.login-info-section {
    background: white;
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #f3f4f6;
}

.info-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}

.info-icon {
    color: #f59e0b;
}

.info-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #374151;
}

.login-details p {
    margin: 0 0 24px 0;
    font-size: 16px;
    color: #6b7280;
    line-height: 1.6;
}

.credential-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.credential-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
}

.credential-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.credential-value {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #6b7280;
}

.credential-value code {
    background: #f3f4f6;
    color: #374151;
    padding: 4px 8px;
    border-radius: 6px;
    font-family: 'Consolas', 'Monaco', monospace;
    font-weight: 600;
    font-size: 13px;
}

.credential-value a {
    color: #3b82f6;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}

.credential-value a:hover {
    text-decoration: underline;
}

.copy-btn {
    background: #e5e7eb;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 4px 6px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
}

.copy-btn:hover {
    background: #d1d5db;
    color: #374151;
}

.security-notice {
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.notice-icon {
    color: #d97706;
    margin-top: 2px;
    flex-shrink: 0;
}

.security-notice p {
    margin: 0;
    font-size: 14px;
    color: #92400e;
    line-height: 1.4;
}

/* Action Section */
.action-section {
    text-align: center;
    padding: 20px 0;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}

/* Buttons */
.btn-primary, .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.2);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 12px -1px rgba(102, 126, 234, 0.3);
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: #f9fafb;
    border-color: #e5e7eb;
    color: #6b7280;
}

.btn-secondary:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #374151;
    transform: translateY(-1px);
    text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-container {
        padding: 16px;
    }
    
    .page-header {
        padding: 24px 20px;
        margin-bottom: 24px;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    
    .header-stats {
        margin-left: 0;
    }
    
    .header-text h1 {
        font-size: 24px;
    }
    
    .summary-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
    }
    
    .credential-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .enhanced-table {
        font-size: 13px;
    }
    
    .enhanced-table td {
        padding: 12px 16px;
    }
    
    .patient-info {
        gap: 8px;
    }
    
    .patient-avatar {
        width: 32px;
        height: 32px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .results-table-wrapper {
        overflow-x: scroll;
    }
    
    .enhanced-table {
        min-width: 600px;
    }
    
    .summary-section,
    .login-info-section {
        padding: 20px 16px;
    }
    
    .section-header {
        padding: 20px 20px;
    }
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Visual feedback
        const button = event.target.closest('.copy-btn');
        const originalBg = button.style.background;
        button.style.background = '#10b981';
        button.style.color = 'white';
        
        setTimeout(() => {
            button.style.background = originalBg;
            button.style.color = '#6b7280';
        }, 1000);
    });
}
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>
