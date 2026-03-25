<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_suppliers', 'suppliers.view', 'suppliers.manage');

$ct2SupplierModel = new CT2_SupplierModel();
$ct2ApprovalModel = new CT2_ApprovalModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Search = trim((string) ($_GET['search'] ?? ''));
    $ct2Suppliers = $ct2SupplierModel->getAll($ct2Search !== '' ? $ct2Search : null);
    ct2_record_api_log('ct2_suppliers', 'GET', 200, ['search' => $ct2Search], ['count' => count($ct2Suppliers)]);
    ct2_json_response(true, ['suppliers' => $ct2Suppliers], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_suppliers', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'supplier_type' => 'supplier',
    'contact_role_title' => 'Account Manager',
    'support_tier' => 'standard',
    'approval_status' => 'pending',
    'onboarding_status' => 'draft',
    'active_status' => 'active',
    'risk_level' => 'low',
    'internal_owner_user_id' => 0,
    'external_supplier_id' => '',
    'source_system' => '',
];

foreach (['supplier_code', 'supplier_name', 'primary_contact_name', 'email', 'phone', 'service_category'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_suppliers', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2UserId = (int) ct2_current_user_id();
$ct2SupplierId = isset($ct2Payload['ct2_supplier_id']) ? (int) $ct2Payload['ct2_supplier_id'] : 0;
if ($ct2SupplierId > 0) {
    $ct2SupplierModel->update($ct2SupplierId, $ct2Payload, $ct2UserId);
    $ct2Action = 'suppliers.api_update';
} else {
    $ct2SupplierId = $ct2SupplierModel->create($ct2Payload, $ct2UserId);
    $ct2Action = 'suppliers.api_create';
}

$ct2ApprovalModel->createOrRefreshRequest('supplier', $ct2SupplierId, $ct2UserId);
$ct2AuditLogModel->recordAudit($ct2UserId, 'supplier', $ct2SupplierId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_suppliers', 'POST', 200, ['supplier_id' => $ct2SupplierId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_supplier_id' => $ct2SupplierId], null, 200);
