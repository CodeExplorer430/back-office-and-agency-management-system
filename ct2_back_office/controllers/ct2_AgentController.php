<?php

declare(strict_types=1);

final class CT2_AgentController extends CT2_BaseController
{
    private CT2_AgentModel $ct2AgentModel;
    private CT2_StaffModel $ct2StaffModel;
    private CT2_AssignmentModel $ct2AssignmentModel;
    private CT2_ApprovalModel $ct2ApprovalModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2AgentModel = new CT2_AgentModel();
        $this->ct2StaffModel = new CT2_StaffModel();
        $this->ct2AssignmentModel = new CT2_AssignmentModel();
        $this->ct2ApprovalModel = new CT2_ApprovalModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function index(): void
    {
        ct2_require_permission('agents.view');

        $ct2Search = trim((string) ($_GET['search'] ?? ''));
        $ct2EditId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : null;

        $this->ct2Render(
            'agents/ct2_index',
            [
                'ct2Agents' => $this->ct2AgentModel->getAll($ct2Search !== '' ? $ct2Search : null),
                'ct2AgentForEdit' => $ct2EditId !== null ? $this->ct2AgentModel->findById($ct2EditId) : null,
                'ct2Assignments' => $this->ct2AssignmentModel->getAll(),
                'ct2StaffOptions' => $this->ct2StaffModel->getAllForAssignments(),
                'ct2Search' => $ct2Search,
            ]
        );
    }

    public function save(): void
    {
        ct2_require_permission('agents.manage');

        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            ct2_flash('error', 'Invalid request token for agent save.');
            $this->ct2Redirect(['module' => 'agents', 'action' => 'index']);
        }

        $ct2Payload = $this->ct2ValidatePayload($_POST);
        $ct2AgentId = isset($_POST['ct2_agent_id']) ? (int) $_POST['ct2_agent_id'] : 0;
        $ct2UserId = (int) ct2_current_user_id();

        if ($ct2AgentId > 0) {
            $this->ct2AgentModel->update($ct2AgentId, $ct2Payload, $ct2UserId);
            $ct2Action = 'agents.update';
        } else {
            $ct2AgentId = $this->ct2AgentModel->create($ct2Payload, $ct2UserId);
            $ct2Action = 'agents.create';
        }

        $this->ct2ApprovalModel->createOrRefreshRequest('agent', $ct2AgentId, $ct2UserId);
        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'agent', $ct2AgentId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Agent profile saved successfully.');
        $this->ct2Redirect(['module' => 'agents', 'action' => 'index']);
    }

    public function assign(): void
    {
        ct2_require_permission('assignments.manage');

        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            ct2_flash('error', 'Invalid request token for assignment save.');
            $this->ct2Redirect(['module' => 'agents', 'action' => 'index']);
        }

        $ct2Payload = [
            'ct2_agent_id' => (int) ($_POST['ct2_agent_id'] ?? 0),
            'ct2_staff_id' => (int) ($_POST['ct2_staff_id'] ?? 0),
            'assignment_role' => trim((string) ($_POST['assignment_role'] ?? '')),
            'assignment_status' => (string) ($_POST['assignment_status'] ?? 'active'),
            'start_date' => (string) ($_POST['start_date'] ?? date('Y-m-d')),
            'end_date' => trim((string) ($_POST['end_date'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if ($ct2Payload['ct2_agent_id'] < 1 || $ct2Payload['ct2_staff_id'] < 1 || $ct2Payload['assignment_role'] === '') {
            ct2_flash('error', 'Agent assignment requires an agent, staff member, and assignment role.');
            $this->ct2Redirect(['module' => 'agents', 'action' => 'index']);
        }

        $this->ct2AssignmentModel->create($ct2Payload);
        $this->ct2AuditLogModel->recordAudit(
            (int) ct2_current_user_id(),
            'assignment',
            $ct2Payload['ct2_agent_id'],
            'assignments.create',
            $ct2Payload
        );

        ct2_flash('success', 'Agent assignment saved successfully.');
        $this->ct2Redirect(['module' => 'agents', 'action' => 'index']);
    }

    private function ct2ValidatePayload(array $ct2Input): array
    {
        $ct2Payload = [
            'agent_code' => trim((string) ($ct2Input['agent_code'] ?? '')),
            'agency_name' => trim((string) ($ct2Input['agency_name'] ?? '')),
            'contact_person' => trim((string) ($ct2Input['contact_person'] ?? '')),
            'email' => trim((string) ($ct2Input['email'] ?? '')),
            'phone' => trim((string) ($ct2Input['phone'] ?? '')),
            'region' => trim((string) ($ct2Input['region'] ?? '')),
            'commission_rate' => number_format((float) ($ct2Input['commission_rate'] ?? 0), 2, '.', ''),
            'support_level' => (string) ($ct2Input['support_level'] ?? 'standard'),
            'approval_status' => (string) ($ct2Input['approval_status'] ?? 'pending'),
            'active_status' => (string) ($ct2Input['active_status'] ?? 'active'),
            'external_booking_id' => trim((string) ($ct2Input['external_booking_id'] ?? '')),
            'external_customer_id' => trim((string) ($ct2Input['external_customer_id'] ?? '')),
            'external_payment_id' => trim((string) ($ct2Input['external_payment_id'] ?? '')),
            'source_system' => trim((string) ($ct2Input['source_system'] ?? '')),
        ];

        foreach (['agent_code', 'agency_name', 'contact_person', 'email', 'phone', 'region'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required agent field: ' . $ct2RequiredField);
            }
        }

        return $ct2Payload;
    }
}
