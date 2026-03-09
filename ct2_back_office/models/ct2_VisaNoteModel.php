<?php

declare(strict_types=1);

final class CT2_VisaNoteModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT vn.*, va.application_reference
             FROM ct2_visa_notes AS vn
             INNER JOIN ct2_visa_applications AS va ON va.ct2_visa_application_id = vn.ct2_visa_application_id
             ORDER BY vn.created_at DESC, vn.ct2_visa_note_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_visa_notes (
                ct2_visa_application_id, note_type, note_body, next_action_date, created_by
            ) VALUES (
                :ct2_visa_application_id, :note_type, :note_body, :next_action_date, :created_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2Payload['ct2_visa_application_id'],
                'note_type' => $ct2Payload['note_type'],
                'note_body' => $ct2Payload['note_body'],
                'next_action_date' => $ct2Payload['next_action_date'] !== '' ? $ct2Payload['next_action_date'] : null,
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
