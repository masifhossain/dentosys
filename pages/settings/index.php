<?php
/*****************************************************************
 * pages/settings/index.php
 * ---------------------------------------------------------------
 * Admin Settings Dashboard - Central hub for all admin functions
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_admin();

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<main class="main-content-enhanced">
    <!-- Header Section -->
    <div class="content-header">
        <h1>⚙️ Administration Settings</h1>
        <div class="breadcrumb">
            Manage system settings and staff accounts
        </div>
    </div>

    <div class="content-body">
        <div class="grid grid-cols-3 gap-6">
            <!-- Staff Management -->
            <div class="card-enhanced admin-menu-card">
                <div class="card-body" style="text-align: center; padding: 40px 30px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">👥</div>
                    <h3 style="margin: 0 0 12px; color: #1e293b;">Staff Management</h3>
                    <p style="color: #64748b; margin-bottom: 24px; font-size: 14px;">
                        Create and manage dentist and receptionist accounts
                    </p>
                    <a href="users.php" class="btn-primary-enhanced">
                        Manage Staff Accounts
                    </a>
                </div>
            </div>

            <!-- Patient Management -->
            <div class="card-enhanced admin-menu-card">
                <div class="card-body" style="text-align: center; padding: 40px 30px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🏥</div>
                    <h3 style="margin: 0 0 12px; color: #1e293b;">Patient Management</h3>
                    <p style="color: #64748b; margin-bottom: 24px; font-size: 14px;">
                        Manage patient accounts and portal access
                    </p>
                    <a href="patients.php" class="btn-primary-enhanced">
                        Manage Patient Accounts
                    </a>
                </div>
            </div>

            <!-- Clinic Information -->
            <div class="card-enhanced admin-menu-card">
                <div class="card-body" style="text-align: center; padding: 40px 30px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🏥</div>
                    <h3 style="margin: 0 0 12px; color: #1e293b;">Clinic Settings</h3>
                    <p style="color: #64748b; margin-bottom: 24px; font-size: 14px;">
                        Update clinic information and contact details
                    </p>
                    <a href="clinic_info.php" class="btn-primary-enhanced">
                        Edit Clinic Info
                    </a>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="grid grid-cols-2 gap-6" style="margin-top: 30px;">

            <!-- Role Management -->
            <div class="card-enhanced admin-menu-card">
                <div class="card-body" style="text-align: center; padding: 40px 30px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🔐</div>
                    <h3 style="margin: 0 0 12px; color: #1e293b;">Role Management</h3>
                    <p style="color: #64748b; margin-bottom: 24px; font-size: 14px;">
                        Configure user roles and permissions
                    </p>
                    <a href="roles.php" class="btn-primary-enhanced">
                        Manage Roles
                    </a>
                </div>
            </div>

            <!-- System Integrations -->
            <div class="card-enhanced admin-menu-card">
                <div class="card-body" style="text-align: center; padding: 40px 30px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🔗</div>
                    <h3 style="margin: 0 0 12px; color: #1e293b;">Integrations</h3>
                    <p style="color: #64748b; margin-bottom: 24px; font-size: 14px;">
                        Configure external system integrations
                    </p>
                    <a href="integrations.php" class="btn-primary-enhanced">
                        Manage Integrations
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats Section -->
        <div class="card-enhanced" style="margin-top: 40px;">
            <div class="card-header">
                <h3>📊 System Overview</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-4 gap-4">
                    <?php
                    // Get system stats
                    $totalUsers = $conn->query("SELECT COUNT(*) as c FROM usertbl WHERE role_id != 4")->fetch_assoc()['c'] ?? 0;
                    $totalPatients = $conn->query("SELECT COUNT(*) as c FROM patient")->fetch_assoc()['c'] ?? 0;
                    $totalAppointments = $conn->query("SELECT COUNT(*) as c FROM appointment WHERE DATE(appointment_dt) >= CURDATE()")->fetch_assoc()['c'] ?? 0;
                    $activeUsers = $conn->query("SELECT COUNT(*) as c FROM usertbl WHERE is_active = 1 AND role_id != 4")->fetch_assoc()['c'] ?? 0;
                    ?>
                    
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: 700; color: #059669; margin-bottom: 8px;">
                            <?= $totalUsers; ?>
                        </div>
                        <div style="font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            Staff Accounts
                        </div>
                    </div>
                    
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: 700; color: #3b82f6; margin-bottom: 8px;">
                            <?= $activeUsers; ?>
                        </div>
                        <div style="font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            Active Staff
                        </div>
                    </div>
                    
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: 700; color: #f59e0b; margin-bottom: 8px;">
                            <?= $totalPatients; ?>
                        </div>
                        <div style="font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            Total Patients
                        </div>
                    </div>
                    
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: 700; color: #8b5cf6; margin-bottom: 8px;">
                            <?= $totalAppointments; ?>
                        </div>
                        <div style="font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            Upcoming Appointments
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Enhanced Admin Settings Styles */
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

/* Admin menu cards */
.admin-menu-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.admin-menu-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid-cols-2,
    .grid-cols-3,
    .grid-cols-4 {
        grid-template-columns: 1fr;
    }
    
    .admin-menu-card .card-body {
        padding: 30px 20px;
    }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
