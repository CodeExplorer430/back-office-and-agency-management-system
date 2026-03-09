<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Operational Overview</p>
            <h2>Back-Office Dashboard</h2>
        </div>
    </div>

    <div class="ct2-stat-grid">
        <article class="ct2-stat-card">
            <h3>Total Agents</h3>
            <strong><?= (int) ($ct2AgentSummary['total_agents'] ?? 0); ?></strong>
            <span>Pending approvals: <?= (int) ($ct2AgentSummary['pending_agents'] ?? 0); ?></span>
        </article>
        <article class="ct2-stat-card">
            <h3>Active Agents</h3>
            <strong><?= (int) ($ct2AgentSummary['active_agents'] ?? 0); ?></strong>
            <span>Approved and operational</span>
        </article>
        <article class="ct2-stat-card">
            <h3>Total Staff</h3>
            <strong><?= (int) ($ct2StaffSummary['total_staff'] ?? 0); ?></strong>
            <span>Available now: <?= (int) ($ct2StaffSummary['available_staff'] ?? 0); ?></span>
        </article>
        <article class="ct2-stat-card">
            <h3>Total Suppliers</h3>
            <strong><?= (int) ($ct2SupplierSummary['total_suppliers'] ?? 0); ?></strong>
            <span>Pending supplier approvals: <?= (int) ($ct2SupplierSummary['pending_suppliers'] ?? 0); ?></span>
        </article>
        <article class="ct2-stat-card">
            <h3>Tour Resources</h3>
            <strong><?= (int) ($ct2ResourceSummary['total_resources'] ?? 0); ?></strong>
            <span>Available now: <?= (int) ($ct2ResourceSummary['available_resources'] ?? 0); ?></span>
        </article>
        <article class="ct2-stat-card">
            <h3>Marketing Campaigns</h3>
            <strong><?= (int) ($ct2CampaignSummary['total_campaigns'] ?? 0); ?></strong>
            <span>Pending approvals: <?= (int) ($ct2CampaignSummary['pending_campaigns'] ?? 0); ?></span>
        </article>
        <article class="ct2-stat-card">
            <h3>Visa Applications</h3>
            <strong><?= (int) ($ct2VisaSummary['total_applications'] ?? 0); ?></strong>
            <span>Review queue: <?= (int) ($ct2VisaSummary['review_queue'] ?? 0); ?></span>
        </article>
        <article class="ct2-stat-card">
            <h3>Financial Snapshots</h3>
            <strong><?= (int) ($ct2FinancialSummary['snapshot_count'] ?? 0); ?></strong>
            <span>Open flags: <?= (int) ($ct2FinancialSummary['open_flags'] ?? 0); ?></span>
        </article>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <div class="ct2-section-header">
            <h3>Recent Approval Queue</h3>
            <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'approvals', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open queue</a>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Requested By</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Approvals as $ct2Approval): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Approval['subject_type'], ENT_QUOTES, 'UTF-8'); ?> #<?= (int) $ct2Approval['subject_id']; ?></td>
                        <td><?= htmlspecialchars((string) $ct2Approval['approval_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2Approval['requested_by_name'] ?? 'System'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Approvals === []): ?>
                    <tr><td colspan="3">No approval records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <div class="ct2-section-header">
            <h3>Recent Dispatch Orders</h3>
            <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open availability module</a>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Booking</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2DispatchOrders as $ct2DispatchOrder): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($ct2DispatchOrder['external_booking_id'] ?? 'Manual'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2DispatchOrder['plate_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2DispatchOrder['dispatch_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2DispatchOrders === []): ?>
                    <tr><td colspan="3">No dispatch orders recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="ct2-grid-3">
    <article class="ct2-placeholder-card">
        <h3>Supplier and Partner Management</h3>
        <p>Supplier onboarding, contract tracking, KPI scoring, and relationship notes are now available as the second CT2 module slice.</p>
        <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open module</a>
    </article>
    <article class="ct2-placeholder-card">
        <h3>Tour Availability and Resource Planning</h3>
        <p>Resource planning, soft-block availability checks, seasonal blocks, and dispatch support are now available as a CT2 module.</p>
        <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open module</a>
    </article>
    <article class="ct2-placeholder-card">
        <h3>Marketing and Promotions Management</h3>
        <p>Campaign management, promotions, voucher tracking, affiliates, and attribution reporting are now available as a CT2 module.</p>
        <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'marketing', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open module</a>
    </article>
    <article class="ct2-placeholder-card">
        <h3>Document and Visa Assistance</h3>
        <p>Visa intake, checklist verification, payment references, and notification tracking are now available as a CT2 module.</p>
        <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open module</a>
    </article>
    <article class="ct2-placeholder-card">
        <h3>Financial Reporting and Analytics</h3>
        <p>Operational report definitions, reconciliation flags, financial snapshots, and CSV exports are now available for CT2 users with finance permissions.</p>
        <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'financial', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open module</a>
    </article>
</section>
