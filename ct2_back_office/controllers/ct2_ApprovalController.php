<?php

declare(strict_types=1);

final class CT2_ApprovalController extends CT2_BaseController
{
    private CT2_ApprovalModel $ct2ApprovalModel;
    private CT2_AgentModel $ct2AgentModel;
    private CT2_SupplierModel $ct2SupplierModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2ApprovalModel = new CT2_ApprovalModel();
        $this->ct2AgentModel = new CT2_AgentModel();
        $this->ct2SupplierModel = new CT2_SupplierModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function index(): void
    {
        ct2_require_permission('approvals.view');

        $this->ct2Render(
            'approvals/ct2_index',
            ['ct2Approvals' => $this->ct2ApprovalModel->getAll()]
        );
    }

    public function decide(): void
    {
        ct2_require_permission('approvals.decide');

        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            ct2_flash('error', 'Invalid request token for approval decision.');
            $this->ct2Redirect(['module' => 'approvals', 'action' => 'index']);
        }

        $ct2WorkflowId = (int) ($_POST['ct2_approval_workflow_id'] ?? 0);
        $ct2Status = (string) ($_POST['approval_status'] ?? 'pending');
        $ct2DecisionNotes = trim((string) ($_POST['decision_notes'] ?? ''));
        $ct2Decision = $this->ct2ApprovalModel->decide(
            $ct2WorkflowId,
            $ct2Status,
            $ct2DecisionNotes,
            (int) ct2_current_user_id()
        );

        if ($ct2Decision === null) {
            ct2_flash('error', 'Approval request not found.');
            $this->ct2Redirect(['module' => 'approvals', 'action' => 'index']);
        }

        if ($ct2Decision['subject_type'] === 'agent') {
            $this->ct2AgentModel->updateApprovalStatus(
                (int) $ct2Decision['subject_id'],
                $ct2Status,
                (int) ct2_current_user_id()
            );
        }

        if ($ct2Decision['subject_type'] === 'supplier') {
            if (!ct2_has_permission('suppliers.approve')) {
                throw new InvalidArgumentException('You do not have permission to approve supplier records.');
            }

            $this->ct2SupplierModel->updateApprovalStatus(
                (int) $ct2Decision['subject_id'],
                $ct2Status,
                (int) ct2_current_user_id()
            );
        }

        $this->ct2AuditLogModel->recordAudit(
            (int) ct2_current_user_id(),
            (string) $ct2Decision['subject_type'],
            (int) $ct2Decision['subject_id'],
            'approvals.decide',
            [
                'workflow_id' => $ct2WorkflowId,
                'approval_status' => $ct2Status,
                'decision_notes' => $ct2DecisionNotes,
            ]
        );

        ct2_flash('success', 'Approval decision recorded.');
        $this->ct2Redirect(['module' => 'approvals', 'action' => 'index']);
    }
}
