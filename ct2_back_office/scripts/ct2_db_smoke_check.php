<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/ct2_bootstrap.php';

if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "pdo_mysql extension is not loaded.\n");
    exit(1);
}

$ct2Config = CT2_Database::getConfig();
$ct2SessionPath = CT2_BASE_PATH . '/storage/sessions';
$ct2UploadPath = CT2_BASE_PATH . '/storage/uploads';

if (!is_dir($ct2SessionPath)) {
    fwrite(STDERR, "Session path does not exist: {$ct2SessionPath}\n");
    exit(1);
}

if (!is_writable($ct2SessionPath)) {
    fwrite(STDERR, "Session path is not writable: {$ct2SessionPath}\n");
    exit(1);
}

if (!is_dir($ct2UploadPath)) {
    fwrite(STDERR, "Upload path does not exist: {$ct2UploadPath}\n");
    exit(1);
}

if (!is_writable($ct2UploadPath)) {
    fwrite(STDERR, "Upload path is not writable: {$ct2UploadPath}\n");
    exit(1);
}

try {
    $ct2Pdo = CT2_Database::getConnection();
} catch (Throwable $ct2Exception) {
    fwrite(
        STDERR,
        sprintf(
            "Unable to connect to CT2 database %s on %s:%s as %s: %s\n",
            $ct2Config['name'],
            $ct2Config['host'],
            $ct2Config['port'],
            $ct2Config['username'],
            $ct2Exception->getMessage()
        )
    );
    exit(1);
}

$ct2CurrentDatabase = (string) ($ct2Pdo->query('SELECT DATABASE()')->fetchColumn() ?: '');
if ($ct2CurrentDatabase === '') {
    fwrite(STDERR, "Connected to MySQL but no active CT2 database was selected.\n");
    exit(1);
}

$ct2RequiredTables = [
    'ct2_users',
    'ct2_roles',
    'ct2_user_roles',
    'ct2_role_permissions',
    'ct2_agents',
    'ct2_suppliers',
    'ct2_tour_packages',
    'ct2_campaigns',
    'ct2_visa_applications',
    'ct2_financial_reports',
];

$ct2TableStatement = $ct2Pdo->prepare(
    'SELECT COUNT(*)
     FROM information_schema.tables
     WHERE table_schema = :table_schema
       AND table_name = :table_name'
);

$ct2MissingTables = [];
foreach ($ct2RequiredTables as $ct2RequiredTable) {
    $ct2TableStatement->execute(
        [
            'table_schema' => $ct2CurrentDatabase,
            'table_name' => $ct2RequiredTable,
        ]
    );

    if ((int) $ct2TableStatement->fetchColumn() !== 1) {
        $ct2MissingTables[] = $ct2RequiredTable;
    }
}

if ($ct2MissingTables !== []) {
    fwrite(STDERR, "Missing CT2 tables:\n" . implode("\n", $ct2MissingTables) . "\n");
    exit(1);
}

$ct2ColumnStatement = $ct2Pdo->prepare(
    'SELECT COUNT(*)
     FROM information_schema.columns
     WHERE table_schema = :table_schema
       AND table_name = :table_name
       AND column_name = :column_name'
);
$ct2ColumnStatement->execute(
    [
        'table_schema' => $ct2CurrentDatabase,
        'table_name' => 'ct2_documents',
        'column_name' => 'file_size_bytes',
    ]
);

if ((int) $ct2ColumnStatement->fetchColumn() !== 1) {
    fwrite(STDERR, "ct2_documents.file_size_bytes is missing from the active CT2 schema.\n");
    exit(1);
}

$ct2AdminUserStatement = $ct2Pdo->prepare(
    'SELECT ct2_user_id
     FROM ct2_users
     WHERE username = :username
     LIMIT 1'
);
$ct2AdminUserStatement->execute(['username' => 'ct2admin']);
$ct2AdminUserId = $ct2AdminUserStatement->fetchColumn();

