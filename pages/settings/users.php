<?php
/*****************************************************************
 * pages/settings/users.php
 * ---------------------------------------------------------------
 * Admin panel for managing staff / user accounts.
 *  • List users
 *  • Add user
 *  • Change role or activate / deactivate
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Admin-only gate ───────── */
if (!is_admin()) {
    flash('User management is restricted to administrators.');
    redirect('/dentosys/index.php');
}

/* --------------------------------------------------------------
 * 1. Fetch roles once for dropdowns
 * ------------------------------------------------------------ */
$rolesDDL = $conn->query("SELECT role_id, role_name FROM Role ORDER BY role_name");
$roleMap  = [];
while ($r = $rolesDDL->fetch_assoc()) {
    $roleMap[$r['role_id']] = $r['role_name'];
}

/* --------------------------------------------------------------
 * 2. Handle actions  
 * ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---- Add new user ---- */
    if ($_POST['action'] === 'add') {
        $email     = $conn->real_escape_string(trim($_POST['email']));
        $pass      = $_POST['password'];
        $role_id   = intval($_POST['role_id']);
        $firstName = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
        $lastName  = $conn->real_escape_string(trim($_POST['last_name'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
            flash('Valid email and password are required.');
            redirect('users.php');
        }

        // Check if email already exists
        $existingUser = $conn->query("SELECT user_id FROM usertbl WHERE email = '$email'")->num_rows;
        if ($existingUser > 0) {
            flash('Email address is already registered.');
            redirect('users.php');
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
          "INSERT INTO UserTbl (email, password_hash, role_id, first_name, last_name)
           VALUES (?,?,?,?,?)"
        );
        $stmt->bind_param('ssiss', $email, $hash, $role_id, $firstName, $lastName);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Create corresponding profile based on role
            if ($role_id == 2) { // Dentist
                $specialty = $conn->real_escape_string($_POST['specialty'] ?? 'General Dentistry');
                $stmt2 = $conn->prepare("INSERT INTO dentist (user_id, specialty) VALUES (?, ?)");
                $stmt2->bind_param('is', $user_id, $specialty);
                $stmt2->execute();
            }
            // Note: Receptionist profiles can be managed separately if needed
            
            flash('User created successfully!');
        } else {
            flash('Database error: ' . $conn->error);
        }
        redirect('users.php');
    }

    /* ---- Update role or active flag ---- */
    if ($_POST['action'] === 'edit') {
        $uid     = intval($_POST['user_id']);
        $role_id = intval($_POST['role_id']);
        $active  = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $conn->prepare(
          "UPDATE UserTbl SET role_id = ?, is_active = ? WHERE user_id = ? LIMIT 1"
        );
        $stmt->bind_param('iii', $role_id, $active, $uid);
        $stmt->execute();
        flash('User updated.');
        redirect('users.php');
    }

    /* ---- Reset password ---- */
    if ($_POST['action'] === 'reset_pw') {
        $uid     = intval($_POST['user_id']);
        $newPass = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $conn->query(
          "UPDATE UserTbl SET password_hash = '$newPass' WHERE user_id = $uid LIMIT 1"
        );
        flash('Password reset.');
        redirect('users.php');
    }
}

/* --------------------------------------------------------------
 * 3. Fetch users list with enhanced data
 * ------------------------------------------------------------ */
