<?php
/*****************************************************************
 * pages/settings/patients.php
 * ---------------------------------------------------------------
 * Admin panel for managing patient accounts and portal access.
 *  • List patient accounts
 *  • Create patient portal accounts
 *  • Enable/disable patient portal access
 *  • Reset patient passwords
 *  • Link existing patients to accounts
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_admin();

/* --------------------------------------------------------------
 * Handle actions  
 * ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---- Create patient account ---- */
    if ($_POST['action'] === 'create_account') {
        $patient_id = intval($_POST['patient_id']);
        $email = trim($_POST['email']);
        $password = $_POST['password'] ?: 'password'; // Default password
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Valid email address is required.', 'error');
            redirect('patients.php');
        }

        // Check if email already exists
        $existing = $conn->prepare("SELECT user_id FROM UserTbl WHERE email = ?");
        $existing->bind_param("s", $email);
        $existing->execute();
        if ($existing->get_result()->num_rows > 0) {
            flash('Email address is already registered.', 'error');
            redirect('patients.php');
        }

        // Create user account
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $createStmt = $conn->prepare("INSERT INTO UserTbl (email, password_hash, role_id, is_active, created_at) VALUES (?, ?, 4, 1, NOW())");
        $createStmt->bind_param("ss", $email, $hashedPassword);
        
        if ($createStmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Link patient to user account
            $linkStmt = $conn->prepare("UPDATE Patient SET user_id = ? WHERE patient_id = ?");
            $linkStmt->bind_param("ii", $user_id, $patient_id);
            
            if ($linkStmt->execute()) {
                flash('Patient portal account created successfully.', 'success');
                log_audit_action("Patient account creation", 'CREATE', 'UserTbl', $user_id, "Admin created portal account for patient ID: $patient_id", 'LOW');
            } else {
                // Rollback user creation
                $conn->query("DELETE FROM UserTbl WHERE user_id = $user_id");
                flash('Failed to link patient account.', 'error');
            }
        } else {
            flash('Failed to create user account.', 'error');
        }
        redirect('patients.php');
    }

    /* ---- Reset password ---- */
    if ($_POST['action'] === 'reset_password') {
        $user_id = intval($_POST['user_id']);
        $new_password = $_POST['new_password'] ?: 'password';
        
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE UserTbl SET password_hash = ? WHERE user_id = ? AND role_id = 4");
        $stmt->bind_param("si", $hashedPassword, $user_id);
        
        if ($stmt->execute()) {
            flash('Password reset successfully.', 'success');
            log_audit_action("Password reset", 'UPDATE', 'UserTbl', $user_id, "Admin reset patient portal password", 'MEDIUM');
        } else {
            flash('Failed to reset password.', 'error');
        }
        redirect('patients.php');
    }

    /* ---- Toggle account status ---- */
    if ($_POST['action'] === 'toggle_status') {
        $user_id = intval($_POST['user_id']);
        $is_active = intval($_POST['is_active']);
        
        $stmt = $conn->prepare("UPDATE UserTbl SET is_active = ? WHERE user_id = ? AND role_id = 4");
        $stmt->bind_param("ii", $is_active, $user_id);
        
        if ($stmt->execute()) {
            $status = $is_active ? 'activated' : 'deactivated';
            flash("Patient portal account $status successfully.", 'success');
            log_audit_action("Account status change", 'UPDATE', 'UserTbl', $user_id, "Admin $status patient portal account", 'MEDIUM');
        } else {
            flash('Failed to update account status.', 'error');
        }
        redirect('patients.php');
    }

    /* ---- Delete account ---- */
    if ($_POST['action'] === 'delete_account') {
        $user_id = intval($_POST['user_id']);
        $patient_id = intval($_POST['patient_id']);
        
        // Unlink patient first
        $unlinkStmt = $conn->prepare("UPDATE Patient SET user_id = NULL WHERE patient_id = ?");
        $unlinkStmt->bind_param("i", $patient_id);
        $unlinkStmt->execute();
        
        // Delete user account
        $deleteStmt = $conn->prepare("DELETE FROM UserTbl WHERE user_id = ? AND role_id = 4");
        $deleteStmt->bind_param("i", $user_id);
        
        if ($deleteStmt->execute()) {
            flash('Patient portal account deleted successfully.', 'success');
            log_audit_action("Account deletion", 'DELETE', 'UserTbl', $user_id, "Admin deleted patient portal account for patient ID: $patient_id", 'HIGH');
        } else {
            flash('Failed to delete account.', 'error');
        }
        redirect('patients.php');
    }
}

