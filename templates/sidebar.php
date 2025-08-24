<?php
/**
 * templates/sidebar.php  (role-aware + user email / role display)
 * ---------------------------------------------------------------
 * Role IDs (default seed):
 *   1 = Admin  – full access
 *   2 = Dentist – no Settings
 *   3 = Receptionist – no Reports, Communications, Settings
 *
 * Requires: $conn from db.php  (already included before sidebar.php is loaded)
 */
if (!defined('BASE_PATH')) { exit; }   // safety

/* ---- 1.  Fetch current user's email, name & role name -------------------- */
$userEmail = $userName = $roleName = '';
$roleID    = 0;
if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $qry = $conn->query(
        "SELECT u.email, u.first_name, u.last_name, r.role_name, r.role_id
         FROM UserTbl u
         JOIN Role   r ON r.role_id = u.role_id
         WHERE u.user_id = $uid LIMIT 1"
    );
    if ($row = $qry->fetch_assoc()) {
        $userEmail = $row['email'];
        $userName = trim($row['first_name'] . ' ' . $row['last_name']);
        $roleName  = $row['role_name'];
        $roleID    = (int) $row['role_id'];
        
        // Fallback to email if name is empty
        if (empty($userName)) {
            $userName = explode('@', $userEmail)[0];
        }
    }
}

/* ---- 2.  Helper to check active link ---------------------------- */
$current = $_SERVER['REQUEST_URI'];
$isActive = fn(string $sub) => (strpos($current, $sub) !== false);
?>

<style>
.modern-sidebar {
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    color: #e2e8f0;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    overflow: hidden;
}

.sidebar-header {
    padding: 1.75rem 1.5rem 1.25rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(135deg, #059669, #047857);
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}

.sidebar-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    z-index: 1;
}

.logo-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    letter-spacing: -0.025em;
}

.user-info {
    padding: 1.25rem 1.5rem;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    flex-shrink: 0;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 0.75rem;
    color: white;
    font-weight: 600;
}

.user-details {
    font-size: 0.875rem;
    line-height: 1.4;
}

.user-name {
    color: white;
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
    letter-spacing: -0.025em;
}

.user-email {
    color: #cbd5e1;
    font-weight: 500;
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
    word-break: break-all;
    opacity: 0.9;
}

.user-role {
    color: #94a3b8;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    display: inline-block;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
    overflow-x: hidden;
    max-height: calc(100vh - 180px);
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
    scroll-behavior: smooth;
}

.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

.nav-section {
    margin-bottom: 1.5rem;
}

.nav-section-title {
    padding: 0.5rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    color: #cbd5e1;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    position: relative;
}

.nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.05);
    border-left-color: #059669;
    transform: translateX(2px);
}

.nav-link.active {
    color: white;
    background: linear-gradient(90deg, rgba(5, 150, 105, 0.2), rgba(5, 150, 105, 0.05));
    border-left-color: #059669;
    font-weight: 600;
}

.nav-link.active::before {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 20px;
    background: #059669;
    border-radius: 2px 0 0 2px;
}

.nav-icon {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.nav-divider {
    margin: 1rem 1.5rem;
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
}

.sidebar-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
    flex-shrink: 0;
}

.logout-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem;
    color: #f87171;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
    border: 1px solid rgba(248, 113, 113, 0.2);
}

.logout-link:hover {
    background: rgba(248, 113, 113, 0.1);
    border-color: rgba(248, 113, 113, 0.3);
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modern-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .modern-sidebar.mobile-open {
        transform: translateX(0);
    }
}

/* Main content adjustment: use padding to avoid horizontal overflow */
body {
    padding-left: 280px;
}

@media (max-width: 768px) {
    body { padding-left: 0; }
}
</style>

