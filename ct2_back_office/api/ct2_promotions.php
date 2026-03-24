<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_promotions', 'marketing.view', 'marketing.manage');

$ct2PromotionModel = new CT2_PromotionModel();
$ct2ApprovalModel = new CT2_ApprovalModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Search = trim((string) ($_GET['search'] ?? ''));
    $ct2Status = trim((string) ($_GET['promotion_status'] ?? ''));
    $ct2Promotions = $ct2PromotionModel->getAll(
        $ct2Search !== '' ? $ct2Search : null,
        $ct2Status !== '' ? $ct2Status : null
    );
    ct2_record_api_log('ct2_promotions', 'GET', 200, ['search' => $ct2Search, 'promotion_status' => $ct2Status], ['count' => count($ct2Promotions)]);
    ct2_json_response(true, ['promotions' => $ct2Promotions], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_promotions', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'promotion_type' => 'percentage',
    'discount_value' => 0,
    'eligibility_rule' => '',
    'usage_limit' => 1,
    'promotion_status' => 'pending_approval',
    'approval_status' => 'pending',
    'external_booking_scope' => '',
    'source_system' => '',
];

if ((int) ($ct2Payload['ct2_campaign_id'] ?? 0) < 1) {
    ct2_record_api_log('ct2_promotions', 'POST', 422, ['field' => 'ct2_campaign_id']);
    ct2_json_response(false, [], 'Missing field: ct2_campaign_id', 422);
}

foreach (['promotion_code', 'promotion_name', 'valid_from', 'valid_until'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_promotions', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2UserId = (int) ct2_current_user_id();
$ct2PromotionId = isset($ct2Payload['ct2_promotion_id']) ? (int) $ct2Payload['ct2_promotion_id'] : 0;
if ($ct2PromotionId > 0) {
    $ct2PromotionModel->update($ct2PromotionId, $ct2Payload, $ct2UserId);
    $ct2Action = 'marketing.api_promotion_update';
} else {
    $ct2PromotionId = $ct2PromotionModel->create($ct2Payload, $ct2UserId);
    $ct2Action = 'marketing.api_promotion_create';
}

$ct2ApprovalModel->createOrRefreshRequest('promotion', $ct2PromotionId, $ct2UserId);
$ct2AuditLogModel->recordAudit($ct2UserId, 'promotion', $ct2PromotionId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_promotions', 'POST', 200, ['ct2_promotion_id' => $ct2PromotionId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_promotion_id' => $ct2PromotionId], null, 200);
