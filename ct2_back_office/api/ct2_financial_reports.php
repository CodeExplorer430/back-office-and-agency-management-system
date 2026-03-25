<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_financial_reports', 'financial.view', 'financial.manage');

$ct2FinancialReportModel = new CT2_FinancialReportModel();
$ct2FinancialAnalyticsModel = new CT2_FinancialAnalyticsModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2FinancialReportId = isset($_GET['ct2_financial_report_id']) ? (int) $_GET['ct2_financial_report_id'] : 0;
    $ct2Data = [
        'summary' => $ct2FinancialAnalyticsModel->getSummary(),
        'reports' => $ct2FinancialReportModel->getAll(),
        'filters' => $ct2FinancialReportId > 0 ? $ct2FinancialReportModel->getFilters($ct2FinancialReportId) : [],
    ];

    ct2_record_api_log('ct2_financial_reports', 'GET', 200, ['ct2_financial_report_id' => $ct2FinancialReportId], ['keys' => array_keys($ct2Data)]);
    ct2_json_response(true, $ct2Data, null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_financial_reports', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'report_code' => '',
    'report_name' => '',
    'report_scope' => 'cross_module',
    'report_status' => 'active',
    'default_date_range' => '30d',
    'definition_notes' => '',
];

foreach (['report_code', 'report_name'] as $ct2RequiredField) {
    if (trim((string) $ct2Payload[$ct2RequiredField]) === '') {
        ct2_record_api_log('ct2_financial_reports', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

if (!in_array((string) $ct2Payload['report_scope'], ['agents', 'suppliers', 'availability', 'marketing', 'visa', 'cross_module'], true)) {
    ct2_record_api_log('ct2_financial_reports', 'POST', 422, ['field' => 'report_scope']);
    ct2_json_response(false, [], 'Invalid report scope.', 422);
}

$ct2UserId = (int) ct2_current_user_id();
$ct2FinancialReportId = isset($ct2Payload['ct2_financial_report_id']) ? (int) $ct2Payload['ct2_financial_report_id'] : 0;

if ($ct2FinancialReportId > 0) {
    $ct2FinancialReportModel->update($ct2FinancialReportId, $ct2Payload, $ct2UserId);
    $ct2Action = 'financial.api_report_update';
} else {
    $ct2FinancialReportId = $ct2FinancialReportModel->create($ct2Payload, $ct2UserId);
    $ct2Action = 'financial.api_report_create';
}

$ct2AuditLogModel->recordAudit($ct2UserId, 'financial_report', $ct2FinancialReportId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_financial_reports', 'POST', 200, ['ct2_financial_report_id' => $ct2FinancialReportId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_financial_report_id' => $ct2FinancialReportId], null, 200);
