<?php

declare(strict_types=1);

final class CT2_DocumentRegistryModel extends CT2_BaseModel
{
    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_documents (
                entity_type, entity_id, file_name, file_path, mime_type, file_size_bytes, uploaded_by
            ) VALUES (
                :entity_type, :entity_id, :file_name, :file_path, :mime_type, :file_size_bytes, :uploaded_by
            )'
        );
        $ct2Statement->execute(
            [
                'entity_type' => $ct2Payload['entity_type'],
                'entity_id' => $ct2Payload['entity_id'],
                'file_name' => $ct2Payload['file_name'],
                'file_path' => $ct2Payload['file_path'],
                'mime_type' => $ct2Payload['mime_type'],
                'file_size_bytes' => $ct2Payload['file_size_bytes'] ?? 0,
                'uploaded_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function getVisaDocuments(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT d.*,
                va.application_reference
             FROM ct2_documents AS d
             LEFT JOIN ct2_visa_applications AS va
                ON va.ct2_visa_application_id = d.entity_id
               AND d.entity_type = "visa_application"
             WHERE d.entity_type = "visa_application"
             ORDER BY d.uploaded_at DESC, d.ct2_document_id DESC'
        );

        return $ct2Statement->fetchAll();
    }
}