<aside class="modern-sidebar">
    <div class="sidebar-header">
        <div class="logo-section">
            <div class="logo-icon">🦷</div>
            <div class="logo-text">DentoSys</div>
        </div>
    </div>

    <div class="user-info">
        <div class="user-avatar">
            <?= strtoupper(substr($userName, 0, 1)); ?>
        </div>
        <div class="user-details">
            <div class="user-name"><?= htmlspecialchars($userName); ?></div>
            <div class="user-email"><?= htmlspecialchars($userEmail); ?></div>
            <div class="user-role"><?= htmlspecialchars($roleName); ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($roleID === 4): /* Patient portal */ ?>
            <div class="nav-section">
                <div class="nav-section-title">Patient Portal</div>
                <a class="nav-link <?= $isActive('/patients/dashboard.php') ? 'active' : '' ?>" href="/dentosys/pages/patients/dashboard.php">
                    <span class="nav-icon">🏠</span>
                    Dashboard
                </a>
                <a class="nav-link <?= $isActive('/patients/my_profile.php') ? 'active' : '' ?>" href="/dentosys/pages/patients/my_profile.php">
                    <span class="nav-icon">👤</span>
                    My Profile
                </a>
                <a class="nav-link <?= $isActive('/appointments') ? 'active' : '' ?>" href="/dentosys/pages/patients/my_appointments.php">
                    <span class="nav-icon">📅</span>
                    My Appointments
                </a>
                <a class="nav-link <?= $isActive('/records') ? 'active' : '' ?>" href="/dentosys/pages/patients/my_records.php">
                    <span class="nav-icon">📋</span>
                    My Records
                </a>
                <a class="nav-link <?= $isActive('/prescriptions') ? 'active' : '' ?>" href="/dentosys/pages/patients/my_prescriptions.php">
                    <span class="nav-icon">💊</span>
                    My Prescriptions
                </a>
                <a class="nav-link <?= $isActive('/billing') ? 'active' : '' ?>" href="/dentosys/pages/patients/my_billing.php">
                    <span class="nav-icon">💰</span>
                    My Bills
                </a>
                <a class="nav-link <?= $isActive('/book') ? 'active' : '' ?>" href="/dentosys/pages/patients/book_appointment.php">
                    <span class="nav-icon">➕</span>
                    Book Appointment
                </a>
            </div>
        <?php else: /* Staff portal */ ?>
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a class="nav-link <?= $isActive('/dashboard.php') ? 'active' : '' ?>" href="/dentosys/pages/dashboard.php">
                    <span class="nav-icon">🏠</span>
                    Dashboard
                </a>
                <a class="nav-link <?= $isActive('/patients') ? 'active' : '' ?>" href="/dentosys/pages/patients/list.php">
                    <span class="nav-icon">🧑‍⚕️</span>
                    <?= $roleID === 2 ? 'My Patients' : 'Patients' ?>
                </a>
                <?php if ($roleID !== 2): /* Hide appointment management for dentists */ ?>
                <a class="nav-link <?= $isActive('/appointments') ? 'active' : '' ?>" href="/dentosys/pages/appointments/calendar.php">
                    <span class="nav-icon">📅</span>
                    Appointments
                </a>
                <?php else: /* Dentists can view their appointments only */ ?>
                <a class="nav-link <?= $isActive('/appointments') ? 'active' : '' ?>" href="/dentosys/pages/appointments/calendar.php">
                    <span class="nav-icon">📅</span>
                    My Appointments
                </a>
                <?php endif; ?>
                <a class="nav-link <?= $isActive('/records') ? 'active' : '' ?>" href="/dentosys/pages/records/list.php">
                    <span class="nav-icon">📋</span>
                    Clinical Records
                </a>
                <?php if ($roleID !== 2): ?>
                <a class="nav-link <?= $isActive('/billing') ? 'active' : '' ?>" href="/dentosys/pages/billing/invoices.php">
                    <span class="nav-icon">💰</span>
                    Billing
                </a>
                <?php endif; ?>
            </div>

            <?php if ($roleID !== 3): /* Receptionist hidden */ ?>
            <div class="nav-section">
                <div class="nav-section-title">Analytics</div>
                <?php if ($roleID === 1): /* Admin - full reports access */ ?>
                <a class="nav-link <?= $isActive('/reports') ? 'active' : '' ?>" href="/dentosys/pages/reports/operational.php">
                    <span class="nav-icon">📊</span>
                    Reports
                </a>
                <?php elseif ($roleID === 2): /* Dentist - operational reports only */ ?>
                <a class="nav-link <?= $isActive('/reports') ? 'active' : '' ?>" href="/dentosys/pages/reports/operational.php">
                    <span class="nav-icon">📊</span>
                    My Reports
                </a>
                <?php endif; ?>
                <a class="nav-link <?= $isActive('/communications') ? 'active' : '' ?>" href="/dentosys/pages/communications/templates.php">
                    <span class="nav-icon">💬</span>
                    Communications
                </a>
            </div>
            <?php endif; ?>

            <?php if ($roleID === 1): /* Admin only */ ?>
            <div class="nav-section">
                <div class="nav-section-title">Administration</div>
                <a class="nav-link <?= $isActive('/settings') ? 'active' : '' ?>" href="/dentosys/pages/settings/index.php">
                    <span class="nav-icon">⚙️</span>
                    Settings
                </a>
                <a class="nav-link <?= $isActive('/users') ? 'active' : '' ?>" href="/dentosys/pages/settings/users.php">
                    <span class="nav-icon">👥</span>
                    Staff Management
                </a>
                <a class="nav-link <?= $isActive('/patients') && strpos($_SERVER['REQUEST_URI'], '/settings/') !== false ? 'active' : '' ?>" href="/dentosys/pages/settings/patients.php">
                    <span class="nav-icon">🏥</span>
                    Patient Management
                </a>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="nav-divider"></div>
    </nav>

    <div class="sidebar-footer">
        <a href="/dentosys/auth/logout.php" class="logout-link">
            <span class="nav-icon">🚪</span>
            Logout
        </a>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.modern-sidebar .sidebar-nav');
    const activeLink = document.querySelector('.nav-link.active');
    const isSettingsPage = window.location.pathname.includes('/settings/');
    
    // Add smooth scrolling to sidebar
    if (sidebar) {
        sidebar.style.scrollBehavior = 'smooth';
    }
    
    if (sidebar && (activeLink || isSettingsPage)) {
        // Small delay to ensure page is fully loaded
        setTimeout(() => {
            // Get saved scroll position or calculate based on active link
            const savedScrollPos = sessionStorage.getItem('sidebarScrollPosition');
            
            // If on settings page, ensure Administration section is visible
            if (isSettingsPage) {
                const adminSection = sidebar.querySelector('.nav-section:has(.nav-link[href*="/settings/"])');
                if (adminSection) {
                    const sectionTop = adminSection.offsetTop;
                    const sidebarHeight = sidebar.clientHeight;
                    
                    // Scroll to show administration section
                    const scrollTo = Math.max(0, sectionTop - (sidebarHeight / 4));
                    sidebar.scrollTop = scrollTo;
                    sessionStorage.setItem('sidebarScrollPosition', scrollTo.toString());
                    return;
                }
            }
            
            if (savedScrollPos && Math.abs(parseInt(savedScrollPos) - sidebar.scrollTop) > 10) {
                // Restore saved scroll position only if it's significantly different
                sidebar.scrollTop = parseInt(savedScrollPos);
            } else if (activeLink) {
                // Calculate scroll position to show active link
                const activeSection = activeLink.closest('.nav-section');
                if (activeSection) {
                    const sectionTop = activeSection.offsetTop;
                    const sidebarHeight = sidebar.clientHeight;
                    const sectionHeight = activeSection.offsetHeight;
                    
                    // Scroll to show the active section in the visible area
                    const scrollTo = Math.max(0, sectionTop - (sidebarHeight / 3));
                    
                    // Only scroll if the active link is not visible
                    const currentScroll = sidebar.scrollTop;
                    const linkTop = activeLink.offsetTop;
                    const linkBottom = linkTop + activeLink.offsetHeight;
                    const visibleTop = currentScroll;
                    const visibleBottom = currentScroll + sidebarHeight;
                    
                    if (linkTop < visibleTop || linkBottom > visibleBottom) {
                        sidebar.scrollTop = scrollTo;
                        sessionStorage.setItem('sidebarScrollPosition', scrollTo.toString());
                    }
                }
            }
        }, 100);
    }
    
    // Save scroll position when user scrolls
    if (sidebar) {
        let scrollTimeout;
        sidebar.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop.toString());
            }, 150);
        });
        
        // Save scroll position when clicking nav links
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop.toString());
            });
        });
    }
});
</script>
