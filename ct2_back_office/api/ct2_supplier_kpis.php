<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_supplier_kpis', 'suppliers.view', 'suppliers.manage');

$ct2SupplierKpiModel = new CT2_SupplierKpiModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Kpis = $ct2SupplierKpiModel->getAll();
    ct2_record_api_log('ct2_supplier_kpis', 'GET', 200, [], ['count' => count($ct2Kpis)]);
    ct2_json_response(true, ['kpis' => $ct2Kpis], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_supplier_kpis', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'risk_flag' => 'none',
    'notes' => '',
];

if ((int) ($ct2Payload['ct2_supplier_id'] ?? 0) < 1 || trim((string) ($ct2Payload['measurement_date'] ?? '')) === '') {
    ct2_record_api_log('ct2_supplier_kpis', 'POST', 422, ['supplier_id' => $ct2Payload['ct2_supplier_id'] ?? 0]);
    ct2_json_response(false, [], 'Supplier ID and measurement date are required.', 422);
}

foreach (['service_score', 'delivery_score', 'compliance_score', 'responsiveness_score'] as $ct2RequiredField) {
    if (!isset($ct2Payload[$ct2RequiredField])) {
        ct2_record_api_log('ct2_supplier_kpis', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2KpiId = $ct2SupplierKpiModel->create($ct2Payload, (int) ct2_current_user_id());
$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'supplier_kpi', $ct2KpiId, 'suppliers.api_kpi_create', $ct2Payload);
ct2_record_api_log('ct2_supplier_kpis', 'POST', 200, ['kpi_id' => $ct2KpiId], ['status' => 'created']);
ct2_json_response(true, ['ct2_supplier_kpi_id' => $ct2KpiId], null, 200);