if ($ct2AdminUserId === false) {
    fwrite(STDERR, "Seeded CT2 admin user was not found.\n");
    exit(1);
}

$ct2RoleStatement = $ct2Pdo->prepare(
    'SELECT COUNT(*)
     FROM ct2_user_roles AS ur
     INNER JOIN ct2_roles AS r ON r.ct2_role_id = ur.ct2_role_id
     WHERE ur.ct2_user_id = :ct2_user_id
       AND r.role_key = :role_key'
);
$ct2RoleStatement->execute(
    [
        'ct2_user_id' => (int) $ct2AdminUserId,
        'role_key' => 'system_admin',
    ]
);

if ((int) $ct2RoleStatement->fetchColumn() !== 1) {
    fwrite(STDERR, "Seeded CT2 admin user is missing the system_admin role.\n");
    exit(1);
}

$ct2PermissionStatement = $ct2Pdo->prepare(
    'SELECT COUNT(*)
     FROM ct2_role_permissions AS rp
     INNER JOIN ct2_roles AS r ON r.ct2_role_id = rp.ct2_role_id
     WHERE r.role_key = :role_key
       AND rp.permission_key IN ("dashboard.view", "api.access", "financial.view")'
);
$ct2PermissionStatement->execute(['role_key' => 'system_admin']);

if ((int) $ct2PermissionStatement->fetchColumn() < 3) {
    fwrite(STDERR, "System admin permission seed is incomplete.\n");
    exit(1);
}

$ct2RequiredUsers = [
    'ct2admin',
    'ct2manager',
    'ct2lead',
    'ct2desk',
    'ct2finance',
];

$ct2RequiredUserStatement = $ct2Pdo->prepare(
    'SELECT COUNT(*)
     FROM ct2_users
     WHERE username = :username'
);

foreach ($ct2RequiredUsers as $ct2RequiredUser) {
    $ct2RequiredUserStatement->execute(['username' => $ct2RequiredUser]);
    if ((int) $ct2RequiredUserStatement->fetchColumn() !== 1) {
        fwrite(STDERR, "Required QA user seed is missing: {$ct2RequiredUser}\n");
        exit(1);
    }
}

$ct2SeedChecks = [
    'SELECT COUNT(*) FROM ct2_agents WHERE agent_code = "AGT-CT2-001"' => 'Seeded agent AGT-CT2-001 is missing.',
    'SELECT COUNT(*) FROM ct2_suppliers WHERE supplier_code = "SUP-CT2-001"' => 'Seeded supplier SUP-CT2-001 is missing.',
    'SELECT COUNT(*) FROM ct2_tour_packages WHERE package_name = "Northern Luzon Discovery QA"' => 'Seeded tour package is missing.',
    'SELECT COUNT(*) FROM ct2_campaigns WHERE campaign_code = "CT2-MKT-001"' => 'Seeded marketing campaign CT2-MKT-001 is missing.',
    'SELECT COUNT(*) FROM ct2_visa_applications WHERE application_reference = "VISA-APP-001"' => 'Seeded visa application VISA-APP-001 is missing.',
    'SELECT COUNT(*) FROM ct2_report_runs WHERE run_label = "QA Baseline Cross-Module Run"' => 'Seeded financial report run is missing.',
    'SELECT COUNT(*) FROM ct2_approval_workflows WHERE approval_status = "pending"' => 'Seeded pending approval workflows are missing.',
];

foreach ($ct2SeedChecks as $ct2Query => $ct2Error) {
    if ((int) $ct2Pdo->query($ct2Query)->fetchColumn() < 1) {
        fwrite(STDERR, $ct2Error . "\n");
        exit(1);
    }
}

echo "CT2 DB smoke check passed.\n";
echo "Database: {$ct2CurrentDatabase}\n";
echo "Host: {$ct2Config['host']}:{$ct2Config['port']}\n";
echo "Admin user: ct2admin\n";
