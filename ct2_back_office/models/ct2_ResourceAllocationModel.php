<?php

declare(strict_types=1);

final class CT2_ResourceAllocationModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT a.*, r.resource_name, p.package_name
             FROM ct2_resource_allocations AS a
             INNER JOIN ct2_inventory_resources AS r ON r.ct2_resource_id = a.ct2_resource_id
             LEFT JOIN ct2_tour_packages AS p ON p.ct2_package_id = a.ct2_package_id
             ORDER BY a.allocation_date DESC, a.created_at DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): array
    {
        $ct2Resource = $this->getResourceContext((int) $ct2Payload['ct2_resource_id']);
        if ($ct2Resource === null) {
            throw new InvalidArgumentException('Selected resource was not found.');
        }

        $ct2Blocked = $this->isDateBlocked((int) $ct2Payload['ct2_resource_id'], (string) $ct2Payload['allocation_date']);
        $ct2ReservedUnits = $this->getReservedUnits((int) $ct2Payload['ct2_resource_id'], (string) $ct2Payload['allocation_date']);
        $ct2ProjectedUnits = $ct2ReservedUnits + (int) $ct2Payload['reserved_units'];
        $ct2Status = (!$ct2Blocked && $ct2ProjectedUnits <= (int) $ct2Resource['capacity']) ? 'reserved' : 'soft_blocked';

        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_resource_allocations (
                ct2_resource_id, ct2_package_id, external_booking_id, allocation_date,
                pax_count, reserved_units, allocation_status, notes, created_by
             ) VALUES (
                :ct2_resource_id, :ct2_package_id, :external_booking_id, :allocation_date,
                :pax_count, :reserved_units, :allocation_status, :notes, :created_by
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_resource_id' => $ct2Payload['ct2_resource_id'],
                'ct2_package_id' => $ct2Payload['ct2_package_id'] ?: null,
                'external_booking_id' => $ct2Payload['external_booking_id'],
                'allocation_date' => $ct2Payload['allocation_date'],
                'pax_count' => $ct2Payload['pax_count'],
                'reserved_units' => $ct2Payload['reserved_units'],
                'allocation_status' => $ct2Status,
                'notes' => $ct2Payload['notes'] ?: null,
                'created_by' => $ct2UserId,
            ]
        );

        return [
            'ct2_allocation_id' => (int) $this->ct2Pdo->lastInsertId(),
            'allocation_status' => $ct2Status,
            'was_blocked' => $ct2Blocked,
            'projected_units' => $ct2ProjectedUnits,
            'capacity' => (int) $ct2Resource['capacity'],
        ];
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_allocation_id, external_booking_id
             FROM ct2_resource_allocations
             WHERE allocation_status <> "released"
             ORDER BY allocation_date DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getReservedUnits(int $ct2ResourceId, string $ct2AllocationDate): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT COALESCE(SUM(reserved_units), 0) AS reserved_units
             FROM ct2_resource_allocations
             WHERE ct2_resource_id = :ct2_resource_id
               AND allocation_date = :allocation_date
               AND allocation_status <> "released"'
        );
        $ct2Statement->execute(
            [
                'ct2_resource_id' => $ct2ResourceId,
                'allocation_date' => $ct2AllocationDate,
            ]
        );

        $ct2Result = $ct2Statement->fetch();
        return (int) ($ct2Result['reserved_units'] ?? 0);
    }

    private function isDateBlocked(int $ct2ResourceId, string $ct2AllocationDate): bool
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT COUNT(*) AS block_count
             FROM ct2_seasonal_blocks
             WHERE ct2_resource_id = :ct2_resource_id
               AND :allocation_date BETWEEN start_date AND end_date'
        );
        $ct2Statement->execute(
            [
                'ct2_resource_id' => $ct2ResourceId,
                'allocation_date' => $ct2AllocationDate,
            ]
        );

        $ct2Result = $ct2Statement->fetch();
        return (int) ($ct2Result['block_count'] ?? 0) > 0;
    }

    private function getResourceContext(int $ct2ResourceId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT ct2_resource_id, capacity
             FROM ct2_inventory_resources
             WHERE ct2_resource_id = :ct2_resource_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_resource_id' => $ct2ResourceId]);
        $ct2Resource = $ct2Statement->fetch();

        return $ct2Resource !== false ? $ct2Resource : null;
    }
}
