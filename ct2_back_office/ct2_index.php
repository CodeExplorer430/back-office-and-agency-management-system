<?php

declare(strict_types=1);

require_once __DIR__ . '/config/ct2_bootstrap.php';

$ct2Module = (string) ($_GET['module'] ?? (ct2_current_user() === null ? 'auth' : 'dashboard'));
$ct2Action = (string) ($_GET['action'] ?? ($ct2Module === 'auth' ? 'login' : 'index'));

$ct2ControllerMap = [
    'auth' => CT2_AuthController::class,
    'dashboard' => CT2_DashboardController::class,
    'agents' => CT2_AgentController::class,
    'suppliers' => CT2_SupplierController::class,
    'staff' => CT2_StaffController::class,
    'approvals' => CT2_ApprovalController::class,
    'placeholders' => CT2_PlaceholderController::class,
];

if (!isset($ct2ControllerMap[$ct2Module])) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

try {
    $ct2ControllerClass = $ct2ControllerMap[$ct2Module];
    $ct2Controller = new $ct2ControllerClass();

    if (!method_exists($ct2Controller, $ct2Action)) {
        http_response_code(404);
        echo 'Not Found';
        exit;
    }

    $ct2Controller->{$ct2Action}();
} catch (InvalidArgumentException $ct2Exception) {
    ct2_flash('error', $ct2Exception->getMessage());
    $ct2FallbackModule = $ct2Module === 'auth' ? 'auth' : $ct2Module;
    $ct2FallbackAction = $ct2Module === 'auth' ? 'login' : 'index';
    ct2_redirect(['module' => $ct2FallbackModule, 'action' => $ct2FallbackAction]);
} catch (Throwable $ct2Exception) {
    http_response_code(500);
    echo 'CT2 application error: ' . htmlspecialchars($ct2Exception->getMessage(), ENT_QUOTES, 'UTF-8');
}
