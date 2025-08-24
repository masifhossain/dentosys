<?php
/*****************************************************************
 * pages/settings/roles.php
 * ---------------------------------------------------------------
 * Manage user roles & permissions (Admin-only).
 *
 * Table expected:
 *   CREATE TABLE Role (
 *     role_id   INT AUTO_INCREMENT PRIMARY KEY,
 *     role_name VARCHAR(40) UNIQUE NOT NULL
 *   ) ENGINE=InnoDB;
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_admin();

/* --------------------------------------------------------------
 * 1. Add NEW role
 * ------------------------------------------------------------ */
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $conn->real_escape_string(trim($_POST['new_role']));
    if ($name === '') {
        flash('Role name cannot be empty.');
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO Role (role_name) VALUES (?)");
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            if ($stmt->affected_rows) {
                flash('Role added.');
            } else {
                flash('Role already exists.');
            }
        } else {
            flash('DB error: ' . $conn->error);
        }
    }
    redirect('roles.php');
}

/* --------------------------------------------------------------
 * 2. Rename existing role
 * ------------------------------------------------------------ */
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $rid  = intval($_POST['role_id']);
    $name = $conn->real_escape_string(trim($_POST['role_name']));

    if ($name === '') {
        flash('Role name cannot be empty.');
    } else {
        $stmt = $conn->prepare(
          "UPDATE Role SET role_name = ? WHERE role_id = ? LIMIT 1"
        );
        $stmt->bind_param('si', $name, $rid);
        if ($stmt->execute()) {
            flash('Role updated.');
        } else {
            flash('DB error: ' . $conn->error);
        }
    }
    redirect('roles.php');
}

/* --------------------------------------------------------------
 * 3. Delete role (only if no users linked)
 * ------------------------------------------------------------ */
if (isset($_GET['delete'])) {
    $rid = intval($_GET['delete']);

    // Prevent deleting Admin (role_id = 1) or if users still linked
    $protected = ($rid === 1);
    $hasUsers  = $conn->query(
        "SELECT 1 FROM usertbl WHERE role_id = $rid LIMIT 1"
    )->num_rows > 0;

    if ($protected) {
        flash('Cannot delete the Admin role.');
    } elseif ($hasUsers) {
        flash('Role has users assigned; reassign them first.');
    } else {
        $conn->query("DELETE FROM Role WHERE role_id = $rid LIMIT 1");
        flash('Role deleted.');
    }
    redirect('roles.php');
}

/* --------------------------------------------------------------
 * 4. Fetch roles list
 * ------------------------------------------------------------ */
$roles = $conn->query("SELECT * FROM Role ORDER BY role_id");

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
.roles-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    min-height: 100vh;
}

.roles-header {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(14, 165, 233, 0.3);
}

.roles-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.roles-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.roles-content {
    display: grid;
    gap: 2rem;
    max-width: 1000px;
}

.roles-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.card-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.form-group {
    display: flex;
    gap: 1rem;
    align-items: end;
}

.form-input {
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s ease;
    background: white;
    flex: 1;
}

.form-input:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.form-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    margin-bottom: 0.5rem;
    display: block;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.btn-primary {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -8px rgba(14, 165, 233, 0.4);
}

.btn-secondary {
    background: #f8fafc;
    color: #475569;
    border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -8px rgba(239, 68, 68, 0.4);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}

.roles-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.roles-table th {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e2e8f0;
}

.roles-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.roles-table tbody tr:hover {
    background: #f8fafc;
}

.role-id {
    font-weight: 600;
    color: #0ea5e9;
    font-size: 0.875rem;
}

.role-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.role-admin {
    background: #fef3c7;
    color: #92400e;
}

.role-default {
    background: #e0e7ff;
    color: #3730a3;
}

.inline-edit-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.inline-edit-input {
    padding: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.875rem;
    width: 150px;
}

.inline-edit-input:focus {
    outline: none;
    border-color: #0ea5e9;
}

