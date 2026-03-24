<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_financial_exports', 'financial.export', 'financial.export');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    ct2_record_api_log('ct2_financial_exports', $_SERVER['REQUEST_METHOD'] ?? 'GET', 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2ReportRunId = isset($_GET['ct2_report_run_id']) ? (int) $_GET['ct2_report_run_id'] : 0;
if ($ct2ReportRunId < 1) {
    ct2_record_api_log('ct2_financial_exports', 'GET', 422, ['field' => 'ct2_report_run_id']);
    ct2_json_response(false, [], 'Missing report run identifier.', 422);
}

$ct2SourceModule = trim((string) ($_GET['source_module'] ?? ''));
$ct2FinancialAnalyticsModel = new CT2_FinancialAnalyticsModel();
$ct2ExportRows = $ct2FinancialAnalyticsModel->getExportRows(
    $ct2ReportRunId,
    $ct2SourceModule !== '' ? $ct2SourceModule : null
);

$ct2Data = [
    'ct2_report_run_id' => $ct2ReportRunId,
    'source_module' => $ct2SourceModule,
    'row_count' => count($ct2ExportRows),
    'download_url' => ct2_app_url(
        [
            'module' => 'financial',
            'action' => 'exportCsv',
            'ct2_report_run_id' => $ct2ReportRunId,
            'source_module' => $ct2SourceModule,
        ]
    ),
    'rows' => $ct2ExportRows,
];

ct2_record_api_log('ct2_financial_exports', 'GET', 200, ['ct2_report_run_id' => $ct2ReportRunId, 'source_module' => $ct2SourceModule], ['row_count' => count($ct2ExportRows)]);
ct2_json_response(true, $ct2Data, null, 200);
