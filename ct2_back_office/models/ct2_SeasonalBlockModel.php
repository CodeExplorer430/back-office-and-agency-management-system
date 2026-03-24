<?php

declare(strict_types=1);

final class CT2_SeasonalBlockModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT b.*, r.resource_name
             FROM ct2_seasonal_blocks AS b
             INNER JOIN ct2_inventory_resources AS r ON r.ct2_resource_id = b.ct2_resource_id
             ORDER BY b.created_at DESC, b.ct2_block_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_seasonal_blocks (
                ct2_resource_id, start_date, end_date, reason, block_type, created_by
             ) VALUES (
                :ct2_resource_id, :start_date, :end_date, :reason, :block_type, :created_by
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_resource_id' => $ct2Payload['ct2_resource_id'],
                'start_date' => $ct2Payload['start_date'],
                'end_date' => $ct2Payload['end_date'],
                'reason' => $ct2Payload['reason'],
                'block_type' => $ct2Payload['block_type'],
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
