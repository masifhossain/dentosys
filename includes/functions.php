<?php
/**
 * functions.php
 * ------------------------------------------------------------------
 * Common helpers: redirects, flash messages, auth gate,
 * dropdown look-ups, role helper, etc.
 * ------------------------------------------------------------------
 */

require_once BASE_PATH . '/includes/db.php';   // ensures $conn + session

/* ───────── App-wide Emoji → HTML Entities (HTML responses only) ───────── */
if (!defined('EMOJI_ENTITY_OUTPUT')) {
    // Default disabled to avoid rendering entity codes literally in some contexts.
    // Enable per-page by defining EMOJI_ENTITY_OUTPUT as true before including this file.
    define('EMOJI_ENTITY_OUTPUT', false);

    /**
     * Convert emoji code points (and joiner/variation selectors) in a string
     * to HTML numeric entities so they render consistently across platforms/browsers.
     * Only converts known emoji ranges and related code points.
     */
    function convert_emojis_to_entities(string $str): string {
        // Unicode ranges for emojis and related symbols + ZWJ (200D) + VS16 (FE0F)
        $pattern = '/[\x{1F300}-\x{1F5FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}\x{1FB00}-\x{1FBFF}\x{200D}\x{FE0F}]/u';
        return preg_replace_callback($pattern, function($m) {
            $char = $m[0];
            $cp = utf8_codepoint($char);
            return $cp !== null ? ('&#' . $cp . ';') : $char;
        }, $str);
    }

    /**
     * Get Unicode code point from a single UTF-8 encoded character (1-4 bytes).
     */
    function utf8_codepoint(string $char): ?int {
        $bytes = unpack('C*', $char);
        $len = count($bytes);
        if ($len === 1) {
            return $bytes[1];
        } elseif ($len === 2) {
            return (($bytes[1] & 0x1F) << 6) | ($bytes[2] & 0x3F);
        } elseif ($len === 3) {
            return (($bytes[1] & 0x0F) << 12) | (($bytes[2] & 0x3F) << 6) | ($bytes[3] & 0x3F);
        } elseif ($len === 4) {
            return (($bytes[1] & 0x07) << 18) | (($bytes[2] & 0x3F) << 12) | (($bytes[3] & 0x3F) << 6) | ($bytes[4] & 0x3F);
        }
        return null;
    }

    /**
     * Convert emojis in HTML while skipping <style> and <script> blocks
     * to avoid breaking CSS/JS where entities are not interpreted.
     */
    function convert_emojis_in_html_skipping_style_script(string $html): string {
        $pattern = '/<(style|script)(?:\s[^>]*)?>.*?<\/\1>/is';
        $out = '';
        $offset = 0;
        while (preg_match($pattern, $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1];
            $length = strlen($m[0][0]);
            $before = substr($html, $offset, $start - $offset);
            // Convert in the non-style/script part
            $out .= convert_emojis_to_entities($before);
            // Append the untouched <style>/<script> block
            $out .= $m[0][0];
            $offset = $start + $length;
        }
        // Tail after the last match
        $out .= convert_emojis_to_entities(substr($html, $offset));
        return $out;
    }

    // Start output buffering with a callback to convert emojis only for text/html responses
    if (EMOJI_ENTITY_OUTPUT && !headers_sent()) {
        ob_start(function ($buffer) {
            $contentType = 'text/html';
            foreach (headers_list() as $h) {
                if (stripos($h, 'Content-Type:') === 0) {
                    $contentType = trim(substr($h, 13));
                    break;
                }
            }
            if (stripos($contentType, 'text/html') !== false) {
                return convert_emojis_in_html_skipping_style_script($buffer);
            }
            return $buffer; // Do not transform non-HTML responses (CSV/JSON, etc.)
        });
    }
}

/* ───────── Redirect helper ───────── */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/* ───────── Flash message helpers ───────── */
function flash(string $msg, string $type = 'info'): void   { 
    $_SESSION['flash'] = $msg; 
    $_SESSION['flash_type'] = $type;
}
function get_flash(): string {
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        $type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
        unset($_SESSION['flash']);
        unset($_SESSION['flash_type']);
        return "<div class='flash flash-$type'>$m</div>";
    }
    return '';
}

/* ───────── Authentication gate ───────── */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        redirect('/dentosys/');
    }
}

/* ───────── Role helper (optional) ───────── */
function is_admin(): bool {
    return (isset($_SESSION['role']) && (int)$_SESSION['role'] === 1); // 1 = admin
}

function is_dentist(): bool {
    return (isset($_SESSION['role']) && (int)$_SESSION['role'] === 2); // 2 = dentist
}

function is_receptionist(): bool {
    return (isset($_SESSION['role']) && (int)$_SESSION['role'] === 3); // 3 = receptionist
}

function is_patient(): bool {
    return (isset($_SESSION['role']) && (int)$_SESSION['role'] === 4); // 4 = patient
}

