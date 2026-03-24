<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_supplier_contracts', 'suppliers.view', 'suppliers.manage');

$ct2SupplierContractModel = new CT2_SupplierContractModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Contracts = $ct2SupplierContractModel->getAll();
    ct2_record_api_log('ct2_supplier_contracts', 'GET', 200, [], ['count' => count($ct2Contracts)]);
    ct2_json_response(true, ['contracts' => $ct2Contracts], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_supplier_contracts', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'renewal_status' => 'not_started',
    'contract_status' => 'draft',
    'clause_summary' => '',
    'mock_signature_status' => 'pending',
    'finance_handoff_status' => 'not_started',
];

if ((int) ($ct2Payload['ct2_supplier_id'] ?? 0) < 1) {
    ct2_record_api_log('ct2_supplier_contracts', 'POST', 422, ['field' => 'ct2_supplier_id']);
    ct2_json_response(false, [], 'Missing field: ct2_supplier_id', 422);
}

foreach (['contract_code', 'contract_title', 'effective_date', 'expiry_date'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_supplier_contracts', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2ContractId = $ct2SupplierContractModel->create($ct2Payload, (int) ct2_current_user_id());
$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'supplier_contract', $ct2ContractId, 'suppliers.api_contract_create', $ct2Payload);
ct2_record_api_log('ct2_supplier_contracts', 'POST', 200, ['contract_id' => $ct2ContractId], ['status' => 'created']);
ct2_json_response(true, ['ct2_supplier_contract_id' => $ct2ContractId], null, 200);
