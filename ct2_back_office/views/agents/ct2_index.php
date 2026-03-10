<?php $ct2AgentForm = $ct2AgentForEdit ?? null; ?>
<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Partner Coverage</p>
            <h2>Travel Agent and Staff Management</h2>
        </div>
        <form method="get" action="<?= htmlspecialchars(ct2_url(), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-inline-form">
            <input type="hidden" name="module" value="agents">
            <input type="hidden" name="action" value="index">
            <input class="ct2-input" name="search" type="text" placeholder="Search agents" value="<?= htmlspecialchars((string) $ct2Search, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="ct2-btn ct2-btn-secondary" type="submit">Filter</button>
        </form>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3><?= $ct2AgentForm !== null ? 'Update Agent' : 'Register Agent'; ?></h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'agents', 'action' => 'save']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form ct2-form-grid">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ct2_agent_id" value="<?= (int) ($ct2AgentForm['ct2_agent_id'] ?? 0); ?>">

            <label class="ct2-label">Agent Code</label>
            <input class="ct2-input" name="agent_code" required value="<?= htmlspecialchars((string) ($ct2AgentForm['agent_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Agency Name</label>
            <input class="ct2-input" name="agency_name" required value="<?= htmlspecialchars((string) ($ct2AgentForm['agency_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Contact Person</label>
            <input class="ct2-input" name="contact_person" required value="<?= htmlspecialchars((string) ($ct2AgentForm['contact_person'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Email</label>
            <input class="ct2-input" name="email" type="email" required value="<?= htmlspecialchars((string) ($ct2AgentForm['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Phone</label>
            <input class="ct2-input" name="phone" required value="<?= htmlspecialchars((string) ($ct2AgentForm['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Region</label>
            <input class="ct2-input" name="region" required value="<?= htmlspecialchars((string) ($ct2AgentForm['region'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Commission Rate</label>
            <input class="ct2-input" name="commission_rate" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($ct2AgentForm['commission_rate'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Support Level</label>
            <select class="ct2-select" name="support_level">
                <?php foreach (['standard', 'priority', 'strategic'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2AgentForm['support_level'] ?? 'standard') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Approval Status</label>
            <select class="ct2-select" name="approval_status">
                <?php foreach (['pending', 'approved', 'rejected'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2AgentForm['approval_status'] ?? 'pending') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Active Status</label>
            <select class="ct2-select" name="active_status">
                <?php foreach (['active', 'inactive'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2AgentForm['active_status'] ?? 'active') === $ct2Option) ? 'selected' : ''; ?>><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">External Booking ID</label>
            <input class="ct2-input" name="external_booking_id" value="<?= htmlspecialchars((string) ($ct2AgentForm['external_booking_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">External Customer ID</label>
            <input class="ct2-input" name="external_customer_id" value="<?= htmlspecialchars((string) ($ct2AgentForm['external_customer_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">External Payment ID</label>
            <input class="ct2-input" name="external_payment_id" value="<?= htmlspecialchars((string) ($ct2AgentForm['external_payment_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="ct1 or financials" value="<?= htmlspecialchars((string) ($ct2AgentForm['source_system'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

            <button class="ct2-btn ct2-btn-primary" type="submit">Save Agent</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Assign Staff To Agent</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'agents', 'action' => 'assign']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">Agent</label>
            <select class="ct2-select" name="ct2_agent_id" required>
                <option value="">Select agent</option>
                <?php foreach ($ct2Agents as $ct2Agent): ?>
                    <option value="<?= (int) $ct2Agent['ct2_agent_id']; ?>"><?= htmlspecialchars((string) $ct2Agent['agency_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Staff Member</label>
            <select class="ct2-select" name="ct2_staff_id" required>
                <option value="">Select staff</option>
                <?php foreach ($ct2StaffOptions as $ct2StaffOption): ?>
                    <option value="<?= (int) $ct2StaffOption['ct2_staff_id']; ?>"><?= htmlspecialchars((string) $ct2StaffOption['full_name'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars((string) $ct2StaffOption['team_name'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Assignment Role</label>
            <input class="ct2-input" name="assignment_role" placeholder="Primary account manager" required>

            <label class="ct2-label">Assignment Status</label>
            <select class="ct2-select" name="assignment_status">
                <?php foreach (['active', 'paused', 'completed'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>

            <label class="ct2-label">Start Date</label>
            <input class="ct2-input" name="start_date" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">

            <label class="ct2-label">End Date</label>
            <input class="ct2-input" name="end_date" type="date">

            <label class="ct2-label">Notes</label>
            <textarea class="ct2-textarea" name="notes" rows="3"></textarea>

            <button class="ct2-btn ct2-btn-primary" type="submit">Save Assignment</button>
        </form>
    </article>
</section>

<section class="ct2-panel">
    <h3>Registered Agents</h3>
    <div class="ct2-table-wrap">
        <table class="ct2-table">
            <thead>
            <tr>
                <th>Code</th>
                <th>Agency</th>
                <th>Contact</th>
                <th>Region</th>
                <th>Approval</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ct2Agents as $ct2Agent): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $ct2Agent['agent_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Agent['agency_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Agent['contact_person'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Agent['region'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Agent['approval_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Agent['active_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'agents', 'action' => 'index', 'edit_id' => (int) $ct2Agent['ct2_agent_id']]), ENT_QUOTES, 'UTF-8'); ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ct2Agents === []): ?>
                <tr><td colspan="7">No agents registered yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="ct2-panel">
    <h3>Current Assignments</h3>
    <div class="ct2-table-wrap">
        <table class="ct2-table">
            <thead>
            <tr>
                <th>Agent</th>
                <th>Staff</th>
                <th>Role</th>
                <th>Status</th>
                <th>Window</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ct2Assignments as $ct2Assignment): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $ct2Assignment['agency_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Assignment['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Assignment['assignment_role'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Assignment['assignment_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string) $ct2Assignment['start_date'], ENT_QUOTES, 'UTF-8'); ?> to <?= htmlspecialchars((string) ($ct2Assignment['end_date'] ?? 'Open'), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($ct2Assignments === []): ?>
                <tr><td colspan="5">No assignments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
