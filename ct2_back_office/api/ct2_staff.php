<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_staff', 'staff.view', 'staff.manage');

$ct2StaffModel = new CT2_StaffModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Search = trim((string) ($_GET['search'] ?? ''));
    $ct2Staff = $ct2StaffModel->getAll($ct2Search !== '' ? $ct2Search : null);
    ct2_record_api_log('ct2_staff', 'GET', 200, ['search' => $ct2Search], ['count' => count($ct2Staff)]);
    ct2_json_response(true, ['staff' => $ct2Staff], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_staff', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input();
$ct2UserId = (int) ct2_current_user_id();

foreach (['staff_code', 'full_name', 'email', 'phone', 'department', 'position_title', 'team_name'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_staff', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

$ct2Payload += [
    'employment_status' => 'active',
    'availability_status' => 'available',
    'notes' => '',
];

$ct2StaffId = isset($ct2Payload['ct2_staff_id']) ? (int) $ct2Payload['ct2_staff_id'] : 0;
if ($ct2StaffId > 0) {
    $ct2StaffModel->update($ct2StaffId, $ct2Payload, $ct2UserId);
    $ct2Action = 'staff.api_update';
} else {
    $ct2StaffId = $ct2StaffModel->create($ct2Payload, $ct2UserId);
    $ct2Action = 'staff.api_create';
}

$ct2AuditLogModel->recordAudit($ct2UserId, 'staff', $ct2StaffId, $ct2Action, $ct2Payload);
ct2_record_api_log('ct2_staff', 'POST', 200, ['staff_id' => $ct2StaffId], ['action' => $ct2Action]);
ct2_json_response(true, ['ct2_staff_id' => $ct2StaffId], null, 200);
