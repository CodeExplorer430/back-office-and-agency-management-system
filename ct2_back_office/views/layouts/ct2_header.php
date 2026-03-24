<?php

declare(strict_types=1);

$ct2CurrentUser = ct2_current_user();
$ct2SuccessMessage = ct2_flash('success');
$ct2ErrorMessage = ct2_flash('error');
$ct2CurrentModule = (string) ($_GET['module'] ?? 'dashboard');
$ct2CurrentAction = (string) ($_GET['action'] ?? ($ct2CurrentModule === 'auth' ? 'login' : 'index'));
$ct2BodyClass = 'ct2-body';
$ct2ToastQueue = [];

if ($ct2CurrentUser === null && $ct2CurrentModule === 'auth') {
    $ct2BodyClass .= ' ct2-body-guest';
} elseif ($ct2CurrentUser !== null) {
    $ct2BodyClass .= ' ct2-body-auth';
}

if ($ct2SuccessMessage !== null) {
    $ct2ToastQueue[] = [
        'type' => 'success',
        'title' => 'Success',
        'message' => $ct2SuccessMessage,
        'delay' => 4000,
    ];
}

if ($ct2ErrorMessage !== null) {
    $ct2ToastQueue[] = [
        'type' => 'error',
        'title' => 'Error',
        'message' => $ct2ErrorMessage,
        'delay' => 6000,
    ];
}

