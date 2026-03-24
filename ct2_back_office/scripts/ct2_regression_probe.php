<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/ct2_bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CT2 regression probe must be run from the CLI.\n");
    exit(1);
}

$ct2Command = $argv[1] ?? '';
if ($ct2Command === '') {
    fwrite(STDERR, "Usage: php ct2_regression_probe.php <command> [arguments]\n");
    exit(1);
}

$ct2Pdo = CT2_Database::getConnection();

try {
    switch ($ct2Command) {
        case 'audit-count':
            $ct2ActionKey = $argv[2] ?? '';
            if ($ct2ActionKey === '') {
                throw new InvalidArgumentException('audit-count requires an action key.');
            }

            $ct2Sql = 'SELECT COUNT(*) FROM ct2_audit_logs WHERE action_key = :action_key';
            $ct2Params = ['action_key' => $ct2ActionKey];

            if (($argv[3] ?? '') !== '') {
                $ct2Sql .= ' AND entity_type = :entity_type';
                $ct2Params['entity_type'] = $argv[3];
            }

            if (($argv[4] ?? '') !== '') {
                $ct2Sql .= ' AND entity_id = :entity_id';
                $ct2Params['entity_id'] = (int) $argv[4];
            }

            echo (string) ct2ProbeScalar($ct2Pdo, $ct2Sql, $ct2Params);
            break;

        case 'api-log-count':
            $ct2EndpointName = $argv[2] ?? '';
            if ($ct2EndpointName === '') {
                throw new InvalidArgumentException('api-log-count requires an endpoint name.');
            }

            $ct2Sql = 'SELECT COUNT(*) FROM ct2_api_logs WHERE endpoint_name = :endpoint_name';
            $ct2Params = ['endpoint_name' => $ct2EndpointName];

            if (($argv[3] ?? '') !== '') {
                $ct2Sql .= ' AND http_method = :http_method';
                $ct2Params['http_method'] = strtoupper((string) $argv[3]);
            }

            if (($argv[4] ?? '') !== '') {
                $ct2Sql .= ' AND status_code = :status_code';
                $ct2Params['status_code'] = (int) $argv[4];
            }

            echo (string) ct2ProbeScalar($ct2Pdo, $ct2Sql, $ct2Params);
            break;

        case 'agent-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_agents', 'ct2_agent_id', 'agent_code', $argv[2] ?? '', 'agent');
            break;

        case 'supplier-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_suppliers', 'ct2_supplier_id', 'supplier_code', $argv[2] ?? '', 'supplier');
            break;

        case 'campaign-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_campaigns', 'ct2_campaign_id', 'campaign_code', $argv[2] ?? '', 'campaign');
            break;

        case 'promotion-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_promotions', 'ct2_promotion_id', 'promotion_code', $argv[2] ?? '', 'promotion');
            break;

        case 'voucher-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_vouchers', 'ct2_voucher_id', 'voucher_code', $argv[2] ?? '', 'voucher');
            break;

        case 'affiliate-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_affiliates', 'ct2_affiliate_id', 'affiliate_code', $argv[2] ?? '', 'affiliate');
            break;

        case 'resource-id':
            echo (string) ct2ProbeIdByCode(
                $ct2Pdo,
                'ct2_inventory_resources',
                'ct2_resource_id',
                'resource_name',
                $argv[2] ?? '',
                'resource'
            );
            break;

        case 'package-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_tour_packages', 'ct2_package_id', 'package_name', $argv[2] ?? '', 'package');
            break;

        case 'visa-application-id':
            echo (string) ct2ProbeIdByCode(
                $ct2Pdo,
                'ct2_visa_applications',
                'ct2_visa_application_id',
                'application_reference',
                $argv[2] ?? '',
                'visa application'
            );
            break;

        case 'visa-type-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_visa_types', 'ct2_visa_type_id', 'visa_code', $argv[2] ?? '', 'visa type');
            break;

        case 'vehicle-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_dispatch_vehicles', 'ct2_vehicle_id', 'plate_number', $argv[2] ?? '', 'dispatch vehicle');
            break;

        case 'driver-id':
            echo (string) ct2ProbeIdByCode($ct2Pdo, 'ct2_dispatch_drivers', 'ct2_driver_id', 'full_name', $argv[2] ?? '', 'dispatch driver');
            break;

        case 'financial-report-id':
            echo (string) ct2ProbeIdByCode(
                $ct2Pdo,
                'ct2_financial_reports',
                'ct2_financial_report_id',
                'report_code',
                $argv[2] ?? '',
                'financial report'
            );
            break;

        case 'report-run-id':
            echo (string) ct2ProbeIdByCode(
                $ct2Pdo,
                'ct2_report_runs',
                'ct2_report_run_id',
                'run_label',
                $argv[2] ?? '',
                'report run'
            );
            break;

        case 'approval-id':
            $ct2SubjectType = $argv[2] ?? '';
            $ct2ReferenceCode = $argv[3] ?? '';
            echo (string) ct2ProbeApprovalField($ct2Pdo, $ct2SubjectType, $ct2ReferenceCode, 'ct2_approval_workflow_id');
            break;

        case 'approval-status':
            $ct2SubjectType = $argv[2] ?? '';
            $ct2ReferenceCode = $argv[3] ?? '';
            echo (string) ct2ProbeApprovalField($ct2Pdo, $ct2SubjectType, $ct2ReferenceCode, 'approval_status');
            break;

        case 'checklist-id':
            $ct2ApplicationReference = $argv[2] ?? '';
            $ct2ItemName = $argv[3] ?? '';
            echo (string) ct2ProbeChecklistField($ct2Pdo, $ct2ApplicationReference, $ct2ItemName, 'ct2_application_checklist_id');
            break;

        case 'checklist-status':
            $ct2ApplicationReference = $argv[2] ?? '';
            $ct2ItemName = $argv[3] ?? '';
            echo (string) ct2ProbeChecklistField($ct2Pdo, $ct2ApplicationReference, $ct2ItemName, 'checklist_status');
            break;

        case 'latest-document-path':
            $ct2ApplicationReference = $argv[2] ?? '';
            echo (string) ct2ProbeLatestDocumentField($ct2Pdo, $ct2ApplicationReference, 'file_path');
            break;

        case 'latest-document-name':
            $ct2ApplicationReference = $argv[2] ?? '';
            echo (string) ct2ProbeLatestDocumentField($ct2Pdo, $ct2ApplicationReference, 'file_name');
            break;

        case 'supplier-onboarding-field':
            $ct2SupplierCode = $argv[2] ?? '';
            $ct2Field = $argv[3] ?? '';
            echo (string) ct2ProbeSupplierOnboardingField($ct2Pdo, $ct2SupplierCode, $ct2Field);
            break;

        case 'flag-id':
            $ct2SourceModule = $argv[2] ?? '';
            $ct2ReferenceCode = $argv[3] ?? '';
            echo (string) ct2ProbeFlagField($ct2Pdo, $ct2SourceModule, $ct2ReferenceCode, 'ct2_reconciliation_flag_id');
            break;

        case 'flag-field':
            $ct2SourceModule = $argv[2] ?? '';
            $ct2ReferenceCode = $argv[3] ?? '';
            $ct2Field = $argv[4] ?? '';
            echo (string) ct2ProbeFlagField($ct2Pdo, $ct2SourceModule, $ct2ReferenceCode, $ct2Field);
            break;

        case 'ensure-user':
            $ct2Username = $argv[2] ?? '';
            $ct2Password = $argv[3] ?? '';
            $ct2IsActive = (int) ($argv[4] ?? 1);
            echo (string) ct2EnsureValidationUser($ct2Pdo, $ct2Username, $ct2Password, $ct2IsActive);
            break;

        case 'user-field':
            $ct2Username = $argv[2] ?? '';
            $ct2Field = $argv[3] ?? '';
            echo (string) ct2ProbeUserField($ct2Pdo, $ct2Username, $ct2Field);
            break;

        case 'session-log-count-by-user':
            $ct2Username = $argv[2] ?? '';
            echo (string) ct2ProbeSessionLogCountByUser($ct2Pdo, $ct2Username);
            break;

        default:
            throw new InvalidArgumentException('Unknown CT2 regression probe command: ' . $ct2Command);
    }
} catch (Throwable $ct2Exception) {
    fwrite(STDERR, $ct2Exception->getMessage() . "\n");
    exit(1);
}