function is_staff(): bool {
    return isset($_SESSION['role']) && in_array((int)$_SESSION['role'], [1, 2, 3]); // Admin, Dentist, Receptionist
}

function require_staff(): void {
    if (!is_staff()) {
        flash('Access denied. Staff only.');
        if (is_patient()) {
            redirect('/dentosys/pages/patients/dashboard.php');
        } else {
            redirect('/dentosys/');
        }
    }
}

function require_patient(): void {
    if (!is_patient()) {
        flash('Access denied. Patients only.');
        redirect('/dentosys/pages/dashboard.php');
    }
}

function require_admin(): void {
    if (!is_admin()) {
        flash('Access denied. Administrators only.');
        if (is_patient()) {
            redirect('/dentosys/pages/patients/my_profile.php');
        } else {
            redirect('/dentosys/pages/dashboard.php');
        }
    }
}

/* ───────── Dropdown look-ups ───────── */
function get_patients(mysqli $c) {
    return $c->query(
        "SELECT patient_id,
                CONCAT(first_name,' ',last_name) AS name
         FROM Patient
         ORDER BY name"
    );
}

function get_dentists(mysqli $c) {
    return $c->query(
        "SELECT d.dentist_id,
                CONCAT(u.email,' (',
                       IFNULL(d.specialty,'General'),
                       ')') AS name
         FROM Dentist d
         JOIN UserTbl u ON u.user_id = d.user_id
         ORDER BY name"
    );
}

