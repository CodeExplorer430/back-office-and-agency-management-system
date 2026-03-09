<?php

declare(strict_types=1);

final class CT2_FinancialController extends CT2_BaseController
{
    private CT2_FinancialReportModel $ct2FinancialReportModel;
    private CT2_FinancialAnalyticsModel $ct2FinancialAnalyticsModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2FinancialReportModel = new CT2_FinancialReportModel();
        $this->ct2FinancialAnalyticsModel = new CT2_FinancialAnalyticsModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function index(): void
    {
        ct2_require_permission('financial.view');

        $ct2ReportEditId = isset($_GET['report_edit_id']) ? (int) $_GET['report_edit_id'] : 0;
        $ct2ReportRunId = isset($_GET['ct2_report_run_id']) ? (int) $_GET['ct2_report_run_id'] : 0;
        $ct2SelectedReportId = isset($_GET['ct2_financial_report_id']) ? (int) $_GET['ct2_financial_report_id'] : 0;
        $ct2SourceModule = trim((string) ($_GET['source_module'] ?? ''));
        $ct2FlagStatus = trim((string) ($_GET['flag_status'] ?? ''));

        $ct2ReportSelection = $this->ct2FinancialReportModel->getAllForSelection();
        if ($ct2SelectedReportId < 1 && $ct2ReportSelection !== []) {
            $ct2SelectedReportId = (int) $ct2ReportSelection[0]['ct2_financial_report_id'];
        }

        $this->ct2Render(
            'financial/ct2_index',
            [
                'ct2FinancialReports' => $this->ct2FinancialReportModel->getAll(),
                'ct2FinancialRuns' => $this->ct2FinancialAnalyticsModel->getRuns(),
                'ct2FinancialSnapshots' => $this->ct2FinancialAnalyticsModel->getSnapshots(
                    $ct2ReportRunId > 0 ? $ct2ReportRunId : null,
                    $ct2SourceModule !== '' ? $ct2SourceModule : null
                ),
                'ct2ReconciliationFlags' => $this->ct2FinancialAnalyticsModel->getFlags(
                    $ct2ReportRunId > 0 ? $ct2ReportRunId : null,
                    $ct2FlagStatus !== '' ? $ct2FlagStatus : null,
                    $ct2SourceModule !== '' ? $ct2SourceModule : null
                ),
                'ct2FinancialSummary' => $this->ct2FinancialAnalyticsModel->getSummary(),
                'ct2ReportSelection' => $ct2ReportSelection,
                'ct2SelectedReportFilters' => $ct2SelectedReportId > 0
                    ? $this->ct2FinancialReportModel->getFilters($ct2SelectedReportId)
                    : [],
                'ct2ReportForEdit' => $ct2ReportEditId > 0 ? $this->ct2FinancialReportModel->findById($ct2ReportEditId) : null,
                'ct2SelectedReportId' => $ct2SelectedReportId,
                'ct2ReportRunId' => $ct2ReportRunId,
                'ct2SourceModule' => $ct2SourceModule,
                'ct2FlagStatus' => $ct2FlagStatus,
            ]
        );
    }

    public function saveReport(): void
    {
        ct2_require_permission('financial.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = $this->ct2ValidateReportPayload($_POST);
        $ct2UserId = (int) ct2_current_user_id();
        $ct2FinancialReportId = isset($_POST['ct2_financial_report_id']) ? (int) $_POST['ct2_financial_report_id'] : 0;

        if ($ct2FinancialReportId > 0) {
            $this->ct2FinancialReportModel->update($ct2FinancialReportId, $ct2Payload, $ct2UserId);
            $ct2Action = 'financial.report_update';
        } else {
            $ct2FinancialReportId = $this->ct2FinancialReportModel->create($ct2Payload, $ct2UserId);
            $ct2Action = 'financial.report_create';
        }

        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'financial_report', $ct2FinancialReportId, $ct2Action, $ct2Payload);
        ct2_flash('success', 'Financial report definition saved successfully.');
        $this->ct2Redirect(['module' => 'financial', 'action' => 'index', 'report_edit_id' => $ct2FinancialReportId, 'ct2_financial_report_id' => $ct2FinancialReportId]);
    }

    public function saveFilter(): void
    {
        ct2_require_permission('financial.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = $this->ct2ValidateFilterPayload($_POST);
        $ct2FilterId = $this->ct2FinancialReportModel->createFilter($ct2Payload);
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'financial_report_filter', $ct2FilterId, 'financial.filter_create', $ct2Payload);

        ct2_flash('success', 'Report filter saved successfully.');
        $this->ct2Redirect(['module' => 'financial', 'action' => 'index', 'ct2_financial_report_id' => (int) $ct2Payload['ct2_financial_report_id']]);
    }

