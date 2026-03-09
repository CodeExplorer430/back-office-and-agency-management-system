<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
    ct2_record_api_log('ct2_visa_status', $_SERVER['REQUEST_METHOD'] ?? 'GET', 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

$ct2VisaApplicationModel = new CT2_VisaApplicationModel();
$ct2NotificationLogModel = new CT2_NotificationLogModel();
$ct2ApprovalModel = new CT2_ApprovalModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Data = [
        'summary' => $ct2VisaApplicationModel->getSummaryCounts(),
        'recent_applications' => array_slice($ct2VisaApplicationModel->getAll(), 0, 10),
        'recent_notifications' => array_slice($ct2NotificationLogModel->getAll(), 0, 10),
    ];
    ct2_record_api_log('ct2_visa_status', 'GET', 200, [], ['keys' => array_keys($ct2Data)]);
    ct2_json_response(true, $ct2Data, null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_visa_status', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'appointment_date' => '',
    'embassy_reference' => '',
    'approval_status' => 'not_required',
    'remarks' => '',
];

if ((int) ($ct2Payload['ct2_visa_application_id'] ?? 0) < 1 || trim((string) ($ct2Payload['status'] ?? '')) === '') {
    ct2_record_api_log('ct2_visa_status', 'POST', 422, ['field' => 'ct2_visa_application_id']);
    ct2_json_response(false, [], 'Missing application identifier or status.', 422);
}

$ct2VisaApplicationModel->updateCaseStatus((int) $ct2Payload['ct2_visa_application_id'], $ct2Payload, (int) ct2_current_user_id());
if (($ct2Payload['approval_status'] ?? 'not_required') === 'pending' || ($ct2Payload['status'] ?? '') === 'escalated_review') {
    $ct2ApprovalModel->createOrRefreshRequest('visa_application', (int) $ct2Payload['ct2_visa_application_id'], (int) ct2_current_user_id());
}

$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_application', (int) $ct2Payload['ct2_visa_application_id'], 'visa.api_status_update', $ct2Payload);
ct2_record_api_log('ct2_visa_status', 'POST', 200, ['ct2_visa_application_id' => (int) $ct2Payload['ct2_visa_application_id']], ['status' => $ct2Payload['status']]);
ct2_json_response(true, ['ct2_visa_application_id' => (int) $ct2Payload['ct2_visa_application_id']], null, 200);
