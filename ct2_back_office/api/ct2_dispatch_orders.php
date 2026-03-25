<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_dispatch_orders', 'availability.view', 'availability.dispatch');

$ct2DispatchModel = new CT2_DispatchModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Orders = $ct2DispatchModel->getDispatchOrders();
    ct2_record_api_log('ct2_dispatch_orders', 'GET', 200, [], ['count' => count($ct2Orders)]);
    ct2_json_response(true, ['dispatch_orders' => $ct2Orders], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_dispatch_orders', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'ct2_allocation_id' => 0,
    'return_time' => '',
    'start_mileage' => 0,
    'end_mileage' => 0,
    'dispatch_status' => 'scheduled',
];

if ((int) ($ct2Payload['ct2_vehicle_id'] ?? 0) < 1 || (int) ($ct2Payload['ct2_driver_id'] ?? 0) < 1 || trim((string) ($ct2Payload['dispatch_date'] ?? '')) === '' || trim((string) ($ct2Payload['dispatch_time'] ?? '')) === '') {
    ct2_record_api_log('ct2_dispatch_orders', 'POST', 422, ['payload' => 'invalid']);
    ct2_json_response(false, [], 'Vehicle, driver, dispatch date, and dispatch time are required.', 422);
}

$ct2DispatchId = $ct2DispatchModel->createDispatchOrder($ct2Payload, (int) ct2_current_user_id());
ct2_record_api_log('ct2_dispatch_orders', 'POST', 200, ['vehicle_id' => $ct2Payload['ct2_vehicle_id']], ['dispatch_order_id' => $ct2DispatchId]);
ct2_json_response(true, ['ct2_dispatch_order_id' => $ct2DispatchId], null, 200);
