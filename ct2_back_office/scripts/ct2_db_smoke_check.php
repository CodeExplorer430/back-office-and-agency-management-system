<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/ct2_bootstrap.php';

if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "pdo_mysql extension is not loaded.\n");
    exit(1);
}

$ct2Config = CT2_Database::getConfig();
$ct2SessionPath = CT2_BASE_PATH . '/storage/sessions';

if (!is_dir($ct2SessionPath)) {
    fwrite(STDERR, "Session path does not exist: {$ct2SessionPath}\n");
    exit(1);
}

if (!is_writable($ct2SessionPath)) {
    fwrite(STDERR, "Session path is not writable: {$ct2SessionPath}\n");
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

echo "CT2 DB smoke check passed.\n";
echo "Database: {$ct2CurrentDatabase}\n";
echo "Host: {$ct2Config['host']}:{$ct2Config['port']}\n";
echo "Admin user: ct2admin\n";
