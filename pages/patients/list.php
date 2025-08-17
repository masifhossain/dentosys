<?php
/*****************************************************************
 * pages/patients/list_enhanced.php
 * ---------------------------------------------------------------
 * Enhanced Patient List with Figma Design
 * • Modern card-based layout
 * • Advanced search and filtering
 * • Interactive patient cards
 * • Quick actions and bulk operations
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

// Define BASE_URL if not defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}

/* ───────── Enhanced search and filtering ───────── */
$where_conditions = [];
$search = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'name';

if ($search !== '') {
    $esc = $conn->real_escape_string($search);
    $where_conditions[] = "(CONCAT(first_name,' ',last_name) LIKE '%$esc%'
                           OR email LIKE '%$esc%'
                           OR phone LIKE '%$esc%')";
}

if ($filter_status && $filter_status !== 'all') {
    $where_conditions[] = "status = '" . $conn->real_escape_string($filter_status) . "'";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Dynamic sorting
$order_clause = 'ORDER BY ';
switch ($sort_by) {
    case 'name':
        $order_clause .= 'last_name, first_name';
        break;
    case 'date':
        $order_clause .= 'created_at DESC';
        break;
    case 'age':
        $order_clause .= 'date_of_birth DESC';
        break;
    default:
        $order_clause .= 'last_name, first_name';
}

$sql = "SELECT *, 
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age,
        'Recently added' AS joined_date
        FROM patient 
        $where_clause 
        $order_clause";

$patients = $conn->query($sql);

// Get statistics
$total_patients = $conn->query("SELECT COUNT(*) as c FROM patient")->fetch_assoc()['c'];
$new_this_month = $conn->query("SELECT COUNT(*) as c FROM patient WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch_assoc()['c'];

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<div class="main-content-enhanced">
    <!-- Header Section -->
    <div class="content-header">
        <h1>👥 Patient Management</h1>
        <div class="breadcrumb">
            <?= $total_patients; ?> total patients • <?= $new_this_month; ?> joined this month
        </div>
    </div>

    <div class="content-body">
        <!-- Quick Stats -->
        <div class="grid grid-cols-4 gap-4" style="margin-bottom: 30px;">
            <div class="stats-card">
                <div class="stats-icon">👥</div>
                <div class="stats-value"><?= $total_patients; ?></div>
                <div class="stats-label">Total Patients</div>
            </div>
            <div class="stats-card">
                <div class="stats-icon">📅</div>
                <div class="stats-value"><?= $new_this_month; ?></div>
                <div class="stats-label">New This Month</div>
            </div>
            <div class="stats-card">
                <div class="stats-icon">🎂</div>
                <div class="stats-value">
                    <?php
                    $upcoming_birthdays = $conn->query("SELECT COUNT(*) as c FROM patient WHERE MONTH(dob) = MONTH(CURDATE())")->fetch_assoc()['c'];
                    echo $upcoming_birthdays;
                    ?>
                </div>
                <div class="stats-label">Birthdays This Month</div>
            </div>
            <div class="stats-card">
                <div class="stats-icon">⚡</div>
                <div class="stats-value">
                    <?php
                    $active_today = $conn->query("SELECT COUNT(DISTINCT a.patient_id) as c FROM appointment a WHERE DATE(a.appointment_dt) = CURDATE()")->fetch_assoc()['c'];
                    echo $active_today;
                    ?>
                </div>
                <div class="stats-label">Appointments Today</div>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="card-enhanced" style="margin-bottom: 30px;">
            <div class="card-body" style="padding: 20px;">
                <form method="get" style="display: flex; gap: 16px; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label class="form-label-enhanced">Search Patients</label>
                        <input type="text" name="q" class="form-input-enhanced" 
                               placeholder="Search by name, email, or phone..." 
                               value="<?= htmlspecialchars($search); ?>">
                    </div>
                    
                    <div style="min-width: 150px;">
                        <label class="form-label-enhanced">Filter by Status</label>
                        <select name="status" class="form-select-enhanced">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Active" <?= $filter_status === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?= $filter_status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div style="min-width: 150px;">
                        <label class="form-label-enhanced">Sort by</label>
                        <select name="sort" class="form-select-enhanced">
                            <option value="name" <?= $sort_by === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="date" <?= $sort_by === 'date' ? 'selected' : ''; ?>>Recently Added</option>
                            <option value="age" <?= $sort_by === 'age' ? 'selected' : ''; ?>>Age (Youngest)</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn-primary-enhanced">🔍 Search</button>
                        <a href="list_enhanced.php" class="btn-secondary-enhanced">Reset</a>
                        <a href="add.php" class="btn-success-enhanced">+ Add Patient</a>
                    </div>
                </form>
            </div>
        </div>

        <?= get_flash(); ?>

        <!-- Patients Grid -->
        <?php if ($patients->num_rows === 0): ?>
            <div class="card-enhanced">
                <div class="card-body" style="text-align: center; padding: 60px 40px;">
                    <div style="font-size: 64px; margin-bottom: 24px;">👤</div>
                    <h3 style="margin: 0 0 16px; color: var(--figma-text-primary);">No Patients Found</h3>
                    <p style="color: var(--figma-text-secondary); margin-bottom: 32px;">
                        <?= $search ? "No patients match your search criteria." : "Get started by adding your first patient."; ?>
                    </p>
                    <a href="add.php" class="btn-primary-enhanced">Add Your First Patient</a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-3 gap-4">
                <?php while ($patient = $patients->fetch_assoc()): ?>
                    <div class="card-enhanced patient-card" style="transition: all 0.2s ease;" 
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'" 
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                        
                        <!-- Patient Header -->
                        <div style="display: flex; align-items: center; padding: 20px 24px 16px; border-bottom: 1px solid #F3F4F6;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #0066CC, #004A99); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; margin-right: 16px; font-size: 18px;">
                                <?= strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?>
                            </div>
                            <div style="flex: 1;">
                                <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: var(--figma-text-primary);">
                                    <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                </h3>
                                <div style="font-size: 12px; color: var(--figma-text-secondary); margin-top: 2px;">
                                    Patient ID: #<?= $patient['patient_id']; ?>
                                </div>
                            </div>
                            <span class="badge-enhanced badge-<?= $patient['status'] === 'Active' ? 'success' : 'secondary'; ?>-enhanced">
                                <?= $patient['status'] ?? 'Active'; ?>
                            </span>
                        </div>

                        <!-- Patient Details -->
                        <div style="padding: 20px 24px;">
                            <div style="margin-bottom: 16px;">
                                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                    <span style="margin-right: 8px;">🎂</span>
                                    <span style="font-size: 14px; color: var(--figma-text-secondary);">
                                        <?= $patient['age']; ?> years old
                                        <?php if ($patient['date_of_birth']): ?>
                                            • Born <?= date('M j, Y', strtotime($patient['date_of_birth'])); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <?php if ($patient['email']): ?>
                                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                        <span style="margin-right: 8px;">📧</span>
                                        <span style="font-size: 14px; color: var(--figma-text-secondary);">
                                            <?= htmlspecialchars($patient['email']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($patient['phone']): ?>
                                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                        <span style="margin-right: 8px;">📞</span>
                                        <span style="font-size: 14px; color: var(--figma-text-secondary);">
                                            <?= htmlspecialchars($patient['phone']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="display: flex; align-items: center;">
                                    <span style="margin-right: 8px;">📅</span>
                                    <span style="font-size: 14px; color: var(--figma-text-secondary);">
                                        Joined <?= $patient['joined_date']; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div style="background: #F8FAFC; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px;">
                                    <?php
                                    $patient_id = $patient['patient_id'];
                                    $appointments = $conn->query("SELECT COUNT(*) as c FROM Appointment WHERE patient_id = $patient_id")->fetch_assoc()['c'];
                                    $invoices = $conn->query("SELECT COUNT(*) as c FROM Invoice WHERE patient_id = $patient_id")->fetch_assoc()['c'];
                                    $last_visit = $conn->query("SELECT MAX(appointment_dt) as last_visit FROM Appointment WHERE patient_id = $patient_id AND status = 'Completed'")->fetch_assoc()['last_visit'];
                                    ?>
                                    <div style="text-align: center;">
                                        <div style="font-weight: 600; color: var(--figma-primary);"><?= $appointments; ?></div>
                                        <div style="color: var(--figma-text-secondary);">Appointments</div>
                                    </div>
                                    <div style="text-align: center;">
                                        <div style="font-weight: 600; color: var(--figma-primary);"><?= $invoices; ?></div>
                                        <div style="color: var(--figma-text-secondary);">Invoices</div>
                                    </div>
                                    <div style="text-align: center;">
                                        <div style="font-weight: 600; color: var(--figma-primary);">
                                            <?= $last_visit ? date('M j', strtotime($last_visit)) : 'None'; ?>
                                        </div>
                                        <div style="color: var(--figma-text-secondary);">Last Visit</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 8px;">
                                <a href="view.php?id=<?= $patient['patient_id']; ?>" 
                                   class="btn-secondary-enhanced" style="flex: 1; text-align: center; font-size: 12px; padding: 8px;">
                                    👁️ View
                                </a>
                                <a href="edit.php?id=<?= $patient['patient_id']; ?>" 
                                   class="btn-secondary-enhanced" style="flex: 1; text-align: center; font-size: 12px; padding: 8px;">
                                    ✏️ Edit
                                </a>
                                <a href="../appointments/book.php?patient_id=<?= $patient['patient_id']; ?>" 
                                   class="btn-primary-enhanced" style="flex: 1; text-align: center; font-size: 12px; padding: 8px;">
                                    📅 Book
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination would go here -->
            <div style="text-align: center; margin-top: 40px;">
                <p style="color: var(--figma-text-secondary); font-size: 14px;">
                    Showing <?= $patients->num_rows; ?> of <?= $total_patients; ?> patients
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Responsive adjustments for patient cards */
@media (max-width: 1200px) {
    .grid-cols-3 { grid-template-columns: repeat(2, 1fr); }
    .grid-cols-4 { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .grid-cols-3,
    .grid-cols-4 { 
        grid-template-columns: repeat(1, 1fr); 
    }
    
    .content-header,
    .content-body {
        padding: 16px;
    }
    
    .patient-card {
        margin-bottom: 16px;
    }
}

/* Additional hover effects */
.patient-card:hover {
    cursor: pointer;
}

.patient-card:hover .stats-icon {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

/* Form responsive adjustments */
@media (max-width: 768px) {
    .card-enhanced .card-body form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .card-enhanced .card-body form > div {
        min-width: auto;
        width: 100%;
    }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
