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
            <h3>Recent Supplier Contracts</h3>
            <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'index']), ENT_QUOTES, 'UTF-8'); ?>">Open supplier module</a>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contract</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2SupplierContracts as $ct2Contract): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Contract['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Contract['contract_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Contract['contract_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2SupplierContracts === []): ?>
                    <tr><td colspan="3">No supplier contracts recorded yet.</td></tr>
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
        <p>Operational references are reserved without taking ownership from CT1 booking and itinerary records.</p>
        <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'placeholders', 'action' => 'show', 'feature' => 'tour-availability-resource-planning']), ENT_QUOTES, 'UTF-8'); ?>">View placeholder</a>
    </article>
    <article class="ct2-placeholder-card">
        <h3>Document and Visa Assistance</h3>
        <p>Compliance, document tracking, and visa-processing seams are scaffolded for later delivery.</p>
        <a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'placeholders', 'action' => 'show', 'feature' => 'document-visa-assistance']), ENT_QUOTES, 'UTF-8'); ?>">View placeholder</a>
    </article>
</section>
