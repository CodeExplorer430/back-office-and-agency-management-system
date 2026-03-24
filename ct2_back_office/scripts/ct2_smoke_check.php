<?php

declare(strict_types=1);

$ct2RequiredFiles = [
    __DIR__ . '/../ct2_index.php',
    __DIR__ . '/../config/ct2_bootstrap.php',
    __DIR__ . '/../config/ct2_database.php',
    __DIR__ . '/../config/ct2_UploadService.php',
    __DIR__ . '/ct2_format_check.php',
    __DIR__ . '/ct2_format_check.sh',
    __DIR__ . '/ct2_db_smoke_check.php',
    __DIR__ . '/ct2_regression_probe.php',
    __DIR__ . '/ct2_powershell_common.ps1',
    __DIR__ . '/ct2_lint.ps1',
    __DIR__ . '/ct2_format_check.ps1',
    __DIR__ . '/ct2_smoke_check.ps1',
    __DIR__ . '/ct2_db_smoke_check.ps1',
    __DIR__ . '/ct2_route_matrix_check.ps1',
    __DIR__ . '/ct2_runtime_hardening_check.ps1',
    __DIR__ . '/ct2_api_post_regression_check.ps1',
    __DIR__ . '/ct2_browser_accessibility_check.ps1',
    __DIR__ . '/ct2_ui_regression_check.ps1',
    __DIR__ . '/ct2_nfr_sanity_check.ps1',
    __DIR__ . '/ct2_load_profile_check.ps1',
    __DIR__ . '/ct2_role_uat_check.ps1',
    __DIR__ . '/ct2_validation_suite.ps1',
    __DIR__ . '/ct2_api_post_regression_check.sh',
    __DIR__ . '/ct2_browser_accessibility_check.sh',
    __DIR__ . '/ct2_browser_accessibility_check.js',
    __DIR__ . '/ct2_browser_accessibility_check.php',
    __DIR__ . '/ct2_ui_regression_check.sh',
    __DIR__ . '/ct2_ui_regression_check.js',
    __DIR__ . '/ct2_ui_regression_check.php',
    __DIR__ . '/ct2_release_artifact.sh',
    __DIR__ . '/ct2_live_http_health_check.php',
    __DIR__ . '/ct2_live_http_health_check.sh',
    __DIR__ . '/ct2_cpanel_post_deploy_check.sh',
    __DIR__ . '/ct2_cpanel_deploy.sh',
    __DIR__ . '/ct2_api_post_regression_check.php',
    __DIR__ . '/ct2_load_profile_check.php',
    __DIR__ . '/ct2_nfr_sanity_check.php',
    __DIR__ . '/ct2_role_uat_check.php',
    __DIR__ . '/ct2_route_matrix_check.php',
    __DIR__ . '/ct2_runtime_hardening_check.php',
    __DIR__ . '/ct2_validation_common.php',
    __DIR__ . '/ct2_load_profile_check.sh',
    __DIR__ . '/ct2_nfr_sanity_check.sh',
    __DIR__ . '/ct2_role_uat_check.sh',
    __DIR__ . '/ct2_runtime_hardening_check.sh',
    __DIR__ . '/ct2_route_matrix_check.sh',
    __DIR__ . '/../assets/css/ct2_styles.css',
    __DIR__ . '/../api/ct2_agents.php',
    __DIR__ . '/../api/ct2_staff.php',
    __DIR__ . '/../api/ct2_approvals.php',
    __DIR__ . '/../api/ct2_suppliers.php',
    __DIR__ . '/../api/ct2_supplier_onboarding.php',
    __DIR__ . '/../api/ct2_supplier_contracts.php',
    __DIR__ . '/../api/ct2_supplier_kpis.php',
    __DIR__ . '/../api/ct2_tour_availability.php',
    __DIR__ . '/../api/ct2_marketing_campaigns.php',
    __DIR__ . '/../api/ct2_promotions.php',
    __DIR__ . '/../api/ct2_vouchers.php',
    __DIR__ . '/../api/ct2_affiliates.php',
    __DIR__ . '/../api/ct2_marketing_reports.php',
    __DIR__ . '/../api/ct2_financial_reports.php',
    __DIR__ . '/../api/ct2_financial_snapshots.php',
    __DIR__ . '/../api/ct2_financial_exports.php',
    __DIR__ . '/../api/ct2_visa_applications.php',
    __DIR__ . '/../api/ct2_visa_checklists.php',
    __DIR__ . '/../api/ct2_visa_payments.php',
    __DIR__ . '/../api/ct2_visa_status.php',
    __DIR__ . '/../api/ct2_resources.php',
    __DIR__ . '/../api/ct2_dispatch_orders.php',
    __DIR__ . '/../api/ct2_module_status.php',
    __DIR__ . '/../ct2_setup.sql',
    __DIR__ . '/../../docs/ct2_manual_qa_pack.md',
    __DIR__ . '/../../docs/ct2_api_validation.md',
    __DIR__ . '/../../docs/ct2_qa_execution_report.md',
    __DIR__ . '/../../docs/ct2_qa_fix_queue.md',
    __DIR__ . '/../../docs/ct2_requirements_traceability_matrix.md',
    __DIR__ . '/../../docs/ct2_requirements_audit_backlog.md',
    __DIR__ . '/../../docs/ct2_technical_debt_register.md',
    __DIR__ . '/../../docs/ct2_nfr_evidence.md',
    __DIR__ . '/../../docs/ct2_performance_accessibility_evidence.md',
    __DIR__ . '/../../docs/ct2_windows_xampp_validation_pack.md',
    __DIR__ . '/../../docs/ct2_windows_xampp_result_template.md',
    __DIR__ . '/../../docs/ct2_deployment_guide.md',
    __DIR__ . '/../../docs/ct2_setup_guide.md',
    __DIR__ . '/../../docs/ct2_integration_guide.md',
    __DIR__ . '/../../docs/ct2_troubleshooting_guide.md',
    __DIR__ . '/../../docs/ct2_operator_runbook.md',
    __DIR__ . '/../../docs/ct2_quality_gate.md',
    __DIR__ . '/../../docs/ct2_cpanel_release_flow.md',
    __DIR__ . '/../../.github/workflows/ct2_quality_gate.yml',
    __DIR__ . '/../../.github/workflows/ct2_cpanel_release.yml',
];

$ct2MissingFiles = [];
foreach ($ct2RequiredFiles as $ct2File) {
    if (!is_file($ct2File)) {
        $ct2MissingFiles[] = $ct2File;
    }
}

if ($ct2MissingFiles !== []) {
    fwrite(STDERR, "Missing CT2 files:\n" . implode("\n", $ct2MissingFiles) . "\n");
    exit(1);
}

$ct2Sql = file_get_contents(__DIR__ . '/../ct2_setup.sql');
if ($ct2Sql === false || strpos($ct2Sql, 'ct2_') === false) {
    fwrite(STDERR, "ct2_setup.sql does not appear to contain CT2-prefixed schema definitions.\n");
    exit(1);
}

echo "CT2 smoke check passed.\n";
