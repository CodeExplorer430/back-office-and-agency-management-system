<?php
$ct2VisaTypeForm = $ct2VisaTypeForEdit ?? null;
$ct2ApplicationForm = $ct2ApplicationForEdit ?? null;
?>
<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Travel Documentation</p>
            <h2>Document and Visa Assistance</h2>
            <p class="ct2-section-copy">Oversee intake, checklist review, uploaded files, appointments, and release status for active visa cases.</p>
        </div>
        <form method="get" action="<?= htmlspecialchars(ct2_url(), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-inline-form">
            <input type="hidden" name="module" value="visa">
            <input type="hidden" name="action" value="index">
            <input class="ct2-input" name="search" type="text" placeholder="Search applications" value="<?= htmlspecialchars((string) $ct2Search, ENT_QUOTES, 'UTF-8'); ?>">
            <select class="ct2-select" name="status">
                <option value="">All statuses</option>
                <?php foreach (['draft', 'submitted', 'document_review', 'appointment_scheduled', 'processing', 'approved', 'released', 'rejected', 'cancelled', 'escalated_review'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= $ct2Status === $ct2Option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="ct2-select" name="ct2_visa_type_id">
                <option value="0">All visa types</option>
                <?php foreach ($ct2VisaTypeSelection as $ct2VisaType): ?>
                    <option value="<?= (int) $ct2VisaType['ct2_visa_type_id']; ?>" <?= $ct2VisaTypeFilter === (int) $ct2VisaType['ct2_visa_type_id'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $ct2VisaType['country_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2VisaType['visa_category'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ct2-btn ct2-btn-secondary" type="submit">Filter</button>
        </form>
    </div>
</section>

<section class="ct2-stat-grid">
    <article class="ct2-stat-card">
        <h3>Total Applications</h3>
        <strong><?= (int) ($ct2VisaSummary['total_applications'] ?? 0); ?></strong>
        <span>Open visa cases in CT2</span>
    </article>
    <article class="ct2-stat-card">
        <h3>Review Queue</h3>
        <strong><?= (int) ($ct2VisaSummary['review_queue'] ?? 0); ?></strong>
        <span>Submitted, document review, or escalated</span>
    </article>
    <article class="ct2-stat-card">
        <h3>Upcoming Appointments</h3>
        <strong><?= (int) ($ct2VisaSummary['upcoming_appointments'] ?? 0); ?></strong>
        <span>Scheduled within the next 7 days</span>
    </article>
    <article class="ct2-stat-card">
        <h3>Completed Cases</h3>
        <strong><?= (int) ($ct2VisaSummary['completed_applications'] ?? 0); ?></strong>
        <span>Approved or released applications</span>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3><?= $ct2VisaTypeForm !== null ? 'Update Visa Type' : 'Visa Type Registry'; ?></h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'saveVisaType']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form ct2-form-grid">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ct2_visa_type_id" value="<?= (int) ($ct2VisaTypeForm['ct2_visa_type_id'] ?? 0); ?>">
            <label class="ct2-label">Visa Code</label>
            <input class="ct2-input" name="visa_code" required value="<?= htmlspecialchars((string) ($ct2VisaTypeForm['visa_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Country</label>
            <input class="ct2-input" name="country_name" required value="<?= htmlspecialchars((string) ($ct2VisaTypeForm['country_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Category</label>
            <input class="ct2-input" name="visa_category" required value="<?= htmlspecialchars((string) ($ct2VisaTypeForm['visa_category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Processing Days</label>
            <input class="ct2-input" name="processing_days" type="number" min="1" value="<?= htmlspecialchars((string) ($ct2VisaTypeForm['processing_days'] ?? '5'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Validity Period (days)</label>
            <input class="ct2-input" name="validity_period_days" type="number" min="1" value="<?= htmlspecialchars((string) ($ct2VisaTypeForm['validity_period_days'] ?? '30'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Base Fee</label>
            <input class="ct2-input" name="base_fee" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($ct2VisaTypeForm['base_fee'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label ct2-checkbox-row"><input type="checkbox" name="biometrics_required" <?= !empty($ct2VisaTypeForm['biometrics_required']) ? 'checked' : ''; ?>> Biometrics required</label>
            <label class="ct2-label ct2-checkbox-row"><input type="checkbox" name="is_active" <?= !isset($ct2VisaTypeForm['is_active']) || !empty($ct2VisaTypeForm['is_active']) ? 'checked' : ''; ?>> Active visa type</label>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Visa Type</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3><?= $ct2ApplicationForm !== null ? 'Update Visa Application' : 'Visa Application Intake'; ?></h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'saveApplication']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="ct2_visa_application_id" value="<?= (int) ($ct2ApplicationForm['ct2_visa_application_id'] ?? 0); ?>">
            <label class="ct2-label">Visa Type</label>
            <select class="ct2-select" name="ct2_visa_type_id" required>
                <option value="">Select visa type</option>
                <?php foreach ($ct2VisaTypeSelection as $ct2VisaType): ?>
                    <option value="<?= (int) $ct2VisaType['ct2_visa_type_id']; ?>" <?= ((int) ($ct2ApplicationForm['ct2_visa_type_id'] ?? 0) === (int) $ct2VisaType['ct2_visa_type_id']) ? 'selected' : ''; ?>><?= htmlspecialchars((string) $ct2VisaType['country_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2VisaType['visa_category'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Application Reference</label>
            <input class="ct2-input" name="application_reference" required value="<?= htmlspecialchars((string) ($ct2ApplicationForm['application_reference'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">External Customer ID</label>
            <input class="ct2-input" name="external_customer_id" required value="<?= htmlspecialchars((string) ($ct2ApplicationForm['external_customer_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">External Agent ID</label>
            <input class="ct2-input" name="external_agent_id" value="<?= htmlspecialchars((string) ($ct2ApplicationForm['external_agent_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="ct1 or partner portal" value="<?= htmlspecialchars((string) ($ct2ApplicationForm['source_system'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Status</label>
            <select class="ct2-select" name="status">
                <?php foreach (['draft', 'submitted', 'document_review', 'appointment_scheduled', 'processing', 'approved', 'released', 'rejected', 'cancelled', 'escalated_review'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2ApplicationForm['status'] ?? 'submitted') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Submission Date</label>
            <input class="ct2-input" name="submission_date" type="date" required value="<?= htmlspecialchars((string) ($ct2ApplicationForm['submission_date'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Appointment Date</label>
            <input class="ct2-input" name="appointment_date" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($ct2ApplicationForm['appointment_date'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Embassy Reference</label>
            <input class="ct2-input" name="embassy_reference" value="<?= htmlspecialchars((string) ($ct2ApplicationForm['embassy_reference'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Approval Status</label>
            <select class="ct2-select" name="approval_status">
                <?php foreach (['not_required', 'pending', 'approved', 'rejected'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>" <?= (($ct2ApplicationForm['approval_status'] ?? 'not_required') === $ct2Option) ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Remarks</label>
            <textarea class="ct2-textarea" name="remarks" rows="3"><?= htmlspecialchars((string) ($ct2ApplicationForm['remarks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Application</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Checklist Template Builder</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'saveChecklistTemplate']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Visa Type</label>
            <select class="ct2-select" name="ct2_visa_type_id" required>
                <option value="">Select visa type</option>
                <?php foreach ($ct2VisaTypeSelection as $ct2VisaType): ?>
                    <option value="<?= (int) $ct2VisaType['ct2_visa_type_id']; ?>"><?= htmlspecialchars((string) $ct2VisaType['country_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2VisaType['visa_category'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Item Name</label>
            <input class="ct2-input" name="item_name" required>
            <label class="ct2-label">Description</label>
            <textarea class="ct2-textarea" name="item_description" rows="3"></textarea>
            <label class="ct2-label">File Size Limit (MB)</label>
            <input class="ct2-input" name="file_size_limit_mb" type="number" min="1" value="5">
            <label class="ct2-label">Display Order</label>
            <input class="ct2-input" name="display_order" type="number" min="1" value="1">
            <label class="ct2-label ct2-checkbox-row"><input type="checkbox" name="is_mandatory" checked> Mandatory item</label>
            <label class="ct2-label ct2-checkbox-row"><input type="checkbox" name="requires_original"> Requires original copy</label>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Checklist Template</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Document And Checklist Verification</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'saveDocumentChecklist']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form" enctype="multipart/form-data">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Application</label>
            <select class="ct2-select" name="ct2_visa_application_id" required>
                <option value="">Select application</option>
                <?php foreach ($ct2ApplicationSelection as $ct2Application): ?>
                    <option value="<?= (int) $ct2Application['ct2_visa_application_id']; ?>"><?= htmlspecialchars((string) $ct2Application['application_reference'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2Application['external_customer_id'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Checklist Item</label>
            <select class="ct2-select" name="ct2_application_checklist_id" required>
                <option value="">Select checklist item</option>
                <?php foreach ($ct2ChecklistSelection as $ct2ChecklistItem): ?>
                    <option value="<?= (int) $ct2ChecklistItem['ct2_application_checklist_id']; ?>"><?= htmlspecialchars((string) $ct2ChecklistItem['application_reference'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2ChecklistItem['item_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2ChecklistItem['checklist_status'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Checklist Status</label>
            <select class="ct2-select" name="checklist_status">
                <?php foreach (['pending', 'submitted', 'verified', 'rejected', 'waived'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Upload Document</label>
            <input class="ct2-input" name="ct2_document_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            <label class="ct2-label">Verification Notes</label>
            <textarea class="ct2-textarea" name="verification_notes" rows="3"></textarea>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Checklist Update</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Payment Register</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'savePayment']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Application</label>
            <select class="ct2-select" name="ct2_visa_application_id" required>
                <option value="">Select application</option>
                <?php foreach ($ct2ApplicationSelection as $ct2Application): ?>
                    <option value="<?= (int) $ct2Application['ct2_visa_application_id']; ?>"><?= htmlspecialchars((string) $ct2Application['application_reference'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Payment Reference</label>
            <input class="ct2-input" name="payment_reference" required>
            <label class="ct2-label">External Payment ID</label>
            <input class="ct2-input" name="external_payment_id">
            <label class="ct2-label">Amount</label>
            <input class="ct2-input" name="amount" type="number" min="0.01" step="0.01" value="0.00" required>
            <label class="ct2-label">Currency</label>
            <input class="ct2-input" name="currency" value="PHP">
            <label class="ct2-label">Payment Method</label>
            <input class="ct2-input" name="payment_method" value="Manual">
            <label class="ct2-label">Payment Status</label>
            <select class="ct2-select" name="payment_status">
                <?php foreach (['pending', 'completed', 'refunded', 'voided'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Paid At</label>
            <input class="ct2-input" name="paid_at" type="datetime-local">
            <label class="ct2-label">Source System</label>
            <input class="ct2-input" name="source_system" placeholder="ct1 or cashier">
            <button class="ct2-btn ct2-btn-primary" type="submit">Record Payment</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Notification Log</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'saveNotification']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Application</label>
            <select class="ct2-select" name="ct2_visa_application_id" required>
                <option value="">Select application</option>
                <?php foreach ($ct2ApplicationSelection as $ct2Application): ?>
                    <option value="<?= (int) $ct2Application['ct2_visa_application_id']; ?>"><?= htmlspecialchars((string) $ct2Application['application_reference'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Channel</label>
            <select class="ct2-select" name="notification_channel">
                <?php foreach (['email', 'sms', 'portal', 'manual'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(strtoupper($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Recipient Reference</label>
            <input class="ct2-input" name="recipient_reference" placeholder="email or phone" required>
            <label class="ct2-label">Subject</label>
            <input class="ct2-input" name="notification_subject" required>
            <label class="ct2-label">Message</label>
            <textarea class="ct2-textarea" name="notification_message" rows="4" required></textarea>
            <label class="ct2-label">Delivery Status</label>
            <select class="ct2-select" name="delivery_status">
                <?php foreach (['queued', 'sent', 'failed'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst($ct2Option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Notification</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Case Notes</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'saveNote']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Application</label>
            <select class="ct2-select" name="ct2_visa_application_id" required>
                <option value="">Select application</option>
                <?php foreach ($ct2ApplicationSelection as $ct2Application): ?>
                    <option value="<?= (int) $ct2Application['ct2_visa_application_id']; ?>"><?= htmlspecialchars((string) $ct2Application['application_reference'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Note Type</label>
            <select class="ct2-select" name="note_type">
                <?php foreach (['review', 'client_update', 'risk', 'embassy', 'payment'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ct2Option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Note Body</label>
            <textarea class="ct2-textarea" name="note_body" rows="4" required></textarea>
            <label class="ct2-label">Next Action Date</label>
            <input class="ct2-input" name="next_action_date" type="date">
            <button class="ct2-btn ct2-btn-primary" type="submit">Record Note</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Workflow Guardrails</h3>
        <ul class="ct2-checklist">
            <li>Customer, agent, and payment source records stay external and are linked only by ID.</li>
            <li>Visa uploads now store real files in CT2-managed storage while keeping `ct2_documents` as the shared metadata registry.</li>
            <li>Approval requests are reserved for escalated-review cases or explicit exception handling.</li>
            <li>Payment status is recalculated from completed payment records against the visa type base fee.</li>
        </ul>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Application Snapshot</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Outstanding</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Applications as $ct2Application): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Application['application_reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Application['external_customer_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Application['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Application['payment_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Application['outstanding_item_count']; ?></td>
                        <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'index', 'application_edit_id' => (int) $ct2Application['ct2_visa_application_id']]), ENT_QUOTES, 'UTF-8'); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Applications === []): ?>
                    <tr><td colspan="6">No visa applications recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>Visa Types And Templates</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Visa Type</th>
                    <th>Fee</th>
                    <th>Processing</th>
                    <th>Templates</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2VisaTypes as $ct2VisaType): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2VisaType['country_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2VisaType['visa_category'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= number_format((float) $ct2VisaType['base_fee'], 2); ?></td>
                        <td><?= (int) $ct2VisaType['processing_days']; ?> days</td>
                        <td><?= (int) $ct2VisaType['template_count']; ?></td>
                        <td><a class="ct2-link" href="<?= htmlspecialchars(ct2_url(['module' => 'visa', 'action' => 'index', 'visa_type_edit_id' => (int) $ct2VisaType['ct2_visa_type_id']]), ENT_QUOTES, 'UTF-8'); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2VisaTypes === []): ?>
                    <tr><td colspan="5">No visa types defined yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Country</th>
                    <th>Checklist Item</th>
                    <th>Mandatory</th>
                    <th>Original</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2ChecklistTemplates, 0, 8) as $ct2Template): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Template['country_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Template['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= !empty($ct2Template['is_mandatory']) ? 'Yes' : 'No'; ?></td>
                        <td><?= !empty($ct2Template['requires_original']) ? 'Yes' : 'No'; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2ChecklistTemplates === []): ?>
                    <tr><td colspan="4">No checklist templates defined yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Checklist And Documents</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Application</th>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Document</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2ApplicationChecklist, 0, 10) as $ct2ChecklistItem): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2ChecklistItem['application_reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2ChecklistItem['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2ChecklistItem['checklist_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2ChecklistItem['file_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2ApplicationChecklist === []): ?>
                    <tr><td colspan="4">No application checklist records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Application</th>
                    <th>File</th>
                    <th>Path</th>
                    <th>Size</th>
                    <th>Mime</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2Documents, 0, 10) as $ct2Document): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($ct2Document['application_reference'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Document['file_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Document['file_path'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= number_format(((int) ($ct2Document['file_size_bytes'] ?? 0)) / 1024, 1); ?> KB</td>
                        <td><?= htmlspecialchars((string) $ct2Document['mime_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Documents === []): ?>
                    <tr><td colspan="5">No visa documents registered yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>Payments, Notifications, And Notes</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Application</th>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2Payments, 0, 6) as $ct2Payment): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Payment['application_reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Payment['payment_reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Payment['payment_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= number_format((float) $ct2Payment['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Payments === []): ?>
                    <tr><td colspan="4">No visa payments logged yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Application</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Recipient</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2Notifications, 0, 6) as $ct2Notification): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Notification['application_reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Notification['notification_channel'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Notification['delivery_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Notification['recipient_reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Notifications === []): ?>
                    <tr><td colspan="4">No notification logs recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Application</th>
                    <th>Type</th>
                    <th>Next Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($ct2VisaNotes, 0, 6) as $ct2VisaNote): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2VisaNote['application_reference'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2VisaNote['note_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) ($ct2VisaNote['next_action_date'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2VisaNotes === []): ?>
                    <tr><td colspan="3">No visa case notes recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
