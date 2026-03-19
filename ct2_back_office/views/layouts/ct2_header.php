<?php

declare(strict_types=1);

$ct2CurrentUser = ct2_current_user();
$ct2SuccessMessage = ct2_flash('success');
$ct2ErrorMessage = ct2_flash('error');
$ct2CurrentModule = (string) ($_GET['module'] ?? 'dashboard');
$ct2CurrentAction = (string) ($_GET['action'] ?? ($ct2CurrentModule === 'auth' ? 'landing' : 'index'));
$ct2BodyClass = 'ct2-body';

if ($ct2CurrentUser === null && $ct2CurrentModule === 'auth') {
    $ct2BodyClass .= ' ct2-body-guest';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(CT2_APP_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="theme-color" content="#12263a">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(ct2_asset_url('icons/ct2-favicon.svg'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(ct2_asset_url('icons/favicon-32x32.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(ct2_asset_url('icons/favicon-16x16.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="shortcut icon" href="<?= htmlspecialchars(ct2_asset_url('icons/favicon.ico'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(ct2_asset_url('icons/apple-touch-icon.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="manifest" href="<?= htmlspecialchars(ct2_asset_url('icons/site.webmanifest'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(ct2_asset_url('css/ct2_styles.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="<?= htmlspecialchars($ct2BodyClass, ENT_QUOTES, 'UTF-8'); ?>">
<div id="ct2-app">
    <header class="ct2-topbar">
        <div class="ct2-topbar-brand">
            <p class="ct2-eyebrow">Travel and Tours ERP</p>
            <h1 class="ct2-title">CORE TRANSACTION 2</h1>
        </div>
        <?php if ($ct2CurrentUser !== null): ?>
            <div class="ct2-userbar">
                <span class="ct2-userbar-status"><?= htmlspecialchars((string) $ct2CurrentUser['display_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'auth', 'action' => 'logout']), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="ct2-btn ct2-btn-secondary" type="submit">Sign Out</button>
                </form>
            </div>
        <?php elseif ($ct2CurrentModule === 'auth' && in_array($ct2CurrentAction, ['landing', 'login'], true)): ?>
            <div class="ct2-userbar ct2-userbar-guest">
                <span class="ct2-userbar-status">Unified operations access for CT2 staff.</span>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($ct2CurrentUser !== null): ?>
        <nav class="ct2-nav" aria-label="Primary">
            <?php
            $ct2NavItems = [
                'dashboard' => 'Dashboard',
                'agents' => 'Agents',
                'suppliers' => 'Suppliers',
                'availability' => 'Availability',
                'marketing' => 'Marketing',
                'financial' => 'Financial',
                'visa' => 'Visa',
                'staff' => 'Staff',
                'approvals' => 'Approvals',
            ];
            ?>
            <?php foreach ($ct2NavItems as $ct2ModuleKey => $ct2Label): ?>
                <?php $ct2NavClass = 'ct2-nav-link' . ($ct2CurrentModule === $ct2ModuleKey ? ' ct2-nav-link-active' : ''); ?>
                <a
                    class="<?= htmlspecialchars($ct2NavClass, ENT_QUOTES, 'UTF-8'); ?>"
                    href="<?= htmlspecialchars(ct2_url(['module' => $ct2ModuleKey, 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>"
                    <?= $ct2CurrentModule === $ct2ModuleKey ? 'aria-current="page"' : ''; ?>
                ><?= htmlspecialchars($ct2Label, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($ct2SuccessMessage !== null): ?>
        <div class="ct2-alert ct2-alert-success"><?= htmlspecialchars($ct2SuccessMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($ct2ErrorMessage !== null): ?>
        <div class="ct2-alert ct2-alert-danger"><?= htmlspecialchars($ct2ErrorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <main class="ct2-main<?= $ct2CurrentUser === null && $ct2CurrentModule === 'auth' ? ' ct2-main-guest' : ''; ?>">
