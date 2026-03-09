<?php

declare(strict_types=1);

final class CT2_SupplierOnboardingModel extends CT2_BaseModel
{
    public function findBySupplierId(int $ct2SupplierId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_supplier_onboarding
             WHERE ct2_supplier_id = :ct2_supplier_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_supplier_id' => $ct2SupplierId]);
        $ct2Record = $ct2Statement->fetch();

        return $ct2Record !== false ? $ct2Record : null;
    }

    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT o.*, s.supplier_name, s.supplier_code
             FROM ct2_supplier_onboarding AS o
             INNER JOIN ct2_suppliers AS s ON s.ct2_supplier_id = o.ct2_supplier_id
             ORDER BY o.updated_at DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function upsert(array $ct2Payload, int $ct2UserId): void
    {
        $ct2Existing = $this->findBySupplierId((int) $ct2Payload['ct2_supplier_id']);

        if ($ct2Existing !== null) {
            $ct2Statement = $this->ct2Pdo->prepare(
                'UPDATE ct2_supplier_onboarding
                 SET checklist_status = :checklist_status,
                     documents_status = :documents_status,
                     compliance_status = :compliance_status,
                     review_notes = :review_notes,
                     blocked_reason = :blocked_reason,
                     target_go_live_date = :target_go_live_date,
                     completed_at = :completed_at,
                     updated_by = :updated_by
                 WHERE ct2_supplier_id = :ct2_supplier_id'
            );
        } else {
            $ct2Statement = $this->ct2Pdo->prepare(
                'INSERT INTO ct2_supplier_onboarding (
                    ct2_supplier_id, checklist_status, documents_status, compliance_status,
                    review_notes, blocked_reason, target_go_live_date, completed_at, updated_by
                 ) VALUES (
                    :ct2_supplier_id, :checklist_status, :documents_status, :compliance_status,
                    :review_notes, :blocked_reason, :target_go_live_date, :completed_at, :updated_by
                 )'
            );
        }

        $ct2Statement->execute(
            [
                'ct2_supplier_id' => $ct2Payload['ct2_supplier_id'],
                'checklist_status' => $ct2Payload['checklist_status'],
                'documents_status' => $ct2Payload['documents_status'],
                'compliance_status' => $ct2Payload['compliance_status'],
                'review_notes' => $ct2Payload['review_notes'] ?: null,
                'blocked_reason' => $ct2Payload['blocked_reason'] ?: null,
                'target_go_live_date' => $ct2Payload['target_go_live_date'] ?: null,
                'completed_at' => $ct2Payload['completed_at'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );
    }
}
