<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_visa_applications', 'visa.view', 'visa.manage');

$ct2VisaApplicationModel = new CT2_VisaApplicationModel();
$ct2VisaChecklistModel = new CT2_VisaChecklistModel();
$ct2ApprovalModel = new CT2_ApprovalModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Search = trim((string) ($_GET['search'] ?? ''));
    $ct2Status = trim((string) ($_GET['status'] ?? ''));
    $ct2VisaTypeId = isset($_GET['ct2_visa_type_id']) ? (int) $_GET['ct2_visa_type_id'] : 0;
    $ct2Applications = $ct2VisaApplicationModel->getAll(
        $ct2Search !== '' ? $ct2Search : null,
        $ct2Status !== '' ? $ct2Status : null,
        $ct2VisaTypeId > 0 ? $ct2VisaTypeId : null
    );
    ct2_record_api_log('ct2_visa_applications', 'GET', 200, ['search' => $ct2Search, 'status' => $ct2Status], ['count' => count($ct2Applications)]);
    ct2_json_response(true, ['applications' => $ct2Applications], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_visa_applications', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'external_agent_id' => '',
    'source_system' => '',
    'status' => 'submitted',
    'appointment_date' => '',
    'embassy_reference' => '',
    'approval_status' => 'not_required',
    'remarks' => '',
];

if ((int) ($ct2Payload['ct2_visa_type_id'] ?? 0) < 1) {
    ct2_record_api_log('ct2_visa_applications', 'POST', 422, ['field' => 'ct2_visa_type_id']);
    ct2_json_response(false, [], 'Missing field: ct2_visa_type_id', 422);
}

foreach (['application_reference', 'external_customer_id', 'submission_date'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_visa_applications', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2UserId = (int) ct2_current_user_id();
$ct2VisaApplicationId = isset($ct2Payload['ct2_visa_application_id']) ? (int) $ct2Payload['ct2_visa_application_id'] : 0;
if ($ct2VisaApplicationId > 0) {
    $ct2VisaApplicationModel->update($ct2VisaApplicationId, $ct2Payload, $ct2UserId);
    $ct2Action = 'visa.api_application_update';
} else {
    $ct2VisaApplicationId = $ct2VisaApplicationModel->create($ct2Payload, $ct2UserId);
    $ct2Action = 'visa.api_application_create';
}

$ct2VisaChecklistModel->syncChecklistForApplication($ct2VisaApplicationId, (int) $ct2Payload['ct2_visa_type_id']);
if (($ct2Payload['approval_status'] ?? 'not_required') === 'pending' || ($ct2Payload['status'] ?? '') === 'escalated_review') {
    $ct2ApprovalModel->createOrRefreshRequest('visa_application', $ct2VisaApplicationId, $ct2UserId);
}

$ct2AuditLogModel->recordAudit($ct2UserId, 'visa_application', $ct2VisaApplicationId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_visa_applications', 'POST', 200, ['ct2_visa_application_id' => $ct2VisaApplicationId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_visa_application_id' => $ct2VisaApplicationId], null, 200);
