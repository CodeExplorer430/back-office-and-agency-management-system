<?php

declare(strict_types=1);

$ct2CurrentUser = ct2_current_user();
$ct2SuccessMessage = ct2_flash('success');
$ct2ErrorMessage = ct2_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(CT2_APP_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(ct2_asset_url('css/ct2_styles.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="ct2-body">
<div id="ct2-app">
    <header class="ct2-topbar">
        <div>
            <p class="ct2-eyebrow">Travel and Tours ERP</p>
            <h1 class="ct2-title">CORE TRANSACTION 2</h1>
        </div>
        <?php if ($ct2CurrentUser !== null): ?>
            <div class="ct2-userbar">
                <span><?= htmlspecialchars((string) $ct2CurrentUser['display_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'auth', 'action' => 'logout']), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="ct2-btn ct2-btn-secondary" type="submit">Sign Out</button>
                </form>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($ct2CurrentUser !== null): ?>
        <nav class="ct2-nav">
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'dashboard', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a>
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'agents', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Agents</a>
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Suppliers</a>
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Availability</a>
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Marketing</a>
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Financial</a>
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Visa</a>
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'staff', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Staff</a>
            <a href="<?= htmlspecialchars(ct2_url(['module' => 'approvals', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Approvals</a>
        </nav>
    <?php endif; ?>

    <?php if ($ct2SuccessMessage !== null): ?>
        <div class="ct2-alert ct2-alert-success"><?= htmlspecialchars($ct2SuccessMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($ct2ErrorMessage !== null): ?>
        <div class="ct2-alert ct2-alert-danger"><?= htmlspecialchars($ct2ErrorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <main class="ct2-main">
