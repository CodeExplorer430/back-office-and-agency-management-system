<?php
$ct2ReportForm = $ct2ReportForEdit ?? null;
?>
<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Final Vertical Slice</p>
            <h2>Financial Reporting and Analytics</h2>
        </div>
        <form method="get" action="<?= htmlspecialchars(ct2_url(), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-inline-form">
            <input type="hidden" name="module" value="financial">
            <input type="hidden" name="action" value="index">
            <select class="ct2-select" name="ct2_financial_report_id">
                <option value="0">All report definitions</option>
                <?php foreach ($ct2ReportSelection as $ct2ReportOption): ?>
                    <option value="<?= (int) $ct2ReportOption['ct2_financial_report_id']; ?>" <?= (int) $ct2SelectedReportId === (int) $ct2ReportOption['ct2_financial_report_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars((string) $ct2ReportOption['report_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="ct2-select" name="source_module">
                <option value="">All source modules</option>
                <?php foreach (['agents', 'suppliers', 'availability', 'marketing', 'visa', 'cross_module'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= $ct2SourceModule === $ct2Option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="ct2-select" name="flag_status">
                <option value="">All flag states</option>
                <?php foreach (['open', 'acknowledged', 'resolved'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= $ct2FlagStatus === $ct2Option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <input class="ct2-input" name="ct2_report_run_id" type="number" min="0" placeholder="Run ID" value="<?= $ct2ReportRunId > 0 ? (int) $ct2ReportRunId : ''; ?>">
            <button class="ct2-btn ct2-btn-secondary" type="submit">Filter</button>
            <?php if ($ct2ReportRunId > 0 && ct2_has_permission('financial.export')): ?>
                <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'exportCsv', 'ct2_report_run_id' => $ct2ReportRunId, 'source_module' => $ct2SourceModule]), ENT_QUOTES, 'UTF-8'); ?>">Export CSV</a>
            <?php endif; ?>
        </form>
    </div>
</section>

<section class="ct2-stat-grid">
    <article class="ct2-stat-card">
        <h3>Latest Run</h3>
        <strong><?= (int) ($ct2FinancialSummary['latest_run_id'] ?? 0); ?></strong>
        <span>Most recent report execution</span>
    </article>
    <article class="ct2-stat-card">
        <h3>Snapshots</h3>
        <strong><?= (int) ($ct2FinancialSummary['snapshot_count'] ?? 0); ?></strong>
        <span>Critical: <?= (int) ($ct2FinancialSummary['critical_snapshots'] ?? 0); ?></span>
    </article>
    <article class="ct2-stat-card">
        <h3>Open Flags</h3>
        <strong><?= (int) ($ct2FinancialSummary['open_flags'] ?? 0); ?></strong>
        <span>Operational reconciliation items</span>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3><?= $ct2ReportForm !== null ? 'Update Report Definition' : 'Create Report Definition'; ?></h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'saveReport']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form ct2-form-grid">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ct2_financial_report_id" value="<?= (int) ($ct2ReportForm['ct2_financial_report_id'] ?? 0); ?>">

            <label class="ct2-label">Report Code</label>
            <input class="ct2-input" name="report_code" required value="<?= htmlspecialchars((string) ($ct2ReportForm['report_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Report Name</label>
            <input class="ct2-input" name="report_name" required value="<?= htmlspecialchars((string) ($ct2ReportForm['report_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Scope</label>
            <select class="ct2-select" name="report_scope">
                <?php foreach (['cross_module', 'agents', 'suppliers', 'availability', 'marketing', 'visa'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2ReportForm['report_scope'] ?? 'cross_module') === $ct2Option) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Status</label>
            <select class="ct2-select" name="report_status">
                <?php foreach (['draft', 'active', 'archived'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2ReportForm['report_status'] ?? 'active') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Default Date Range</label>
            <select class="ct2-select" name="default_date_range">
                <?php foreach (['7d', '30d', '90d', 'custom'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2ReportForm['default_date_range'] ?? '30d') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(strtoupper($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Definition Notes</label>
            <textarea class="ct2-textarea" name="definition_notes" rows="4"><?= htmlspecialchars((string) ($ct2ReportForm['definition_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>

            <button class="ct2-btn ct2-btn-primary" type="submit">Save Report</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Report Filter Catalog</h3>
        <p class="ct2-subtle">Store reusable filter metadata per report definition. Run-time inputs remain explicit so CT2 does not hide financial assumptions.</p>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'saveFilter']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Report</label>
            <select class="ct2-select" name="ct2_financial_report_id" required>
                <option value="">Select report</option>
                <?php foreach ($ct2ReportSelection as $ct2ReportOption): ?>
                    <option value="<?= (int) $ct2ReportOption['ct2_financial_report_id']; ?>" <?= (int) $ct2SelectedReportId === (int) $ct2ReportOption['ct2_financial_report_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars((string) $ct2ReportOption['report_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Filter Key</label>
            <input class="ct2-input" name="filter_key" required placeholder="date_from">
            <label class="ct2-label">Filter Label</label>
            <input class="ct2-input" name="filter_label" required placeholder="Date From">
            <label class="ct2-label">Filter Type</label>
            <select class="ct2-select" name="filter_type">
                <?php foreach (['date', 'select', 'text', 'status'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Default Value</label>
            <input class="ct2-input" name="default_value" placeholder="optional">
            <label class="ct2-label">Sort Order</label>
            <input class="ct2-input" name="sort_order" type="number" min="1" value="<?= count($ct2SelectedReportFilters) + 1; ?>">
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Filter</button>
        </form>

        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Key</th>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Default</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2SelectedReportFilters as $ct2Filter): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Filter['filter_key'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Filter['filter_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Filter['filter_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2Filter['default_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2SelectedReportFilters === []): ?>
                    <tr><td colspan="4">No filter definitions stored for the selected report.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Generate Report Run</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'runReport']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form ct2-form-grid">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Report</label>
            <select class="ct2-select" name="ct2_financial_report_id" required>
                <option value="">Select report</option>
                <?php foreach ($ct2ReportSelection as $ct2ReportOption): ?>
                    <option value="<?= (int) $ct2ReportOption['ct2_financial_report_id']; ?>" <?= (int) $ct2SelectedReportId === (int) $ct2ReportOption['ct2_financial_report_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars((string) $ct2ReportOption['report_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Run Label</label>
            <input class="ct2-input" name="run_label" placeholder="Month-end CT2 snapshot">

            <label class="ct2-label">Date From</label>
            <input class="ct2-input" name="date_from" type="date" value="<?= htmlspecialchars(date('Y-m-01'), ENT_QUOTES, 'UTF-8'); ?>" required>

            <label class="ct2-label">Date To</label>
            <input class="ct2-input" name="date_to" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required>

            <label class="ct2-label">Module Scope Override</label>
            <select class="ct2-select" name="module_key">
                <?php foreach (['all', 'agents', 'suppliers', 'availability', 'marketing', 'visa'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="financials or ct1">

            <button class="ct2-btn ct2-btn-primary" type="submit">Generate Run</button>
        </form>
    </article>

    <article class="ct2-panel">
        <div class="ct2-section-header">
            <h3>Recent Report Runs</h3>
            <?php if ($ct2ReportRunId > 0 && ct2_has_permission('financial.export')): ?>
                <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'exportCsv', 'ct2_report_run_id' => $ct2ReportRunId, 'source_module' => $ct2SourceModule]), ENT_QUOTES, 'UTF-8'); ?>">Download current run</a>
            <?php endif; ?>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Run</th>
                    <th>Report</th>
                    <th>Window</th>
                    <th>Snapshots</th>
                    <th>Open Flags</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2FinancialRuns as $ct2Run): ?>
                    <tr>
                        <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'index', 'ct2_report_run_id' => (int) $ct2Run['ct2_report_run_id'], 'ct2_financial_report_id' => (int) $ct2Run['ct2_financial_report_id']]), ENT_QUOTES, 'UTF-8'); ?>">#<?= (int) $ct2Run['ct2_report_run_id']; ?></a></td>
                        <td><?= htmlspecialchars((string) $ct2Run['report_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Run['date_from'], ENT_QUOTES, 'UTF-8'); ?> to <?= htmlspecialchars((string) $ct2Run['date_to'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) ($ct2Run['snapshot_count'] ?? 0); ?></td>
                        <td><?= (int) ($ct2Run['open_flag_count'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2FinancialRuns === []): ?>
                    <tr><td colspan="5">No report runs generated yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="ct2-panel">
    <h3>Report Catalog</h3>
    <div class="ct2-table-wrap">
        <table class="ct2-table">
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Scope</th>
                <th>Status</th>
                <th>Filters</th>
                <th>Runs</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ct2FinancialReports as $ct2Report): ?>
                <tr>
                    <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'index', 'report_edit_id' => (int) $ct2Report['ct2_financial_report_id'], 'ct2_financial_report_id' => (int) $ct2Report['ct2_financial_report_id']]), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $ct2Report['report_code'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                    <td><?= htmlspecialchars((string) $ct2Report['report_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Report['report_scope'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Report['report_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= (int) ($ct2Report['filter_count'] ?? 0); ?></td>
                    <td><?= (int) ($ct2Report['run_count'] ?? 0); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ct2FinancialReports === []): ?>
                <tr><td colspan="6">No financial report definitions available.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Financial Snapshots</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Module</th>
                    <th>Reference</th>
                    <th>Metric</th>
                    <th>Value</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2FinancialSnapshots as $ct2Snapshot): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Snapshot['source_module'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Snapshot['reference_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Snapshot['metric_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= number_format((float) $ct2Snapshot['metric_value'], 2); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Snapshot['status_flag'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2FinancialSnapshots === []): ?>
                    <tr><td colspan="5">No snapshots match the current filters.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>Reconciliation Flags</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Module</th>
                    <th>Severity</th>
                    <th>Summary</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2ReconciliationFlags as $ct2Flag): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Flag['source_module'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Flag['severity'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Flag['flag_summary'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Flag['flag_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'resolveFlag']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-approval-form">
                                <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="ct2_reconciliation_flag_id" value="<?= (int) $ct2Flag['ct2_reconciliation_flag_id']; ?>">
                                <input type="hidden" name="ct2_report_run_id" value="<?= $ct2ReportRunId; ?>">
                                <input type="hidden" name="source_module" value="<?= htmlspecialchars($ct2SourceModule, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="flag_filter_status" value="<?= htmlspecialchars($ct2FlagStatus, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="ct2_financial_report_id" value="<?= (int) $ct2SelectedReportId; ?>">
                                <select class="ct2-select" name="flag_status">
                                    <?php foreach (['open', 'acknowledged', 'resolved'] as $ct2Option): ?>
                                        <option value="<?= $ct2Option; ?>" <?= ((string) $ct2Flag['flag_status'] === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea class="ct2-textarea" name="resolution_notes" rows="2" placeholder="Resolution note"><?= htmlspecialchars((string) ($ct2Flag['resolution_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <button class="ct2-btn ct2-btn-secondary" type="submit">Update Flag</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2ReconciliationFlags === []): ?>
                    <tr><td colspan="5">No reconciliation flags match the current filters.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
