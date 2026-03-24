<?php $ct2StaffForm = $ct2StaffForEdit ?? null; ?>
<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Internal Workforce</p>
            <h2>Staff Management</h2>
            <p class="ct2-section-copy">Keep the CT2 roster, team ownership, and availability records aligned before staff are assigned into live agency operations.</p>
        </div>
        <form method="get" action="<?= htmlspecialchars(ct2_url(), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-inline-form">
            <input type="hidden" name="module" value="staff">
            <input type="hidden" name="action" value="index">
            <input class="ct2-input" name="search" type="text" placeholder="Search staff" value="<?= htmlspecialchars((string) $ct2Search, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="ct2-btn ct2-btn-secondary" type="submit">Filter</button>
        </form>
    </div>
</section>

<section class="ct2-panel ct2-action-panel">
    <div class="ct2-section-header">
        <div>
            <h3>Workspace Actions</h3>
            <p class="ct2-subtle">Open staff creation and update work in a modal so the directory and team guidance remain the primary on-page focus.</p>
        </div>
    </div>
    <div class="ct2-action-grid">
        <button class="ct2-btn ct2-btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#ct2-staff-form-modal">Add Staff Member</button>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Team Assignment Guidance</h3>
        <p class="ct2-subtle">Staff records establish the internal CT2 roster. Agent-to-staff allocations are managed in the agent module so assignments stay aligned with external-facing responsibilities.</p>
        <ul class="ct2-checklist">
            <li>Keep team names consistent for accurate routing and approvals.</li>
            <li>Use availability status before assigning staff to active agent accounts.</li>
            <li>Record departmental ownership to support future CT2 analytics.</li>
        </ul>
    </article>

    <article class="ct2-panel">
        <h3>Roster Snapshot</h3>
        <p class="ct2-subtle">Search results stay visible on the page while add and edit workflows open as modal forms.</p>
        <ul class="ct2-checklist">
            <li>Active search filters still narrow the directory below.</li>
            <li>Edit links reopen the page with the correct record and auto-launch the modal.</li>
            <li>Availability and employment status remain auditable in the directory itself.</li>
        </ul>
    </article>
</section>

<section class="ct2-panel">
    <h3>CT2 Staff Directory</h3>
    <div class="ct2-table-wrap">
        <table class="ct2-table">
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Department</th>
                <th>Role</th>
                <th>Team</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ct2StaffMembers as $ct2StaffMember): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $ct2StaffMember['staff_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2StaffMember['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2StaffMember['department'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2StaffMember['position_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2StaffMember['team_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2StaffMember['availability_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'staff', 'action' => 'index', 'edit_id' => (int) $ct2StaffMember['ct2_staff_id']]), ENT_QUOTES, 'UTF-8'); ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ct2StaffMembers === []): ?>
                <tr><td colspan="7">No staff profiles found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade ct2-modal" id="ct2-staff-form-modal" tabindex="-1" aria-labelledby="ct2-staff-form-modal-title" aria-hidden="true" data-ct2-modal-auto-open="<?= $ct2StaffForm !== null ? 'true' : 'false'; ?>">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="ct2-staff-form-modal-title"><?= $ct2StaffForm !== null ? 'Update Staff Member' : 'Add Staff Member'; ?></h3>
                    <p class="ct2-subtle mb-0">Capture the CT2 roster profile used by assignments, routing, and workforce readiness views.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'staff', 'action' => 'save']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form ct2-form-grid">
                    <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="ct2_staff_id" value="<?= (int) ($ct2StaffForm['ct2_staff_id'] ?? 0); ?>">

                    <label class="ct2-label">Staff Code</label>
                    <input class="ct2-input" name="staff_code" required value="<?= htmlspecialchars((string) ($ct2StaffForm['staff_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label class="ct2-label">Full Name</label>
                    <input class="ct2-input" name="full_name" required value="<?= htmlspecialchars((string) ($ct2StaffForm['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label class="ct2-label">Email</label>
                    <input class="ct2-input" name="email" type="email" required value="<?= htmlspecialchars((string) ($ct2StaffForm['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label class="ct2-label">Phone</label>
                    <input class="ct2-input" name="phone" required value="<?= htmlspecialchars((string) ($ct2StaffForm['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label class="ct2-label">Department</label>
                    <input class="ct2-input" name="department" required value="<?= htmlspecialchars((string) ($ct2StaffForm['department'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label class="ct2-label">Position Title</label>
                    <input class="ct2-input" name="position_title" required value="<?= htmlspecialchars((string) ($ct2StaffForm['position_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label class="ct2-label">Employment Status</label>
                    <select class="ct2-select" name="employment_status">
                        <?php foreach (['active', 'inactive', 'suspended'] as $ct2Option): ?>
                            <option value="<?= $ct2Option; ?>" <?= (($ct2StaffForm['employment_status'] ?? 'active') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="ct2-label">Availability</label>
                    <select class="ct2-select" name="availability_status">
                        <?php foreach (['available', 'busy', 'on_leave'] as $ct2Option): ?>
                            <option value="<?= $ct2Option; ?>" <?= (($ct2StaffForm['availability_status'] ?? 'available') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="ct2-label">Team</label>
                    <input class="ct2-input" name="team_name" required value="<?= htmlspecialchars((string) ($ct2StaffForm['team_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <label class="ct2-label">Notes</label>
                    <textarea class="ct2-textarea" name="notes" rows="4"><?= htmlspecialchars((string) ($ct2StaffForm['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>

                    <button class="ct2-btn ct2-btn-primary" type="submit">Save Staff</button>
                </form>
            </div>
        </div>
    </div>
</div>
