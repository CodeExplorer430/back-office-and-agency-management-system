<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
    ct2_record_api_log('ct2_agents', $_SERVER['REQUEST_METHOD'] ?? 'GET', 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

$ct2AgentModel = new CT2_AgentModel();
$ct2ApprovalModel = new CT2_ApprovalModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Search = trim((string) ($_GET['search'] ?? ''));
    $ct2Agents = $ct2AgentModel->getAll($ct2Search !== '' ? $ct2Search : null);
    ct2_record_api_log('ct2_agents', 'GET', 200, ['search' => $ct2Search], ['count' => count($ct2Agents)]);
    ct2_json_response(true, ['agents' => $ct2Agents], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_agents', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input();
$ct2UserId = (int) ct2_current_user_id();

foreach (['agent_code', 'agency_name', 'contact_person', 'email', 'phone', 'region'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_agents', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2Payload += [
    'commission_rate' => '0.00',
    'support_level' => 'standard',
    'approval_status' => 'pending',
    'active_status' => 'active',
    'external_booking_id' => '',
    'external_customer_id' => '',
    'external_payment_id' => '',
    'source_system' => '',
];

$ct2AgentId = isset($ct2Payload['ct2_agent_id']) ? (int) $ct2Payload['ct2_agent_id'] : 0;
if ($ct2AgentId > 0) {
    $ct2AgentModel->update($ct2AgentId, $ct2Payload, $ct2UserId);
    $ct2Action = 'agents.api_update';
} else {
    $ct2AgentId = $ct2AgentModel->create($ct2Payload, $ct2UserId);
    $ct2Action = 'agents.api_create';
}

$ct2ApprovalModel->createOrRefreshRequest('agent', $ct2AgentId, $ct2UserId);
$ct2AuditLogModel->recordAudit($ct2UserId, 'agent', $ct2AgentId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_agents', 'POST', 200, ['agent_id' => $ct2AgentId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_agent_id' => $ct2AgentId], null, 200);
