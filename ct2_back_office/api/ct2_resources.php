<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (ct2_current_user() === null || !ct2_has_permission('api.access')) {
    ct2_record_api_log('ct2_resources', $_SERVER['REQUEST_METHOD'] ?? 'GET', 403);
    ct2_json_response(false, [], 'Forbidden.', 403);
}

$ct2ResourceModel = new CT2_ResourceModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Search = trim((string) ($_GET['search'] ?? ''));
    $ct2Resources = $ct2ResourceModel->getAll($ct2Search !== '' ? $ct2Search : null);
    ct2_record_api_log('ct2_resources', 'GET', 200, ['search' => $ct2Search], ['count' => count($ct2Resources)]);
    ct2_json_response(true, ['resources' => $ct2Resources], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_resources', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'resource_type' => 'other',
    'capacity' => 0,
    'base_cost' => '0.00',
    'status' => 'available',
    'notes' => '',
];

if ((int) ($ct2Payload['ct2_supplier_id'] ?? 0) < 1 || trim((string) ($ct2Payload['resource_name'] ?? '')) === '' || (int) ($ct2Payload['capacity'] ?? 0) < 1) {
    ct2_record_api_log('ct2_resources', 'POST', 422, ['payload' => 'invalid']);
    ct2_json_response(false, [], 'Supplier, resource name, and capacity are required.', 422);
}

$ct2ResourceId = $ct2ResourceModel->create($ct2Payload, (int) ct2_current_user_id());
ct2_record_api_log('ct2_resources', 'POST', 200, ['supplier_id' => $ct2Payload['ct2_supplier_id']], ['resource_id' => $ct2ResourceId]);
ct2_json_response(true, ['ct2_resource_id' => $ct2ResourceId], null, 200);
