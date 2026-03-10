<?php $ct2SupplierForm = $ct2SupplierForEdit ?? null; ?>
<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Partner Network</p>
            <h2>Supplier and Partner Management</h2>
        </div>
        <form method="get" action="<?= htmlspecialchars(ct2_url(), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-inline-form">
            <input type="hidden" name="module" value="suppliers">
            <input type="hidden" name="action" value="index">
            <input class="ct2-input" name="search" type="text" placeholder="Search suppliers" value="<?= htmlspecialchars((string) $ct2Search, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="ct2-btn ct2-btn-secondary" type="submit">Filter</button>
        </form>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3><?= $ct2SupplierForm !== null ? 'Update Supplier Record' : 'Register Supplier'; ?></h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'save']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form ct2-form-grid">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ct2_supplier_id" value="<?= (int) ($ct2SupplierForm['ct2_supplier_id'] ?? 0); ?>">

            <label class="ct2-label">Supplier Code</label>
            <input class="ct2-input" name="supplier_code" required value="<?= htmlspecialchars((string) ($ct2SupplierForm['supplier_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Supplier Name</label>
            <input class="ct2-input" name="supplier_name" required value="<?= htmlspecialchars((string) ($ct2SupplierForm['supplier_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Supplier Type</label>
            <select class="ct2-select" name="supplier_type">
                <?php foreach (['supplier', 'partner', 'hybrid'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2SupplierForm['supplier_type'] ?? 'supplier') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Primary Contact</label>
            <input class="ct2-input" name="primary_contact_name" required value="<?= htmlspecialchars((string) ($ct2SupplierForm['primary_contact_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Contact Role</label>
            <input class="ct2-input" name="contact_role_title" value="Account Manager">

            <label class="ct2-label">Email</label>
            <input class="ct2-input" name="email" type="email" required value="<?= htmlspecialchars((string) ($ct2SupplierForm['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Phone</label>
            <input class="ct2-input" name="phone" required value="<?= htmlspecialchars((string) ($ct2SupplierForm['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Service Category</label>
            <input class="ct2-input" name="service_category" required value="<?= htmlspecialchars((string) ($ct2SupplierForm['service_category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Support Tier</label>
            <select class="ct2-select" name="support_tier">
                <?php foreach (['standard', 'priority', 'strategic'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2SupplierForm['support_tier'] ?? 'standard') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Approval Status</label>
            <select class="ct2-select" name="approval_status">
                <?php foreach (['pending', 'approved', 'rejected'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2SupplierForm['approval_status'] ?? 'pending') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Onboarding Status</label>
            <select class="ct2-select" name="onboarding_status">
                <?php foreach (['draft', 'in_review', 'approved', 'live', 'blocked'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2SupplierForm['onboarding_status'] ?? 'draft') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Active Status</label>
            <select class="ct2-select" name="active_status">
                <?php foreach (['active', 'inactive'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2SupplierForm['active_status'] ?? 'active') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Risk Level</label>
            <select class="ct2-select" name="risk_level">
                <?php foreach (['low', 'medium', 'high'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2SupplierForm['risk_level'] ?? 'low') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Internal Owner User ID</label>
            <input class="ct2-input" name="internal_owner_user_id" type="number" min="0" value="<?= htmlspecialchars((string) ($ct2SupplierForm['internal_owner_user_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">External Supplier ID</label>
            <input class="ct2-input" name="external_supplier_id" value="<?= htmlspecialchars((string) ($ct2SupplierForm['external_supplier_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="external portal or finance" value="<?= htmlspecialchars((string) ($ct2SupplierForm['source_system'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <button class="ct2-btn ct2-btn-primary" type="submit">Save Supplier</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Operational Integration Notes</h3>
        <ul class="ct2-checklist">
            <li>Supplier self-service portal: mocked as internal handoff only.</li>
            <li>E-signature integration: represented by contract signature status values.</li>
            <li>Finance handoff: tracked through deterministic status fields, not live APIs.</li>
        </ul>
        <p class="ct2-subtle">Use the selected supplier context below to register onboarding, contracts, KPI scores, and relationship notes.</p>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Onboarding Tracker</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'saveOnboarding']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Supplier</label>
            <select class="ct2-select" name="ct2_supplier_id" required>
                <option value="">Select supplier</option>
                <?php foreach ($ct2SupplierSelection as $ct2SupplierOption): ?>
                    <option value="<?= (int) $ct2SupplierOption['ct2_supplier_id']; ?>" <?= ($ct2SelectedSupplierId === (int) $ct2SupplierOption['ct2_supplier_id']) ? 'selected' : ''; ?>><?= htmlspecialchars((string) $ct2SupplierOption['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Checklist Status</label>
            <select class="ct2-select" name="checklist_status">
                <?php foreach (['not_started', 'collecting', 'review_ready', 'completed'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= ((string) ($ct2OnboardingForSelectedSupplier['checklist_status'] ?? 'not_started') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Documents Status</label>
            <select class="ct2-select" name="documents_status">
                <?php foreach (['missing', 'partial', 'complete'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= ((string) ($ct2OnboardingForSelectedSupplier['documents_status'] ?? 'missing') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Compliance Status</label>
            <select class="ct2-select" name="compliance_status">
                <?php foreach (['pending', 'cleared', 'flagged'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= ((string) ($ct2OnboardingForSelectedSupplier['compliance_status'] ?? 'pending') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Target Go Live</label>
            <input class="ct2-input" name="target_go_live_date" type="date" value="<?= htmlspecialchars((string) ($ct2OnboardingForSelectedSupplier['target_go_live_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Completed At</label>
            <input class="ct2-input" name="completed_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($ct2OnboardingForSelectedSupplier['completed_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Blocked Reason</label>
            <input class="ct2-input" name="blocked_reason" value="<?= htmlspecialchars((string) ($ct2OnboardingForSelectedSupplier['blocked_reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Review Notes</label>
            <textarea class="ct2-textarea" name="review_notes" rows="3"><?= htmlspecialchars((string) ($ct2OnboardingForSelectedSupplier['review_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Onboarding</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Contract Register</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'saveContract']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Supplier</label>
            <select class="ct2-select" name="ct2_supplier_id" required>
                <option value="">Select supplier</option>
                <?php foreach ($ct2SupplierSelection as $ct2SupplierOption): ?>
                    <option value="<?= (int) $ct2SupplierOption['ct2_supplier_id']; ?>" <?= ($ct2SelectedSupplierId === (int) $ct2SupplierOption['ct2_supplier_id']) ? 'selected' : ''; ?>><?= htmlspecialchars((string) $ct2SupplierOption['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Contract Code</label>
            <input class="ct2-input" name="contract_code" required>
            <label class="ct2-label">Contract Title</label>
            <input class="ct2-input" name="contract_title" required>
            <label class="ct2-label">Effective Date</label>
            <input class="ct2-input" name="effective_date" type="date" required>
            <label class="ct2-label">Expiry Date</label>
            <input class="ct2-input" name="expiry_date" type="date" required>
            <label class="ct2-label">Renewal Status</label>
            <select class="ct2-select" name="renewal_status">
                <?php foreach (['not_started', 'renewal_due', 'renewed', 'expired'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Contract Status</label>
            <select class="ct2-select" name="contract_status">
                <?php foreach (['draft', 'pending_signature', 'active', 'expired', 'terminated'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Mock Signature Status</label>
            <select class="ct2-select" name="mock_signature_status">
                <?php foreach (['pending', 'sent', 'signed'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Finance Handoff Status</label>
            <select class="ct2-select" name="finance_handoff_status">
                <?php foreach (['not_started', 'shared', 'confirmed'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Clause Summary</label>
            <textarea class="ct2-textarea" name="clause_summary" rows="3"></textarea>
            <button class="ct2-btn ct2-btn-primary" type="submit">Register Contract</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>KPI Scorecard</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'saveKpi']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Supplier</label>
            <select class="ct2-select" name="ct2_supplier_id" required>
                <option value="">Select supplier</option>
                <?php foreach ($ct2SupplierSelection as $ct2SupplierOption): ?>
                    <option value="<?= (int) $ct2SupplierOption['ct2_supplier_id']; ?>" <?= ($ct2SelectedSupplierId === (int) $ct2SupplierOption['ct2_supplier_id']) ? 'selected' : ''; ?>><?= htmlspecialchars((string) $ct2SupplierOption['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Measurement Date</label>
            <input class="ct2-input" name="measurement_date" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required>
            <label class="ct2-label">Service Score</label>
            <input class="ct2-input" name="service_score" type="number" min="0" max="100" step="0.01" value="80.00" required>
            <label class="ct2-label">Delivery Score</label>
            <input class="ct2-input" name="delivery_score" type="number" min="0" max="100" step="0.01" value="80.00" required>
            <label class="ct2-label">Compliance Score</label>
            <input class="ct2-input" name="compliance_score" type="number" min="0" max="100" step="0.01" value="80.00" required>
            <label class="ct2-label">Responsiveness Score</label>
            <input class="ct2-input" name="responsiveness_score" type="number" min="0" max="100" step="0.01" value="80.00" required>
            <label class="ct2-label">Risk Flag</label>
            <select class="ct2-select" name="risk_flag">
                <?php foreach (['none', 'watch', 'critical'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Notes</label>
            <textarea class="ct2-textarea" name="notes" rows="3"></textarea>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save KPI</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Relationship Note</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'saveNote']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Supplier</label>
            <select class="ct2-select" name="ct2_supplier_id" required>
                <option value="">Select supplier</option>
                <?php foreach ($ct2SupplierSelection as $ct2SupplierOption): ?>
                    <option value="<?= (int) $ct2SupplierOption['ct2_supplier_id']; ?>" <?= ($ct2SelectedSupplierId === (int) $ct2SupplierOption['ct2_supplier_id']) ? 'selected' : ''; ?>><?= htmlspecialchars((string) $ct2SupplierOption['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Note Type</label>
            <select class="ct2-select" name="note_type">
                <?php foreach (['communication', 'escalation', 'improvement_plan', 'review'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Title</label>
            <input class="ct2-input" name="note_title" required>
            <label class="ct2-label">Note</label>
            <textarea class="ct2-textarea" name="note_body" rows="4" required></textarea>
            <label class="ct2-label">Next Action Date</label>
            <input class="ct2-input" name="next_action_date" type="date">
            <button class="ct2-btn ct2-btn-primary" type="submit">Record Note</button>
        </form>
    </article>
</section>

<section class="ct2-panel">
    <h3>Supplier Directory</h3>
    <div class="ct2-table-wrap">
        <table class="ct2-table">
            <thead>
            <tr>
                <th>Code</th>
                <th>Supplier</th>
                <th>Category</th>
                <th>Onboarding</th>
                <th>Approval</th>
                <th>Risk</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ct2Suppliers as $ct2Supplier): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $ct2Supplier['supplier_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Supplier['service_category'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Supplier['onboarding_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Supplier['approval_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Supplier['risk_level'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'suppliers', 'action' => 'index', 'edit_id' => (int) $ct2Supplier['ct2_supplier_id']]), ENT_QUOTES, 'UTF-8'); ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ct2Suppliers === []): ?>
                <tr><td colspan="7">No suppliers registered yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Contract Register Snapshot</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contract</th>
                    <th>Renewal</th>
                    <th>Expiry</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Contracts as $ct2Contract): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Contract['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Contract['contract_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Contract['renewal_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Contract['expiry_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Contracts === []): ?>
                    <tr><td colspan="4">No contracts recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>KPI and Relationship Snapshot</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Weighted Score</th>
                    <th>Risk</th>
                    <th>Measurement Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Kpis as $ct2Kpi): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Kpi['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Kpi['weighted_score'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Kpi['risk_flag'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Kpi['measurement_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Kpis === []): ?>
                    <tr><td colspan="4">No KPI measurements recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h4>Latest Relationship Notes</h4>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Type</th>
                    <th>Title</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2RelationshipNotes, 0, 5) as $ct2Note): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Note['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Note['note_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Note['note_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2RelationshipNotes === []): ?>
                    <tr><td colspan="3">No relationship notes recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
