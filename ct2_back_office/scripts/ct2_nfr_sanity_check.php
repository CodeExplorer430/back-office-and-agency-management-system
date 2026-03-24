<?php

declare(strict_types=1);

require_once __DIR__ . '/ct2_validation_common.php';

ct2AssertCli();

$ct2Prefix = 'ct2-nfr';
$ct2TempDir = ct2CreateTempDir($ct2Prefix);
$ct2Server = null;
$ct2MaxSeconds = 5.0;

try {
    ct2Log($ct2Prefix, 'Checking structural accessibility markers in CT2 views.');
    $ct2Views = [
        'auth/ct2_login.php',
        'dashboard/ct2_home.php',
        'agents/ct2_index.php',
        'staff/ct2_index.php',
        'suppliers/ct2_index.php',
        'availability/ct2_index.php',
        'marketing/ct2_index.php',
        'financial/ct2_index.php',
        'visa/ct2_index.php',
        'approvals/ct2_index.php',
    ];

    foreach ($ct2Views as $ct2View) {
        $ct2Contents = file_get_contents(ct2AppRoot() . '/views/' . $ct2View);
        if ($ct2Contents === false) {
            ct2Fail($ct2Prefix, 'Unable to read view file: ' . $ct2View);
        }
        ct2AssertContains($ct2Contents, '<h2>', 'Missing h2 heading in ' . $ct2View, $ct2Prefix);
    }

    $ct2LabelViews = [
        'auth/ct2_login.php',
        'agents/ct2_index.php',
        'staff/ct2_index.php',
        'suppliers/ct2_index.php',
        'availability/ct2_index.php',
        'marketing/ct2_index.php',
        'financial/ct2_index.php',
        'visa/ct2_index.php',
    ];

    foreach ($ct2LabelViews as $ct2View) {
        $ct2Contents = file_get_contents(ct2AppRoot() . '/views/' . $ct2View);
        if ($ct2Contents === false) {
            ct2Fail($ct2Prefix, 'Unable to read view file: ' . $ct2View);
        }
        ct2AssertContains($ct2Contents, 'ct2-label', 'Missing form label markup in ' . $ct2View, $ct2Prefix);
    }

    ct2Log($ct2Prefix, 'Starting local CT2 PHP server.');
    $ct2Port = ct2SelectPort(8096);
    $ct2Server = ct2StartPhpServer($ct2Port, $ct2TempDir);
    $ct2BaseUrl = 'http://127.0.0.1:' . $ct2Port . '/ct2_index.php';
    $ct2ApiBaseUrl = 'http://127.0.0.1:' . $ct2Port . '/api';
    $ct2Session = ct2CreateHttpSession($ct2TempDir);

    ct2Log($ct2Prefix, 'Sampling seeded local response times.');
    $ct2LoginPage = ct2HttpRequest('GET', $ct2BaseUrl . '?module=auth&action=login', $ct2Session);
    ct2AssertSecondsWithinLimit($ct2LoginPage['time_total'], $ct2MaxSeconds, 'Login page', $ct2Prefix);
    ct2AssertContains($ct2LoginPage['body'], 'CT2 Back-Office Login', 'Login page did not render', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2LoginPage['body']);

    $ct2LoginPost = ct2HttpRequest(
        'POST',
        $ct2BaseUrl . '?module=auth&action=login',
        $ct2Session,
        [],
        [
            'ct2_csrf_token' => $ct2Csrf,
            'username' => 'ct2admin',
            'password' => 'ChangeMe123!',
        ]
    );
    ct2AssertSecondsWithinLimit($ct2LoginPost['time_total'], $ct2MaxSeconds, 'Login submit', $ct2Prefix);
    ct2AssertContains($ct2LoginPost['body'], 'Back-Office Dashboard', 'Login POST did not reach the dashboard', $ct2Prefix);

    $ct2Dashboard = ct2HttpRequest('GET', $ct2BaseUrl . '?module=dashboard&action=index', $ct2Session);
    ct2AssertSecondsWithinLimit($ct2Dashboard['time_total'], $ct2MaxSeconds, 'Dashboard route', $ct2Prefix);
    ct2AssertContains($ct2Dashboard['body'], 'Back-Office Dashboard', 'Dashboard route did not render', $ct2Prefix);

    $ct2Agents = ct2HttpRequest('GET', $ct2BaseUrl . '?module=agents&action=index&search=AGT-CT2-001', $ct2Session);
    ct2AssertSecondsWithinLimit($ct2Agents['time_total'], $ct2MaxSeconds, 'Agents filtered route', $ct2Prefix);
    ct2AssertContains($ct2Agents['body'], 'AGT-CT2-001', 'Agents filtered route did not render the seeded record', $ct2Prefix);

    $ct2ModuleStatus = ct2HttpRequest('GET', $ct2ApiBaseUrl . '/ct2_module_status.php', $ct2Session);
    ct2AssertSecondsWithinLimit($ct2ModuleStatus['time_total'], $ct2MaxSeconds, 'Module status API', $ct2Prefix);
    ct2AssertContains($ct2ModuleStatus['body'], '"success":true', 'Module status API did not return a success payload', $ct2Prefix);

    ct2Log($ct2Prefix, 'Measured response times (seconds):');
    fwrite(STDOUT, sprintf("login_get=%.6f\n", $ct2LoginPage['time_total']));
    fwrite(STDOUT, sprintf("login_post=%.6f\n", $ct2LoginPost['time_total']));
    fwrite(STDOUT, sprintf("dashboard=%.6f\n", $ct2Dashboard['time_total']));
    fwrite(STDOUT, sprintf("agents_filtered=%.6f\n", $ct2Agents['time_total']));
    fwrite(STDOUT, sprintf("module_status_api=%.6f\n", $ct2ModuleStatus['time_total']));

    ct2Log($ct2Prefix, 'CT2 NFR sanity checks passed.');
} finally {
    if (is_array($ct2Server)) {
        ct2StopProcess($ct2Server['proc']);
    }
    ct2RemoveDir($ct2TempDir);
}
