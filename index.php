<?php
/*****************************************************************
 * index.php  (root)
 * ---------------------------------------------------------------
 * Entry point for DentoSys.
 *  • Shows landing page for non-logged-in users
 *  • Redirects logged-in users to appropriate dashboard
 *****************************************************************/
require_once __DIR__ . '/includes/db.php';        // starts session + $conn
require_once BASE_PATH . '/includes/functions.php';

/* Check if user is logged in */
if (!empty($_SESSION['user_id'])) {
    /* Role-based redirect for logged-in users */
    if (is_patient()) {
        redirect('/dentosys/pages/patients/dashboard.php');
    } else {
        redirect('/dentosys/pages/dashboard.php');
    }
} else {
    /* Show landing page for non-logged-in users */
    include __DIR__ . '/landing.php';
    exit;
}