<?php

declare(strict_types=1);

final class CT2_VisaChecklistModel extends CT2_BaseModel
{
    public function findApplicationIdByChecklistId(int $ct2ApplicationChecklistId): ?int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT ct2_visa_application_id
             FROM ct2_application_checklist
             WHERE ct2_application_checklist_id = :ct2_application_checklist_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_application_checklist_id' => $ct2ApplicationChecklistId]);
        $ct2Checklist = $ct2Statement->fetch();

        return $ct2Checklist !== false ? (int) $ct2Checklist['ct2_visa_application_id'] : null;
    }

    public function getChecklistUploadContext(int $ct2ApplicationChecklistId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT
                ac.ct2_visa_application_id,
                ci.file_size_limit_mb,
                ci.item_name
             FROM ct2_application_checklist AS ac
             INNER JOIN ct2_visa_checklist_items AS ci ON ci.ct2_visa_checklist_item_id = ac.ct2_visa_checklist_item_id
             WHERE ac.ct2_application_checklist_id = :ct2_application_checklist_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_application_checklist_id' => $ct2ApplicationChecklistId]);
        $ct2Context = $ct2Statement->fetch();

        return $ct2Context !== false ? $ct2Context : null;
    }

    public function getTemplateItems(?int $ct2VisaTypeId = null): array
    {
        $ct2Sql = 'SELECT ci.*, vt.visa_code, vt.country_name, vt.visa_category
            FROM ct2_visa_checklist_items AS ci
            INNER JOIN ct2_visa_types AS vt ON vt.ct2_visa_type_id = ci.ct2_visa_type_id';
        $ct2Parameters = [];

        if ($ct2VisaTypeId !== null && $ct2VisaTypeId > 0) {
            $ct2Sql .= ' WHERE ci.ct2_visa_type_id = :ct2_visa_type_id';
            $ct2Parameters['ct2_visa_type_id'] = $ct2VisaTypeId;
        }

        $ct2Sql .= ' ORDER BY vt.country_name ASC, ci.display_order ASC, ci.item_name ASC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function createTemplateItem(array $ct2Payload): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_visa_checklist_items (
                ct2_visa_type_id, item_name, item_description, is_mandatory,
                file_size_limit_mb, requires_original, display_order
            ) VALUES (
                :ct2_visa_type_id, :item_name, :item_description, :is_mandatory,
                :file_size_limit_mb, :requires_original, :display_order
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_type_id' => $ct2Payload['ct2_visa_type_id'],
                'item_name' => $ct2Payload['item_name'],
                'item_description' => $ct2Payload['item_description'] ?: null,
                'is_mandatory' => $ct2Payload['is_mandatory'],
                'file_size_limit_mb' => $ct2Payload['file_size_limit_mb'],
                'requires_original' => $ct2Payload['requires_original'],
                'display_order' => $ct2Payload['display_order'],
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function getApplicationChecklist(?int $ct2VisaApplicationId = null): array
    {
        $ct2Sql = 'SELECT ac.*,
                va.application_reference,
                ci.item_name,
                ci.is_mandatory,
                ci.requires_original,
                d.file_name,
                d.file_path,
                d.mime_type,
                d.file_size_bytes
            FROM ct2_application_checklist AS ac
            INNER JOIN ct2_visa_applications AS va ON va.ct2_visa_application_id = ac.ct2_visa_application_id
            INNER JOIN ct2_visa_checklist_items AS ci ON ci.ct2_visa_checklist_item_id = ac.ct2_visa_checklist_item_id
            LEFT JOIN ct2_documents AS d ON d.ct2_document_id = ac.ct2_document_id';
        $ct2Parameters = [];

        if ($ct2VisaApplicationId !== null && $ct2VisaApplicationId > 0) {
            $ct2Sql .= ' WHERE ac.ct2_visa_application_id = :ct2_visa_application_id';
            $ct2Parameters['ct2_visa_application_id'] = $ct2VisaApplicationId;
        }

        $ct2Sql .= ' ORDER BY va.created_at DESC, ci.display_order ASC, ac.ct2_application_checklist_id ASC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ac.ct2_application_checklist_id, va.application_reference, ci.item_name, ac.checklist_status
             FROM ct2_application_checklist AS ac
             INNER JOIN ct2_visa_applications AS va ON va.ct2_visa_application_id = ac.ct2_visa_application_id
             INNER JOIN ct2_visa_checklist_items AS ci ON ci.ct2_visa_checklist_item_id = ac.ct2_visa_checklist_item_id
             ORDER BY va.created_at DESC, ci.display_order ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function syncChecklistForApplication(int $ct2VisaApplicationId, int $ct2VisaTypeId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_application_checklist (
                ct2_visa_application_id, ct2_visa_checklist_item_id, checklist_status
            )
            SELECT :ct2_visa_application_id, ci.ct2_visa_checklist_item_id, "pending"
            FROM ct2_visa_checklist_items AS ci
            WHERE ci.ct2_visa_type_id = :ct2_visa_type_id
              AND NOT EXISTS (
                  SELECT 1
                  FROM ct2_application_checklist AS ac
                  WHERE ac.ct2_visa_application_id = :ct2_visa_application_id_check
                    AND ac.ct2_visa_checklist_item_id = ci.ct2_visa_checklist_item_id
              )'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2VisaApplicationId,
                'ct2_visa_type_id' => $ct2VisaTypeId,
                'ct2_visa_application_id_check' => $ct2VisaApplicationId,
            ]
        );

        $this->refreshApplicationState($ct2VisaApplicationId);
    }

    public function updateApplicationItem(int $ct2ApplicationChecklistId, array $ct2Payload, int $ct2UserId): ?int
    {
        $ct2VisaApplicationId = $this->findApplicationIdByChecklistId($ct2ApplicationChecklistId);
        if ($ct2VisaApplicationId === null) {
            return null;
        }

        $ct2VerifiedBy = null;
        $ct2VerifiedAt = null;
        if (in_array($ct2Payload['checklist_status'], ['verified', 'rejected', 'waived'], true)) {
            $ct2VerifiedBy = $ct2UserId;
            $ct2VerifiedAt = date('Y-m-d H:i:s');
        }

        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_application_checklist
             SET checklist_status = :checklist_status,
                 verification_notes = :verification_notes,
                 ct2_document_id = :ct2_document_id,
                 verified_by = :verified_by,
                 verified_at = :verified_at
             WHERE ct2_application_checklist_id = :ct2_application_checklist_id'
        );
        $ct2Statement->execute(
            [
                'ct2_application_checklist_id' => $ct2ApplicationChecklistId,
                'checklist_status' => $ct2Payload['checklist_status'],
                'verification_notes' => $ct2Payload['verification_notes'] ?: null,
                'ct2_document_id' => $ct2Payload['ct2_document_id'] > 0 ? $ct2Payload['ct2_document_id'] : null,
                'verified_by' => $ct2VerifiedBy,
                'verified_at' => $ct2VerifiedAt,
            ]
        );

        $this->refreshApplicationState($ct2VisaApplicationId);

        return $ct2VisaApplicationId;
    }

    public function refreshApplicationState(int $ct2VisaApplicationId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT
                SUM(CASE WHEN ci.is_mandatory = 1 AND ac.checklist_status NOT IN ("verified", "waived") THEN 1 ELSE 0 END) AS outstanding_item_count
             FROM ct2_application_checklist AS ac
             INNER JOIN ct2_visa_checklist_items AS ci ON ci.ct2_visa_checklist_item_id = ac.ct2_visa_checklist_item_id
             WHERE ac.ct2_visa_application_id = :ct2_visa_application_id'
        );
        $ct2Statement->execute(['ct2_visa_application_id' => $ct2VisaApplicationId]);
        $ct2Summary = $ct2Statement->fetch() ?: ['outstanding_item_count' => 0];
        $ct2OutstandingItemCount = (int) ($ct2Summary['outstanding_item_count'] ?? 0);

        $ct2ApplicationModel = new CT2_VisaApplicationModel();
        $ct2ApplicationModel->updateChecklistState(
            $ct2VisaApplicationId,
            $ct2OutstandingItemCount,
            $ct2OutstandingItemCount === 0
        );
    }
}
