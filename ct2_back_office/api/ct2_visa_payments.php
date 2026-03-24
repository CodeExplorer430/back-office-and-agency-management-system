<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

ct2_require_api_permission('ct2_visa_payments', 'visa.view', 'visa.manage');

$ct2VisaPaymentModel = new CT2_VisaPaymentModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2Method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($ct2Method === 'GET') {
    $ct2Payments = $ct2VisaPaymentModel->getAll();
    ct2_record_api_log('ct2_visa_payments', 'GET', 200, [], ['count' => count($ct2Payments)]);
    ct2_json_response(true, ['payments' => $ct2Payments], null, 200);
}

if ($ct2Method !== 'POST') {
    ct2_record_api_log('ct2_visa_payments', $ct2Method, 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input() + [
    'external_payment_id' => '',
    'currency' => 'PHP',
    'payment_method' => 'Manual',
    'payment_status' => 'pending',
    'paid_at' => '',
    'source_system' => '',
];

if ((int) ($ct2Payload['ct2_visa_application_id'] ?? 0) < 1) {
    ct2_record_api_log('ct2_visa_payments', 'POST', 422, ['field' => 'ct2_visa_application_id']);
    ct2_json_response(false, [], 'Missing field: ct2_visa_application_id', 422);
}

foreach (['payment_reference', 'amount'] as $ct2RequiredField) {
    if (trim((string) ($ct2Payload[$ct2RequiredField] ?? '')) === '') {
        ct2_record_api_log('ct2_visa_payments', 'POST', 422, ['field' => $ct2RequiredField]);
        ct2_json_response(false, [], 'Missing field: ' . $ct2RequiredField, 422);
    }
}

if ((float) $ct2Payload['amount'] <= 0) {
    ct2_record_api_log('ct2_visa_payments', 'POST', 422, ['field' => 'amount']);
    ct2_json_response(false, [], 'Amount must be greater than zero.', 422);
}

$ct2VisaPaymentId = $ct2VisaPaymentModel->create($ct2Payload, (int) ct2_current_user_id());
$ct2AuditLogModel->recordAudit((int) ct2_current_user_id(), 'visa_payment', $ct2VisaPaymentId, 'visa.api_payment_create', $ct2Payload);
ct2_record_api_log('ct2_visa_payments', 'POST', 200, ['ct2_visa_payment_id' => $ct2VisaPaymentId], ['payment_status' => $ct2Payload['payment_status']]);
ct2_json_response(true, ['ct2_visa_payment_id' => $ct2VisaPaymentId], null, 200);
