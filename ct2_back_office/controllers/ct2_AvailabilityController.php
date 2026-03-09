<?php

declare(strict_types=1);

final class CT2_AvailabilityController extends CT2_BaseController
{
    private CT2_SupplierModel $ct2SupplierModel;
    private CT2_ResourceModel $ct2ResourceModel;
    private CT2_TourPackageModel $ct2TourPackageModel;
    private CT2_ResourceAllocationModel $ct2ResourceAllocationModel;
    private CT2_SeasonalBlockModel $ct2SeasonalBlockModel;
    private CT2_DispatchModel $ct2DispatchModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2SupplierModel = new CT2_SupplierModel();
        $this->ct2ResourceModel = new CT2_ResourceModel();
        $this->ct2TourPackageModel = new CT2_TourPackageModel();
        $this->ct2ResourceAllocationModel = new CT2_ResourceAllocationModel();
        $this->ct2SeasonalBlockModel = new CT2_SeasonalBlockModel();
        $this->ct2DispatchModel = new CT2_DispatchModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function index(): void
    {
        ct2_require_permission('availability.view');

        $ct2Search = trim((string) ($_GET['search'] ?? ''));
        $this->ct2Render(
            'availability/ct2_index',
            [
                'ct2Suppliers' => $this->ct2SupplierModel->getAllForSelection(),
                'ct2Resources' => $this->ct2ResourceModel->getAll($ct2Search !== '' ? $ct2Search : null),
                'ct2Packages' => $this->ct2TourPackageModel->getAll(),
                'ct2Allocations' => $this->ct2ResourceAllocationModel->getAll(),
                'ct2Blocks' => $this->ct2SeasonalBlockModel->getAll(),
                'ct2Vehicles' => $this->ct2DispatchModel->getVehicles(),
                'ct2Drivers' => $this->ct2DispatchModel->getDrivers(),
                'ct2DispatchOrders' => $this->ct2DispatchModel->getDispatchOrders(),
                'ct2MaintenanceLogs' => $this->ct2DispatchModel->getMaintenanceLogs(),
                'ct2ResourceSelection' => $this->ct2ResourceModel->getAllForSelection(),
                'ct2PackageSelection' => $this->ct2TourPackageModel->getAllForSelection(),
                'ct2AllocationSelection' => $this->ct2ResourceAllocationModel->getAllForSelection(),
                'ct2Search' => $ct2Search,
            ]
        );
    }

    public function saveResource(): void
    {
        ct2_require_permission('availability.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_supplier_id' => (int) ($_POST['ct2_supplier_id'] ?? 0),
            'resource_name' => trim((string) ($_POST['resource_name'] ?? '')),
            'resource_type' => (string) ($_POST['resource_type'] ?? 'other'),
            'capacity' => (int) ($_POST['capacity'] ?? 0),
            'base_cost' => number_format((float) ($_POST['base_cost'] ?? 0), 2, '.', ''),
            'status' => (string) ($_POST['status'] ?? 'available'),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if ($ct2Payload['ct2_supplier_id'] < 1 || $ct2Payload['resource_name'] === '' || $ct2Payload['capacity'] < 1) {
            throw new InvalidArgumentException('Resource creation requires supplier, resource name, and capacity.');
        }

        $ct2ResourceId = $this->ct2ResourceModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'resource', $ct2ResourceId, 'availability.resource_create', $ct2Payload);

        ct2_flash('success', 'Inventory resource saved.');
        $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
    }

    public function savePackage(): void
    {
        ct2_require_permission('availability.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'package_name' => trim((string) ($_POST['package_name'] ?? '')),
            'base_price' => number_format((float) ($_POST['base_price'] ?? 0), 2, '.', ''),
            'margin_percentage' => number_format((float) ($_POST['margin_percentage'] ?? 0), 2, '.', ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'ct2_resource_id' => (int) ($_POST['ct2_resource_id'] ?? 0),
            'units_required' => max(1, (int) ($_POST['units_required'] ?? 1)),
        ];

        if ($ct2Payload['package_name'] === '') {
            throw new InvalidArgumentException('Tour package requires a package name.');
        }

        $ct2PackageId = $this->ct2TourPackageModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'tour_package', $ct2PackageId, 'availability.package_create', $ct2Payload);

        ct2_flash('success', 'Tour package saved.');
        $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
    }

    public function saveAllocation(): void
    {
        ct2_require_permission('availability.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_resource_id' => (int) ($_POST['ct2_resource_id'] ?? 0),
            'ct2_package_id' => (int) ($_POST['ct2_package_id'] ?? 0),
            'external_booking_id' => trim((string) ($_POST['external_booking_id'] ?? '')),
            'allocation_date' => (string) ($_POST['allocation_date'] ?? ''),
            'pax_count' => max(1, (int) ($_POST['pax_count'] ?? 1)),
            'reserved_units' => max(1, (int) ($_POST['reserved_units'] ?? 1)),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if ($ct2Payload['ct2_resource_id'] < 1 || $ct2Payload['external_booking_id'] === '' || $ct2Payload['allocation_date'] === '') {
            throw new InvalidArgumentException('Allocation requires resource, external booking ID, and allocation date.');
        }

        $ct2Result = $this->ct2ResourceAllocationModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'resource_allocation', $ct2Result['ct2_allocation_id'], 'availability.allocation_create', $ct2Payload + $ct2Result);

        if ($ct2Result['allocation_status'] === 'soft_blocked') {
            ct2_flash('success', 'Allocation saved as soft-blocked due to capacity or block conflict.');
        } else {
            ct2_flash('success', 'Allocation reserved successfully.');
        }

        $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
    }