    public function runReport(): void
    {
        ct2_require_permission('financial.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = $this->ct2ValidateRunPayload($_POST);
        $ct2UserId = (int) ct2_current_user_id();
        $ct2ReportRunId = $this->ct2FinancialAnalyticsModel->createRun($ct2Payload, $ct2UserId);
        $ct2Summary = $this->ct2FinancialAnalyticsModel->generateRun($ct2ReportRunId, $ct2Payload);

        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'financial_report_run', $ct2ReportRunId, 'financial.run_generate', $ct2Payload + $ct2Summary);
        ct2_flash(
            'success',
            sprintf(
                'Financial report run generated with %d snapshots and %d reconciliation flags.',
                (int) ($ct2Summary['snapshot_count'] ?? 0),
                (int) ($ct2Summary['flag_count'] ?? 0)
            )
        );

        $this->ct2Redirect(['module' => 'financial', 'action' => 'index', 'ct2_report_run_id' => $ct2ReportRunId, 'ct2_financial_report_id' => (int) $ct2Payload['ct2_financial_report_id']]);
    }

    public function resolveFlag(): void
    {
        ct2_require_permission('financial.manage');
        $this->assertPostWithCsrf();

        $ct2ReconciliationFlagId = (int) ($_POST['ct2_reconciliation_flag_id'] ?? 0);
        if ($ct2ReconciliationFlagId < 1) {
            throw new InvalidArgumentException('Missing reconciliation flag identifier.');
        }

        $ct2FlagStatus = trim((string) ($_POST['flag_status'] ?? ''));
        if (!in_array($ct2FlagStatus, ['open', 'acknowledged', 'resolved'], true)) {
            throw new InvalidArgumentException('Invalid reconciliation flag status.');
        }

        $ct2Payload = [
            'flag_status' => $ct2FlagStatus,
            'resolution_notes' => trim((string) ($_POST['resolution_notes'] ?? '')),
        ];

        $this->ct2FinancialAnalyticsModel->updateFlag($ct2ReconciliationFlagId, $ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'reconciliation_flag', $ct2ReconciliationFlagId, 'financial.flag_update', $ct2Payload);

        ct2_flash('success', 'Reconciliation flag updated successfully.');
        $this->ct2Redirect(
            [
                'module' => 'financial',
                'action' => 'index',
                'ct2_report_run_id' => (int) ($_POST['ct2_report_run_id'] ?? 0),
                'source_module' => trim((string) ($_POST['source_module'] ?? '')),
                'flag_status' => trim((string) ($_POST['flag_filter_status'] ?? '')),
                'ct2_financial_report_id' => (int) ($_POST['ct2_financial_report_id'] ?? 0),
            ]
        );
    }

    public function exportCsv(): void
    {
        ct2_require_permission('financial.export');

        $ct2ReportRunId = isset($_GET['ct2_report_run_id']) ? (int) $_GET['ct2_report_run_id'] : 0;
        if ($ct2ReportRunId < 1) {
            throw new InvalidArgumentException('Missing report run identifier for export.');
        }

        $ct2SourceModule = trim((string) ($_GET['source_module'] ?? ''));
        $ct2ExportRows = $this->ct2FinancialAnalyticsModel->getExportRows(
            $ct2ReportRunId,
            $ct2SourceModule !== '' ? $ct2SourceModule : null
        );

        $ct2FileName = 'ct2_financial_export_run_' . $ct2ReportRunId;
        if ($ct2SourceModule !== '') {
            $ct2FileName .= '_' . preg_replace('/[^a-z0-9_]+/i', '_', $ct2SourceModule);
        }
        $ct2FileName .= '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $ct2FileName . '"');

        $ct2Output = fopen('php://output', 'wb');
        if ($ct2Output === false) {
            throw new RuntimeException('Unable to create export output stream.');
        }

        fputcsv(
            $ct2Output,
            [
                'report_run_id',
                'report_name',
                'run_label',
                'source_module',
                'snapshot_type',
                'reference_code',
                'metric_label',
                'metric_value',
                'metric_count',
                'status_flag',
                'external_reference_id',
                'notes',
                'created_at',
            ]
        );

        foreach ($ct2ExportRows as $ct2ExportRow) {
            fputcsv(
                $ct2Output,
                [
                    (int) ($ct2ExportRow['ct2_report_run_id'] ?? 0),
                    (string) ($ct2ExportRow['report_name'] ?? ''),
                    (string) ($ct2ExportRow['run_label'] ?? ''),
                    (string) ($ct2ExportRow['source_module'] ?? ''),
                    (string) ($ct2ExportRow['snapshot_type'] ?? ''),
                    (string) ($ct2ExportRow['reference_code'] ?? ''),
                    (string) ($ct2ExportRow['metric_label'] ?? ''),
                    number_format((float) ($ct2ExportRow['metric_value'] ?? 0), 2, '.', ''),
                    (int) ($ct2ExportRow['metric_count'] ?? 0),
                    (string) ($ct2ExportRow['status_flag'] ?? ''),
                    (string) ($ct2ExportRow['external_reference_id'] ?? ''),
                    (string) ($ct2ExportRow['notes'] ?? ''),
                    (string) ($ct2ExportRow['created_at'] ?? ''),
                ]
            );
        }

        fclose($ct2Output);
        exit;
    }

