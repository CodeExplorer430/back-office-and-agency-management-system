<?php

declare(strict_types=1);

final class CT2_ApprovalModel extends CT2_BaseModel
{
    public function createOrRefreshRequest(string $ct2SubjectType, int $ct2SubjectId, int $ct2RequestedBy): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT ct2_approval_workflow_id
             FROM ct2_approval_workflows
             WHERE subject_type = :subject_type
               AND subject_id = :subject_id
             LIMIT 1'
        );
        $ct2Statement->execute(
            [
                'subject_type' => $ct2SubjectType,
                'subject_id' => $ct2SubjectId,
            ]
        );
        $ct2Existing = $ct2Statement->fetch();

        if ($ct2Existing !== false) {
            $ct2Update = $this->ct2Pdo->prepare(
                'UPDATE ct2_approval_workflows
                 SET approval_status = "pending",
                     requested_by = :requested_by,
                     approver_user_id = NULL,
                     requested_at = NOW(),
                     decided_at = NULL,
                     decision_notes = NULL
                 WHERE ct2_approval_workflow_id = :ct2_approval_workflow_id'
            );
            $ct2Update->execute(
                [
                    'requested_by' => $ct2RequestedBy,
                    'ct2_approval_workflow_id' => $ct2Existing['ct2_approval_workflow_id'],
                ]
            );
            return;
        }

        $ct2Insert = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_approval_workflows (
                subject_type, subject_id, requested_by, approval_status
             ) VALUES (
                :subject_type, :subject_id, :requested_by, "pending"
             )'
        );
        $ct2Insert->execute(
            [
                'subject_type' => $ct2SubjectType,
                'subject_id' => $ct2SubjectId,
                'requested_by' => $ct2RequestedBy,
            ]
        );
    }

    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT aw.*, u.display_name AS requested_by_name
             FROM ct2_approval_workflows AS aw
             LEFT JOIN ct2_users AS u ON u.ct2_user_id = aw.requested_by
             ORDER BY aw.requested_at DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function decide(int $ct2WorkflowId, string $ct2Status, string $ct2Notes, int $ct2ApproverUserId): ?array
    {
        $ct2Find = $this->ct2Pdo->prepare(
            'SELECT subject_type, subject_id
             FROM ct2_approval_workflows
             WHERE ct2_approval_workflow_id = :ct2_approval_workflow_id
             LIMIT 1'
        );
        $ct2Find->execute(['ct2_approval_workflow_id' => $ct2WorkflowId]);
        $ct2Workflow = $ct2Find->fetch();

        if ($ct2Workflow === false) {
            return null;
        }

        $ct2Update = $this->ct2Pdo->prepare(
            'UPDATE ct2_approval_workflows
             SET approval_status = :approval_status,
                 approver_user_id = :approver_user_id,
                 decided_at = NOW(),
                 decision_notes = :decision_notes
             WHERE ct2_approval_workflow_id = :ct2_approval_workflow_id'
        );
        $ct2Update->execute(
            [
                'approval_status' => $ct2Status,
                'approver_user_id' => $ct2ApproverUserId,
                'decision_notes' => $ct2Notes !== '' ? $ct2Notes : null,
                'ct2_approval_workflow_id' => $ct2WorkflowId,
            ]
        );

        return $ct2Workflow;
    }
}
