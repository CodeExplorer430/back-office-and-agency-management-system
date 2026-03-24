<?php
$ct2BarMax = 1;
foreach ($ct2AnalyticsBars as $ct2Bar) {
    $ct2BarMax = max($ct2BarMax, (int) ($ct2Bar['value'] ?? 0));
}

$ct2ChartPayload = [
    'systemFlow' => [
        'categories' => array_map(static fn (array $ct2Metric): string => (string) $ct2Metric['label'], $ct2SystemFlowChart),
        'values' => array_map(static fn (array $ct2Metric): int => (int) ($ct2Metric['value'] ?? 0), $ct2SystemFlowChart),
    ],
    'departmentReadiness' => [
        'categories' => array_map(static fn (array $ct2Metric): string => (string) $ct2Metric['label'], $ct2DepartmentGraph),
        'values' => array_map(static fn (array $ct2Metric): int => (int) ($ct2Metric['value'] ?? 0), $ct2DepartmentGraph),
    ],
];

$ct2PrimaryCard = $ct2AnalyticsCards[0] ?? null;
$ct2SecondaryCards = array_slice($ct2AnalyticsCards, 1, 2);
$ct2TertiaryCards = array_slice($ct2AnalyticsCards, 3);
$ct2ActionBadges = ['Governance', 'Dispatch', 'Finance', 'Compliance', 'Partners', 'Staffing'];
$ct2ActionTones = ['success', 'primary', 'warning', 'info', 'secondary', 'success'];
$ct2BridgeItems = [
    [
        'kicker' => 'CT1 Input',
        'title' => 'Booking Data',
        'copy' => 'Confirmed bookings route into agent workload, staffing, and resource planning.',
    ],
    [
        'kicker' => 'Logistics',
        'title' => 'Fleet Coordination',
        'copy' => 'Dispatch planning keeps vehicles, equipment, and tour readiness aligned.',
    ],
    [
        'kicker' => 'Financials',
        'title' => 'Settlement',
        'copy' => 'Payment-linked records flow into reporting, flags, disbursement, and audit review.',
    ],
    [
        'kicker' => 'Administrative',
        'title' => 'Compliance',
        'copy' => 'Approvals, visas, and archived documents stay visible from one operating layer.',
    ],
];
?>

