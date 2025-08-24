<?php
/**
 * templates/header.php
 * ---------------------------------------------------------------
 * Opens the document, links CSS, and starts <body>.
 *  – Adds styling so the footer is always at the bottom.
 */
if (!defined('BASE_PATH')) { exit; }

$pageTitle = $pageTitle ?? 'DentoSys';
// Ensure UTF-8 is sent in HTTP headers to avoid mojibake (�) for emoji/text
if (!headers_sent()) {
  header('Content-Type: text/html; charset=utf-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($pageTitle); ?></title>

  <!-- Main stylesheets (cache-busted) -->
  <?php
    $fwCssV = @filemtime(BASE_PATH . '/assets/css/framework.css') ?: time();
    $appCssV = @filemtime(BASE_PATH . '/assets/css/style.css') ?: time();
  ?>
  <link rel="stylesheet" href="/dentosys/assets/css/framework.css?v=<?= $fwCssV ?>">
  <link rel="stylesheet" href="/dentosys/assets/css/style.css?v=<?= $appCssV ?>">
  
  <!-- Google Fonts for enhanced typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Common JavaScript utilities -->
  <?php
    $commonJsV = @filemtime(BASE_PATH . '/assets/js/common.js') ?: time();
  ?>
  <script src="/dentosys/assets/js/common.js?v=<?= $commonJsV ?>" defer></script>

  <!-- Fallback / critical styles -->
  <style>
    /* Minimal critical styles to prevent FOUC */
    body { display: flex; margin: 0; }
    main { flex: 1; }
  </style>
  <style>
    /* Responsive fix for footer when sidebar collapses */
    @media (max-width: 768px) {
      .site-footer{ left:0; width:100%; }
    }
  </style>
</head>
<body>