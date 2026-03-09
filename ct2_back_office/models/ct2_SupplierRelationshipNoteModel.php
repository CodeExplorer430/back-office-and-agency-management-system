<?php

declare(strict_types=1);

final class CT2_SupplierRelationshipNoteModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT n.*, s.supplier_name, s.supplier_code
             FROM ct2_supplier_relationship_notes AS n
             INNER JOIN ct2_suppliers AS s ON s.ct2_supplier_id = n.ct2_supplier_id
             ORDER BY n.created_at DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_supplier_relationship_notes (
                ct2_supplier_id, note_type, note_title, note_body, next_action_date, created_by
             ) VALUES (
                :ct2_supplier_id, :note_type, :note_title, :note_body, :next_action_date, :created_by
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_supplier_id' => $ct2Payload['ct2_supplier_id'],
                'note_type' => $ct2Payload['note_type'],
                'note_title' => $ct2Payload['note_title'],
                'note_body' => $ct2Payload['note_body'],
                'next_action_date' => $ct2Payload['next_action_date'] ?: null,
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
