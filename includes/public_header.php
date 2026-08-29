<?php
/** @var string|null $pageTitle */
$pageTitle = $pageTitle ?? '';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle !== '' ? $pageTitle : site_title()) ?></title>
  <link rel="stylesheet" href="<?= site_url('/assets/css/admin.css') ?>">
  <link rel="stylesheet" href="<?= site_url('/assets/css/front.css') ?>">
</head>
<body>
<header class="vrp-site-header">
  <div class="vrp-header-inner">
    <div class="vrp-brand">
      <a href="<?= site_url('/') ?>"><span class="title"><?= e(site_title()) ?></span></a>
      <div class="tagline"><?= e(site_tagline()) ?></div>
    </div>
    <nav class="vrp-nav" aria-label="Primary navigation">
      <?= render_menu_html('primary') ?>
    </nav>
  </div>
</header>
<main class="front-wrap" id="main-content">
