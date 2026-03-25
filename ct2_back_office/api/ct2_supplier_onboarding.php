<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_supplier_onboarding', 'suppliers.view', 'suppliers.manage');

$ct2SupplierOnboardingModel = new CT2_SupplierOnboardingModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Records = $ct2SupplierOnboardingModel->getAll();
    ct2_record_api_log('ct2_supplier_onboarding', 'GET', 200, [], ['count' => count($ct2Records)]);
    ct2_json_response(true, ['onboarding' => $ct2Records], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_supplier_onboarding', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input();
if ((int) ($ct2Payload['ct2_supplier_id'] ?? 0) < 1) {
    ct2_record_api_log('ct2_supplier_onboarding', 'POST', 422, ['ct2_supplier_id' => 0]);
    ct2_json_response(false, [], 'Supplier ID is required.', 422);
}

$ct2Payload += [
    'checklist_status' => 'not_started',
    'documents_status' => 'missing',
    'compliance_status' => 'pending',
    'review_notes' => '',
    'blocked_reason' => '',
    'target_go_live_date' => '',
    'completed_at' => '',
];

$ct2SupplierOnboardingModel->upsert($ct2Payload, (int) ct2_current_user_id());
$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'supplier', (int) $ct2Payload['ct2_supplier_id'], 'suppliers.api_onboarding_update', $ct2Payload);
ct2_record_api_log('ct2_supplier_onboarding', 'POST', 200, ['supplier_id' => $ct2Payload['ct2_supplier_id']], ['status' => 'updated']);
ct2_json_response(true, ['ct2_supplier_id' => (int) $ct2Payload['ct2_supplier_id']], null, 200);