$ct2NavItems = [
    'dashboard' => ['label' => 'Dashboard', 'description' => 'Overview and analytics', 'icon' => 'dashboard'],
    'agents' => ['label' => 'Agents', 'description' => 'Bookings and commissions', 'icon' => 'users'],
    'suppliers' => ['label' => 'Suppliers', 'description' => 'Partners and onboarding', 'icon' => 'building-store'],
    'availability' => ['label' => 'Availability', 'description' => 'Resources and dispatch', 'icon' => 'calendar-event'],
    'marketing' => ['label' => 'Marketing', 'description' => 'Campaign visibility', 'icon' => 'megaphone'],
    'financial' => ['label' => 'Financial', 'description' => 'Reports and settlement', 'icon' => 'chart-donut'],
    'visa' => ['label' => 'Visa', 'description' => 'Documents and cases', 'icon' => 'passport'],
    'staff' => ['label' => 'Staff', 'description' => 'Workforce readiness', 'icon' => 'briefcase'],
    'approvals' => ['label' => 'Approvals', 'description' => 'Governance queue', 'icon' => 'shield-check'],
];
$ct2CurrentNavItem = $ct2NavItems[$ct2CurrentModule] ?? ['label' => CT2_APP_NAME, 'description' => 'Back-office workspace', 'icon' => 'layout-grid'];
$ct2RenderSidebarIcon = static function (string $ct2Icon): string {
    return match ($ct2Icon) {
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a3 3 0 1 0 0-6a3 3 0 0 0 0 6zm8 1a2.5 2.5 0 1 0 0-5a2.5 2.5 0 0 0 0 5zM4.5 19a4.5 4.5 0 0 1 9 0M14.5 18a3.5 3.5 0 0 1 5 0"/></svg>',
        'building-store' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 10v9h14v-9M4 10l1.5-5h13L20 10M9 14h6M8 19v-5h8v5"/></svg>',
        'calendar-event' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1zm7 6l1.5 3H17l-2.75 2 1 3L12 17l-3.25 2 1-3L7 14h3.5z"/></svg>',
        'megaphone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 14v-4l10-4v12L5 14zm0 0v4a2 2 0 0 0 2 2h1l1-5M15 8h2a3 3 0 0 1 0 6h-2"/></svg>',
        'chart-donut' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9h-6V3zm2 0.5A9 9 0 0 1 20.5 10H14V3.5zM12 8a4 4 0 1 0 4 4h-4V8z"/></svg>',
        'passport' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h9a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7zm0 0v16M12.5 9a2.5 2.5 0 1 0 0 5a2.5 2.5 0 0 0 0-5zm-4 8c1.2-1.6 3-2.5 5-2.5s3.8.9 5 2.5"/></svg>',
        'briefcase' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7V5h6v2M4 9h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm0 0V8a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v1M4 13h16"/></svg>',
        'shield-check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-2.8 7.9-7 10c-4.2-2.1-7-5.5-7-10V6zm-3 9l2 2l4-4"/></svg>',
        default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg>',
    };
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(CT2_APP_NAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="theme-color" content="#8fe07f">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(ct2_asset_url('icons/ct2-favicon.svg'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(ct2_asset_url('icons/favicon-32x32.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars(ct2_asset_url('icons/favicon-16x16.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="shortcut icon" href="<?= htmlspecialchars(ct2_asset_url('icons/favicon.ico'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(ct2_asset_url('icons/apple-touch-icon.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="manifest" href="<?= htmlspecialchars(ct2_asset_url('icons/site.webmanifest'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(ct2_asset_url('vendor/bootstrap/css/bootstrap.min.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(ct2_asset_url('vendor/tabler/css/tabler.min.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(ct2_asset_url('vendor/animate/animate.min.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(ct2_asset_url('css/ct2_styles.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($ct2CurrentUser !== null): ?>
        <script>
            (() => {
                try {
                    if (window.localStorage.getItem('ct2SidebarState') === 'collapsed') {
                        document.documentElement.setAttribute('data-ct2-sidebar-state', 'collapsed');
                    }
                } catch (error) {
                }
            })();
        </script>
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($ct2BodyClass, ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($ct2CurrentUser !== null): ?>
<aside class="navbar navbar-vertical navbar-expand-lg ct2-tabler-sidebar animate__animated animate__fadeInLeft animate__faster" data-bs-theme="light" data-ct2-sidebar>
    <div class="container-fluid">
        <div class="ct2-sidebar-account">
            <span class="badge bg-success-lt text-success-emphasis ct2-user-pill"><?= htmlspecialchars((string) $ct2CurrentUser['display_name'], ENT_QUOTES, 'UTF-8'); ?></span>
            <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'auth', 'action' => 'logout']), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <button class="btn btn-outline-success w-100 ct2-sidebar-signout" type="submit">
                    <span class="ct2-sidebar-signout-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M14 7l5 5l-5 5M19 12H9M12 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6"/>
                        </svg>
                    </span>
                    <span class="ct2-sidebar-signout-label">Sign Out</span>
                </button>
            </form>
        </div>
        <div class="ct2-sidebar-topbar">
            <h1 class="navbar-brand navbar-brand-autodark">
                <div class="ct2-brand-link">
                    <span class="ct2-brand-mark" aria-hidden="true">
                        <img
                            class="ct2-brand-mark-icon"
                            src="<?= htmlspecialchars(ct2_asset_url('icons/ct2-favicon.svg'), ENT_QUOTES, 'UTF-8'); ?>"
                            alt=""
                        >
                    </span>
                    <span class="ct2-brand-copy">
                        <small>Travel ERP</small>
                        <strong>Core Transaction 2</strong>
                    </span>
                </div>
            </h1>
            <button
                class="btn btn-icon btn-outline-success ct2-sidebar-toggle"
                type="button"
                data-ct2-sidebar-toggle
                aria-controls="ct2-sidebar-menu"
                aria-expanded="true"
                aria-label="Collapse sidebar"
                title="Collapse sidebar"
            >
                <span class="ct2-sidebar-toggle-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M15 6l-6 6l6 6"/>
                    </svg>
                </span>
            </button>
        </div>
        <div class="navbar-collapse ct2-sidebar-menu" id="ct2-sidebar-menu">
            <div class="w-100">
                <div class="text-uppercase text-secondary fw-bold small mb-2 ct2-sidebar-section-label">Modules</div>
                <ul class="navbar-nav pt-lg-2">
                    <?php foreach ($ct2NavItems as $ct2ModuleKey => $ct2NavItem): ?>
                        <?php $ct2NavClass = 'nav-link' . ($ct2CurrentModule === $ct2ModuleKey ? ' active' : ''); ?>
                        <li class="nav-item">
                            <a
                                class="<?= htmlspecialchars($ct2NavClass, ENT_QUOTES, 'UTF-8'); ?>"
                                href="<?= htmlspecialchars(ct2_url(['module' => $ct2ModuleKey, 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>"
                                title="<?= htmlspecialchars((string) $ct2NavItem['description'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?= $ct2CurrentModule === $ct2ModuleKey ? 'aria-current="page"' : ''; ?>
                            >
                                <span class="nav-link-icon ct2-nav-icon" aria-hidden="true">
                                    <?= $ct2RenderSidebarIcon((string) $ct2NavItem['icon']); ?>
                                </span>
                                <span class="ct2-nav-copy">
                                    <span class="nav-link-title"><?= htmlspecialchars((string) $ct2NavItem['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="ct2-nav-desc"><?= htmlspecialchars((string) $ct2NavItem['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card mt-4 ct2-sidebar-summary">
                <div class="card-body">
                    <div class="text-uppercase text-secondary fw-bold small mb-2">Primary Intake</div>
                    <div class="h3 mb-2">CT1 feeds CT2 daily operations.</div>
                    <p class="text-secondary mb-0">Bookings, payments, and sold inventory flow into staffing, finance, dispatch, and compliance from one back-office hub.</p>
                </div>
            </div>
        </div>
    </div>
</aside>

<div class="page ct2-app-shell">
    <div class="page-wrapper">
        <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none ct2-app-header animate__animated animate__fadeInDown animate__faster">
            <div class="container-fluid ct2-page-container">
                <div class="row align-items-center w-100 g-3">
                    <div class="col">
                        <div class="page-pretitle">Active Workspace</div>
                        <h2 class="page-title"><?= htmlspecialchars((string) $ct2CurrentNavItem['label'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="text-secondary"><?= htmlspecialchars((string) $ct2CurrentNavItem['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            </div>
        </header>
<?php else: ?>
<div id="ct2-app">
    <header class="ct2-topbar animate__animated animate__fadeInDown animate__faster">
        <div class="ct2-topbar-brand">
            <p class="ct2-eyebrow">Travel and Tours ERP</p>
            <h1 class="ct2-title">CORE TRANSACTION 2</h1>
        </div>
        <?php if ($ct2CurrentModule === 'auth' && in_array($ct2CurrentAction, ['landing', 'login'], true)): ?>
            <div class="ct2-userbar ct2-userbar-guest">
                <span class="ct2-userbar-status">Back-office access for CT2 staff and department leads.</span>
            </div>
        <?php endif; ?>
    </header>
<?php endif; ?>

    <?php if ($ct2CurrentUser !== null): ?>
        <main class="page-body">
            <div class="container-fluid ct2-page-container ct2-page-content animate__animated animate__fadeInUp animate__fast">
    <?php else: ?>
        <main class="ct2-main<?= $ct2CurrentUser === null && $ct2CurrentModule === 'auth' ? ' ct2-main-guest' : ''; ?> animate__animated animate__fadeInUp animate__fast">
    <?php endif; ?>
