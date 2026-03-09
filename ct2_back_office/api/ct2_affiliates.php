<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
    ct2_record_api_log('ct2_affiliates', $_SERVER['REQUEST_METHOD'] ?? 'GET', 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

$ct2AffiliateModel = new CT2_AffiliateModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Search = trim((string) ($_GET['search'] ?? ''));
    $ct2Status = trim((string) ($_GET['affiliate_status'] ?? ''));
    $ct2Affiliates = $ct2AffiliateModel->getAll(
        $ct2Search !== '' ? $ct2Search : null,
        $ct2Status !== '' ? $ct2Status : null
    );
    ct2_record_api_log('ct2_affiliates', 'GET', 200, ['search' => $ct2Search, 'affiliate_status' => $ct2Status], ['count' => count($ct2Affiliates)]);
    ct2_json_response(true, ['affiliates' => $ct2Affiliates], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_affiliates', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'affiliate_status' => 'onboarding',
    'commission_rate' => 0,
    'payout_status' => 'pending_setup',
    'external_partner_id' => '',
    'source_system' => '',
];

foreach (['affiliate_code', 'affiliate_name', 'contact_name', 'email', 'phone', 'referral_code'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_affiliates', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2AffiliateId = isset($ct2Payload['ct2_affiliate_id']) ? (int) $ct2Payload['ct2_affiliate_id'] : 0;
if ($ct2AffiliateId > 0) {
    $ct2AffiliateModel->update($ct2AffiliateId, $ct2Payload, (int) ct2_current_user_id());
    $ct2Action = 'marketing.api_affiliate_update';
} else {
    $ct2AffiliateId = $ct2AffiliateModel->create($ct2Payload, (int) ct2_current_user_id());
    $ct2Action = 'marketing.api_affiliate_create';
}

$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'affiliate', $ct2AffiliateId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_affiliates', 'POST', 200, ['ct2_affiliate_id' => $ct2AffiliateId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_affiliate_id' => $ct2AffiliateId], null, 200);
