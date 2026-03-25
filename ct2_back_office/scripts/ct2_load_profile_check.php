<?php

declare(strict_types=1);

require_once __DIR__ . '/ct2_validation_common.php';

ct2AssertCli();

$ct2Prefix = 'ct2-load';
$ct2TempDir = ct2CreateTempDir($ct2Prefix);
$ct2Server = null;
$ct2Iterations = 5;
$ct2MaxSeconds = 5.0;

try {
    ct2Log($ct2Prefix, 'Starting local CT2 PHP server.');
    $ct2Port = ct2SelectPort(8098);
    $ct2Server = ct2StartPhpServer($ct2Port, $ct2TempDir);
    $ct2BaseUrl = 'http://127.0.0.1:' . $ct2Port . '/ct2_index.php';
    $ct2ApiBaseUrl = 'http://127.0.0.1:' . $ct2Port . '/api';
    $ct2Stats = [
        'login_get' => [],
        'login_post' => [],
        'dashboard_get' => [],
        'agents_filtered_get' => [],
        'module_status_api_get' => [],
        'financial_export_metadata_get' => [],
    ];

    ct2Log($ct2Prefix, 'Sampling repeated request timings.');
    for ($ct2Iteration = 0; $ct2Iteration < $ct2Iterations; $ct2Iteration++) {
        $ct2LoginSession = ct2CreateHttpSession($ct2TempDir);
        $ct2LoginPage = ct2HttpRequest('GET', $ct2BaseUrl . '?module=auth&action=login', $ct2LoginSession);
        ct2AssertSecondsWithinLimit($ct2LoginPage['time_total'], $ct2MaxSeconds, 'Login page GET', $ct2Prefix);
        ct2AssertContains($ct2LoginPage['body'], 'CT2 Back-Office Login', 'Login page did not render during repeated sampling', $ct2Prefix);
        $ct2Stats['login_get'][] = $ct2LoginPage['time_total'];

        $ct2Csrf = ct2ExtractCsrf($ct2LoginPage['body']);
        $ct2LoginPost = ct2HttpRequest(
            'POST',
            $ct2BaseUrl . '?module=auth&action=login',
            $ct2LoginSession,
            [],
            [
                'ct2_csrf_token' => $ct2Csrf,
                'username' => 'ct2admin',
                'password' => 'ChangeMe123!',
            ]
        );
        ct2AssertSecondsWithinLimit($ct2LoginPost['time_total'], $ct2MaxSeconds, 'Login submit POST', $ct2Prefix);
        ct2AssertContains($ct2LoginPost['body'], 'Back-Office Dashboard', 'Repeated login submit did not reach the dashboard', $ct2Prefix);
        $ct2Stats['login_post'][] = $ct2LoginPost['time_total'];
    }

    $ct2Session = ct2CreateHttpSession($ct2TempDir);
    $ct2LoginPage = ct2HttpRequest('GET', $ct2BaseUrl . '?module=auth&action=login', $ct2Session);
    $ct2Csrf = ct2ExtractCsrf($ct2LoginPage['body']);
    $ct2Login = ct2HttpRequest(
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
    ct2AssertContains($ct2Login['body'], 'Back-Office Dashboard', 'Seeded admin login did not reach the dashboard', $ct2Prefix);

    for ($ct2Iteration = 0; $ct2Iteration < $ct2Iterations; $ct2Iteration++) {
        $ct2Dashboard = ct2HttpRequest('GET', $ct2BaseUrl . '?module=dashboard&action=index', $ct2Session);
        ct2AssertSecondsWithinLimit($ct2Dashboard['time_total'], $ct2MaxSeconds, 'Dashboard GET', $ct2Prefix);
        ct2AssertContains($ct2Dashboard['body'], 'Back-Office Dashboard', 'Dashboard route did not render during repeated sampling', $ct2Prefix);
        $ct2Stats['dashboard_get'][] = $ct2Dashboard['time_total'];

        $ct2Agents = ct2HttpRequest('GET', $ct2BaseUrl . '?module=agents&action=index&search=AGT-CT2-001', $ct2Session);
        ct2AssertSecondsWithinLimit($ct2Agents['time_total'], $ct2MaxSeconds, 'Agents filtered GET', $ct2Prefix);
        ct2AssertContains($ct2Agents['body'], 'AGT-CT2-001', 'Agents filtered route did not render the seeded record during repeated sampling', $ct2Prefix);
        $ct2Stats['agents_filtered_get'][] = $ct2Agents['time_total'];

        $ct2ModuleStatus = ct2HttpRequest('GET', $ct2ApiBaseUrl . '/ct2_module_status.php', $ct2Session);
        ct2AssertSecondsWithinLimit($ct2ModuleStatus['time_total'], $ct2MaxSeconds, 'Module status API GET', $ct2Prefix);
        ct2AssertContains($ct2ModuleStatus['body'], '"success":true', 'Module status API did not return a success payload during repeated sampling', $ct2Prefix);
        $ct2Stats['module_status_api_get'][] = $ct2ModuleStatus['time_total'];

        $ct2FinancialMetadata = ct2HttpRequest(
            'GET',
            $ct2ApiBaseUrl . '/ct2_financial_exports.php?ct2_report_run_id=1&source_module=suppliers',
            $ct2Session
        );
        ct2AssertSecondsWithinLimit($ct2FinancialMetadata['time_total'], $ct2MaxSeconds, 'Financial export metadata GET', $ct2Prefix);
        ct2AssertContains($ct2FinancialMetadata['body'], '"success":true', 'Financial export metadata API did not return a success payload during repeated sampling', $ct2Prefix);
        $ct2Stats['financial_export_metadata_get'][] = $ct2FinancialMetadata['time_total'];
    }

    ct2Log($ct2Prefix, 'Repeated timing summary:');
    foreach ($ct2Stats as $ct2Label => $ct2Samples) {
        fwrite(STDOUT, ct2SummarizeSamples($ct2Label, $ct2Samples) . PHP_EOL);
    }

    ct2Log($ct2Prefix, 'CT2 load profile checks passed.');
} finally {
    if (is_array($ct2Server)) {
        ct2StopProcess($ct2Server['proc']);
    }
    ct2RemoveDir($ct2TempDir);
}