function ct2ProbeScalar(PDO $ct2Pdo, string $ct2Sql, array $ct2Params = []): string
{
    $ct2Statement = $ct2Pdo->prepare($ct2Sql);
    $ct2Statement->execute($ct2Params);
    $ct2Value = $ct2Statement->fetchColumn();

    if ($ct2Value === false) {
        throw new RuntimeException('CT2 regression probe query returned no result.');
    }

    return (string) $ct2Value;
}

function ct2ProbeIdByCode(
    PDO $ct2Pdo,
    string $ct2TableName,
    string $ct2IdColumn,
    string $ct2LookupColumn,
    string $ct2LookupValue,
    string $ct2Label
): int {
    if ($ct2LookupValue === '') {
        throw new InvalidArgumentException('Missing lookup value for ' . $ct2Label . '.');
    }

    $ct2Statement = $ct2Pdo->prepare(
        sprintf(
            'SELECT %s FROM %s WHERE %s = :lookup_value LIMIT 1',
            $ct2IdColumn,
            $ct2TableName,
            $ct2LookupColumn
        )
    );
    $ct2Statement->execute(['lookup_value' => $ct2LookupValue]);
    $ct2Id = $ct2Statement->fetchColumn();

    if ($ct2Id === false) {
        throw new RuntimeException('Unable to resolve seeded ' . $ct2Label . ': ' . $ct2LookupValue);
    }

    return (int) $ct2Id;
}

