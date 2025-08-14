<?php
/*****************************************************************
 * auth/logout.php
 * ---------------------------------------------------------------
 * Destroys the current session and sends the user back to login.
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';   // loads BASE_PATH
require_once BASE_PATH . '/includes/functions.php';   // redirect() helper

/* ─── optional audit log ─── */
if (!empty($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $conn->query("INSERT INTO AuditLog (user_id, action)
                  VALUES ($uid, 'User logout')");
}

/* ─── clear session ─── */
session_unset();   // remove all session variables
session_destroy(); // end the session

/* ─── goodbye flash & redirect ─── */
flash('You have been logged out.');
redirect('/auth/login.php');