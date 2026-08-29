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
<div class="front-wrap">
  <header class="vrp-header">
    <div class="vrp-brand">
      <a href="<?= site_url('/') ?>"><span class="title"><?= e(site_title()) ?></span></a>
      <div class="tagline"><?= e(site_tagline()) ?></div>
    </div>
    <nav>
      <?= render_menu_html('primary') ?>
    </nav>
  </header>
