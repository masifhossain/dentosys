<?php
/**
 * templates/header.php
 * ---------------------------------------------------------------
 * Opens the document, links CSS, and starts <body>.
 *  – Adds styling so the footer is always at the bottom.
 */
if (!defined('BASE_PATH')) { exit; }

$pageTitle = $pageTitle ?? 'DentoSys';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($pageTitle); ?></title>

  <!-- Main stylesheets -->
  <link rel="stylesheet" href="/dentosys/assets/css/framework.css">
  <link rel="stylesheet" href="/dentosys/assets/css/style.css">
  
  <!-- Google Fonts for enhanced typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Fallback / critical styles -->
  <style>
    /* ===== Layout ===== */
    body               {display:flex;min-height:100vh;margin:0;font-family:Arial,Helvetica,sans-serif;}
    .sidebar           {width:200px;min-height:100vh;background:#004F6E;color:#fff;padding:20px 14px;box-sizing:border-box;}
    .sidebar a         {color:#fff;text-decoration:none;display:block;margin:8px 0;}
    .sidebar a:hover   {opacity:0.85}
    main               {flex:1;padding:20px 20px 60px;box-sizing:border-box;overflow-x:auto;}
    /* bottom padding ↑ ensures content isn’t hidden behind fixed footer */

    /* ===== Sticky footer ===== */
    .site-footer{
      position:fixed;
      left:200px;                 /* sidebar width */
      bottom:0;
      width:calc(100% - 200px);   /* fill remaining width */
      background:#f4f4f4;
      color:#666;
      text-align:center;
      font-size:12px;
      padding:6px 0;
      border-top:1px solid #ccc;
      box-sizing:border-box;
    }

    /* ===== Card (used in dashboard / help) ===== */
    .card{
      background:#f5f5f5;border:1px solid #ddd;border-radius:6px;
      padding:14px;text-align:center;min-width:160px;
    }
    .card h3{margin:0 0 6px;font-size:16px;color:#555;}

    /* ===== Buttons ===== */
    .btn{background:#0077aa;color:#fff;padding:4px 8px;border-radius:3px;text-decoration:none;margin-left:6px}
    .btn.ok{background:#2ecc71}
    .btn.cancel{background:#e74c3c}

    /* ===== Tables ===== */
    table{border-collapse:collapse;width:100%}
    th,td{border:1px solid #ccc;padding:6px}
    th{background:#f4f4f4}
  </style>
</head>
<body>