    private function assertPostWithCsrf(): void
    {
        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            throw new InvalidArgumentException('Invalid request token.');
        }
    }

    private function ct2ValidateReportPayload(array $ct2Input): array
    {
        $ct2Payload = [
            'report_code' => strtoupper(trim((string) ($ct2Input['report_code'] ?? ''))),
            'report_name' => trim((string) ($ct2Input['report_name'] ?? '')),
            'report_scope' => trim((string) ($ct2Input['report_scope'] ?? 'cross_module')),
            'report_status' => trim((string) ($ct2Input['report_status'] ?? 'active')),
            'default_date_range' => trim((string) ($ct2Input['default_date_range'] ?? '30d')),
            'definition_notes' => trim((string) ($ct2Input['definition_notes'] ?? '')),
        ];

        foreach (['report_code', 'report_name'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required financial report field: ' . $ct2RequiredField);
            }
        }

        if (!in_array($ct2Payload['report_scope'], ['agents', 'suppliers', 'availability', 'marketing', 'visa', 'cross_module'], true)) {
            throw new InvalidArgumentException('Invalid report scope.');
        }

        if (!in_array($ct2Payload['report_status'], ['draft', 'active', 'archived'], true)) {
            throw new InvalidArgumentException('Invalid report status.');
        }

        if (!in_array($ct2Payload['default_date_range'], ['7d', '30d', '90d', 'custom'], true)) {
            throw new InvalidArgumentException('Invalid default date range.');
        }

        return $ct2Payload;
    }

    private function ct2ValidateFilterPayload(array $ct2Input): array
    {
        $ct2Payload = [
            'ct2_financial_report_id' => (int) ($ct2Input['ct2_financial_report_id'] ?? 0),
            'filter_key' => trim((string) ($ct2Input['filter_key'] ?? '')),
            'filter_label' => trim((string) ($ct2Input['filter_label'] ?? '')),
            'filter_type' => trim((string) ($ct2Input['filter_type'] ?? 'text')),
            'default_value' => trim((string) ($ct2Input['default_value'] ?? '')),
            'sort_order' => max(1, (int) ($ct2Input['sort_order'] ?? 1)),
        ];

        if ($ct2Payload['ct2_financial_report_id'] < 1 || $ct2Payload['filter_key'] === '' || $ct2Payload['filter_label'] === '') {
            throw new InvalidArgumentException('Report filter requires a report, filter key, and filter label.');
        }

        if (!in_array($ct2Payload['filter_type'], ['date', 'select', 'text', 'status'], true)) {
            throw new InvalidArgumentException('Invalid filter type.');
        }

        return $ct2Payload;
    }

    private function ct2ValidateRunPayload(array $ct2Input): array
    {
        $ct2Payload = [
            'ct2_financial_report_id' => (int) ($ct2Input['ct2_financial_report_id'] ?? 0),
            'run_label' => trim((string) ($ct2Input['run_label'] ?? '')),
            'date_from' => trim((string) ($ct2Input['date_from'] ?? '')),
            'date_to' => trim((string) ($ct2Input['date_to'] ?? '')),
            'module_key' => trim((string) ($ct2Input['module_key'] ?? 'all')),
            'source_system' => trim((string) ($ct2Input['source_system'] ?? '')),
        ];

        if ($ct2Payload['ct2_financial_report_id'] < 1 || $ct2Payload['date_from'] === '' || $ct2Payload['date_to'] === '') {
            throw new InvalidArgumentException('Report generation requires a report, date from, and date to.');
        }

        if ($ct2Payload['run_label'] === '') {
            $ct2Payload['run_label'] = 'Run ' . $ct2Payload['date_from'] . ' to ' . $ct2Payload['date_to'];
        }

        if (!in_array($ct2Payload['module_key'], ['all', 'agents', 'suppliers', 'availability', 'marketing', 'visa'], true)) {
            throw new InvalidArgumentException('Invalid module key for report generation.');
        }

        if ($ct2Payload['date_from'] > $ct2Payload['date_to']) {
            throw new InvalidArgumentException('Date from cannot be later than date to.');
        }

        return $ct2Payload;
    }
}