    public function saveBlock(): void
    {
        ct2_require_permission('availability.manage');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_resource_id' => (int) ($_POST['ct2_resource_id'] ?? 0),
            'start_date' => (string) ($_POST['start_date'] ?? ''),
            'end_date' => (string) ($_POST['end_date'] ?? ''),
            'reason' => trim((string) ($_POST['reason'] ?? '')),
            'block_type' => (string) ($_POST['block_type'] ?? 'manual_soft_block'),
        ];

        if ($ct2Payload['ct2_resource_id'] < 1 || $ct2Payload['start_date'] === '' || $ct2Payload['end_date'] === '' || $ct2Payload['reason'] === '') {
            throw new InvalidArgumentException('Seasonal block requires resource, date range, and reason.');
        }

        $ct2BlockId = $this->ct2SeasonalBlockModel->create($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'seasonal_block', $ct2BlockId, 'availability.block_create', $ct2Payload);

        ct2_flash('success', 'Seasonal block created.');
        $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
    }

    public function saveVehicle(): void
    {
        ct2_require_permission('availability.dispatch');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'plate_number' => trim((string) ($_POST['plate_number'] ?? '')),
            'model_name' => trim((string) ($_POST['model_name'] ?? '')),
            'capacity' => max(0, (int) ($_POST['vehicle_capacity'] ?? 0)),
            'current_mileage' => max(0, (int) ($_POST['current_mileage'] ?? 0)),
            'status' => (string) ($_POST['vehicle_status'] ?? 'available'),
        ];

        if ($ct2Payload['plate_number'] === '' || $ct2Payload['model_name'] === '') {
            throw new InvalidArgumentException('Vehicle creation requires a plate number and model.');
        }

        $ct2VehicleId = $this->ct2DispatchModel->createVehicle($ct2Payload);
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'dispatch_vehicle', $ct2VehicleId, 'availability.vehicle_create', $ct2Payload);

        ct2_flash('success', 'Dispatch vehicle saved.');
        $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
    }

    public function saveDriver(): void
    {
        ct2_require_permission('availability.dispatch');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'full_name' => trim((string) ($_POST['driver_name'] ?? '')),
            'license_expiry' => (string) ($_POST['license_expiry'] ?? ''),
            'status' => (string) ($_POST['driver_status'] ?? 'available'),
        ];

        if ($ct2Payload['full_name'] === '' || $ct2Payload['license_expiry'] === '') {
            throw new InvalidArgumentException('Driver creation requires a name and license expiry date.');
        }

        $ct2DriverId = $this->ct2DispatchModel->createDriver($ct2Payload);
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'dispatch_driver', $ct2DriverId, 'availability.driver_create', $ct2Payload);

        ct2_flash('success', 'Dispatch driver saved.');
        $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
    }

    public function saveDispatch(): void
    {
        ct2_require_permission('availability.dispatch');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_allocation_id' => (int) ($_POST['ct2_allocation_id'] ?? 0),
            'ct2_vehicle_id' => (int) ($_POST['ct2_vehicle_id'] ?? 0),
            'ct2_driver_id' => (int) ($_POST['ct2_driver_id'] ?? 0),
            'dispatch_date' => (string) ($_POST['dispatch_date'] ?? ''),
            'dispatch_time' => (string) ($_POST['dispatch_time'] ?? ''),
            'return_time' => trim((string) ($_POST['return_time'] ?? '')),
            'start_mileage' => max(0, (int) ($_POST['start_mileage'] ?? 0)),
            'end_mileage' => max(0, (int) ($_POST['end_mileage'] ?? 0)),
            'dispatch_status' => (string) ($_POST['dispatch_status'] ?? 'scheduled'),
        ];

        if ($ct2Payload['ct2_vehicle_id'] < 1 || $ct2Payload['ct2_driver_id'] < 1 || $ct2Payload['dispatch_date'] === '' || $ct2Payload['dispatch_time'] === '') {
            throw new InvalidArgumentException('Dispatch order requires vehicle, driver, date, and dispatch time.');
        }

        $ct2DispatchId = $this->ct2DispatchModel->createDispatchOrder($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'dispatch_order', $ct2DispatchId, 'availability.dispatch_create', $ct2Payload);

        ct2_flash('success', 'Dispatch order saved.');
        $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
    }

    public function saveMaintenance(): void
    {
        ct2_require_permission('availability.dispatch');
        $this->assertPostWithCsrf();

        $ct2Payload = [
            'ct2_vehicle_id' => (int) ($_POST['maintenance_vehicle_id'] ?? 0),
            'service_date' => (string) ($_POST['service_date'] ?? ''),
            'service_type' => trim((string) ($_POST['service_type'] ?? '')),
            'mechanic_notes' => trim((string) ($_POST['mechanic_notes'] ?? '')),
            'cost' => number_format((float) ($_POST['maintenance_cost'] ?? 0), 2, '.', ''),
        ];

        if ($ct2Payload['ct2_vehicle_id'] < 1 || $ct2Payload['service_date'] === '' || $ct2Payload['service_type'] === '') {
            throw new InvalidArgumentException('Maintenance log requires vehicle, service date, and service type.');
        }

        $ct2MaintenanceId = $this->ct2DispatchModel->createMaintenanceLog($ct2Payload, (int) ct2_current_user_id());
        $this->ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'maintenance_log', $ct2MaintenanceId, 'availability.maintenance_create', $ct2Payload);

        ct2_flash('success', 'Maintenance log saved.');
        $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
    }

    private function assertPostWithCsrf(): void
    {
        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            ct2_flash('error', 'Invalid request token.');
            $this->ct2Redirect(['module' => 'availability', 'action' => 'index']);
        }
    }
}