function ct2ProbeApprovalField(PDO $ct2Pdo, string $ct2SubjectType, string $ct2ReferenceCode, string $ct2Field): string
{
    $ct2AllowedFields = ['ct2_approval_workflow_id', 'approval_status'];
    if (!in_array($ct2Field, $ct2AllowedFields, true)) {
        throw new InvalidArgumentException('Unsupported approval field: ' . $ct2Field);
    }

    [$ct2JoinSql, $ct2ReferenceColumn] = ct2ProbeApprovalReferenceTarget($ct2SubjectType);
    $ct2Statement = $ct2Pdo->prepare(
        sprintf(
            'SELECT aw.%s
             FROM ct2_approval_workflows AS aw
             %s
             WHERE %s = :reference_code
             LIMIT 1',
            $ct2Field,
            $ct2JoinSql,
            $ct2ReferenceColumn
        )
    );
    $ct2Statement->execute(['reference_code' => $ct2ReferenceCode]);
    $ct2Value = $ct2Statement->fetchColumn();

    if ($ct2Value === false) {
        throw new RuntimeException('Unable to resolve approval workflow for ' . $ct2SubjectType . ' reference ' . $ct2ReferenceCode);
    }

    return (string) $ct2Value;
}

function ct2ProbeApprovalReferenceTarget(string $ct2SubjectType): array
{
    return match ($ct2SubjectType) {
        'agent' => [
            'INNER JOIN ct2_agents AS ref ON ref.ct2_agent_id = aw.subject_id AND aw.subject_type = "agent"',
            'ref.agent_code',
        ],
        'supplier' => [
            'INNER JOIN ct2_suppliers AS ref ON ref.ct2_supplier_id = aw.subject_id AND aw.subject_type = "supplier"',
            'ref.supplier_code',
        ],
        'campaign' => [
            'INNER JOIN ct2_campaigns AS ref ON ref.ct2_campaign_id = aw.subject_id AND aw.subject_type = "campaign"',
            'ref.campaign_code',
        ],
        'promotion' => [
            'INNER JOIN ct2_promotions AS ref ON ref.ct2_promotion_id = aw.subject_id AND aw.subject_type = "promotion"',
            'ref.promotion_code',
        ],
        'visa_application' => [
            'INNER JOIN ct2_visa_applications AS ref ON ref.ct2_visa_application_id = aw.subject_id AND aw.subject_type = "visa_application"',
            'ref.application_reference',
        ],
        default => throw new InvalidArgumentException('Unsupported approval subject type: ' . $ct2SubjectType),
    };
}

function ct2ProbeChecklistField(PDO $ct2Pdo, string $ct2ApplicationReference, string $ct2ItemName, string $ct2Field): string
{
    $ct2AllowedFields = ['ct2_application_checklist_id', 'checklist_status'];
    if (!in_array($ct2Field, $ct2AllowedFields, true)) {
        throw new InvalidArgumentException('Unsupported checklist field: ' . $ct2Field);
    }

    if ($ct2ApplicationReference === '' || $ct2ItemName === '') {
        throw new InvalidArgumentException('checklist lookups require application reference and item name.');
    }

    $ct2Statement = $ct2Pdo->prepare(
        sprintf(
            'SELECT ac.%s
             FROM ct2_application_checklist AS ac
             INNER JOIN ct2_visa_applications AS va
                ON va.ct2_visa_application_id = ac.ct2_visa_application_id
             INNER JOIN ct2_visa_checklist_items AS ci
                ON ci.ct2_visa_checklist_item_id = ac.ct2_visa_checklist_item_id
             WHERE va.application_reference = :application_reference
               AND ci.item_name = :item_name
             LIMIT 1',
            $ct2Field
        )
    );
    $ct2Statement->execute(
        [
            'application_reference' => $ct2ApplicationReference,
            'item_name' => $ct2ItemName,
        ]
    );
    $ct2Value = $ct2Statement->fetchColumn();

    if ($ct2Value === false) {
        throw new RuntimeException('Unable to resolve checklist item ' . $ct2ItemName . ' for ' . $ct2ApplicationReference);
    }

    return (string) $ct2Value;
}

