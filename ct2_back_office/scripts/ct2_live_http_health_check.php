<?php

declare(strict_types=1);

require_once __DIR__ . '/ct2_validation_common.php';

ct2AssertCli();

$ct2Prefix = 'ct2-live-health';
$ct2TempDir = ct2CreateTempDir($ct2Prefix);

try {
    $ct2BaseInput = getenv('CT2_BASE_URL');
    if ($ct2BaseInput === false || trim($ct2BaseInput) === '') {
        throw new RuntimeException('CT2_BASE_URL is required for live health checks.');
    }

    $ct2BaseInput = rtrim(trim($ct2BaseInput), '/');
    $ct2BaseUrl = str_ends_with($ct2BaseInput, '/ct2_index.php')
        ? $ct2BaseInput
        : $ct2BaseInput . '/ct2_index.php';

    $ct2Session = ct2CreateHttpSession($ct2TempDir);

    ct2Log($ct2Prefix, 'Checking live login page.');
    $ct2LoginPage = ct2HttpRequest('GET', $ct2BaseUrl . '?module=auth&action=login', $ct2Session);
    ct2AssertStatus(200, $ct2LoginPage, 'Live login page did not return 200', $ct2Prefix);
    ct2AssertContains($ct2LoginPage['body'], 'username', 'Live login page did not render the sign-in form', $ct2Prefix);
    ct2AssertHtmlClean($ct2LoginPage['body'], 'Live login page rendered warning-like output', $ct2Prefix);

    $ct2HealthUser = getenv('CT2_HEALTHCHECK_USERNAME');
    $ct2HealthPass = getenv('CT2_HEALTHCHECK_PASSWORD');
    if ($ct2HealthUser !== false && $ct2HealthUser !== '' && $ct2HealthPass !== false && $ct2HealthPass !== '') {
        ct2Log($ct2Prefix, 'Running authenticated dashboard health check.');
        $ct2Csrf = ct2ExtractCsrf($ct2LoginPage['body']);
        $ct2LoginResult = ct2HttpRequest(
            'POST',
            $ct2BaseUrl . '?module=auth&action=login',
            $ct2Session,
            [],
            [
                'ct2_csrf_token' => $ct2Csrf,
                'username' => $ct2HealthUser,
                'password' => $ct2HealthPass,
            ]
        );
        ct2AssertStatus(200, $ct2LoginResult, 'Live health-check login did not complete', $ct2Prefix);
        ct2AssertContains($ct2LoginResult['body'], 'Back-Office Dashboard', 'Live health-check login did not reach the dashboard', $ct2Prefix);
        ct2AssertHtmlClean($ct2LoginResult['body'], 'Live health-check dashboard rendered warning-like output', $ct2Prefix);
    }

    ct2Log($ct2Prefix, 'CT2 live HTTP health check passed.');
} finally {
    ct2RemoveDir($ct2TempDir);
}
