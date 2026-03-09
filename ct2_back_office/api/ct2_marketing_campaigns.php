<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
    ct2_record_api_log('ct2_marketing_campaigns', $_SERVER['REQUEST_METHOD'] ?? 'GET', 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

$ct2CampaignModel = new CT2_MarketingCampaignModel();
$ct2ApprovalModel = new CT2_ApprovalModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Search = trim((string) ($_GET['search'] ?? ''));
    $ct2Status = trim((string) ($_GET['campaign_status'] ?? ''));
    $ct2Channel = trim((string) ($_GET['channel_type'] ?? ''));
    $ct2Campaigns = $ct2CampaignModel->getAll(
        $ct2Search !== '' ? $ct2Search : null,
        $ct2Status !== '' ? $ct2Status : null,
        $ct2Channel !== '' ? $ct2Channel : null
    );
    ct2_record_api_log('ct2_marketing_campaigns', 'GET', 200, ['search' => $ct2Search, 'campaign_status' => $ct2Status], ['count' => count($ct2Campaigns)]);
    ct2_json_response(true, ['campaigns' => $ct2Campaigns], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_marketing_campaigns', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'campaign_type' => 'other',
    'channel_type' => 'hybrid',
    'budget_amount' => 0,
    'status' => 'pending_approval',
    'approval_status' => 'pending',
    'target_audience' => '',
    'external_customer_segment_id' => '',
    'source_system' => '',
];

foreach (['campaign_code', 'campaign_name', 'start_date', 'end_date'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_marketing_campaigns', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2UserId = (int) ct2_current_user_id();
$ct2CampaignId = isset($ct2Payload['ct2_campaign_id']) ? (int) $ct2Payload['ct2_campaign_id'] : 0;
if ($ct2CampaignId > 0) {
    $ct2CampaignModel->update($ct2CampaignId, $ct2Payload, $ct2UserId);
    $ct2Action = 'marketing.api_campaign_update';
} else {
    $ct2CampaignId = $ct2CampaignModel->create($ct2Payload, $ct2UserId);
    $ct2Action = 'marketing.api_campaign_create';
}

$ct2ApprovalModel->createOrRefreshRequest('campaign', $ct2CampaignId, $ct2UserId);
$ct2AuditLogModel->recordAudit($ct2UserId, 'campaign', $ct2CampaignId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_marketing_campaigns', 'POST', 200, ['ct2_campaign_id' => $ct2CampaignId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_campaign_id' => $ct2CampaignId], null, 200);
