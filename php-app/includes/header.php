<?php
// Expects $pageTitle and $user (from require_role/require_login) to be set
// by the including page before this file is required.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Hospital Management System') ?></title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-shell">
  <?php require __DIR__ . '/sidebar.php'; ?>
  <main class="main-content">