$users = $conn->query(
  "SELECT u.*, r.role_name,
          COALESCE(u.first_name, '') as first_name,
          COALESCE(u.last_name, '') as last_name,
          COALESCE(u.is_active, 1) as is_active
   FROM usertbl u
   JOIN role r ON r.role_id = u.role_id
   WHERE r.role_name != 'Patient'
   ORDER BY r.role_id, u.first_name, u.last_name"
);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<main class="main-content-enhanced">
    <!-- Header Section -->
    <div class="content-header">
        <h1>👥 User Management</h1>
        <div class="breadcrumb">
            Manage staff accounts and roles
        </div>
    </div>

    <div class="content-body">
        <?= get_flash(); ?>

        <!-- Add User Section -->
        <div class="card-enhanced" style="margin-bottom: 30px;">
            <div class="card-header">
                <h3>➕ Create New Staff Account</h3>
            </div>
            <div class="card-body">
                <form method="post" id="addUserForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="grid grid-cols-2 gap-4" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label-enhanced">First Name *</label>
                            <input type="text" name="first_name" class="form-input-enhanced" 
                                   placeholder="Enter first name" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label-enhanced">Last Name *</label>
                            <input type="text" name="last_name" class="form-input-enhanced" 
                                   placeholder="Enter last name" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label-enhanced">Email Address *</label>
                            <input type="email" name="email" class="form-input-enhanced" 
                                   placeholder="Enter email address" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label-enhanced">Initial Password *</label>
                            <input type="password" name="password" class="form-input-enhanced" 
                                   placeholder="Create initial password" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label-enhanced">Role *</label>
                        <select name="role_id" class="form-select-enhanced" id="roleSelect" required>
                            <option value="">Select a role...</option>
                            <?php foreach ($roleMap as $rid => $rname): ?>
                                <?php if ($rname !== 'Patient'): // Hide Patient role from admin creation ?>
                                    <option value="<?= $rid; ?>"><?= htmlspecialchars($rname); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dentist-specific fields -->
                    <div id="dentistFields" class="role-specific-fields" style="display: none;">
                        <h4>🦷 Dentist Information</h4>
                        <div class="form-group">
                            <label class="form-label-enhanced">Specialty</label>
                            <select name="specialty" class="form-select-enhanced">
                                <option value="General Dentistry">General Dentistry</option>
                                <option value="Orthodontics">Orthodontics</option>
                                <option value="Endodontics">Endodontics</option>
                                <option value="Periodontics">Periodontics</option>
                                <option value="Oral Surgery">Oral Surgery</option>
                                <option value="Pediatric Dentistry">Pediatric Dentistry</option>
                                <option value="Prosthodontics">Prosthodontics</option>
                            </select>
                        </div>
                    </div>

                    <!-- Receptionist-specific fields -->
                    <div id="receptionistFields" class="role-specific-fields" style="display: none;">
                        <h4>📋 Receptionist Information</h4>
                        <p style="color: #64748b; font-size: 14px; margin: 0;">
                            Additional details can be managed after account creation.
                        </p>
                    </div>

                    <button type="submit" class="btn-primary-enhanced">
                        👤 Create User Account
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Users Section -->
        <div class="card-enhanced">
            <div class="card-header">
                <h3>📋 Staff Accounts</h3>
            </div>
            <div class="card-body">
                <?php if ($users->num_rows === 0): ?>
                    <div style="text-align: center; padding: 40px 20px; color: #64748B;">
                        <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
                        <p>No staff accounts found</p>
                        <p style="font-size: 14px;">Create your first staff account using the form above.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4">
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <div class="user-card" style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; background: #f8fafc;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                    <div style="display: flex; align-items: center;">
                                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, 
                                            <?= $user['role_name'] === 'Admin' ? '#dc2626, #b91c1c' : 
                                                ($user['role_name'] === 'Dentist' ? '#059669, #047857' : '#3b82f6, #2563eb'); ?>); 
                                            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
                                            color: white; font-weight: 600; margin-right: 16px; font-size: 18px;">
                                            <?php if (!empty($user['first_name']) && !empty($user['last_name'])): ?>
                                                <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            <?php else: ?>
                                                <?= strtoupper(substr($user['email'], 0, 2)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h4 style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b;">
                                                <?php if (!empty($user['first_name']) && !empty($user['last_name'])): ?>
                                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($user['email']); ?>
                                                <?php endif; ?>
                                            </h4>
                                            <div style="font-size: 14px; color: #64748b; margin-top: 2px;">
                                                <?= htmlspecialchars($user['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="badge-enhanced badge-<?= 
                                            $user['role_name'] === 'Admin' ? 'error' : 
                                            ($user['role_name'] === 'Dentist' ? 'success' : 'warning'); ?>-enhanced">
                                            <?= htmlspecialchars($user['role_name']); ?>
                                        </span>
                                        
                                        <span class="badge-enhanced badge-<?= $user['is_active'] ? 'success' : 'error'; ?>-enhanced">
                                            <?= $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- User Actions -->
                                <div style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
                                    <!-- Role & Status Update Form -->
                                    <form method="post" style="display: flex; gap: 12px; align-items: end; flex: 1;">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                                        
                                        <div style="min-width: 140px;">
                                            <label class="form-label-enhanced" style="font-size: 12px;">Role</label>
                                            <select name="role_id" class="form-select-enhanced" style="padding: 8px 12px; font-size: 14px;">
                                                <?php foreach ($roleMap as $rid => $rname): ?>
                                                    <?php if ($rname !== 'Patient'): ?>
                                                        <option value="<?= $rid; ?>" <?= $rid == $user['role_id'] ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($rname); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <input type="checkbox" name="is_active" value="1" id="active_<?= $user['user_id']; ?>"
                                                   <?= $user['is_active'] ? 'checked' : ''; ?> style="margin: 0;">
                                            <label for="active_<?= $user['user_id']; ?>" style="font-size: 14px; margin: 0;">Active</label>
                                        </div>
                                        
                                        <button type="submit" class="btn-secondary-enhanced" style="padding: 8px 16px; font-size: 14px;">
                                            💾 Update
                                        </button>
                                    </form>

                                    <!-- Password Reset Form -->
                                    <form method="post" style="display: flex; gap: 8px; align-items: end;">
                                        <input type="hidden" name="action" value="reset_pw">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                                        
                                        <div style="min-width: 120px;">
                                            <label class="form-label-enhanced" style="font-size: 12px;">New Password</label>
                                            <input type="password" name="new_password" class="form-input-enhanced" 
                                                   style="padding: 8px 12px; font-size: 14px;" placeholder="New password" required>
                                        </div>
                                        
                                        <button type="submit" class="btn-warning-enhanced" style="padding: 8px 16px; font-size: 14px;">
                                            🔑 Reset
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
/* Enhanced User Management Styles */
main.main-content-enhanced {
    flex: 1;
    padding: 0;
    background: #f8fafc;
    margin: 0;
    overflow-x: auto;
}

/* Override default main padding */
body:has(main.main-content-enhanced) main {
    padding: 0;
}

/* Role-specific fields */
.role-specific-fields {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}

.role-specific-fields h4 {
    margin: 0 0 12px 0;
    color: #374151;
    font-size: 14px;
    font-weight: 600;
}

/* User cards */
.user-card {
    transition: all 0.2s ease;
}

.user-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Warning button */
.btn-warning-enhanced {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.4);
}

.btn-warning-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(245, 158, 11, 0.4);
    text-decoration: none;
    color: white;
}

/* Form improvements */
.form-group {
    margin-bottom: 16px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .user-card > div:last-child {
        flex-direction: column;
        align-items: stretch;
    }
    
    .user-card form {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('roleSelect');
    const dentistFields = document.getElementById('dentistFields');
    const receptionistFields = document.getElementById('receptionistFields');
    
    function toggleRoleFields() {
        const selectedRole = roleSelect.value;
        const selectedRoleName = roleSelect.options[roleSelect.selectedIndex].text;
        
        // Hide all role-specific fields first
        dentistFields.style.display = 'none';
        receptionistFields.style.display = 'none';
        
        // Show relevant fields based on selection
        if (selectedRoleName === 'Dentist') {
            dentistFields.style.display = 'block';
        } else if (selectedRoleName === 'Receptionist') {
            receptionistFields.style.display = 'block';
        }
    }
    
    // Listen for role changes
    roleSelect.addEventListener('change', toggleRoleFields);
    
    // Initialize on page load
    toggleRoleFields();
    
    // Form validation
    document.getElementById('addUserForm').addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="password"]').value;
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long.');
            return false;
        }
    });
});
</script>
<?php include BASE_PATH . '/templates/footer.php'; ?>