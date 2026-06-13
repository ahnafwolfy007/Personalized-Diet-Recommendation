<?php
// frontend/partials/head.php
// Shared document head for authenticated pages. Before including, the page sets:
//   $pageTitle (string)            – the <title>
//   $pageCss   (string|null)       – optional page-specific stylesheet in styles/
// Requires csrf_token() (available via guard.php) to emit the CSRF meta tag.
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'DietSync') ?></title>
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
  <link rel="stylesheet" href="./styles/base.css">
  <?php if (!empty($pageCss)): ?>
  <link rel="stylesheet" href="./styles/<?= htmlspecialchars($pageCss) ?>">
  <?php endif; ?>
  <script src="./assets/app.js"></script>
</head>
