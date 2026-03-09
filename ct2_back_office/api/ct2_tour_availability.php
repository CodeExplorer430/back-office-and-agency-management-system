<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
    ct2_record_api_log('ct2_tour_availability', $_SERVER['REQUEST_METHOD'] ?? 'GET', 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

$ct2ResourceAllocationModel = new CT2_ResourceAllocationModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Allocations = $ct2ResourceAllocationModel->getAll();
    ct2_record_api_log('ct2_tour_availability', 'GET', 200, [], ['count' => count($ct2Allocations)]);
    ct2_json_response(true, ['allocations' => $ct2Allocations], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_tour_availability', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'ct2_package_id' => 0,
    'pax_count' => 1,
    'reserved_units' => 1,
    'notes' => '',
];

if ((int) ($ct2Payload['ct2_resource_id'] ?? 0) < 1 || trim((string) ($ct2Payload['external_booking_id'] ?? '')) === '' || trim((string) ($ct2Payload['allocation_date'] ?? '')) === '') {
    ct2_record_api_log('ct2_tour_availability', 'POST', 422, ['payload' => 'invalid']);
    ct2_json_response(false, [], 'Resource, external booking ID, and allocation date are required.', 422);
}

$ct2Result = $ct2ResourceAllocationModel->create($ct2Payload, (int) ct2_current_user_id());
ct2_record_api_log('ct2_tour_availability', 'POST', 200, ['resource_id' => $ct2Payload['ct2_resource_id']], ['allocation_status' => $ct2Result['allocation_status']]);
ct2_json_response(true, $ct2Result, null, 200);
