<?php

declare(strict_types=1);

final class CT2_ResourceModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Search = null): array
    {
        $ct2Sql = 'SELECT r.*, s.supplier_name,
                COALESCE((
                    SELECT SUM(a.reserved_units)
                    FROM ct2_resource_allocations AS a
                    WHERE a.ct2_resource_id = r.ct2_resource_id
                      AND a.allocation_status <> "released"
                ), 0) AS reserved_units
            FROM ct2_inventory_resources AS r
            INNER JOIN ct2_suppliers AS s ON s.ct2_supplier_id = r.ct2_supplier_id';
        $ct2Parameters = [];

        if ($ct2Search !== null && $ct2Search !== '') {
            $ct2Sql .= ' WHERE r.resource_name LIKE :search
                OR r.resource_type LIKE :search
                OR s.supplier_name LIKE :search';
            $ct2Parameters['search'] = '%' . $ct2Search . '%';
        }

        $ct2Sql .= ' ORDER BY r.created_at DESC';
        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2ResourceId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_inventory_resources
             WHERE ct2_resource_id = :ct2_resource_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_resource_id' => $ct2ResourceId]);
        $ct2Resource = $ct2Statement->fetch();

        return $ct2Resource !== false ? $ct2Resource : null;
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_inventory_resources (
                ct2_supplier_id, resource_name, resource_type, capacity, base_cost, status, notes, created_by, updated_by
             ) VALUES (
                :ct2_supplier_id, :resource_name, :resource_type, :capacity, :base_cost, :status, :notes, :created_by, :updated_by
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_supplier_id' => $ct2Payload['ct2_supplier_id'],
                'resource_name' => $ct2Payload['resource_name'],
                'resource_type' => $ct2Payload['resource_type'],
                'capacity' => $ct2Payload['capacity'],
                'base_cost' => $ct2Payload['base_cost'],
                'status' => $ct2Payload['status'],
                'notes' => $ct2Payload['notes'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT r.ct2_resource_id, r.resource_name, s.supplier_name
             FROM ct2_inventory_resources AS r
             INNER JOIN ct2_suppliers AS s ON s.ct2_supplier_id = r.ct2_supplier_id
             WHERE r.status <> "inactive"
             ORDER BY r.resource_name ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getSummaryCounts(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT
                COUNT(*) AS total_resources,
                SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) AS available_resources,
                SUM(CASE WHEN status = "maintenance" THEN 1 ELSE 0 END) AS maintenance_resources
             FROM ct2_inventory_resources'
        );

        return $ct2Statement->fetch() ?: [
            'total_resources' => 0,
            'available_resources' => 0,
            'maintenance_resources' => 0,
        ];
    }
}