/* --------------------------------------------------------------
 * Fetch patient data
 * ------------------------------------------------------------ */

// Get all patients with their user account status
$patients_query = "
    SELECT 
        p.patient_id,
        p.first_name,
        p.last_name,
        p.email,
        p.phone,
        p.user_id,
        u.email AS user_email,
        u.is_active,
        u.created_at AS account_created
    FROM Patient p
    LEFT JOIN UserTbl u ON p.user_id = u.user_id AND u.role_id = 4
    ORDER BY p.first_name, p.last_name
";
$patients_result = $conn->query($patients_query);

// Get statistics
$total_patients = $conn->query("SELECT COUNT(*) as c FROM Patient")->fetch_assoc()['c'] ?? 0;
$patients_with_accounts = $conn->query("SELECT COUNT(*) as c FROM Patient WHERE user_id IS NOT NULL")->fetch_assoc()['c'] ?? 0;
$active_accounts = $conn->query("SELECT COUNT(*) as c FROM Patient p JOIN UserTbl u ON p.user_id = u.user_id WHERE u.role_id = 4 AND u.is_active = 1")->fetch_assoc()['c'] ?? 0;
$patients_without_accounts = $total_patients - $patients_with_accounts;

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<main class="main-content">
    <!-- Header Section -->
    <div class="content-header">
        <h1>👥 Patient Account Management</h1>
        <div class="breadcrumb">
            <a href="index.php">Settings</a> > Patient Management
        </div>
    </div>

    <div class="content-body">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-4 gap-6 mb-6">
            <div class="card-enhanced">
                <div class="card-body text-center p-6">
                    <div class="text-3xl font-bold text-blue-600 mb-2"><?= $total_patients; ?></div>
                    <div class="text-gray-600 text-sm">Total Patients</div>
                </div>
            </div>
            <div class="card-enhanced">
                <div class="card-body text-center p-6">
                    <div class="text-3xl font-bold text-green-600 mb-2"><?= $patients_with_accounts; ?></div>
                    <div class="text-gray-600 text-sm">With Portal Access</div>
                </div>
            </div>
            <div class="card-enhanced">
                <div class="card-body text-center p-6">
                    <div class="text-3xl font-bold text-orange-600 mb-2"><?= $active_accounts; ?></div>
                    <div class="text-gray-600 text-sm">Active Accounts</div>
                </div>
            </div>
            <div class="card-enhanced">
                <div class="card-body text-center p-6">
                    <div class="text-3xl font-bold text-red-600 mb-2"><?= $patients_without_accounts; ?></div>
                    <div class="text-gray-600 text-sm">No Portal Access</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card-enhanced mb-6">
            <div class="card-header">
                <h3>🚀 Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="flex gap-4">
                    <a href="../patients/create_existing_patient_accounts.php" class="btn-primary">
                        📝 Bulk Create Accounts
                    </a>
                    <a href="../patients/link_existing_patients.php" class="btn-secondary">
                        🔗 Link Existing Patients
                    </a>
                    <a href="../patients/list.php" class="btn-secondary">
                        📋 Manage Patient Records
                    </a>
                </div>
            </div>
        </div>

        <!-- Patient List -->
        <div class="card-enhanced">
            <div class="card-header">
                <h3>👤 Patient Portal Accounts</h3>
                <p class="text-gray-600 text-sm">Manage patient portal access and account settings</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-enhanced">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Contact</th>
                                <th>Portal Status</th>
                                <th>Account Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($patient = $patients_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></div>
                                    <div class="text-sm text-gray-500">ID: <?= $patient['patient_id']; ?></div>
                                </td>
                                <td>
                                    <div class="text-sm">
                                        <?php if ($patient['email']): ?>
                                            <div>📧 <?= htmlspecialchars($patient['email']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($patient['phone']): ?>
                                            <div>📞 <?= htmlspecialchars($patient['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($patient['user_id']): ?>
                                        <?php if ($patient['is_active']): ?>
                                            <span class="badge badge-success">✅ Active Portal Access</span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Login: <?= htmlspecialchars($patient['user_email']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge badge-warning">⚠️ Account Disabled</span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Login: <?= htmlspecialchars($patient['user_email']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">❌ No Portal Access</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($patient['account_created']): ?>
                                        <div class="text-sm"><?= date('M j, Y', strtotime($patient['account_created'])); ?></div>
                                        <div class="text-xs text-gray-500"><?= date('g:i A', strtotime($patient['account_created'])); ?></div>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <?php if (!$patient['user_id']): ?>
                                            <!-- Create Account Button -->
                                            <button class="btn-sm btn-primary" onclick="showCreateAccountModal(<?= $patient['patient_id']; ?>, '<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>', '<?= htmlspecialchars($patient['email']); ?>')">
                                                📝 Create Account
                                            </button>
                                        <?php else: ?>
                                            <!-- Reset Password -->
                                            <button class="btn-sm btn-secondary" onclick="showResetPasswordModal(<?= $patient['user_id']; ?>, '<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>')">
                                                🔑 Reset Password
                                            </button>
                                            
                                            <!-- Toggle Status -->
                                            <?php if ($patient['is_active']): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Disable this patient\'s portal access?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?= $patient['user_id']; ?>">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="btn-sm btn-warning">⏸️ Disable</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?= $patient['user_id']; ?>">
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit" class="btn-sm btn-success">▶️ Enable</button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <!-- Delete Account -->
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this patient\'s portal account? This action cannot be undone.')">
                                                <input type="hidden" name="action" value="delete_account">
                                                <input type="hidden" name="user_id" value="<?= $patient['user_id']; ?>">
                                                <input type="hidden" name="patient_id" value="<?= $patient['patient_id']; ?>">
                                                <button type="submit" class="btn-sm btn-danger">🗑️ Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Create Account Modal -->
<div id="createAccountModal" class="modal">
    <div class="modal-content enhanced-modal">
        <div class="modal-header enhanced-header">
            <div class="modal-icon">
                <div class="icon-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2"/>
                        <circle cx="17" cy="17" r="3" stroke="currentColor" stroke-width="2"/>
                        <path d="M16 17H18M17 16V18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            <div class="modal-title-section">
                <h3>Create Patient Account</h3>
                <p class="modal-subtitle">Set up portal access for this patient</p>
            </div>
            <button type="button" class="close-btn" onclick="closeModal('createAccountModal')">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <form method="POST">
            <div class="modal-body enhanced-body">
                <input type="hidden" name="action" value="create_account">
                <input type="hidden" name="patient_id" id="create_patient_id">
                
                <div class="form-group enhanced-form-group">
                    <label class="enhanced-label">
                        <span class="label-text">Patient Name</span>
                        <span class="label-badge">Read Only</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="create_patient_name" readonly class="enhanced-input readonly-input">
                        <div class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 8C9.65685 8 11 6.65685 11 5C11 3.34315 9.65685 2 8 2C6.34315 2 5 3.34315 5 5C5 6.65685 6.34315 8 8 8Z" fill="currentColor"/>
                                <path d="M3 14C3 11.7909 4.79086 10 7 10H9C11.2091 10 13 11.7909 13 14V14H3V14Z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="form-group enhanced-form-group">
                    <label for="create_email" class="enhanced-label">
                        <span class="label-text">Email Address</span>
                        <span class="label-required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="create_email" class="enhanced-input" required autocomplete="email">
                        <div class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 4L8 8L14 4V3C14 2.44772 13.5523 2 13 2H3C2.44772 2 2 2.44772 2 3V4Z" fill="currentColor"/>
                                <path d="M2 4V13C2 13.5523 2.44772 14 3 14H13C13.5523 14 14 13.5523 14 13V4L8 8L2 4Z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                    <div class="input-help">
                        <div class="help-icon">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M7 9.5V7M7 4.5H7.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <span>This will be the patient's login email address</span>
                    </div>
                </div>
                
                <div class="form-group enhanced-form-group">
                    <label for="create_password" class="enhanced-label">
                        <span class="label-text">Initial Password</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" name="password" id="create_password" class="enhanced-input" value="password" autocomplete="new-password">
                        <div class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6V4.5C4 2.567 5.567 1 7.5 1S11 2.567 11 4.5V6M3 6H12C12.5523 6 13 6.44772 13 7V13C13 13.5523 12.5523 14 12 14H3C2.44772 14 2 13.5523 2 13V7C2 6.44772 2.44772 6 3 6Z" stroke="currentColor" stroke-width="1.5" fill="none"/>
                            </svg>
                        </div>
                        <button type="button" class="generate-password-btn" onclick="generateCreatePassword()">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13 7C13 10.3137 10.3137 13 7 13C3.68629 13 1 10.3137 1 7C1 3.68629 3.68629 1 7 1C10.3137 1 13 3.68629 13 7Z" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M4.5 7L6.5 9L9.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Generate
                        </button>
                    </div>
                    <div class="input-help">
                        <div class="help-icon">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M7 9.5V7M7 4.5H7.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <span>Leave as 'password' for default, or set a custom initial password</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer enhanced-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('createAccountModal')">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Cancel
                </button>
                <button type="submit" class="btn-primary enhanced-primary">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 3V8M8 8L11 5M8 8L5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3 13H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal">
    <div class="modal-content enhanced-modal">
        <div class="modal-header enhanced-header">
            <div class="modal-icon">
                <div class="icon-circle">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 10V8C6 5.79086 7.79086 4 10 4H14C16.2091 4 18 5.79086 18 8V10M5 10H19C19.5523 10 20 10.4477 20 11V19C20 19.5523 19.5523 20 19 20H5C4.44772 20 4 19.5523 4 19V11C4 10.4477 4.44772 10 5 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="15" r="2" fill="currentColor"/>
                    </svg>
                </div>
            </div>
            <div class="modal-title-section">
                <h3>Reset Patient Password</h3>
                <p class="modal-subtitle">Generate a new password for patient portal access</p>
            </div>
            <button type="button" class="close-btn" onclick="closeModal('resetPasswordModal')">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <form method="POST">
            <div class="modal-body enhanced-body">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                
                <div class="form-group enhanced-form-group">
                    <label class="enhanced-label">
                        <span class="label-text">Patient Name</span>
                        <span class="label-badge">Read Only</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="reset_patient_name" readonly class="enhanced-input readonly-input">
                        <div class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 8C9.65685 8 11 6.65685 11 5C11 3.34315 9.65685 2 8 2C6.34315 2 5 3.34315 5 5C5 6.65685 6.34315 8 8 8Z" fill="currentColor"/>
                                <path d="M3 14C3 11.7909 4.79086 10 7 10H9C11.2091 10 13 11.7909 13 14V14H3V14Z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="form-group enhanced-form-group">
                    <label for="reset_new_password" class="enhanced-label">
                        <span class="label-text">New Password</span>
                        <span class="label-required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" name="new_password" id="reset_new_password" class="enhanced-input" value="password" autocomplete="new-password">
                        <div class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6V4.5C4 2.567 5.567 1 7.5 1S11 2.567 11 4.5V6M3 6H12C12.5523 6 13 6.44772 13 7V13C13 13.5523 12.5523 14 12 14H3C2.44772 14 2 13.5523 2 13V7C2 6.44772 2.44772 6 3 6Z" stroke="currentColor" stroke-width="1.5" fill="none"/>
                            </svg>
                        </div>
                        <button type="button" class="generate-password-btn" onclick="generatePassword()">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13 7C13 10.3137 10.3137 13 7 13C3.68629 13 1 10.3137 1 7C1 3.68629 3.68629 1 7 1C10.3137 1 13 3.68629 13 7Z" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M4.5 7L6.5 9L9.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Generate
                        </button>
                    </div>
                    <div class="input-help">
                        <div class="help-icon">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M7 9.5V7M7 4.5H7.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <span>Patient should change this password after their first login for security</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer enhanced-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('resetPasswordModal')">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Cancel
                </button>
                <button type="submit" class="btn-primary enhanced-primary">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 8L7 11L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Enhanced Table Styles */
.table-enhanced {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.table-enhanced th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-enhanced td {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}

.table-enhanced tr:hover {
    background-color: #f8fafc;
}

/* Badge Styles */
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-success {
    background-color: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background-color: #fef3c7;
    color: #92400e;
}

.badge-secondary {
    background-color: #f3f4f6;
    color: #374151;
}

/* Button Styles */
.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-sm:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.show {
    opacity: 1;
}

.modal-content {
    background-color: white;
    margin: 3% auto;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: translateY(20px);
    transition: transform 0.3s ease;
}

.modal.show .modal-content {
    transform: translateY(0);
}

/* Enhanced Modal Styles */
.enhanced-modal {
    max-width: 520px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
}

.enhanced-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px 28px;
    border-bottom: none;
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
}

.enhanced-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    pointer-events: none;
}

.modal-icon {
    flex-shrink: 0;
    z-index: 1;
}

.icon-circle {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.modal-title-section {
    flex: 1;
    z-index: 1;
}

.modal-title-section h3 {
    margin: 0 0 4px 0;
    font-size: 20px;
    font-weight: 600;
    color: white;
}

.modal-subtitle {
    margin: 0;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 400;
}

.close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: all 0.2s ease;
    flex-shrink: 0;
    z-index: 1;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.enhanced-body {
    padding: 32px 28px;
    background: #fafbfc;
}

.enhanced-form-group {
    margin-bottom: 24px;
}

.enhanced-form-group:last-child {
    margin-bottom: 0;
}

.enhanced-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

.label-text {
    display: flex;
    align-items: center;
    gap: 6px;
}

.label-required {
    color: #dc2626;
    font-weight: 600;
}

.label-badge {
    background: #e5e7eb;
    color: #6b7280;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.enhanced-input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    background: white;
    transition: all 0.2s ease;
    color: #374151;
}

.enhanced-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
}

.readonly-input {
    background: #f9fafb !important;
    color: #6b7280 !important;
    cursor: not-allowed;
}

.readonly-input:focus {
    border-color: #d1d5db !important;
    box-shadow: none !important;
}

.input-icon {
    position: absolute;
    left: 14px;
    color: #9ca3af;
    z-index: 1;
    transition: color 0.2s ease;
}

.enhanced-input:focus + .input-icon,
.input-wrapper:hover .input-icon {
    color: #667eea;
}

.readonly-input + .input-icon {
    color: #d1d5db !important;
}

.generate-password-btn {
    position: absolute;
    right: 8px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
}

.generate-password-btn:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
}

.input-help {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-top: 8px;
    padding: 12px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 8px;
    font-size: 13px;
    color: #1e40af;
    line-height: 1.4;
}

.help-icon {
    color: #3b82f6;
    margin-top: 1px;
    flex-shrink: 0;
}

.enhanced-footer {
    padding: 20px 28px 28px;
    background: white;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn-cancel {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    color: #6b7280;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-cancel:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #374151;
    transform: translateY(-1px);
}

.enhanced-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 2px solid transparent;
    color: white;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.2);
}

.enhanced-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 12px -1px rgba(102, 126, 234, 0.3);
}

