<?php
require_once __DIR__ . '/functions.php';
$flash = get_flash();
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(cms_name()) ?> Admin — <?= e(site_title()) ?></title>
    <link rel="stylesheet" href="<?= site_url('/assets/css/admin.css') ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <img class="brand-logo" src="<?= cms_logo_url() ?>" alt="<?= e(cms_name()) ?>">
            <div class="brand-text">
                <div class="brand-name"><?= e(cms_name()) ?></div>
                <div class="brand-sub"><?= e(cms_tagline()) ?></div>
            </div>
        </div>
        <nav class="nav">
            <a href="<?= admin_url('/dashboard.php') ?>" class="<?= str_contains($currentPath, '/admin/dashboard.php') ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= admin_url('/pages/list.php') ?>" class="<?= str_contains($currentPath, '/pages/') ? 'active' : '' ?>">Pages</a>
            <a href="<?= admin_url('/posts/list.php') ?>" class="<?= str_contains($currentPath, '/posts/') ? 'active' : '' ?>">Posts</a>
            <a href="<?= admin_url('/categories/list.php') ?>" class="<?= str_contains($currentPath, '/categories/') ? 'active' : '' ?>">Categories</a>
            <a href="<?= admin_url('/media/index.php') ?>" class="<?= str_contains($currentPath, '/media/') ? 'active' : '' ?>">Media</a>
            <a href="<?= admin_url('/menus/index.php') ?>" class="<?= str_contains($currentPath, '/menus/') ? 'active' : '' ?>">Menus</a>
            <a href="<?= admin_url('/comments/list.php') ?>" class="<?= str_contains($currentPath, '/comments/') ? 'active' : '' ?>">Comments</a>
            <a href="<?= admin_url('/settings/general.php') ?>" class="<?= str_contains($currentPath, '/settings/general.php') ? 'active' : '' ?>">General Settings</a>
            <a href="<?= admin_url('/settings/change_password.php') ?>" class="<?= str_contains($currentPath, '/settings/change_password.php') ? 'active' : '' ?>">Change Password</a>
            <a href="<?= admin_url('/about.php') ?>" class="<?= str_contains($currentPath, '/admin/about.php') ? 'active' : '' ?>">About VRAPress</a>
            <a href="<?= site_url('/') ?>" target="_blank">View Website</a>
            <a href="<?= admin_url('/logout.php') ?>">Logout</a>
        </nav>
    </aside>
    <main class="content">
        <div class="topbar">
            <div>
                <strong><?= e(site_title()) ?></strong><br>
                <span class="muted small"><?= e(site_tagline()) ?></span>
            </div>
            <div class="small muted">Logged in as: <?= e(current_admin()['name'] ?? 'Guest') ?></div>
        </div>
        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
