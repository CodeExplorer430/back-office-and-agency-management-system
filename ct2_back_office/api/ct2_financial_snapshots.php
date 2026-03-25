<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_financial_snapshots', 'financial.view', 'financial.manage');

$ct2FinancialAnalyticsModel = new CT2_FinancialAnalyticsModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2ReportRunId = isset($_GET['ct2_report_run_id']) ? (int) $_GET['ct2_report_run_id'] : 0;
    $ct2SourceModule = trim((string) ($_GET['source_module'] ?? ''));
    $ct2FlagStatus = trim((string) ($_GET['flag_status'] ?? ''));

    $ct2Data = [
        'runs' => $ct2FinancialAnalyticsModel->getRuns(),
        'snapshots' => $ct2FinancialAnalyticsModel->getSnapshots(
            $ct2ReportRunId > 0 ? $ct2ReportRunId : null,
            $ct2SourceModule !== '' ? $ct2SourceModule : null
        ),
        'flags' => $ct2FinancialAnalyticsModel->getFlags(
            $ct2ReportRunId > 0 ? $ct2ReportRunId : null,
            $ct2FlagStatus !== '' ? $ct2FlagStatus : null,
            $ct2SourceModule !== '' ? $ct2SourceModule : null
        ),
    ];

    ct2_record_api_log(
        'ct2_financial_snapshots',
        'GET',
        200,
        ['ct2_report_run_id' => $ct2ReportRunId, 'source_module' => $ct2SourceModule, 'flag_status' => $ct2FlagStatus],
        ['snapshot_count' => count($ct2Data['snapshots']), 'flag_count' => count($ct2Data['flags'])]
    );
    ct2_json_response(true, $ct2Data, null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_financial_snapshots', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'ct2_reconciliation_flag_id' => 0,
    'flag_status' => 'acknowledged',
    'resolution_notes' => '',
];

if ((int) $ct2Payload['ct2_reconciliation_flag_id'] < 1) {
    ct2_record_api_log('ct2_financial_snapshots', 'POST', 422, ['field' => 'ct2_reconciliation_flag_id']);
    ct2_json_response(false, [], 'Missing reconciliation flag identifier.', 422);
}

if (!in_array((string) $ct2Payload['flag_status'], ['open', 'acknowledged', 'resolved'], true)) {
    ct2_record_api_log('ct2_financial_snapshots', 'POST', 422, ['field' => 'flag_status']);
    ct2_json_response(false, [], 'Invalid flag status.', 422);
}

$ct2FinancialAnalyticsModel->updateFlag((int) $ct2Payload['ct2_reconciliation_flag_id'], $ct2Payload, (int) ct2_current_user_id());
$ct2AuditLogModel->recordAudit(
    (int) ct2_current_user_id(),
    'reconciliation_flag',
    (int) $ct2Payload['ct2_reconciliation_flag_id'],
    'financial.api_flag_update',
    $ct2Payload
);

ct2_record_api_log('ct2_financial_snapshots', 'POST', 200, ['ct2_reconciliation_flag_id' => (int) $ct2Payload['ct2_reconciliation_flag_id']], ['flag_status' => $ct2Payload['flag_status']]);
ct2_json_response(true, ['ct2_reconciliation_flag_id' => (int) $ct2Payload['ct2_reconciliation_flag_id']], null, 200);