function ct2ProbeLatestDocumentField(PDO $ct2Pdo, string $ct2ApplicationReference, string $ct2Field): string
{
    $ct2AllowedFields = ['file_path', 'file_name'];
    if (!in_array($ct2Field, $ct2AllowedFields, true)) {
        throw new InvalidArgumentException('Unsupported document field: ' . $ct2Field);
    }

    if ($ct2ApplicationReference === '') {
        throw new InvalidArgumentException('latest-document lookup requires an application reference.');
    }

    $ct2Statement = $ct2Pdo->prepare(
        sprintf(
            'SELECT d.%s
             FROM ct2_documents AS d
             INNER JOIN ct2_visa_applications AS va
                ON va.ct2_visa_application_id = d.entity_id
               AND d.entity_type = "visa_application"
             WHERE va.application_reference = :application_reference
             ORDER BY d.ct2_document_id DESC
             LIMIT 1',
            $ct2Field
        )
    );
    $ct2Statement->execute(['application_reference' => $ct2ApplicationReference]);
    $ct2Value = $ct2Statement->fetchColumn();

    if ($ct2Value === false) {
        throw new RuntimeException('Unable to resolve the latest CT2 document for ' . $ct2ApplicationReference);
    }

    return (string) $ct2Value;
}

function ct2ProbeSupplierOnboardingField(PDO $ct2Pdo, string $ct2SupplierCode, string $ct2Field): string
{
    $ct2AllowedFields = [
        'checklist_status',
        'documents_status',
        'compliance_status',
        'review_notes',
        'blocked_reason',
        'target_go_live_date',
    ];
    if (!in_array($ct2Field, $ct2AllowedFields, true)) {
        throw new InvalidArgumentException('Unsupported supplier onboarding field: ' . $ct2Field);
    }

    if ($ct2SupplierCode === '') {
        throw new InvalidArgumentException('supplier-onboarding-field requires a supplier code.');
    }

    $ct2Statement = $ct2Pdo->prepare(
        sprintf(
            'SELECT so.%s
             FROM ct2_supplier_onboarding AS so
             INNER JOIN ct2_suppliers AS s ON s.ct2_supplier_id = so.ct2_supplier_id
             WHERE s.supplier_code = :supplier_code
             LIMIT 1',
            $ct2Field
        )
    );
    $ct2Statement->execute(['supplier_code' => $ct2SupplierCode]);
    $ct2Value = $ct2Statement->fetchColumn();

    if ($ct2Value === false) {
        throw new RuntimeException('Unable to resolve supplier onboarding row for ' . $ct2SupplierCode);
    }

    return (string) $ct2Value;
}

function ct2ProbeFlagField(PDO $ct2Pdo, string $ct2SourceModule, string $ct2ReferenceCode, string $ct2Field): string
{
    $ct2AllowedFields = ['ct2_reconciliation_flag_id', 'flag_status', 'resolution_notes'];
    if (!in_array($ct2Field, $ct2AllowedFields, true)) {
        throw new InvalidArgumentException('Unsupported reconciliation flag field: ' . $ct2Field);
    }

    if ($ct2SourceModule === '' || $ct2ReferenceCode === '') {
        throw new InvalidArgumentException('flag lookups require source module and reference code.');
    }

    [$ct2JoinSql, $ct2ReferenceColumn] = ct2ProbeFlagReferenceTarget($ct2SourceModule);
    $ct2Statement = $ct2Pdo->prepare(
        sprintf(
            'SELECT rf.%s
             FROM ct2_reconciliation_flags AS rf
             %s
             WHERE rf.source_module = :source_module
               AND %s = :reference_code
             ORDER BY rf.ct2_reconciliation_flag_id DESC
             LIMIT 1',
            $ct2Field,
            $ct2JoinSql,
            $ct2ReferenceColumn
        )
    );
    $ct2Statement->execute(
        [
            'source_module' => $ct2SourceModule,
            'reference_code' => $ct2ReferenceCode,
        ]
    );
    $ct2Value = $ct2Statement->fetchColumn();

    if ($ct2Value === false) {
        throw new RuntimeException('Unable to resolve reconciliation flag for ' . $ct2SourceModule . ' / ' . $ct2ReferenceCode);
    }

    return (string) $ct2Value;
}

