<?php

declare(strict_types=1);

final class CT2_VisaController extends CT2_BaseController
{
    private CT2_VisaTypeModel $ct2VisaTypeModel;
    private CT2_VisaApplicationModel $ct2VisaApplicationModel;
    private CT2_VisaChecklistModel $ct2VisaChecklistModel;
    private CT2_DocumentRegistryModel $ct2DocumentRegistryModel;
    private CT2_VisaPaymentModel $ct2VisaPaymentModel;
    private CT2_NotificationLogModel $ct2NotificationLogModel;
    private CT2_VisaNoteModel $ct2VisaNoteModel;
    private CT2_ApprovalModel $ct2ApprovalModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2VisaTypeModel = new CT2_VisaTypeModel();
        $this->ct2VisaApplicationModel = new CT2_VisaApplicationModel();
        $this->ct2VisaChecklistModel = new CT2_VisaChecklistModel();
        $this->ct2DocumentRegistryModel = new CT2_DocumentRegistryModel();
        $this->ct2VisaPaymentModel = new CT2_VisaPaymentModel();
        $this->ct2NotificationLogModel = new CT2_NotificationLogModel();
        $this->ct2VisaNoteModel = new CT2_VisaNoteModel();
        $this->ct2ApprovalModel = new CT2_ApprovalModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function index(): void
    {
        ct2_require_permission('visa.view');

        $ct2Search = trim((string) ($_GET['search'] ?? ''));
        $ct2Status = trim((string) ($_GET['status'] ?? ''));
        $ct2VisaTypeFilter = isset($_GET['ct2_visa_type_id']) ? (int) $_GET['ct2_visa_type_id'] : 0;
        $ct2VisaTypeEditId = isset($_GET['visa_type_edit_id']) ? (int) $_GET['visa_type_edit_id'] : 0;
        $ct2ApplicationEditId = isset($_GET['application_edit_id']) ? (int) $_GET['application_edit_id'] : 0;

        $this->ct2Render(
            'visa/ct2_index',
            [
                'ct2VisaTypes' => $this->ct2VisaTypeModel->getAll(),
                'ct2Applications' => $this->ct2VisaApplicationModel->getAll(
                    $ct2Search !== '' ? $ct2Search : null,
                    $ct2Status !== '' ? $ct2Status : null,
                    $ct2VisaTypeFilter > 0 ? $ct2VisaTypeFilter : null
                ),
                'ct2ChecklistTemplates' => $this->ct2VisaChecklistModel->getTemplateItems(),
                'ct2ApplicationChecklist' => $this->ct2VisaChecklistModel->getApplicationChecklist(),
                'ct2Documents' => $this->ct2DocumentRegistryModel->getVisaDocuments(),
                'ct2Payments' => $this->ct2VisaPaymentModel->getAll(),
                'ct2Notifications' => $this->ct2NotificationLogModel->getAll(),
                'ct2VisaNotes' => $this->ct2VisaNoteModel->getAll(),
                'ct2VisaSummary' => $this->ct2VisaApplicationModel->getSummaryCounts(),
                'ct2VisaTypeSelection' => $this->ct2VisaTypeModel->getAllForSelection(),
                'ct2ApplicationSelection' => $this->ct2VisaApplicationModel->getAllForSelection(),
                'ct2ChecklistSelection' => $this->ct2VisaChecklistModel->getAllForSelection(),
                'ct2VisaTypeForEdit' => $ct2VisaTypeEditId > 0 ? $this->ct2VisaTypeModel->findById($ct2VisaTypeEditId) : null,
                'ct2ApplicationForEdit' => $ct2ApplicationEditId > 0 ? $this->ct2VisaApplicationModel->findById($ct2ApplicationEditId) : null,
                'ct2Search' => $ct2Search,
                'ct2Status' => $ct2Status,
                'ct2VisaTypeFilter' => $ct2VisaTypeFilter,
            ]
        );
    }

