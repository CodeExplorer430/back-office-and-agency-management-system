<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/ct2_bootstrap.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    ct2_record_api_log('ct2_auth_login', 'GET', 405);
    ct2_json_response(false, [], 'Method not allowed.', 405);
}

$ct2Payload = ct2_json_input();
$ct2Username = trim((string) ($ct2Payload['username'] ?? ''));
$ct2Password = (string) ($ct2Payload['password'] ?? '');

$ct2UserModel = new CT2_UserModel();
$ct2AuditLogModel = new CT2_AuditLogModel();
$ct2User = $ct2UserModel->findByUsername($ct2Username);

if ($ct2User === null || !password_verify($ct2Password, (string) $ct2User['password_hash'])) {
    ct2_record_api_log('ct2_auth_login', 'POST', 401, ['username' => $ct2Username], ['message' => 'Invalid credentials']);
    ct2_json_response(false, [], 'Invalid credentials.', 401);
}

$ct2UserModel->updateLastLogin((int) $ct2User['ct2_user_id']);
$ct2UserModel->recordSession((int) $ct2User['ct2_user_id'], session_id());
$ct2HydratedUser = $ct2UserModel->getHydratedUser((int) $ct2User['ct2_user_id']);

if ($ct2HydratedUser === null) {
    ct2_record_api_log('ct2_auth_login', 'POST', 500, ['username' => $ct2Username], ['message' => 'Hydration failed']);
    ct2_json_response(false, [], 'Unable to initialize session.', 500);
}

ct2_store_user_session($ct2HydratedUser);
$ct2AuditLogModel->recordAudit((int) $ct2HydratedUser['ct2_user_id'], 'auth', null, 'auth.api_login', ['username' => $ct2HydratedUser['username']]);
ct2_record_api_log('ct2_auth_login', 'POST', 200, ['username' => $ct2Username], ['user_id' => $ct2HydratedUser['ct2_user_id']]);
ct2_json_response(true, ['user' => $ct2HydratedUser], null, 200);
