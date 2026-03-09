<?php

declare(strict_types=1);

final class CT2_TourPackageModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT p.*,
                ROUND(p.base_price + (p.base_price * (p.margin_percentage / 100)), 2) AS selling_price
             FROM ct2_tour_packages AS p
             ORDER BY p.created_at DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_tour_packages (
                package_name, base_price, margin_percentage, is_active, created_by, updated_by
             ) VALUES (
                :package_name, :base_price, :margin_percentage, :is_active, :created_by, :updated_by
             )'
        );
        $ct2Statement->execute(
            [
                'package_name' => $ct2Payload['package_name'],
                'base_price' => $ct2Payload['base_price'],
                'margin_percentage' => $ct2Payload['margin_percentage'],
                'is_active' => $ct2Payload['is_active'],
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        $ct2PackageId = (int) $this->ct2Pdo->lastInsertId();
        if ($ct2Payload['ct2_resource_id'] > 0) {
            $this->linkResource($ct2PackageId, $ct2Payload['ct2_resource_id'], $ct2Payload['units_required']);
        }

        return $ct2PackageId;
    }

    public function linkResource(int $ct2PackageId, int $ct2ResourceId, int $ct2UnitsRequired): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_package_resources (
                ct2_package_id, ct2_resource_id, units_required
             ) VALUES (
                :ct2_package_id, :ct2_resource_id, :units_required
             )
             ON DUPLICATE KEY UPDATE units_required = VALUES(units_required)'
        );
        $ct2Statement->execute(
            [
                'ct2_package_id' => $ct2PackageId,
                'ct2_resource_id' => $ct2ResourceId,
                'units_required' => $ct2UnitsRequired,
            ]
        );
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_package_id, package_name
             FROM ct2_tour_packages
             WHERE is_active = 1
             ORDER BY package_name ASC'
        );

        return $ct2Statement->fetchAll();
    }
}