.enhanced-primary:active {
    transform: translateY(0);
}

.modal-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #1f2937;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #6b7280;
}

.close:hover {
    color: #374151;
}

.modal-body {
    padding: 20px 24px;
}

.modal-footer {
    padding: 16px 24px 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .enhanced-modal {
        margin: 5% 4%;
        max-width: none;
        width: auto;
    }
    
    .enhanced-header {
        padding: 20px 20px;
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .modal-title-section {
        order: 2;
    }
    
    .close-btn {
        position: absolute;
        top: 16px;
        right: 16px;
        order: 3;
    }
    
    .enhanced-body {
        padding: 24px 20px;
    }
    
    .enhanced-footer {
        padding: 16px 20px 20px;
        flex-direction: column-reverse;
        gap: 8px;
    }
    
    .btn-cancel,
    .enhanced-primary {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .grid-cols-4 {
        grid-template-columns: 1fr;
    }
    
    .flex {
        flex-direction: column;
    }
    
    .btn-sm {
        margin-bottom: 4px;
    }
    
    .enhanced-modal {
        margin: 2% 8px;
    }
    
    .modal-content {
        margin: 2% auto;
    }
    
    .enhanced-header {
        padding: 16px;
    }
    
    .enhanced-body {
        padding: 20px 16px;
    }
    
    .enhanced-footer {
        padding: 12px 16px 16px;
    }
    
    .input-wrapper {
        flex-direction: column;
        align-items: stretch;
    }
    
    .generate-password-btn {
        position: static;
        margin-top: 8px;
        align-self: flex-start;
    }
}
</style>

<script>
function showCreateAccountModal(patientId, patientName, email) {
    document.getElementById('create_patient_id').value = patientId;
    document.getElementById('create_patient_name').value = patientName;
    document.getElementById('create_email').value = email || '';
    const modal = document.getElementById('createAccountModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);
}

function showResetPasswordModal(userId, patientName) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_patient_name').value = patientName;
    const modal = document.getElementById('resetPasswordModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Focus on password field
    setTimeout(() => {
        document.getElementById('reset_new_password').select();
    }, 100);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function generateCreatePassword() {
    // Generate a secure password for create account modal
    const adjectives = ['Swift', 'Bright', 'Clear', 'Fresh', 'Quick', 'Smart', 'Cool', 'Warm', 'Blue', 'Green'];
    const nouns = ['River', 'Mountain', 'Ocean', 'Forest', 'Valley', 'Lake', 'Creek', 'Hill', 'Pine', 'Oak'];
    const numbers = Math.floor(Math.random() * 900) + 100; // 3-digit number
    
    const adjective = adjectives[Math.floor(Math.random() * adjectives.length)];
    const noun = nouns[Math.floor(Math.random() * nouns.length)];
    
    const password = `${adjective}${noun}${numbers}`;
    
    const passwordField = document.getElementById('create_password');
    passwordField.value = password;
    passwordField.select();
    
    // Visual feedback
    const button = event.target.closest('.generate-password-btn');
    const originalContent = button.innerHTML;
    button.innerHTML = `
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4.5 7L6.5 9L9.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Generated!
    `;
    button.style.background = '#10b981';
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.style.background = '#667eea';
    }, 2000);
}

function generatePassword() {
    // Generate a secure password
    const adjectives = ['Swift', 'Bright', 'Clear', 'Fresh', 'Quick', 'Smart', 'Cool', 'Warm', 'Blue', 'Green'];
    const nouns = ['River', 'Mountain', 'Ocean', 'Forest', 'Valley', 'Lake', 'Creek', 'Hill', 'Pine', 'Oak'];
    const numbers = Math.floor(Math.random() * 900) + 100; // 3-digit number
    
    const adjective = adjectives[Math.floor(Math.random() * adjectives.length)];
    const noun = nouns[Math.floor(Math.random() * nouns.length)];
    
    const password = `${adjective}${noun}${numbers}`;
    
    const passwordField = document.getElementById('reset_new_password');
    passwordField.value = password;
    passwordField.select();
    
    // Visual feedback
    const button = event.target.closest('.generate-password-btn');
    const originalContent = button.innerHTML;
    button.innerHTML = `
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4.5 7L6.5 9L9.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Generated!
    `;
    button.style.background = '#10b981';
    
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.style.background = '#667eea';
    }, 2000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
}

// Enhanced keyboard navigation
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// Add smooth transitions on page load
document.addEventListener('DOMContentLoaded', function() {
    // Preload modal transitions
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.style.transition = 'opacity 0.3s ease';
    });
});
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>