function ct2EnsureValidationUser(PDO $ct2Pdo, string $ct2Username, string $ct2Password, int $ct2IsActive): int
{
    if ($ct2Username === '' || $ct2Password === '') {
        throw new InvalidArgumentException('ensure-user requires username and password.');
    }

    $ct2Statement = $ct2Pdo->prepare(
        'INSERT INTO ct2_users (
            username, email, password_hash, display_name, is_active, last_login_at
         ) VALUES (
            :username, :email, :password_hash, :display_name, :is_active, NULL
         )
         ON DUPLICATE KEY UPDATE
            email = VALUES(email),
            password_hash = VALUES(password_hash),
            display_name = VALUES(display_name),
            is_active = VALUES(is_active),
            last_login_at = NULL'
    );
    $ct2Statement->execute(
        [
            'username' => $ct2Username,
            'email' => $ct2Username . '@example.com',
            'password_hash' => password_hash($ct2Password, PASSWORD_DEFAULT),
            'display_name' => 'CT2 Validation User ' . $ct2Username,
            'is_active' => $ct2IsActive === 1 ? 1 : 0,
        ]
    );

    return (int) ct2ProbeUserField($ct2Pdo, $ct2Username, 'ct2_user_id');
}

function ct2ProbeUserField(PDO $ct2Pdo, string $ct2Username, string $ct2Field): string
{
    $ct2AllowedFields = ['ct2_user_id', 'last_login_at', 'is_active'];
    if ($ct2Username === '') {
        throw new InvalidArgumentException('user-field requires a username.');
    }

    if (!in_array($ct2Field, $ct2AllowedFields, true)) {
        throw new InvalidArgumentException('Unsupported user field: ' . $ct2Field);
    }

    $ct2Statement = $ct2Pdo->prepare(
        sprintf(
            'SELECT %s FROM ct2_users WHERE username = :username LIMIT 1',
            $ct2Field
        )
    );
    $ct2Statement->execute(['username' => $ct2Username]);
    $ct2Value = $ct2Statement->fetchColumn();

    if ($ct2Value === false) {
        throw new RuntimeException('Unable to resolve user: ' . $ct2Username);
    }

    return $ct2Value === null ? 'null' : (string) $ct2Value;
}

function ct2ProbeSessionLogCountByUser(PDO $ct2Pdo, string $ct2Username): int
{
    if ($ct2Username === '') {
        throw new InvalidArgumentException('session-log-count-by-user requires a username.');
    }

    $ct2Statement = $ct2Pdo->prepare(
        'SELECT COUNT(*)
         FROM ct2_session_logs AS sl
         INNER JOIN ct2_users AS u ON u.ct2_user_id = sl.ct2_user_id
         WHERE u.username = :username'
    );
    $ct2Statement->execute(['username' => $ct2Username]);
    $ct2Value = $ct2Statement->fetchColumn();

    if ($ct2Value === false) {
        throw new RuntimeException('Unable to count session logs for user: ' . $ct2Username);
    }

    return (int) $ct2Value;
}

function ct2ProbeFlagReferenceTarget(string $ct2SourceModule): array
{
    return match ($ct2SourceModule) {
        'agents' => [
            'INNER JOIN ct2_agents AS ref ON ref.ct2_agent_id = rf.source_record_id',
            'ref.agent_code',
        ],
        'suppliers' => [
            'INNER JOIN ct2_suppliers AS ref ON ref.ct2_supplier_id = rf.source_record_id',
            'ref.supplier_code',
        ],
        'marketing' => [
            'INNER JOIN ct2_campaigns AS ref ON ref.ct2_campaign_id = rf.source_record_id',
            'ref.campaign_code',
        ],
        'visa' => [
            'INNER JOIN ct2_visa_applications AS ref ON ref.ct2_visa_application_id = rf.source_record_id',
            'ref.application_reference',
        ],
        default => throw new InvalidArgumentException('Unsupported reconciliation flag source module: ' . $ct2SourceModule),
    };
}
