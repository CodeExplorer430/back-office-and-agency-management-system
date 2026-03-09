<?php

declare(strict_types=1);

final class CT2_DispatchModel extends CT2_BaseModel
{
    public function getVehicles(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT *
             FROM ct2_dispatch_vehicles
             ORDER BY plate_number ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function createVehicle(array $ct2Payload): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_dispatch_vehicles (
                plate_number, model_name, capacity, current_mileage, status
             ) VALUES (
                :plate_number, :model_name, :capacity, :current_mileage, :status
             )'
        );
        $ct2Statement->execute($ct2Payload);

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function getDrivers(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT *
             FROM ct2_dispatch_drivers
             ORDER BY full_name ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function createDriver(array $ct2Payload): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_dispatch_drivers (
                full_name, license_expiry, status
             ) VALUES (
                :full_name, :license_expiry, :status
             )'
        );
        $ct2Statement->execute($ct2Payload);

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function getDispatchOrders(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT d.*, v.plate_number, dr.full_name, a.external_booking_id
             FROM ct2_dispatch_orders AS d
             INNER JOIN ct2_dispatch_vehicles AS v ON v.ct2_vehicle_id = d.ct2_vehicle_id
             INNER JOIN ct2_dispatch_drivers AS dr ON dr.ct2_driver_id = d.ct2_driver_id
             LEFT JOIN ct2_resource_allocations AS a ON a.ct2_allocation_id = d.ct2_allocation_id
             ORDER BY d.dispatch_date DESC, d.dispatch_time DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function createDispatchOrder(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_dispatch_orders (
                ct2_allocation_id, ct2_vehicle_id, ct2_driver_id, dispatch_date, dispatch_time,
                return_time, start_mileage, end_mileage, dispatch_status, created_by
             ) VALUES (
                :ct2_allocation_id, :ct2_vehicle_id, :ct2_driver_id, :dispatch_date, :dispatch_time,
                :return_time, :start_mileage, :end_mileage, :dispatch_status, :created_by
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_allocation_id' => $ct2Payload['ct2_allocation_id'] ?: null,
                'ct2_vehicle_id' => $ct2Payload['ct2_vehicle_id'],
                'ct2_driver_id' => $ct2Payload['ct2_driver_id'],
                'dispatch_date' => $ct2Payload['dispatch_date'],
                'dispatch_time' => $ct2Payload['dispatch_time'],
                'return_time' => $ct2Payload['return_time'] ?: null,
                'start_mileage' => $ct2Payload['start_mileage'],
                'end_mileage' => $ct2Payload['end_mileage'] ?: null,
                'dispatch_status' => $ct2Payload['dispatch_status'],
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function getMaintenanceLogs(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT m.*, v.plate_number
             FROM ct2_maintenance_logs AS m
             INNER JOIN ct2_dispatch_vehicles AS v ON v.ct2_vehicle_id = m.ct2_vehicle_id
             ORDER BY m.service_date DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function createMaintenanceLog(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_maintenance_logs (
                ct2_vehicle_id, service_date, service_type, mechanic_notes, cost, created_by
             ) VALUES (
                :ct2_vehicle_id, :service_date, :service_type, :mechanic_notes, :cost, :created_by
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_vehicle_id' => $ct2Payload['ct2_vehicle_id'],
                'service_date' => $ct2Payload['service_date'],
                'service_type' => $ct2Payload['service_type'],
                'mechanic_notes' => $ct2Payload['mechanic_notes'] ?: null,
                'cost' => $ct2Payload['cost'],
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
