<?php
/*****************************************************************
 * index.php  (root)
 * ---------------------------------------------------------------
 * Entry point for DentoSys.
 *  • Requires login
 *  • Immediately redirects to the Dashboard module
 *****************************************************************/
require_once __DIR__ . '/includes/db.php';        // starts session + $conn
require_once BASE_PATH . '/includes/functions.php';

require_login();                                  // bounce unauthenticated users

/* If you want a landing dashboard-lite right here,
   replace the redirect line with header + sidebar + main content. */
redirect('/dentosys/pages/dashboard.php');