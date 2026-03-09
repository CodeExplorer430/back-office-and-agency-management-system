<?php

declare(strict_types=1);

final class CT2_SupplierController extends CT2_BaseController
{
    private CT2_SupplierModel $ct2SupplierModel;
    private CT2_SupplierOnboardingModel $ct2SupplierOnboardingModel;
    private CT2_SupplierContractModel $ct2SupplierContractModel;
    private CT2_SupplierKpiModel $ct2SupplierKpiModel;
    private CT2_SupplierRelationshipNoteModel $ct2SupplierRelationshipNoteModel;
    private CT2_ApprovalModel $ct2ApprovalModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2SupplierModel = new CT2_SupplierModel();
        $this->ct2SupplierOnboardingModel = new CT2_SupplierOnboardingModel();
        $this->ct2SupplierContractModel = new CT2_SupplierContractModel();
        $this->ct2SupplierKpiModel = new CT2_SupplierKpiModel();
        $this->ct2SupplierRelationshipNoteModel = new CT2_SupplierRelationshipNoteModel();
        $this->ct2ApprovalModel = new CT2_ApprovalModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function index(): void
    {
        ct2_require_permission('suppliers.view');

        $ct2Search = trim((string) ($_GET['search'] ?? ''));
        $ct2EditId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
        $ct2SupplierForEdit = $ct2EditId > 0 ? $this->ct2SupplierModel->findById($ct2EditId) : null;
        $ct2SupplierId = $ct2SupplierForEdit !== null
            ? (int) $ct2SupplierForEdit['ct2_supplier_id']
            : (int) ($_GET['supplier_id'] ?? 0);

        $this->ct2Render(
            'suppliers/ct2_index',
            [
                'ct2Suppliers' => $this->ct2SupplierModel->getAll($ct2Search !== '' ? $ct2Search : null),
                'ct2SupplierForEdit' => $ct2SupplierForEdit,
                'ct2SupplierSelection' => $this->ct2SupplierModel->getAllForSelection(),
                'ct2SupplierContacts' => $this->ct2SupplierModel->getPrimaryContacts(),
                'ct2OnboardingRecords' => $this->ct2SupplierOnboardingModel->getAll(),
                'ct2Contracts' => $this->ct2SupplierContractModel->getAll(),
                'ct2Kpis' => $this->ct2SupplierKpiModel->getAll(),
                'ct2RelationshipNotes' => $this->ct2SupplierRelationshipNoteModel->getAll(),
                'ct2SelectedSupplierId' => $ct2SupplierId,
                'ct2OnboardingForSelectedSupplier' => $ct2SupplierId > 0 ? $this->ct2SupplierOnboardingModel->findBySupplierId($ct2SupplierId) : null,
                'ct2Search' => $ct2Search,
            ]
        );
    }

    public function save(): void
    {
        ct2_require_permission('suppliers.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = $this->ct2ValidateSupplierPayload($_POST);
        $ct2UserId = (int) ct2_current_user_id();
        $ct2SupplierId = isset($_POST['ct2_supplier_id']) ? (int) $_POST['ct2_supplier_id'] : 0;

        if ($ct2SupplierId > 0) {
            $this->ct2SupplierModel->update($ct2SupplierId, $ct2Payload, $ct2UserId);
            $ct2Action = 'suppliers.update';
        } else {
            $ct2SupplierId = $this->ct2SupplierModel->create($ct2Payload, $ct2UserId);
            $ct2Action = 'suppliers.create';
        }

        $this->ct2ApprovalModel->createOrRefreshRequest('supplier', $ct2SupplierId, $ct2UserId);
        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'supplier', $ct2SupplierId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Supplier profile saved successfully.');
        $this->ct2Redirect(['module' => 'suppliers', 'action' => 'index', 'edit_id' => $ct2SupplierId]);
    }

    public function saveOnboarding(): void
    {
        ct2_require_permission('suppliers.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_supplier_id' => (int) ($_POST['ct2_supplier_id'] ?? 0),
            'checklist_status' => (string) ($_POST['checklist_status'] ?? 'not_started'),
            'documents_status' => (string) ($_POST['documents_status'] ?? 'missing'),
            'compliance_status' => (string) ($_POST['compliance_status'] ?? 'pending'),
            'review_notes' => trim((string) ($_POST['review_notes'] ?? '')),
            'blocked_reason' => trim((string) ($_POST['blocked_reason'] ?? '')),
            'target_go_live_date' => trim((string) ($_POST['target_go_live_date'] ?? '')),
            'completed_at' => trim((string) ($_POST['completed_at'] ?? '')),
        ];

        if ($ct2Payload['ct2_supplier_id'] < 1) {
            throw new InvalidArgumentException('Supplier onboarding requires a supplier selection.');
        }

        $this->ct2SupplierOnboardingModel->upsert($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'supplier', $ct2Payload['ct2_supplier_id'], 'suppliers.onboarding_update', $ct2Payload);

        ct2_flash('success', 'Supplier onboarding record updated.');
        $this->ct2Redirect(['module' => 'suppliers', 'action' => 'index', 'supplier_id' => $ct2Payload['ct2_supplier_id']]);
    }

    public function saveContract(): void
    {
        ct2_require_permission('suppliers.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_supplier_id' => (int) ($_POST['ct2_supplier_id'] ?? 0),
            'contract_code' => trim((string) ($_POST['contract_code'] ?? '')),
            'contract_title' => trim((string) ($_POST['contract_title'] ?? '')),
            'effective_date' => (string) ($_POST['effective_date'] ?? ''),
            'expiry_date' => (string) ($_POST['expiry_date'] ?? ''),
            'renewal_status' => (string) ($_POST['renewal_status'] ?? 'not_started'),
            'contract_status' => (string) ($_POST['contract_status'] ?? 'draft'),
            'clause_summary' => trim((string) ($_POST['clause_summary'] ?? '')),
            'mock_signature_status' => (string) ($_POST['mock_signature_status'] ?? 'pending'),
            'finance_handoff_status' => (string) ($_POST['finance_handoff_status'] ?? 'not_started'),
        ];

        if ($ct2Payload['ct2_supplier_id'] < 1) {
            throw new InvalidArgumentException('Supplier contract requires a supplier selection.');
        }

        foreach (['contract_code', 'contract_title', 'effective_date', 'expiry_date'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Supplier contract requires: ' . $ct2RequiredField);
            }
        }

        $ct2ContractId = $this->ct2SupplierContractModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'supplier_contract', $ct2ContractId, 'suppliers.contract_create', $ct2Payload);

        ct2_flash('success', 'Supplier contract registered.');
        $this->ct2Redirect(['module' => 'suppliers', 'action' => 'index', 'supplier_id' => $ct2Payload['ct2_supplier_id']]);
    }