.protected-role {
    color: #64748b;
    font-style: italic;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #64748b;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .roles-main {
        padding: 0 1rem 2rem;
    }
    
    .roles-header {
        margin: 0 -1rem 1.5rem;
        padding: 1.5rem;
    }
    
    .roles-title {
        font-size: 2rem;
    }
    
    .roles-card {
        padding: 1.5rem;
    }
    
    .form-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .roles-table {
        font-size: 0.875rem;
    }
    
    .roles-table th,
    .roles-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<main class="roles-main">
    <div class="roles-header">
        <h1 class="roles-title">
            <span>👥</span>
            User Roles & Permissions
        </h1>
        <p class="roles-subtitle">
            Manage system roles and access control for different user types
        </p>
    </div>

    <?= get_flash(); ?>

    <div class="roles-content">
        <!-- Add New Role -->
        <div class="roles-card">
            <div class="card-header">
                <div class="card-icon">➕</div>
                <h3 class="card-title">Create New Role</h3>
            </div>
            
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <div style="flex: 1;">
                        <label class="form-label">Role Name</label>
                        <input type="text" name="new_role" class="form-input" 
                               placeholder="Enter role name (e.g., Receptionist, Hygienist)" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span>✅</span>
                        Add Role
                    </button>
                </div>
            </form>
        </div>

        <!-- Roles List -->
        <div class="roles-card">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <h3 class="card-title">System Roles</h3>
            </div>
            
            <?php if ($roles->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👤</div>
                    <h3>No roles configured</h3>
                    <p>Create your first role to start managing user permissions.</p>
                </div>
            <?php else: ?>
                <table class="roles-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Role Name</th>
                            <th style="width: 100px;">Type</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $roles->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="role-id">#<?= $r['role_id']; ?></span>
                                </td>
                                <td>
                                    <?php if ($r['role_id'] === 1): ?>
                                        <!-- Protect admin role from editing -->
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-weight: 600;"><?= htmlspecialchars($r['role_name']); ?></span>
                                            <span class="role-badge role-admin">Protected</span>
                                        </div>
                                        <div class="protected-role">System administrator role - cannot be modified</div>
                                    <?php else: ?>
                                        <!-- Inline edit form for other roles -->
                                        <form method="post" class="inline-edit-form">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="role_id" value="<?= $r['role_id']; ?>">
                                            <input type="text" name="role_name" class="inline-edit-input"
                                                   value="<?= htmlspecialchars($r['role_name']); ?>" required>
                                            <button type="submit" class="btn btn-sm btn-secondary">
                                                💾 Save
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['role_id'] === 1): ?>
                                        <span class="role-badge role-admin">Admin</span>
                                    <?php else: ?>
                                        <span class="role-badge role-default">Custom</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['role_id'] !== 1): ?>
                                        <button onclick="deleteRole(<?= $r['role_id']; ?>, '<?= htmlspecialchars($r['role_name'], ENT_QUOTES); ?>')" 
                                                class="btn btn-sm btn-danger">
                                            🗑️ Delete
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #64748b; font-size: 0.875rem;">Protected Role</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Role Information -->
        <div class="roles-card" style="background: #f8fafc; border-color: #e2e8f0;">
            <div class="card-header">
                <div class="card-icon">ℹ️</div>
                <h3 class="card-title">Role Management Information</h3>
            </div>
            
            <div style="display: grid; gap: 1rem; font-size: 0.875rem; color: #64748b;">
                <p><strong>⚠️ Important Notes:</strong></p>
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <li>The <strong>Admin</strong> role cannot be deleted or renamed for security reasons</li>
                    <li>You cannot delete roles that have users assigned to them</li>
                    <li>Always reassign users before deleting a role</li>
                    <li>Role permissions are managed through the application's access control system</li>
                </ul>
                
                <p><strong>💡 Best Practices:</strong></p>
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <li>Use descriptive role names (e.g., "Dental Hygienist", "Office Manager")</li>
                    <li>Create roles based on job functions rather than individual users</li>
                    <li>Regularly review and update role assignments</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<script>
function deleteRole(roleId, roleName) {
    if (confirm(`Are you sure you want to delete the role "${roleName}"?\n\nThis action cannot be undone. Make sure no users are assigned to this role.`)) {
        window.location.href = `?delete=${roleId}`;
    }
}
</script>
<?php include BASE_PATH . '/templates/footer.php'; ?>