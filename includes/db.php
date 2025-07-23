<?php
/**
 * db.php
 * ------------------------------------------------------------------
 * Global database connection + project-wide constants.
 * Included by (almost) every PHP page before any HTML output.
 * ------------------------------------------------------------------
 */

/* ───────── PROJECT ROOT (BASE_PATH) ───────── */
if (!defined('BASE_PATH')) {
    // Example: C:\xampp\htdocs\dentosys
    define('BASE_PATH', dirname(__DIR__));
}

/* ───────── START SESSION ───────── */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ───────── DATABASE SETTINGS ─────────
 *  Adjust for your local MySQL user / password.
 */
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';          //  ⇦  default XAMPP root has blank password
$DB_NAME = 'dentosys_db';

/* ───────── CONNECT ───────── */
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

/* ───────── ERROR CHECK ───────── */
if ($conn->connect_error) {
    die('MySQL Connection failed: ' . $conn->connect_error);
}

/* ───────── OPTIONAL SETTINGS ───────── */
$conn->set_charset('utf8mb4');           // full Unicode support
date_default_timezone_set('Australia/Sydney');  // pick your timezone