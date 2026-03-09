<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
    ct2_record_api_log('ct2_approvals', $_SERVER['REQUEST_METHOD'] ?? 'GET', 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

$ct2ApprovalModel = new CT2_ApprovalModel();
$ct2AgentModel = new CT2_AgentModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Approvals = $ct2ApprovalModel->getAll();
    ct2_record_api_log('ct2_approvals', 'GET', 200, [], ['count' => count($ct2Approvals)]);
    ct2_json_response(true, ['approvals' => $ct2Approvals], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_approvals', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input();
$ct2WorkflowId = (int) ($ct2Payload['ct2_approval_workflow_id'] ?? 0);
$ct2Status = (string) ($ct2Payload['approval_status'] ?? 'pending');
$ct2DecisionNotes = trim((string) ($ct2Payload['decision_notes'] ?? ''));

if ($ct2WorkflowId < 1) {
    ct2_record_api_log('ct2_approvals', 'POST', 422, ['ct2_approval_workflow_id' => $ct2WorkflowId]);
    ct2_json_response(false, [], 'Approval workflow ID is required.', 422);
}

$ct2Decision = $ct2ApprovalModel->decide($ct2WorkflowId, $ct2Status, $ct2DecisionNotes, (int) ct2_current_user_id());
if ($ct2Decision === null) {
    ct2_record_api_log('ct2_approvals', 'POST', 404, ['ct2_approval_workflow_id' => $ct2WorkflowId]);
    ct2_json_response(false, [], 'Approval request not found.', 404);
}

if ($ct2Decision['subject_type'] === 'agent') {
    $ct2AgentModel->updateApprovalStatus((int) $ct2Decision['subject_id'], $ct2Status, (int) ct2_current_user_id());
}

$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), (string) $ct2Decision['subject_type'], (int) $ct2Decision['subject_id'], 'approvals.api_decide', $ct2Payload);
ct2_record_api_log('ct2_approvals', 'POST', 200, ['ct2_approval_workflow_id' => $ct2WorkflowId], ['approval_status' => $ct2Status]);
ct2_json_response(true, ['workflow' => $ct2Decision], null, 200);
