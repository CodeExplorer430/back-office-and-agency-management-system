<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_visa_checklists', 'visa.view', 'visa.manage');

$ct2VisaChecklistModel = new CT2_VisaChecklistModel();
$ct2DocumentRegistryModel = new CT2_DocumentRegistryModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2VisaApplicationId = isset($_GET['ct2_visa_application_id']) ? (int) $_GET['ct2_visa_application_id'] : 0;
    $ct2Checklist = $ct2VisaChecklistModel->getApplicationChecklist($ct2VisaApplicationId > 0 ? $ct2VisaApplicationId : null);
    ct2_record_api_log('ct2_visa_checklists', 'GET', 200, ['ct2_visa_application_id' => $ct2VisaApplicationId], ['count' => count($ct2Checklist)]);
    ct2_json_response(true, ['checklist' => $ct2Checklist], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_visa_checklists', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'checklist_status' => 'submitted',
    'verification_notes' => '',
    'file_name' => '',
    'file_path' => '',
    'mime_type' => '',
];

if ((int) ($ct2Payload['ct2_application_checklist_id'] ?? 0) < 1 || (int) ($ct2Payload['ct2_visa_application_id'] ?? 0) < 1) {
    ct2_record_api_log('ct2_visa_checklists', 'POST', 422, ['field' => 'ct2_application_checklist_id']);
    ct2_json_response(false, [], 'Missing application or checklist identifier.', 422);
}

$ct2ChecklistUpdatePayload = [
    'checklist_status' => (string) ($ct2Payload['checklist_status'] ?? 'submitted'),
    'verification_notes' => trim((string) ($ct2Payload['verification_notes'] ?? '')),
    'ct2_document_id' => 0,
];

$ct2ResolvedApplicationId = $ct2VisaChecklistModel->findApplicationIdByChecklistId((int) $ct2Payload['ct2_application_checklist_id']);
if ($ct2ResolvedApplicationId === null || $ct2ResolvedApplicationId !== (int) $ct2Payload['ct2_visa_application_id']) {
    ct2_record_api_log('ct2_visa_checklists', 'POST', 422, ['field' => 'ct2_application_checklist_id']);
    ct2_json_response(false, [], 'Checklist item does not match the selected application.', 422);
}

$ct2FileName = trim((string) ($ct2Payload['file_name'] ?? ''));
$ct2FilePath = trim((string) ($ct2Payload['file_path'] ?? ''));
$ct2MimeType = trim((string) ($ct2Payload['mime_type'] ?? ''));
if ($ct2FileName !== '' || $ct2FilePath !== '' || $ct2MimeType !== '') {
    foreach (['file_name' => $ct2FileName, 'file_path' => $ct2FilePath, 'mime_type' => $ct2MimeType] as $ct2FieldKey => $ct2FieldValue) {
        if ($ct2FieldValue === '') {
            ct2_record_api_log('ct2_visa_checklists', 'POST', 422, ['field' => $ct2FieldKey]);
            ct2_json_response(false, [], 'Incomplete document metadata.', 422);
        }
    }

    $ct2ChecklistUpdatePayload['ct2_document_id'] = $ct2DocumentRegistryModel->create(
        [
            'entity_type' => 'visa_application',
            'entity_id' => $ct2ResolvedApplicationId,
            'file_name' => $ct2FileName,
            'file_path' => $ct2FilePath,
            'mime_type' => $ct2MimeType,
        ],
        (int) ct2_current_user_id()
    );
}

$ct2UpdatedApplicationId = $ct2VisaChecklistModel->updateApplicationItem((int) $ct2Payload['ct2_application_checklist_id'], $ct2ChecklistUpdatePayload, (int) ct2_current_user_id());
if ($ct2UpdatedApplicationId === null) {
    ct2_record_api_log('ct2_visa_checklists', 'POST', 404, ['ct2_application_checklist_id' => (int) $ct2Payload['ct2_application_checklist_id']]);
    ct2_json_response(false, [], 'Checklist item not found.', 404);
}

$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_application', $ct2UpdatedApplicationId, 'visa.api_checklist_update', $ct2ChecklistUpdatePayload);
ct2_record_api_log('ct2_visa_checklists', 'POST', 200, ['ct2_visa_application_id' => $ct2UpdatedApplicationId], ['checklist_status' => $ct2ChecklistUpdatePayload['checklist_status']]);
ct2_json_response(true, ['ct2_visa_application_id' => $ct2UpdatedApplicationId], null, 200);
