<?php

declare(strict_types=1);

$ct2RequiredFiles = [
    __DIR__ . '/../ct2_index.php',
    __DIR__ . '/../config/ct2_bootstrap.php',
    __DIR__ . '/../config/ct2_database.php',
    __DIR__ . '/../config/ct2_UploadService.php',
    __DIR__ . '/ct2_db_smoke_check.php',
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