    public function saveVisaType(): void
    {
        ct2_require_permission('visa.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'visa_code' => trim((string) ($_POST['visa_code'] ?? '')),
            'country_name' => trim((string) ($_POST['country_name'] ?? '')),
            'visa_category' => trim((string) ($_POST['visa_category'] ?? '')),
            'processing_days' => max(1, (int) ($_POST['processing_days'] ?? 1)),
            'biometrics_required' => isset($_POST['biometrics_required']) ? 1 : 0,
            'validity_period_days' => max(1, (int) ($_POST['validity_period_days'] ?? 1)),
            'base_fee' => number_format((float) ($_POST['base_fee'] ?? 0), 2, '.', ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        foreach (['visa_code', 'country_name', 'visa_category'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required visa type field: ' . $ct2RequiredField);
            }
        }

        $ct2VisaTypeId = isset($_POST['ct2_visa_type_id']) ? (int) $_POST['ct2_visa_type_id'] : 0;
        if ($ct2VisaTypeId > 0) {
            $this->ct2VisaTypeModel->update($ct2VisaTypeId, $ct2Payload);
            $ct2Action = 'visa.type_update';
        } else {
            $ct2VisaTypeId = $this->ct2VisaTypeModel->create($ct2Payload);
            $ct2Action = 'visa.type_create';
        }

        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_type', $ct2VisaTypeId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Visa type saved successfully.');
        $this->ct2Redirect(['module' => 'visa', 'action' => 'index', 'visa_type_edit_id' => $ct2VisaTypeId]);
    }

    public function saveApplication(): void
    {
        ct2_require_permission('visa.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = $this->ct2ValidateApplicationPayload($_POST);
        $ct2UserId = (int) ct2_current_user_id();
        $ct2VisaApplicationId = isset($_POST['ct2_visa_application_id']) ? (int) $_POST['ct2_visa_application_id'] : 0;

        if ($ct2VisaApplicationId > 0) {
            $this->ct2VisaApplicationModel->update($ct2VisaApplicationId, $ct2Payload, $ct2UserId);
            $ct2Action = 'visa.application_update';
        } else {
            $ct2VisaApplicationId = $this->ct2VisaApplicationModel->create($ct2Payload, $ct2UserId);
            $ct2Action = 'visa.application_create';
        }

        $this->ct2VisaChecklistModel->syncChecklistForApplication($ct2VisaApplicationId, $ct2Payload['ct2_visa_type_id']);

        if ($ct2Payload['approval_status'] === 'pending' || $ct2Payload['status'] === 'escalated_review') {
            $this->ct2ApprovalModel->createOrRefreshRequest('visa_application', $ct2VisaApplicationId, $ct2UserId);
        }

        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'visa_application', $ct2VisaApplicationId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Visa application saved successfully.');
        $this->ct2Redirect(['module' => 'visa', 'action' => 'index', 'application_edit_id' => $ct2VisaApplicationId]);
    }

    public function saveChecklistTemplate(): void
    {
        ct2_require_permission('visa.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_visa_type_id' => (int) ($_POST['ct2_visa_type_id'] ?? 0),
            'item_name' => trim((string) ($_POST['item_name'] ?? '')),
            'item_description' => trim((string) ($_POST['item_description'] ?? '')),
            'is_mandatory' => isset($_POST['is_mandatory']) ? 1 : 0,
            'file_size_limit_mb' => max(1, (int) ($_POST['file_size_limit_mb'] ?? 1)),
            'requires_original' => isset($_POST['requires_original']) ? 1 : 0,
            'display_order' => max(1, (int) ($_POST['display_order'] ?? 1)),
        ];

        if ($ct2Payload['ct2_visa_type_id'] < 1 || $ct2Payload['item_name'] === '') {
            throw new InvalidArgumentException('Checklist template requires a visa type and item name.');
        }

        $ct2ChecklistTemplateId = $this->ct2VisaChecklistModel->createTemplateItem($ct2Payload);
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_checklist_template', $ct2ChecklistTemplateId, 'visa.checklist_template_create', $ct2Payload);

        ct2_flash('success', 'Checklist template item saved.');
        $this->ct2Redirect(['module' => 'visa', 'action' => 'index']);
    }

    public function saveDocumentChecklist(): void
    {
        ct2_require_permission('visa.manage');
        $this->assertPostWithCsrf();

        $ct2VisaApplicationId = (int) ($_POST['ct2_visa_application_id'] ?? 0);
        $ct2ApplicationChecklistId = (int) ($_POST['ct2_application_checklist_id'] ?? 0);
        $ct2Payload = [
            'checklist_status' => (string) ($_POST['checklist_status'] ?? 'submitted'),
            'verification_notes' => trim((string) ($_POST['verification_notes'] ?? '')),
            'ct2_document_id' => 0,
        ];

        if ($ct2VisaApplicationId < 1 || $ct2ApplicationChecklistId < 1) {
            throw new InvalidArgumentException('Document verification requires an application and checklist item.');
        }

        $ct2ChecklistApplicationId = $this->ct2VisaChecklistModel->findApplicationIdByChecklistId($ct2ApplicationChecklistId);
        if ($ct2ChecklistApplicationId === null || $ct2ChecklistApplicationId !== $ct2VisaApplicationId) {
            throw new InvalidArgumentException('Checklist item does not match the selected visa application.');
        }

        $ct2FileName = trim((string) ($_POST['file_name'] ?? ''));
        $ct2FilePath = trim((string) ($_POST['file_path'] ?? ''));
        $ct2MimeType = trim((string) ($_POST['mime_type'] ?? ''));

        if ($ct2FileName !== '' || $ct2FilePath !== '' || $ct2MimeType !== '') {
            foreach (['file_name' => $ct2FileName, 'file_path' => $ct2FilePath, 'mime_type' => $ct2MimeType] as $ct2FieldKey => $ct2FieldValue) {
                if ($ct2FieldValue === '') {
                    throw new InvalidArgumentException('Incomplete document metadata. Provide file name, file path, and mime type together.');
                }
            }

            $ct2Payload['ct2_document_id'] = $this->ct2DocumentRegistryModel->create(
                [
                    'entity_type' => 'visa_application',
                    'entity_id' => $ct2VisaApplicationId,
                    'file_name' => $ct2FileName,
                    'file_path' => $ct2FilePath,
                    'mime_type' => $ct2MimeType,
                ],
                (int) ct2_current_user_id()
            );
        }

        $ct2ResolvedApplicationId = $this->ct2VisaChecklistModel->updateApplicationItem($ct2ApplicationChecklistId, $ct2Payload, (int) ct2_current_user_id());
        if ($ct2ResolvedApplicationId === null) {
            throw new InvalidArgumentException('Application checklist item not found.');
        }

        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_application', $ct2ResolvedApplicationId, 'visa.document_checklist_update', $ct2Payload + ['ct2_application_checklist_id' => $ct2ApplicationChecklistId]);

        ct2_flash('success', 'Document and checklist status updated.');
        $this->ct2Redirect(['module' => 'visa', 'action' => 'index']);
    }

    public function savePayment(): void
    {
        ct2_require_permission('visa.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_visa_application_id' => (int) ($_POST['ct2_visa_application_id'] ?? 0),
            'payment_reference' => trim((string) ($_POST['payment_reference'] ?? '')),
            'external_payment_id' => trim((string) ($_POST['external_payment_id'] ?? '')),
            'amount' => number_format((float) ($_POST['amount'] ?? 0), 2, '.', ''),
            'currency' => trim((string) ($_POST['currency'] ?? 'PHP')),
            'payment_method' => trim((string) ($_POST['payment_method'] ?? 'Manual')),
            'payment_status' => (string) ($_POST['payment_status'] ?? 'pending'),
            'paid_at' => trim((string) ($_POST['paid_at'] ?? '')),
            'source_system' => trim((string) ($_POST['source_system'] ?? '')),
        ];

        if ($ct2Payload['ct2_visa_application_id'] < 1 || $ct2Payload['payment_reference'] === '' || (float) $ct2Payload['amount'] <= 0) {
            throw new InvalidArgumentException('Payment logging requires application, reference, and amount.');
        }

        $ct2VisaPaymentId = $this->ct2VisaPaymentModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_payment', $ct2VisaPaymentId, 'visa.payment_create', $ct2Payload);

        ct2_flash('success', 'Visa payment recorded.');
        $this->ct2Redirect(['module' => 'visa', 'action' => 'index']);
    }

    public function saveNotification(): void
    {
        ct2_require_permission('visa.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_visa_application_id' => (int) ($_POST['ct2_visa_application_id'] ?? 0),
            'notification_channel' => (string) ($_POST['notification_channel'] ?? 'email'),
            'recipient_reference' => trim((string) ($_POST['recipient_reference'] ?? '')),
            'notification_subject' => trim((string) ($_POST['notification_subject'] ?? '')),
            'notification_message' => trim((string) ($_POST['notification_message'] ?? '')),
            'delivery_status' => (string) ($_POST['delivery_status'] ?? 'queued'),
        ];

        if ($ct2Payload['ct2_visa_application_id'] < 1 || $ct2Payload['recipient_reference'] === '' || $ct2Payload['notification_subject'] === '' || $ct2Payload['notification_message'] === '') {
            throw new InvalidArgumentException('Notification logging requires application, recipient, subject, and message.');
        }

        $ct2NotificationLogId = $this->ct2NotificationLogModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_notification', $ct2NotificationLogId, 'visa.notification_create', $ct2Payload);

        ct2_flash('success', 'Notification log saved.');
        $this->ct2Redirect(['module' => 'visa', 'action' => 'index']);
    }

    public function saveNote(): void
    {
        ct2_require_permission('visa.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_visa_application_id' => (int) ($_POST['ct2_visa_application_id'] ?? 0),
            'note_type' => (string) ($_POST['note_type'] ?? 'review'),
            'note_body' => trim((string) ($_POST['note_body'] ?? '')),
            'next_action_date' => trim((string) ($_POST['next_action_date'] ?? '')),
        ];

        if ($ct2Payload['ct2_visa_application_id'] < 1 || $ct2Payload['note_body'] === '') {
            throw new InvalidArgumentException('Visa notes require an application and note body.');
        }

        $ct2VisaNoteId = $this->ct2VisaNoteModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_note', $ct2VisaNoteId, 'visa.note_create', $ct2Payload);

        ct2_flash('success', 'Visa case note recorded.');
        $this->ct2Redirect(['module' => 'visa', 'action' => 'index']);
    }

    private function assertPostWithCsrf(): void
    {
        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            ct2_flash('error', 'Invalid request token.');
            $this->ct2Redirect(['module' => 'visa', 'action' => 'index']);
        }
    }

    private function ct2ValidateApplicationPayload(array $ct2Input): array
    {
        $ct2Payload = [
            'ct2_visa_type_id' => (int) ($ct2Input['ct2_visa_type_id'] ?? 0),
            'application_reference' => trim((string) ($ct2Input['application_reference'] ?? '')),
            'external_customer_id' => trim((string) ($ct2Input['external_customer_id'] ?? '')),
            'external_agent_id' => trim((string) ($ct2Input['external_agent_id'] ?? '')),
            'source_system' => trim((string) ($ct2Input['source_system'] ?? '')),
            'status' => (string) ($ct2Input['status'] ?? 'submitted'),
            'submission_date' => (string) ($ct2Input['submission_date'] ?? ''),
            'appointment_date' => trim((string) ($ct2Input['appointment_date'] ?? '')),
            'embassy_reference' => trim((string) ($ct2Input['embassy_reference'] ?? '')),
            'approval_status' => (string) ($ct2Input['approval_status'] ?? 'not_required'),
            'remarks' => trim((string) ($ct2Input['remarks'] ?? '')),
        ];

        if ($ct2Payload['ct2_visa_type_id'] < 1) {
            throw new InvalidArgumentException('Visa application requires a visa type.');
        }

        foreach (['application_reference', 'external_customer_id', 'submission_date'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required application field: ' . $ct2RequiredField);
            }
        }

        return $ct2Payload;
    }
}