    public function saveKpi(): void
    {
        ct2_require_permission('suppliers.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_supplier_id' => (int) ($_POST['ct2_supplier_id'] ?? 0),
            'measurement_date' => (string) ($_POST['measurement_date'] ?? ''),
            'service_score' => number_format((float) ($_POST['service_score'] ?? 0), 2, '.', ''),
            'delivery_score' => number_format((float) ($_POST['delivery_score'] ?? 0), 2, '.', ''),
            'compliance_score' => number_format((float) ($_POST['compliance_score'] ?? 0), 2, '.', ''),
            'responsiveness_score' => number_format((float) ($_POST['responsiveness_score'] ?? 0), 2, '.', ''),
            'risk_flag' => (string) ($_POST['risk_flag'] ?? 'none'),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if ($ct2Payload['ct2_supplier_id'] < 1 || $ct2Payload['measurement_date'] === '') {
            throw new InvalidArgumentException('Supplier KPI entry requires a supplier and measurement date.');
        }

        $ct2KpiId = $this->ct2SupplierKpiModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'supplier_kpi', $ct2KpiId, 'suppliers.kpi_create', $ct2Payload);

        ct2_flash('success', 'Supplier KPI measurement saved.');
        $this->ct2Redirect(['module' => 'suppliers', 'action' => 'index', 'supplier_id' => $ct2Payload['ct2_supplier_id']]);
    }

    public function saveNote(): void
    {
        ct2_require_permission('suppliers.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_supplier_id' => (int) ($_POST['ct2_supplier_id'] ?? 0),
            'note_type' => (string) ($_POST['note_type'] ?? 'communication'),
            'note_title' => trim((string) ($_POST['note_title'] ?? '')),
            'note_body' => trim((string) ($_POST['note_body'] ?? '')),
            'next_action_date' => trim((string) ($_POST['next_action_date'] ?? '')),
        ];

        if ($ct2Payload['ct2_supplier_id'] < 1 || $ct2Payload['note_title'] === '' || $ct2Payload['note_body'] === '') {
            throw new InvalidArgumentException('Relationship notes require a supplier, title, and note body.');
        }

        $ct2NoteId = $this->ct2SupplierRelationshipNoteModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'supplier_note', $ct2NoteId, 'suppliers.note_create', $ct2Payload);

        ct2_flash('success', 'Supplier relationship note recorded.');
        $this->ct2Redirect(['module' => 'suppliers', 'action' => 'index', 'supplier_id' => $ct2Payload['ct2_supplier_id']]);
    }

    private function assertPostWithCsrf(): void
    {
        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            ct2_flash('error', 'Invalid request token.');
            $this->ct2Redirect(['module' => 'suppliers', 'action' => 'index']);
        }
    }

    private function ct2ValidateSupplierPayload(array $ct2Input): array
    {
        $ct2Payload = [
            'supplier_code' => trim((string) ($ct2Input['supplier_code'] ?? '')),
            'supplier_name' => trim((string) ($ct2Input['supplier_name'] ?? '')),
            'supplier_type' => (string) ($ct2Input['supplier_type'] ?? 'supplier'),
            'primary_contact_name' => trim((string) ($ct2Input['primary_contact_name'] ?? '')),
            'contact_role_title' => trim((string) ($ct2Input['contact_role_title'] ?? 'Account Manager')),
            'email' => trim((string) ($ct2Input['email'] ?? '')),
            'phone' => trim((string) ($ct2Input['phone'] ?? '')),
            'service_category' => trim((string) ($ct2Input['service_category'] ?? '')),
            'support_tier' => (string) ($ct2Input['support_tier'] ?? 'standard'),
            'approval_status' => (string) ($ct2Input['approval_status'] ?? 'pending'),
            'onboarding_status' => (string) ($ct2Input['onboarding_status'] ?? 'draft'),
            'active_status' => (string) ($ct2Input['active_status'] ?? 'active'),
            'risk_level' => (string) ($ct2Input['risk_level'] ?? 'low'),
            'internal_owner_user_id' => (int) ($ct2Input['internal_owner_user_id'] ?? 0),
            'external_supplier_id' => trim((string) ($ct2Input['external_supplier_id'] ?? '')),
            'source_system' => trim((string) ($ct2Input['source_system'] ?? '')),
        ];

        foreach (['supplier_code', 'supplier_name', 'primary_contact_name', 'email', 'phone', 'service_category'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required supplier field: ' . $ct2RequiredField);
            }
        }

        return $ct2Payload;
    }
}
