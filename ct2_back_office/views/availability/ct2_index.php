<section class="ct2-section">
    <div class="ct2-section-header">
        <div>
            <p class="ct2-eyebrow">Third Vertical Slice</p>
            <h2>Tour Availability and Resource Planning</h2>
        </div>
        <form method="get" action="<?= htmlspecialchars(ct2_url(), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-inline-form">
            <input type="hidden" name="module" value="availability">
            <input type="hidden" name="action" value="index">
            <input class="ct2-input" name="search" type="text" placeholder="Search resources" value="<?= htmlspecialchars((string) $ct2Search, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="ct2-btn ct2-btn-secondary" type="submit">Filter</button>
        </form>
    </div>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Resource Registry</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'saveResource']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Supplier / Vendor</label>
            <select class="ct2-select" name="ct2_supplier_id" required>
                <option value="">Select supplier</option>
                <?php foreach ($ct2Suppliers as $ct2Supplier): ?>
                    <option value="<?= (int) $ct2Supplier['ct2_supplier_id']; ?>"><?= htmlspecialchars((string) $ct2Supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Resource Name</label>
            <input class="ct2-input" name="resource_name" required>
            <label class="ct2-label">Resource Type</label>
            <select class="ct2-select" name="resource_type">
                <?php foreach (['transport', 'hotel', 'guide', 'equipment', 'other'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Capacity</label>
            <input class="ct2-input" name="capacity" type="number" min="1" value="1" required>
            <label class="ct2-label">Base Cost</label>
            <input class="ct2-input" name="base_cost" type="number" min="0" step="0.01" value="0.00">
            <label class="ct2-label">Status</label>
            <select class="ct2-select" name="status">
                <?php foreach (['available', 'maintenance', 'inactive'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Notes</label>
            <textarea class="ct2-textarea" name="notes" rows="3"></textarea>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Resource</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Package Planner</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'savePackage']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Package Name</label>
            <input class="ct2-input" name="package_name" required>
            <label class="ct2-label">Base Price</label>
            <input class="ct2-input" name="base_price" type="number" min="0" step="0.01" value="0.00">
            <label class="ct2-label">Margin Percentage</label>
            <input class="ct2-input" name="margin_percentage" type="number" min="0" step="0.01" value="15.00">
            <label class="ct2-label">Primary Resource Link</label>
            <select class="ct2-select" name="ct2_resource_id">
                <option value="0">Optional resource</option>
                <?php foreach ($ct2ResourceSelection as $ct2Resource): ?>
                    <option value="<?= (int) $ct2Resource['ct2_resource_id']; ?>"><?= htmlspecialchars((string) $ct2Resource['resource_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2Resource['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Units Required</label>
            <input class="ct2-input" name="units_required" type="number" min="1" value="1">
            <label class="ct2-label ct2-checkbox-row"><input type="checkbox" name="is_active" checked> Package is active</label>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Package</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Availability Allocation</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'saveAllocation']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Resource</label>
            <select class="ct2-select" name="ct2_resource_id" required>
                <option value="">Select resource</option>
                <?php foreach ($ct2ResourceSelection as $ct2Resource): ?>
                    <option value="<?= (int) $ct2Resource['ct2_resource_id']; ?>"><?= htmlspecialchars((string) $ct2Resource['resource_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2Resource['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Package</label>
            <select class="ct2-select" name="ct2_package_id">
                <option value="0">Optional package</option>
                <?php foreach ($ct2PackageSelection as $ct2Package): ?>
                    <option value="<?= (int) $ct2Package['ct2_package_id']; ?>"><?= htmlspecialchars((string) $ct2Package['package_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">External Booking ID</label>
            <input class="ct2-input" name="external_booking_id" required>
            <label class="ct2-label">Allocation Date</label>
            <input class="ct2-input" name="allocation_date" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required>
            <label class="ct2-label">Passenger Count</label>
            <input class="ct2-input" name="pax_count" type="number" min="1" value="1">
            <label class="ct2-label">Reserved Units</label>
            <input class="ct2-input" name="reserved_units" type="number" min="1" value="1">
            <label class="ct2-label">Notes</label>
            <textarea class="ct2-textarea" name="notes" rows="3"></textarea>
            <button class="ct2-btn ct2-btn-primary" type="submit">Run Availability Check</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Seasonal / Soft Blocks</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'saveBlock']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Resource</label>
            <select class="ct2-select" name="ct2_resource_id" required>
                <option value="">Select resource</option>
                <?php foreach ($ct2ResourceSelection as $ct2Resource): ?>
                    <option value="<?= (int) $ct2Resource['ct2_resource_id']; ?>"><?= htmlspecialchars((string) $ct2Resource['resource_name'], ENT_QUOTES, 'UTF-8'); ?> / <?= htmlspecialchars((string) $ct2Resource['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Start Date</label>
            <input class="ct2-input" name="start_date" type="date" required>
            <label class="ct2-label">End Date</label>
            <input class="ct2-input" name="end_date" type="date" required>
            <label class="ct2-label">Block Type</label>
            <select class="ct2-select" name="block_type">
                <?php foreach (['maintenance', 'peak_hold', 'supplier_hold', 'manual_soft_block'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Reason</label>
            <input class="ct2-input" name="reason" required>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Block</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Dispatch Setup</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'saveVehicle']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Vehicle Plate</label>
            <input class="ct2-input" name="plate_number" required>
            <label class="ct2-label">Model</label>
            <input class="ct2-input" name="model_name" required>
            <label class="ct2-label">Vehicle Capacity</label>
            <input class="ct2-input" name="vehicle_capacity" type="number" min="0" value="0">
            <label class="ct2-label">Current Mileage</label>
            <input class="ct2-input" name="current_mileage" type="number" min="0" value="0">
            <label class="ct2-label">Vehicle Status</label>
            <select class="ct2-select" name="vehicle_status">
                <?php foreach (['available', 'maintenance', 'inactive'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Vehicle</button>
        </form>

        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'saveDriver']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Driver Name</label>
            <input class="ct2-input" name="driver_name" required>
            <label class="ct2-label">License Expiry</label>
            <input class="ct2-input" name="license_expiry" type="date" required>
            <label class="ct2-label">Driver Status</label>
            <select class="ct2-select" name="driver_status">
                <?php foreach (['available', 'assigned', 'inactive'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst($ct2Option); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Driver</button>
        </form>
    </article>

    <article class="ct2-panel">
        <h3>Dispatch And Maintenance</h3>
        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'saveDispatch']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Allocation</label>
            <select class="ct2-select" name="ct2_allocation_id">
                <option value="0">Optional allocation</option>
                <?php foreach ($ct2AllocationSelection as $ct2Allocation): ?>
                    <option value="<?= (int) $ct2Allocation['ct2_allocation_id']; ?>"><?= htmlspecialchars((string) $ct2Allocation['external_booking_id'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Vehicle</label>
            <select class="ct2-select" name="ct2_vehicle_id" required>
                <option value="">Select vehicle</option>
                <?php foreach ($ct2Vehicles as $ct2Vehicle): ?>
                    <option value="<?= (int) $ct2Vehicle['ct2_vehicle_id']; ?>"><?= htmlspecialchars((string) $ct2Vehicle['plate_number'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Driver</label>
            <select class="ct2-select" name="ct2_driver_id" required>
                <option value="">Select driver</option>
                <?php foreach ($ct2Drivers as $ct2Driver): ?>
                    <option value="<?= (int) $ct2Driver['ct2_driver_id']; ?>"><?= htmlspecialchars((string) $ct2Driver['full_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Dispatch Date</label>
            <input class="ct2-input" name="dispatch_date" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required>
            <label class="ct2-label">Dispatch Time</label>
            <input class="ct2-input" name="dispatch_time" type="datetime-local" required>
            <label class="ct2-label">Return Time</label>
            <input class="ct2-input" name="return_time" type="datetime-local">
            <label class="ct2-label">Start Mileage</label>
            <input class="ct2-input" name="start_mileage" type="number" min="0" value="0">
            <label class="ct2-label">End Mileage</label>
            <input class="ct2-input" name="end_mileage" type="number" min="0" value="0">
            <label class="ct2-label">Dispatch Status</label>
            <select class="ct2-select" name="dispatch_status">
                <?php foreach (['scheduled', 'en_route', 'completed', 'cancelled'] as $ct2Option): ?>
                    <option value="<?= $ct2Option; ?>"><?= ucfirst(str_replace('_', ' ', $ct2Option)); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Dispatch Order</button>
        </form>

        <form method="post" action="<?= htmlspecialchars(ct2_url(['module' => 'availability', 'action' => 'saveMaintenance']), ENT_QUOTES, 'UTF-8'); ?>" class="ct2-form">
            <input type="hidden" name="ct2_csrf_token" value="<?= htmlspecialchars(ct2_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <label class="ct2-label">Vehicle</label>
            <select class="ct2-select" name="maintenance_vehicle_id" required>
                <option value="">Select vehicle</option>
                <?php foreach ($ct2Vehicles as $ct2Vehicle): ?>
                    <option value="<?= (int) $ct2Vehicle['ct2_vehicle_id']; ?>"><?= htmlspecialchars((string) $ct2Vehicle['plate_number'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="ct2-label">Service Date</label>
            <input class="ct2-input" name="service_date" type="date" required>
            <label class="ct2-label">Service Type</label>
            <input class="ct2-input" name="service_type" required>
            <label class="ct2-label">Maintenance Cost</label>
            <input class="ct2-input" name="maintenance_cost" type="number" min="0" step="0.01" value="0.00">
            <label class="ct2-label">Mechanic Notes</label>
            <textarea class="ct2-textarea" name="mechanic_notes" rows="3"></textarea>
            <button class="ct2-btn ct2-btn-primary" type="submit">Save Maintenance Log</button>
        </form>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Resource Snapshot</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Resource</th>
                    <th>Supplier</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Reserved</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Resources as $ct2Resource): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Resource['resource_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Resource['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Resource['resource_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= (int) $ct2Resource['capacity']; ?></td>
                        <td><?= (int) $ct2Resource['reserved_units']; ?></td>
                        <td><?= htmlspecialchars((string) $ct2Resource['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Resources === []): ?>
                    <tr><td colspan="6">No resources registered yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>Packages and Allocations</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Package</th>
                    <th>Base</th>
                    <th>Margin</th>
                    <th>Selling Price</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Packages as $ct2Package): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Package['package_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Package['base_price'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Package['margin_percentage'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                        <td><?= htmlspecialchars((string) $ct2Package['selling_price'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Packages === []): ?>
                    <tr><td colspan="4">No packages saved yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Booking</th>
                    <th>Resource</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Allocations as $ct2Allocation): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Allocation['external_booking_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Allocation['resource_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Allocation['allocation_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Allocation['allocation_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Allocations === []): ?>
                    <tr><td colspan="4">No allocations recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="ct2-grid-2">
    <article class="ct2-panel">
        <h3>Seasonal Blocks</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Resource</th>
                    <th>Range</th>
                    <th>Type</th>
                    <th>Reason</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2Blocks as $ct2Block): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2Block['resource_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Block['start_date'], ENT_QUOTES, 'UTF-8'); ?> to <?= htmlspecialchars((string) $ct2Block['end_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Block['block_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2Block['reason'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2Blocks === []): ?>
                    <tr><td colspan="4">No blocks recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="ct2-panel">
        <h3>Dispatch and Maintenance Snapshot</h3>
        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Dispatch Date</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2DispatchOrders as $ct2DispatchOrder): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2DispatchOrder['plate_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2DispatchOrder['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2DispatchOrder['dispatch_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2DispatchOrder['dispatch_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2DispatchOrders === []): ?>
                    <tr><td colspan="4">No dispatch orders recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="ct2-table-wrap">
            <table class="ct2-table">
                <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Service Date</th>
                    <th>Type</th>
                    <th>Cost</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ct2MaintenanceLogs as $ct2MaintenanceLog): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ct2MaintenanceLog['plate_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2MaintenanceLog['service_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2MaintenanceLog['service_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string) $ct2MaintenanceLog['cost'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($ct2MaintenanceLogs === []): ?>
                    <tr><td colspan="4">No maintenance logs recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