<section class="card ct2-overview-hero-card mb-4">
    <div class="card-body">
        <div class="row g-4 align-items-stretch">
            <div class="col-xl-8">
                <div class="ct2-hero-copy">
                    <div class="page-pretitle">Operational overview</div>
                    <h2>Back-Office Dashboard</h2>
                    <p class="text-secondary mb-0">
                        Track the CT1 intake stream, fulfillment queues, finance visibility, workforce capacity, and compliance activity from one cleaner command center.
                    </p>
                </div>
                <div class="ct2-hero-pill-row">
                    <span class="badge bg-success-lt text-success-emphasis">CT1-linked records: <?= (int) ($ct2DashboardSummary['ct1_records'] ?? 0); ?></span>
                    <span class="badge bg-success-lt text-success-emphasis">Active dispatches: <?= (int) ($ct2DashboardSummary['dispatch_active'] ?? 0); ?></span>
                    <span class="badge bg-success-lt text-success-emphasis">Open flags: <?= (int) ($ct2DashboardSummary['open_financial_flags'] ?? 0); ?></span>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="ct2-hero-stack">
                    <div class="ct2-hero-stat-grid">
                        <article class="ct2-hero-stat">
                            <span class="ct2-hero-stat-label">Pending approvals</span>
                            <strong><?= (int) ($ct2DashboardSummary['pending_approvals'] ?? 0); ?></strong>
                            <span class="text-secondary">Governance decisions waiting on release.</span>
                        </article>
                        <article class="ct2-hero-stat">
                            <span class="ct2-hero-stat-label">Visa review queue</span>
                            <strong><?= (int) ($ct2DashboardSummary['pending_visa_cases'] ?? 0); ?></strong>
                            <span class="text-secondary">Document-review cases still in motion.</span>
                        </article>
                    </div>
                    <div class="btn-list">
                        <a class="btn btn-success" href="<?= htmlspecialchars(ct2_url(['module' => 'approvals', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Review approvals</a>
                        <a class="btn btn-outline-success" href="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open financials</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row row-cards">
    <?php if ($ct2PrimaryCard !== null): ?>
        <div class="col-12 col-xl-5">
            <a class="card ct2-dashboard-card-link ct2-kpi-primary" href="<?= htmlspecialchars(ct2_url($ct2PrimaryCard['link_params']), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="page-pretitle mb-2"><?= htmlspecialchars((string) $ct2PrimaryCard['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="ct2-kpi-number"><?= (int) ($ct2PrimaryCard['value'] ?? 0); ?></div>
                        </div>
                        <span class="ct2-kpi-arrow" aria-hidden="true">↗</span>
                    </div>
                    <p class="mb-3"><?= htmlspecialchars((string) ($ct2PrimaryCard['meta'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="ct2-kpi-foot">
                        <span class="badge bg-success text-success-fg"><?= htmlspecialchars((string) ($ct2PrimaryCard['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </a>
        </div>
    <?php endif; ?>

    <?php foreach ($ct2SecondaryCards as $ct2Card): ?>
        <div class="col-sm-6 col-xl-3">
            <a class="card ct2-dashboard-card-link ct2-kpi-card h-100" href="<?= htmlspecialchars(ct2_url($ct2Card['link_params']), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="card-body">
                    <div class="page-pretitle mb-2"><?= htmlspecialchars((string) $ct2Card['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="ct2-kpi-number ct2-kpi-number-sm"><?= (int) ($ct2Card['value'] ?? 0); ?></div>
                    <p class="text-secondary mb-3"><?= htmlspecialchars((string) ($ct2Card['meta'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="small fw-semibold text-success"><?= htmlspecialchars((string) ($ct2Card['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

    <?php foreach ($ct2TertiaryCards as $ct2Card): ?>
        <div class="col-sm-6 col-xl-4">
            <a class="card ct2-dashboard-card-link ct2-kpi-card h-100" href="<?= htmlspecialchars(ct2_url($ct2Card['link_params']), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="page-pretitle mb-2"><?= htmlspecialchars((string) $ct2Card['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <span class="badge bg-success-lt text-success-emphasis"><?= (int) ($ct2Card['value'] ?? 0); ?></span>
                    </div>
                    <p class="text-secondary mb-3"><?= htmlspecialchars((string) ($ct2Card['meta'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="small fw-semibold text-success"><?= htmlspecialchars((string) ($ct2Card['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row row-cards mt-1">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <div>
                    <div class="page-pretitle">Drill-down</div>
                    <h3 class="card-title mb-1">Operational Signals</h3>
                    <p class="text-secondary mb-0">Use the bars below to jump directly into the modules carrying the live load.</p>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="ct2-analytics-stack">
                    <?php foreach ($ct2AnalyticsBars as $ct2Bar): ?>
                        <?php $ct2BarWidth = max(10, (int) round(((int) ($ct2Bar['value'] ?? 0) / $ct2BarMax) * 100)); ?>
                        <a class="ct2-analytics-item" href="<?= htmlspecialchars(ct2_url($ct2Bar['link_params']), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) $ct2Bar['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-secondary small"><?= htmlspecialchars((string) ($ct2Bar['meta'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <span class="ct2-analytics-value"><?= (int) ($ct2Bar['value'] ?? 0); ?></span>
                            </div>
                            <div class="progress progress-sm mt-3">
                                <div class="progress-bar bg-success ct2-progress-bar" style="width: <?= $ct2BarWidth; ?>%" role="progressbar" aria-valuenow="<?= (int) ($ct2Bar['value'] ?? 0); ?>" aria-valuemin="0" aria-valuemax="<?= $ct2BarMax; ?>"></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <div>
                    <div class="page-pretitle">Action Center</div>
                    <h3 class="card-title mb-1">Quick Actions</h3>
                    <p class="text-secondary mb-0">Open the highest-friction work immediately without scanning every module.</p>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="row g-3">
                    <?php foreach ($ct2QuickActions as $ct2ActionIndex => $ct2QuickAction): ?>
                        <div class="col-12">
                            <a class="ct2-quick-action-tile" href="<?= htmlspecialchars(ct2_url($ct2QuickAction['link_params']), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <span class="badge bg-<?= htmlspecialchars($ct2ActionTones[$ct2ActionIndex] ?? 'success', ENT_QUOTES, 'UTF-8'); ?>-lt text-<?= htmlspecialchars($ct2ActionTones[$ct2ActionIndex] ?? 'success', ENT_QUOTES, 'UTF-8'); ?>-emphasis mb-2">
                                            <?= htmlspecialchars($ct2ActionBadges[$ct2ActionIndex] ?? 'Action', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) $ct2QuickAction['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-secondary small"><?= htmlspecialchars((string) $ct2QuickAction['copy'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <span class="ct2-kpi-arrow" aria-hidden="true">→</span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mt-1">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <div>
                    <div class="page-pretitle">Operations Feed</div>
                    <h3 class="card-title mb-1">Latest Operational Records</h3>
                    <p class="text-secondary mb-0">Recent approvals, dispatches, and visa cases across the CT2 operating lane.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Owner</th>
                        <th>Module</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ct2RecentRecords as $ct2Record): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $ct2Record['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><a class="text-reset fw-semibold" href="<?= htmlspecialchars(ct2_url($ct2Record['link_params']), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) $ct2Record['reference'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                            <td><span class="badge bg-success-lt text-success-emphasis"><?= htmlspecialchars((string) $ct2Record['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?= htmlspecialchars((string) $ct2Record['owner'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) $ct2Record['module'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($ct2RecentRecords === []): ?>
                        <tr><td colspan="5" class="text-secondary">No operational records are available yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card ct2-chart-card">
            <div class="card-header border-0 pb-0">
                <div>
                    <div class="page-pretitle">Cross-System View</div>
                    <h3 class="card-title mb-1">ERP Interaction Load</h3>
                    <p class="text-secondary mb-0">Current pull from CT1, logistics, finance, HR, and administrative support.</p>
                </div>
            </div>
            <div class="card-body pt-3">
                <div id="ct2-system-flow-chart" class="ct2-chart-host"></div>
            </div>
        </div>
        <div class="card ct2-chart-card mt-3">
            <div class="card-header border-0 pb-0">
                <div>
                    <div class="page-pretitle">Capacity Snapshot</div>
                    <h3 class="card-title mb-1">Department Readiness</h3>
                    <p class="text-secondary mb-0">Availability across the modules carrying the largest daily demand.</p>
                </div>
            </div>
            <div class="card-body pt-3">
                <div id="ct2-department-chart" class="ct2-chart-host"></div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mt-1">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <div>
                    <div class="page-pretitle">System Bridge</div>
                    <h3 class="card-title mb-1">CT1 to CT2 Hand-offs</h3>
                    <p class="text-secondary mb-0">The operational bridge between intake, fulfillment, finance, and compliance.</p>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="row g-3">
                    <?php foreach ($ct2BridgeItems as $ct2BridgeItem): ?>
                        <div class="col-sm-6">
                            <article class="ct2-bridge-item">
                                <div class="ct2-bridge-kicker"><?= htmlspecialchars((string) $ct2BridgeItem['kicker'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <h4><?= htmlspecialchars((string) $ct2BridgeItem['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                <p class="mb-0"><?= htmlspecialchars((string) $ct2BridgeItem['copy'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <div>
                    <div class="page-pretitle">Campaign Snapshot</div>
                    <h3 class="card-title mb-1">Top Marketing Revenue Drivers</h3>
                    <p class="text-secondary mb-0">Highest-yield campaigns still feeding the back-office financial view.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th>Conversions</th>
                        <th class="text-end">Revenue</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ct2TopCampaigns as $ct2Campaign): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars((string) $ct2Campaign['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge bg-success-lt text-success-emphasis"><?= htmlspecialchars((string) $ct2Campaign['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?= (int) ($ct2Campaign['total_conversions'] ?? 0); ?></td>
                            <td class="text-end"><?= number_format((float) ($ct2Campaign['attributed_revenue'] ?? 0), 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($ct2TopCampaigns === []): ?>
                        <tr><td colspan="4" class="text-secondary">No campaign metrics are available yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script id="ct2-dashboard-chart-data" type="application/json"><?= json_encode($ct2ChartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?></script>
