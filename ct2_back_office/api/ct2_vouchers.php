<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
    ct2_record_api_log('ct2_vouchers', $_SERVER['REQUEST_METHOD'] ?? 'GET', 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

$ct2VoucherModel = new CT2_VoucherModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Status = trim((string) ($_GET['voucher_status'] ?? ''));
    $ct2Vouchers = $ct2VoucherModel->getAll($ct2Status !== '' ? $ct2Status : null);
    ct2_record_api_log('ct2_vouchers', 'GET', 200, ['voucher_status' => $ct2Status], ['count' => count($ct2Vouchers)]);
    ct2_json_response(true, ['vouchers' => $ct2Vouchers], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_vouchers', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'ct2_promotion_id' => 0,
    'customer_scope' => 'single_use',
    'max_redemptions' => 1,
    'voucher_status' => 'issued',
    'external_customer_id' => '',
    'source_system' => '',
];

foreach (['voucher_code', 'voucher_name', 'valid_from', 'valid_until'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_vouchers', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2VoucherId = isset($ct2Payload['ct2_voucher_id']) ? (int) $ct2Payload['ct2_voucher_id'] : 0;
if ($ct2VoucherId > 0) {
    $ct2VoucherModel->update($ct2VoucherId, $ct2Payload);
    $ct2Action = 'marketing.api_voucher_update';
} else {
    $ct2VoucherId = $ct2VoucherModel->create($ct2Payload, (int) ct2_current_user_id());
    $ct2Action = 'marketing.api_voucher_create';
}

$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'voucher', $ct2VoucherId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_vouchers', 'POST', 200, ['ct2_voucher_id' => $ct2VoucherId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_voucher_id' => $ct2VoucherId], null, 200);
