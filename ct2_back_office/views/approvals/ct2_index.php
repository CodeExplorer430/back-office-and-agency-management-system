<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Operational Governance</p>
            <h2>Approval Queue</h2>
            <p class="ct2-section-copy">Review queued operational decisions, confirm approvals, and keep cross-module change control moving without breaking audit continuity.</p>
        </div>
    </div>
</section>

<section class="ct2-panel">
    <div class="ct2-table-wrap">
        <table class="ct2-table ct2-table-mobile-cards">
            <thead>
            <tr>
                <th>Subject</th>
                <th>Status</th>
                <th>Requested By</th>
                <th>Requested At</th>
                <th>Decision</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ct2Approvals as $ct2Approval): ?>
                <tr>
                    <td data-label="Subject"><?= htmlspecialchars((string) $ct2Approval['subject_type'], ENT_QUOTES, 'UTF-8'); ?> #<?= (int) $ct2Approval['subject_id']; ?></td>
                    <td data-label="Status"><?= htmlspecialchars((string) $ct2Approval['approval_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="Requested By"><?= htmlspecialchars((string) ($ct2Approval['requested_by_name'] ?? 'System'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="Requested At"><?= htmlspecialchars((string) $ct2Approval['requested_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td data-label="Decision">
                        <?php if (ct2_has_permission('approvals.decide')): ?>
                            <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'approvals', 'action' => 'decide']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-approval-form">
                                <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="ct2_approval_workflow_id" value="<?= (int) $ct2Approval['ct2_approval_workflow_id']; ?>">
                                <select class="ct2-select" name="approval_status">
                                    <?php foreach (['approved', 'rejected', 'pending'] as $ct2Option): ?>
                                        <option value="<?= $ct2Option; ?>" <?= ($ct2Approval['approval_status'] === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea class="ct2-textarea" name="decision_notes" rows="2" placeholder="Decision notes"><?= htmlspecialchars((string) ($ct2Approval['decision_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <button class="ct2-btn ct2-btn-primary" type="submit">Save</button>
                            </form>
                        <?php else: ?>
                            Read only
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ct2Approvals === []): ?>
                <tr><td colspan="5">Approval queue is empty.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
