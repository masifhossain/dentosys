<?php
// includes/functions.php

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit;
}

// Flash messaging
function flash($msg) {
    $_SESSION['flash'] = $msg;
}
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return "<div class=\"flash\">$f</div>";
    }
    return '';
}

// Simple auth check
function require_login() {
    if (empty($_SESSION['user_id'])) {
        redirect('/dentosys/login.php');
    }
}
?>