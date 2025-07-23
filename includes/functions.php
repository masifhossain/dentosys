<?php
/**
 * functions.php
 * ------------------------------------------------------------------
 * Common helpers: redirects, flash messages, auth gate,
 * dropdown look-ups, role helper, etc.
 * ------------------------------------------------------------------
 */

require_once BASE_PATH . '/includes/db.php';   // ensures $conn + session

/* ───────── Redirect helper ───────── */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/* ───────── Flash message helpers ───────── */
function flash(string $msg): void   { $_SESSION['flash'] = $msg; }
function get_flash(): string {
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return "<div class='flash'>$m</div>";
    }
    return '';
}

/* ───────── Authentication gate ───────── */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        redirect('/dentosys/auth/login.php');
    }
}

/* ───────── Role helper (optional) ───────── */
function is_admin(): bool {
    return (isset($_SESSION['role']) && (int)$_SESSION['role'] === 1); // 1 = admin
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