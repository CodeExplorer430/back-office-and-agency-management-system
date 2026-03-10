<?php

declare(strict_types=1);

require_once __DIR__ . '/ct2_validation_common.php';

ct2AssertCli();

$ct2Prefix = 'ct2-route-matrix';
$ct2TempDir = ct2CreateTempDir($ct2Prefix);
$ct2Server = null;

try {
    ct2Log($ct2Prefix, 'Starting local CT2 PHP server.');
    $ct2Server = ct2StartPhpServer(8093, $ct2TempDir);
    $ct2BaseUrl = 'http://127.0.0.1:8093/ct2_index.php';
    $ct2ApiBaseUrl = 'http://127.0.0.1:8093/api';
    $ct2Session = ct2CreateHttpSession($ct2TempDir);

    ct2Log($ct2Prefix, 'Signing in as seeded CT2 administrator.');
    $ct2LoginPage = ct2HttpRequest('GET', $ct2BaseUrl . '?module=auth&action=login', $ct2Session);
    ct2AssertStatus(200, $ct2LoginPage, 'Login page did not load', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2LoginPage['body']);
    $ct2LoginResult = ct2HttpRequest(
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
    ct2AssertStatus(200, $ct2LoginResult, 'Login submission did not complete', $ct2Prefix);
    ct2AssertContains($ct2LoginResult['body'], 'Back-Office Dashboard', 'Login did not reach the dashboard', $ct2Prefix);

    $ct2ReportRunId = ct2Probe('report-run-id', 'QA Baseline Cross-Module Run');
    $ct2FinancialReportId = ct2Probe('financial-report-id', 'CT2-OPS-001');

    ct2Log($ct2Prefix, 'Running route breadth checks.');
    $ct2Routes = [
        [
            'url' => $ct2BaseUrl . '?module=dashboard&action=index',
            'status' => 200,
            'needle' => 'Back-Office Dashboard',
            'error' => 'Dashboard route did not render correctly',
        ],
        [
            'url' => $ct2BaseUrl . '?module=agents&action=index&search=AGT-CT2-001',
            'status' => 200,
            'needle' => 'AGT-CT2-001',
            'error' => 'Agents filtered route did not render correctly',
        ],
        [
            'url' => $ct2BaseUrl . '?module=staff&action=index&search=STF-CT2-001',
            'status' => 200,
            'needle' => 'STF-CT2-001',
            'error' => 'Staff filtered route did not render correctly',
        ],
        [
            'url' => $ct2BaseUrl . '?module=suppliers&action=index&search=SUP-CT2-001',
            'status' => 200,
            'needle' => 'SUP-CT2-001',
            'error' => 'Suppliers filtered route did not render correctly',
        ],
        [
            'url' => $ct2BaseUrl . '?module=availability&action=index&search=Skyline',
            'status' => 200,
            'needle' => 'Skyline Coaster 18-Seater',
            'error' => 'Availability filtered route did not render correctly',
        ],
        [
            'url' => $ct2BaseUrl . '?module=marketing&action=index&search=North%20Luzon',
            'status' => 200,
            'needle' => 'North Luzon Coach Summer Push',
            'error' => 'Marketing filtered route did not render correctly',
        ],
        [
            'url' => $ct2BaseUrl . '?module=financial&action=index&ct2_report_run_id=' . rawurlencode($ct2ReportRunId) . '&ct2_financial_report_id=' . rawurlencode($ct2FinancialReportId),
            'status' => 200,
            'needle' => 'Report Catalog',
            'error' => 'Financial route did not render correctly',
        ],
        [
            'url' => $ct2BaseUrl . '?module=visa&action=index&search=VISA-APP-001',
            'status' => 200,
            'needle' => 'VISA-APP-001',
            'error' => 'Visa filtered route did not render correctly',
        ],
        [
            'url' => $ct2BaseUrl . '?module=approvals&action=index',
            'status' => 200,
            'needle' => 'Approval Queue',
            'error' => 'Approvals route did not render correctly',
        ],
    ];

    foreach ($ct2Routes as $ct2Route) {
        $ct2Response = ct2HttpRequest('GET', $ct2Route['url'], $ct2Session);
        ct2AssertStatus($ct2Route['status'], $ct2Response, $ct2Route['error'], $ct2Prefix);
        ct2AssertContains($ct2Response['body'], $ct2Route['needle'], $ct2Route['error'], $ct2Prefix);
        ct2AssertHtmlClean($ct2Response['body'], $ct2Route['error'], $ct2Prefix);
    }

    ct2Log($ct2Prefix, 'Running representative JSON route checks.');
    $ct2ApiRoutes = [
        [
            'url' => $ct2ApiBaseUrl . '/ct2_module_status.php',
            'needle' => '"module_key":"marketing-promotions-management"',
            'error' => 'Module status endpoint did not return the expected module list',
        ],
        [
            'url' => $ct2ApiBaseUrl . '/ct2_agents.php?search=AGT-CT2-001',
            'needle' => '"agent_code":"AGT-CT2-001"',
            'error' => 'Agents API route did not return the seeded agent',
        ],
        [
            'url' => $ct2ApiBaseUrl . '/ct2_staff.php?search=STF-CT2-001',
            'needle' => '"staff_code":"STF-CT2-001"',
            'error' => 'Staff API route did not return the seeded staff record',
        ],
        [
            'url' => $ct2ApiBaseUrl . '/ct2_suppliers.php?search=SUP-CT2-001',
            'needle' => '"supplier_code":"SUP-CT2-001"',
            'error' => 'Suppliers API route did not return the seeded supplier',
        ],
        [
            'url' => $ct2ApiBaseUrl . '/ct2_resources.php?search=Skyline',
            'needle' => '"resource_name":"Skyline Coaster 18-Seater"',
            'error' => 'Resources API route did not return the seeded resource',
        ],
        [
            'url' => $ct2ApiBaseUrl . '/ct2_marketing_campaigns.php?search=CT2-MKT-001',
            'needle' => '"campaign_code":"CT2-MKT-001"',
            'error' => 'Marketing campaigns API route did not return the seeded campaign',
        ],
        [
            'url' => $ct2ApiBaseUrl . '/ct2_affiliates.php?search=AFF-CT2-001',
            'needle' => '"affiliate_code":"AFF-CT2-001"',
            'error' => 'Affiliates API route did not return the seeded affiliate',
        ],
        [
            'url' => $ct2ApiBaseUrl . '/ct2_visa_applications.php?search=VISA-APP-001',
            'needle' => '"application_reference":"VISA-APP-001"',
            'error' => 'Visa applications API route did not return the seeded application',
        ],
        [
            'url' => $ct2ApiBaseUrl . '/ct2_financial_reports.php?ct2_financial_report_id=' . rawurlencode($ct2FinancialReportId),
            'needle' => '"report_code":"CT2-OPS-001"',
            'error' => 'Financial reports API route did not return the seeded report definition',
        ],
    ];

    foreach ($ct2ApiRoutes as $ct2Route) {
        $ct2Response = ct2HttpRequest('GET', $ct2Route['url'], $ct2Session);
        ct2AssertStatus(200, $ct2Response, $ct2Route['error'], $ct2Prefix);
        ct2AssertJsonSuccess($ct2Response, $ct2Route['error'], $ct2Prefix);
        ct2AssertContains($ct2Response['body'], $ct2Route['needle'], $ct2Route['error'], $ct2Prefix);
    }

    ct2Log($ct2Prefix, 'Running export breadth check.');
    $ct2Export = ct2HttpRequest(
        'GET',
        $ct2BaseUrl . '?module=financial&action=exportCsv&ct2_report_run_id=' . rawurlencode($ct2ReportRunId),
        $ct2Session
    );
    ct2AssertStatus(200, $ct2Export, 'Financial export route did not return 200', $ct2Prefix);
    ct2AssertHeaderContains($ct2Export, 'Content-Type', 'text/csv', 'Financial export route did not return CSV content type', $ct2Prefix);
    ct2AssertContains($ct2Export['body'], 'report_run_id,report_name,run_label,source_module', 'Financial export route did not return CSV headers', $ct2Prefix);

    ct2Log($ct2Prefix, 'CT2 route matrix check passed.');
} finally {
    if (is_array($ct2Server)) {
        ct2StopProcess($ct2Server['proc']);
    }
    ct2RemoveDir($ct2TempDir);
}