/* ───────── Enhanced Audit Logging ───────── */
function log_audit_action(
    string $action, 
    string $action_type = 'GENERAL', 
    string $table_name = null, 
    int $record_id = null,
    string $details = null, 
    string $severity = 'LOW',
    int $user_id = null
): bool {
    global $conn;
    
    // Use current session user if not specified
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? null;
    }
    
    // Get client information
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $session_id = session_id() ?: null;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO auditlog 
            (user_id, action, action_type, table_name, record_id, details, ip_address, user_agent, session_id, severity, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param(
            "ississssss", 
            $user_id, $action, $action_type, $table_name, $record_id, 
            $details, $ip_address, $user_agent, $session_id, $severity
        );
        
        return $stmt->execute();
    } catch (Exception $e) {
        // Log to PHP error log but don't break the application
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

function log_login_attempt(string $email, bool $success, string $user_type = 'staff'): bool {
    $action = $success ? 'Login successful' : 'Login failed';
    $action_type = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
    $severity = $success ? 'LOW' : 'MEDIUM';
    $details = ($success ? 'Successful' : 'Failed') . " $user_type login for: $email";
    
    return log_audit_action($action, $action_type, 'UserTbl', null, $details, $severity);
}

function log_logout(int $user_id): bool {
    return log_audit_action('User logout', 'LOGOUT', 'UserTbl', $user_id, 'User logged out', 'LOW', $user_id);
}

function log_data_change(string $operation, string $table, int $record_id, string $details = null): bool {
    $action_types = [
        'create' => 'CREATE',
        'update' => 'UPDATE', 
        'delete' => 'DELETE'
    ];
    
    $severities = [
        'create' => 'LOW',
        'update' => 'LOW',
        'delete' => 'HIGH'
    ];
    
    $operation = strtolower($operation);
    $action_type = $action_types[$operation] ?? 'GENERAL';
    $severity = $severities[$operation] ?? 'LOW';
    $action = ucfirst($operation) . " operation on $table";
    
    return log_audit_action($action, $action_type, $table, $record_id, $details, $severity);
}

function log_security_event(string $event, string $details = null, string $severity = 'HIGH'): bool {
    return log_audit_action($event, 'SECURITY', null, null, $details, $severity);
}

function log_system_event(string $event, string $details = null): bool {
    return log_audit_action($event, 'SYSTEM', 'System', null, $details, 'MEDIUM');
}

function log_export_event(string $report_type, string $details = null): bool {
    $action = "Exported $report_type report";
    return log_audit_action($action, 'EXPORT', 'Report', null, $details, 'LOW');
}

/* ───────── Patient User Account Management ───────── */
/**
 * Generate a temporary password for staff-created patient accounts
 * Format: FirstnameLastname + 4 random digits
 */
function generate_patient_temp_password(string $firstName, string $lastName): string {
    $cleanFirst = ucfirst(strtolower(preg_replace('/[^a-zA-Z]/', '', $firstName)));
    $cleanLast = ucfirst(strtolower(preg_replace('/[^a-zA-Z]/', '', $lastName)));
    $randomDigits = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    
    return $cleanFirst . $cleanLast . $randomDigits;
}

/**
 * Create a user account for a patient added by staff
 * Returns the user_id if successful, false on failure
 */
function create_patient_user_account(
    string $email, 
    string $firstName, 
    string $lastName, 
    int $patientId
): int|false {
    global $conn;
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Check if user already exists with this email
    $stmt = $conn->prepare("SELECT user_id FROM UserTbl WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User already exists - link the existing user to this patient
        $row = $result->fetch_assoc();
        $userId = $row['user_id'];
        
        // Update patient record to link to existing user
        $updateStmt = $conn->prepare("UPDATE Patient SET user_id = ? WHERE patient_id = ?");
        $updateStmt->bind_param("ii", $userId, $patientId);
        $updateStmt->execute();
        
        return $userId;
    }
    
    try {
        // Generate temporary password
        $tempPassword = generate_patient_temp_password($firstName, $lastName);
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Create user account (role_id 4 = Patient)
        $stmt = $conn->prepare("
            INSERT INTO UserTbl (email, password_hash, role_id, is_active, created_at) 
            VALUES (?, ?, 4, 1, NOW())
        ");
        $stmt->bind_param("ss", $email, $hashedPassword);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        $userId = $conn->insert_id;
        
        // Update patient record to link to the new user
        $updateStmt = $conn->prepare("UPDATE Patient SET user_id = ? WHERE patient_id = ?");
        $updateStmt->bind_param("ii", $userId, $patientId);
        
        if (!$updateStmt->execute()) {
            // Rollback - delete the user account we just created
            $deleteStmt = $conn->prepare("DELETE FROM UserTbl WHERE user_id = ?");
            $deleteStmt->bind_param("i", $userId);
            $deleteStmt->execute();
            return false;
        }
        
        // Log the account creation
        log_audit_action(
            "Patient user account created by staff", 
            'CREATE', 
            'UserTbl', 
            $userId,
            "Auto-created account for patient: $firstName $lastName ($email). Temporary password: $tempPassword",
            'LOW'
        );
        
        // Store the temporary password in session for staff to inform patient
        if (!isset($_SESSION['temp_passwords'])) {
            $_SESSION['temp_passwords'] = [];
        }
        $_SESSION['temp_passwords'][$email] = $tempPassword;
        
        return $userId;
        
    } catch (Exception $e) {
        error_log("Patient user account creation failed: " . $e->getMessage());
        return false;
    }
}

/* ───────── Dentist Access Control Functions ───────── */

/**
 * Get the dentist_id for the current logged-in dentist
 */
function get_current_dentist_id(): int|false {
    global $conn;
    
    if (!is_dentist() || !isset($_SESSION['user_id'])) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT dentist_id FROM Dentist WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (int)$row['dentist_id'];
    }
    
    return false;
}

/**
 * Get all patient IDs that are assigned to the current dentist
 * (patients who have appointments with this dentist)
 */
function get_dentist_patient_ids(): array {
    global $conn;
    
    $dentistId = get_current_dentist_id();
    if (!$dentistId) {
        return [];
    }
    
    $stmt = $conn->prepare("
        SELECT DISTINCT patient_id 
        FROM Appointment 
        WHERE dentist_id = ?
        ORDER BY patient_id
    ");
    $stmt->bind_param("i", $dentistId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $patientIds = [];
    while ($row = $result->fetch_assoc()) {
        $patientIds[] = (int)$row['patient_id'];
    }
    
    return $patientIds;
}

/**
 * Check if a specific patient is assigned to the current dentist
 */
function can_dentist_access_patient(int $patientId): bool {
    if (!is_dentist()) {
        return true; // Non-dentists (admin/receptionist) can access all
    }
    
    $patientIds = get_dentist_patient_ids();
    return in_array($patientId, $patientIds);
}

/**
 * Get SQL WHERE clause for dentist patient filtering
 * Returns appropriate WHERE condition based on user role
 */
function get_dentist_patient_filter_sql(string $patientIdColumn = 'patient_id'): string {
    if (!is_dentist()) {
        return "1=1"; // No filtering for non-dentists
    }
    
    $patientIds = get_dentist_patient_ids();
    if (empty($patientIds)) {
        return "1=0"; // No patients assigned
    }
    
    $patientIdList = implode(',', $patientIds);
    return "$patientIdColumn IN ($patientIdList)";
}

/**
 * Require that the current user can access a specific patient
 * Redirects with error if access denied
 */
function require_patient_access(int $patientId): void {
    if (!can_dentist_access_patient($patientId)) {
        flash('Access denied. You can only view patients assigned to you.', 'error');
        redirect('/dentosys/pages/dashboard.php');
    }
}

/**
 * Check if current dentist can access appointment-related data
 */
function can_dentist_access_appointment(int $appointmentId): bool {
    global $conn;
    
    if (!is_dentist()) {
        return true; // Non-dentists can access all
    }
    
    $dentistId = get_current_dentist_id();
    if (!$dentistId) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT dentist_id FROM Appointment WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (int)$row['dentist_id'] === $dentistId;
    }
    
    return false;
}