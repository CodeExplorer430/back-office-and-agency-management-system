<?php

declare(strict_types=1);

require_once __DIR__ . '/ct2_validation_common.php';

ct2AssertCli();

$ct2Prefix = 'ct2-role-uat';
$ct2TempDir = ct2CreateTempDir($ct2Prefix);
$ct2Server = null;
$ct2RunId = 'ROLE-UAT-' . time();

try {
    ct2Log($ct2Prefix, 'Starting local CT2 PHP server.');
    $ct2Server = ct2StartPhpServer(8099, $ct2TempDir);
    $ct2BaseUrl = 'http://127.0.0.1:8099/ct2_index.php';

    $ct2ManagerSession = ct2CreateHttpSession($ct2TempDir);
    $ct2DeskSession = ct2CreateHttpSession($ct2TempDir);
    $ct2FinanceSession = ct2CreateHttpSession($ct2TempDir);

    $ct2LoginUser = static function (array $session, string $username, string $password) use ($ct2BaseUrl, $ct2Prefix): void {
        $ct2LoginPage = ct2HttpRequest('GET', $ct2BaseUrl . '?module=auth&action=login', $session);
        $ct2Csrf = ct2ExtractCsrf($ct2LoginPage['body']);
        $ct2Login = ct2HttpRequest(
            'POST',
            $ct2BaseUrl . '?module=auth&action=login',
            $session,
            [],
            [
                'ct2_csrf_token' => $ct2Csrf,
                'username' => $username,
                'password' => $password,
            ]
        );
        ct2AssertContains($ct2Login['body'], 'CORE TRANSACTION 2', 'Login failed for ' . $username, $ct2Prefix);
    };

    ct2Log($ct2Prefix, 'Verifying back-office manager role flow.');
    $ct2LoginUser($ct2ManagerSession, 'ct2manager', 'ChangeMe123!');
    $ct2Approvals = ct2HttpRequest('GET', $ct2BaseUrl . '?module=approvals&action=index', $ct2ManagerSession);
    ct2AssertStatus(200, $ct2Approvals, 'Manager approvals page did not load', $ct2Prefix);
    ct2AssertContains($ct2Approvals['body'], 'Approval Queue', 'Manager approvals page did not render the approval queue', $ct2Prefix);
    ct2AssertContains($ct2Approvals['body'], 'Decision notes', 'Manager approvals page did not expose the decision form', $ct2Prefix);
    $ct2ManagerCsrf = ct2ExtractCsrf($ct2Approvals['body']);
    if (!preg_match('/name="ct2_approval_workflow_id" value="([0-9]+)"/', $ct2Approvals['body'], $ct2Matches)) {
        ct2Fail($ct2Prefix, 'Unable to resolve the first approval workflow for the manager role walkthrough.');
    }
    $ct2ManagerDecision = ct2HttpRequest(
        'POST',
        $ct2BaseUrl . '?module=approvals&action=decide',
        $ct2ManagerSession,
        [],
        [
            'ct2_csrf_token' => $ct2ManagerCsrf,
            'ct2_approval_workflow_id' => $ct2Matches[1],
            'approval_status' => 'approved',
            'decision_notes' => 'Manager role walkthrough ' . $ct2RunId,
        ]
    );
    ct2AssertStatus(200, $ct2ManagerDecision, 'Manager approval decision did not complete', $ct2Prefix);
    ct2AssertContains($ct2ManagerDecision['body'], 'Approval Queue', 'Manager approval decision did not return to the approval queue', $ct2Prefix);

    $ct2ManagerMarketing = ct2HttpRequest('GET', $ct2BaseUrl . '?module=marketing&action=index', $ct2ManagerSession);
    ct2AssertStatus(200, $ct2ManagerMarketing, 'Manager marketing page did not load', $ct2Prefix);
    ct2AssertContains($ct2ManagerMarketing['body'], 'Marketing and Promotions Management', 'Manager marketing page did not render', $ct2Prefix);

    ct2Log($ct2Prefix, 'Verifying front desk role flow.');
    $ct2LoginUser($ct2DeskSession, 'ct2desk', 'ChangeMe123!');
    $ct2DeskVisa = ct2HttpRequest('GET', $ct2BaseUrl . '?module=visa&action=index', $ct2DeskSession);
    ct2AssertStatus(200, $ct2DeskVisa, 'Front desk visa page did not load', $ct2Prefix);
    ct2AssertContains($ct2DeskVisa['body'], 'Document and Visa Assistance', 'Front desk visa page did not render', $ct2Prefix);

    $ct2DeskFinancial = ct2HttpRequest('GET', $ct2BaseUrl . '?module=financial&action=index', $ct2DeskSession);
    ct2AssertStatus(403, $ct2DeskFinancial, 'Front desk financial page was not denied', $ct2Prefix);
    ct2AssertContains($ct2DeskFinancial['body'], 'Forbidden', 'Front desk financial denial did not render a forbidden response', $ct2Prefix);

    ct2Log($ct2Prefix, 'Verifying accounting staff role flow.');
    $ct2LoginUser($ct2FinanceSession, 'ct2finance', 'ChangeMe123!');
    $ct2FinanceFinancial = ct2HttpRequest(
        'GET',
        $ct2BaseUrl . '?module=financial&action=index&ct2_report_run_id=1&source_module=suppliers',
        $ct2FinanceSession
    );
    ct2AssertStatus(200, $ct2FinanceFinancial, 'Accounting financial page did not load', $ct2Prefix);
    ct2AssertContains($ct2FinanceFinancial['body'], 'Financial Reporting and Analytics', 'Accounting financial page did not render', $ct2Prefix);
    ct2AssertContains($ct2FinanceFinancial['body'], 'Export CSV', 'Accounting financial page did not expose the export trigger', $ct2Prefix);

    $ct2FinanceExport = ct2HttpRequest(
        'GET',
        $ct2BaseUrl . '?module=financial&action=exportCsv&ct2_report_run_id=1&source_module=suppliers',
        $ct2FinanceSession
    );
    ct2AssertStatus(200, $ct2FinanceExport, 'Accounting CSV export did not succeed', $ct2Prefix);
    ct2AssertHeaderContains($ct2FinanceExport, 'Content-Type', 'text/csv', 'Accounting CSV export did not return CSV content', $ct2Prefix);

    ct2Log($ct2Prefix, 'CT2 role-specific UAT checks passed.');
} finally {
    if (is_array($ct2Server)) {
        ct2StopProcess($ct2Server['proc']);
    }
    ct2RemoveDir($ct2TempDir);
}
