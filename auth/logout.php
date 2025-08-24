<?php
/*****************************************************************
 * auth/logout.php
 * ---------------------------------------------------------------
 * Destroys the current session and sends the user back to login.
 *****************************************************************/
require_once dirname(__DIR__) . '/includes/db.php';   // loads BASE_PATH
require_once BASE_PATH . '/includes/functions.php';   // redirect() helper

/* ─── enhanced audit log ─── */
if (!empty($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    log_logout($uid);
}

/* ─── clear session ─── */
session_unset();   // remove all session variables
session_destroy(); // end the session

/* ─── goodbye flash & redirect ─── */
flash('You have been logged out.');
redirect('/dentosys/');