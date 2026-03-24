<?php

declare(strict_types=1);

require_once __DIR__ . '/ct2_validation_common.php';

ct2AssertCli();

$ct2Prefix = 'ct2-api-post';
$ct2TempDir = ct2CreateTempDir($ct2Prefix);
$ct2Server = null;
$ct2RunId = (string) time();

try {
    $ct2Port = ct2SelectPort(8094);
    $ct2ApiBaseUrl = 'http://127.0.0.1:' . $ct2Port . '/api';
    $ct2Today = date('Y-m-d');
    $ct2Tomorrow = date('Y-m-d', strtotime('+1 day'));
    $ct2NextWeek = date('Y-m-d', strtotime('+7 day'));
    $ct2NextMonth = date('Y-m-d', strtotime('+30 day'));
    $ct2NowLocal = date('Y-m-d\TH:i');

    $ct2AgentCode = 'AGT-API-' . $ct2RunId;
    $ct2StaffCode = 'STF-API-' . $ct2RunId;
    $ct2SupplierCode = 'SUP-API-' . $ct2RunId;
    $ct2ResourceName = 'CT2 API Resource ' . $ct2RunId;
    $ct2AllocationBookingId = 'CT2-API-BKG-' . $ct2RunId;
    $ct2ContractCode = 'CTR-API-' . $ct2RunId;
    $ct2KpiNote = 'CT2 API KPI ' . $ct2RunId;
    $ct2CampaignCode = 'CT2-API-MKT-' . $ct2RunId;
    $ct2PromotionCode = 'PROMO-API-' . $ct2RunId;
    $ct2VoucherCode = 'VOUCHAPI' . $ct2RunId;
    $ct2AffiliateCode = 'AFF-API-' . $ct2RunId;
    $ct2AffiliateReferral = 'AFFREF-' . $ct2RunId;
    $ct2ApplicationReference = 'VISA-API-' . $ct2RunId;
    $ct2PaymentReference = 'PAY-API-' . $ct2RunId;
    $ct2DocumentName = 'api_doc_' . $ct2RunId . '.pdf';
    $ct2DocumentPath = 'storage/uploads/api/visa_' . $ct2RunId . '.pdf';
    $ct2ReportCode = 'FIN-API-' . $ct2RunId;
    $ct2ReportName = 'CT2 API Report ' . $ct2RunId;
    $ct2FlagNote = 'CT2 API flag note ' . $ct2RunId;

    ct2Log($ct2Prefix, 'Starting local CT2 PHP server.');
    $ct2Server = ct2StartPhpServer($ct2Port, $ct2TempDir, '/api/ct2_module_status.php');

    $ct2AdminSession = ct2CreateHttpSession($ct2TempDir);
    $ct2DeskSession = ct2CreateHttpSession($ct2TempDir);
    $ct2FinanceSession = ct2CreateHttpSession($ct2TempDir);
    $ct2InactiveSession = ct2CreateHttpSession($ct2TempDir);

    $ct2PostJson = static function (?array $session, string $path, array $payload) use ($ct2ApiBaseUrl): array {
        return ct2HttpRequest('POST', $ct2ApiBaseUrl . '/' . $path, $session, [], [], json_encode($payload, JSON_UNESCAPED_SLASHES));
    };
    $ct2GetJson = static function (?array $session, string $pathWithQuery) use ($ct2ApiBaseUrl): array {
        return ct2HttpRequest('GET', $ct2ApiBaseUrl . '/' . $pathWithQuery, $session);
    };
    $ct2AssertJson = static function (int $status, array $response, bool $success, string $message) use ($ct2Prefix): array {
        ct2AssertStatus($status, $response, $message, $ct2Prefix);
        return ct2AssertJsonEnvelope($response, $success, $message, $ct2Prefix);
    };
    $ct2LoginApi = static function (array $session, string $username, string $password, int $expectedStatus, bool $expectedSuccess) use ($ct2PostJson, $ct2AssertJson): array {
        $ct2Response = $ct2PostJson($session, 'ct2_auth_login.php', [
            'username' => $username,
            'password' => $password,
        ]);

        return $ct2AssertJson($expectedStatus, $ct2Response, $expectedSuccess, 'API login response was invalid');
    };

    ct2Log($ct2Prefix, 'Validating API authentication.');
    $ct2ModuleStatus = $ct2GetJson($ct2AdminSession, 'ct2_module_status.php');
    $ct2AssertJson(200, $ct2ModuleStatus, true, 'API module-status warmup did not return JSON 200');
    $ct2AdminSessionBeforeLogin = ct2SessionCookieValue($ct2AdminSession);
    $ct2Auth401Before = ct2ApiLogCount('ct2_auth_login', 401);
    $ct2InvalidLogin = $ct2LoginApi($ct2AdminSession, 'ct2admin', 'WrongPassword!', 401, false);
    ct2AssertContains($ct2InvalidLogin['error'] ?? '', 'Invalid credentials.', 'API login invalid-credential error was not returned', $ct2Prefix);
    ct2AssertEquals((string) ($ct2Auth401Before + 1), (string) ct2ApiLogCount('ct2_auth_login', 401), 'API login 401 log count did not increment', $ct2Prefix);

    $ct2Auth200Before = ct2ApiLogCount('ct2_auth_login', 200);
    $ct2AdminLogin = $ct2LoginApi($ct2AdminSession, 'ct2admin', 'ChangeMe123!', 200, true);
    ct2AssertEquals('ct2admin', ct2JsonValue($ct2AdminLogin, 'data.user.username'), 'API login success did not return the admin user', $ct2Prefix);
    $ct2AdminSessionAfterLogin = ct2SessionCookieValue($ct2AdminSession);
    ct2AssertNotEquals($ct2AdminSessionBeforeLogin, $ct2AdminSessionAfterLogin, 'API login did not rotate the CT2 session identifier', $ct2Prefix);
    ct2AssertEquals((string) ($ct2Auth200Before + 1), (string) ct2ApiLogCount('ct2_auth_login', 200), 'API login 200 log count did not increment', $ct2Prefix);
    $ct2LoginApi($ct2DeskSession, 'ct2desk', 'ChangeMe123!', 200, true);
    $ct2LoginApi($ct2FinanceSession, 'ct2finance', 'ChangeMe123!', 200, true);

    $ct2InactiveUsername = 'ct2inactive' . substr($ct2RunId, -6);
    ct2Probe('ensure-user', $ct2InactiveUsername, 'ChangeMe123!', '0');
    $ct2InactiveLastLoginBefore = ct2Probe('user-field', $ct2InactiveUsername, 'last_login_at');
    $ct2InactiveSessionLogsBefore = ct2Probe('session-log-count-by-user', $ct2InactiveUsername);
    $ct2InactiveLogin = $ct2LoginApi($ct2InactiveSession, $ct2InactiveUsername, 'ChangeMe123!', 401, false);
    ct2AssertContains($ct2InactiveLogin['error'] ?? '', 'Invalid credentials.', 'Inactive-user API login did not return the generic auth failure message', $ct2Prefix);
    ct2AssertEquals($ct2InactiveLastLoginBefore, ct2Probe('user-field', $ct2InactiveUsername, 'last_login_at'), 'Inactive-user API login changed last_login_at', $ct2Prefix);
    ct2AssertEquals($ct2InactiveSessionLogsBefore, ct2Probe('session-log-count-by-user', $ct2InactiveUsername), 'Inactive-user API login created a session log entry', $ct2Prefix);

    ct2Log($ct2Prefix, 'Checking anonymous API denial.');
    $ct2Anon403Before = ct2ApiLogCount('ct2_agents', 403);
    $ct2AnonAgent = $ct2PostJson(null, 'ct2_agents.php', ['agent_code' => 'ANON']);
    $ct2AssertJson(403, $ct2AnonAgent, false, 'Anonymous agent POST did not return JSON 403');
    ct2AssertEquals((string) ($ct2Anon403Before + 1), (string) ct2ApiLogCount('ct2_agents', 403), 'Anonymous agent POST log count did not increment', $ct2Prefix);

    ct2Log($ct2Prefix, 'Checking API permission parity for seeded non-admin roles.');
    $ct2AgentGet403Before = ct2ApiLogCount('ct2_agents', 403, 'GET');
    $ct2DeskAgentGet = $ct2GetJson($ct2DeskSession, 'ct2_agents.php');
    $ct2AssertJson(403, $ct2DeskAgentGet, false, 'Desk agent GET did not return JSON 403', $ct2Prefix);
    ct2AssertEquals((string) ($ct2AgentGet403Before + 1), (string) ct2ApiLogCount('ct2_agents', 403, 'GET'), 'Desk agent GET 403 log count did not increment', $ct2Prefix);

    $ct2AgentPost403Before = ct2ApiLogCount('ct2_agents', 403);
    $ct2DeskAgentPost = $ct2PostJson($ct2DeskSession, 'ct2_agents.php', ['agent_code' => 'DENIED']);
    $ct2AssertJson(403, $ct2DeskAgentPost, false, 'Desk agent POST did not return JSON 403', $ct2Prefix);
    ct2AssertEquals((string) ($ct2AgentPost403Before + 1), (string) ct2ApiLogCount('ct2_agents', 403), 'Desk agent POST 403 log count did not increment', $ct2Prefix);

    $ct2ApprovalsGet = $ct2GetJson($ct2FinanceSession, 'ct2_approvals.php');
    $ct2AssertJson(200, $ct2ApprovalsGet, true, 'Finance approvals GET did not return JSON 200', $ct2Prefix);

    $ct2Approvals403Before = ct2ApiLogCount('ct2_approvals', 403);
    $ct2FinanceApprovalPost = $ct2PostJson($ct2FinanceSession, 'ct2_approvals.php', ['ct2_approval_workflow_id' => 1, 'approval_status' => 'approved']);
    $ct2AssertJson(403, $ct2FinanceApprovalPost, false, 'Finance approvals POST did not return JSON 403', $ct2Prefix);
    ct2AssertEquals((string) ($ct2Approvals403Before + 1), (string) ct2ApiLogCount('ct2_approvals', 403), 'Finance approvals POST 403 log count did not increment', $ct2Prefix);

    $ct2SuppliersGet = $ct2GetJson($ct2FinanceSession, 'ct2_suppliers.php');
    $ct2AssertJson(200, $ct2SuppliersGet, true, 'Finance suppliers GET did not return JSON 200', $ct2Prefix);

    $ct2Suppliers403Before = ct2ApiLogCount('ct2_suppliers', 403);
    $ct2FinanceSupplierPost = $ct2PostJson($ct2FinanceSession, 'ct2_suppliers.php', ['supplier_code' => 'DENIED']);
    $ct2AssertJson(403, $ct2FinanceSupplierPost, false, 'Finance suppliers POST did not return JSON 403', $ct2Prefix);
    ct2AssertEquals((string) ($ct2Suppliers403Before + 1), (string) ct2ApiLogCount('ct2_suppliers', 403), 'Finance suppliers POST 403 log count did not increment', $ct2Prefix);

    $ct2MarketingGet = $ct2GetJson($ct2FinanceSession, 'ct2_marketing_campaigns.php');
    $ct2AssertJson(200, $ct2MarketingGet, true, 'Finance marketing GET did not return JSON 200', $ct2Prefix);

    $ct2Marketing403Before = ct2ApiLogCount('ct2_marketing_campaigns', 403);
    $ct2FinanceMarketingPost = $ct2PostJson($ct2FinanceSession, 'ct2_marketing_campaigns.php', ['campaign_code' => 'DENIED']);
    $ct2AssertJson(403, $ct2FinanceMarketingPost, false, 'Finance marketing POST did not return JSON 403', $ct2Prefix);
    ct2AssertEquals((string) ($ct2Marketing403Before + 1), (string) ct2ApiLogCount('ct2_marketing_campaigns', 403), 'Finance marketing POST 403 log count did not increment', $ct2Prefix);

    $ct2VisaGet = $ct2GetJson($ct2FinanceSession, 'ct2_visa_applications.php');
    $ct2AssertJson(200, $ct2VisaGet, true, 'Finance visa GET did not return JSON 200', $ct2Prefix);

    $ct2Visa403Before = ct2ApiLogCount('ct2_visa_status', 403);
    $ct2FinanceVisaPost = $ct2PostJson($ct2FinanceSession, 'ct2_visa_status.php', ['ct2_visa_application_id' => 1, 'status' => 'approved']);
    $ct2AssertJson(403, $ct2FinanceVisaPost, false, 'Finance visa status POST did not return JSON 403', $ct2Prefix);
    ct2AssertEquals((string) ($ct2Visa403Before + 1), (string) ct2ApiLogCount('ct2_visa_status', 403), 'Finance visa status POST 403 log count did not increment', $ct2Prefix);

    $ct2AvailabilityGet403Before = ct2ApiLogCount('ct2_resources', 403, 'GET');
    $ct2FinanceResourcesGet = $ct2GetJson($ct2FinanceSession, 'ct2_resources.php');
    $ct2AssertJson(403, $ct2FinanceResourcesGet, false, 'Finance resources GET did not return JSON 403', $ct2Prefix);
    ct2AssertEquals((string) ($ct2AvailabilityGet403Before + 1), (string) ct2ApiLogCount('ct2_resources', 403, 'GET'), 'Finance resources GET 403 log count did not increment', $ct2Prefix);

    ct2Log($ct2Prefix, 'Creating agent through API.');
    $ct2Agents422Before = ct2ApiLogCount('ct2_agents', 422);
    $ct2InvalidAgent = $ct2PostJson($ct2AdminSession, 'ct2_agents.php', ['agent_code' => $ct2AgentCode]);
    $ct2InvalidAgentData = $ct2AssertJson(422, $ct2InvalidAgent, false, 'Agent API invalid payload did not return JSON 422');
    ct2AssertContains($ct2InvalidAgentData['error'] ?? '', 'Missing field: agency_name', 'Agent API invalid payload error was not correct', $ct2Prefix);
    ct2AssertEquals((string) ($ct2Agents422Before + 1), (string) ct2ApiLogCount('ct2_agents', 422), 'Agent API 422 log count did not increment', $ct2Prefix);

    $ct2Agents200Before = ct2ApiLogCount('ct2_agents', 200);
    $ct2AgentCreate = $ct2PostJson($ct2AdminSession, 'ct2_agents.php', [
        'agent_code' => $ct2AgentCode,
        'agency_name' => 'CT2 API Agency ' . $ct2RunId,
        'contact_person' => 'API Agent Contact',
        'email' => 'agent-api-' . $ct2RunId . '@example.com',
        'phone' => '+63-917-510-' . str_pad((string) ((int) $ct2RunId % 10000), 4, '0', STR_PAD_LEFT),
        'region' => 'API Region',
        'commission_rate' => '9.50',
        'support_level' => 'priority',
        'approval_status' => 'pending',
        'active_status' => 'active',
        'external_booking_id' => 'API-BKG-' . $ct2RunId,
        'external_customer_id' => 'API-CUST-' . $ct2RunId,
        'external_payment_id' => 'API-PAY-' . $ct2RunId,
        'source_system' => 'ct1',
    ]);
    $ct2AgentData = $ct2AssertJson(200, $ct2AgentCreate, true, 'Agent API create did not return JSON 200');
    $ct2AgentId = ct2JsonValue($ct2AgentData, 'data.ct2_agent_id');
    ct2AssertEquals((string) ($ct2Agents200Before + 1), (string) ct2ApiLogCount('ct2_agents', 200), 'Agent API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'agents.api_create', 'agent', $ct2AgentId), 'Agent API create audit row was not recorded', $ct2Prefix);
    $ct2AgentSearch = $ct2GetJson($ct2AdminSession, 'ct2_agents.php?search=' . rawurlencode($ct2AgentCode));
    $ct2AssertJson(200, $ct2AgentSearch, true, 'Agent API search did not return JSON 200');
    ct2AssertContains($ct2AgentSearch['body'], '"agent_code":"' . $ct2AgentCode . '"', 'Agent API search did not return the created agent', $ct2Prefix);

    ct2Log($ct2Prefix, 'Deciding approval through API.');
    $ct2ApprovalId = ct2Probe('approval-id', 'agent', $ct2AgentCode);
    $ct2Approvals422Before = ct2ApiLogCount('ct2_approvals', 422);
    $ct2InvalidApproval = $ct2PostJson($ct2AdminSession, 'ct2_approvals.php', ['approval_status' => 'approved']);
    $ct2AssertJson(422, $ct2InvalidApproval, false, 'Approvals API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Approvals422Before + 1), (string) ct2ApiLogCount('ct2_approvals', 422), 'Approvals API 422 log count did not increment', $ct2Prefix);

    $ct2Approvals200Before = ct2ApiLogCount('ct2_approvals', 200);
    $ct2ApprovalCreate = $ct2PostJson($ct2AdminSession, 'ct2_approvals.php', [
        'ct2_approval_workflow_id' => (int) $ct2ApprovalId,
        'approval_status' => 'approved',
        'decision_notes' => 'API approval ' . $ct2RunId,
    ]);
    $ct2AssertJson(200, $ct2ApprovalCreate, true, 'Approvals API success did not return JSON 200');
    ct2AssertEquals((string) ($ct2Approvals200Before + 1), (string) ct2ApiLogCount('ct2_approvals', 200), 'Approvals API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('approved', ct2Probe('approval-status', 'agent', $ct2AgentCode), 'Approvals API did not persist the approved status', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'approvals.api_decide', 'agent', $ct2AgentId), 'Approvals API audit row was not recorded', $ct2Prefix);

    ct2Log($ct2Prefix, 'Creating staff through API.');
    $ct2Staff422Before = ct2ApiLogCount('ct2_staff', 422);
    $ct2InvalidStaff = $ct2PostJson($ct2AdminSession, 'ct2_staff.php', ['staff_code' => $ct2StaffCode]);
    $ct2AssertJson(422, $ct2InvalidStaff, false, 'Staff API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Staff422Before + 1), (string) ct2ApiLogCount('ct2_staff', 422), 'Staff API 422 log count did not increment', $ct2Prefix);

    $ct2Staff200Before = ct2ApiLogCount('ct2_staff', 200);
    $ct2StaffCreate = $ct2PostJson($ct2AdminSession, 'ct2_staff.php', [
        'staff_code' => $ct2StaffCode,
        'full_name' => 'CT2 API Staff ' . $ct2RunId,
        'email' => 'staff-api-' . $ct2RunId . '@example.com',
        'phone' => '+63-917-520-' . str_pad((string) ((int) $ct2RunId % 10000), 4, '0', STR_PAD_LEFT),
        'department' => 'API Ops',
        'position_title' => 'API Coordinator',
        'team_name' => 'API Team',
        'employment_status' => 'active',
        'availability_status' => 'available',
        'notes' => 'API staff record ' . $ct2RunId,
    ]);
    $ct2StaffData = $ct2AssertJson(200, $ct2StaffCreate, true, 'Staff API create did not return JSON 200');
    $ct2StaffId = ct2JsonValue($ct2StaffData, 'data.ct2_staff_id');
    ct2AssertEquals((string) ($ct2Staff200Before + 1), (string) ct2ApiLogCount('ct2_staff', 200), 'Staff API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'staff.api_create', 'staff', $ct2StaffId), 'Staff API create audit row was not recorded', $ct2Prefix);
    $ct2StaffSearch = $ct2GetJson($ct2AdminSession, 'ct2_staff.php?search=' . rawurlencode($ct2StaffCode));
    $ct2AssertJson(200, $ct2StaffSearch, true, 'Staff API search did not return JSON 200');
    ct2AssertContains($ct2StaffSearch['body'], '"staff_code":"' . $ct2StaffCode . '"', 'Staff API search did not return the created staff record', $ct2Prefix);

    ct2Log($ct2Prefix, 'Creating supplier and supplier auxiliary records through API.');
    $ct2Suppliers422Before = ct2ApiLogCount('ct2_suppliers', 422);
    $ct2InvalidSupplier = $ct2PostJson($ct2AdminSession, 'ct2_suppliers.php', ['supplier_code' => $ct2SupplierCode]);
    $ct2AssertJson(422, $ct2InvalidSupplier, false, 'Suppliers API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Suppliers422Before + 1), (string) ct2ApiLogCount('ct2_suppliers', 422), 'Suppliers API 422 log count did not increment', $ct2Prefix);

    $ct2Suppliers200Before = ct2ApiLogCount('ct2_suppliers', 200);
    $ct2SupplierCreate = $ct2PostJson($ct2AdminSession, 'ct2_suppliers.php', [
        'supplier_code' => $ct2SupplierCode,
        'supplier_name' => 'CT2 API Supplier ' . $ct2RunId,
        'primary_contact_name' => 'API Supplier Contact',
        'email' => 'supplier-api-' . $ct2RunId . '@example.com',
        'phone' => '+63-917-530-' . str_pad((string) ((int) $ct2RunId % 10000), 4, '0', STR_PAD_LEFT),
        'service_category' => 'Transport',
        'support_tier' => 'priority',
        'approval_status' => 'pending',
        'onboarding_status' => 'draft',
        'active_status' => 'active',
        'risk_level' => 'low',
        'source_system' => 'financials',
    ]);
    $ct2SupplierData = $ct2AssertJson(200, $ct2SupplierCreate, true, 'Suppliers API create did not return JSON 200');
    $ct2SupplierId = ct2JsonValue($ct2SupplierData, 'data.ct2_supplier_id');
    ct2AssertEquals((string) ($ct2Suppliers200Before + 1), (string) ct2ApiLogCount('ct2_suppliers', 200), 'Suppliers API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'suppliers.api_create', 'supplier', $ct2SupplierId), 'Suppliers API create audit row was not recorded', $ct2Prefix);
    $ct2SupplierSearch = $ct2GetJson($ct2AdminSession, 'ct2_suppliers.php?search=' . rawurlencode($ct2SupplierCode));
    $ct2AssertJson(200, $ct2SupplierSearch, true, 'Suppliers API search did not return JSON 200');
    ct2AssertContains($ct2SupplierSearch['body'], '"supplier_code":"' . $ct2SupplierCode . '"', 'Suppliers API search did not return the created supplier', $ct2Prefix);

    $ct2Onboarding422Before = ct2ApiLogCount('ct2_supplier_onboarding', 422);
    $ct2InvalidOnboarding = $ct2PostJson($ct2AdminSession, 'ct2_supplier_onboarding.php', []);
    $ct2AssertJson(422, $ct2InvalidOnboarding, false, 'Supplier onboarding API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Onboarding422Before + 1), (string) ct2ApiLogCount('ct2_supplier_onboarding', 422), 'Supplier onboarding API 422 log count did not increment', $ct2Prefix);

    $ct2Onboarding200Before = ct2ApiLogCount('ct2_supplier_onboarding', 200);
    $ct2OnboardingSave = $ct2PostJson($ct2AdminSession, 'ct2_supplier_onboarding.php', [
        'ct2_supplier_id' => (int) $ct2SupplierId,
        'checklist_status' => 'review_ready',
        'documents_status' => 'complete',
        'compliance_status' => 'cleared',
        'review_notes' => 'API onboarding ' . $ct2RunId,
        'target_go_live_date' => $ct2NextWeek,
    ]);
    $ct2AssertJson(200, $ct2OnboardingSave, true, 'Supplier onboarding API update did not return JSON 200');
    ct2AssertEquals((string) ($ct2Onboarding200Before + 1), (string) ct2ApiLogCount('ct2_supplier_onboarding', 200), 'Supplier onboarding API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'suppliers.api_onboarding_update', 'supplier', $ct2SupplierId), 'Supplier onboarding API audit row was not recorded', $ct2Prefix);
    ct2AssertEquals('API onboarding ' . $ct2RunId, ct2Probe('supplier-onboarding-field', $ct2SupplierCode, 'review_notes'), 'Supplier onboarding API did not persist review notes', $ct2Prefix);

    $ct2Contracts422Before = ct2ApiLogCount('ct2_supplier_contracts', 422);
    $ct2InvalidContract = $ct2PostJson($ct2AdminSession, 'ct2_supplier_contracts.php', ['ct2_supplier_id' => (int) $ct2SupplierId]);
    $ct2AssertJson(422, $ct2InvalidContract, false, 'Supplier contracts API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Contracts422Before + 1), (string) ct2ApiLogCount('ct2_supplier_contracts', 422), 'Supplier contracts API 422 log count did not increment', $ct2Prefix);

    $ct2Contracts200Before = ct2ApiLogCount('ct2_supplier_contracts', 200);
    $ct2ContractCreate = $ct2PostJson($ct2AdminSession, 'ct2_supplier_contracts.php', [
        'ct2_supplier_id' => (int) $ct2SupplierId,
        'contract_code' => $ct2ContractCode,
        'contract_title' => 'CT2 API Contract ' . $ct2RunId,
        'effective_date' => $ct2Today,
        'expiry_date' => $ct2NextMonth,
        'renewal_status' => 'not_started',
        'contract_status' => 'active',
        'clause_summary' => 'API supplier contract ' . $ct2RunId,
        'mock_signature_status' => 'signed',
        'finance_handoff_status' => 'confirmed',
    ]);
    $ct2ContractData = $ct2AssertJson(200, $ct2ContractCreate, true, 'Supplier contracts API create did not return JSON 200');
    $ct2ContractId = ct2JsonValue($ct2ContractData, 'data.ct2_supplier_contract_id');
    ct2AssertEquals((string) ($ct2Contracts200Before + 1), (string) ct2ApiLogCount('ct2_supplier_contracts', 200), 'Supplier contracts API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'suppliers.api_contract_create', 'supplier_contract', $ct2ContractId), 'Supplier contract API audit row was not recorded', $ct2Prefix);
    $ct2ContractsGet = $ct2GetJson($ct2AdminSession, 'ct2_supplier_contracts.php');
    $ct2AssertJson(200, $ct2ContractsGet, true, 'Supplier contracts API GET did not return JSON 200');
    ct2AssertContains($ct2ContractsGet['body'], '"contract_code":"' . $ct2ContractCode . '"', 'Supplier contracts API GET did not return the created contract', $ct2Prefix);

    $ct2Kpis422Before = ct2ApiLogCount('ct2_supplier_kpis', 422);
    $ct2InvalidKpi = $ct2PostJson($ct2AdminSession, 'ct2_supplier_kpis.php', ['ct2_supplier_id' => (int) $ct2SupplierId]);
    $ct2AssertJson(422, $ct2InvalidKpi, false, 'Supplier KPI API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Kpis422Before + 1), (string) ct2ApiLogCount('ct2_supplier_kpis', 422), 'Supplier KPI API 422 log count did not increment', $ct2Prefix);

    $ct2Kpis200Before = ct2ApiLogCount('ct2_supplier_kpis', 200);
    $ct2KpiCreate = $ct2PostJson($ct2AdminSession, 'ct2_supplier_kpis.php', [
        'ct2_supplier_id' => (int) $ct2SupplierId,
        'measurement_date' => $ct2Today,
        'service_score' => 91,
        'delivery_score' => 89,
        'compliance_score' => 94,
        'responsiveness_score' => 90,
        'risk_flag' => 'watch',
        'notes' => $ct2KpiNote,
    ]);
    $ct2KpiData = $ct2AssertJson(200, $ct2KpiCreate, true, 'Supplier KPI API create did not return JSON 200');
    $ct2KpiId = ct2JsonValue($ct2KpiData, 'data.ct2_supplier_kpi_id');
    ct2AssertEquals((string) ($ct2Kpis200Before + 1), (string) ct2ApiLogCount('ct2_supplier_kpis', 200), 'Supplier KPI API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'suppliers.api_kpi_create', 'supplier_kpi', $ct2KpiId), 'Supplier KPI API audit row was not recorded', $ct2Prefix);
    $ct2KpiGet = $ct2GetJson($ct2AdminSession, 'ct2_supplier_kpis.php');
    $ct2AssertJson(200, $ct2KpiGet, true, 'Supplier KPI API GET did not return JSON 200');
    ct2AssertContains($ct2KpiGet['body'], '"notes":"' . $ct2KpiNote . '"', 'Supplier KPI API GET did not return the created KPI', $ct2Prefix);

    ct2Log($ct2Prefix, 'Creating availability records through API.');
    $ct2Resources422Before = ct2ApiLogCount('ct2_resources', 422);
    $ct2InvalidResource = $ct2PostJson($ct2AdminSession, 'ct2_resources.php', ['resource_name' => $ct2ResourceName]);
    $ct2AssertJson(422, $ct2InvalidResource, false, 'Resources API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Resources422Before + 1), (string) ct2ApiLogCount('ct2_resources', 422), 'Resources API 422 log count did not increment', $ct2Prefix);

    $ct2Resources200Before = ct2ApiLogCount('ct2_resources', 200);
    $ct2ResourceCreate = $ct2PostJson($ct2AdminSession, 'ct2_resources.php', [
        'ct2_supplier_id' => (int) $ct2SupplierId,
        'resource_name' => $ct2ResourceName,
        'resource_type' => 'transport',
        'capacity' => 12,
        'base_cost' => '8500.00',
        'status' => 'available',
        'notes' => 'API resource ' . $ct2RunId,
    ]);
    $ct2ResourceData = $ct2AssertJson(200, $ct2ResourceCreate, true, 'Resources API create did not return JSON 200');
    $ct2ResourceId = ct2JsonValue($ct2ResourceData, 'data.ct2_resource_id');
    ct2AssertEquals((string) ($ct2Resources200Before + 1), (string) ct2ApiLogCount('ct2_resources', 200), 'Resources API 200 log count did not increment', $ct2Prefix);
    $ct2ResourceGet = $ct2GetJson($ct2AdminSession, 'ct2_resources.php?search=' . rawurlencode($ct2ResourceName));
    $ct2AssertJson(200, $ct2ResourceGet, true, 'Resources API GET did not return JSON 200');
    ct2AssertContains($ct2ResourceGet['body'], '"resource_name":"' . $ct2ResourceName . '"', 'Resources API GET did not return the created resource', $ct2Prefix);

    $ct2Alloc422Before = ct2ApiLogCount('ct2_tour_availability', 422);
    $ct2InvalidAllocation = $ct2PostJson($ct2AdminSession, 'ct2_tour_availability.php', ['ct2_resource_id' => (int) $ct2ResourceId]);
    $ct2AssertJson(422, $ct2InvalidAllocation, false, 'Tour availability API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Alloc422Before + 1), (string) ct2ApiLogCount('ct2_tour_availability', 422), 'Tour availability API 422 log count did not increment', $ct2Prefix);

    $ct2Alloc200Before = ct2ApiLogCount('ct2_tour_availability', 200);
    $ct2AllocationCreate = $ct2PostJson($ct2AdminSession, 'ct2_tour_availability.php', [
        'ct2_resource_id' => (int) $ct2ResourceId,
        'external_booking_id' => $ct2AllocationBookingId,
        'allocation_date' => $ct2Tomorrow,
        'pax_count' => 8,
        'reserved_units' => 1,
        'notes' => 'API allocation ' . $ct2RunId,
    ]);
    $ct2AssertJson(200, $ct2AllocationCreate, true, 'Tour availability API create did not return JSON 200');
    ct2AssertEquals((string) ($ct2Alloc200Before + 1), (string) ct2ApiLogCount('ct2_tour_availability', 200), 'Tour availability API 200 log count did not increment', $ct2Prefix);
    $ct2AllocationGet = $ct2GetJson($ct2AdminSession, 'ct2_tour_availability.php');
    $ct2AssertJson(200, $ct2AllocationGet, true, 'Tour availability API GET did not return JSON 200');
    ct2AssertContains($ct2AllocationGet['body'], '"external_booking_id":"' . $ct2AllocationBookingId . '"', 'Tour availability API GET did not return the created allocation', $ct2Prefix);

    ct2Log($ct2Prefix, 'Creating dispatch order through API.');
    $ct2VehicleId = ct2Probe('vehicle-id', 'NAA-4581');
    $ct2DriverId = ct2Probe('driver-id', 'Aris Navarro');
    $ct2Dispatch422Before = ct2ApiLogCount('ct2_dispatch_orders', 422);
    $ct2InvalidDispatch = $ct2PostJson($ct2AdminSession, 'ct2_dispatch_orders.php', ['ct2_driver_id' => (int) $ct2DriverId]);
    $ct2AssertJson(422, $ct2InvalidDispatch, false, 'Dispatch orders API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Dispatch422Before + 1), (string) ct2ApiLogCount('ct2_dispatch_orders', 422), 'Dispatch orders API 422 log count did not increment', $ct2Prefix);

    $ct2Dispatch200Before = ct2ApiLogCount('ct2_dispatch_orders', 200);
    $ct2DispatchCreate = $ct2PostJson($ct2AdminSession, 'ct2_dispatch_orders.php', [
        'ct2_vehicle_id' => (int) $ct2VehicleId,
        'ct2_driver_id' => (int) $ct2DriverId,
        'dispatch_date' => $ct2Tomorrow,
        'dispatch_time' => $ct2NowLocal,
        'dispatch_status' => 'scheduled',
        'start_mileage' => ((int) $ct2RunId % 100000),
    ]);
    $ct2DispatchData = $ct2AssertJson(200, $ct2DispatchCreate, true, 'Dispatch orders API create did not return JSON 200');
    $ct2DispatchId = ct2JsonValue($ct2DispatchData, 'data.ct2_dispatch_order_id');
    ct2AssertEquals((string) ($ct2Dispatch200Before + 1), (string) ct2ApiLogCount('ct2_dispatch_orders', 200), 'Dispatch orders API 200 log count did not increment', $ct2Prefix);
    $ct2DispatchGet = $ct2GetJson($ct2AdminSession, 'ct2_dispatch_orders.php');
    $ct2AssertJson(200, $ct2DispatchGet, true, 'Dispatch orders API GET did not return JSON 200');
    ct2AssertContains($ct2DispatchGet['body'], '"ct2_dispatch_order_id":' . $ct2DispatchId, 'Dispatch orders API GET did not return the created dispatch order', $ct2Prefix);

    ct2Log($ct2Prefix, 'Creating marketing records through API.');
    $ct2Campaigns422Before = ct2ApiLogCount('ct2_marketing_campaigns', 422);
    $ct2InvalidCampaign = $ct2PostJson($ct2AdminSession, 'ct2_marketing_campaigns.php', ['campaign_name' => 'API Campaign']);
    $ct2AssertJson(422, $ct2InvalidCampaign, false, 'Marketing campaigns API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Campaigns422Before + 1), (string) ct2ApiLogCount('ct2_marketing_campaigns', 422), 'Marketing campaigns API 422 log count did not increment', $ct2Prefix);

    $ct2Campaigns200Before = ct2ApiLogCount('ct2_marketing_campaigns', 200);
    $ct2CampaignCreate = $ct2PostJson($ct2AdminSession, 'ct2_marketing_campaigns.php', [
        'campaign_code' => $ct2CampaignCode,
        'campaign_name' => 'CT2 API Campaign ' . $ct2RunId,
        'campaign_type' => 'seasonal',
        'channel_type' => 'hybrid',
        'start_date' => $ct2Today,
        'end_date' => $ct2NextMonth,
        'budget_amount' => 100000,
        'status' => 'pending_approval',
        'approval_status' => 'pending',
        'target_audience' => 'API travelers',
        'source_system' => 'crm',
    ]);
    $ct2CampaignData = $ct2AssertJson(200, $ct2CampaignCreate, true, 'Marketing campaigns API create did not return JSON 200');
    $ct2CampaignId = ct2JsonValue($ct2CampaignData, 'data.ct2_campaign_id');
    ct2AssertEquals((string) ($ct2Campaigns200Before + 1), (string) ct2ApiLogCount('ct2_marketing_campaigns', 200), 'Marketing campaigns API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'marketing.api_campaign_create', 'campaign', $ct2CampaignId), 'Marketing campaign API audit row was not recorded', $ct2Prefix);
    $ct2CampaignGet = $ct2GetJson($ct2AdminSession, 'ct2_marketing_campaigns.php?search=' . rawurlencode($ct2CampaignCode));
    $ct2AssertJson(200, $ct2CampaignGet, true, 'Marketing campaigns API GET did not return JSON 200');
    ct2AssertContains($ct2CampaignGet['body'], '"campaign_code":"' . $ct2CampaignCode . '"', 'Marketing campaigns API GET did not return the created campaign', $ct2Prefix);

    $ct2Promotions422Before = ct2ApiLogCount('ct2_promotions', 422);
    $ct2InvalidPromotion = $ct2PostJson($ct2AdminSession, 'ct2_promotions.php', ['promotion_code' => $ct2PromotionCode]);
    $ct2AssertJson(422, $ct2InvalidPromotion, false, 'Promotions API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Promotions422Before + 1), (string) ct2ApiLogCount('ct2_promotions', 422), 'Promotions API 422 log count did not increment', $ct2Prefix);

    $ct2Promotions200Before = ct2ApiLogCount('ct2_promotions', 200);
    $ct2PromotionCreate = $ct2PostJson($ct2AdminSession, 'ct2_promotions.php', [
        'ct2_campaign_id' => (int) $ct2CampaignId,
        'promotion_code' => $ct2PromotionCode,
        'promotion_name' => 'CT2 API Promotion ' . $ct2RunId,
        'promotion_type' => 'percentage',
        'discount_value' => 15,
        'valid_from' => $ct2Today,
        'valid_until' => $ct2NextMonth,
        'usage_limit' => 25,
        'promotion_status' => 'pending_approval',
        'approval_status' => 'pending',
        'eligibility_rule' => 'API rule',
        'source_system' => 'ct1',
    ]);
    $ct2PromotionData = $ct2AssertJson(200, $ct2PromotionCreate, true, 'Promotions API create did not return JSON 200');
    $ct2PromotionId = ct2JsonValue($ct2PromotionData, 'data.ct2_promotion_id');
    ct2AssertEquals((string) ($ct2Promotions200Before + 1), (string) ct2ApiLogCount('ct2_promotions', 200), 'Promotions API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'marketing.api_promotion_create', 'promotion', $ct2PromotionId), 'Promotion API audit row was not recorded', $ct2Prefix);
    $ct2PromotionGet = $ct2GetJson($ct2AdminSession, 'ct2_promotions.php?search=' . rawurlencode($ct2PromotionCode));
    $ct2AssertJson(200, $ct2PromotionGet, true, 'Promotions API GET did not return JSON 200');
    ct2AssertContains($ct2PromotionGet['body'], '"promotion_code":"' . $ct2PromotionCode . '"', 'Promotions API GET did not return the created promotion', $ct2Prefix);

    $ct2Vouchers422Before = ct2ApiLogCount('ct2_vouchers', 422);
    $ct2InvalidVoucher = $ct2PostJson($ct2AdminSession, 'ct2_vouchers.php', ['voucher_name' => 'Missing code']);
    $ct2AssertJson(422, $ct2InvalidVoucher, false, 'Vouchers API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Vouchers422Before + 1), (string) ct2ApiLogCount('ct2_vouchers', 422), 'Vouchers API 422 log count did not increment', $ct2Prefix);

    $ct2Vouchers200Before = ct2ApiLogCount('ct2_vouchers', 200);
    $ct2VoucherCreate = $ct2PostJson($ct2AdminSession, 'ct2_vouchers.php', [
        'ct2_promotion_id' => (int) $ct2PromotionId,
        'voucher_code' => $ct2VoucherCode,
        'voucher_name' => 'CT2 API Voucher ' . $ct2RunId,
        'customer_scope' => 'single_use',
        'max_redemptions' => 1,
        'voucher_status' => 'issued',
        'valid_from' => $ct2Today,
        'valid_until' => $ct2NextMonth,
        'external_customer_id' => 'API-CUST-' . $ct2RunId,
        'source_system' => 'ct1',
    ]);
    $ct2VoucherData = $ct2AssertJson(200, $ct2VoucherCreate, true, 'Vouchers API create did not return JSON 200');
    $ct2VoucherId = ct2JsonValue($ct2VoucherData, 'data.ct2_voucher_id');
    ct2AssertEquals((string) ($ct2Vouchers200Before + 1), (string) ct2ApiLogCount('ct2_vouchers', 200), 'Vouchers API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'marketing.api_voucher_create', 'voucher', $ct2VoucherId), 'Voucher API audit row was not recorded', $ct2Prefix);
    $ct2VoucherGet = $ct2GetJson($ct2AdminSession, 'ct2_vouchers.php');
    $ct2AssertJson(200, $ct2VoucherGet, true, 'Vouchers API GET did not return JSON 200');
    ct2AssertContains($ct2VoucherGet['body'], '"voucher_code":"' . $ct2VoucherCode . '"', 'Vouchers API GET did not return the created voucher', $ct2Prefix);

    $ct2Affiliates422Before = ct2ApiLogCount('ct2_affiliates', 422);
    $ct2InvalidAffiliate = $ct2PostJson($ct2AdminSession, 'ct2_affiliates.php', ['affiliate_code' => $ct2AffiliateCode]);
    $ct2AssertJson(422, $ct2InvalidAffiliate, false, 'Affiliates API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Affiliates422Before + 1), (string) ct2ApiLogCount('ct2_affiliates', 422), 'Affiliates API 422 log count did not increment', $ct2Prefix);

    $ct2Affiliates200Before = ct2ApiLogCount('ct2_affiliates', 200);
    $ct2AffiliateCreate = $ct2PostJson($ct2AdminSession, 'ct2_affiliates.php', [
        'affiliate_code' => $ct2AffiliateCode,
        'affiliate_name' => 'CT2 API Affiliate ' . $ct2RunId,
        'contact_name' => 'API Affiliate Contact',
        'email' => 'affiliate-api-' . $ct2RunId . '@example.com',
        'phone' => '+63-917-540-' . str_pad((string) ((int) $ct2RunId % 10000), 4, '0', STR_PAD_LEFT),
        'affiliate_status' => 'active',
        'commission_rate' => 8.5,
        'payout_status' => 'ready',
        'referral_code' => $ct2AffiliateReferral,
        'source_system' => 'partner_portal',
    ]);
    $ct2AffiliateData = $ct2AssertJson(200, $ct2AffiliateCreate, true, 'Affiliates API create did not return JSON 200');
    $ct2AffiliateId = ct2JsonValue($ct2AffiliateData, 'data.ct2_affiliate_id');
    ct2AssertEquals((string) ($ct2Affiliates200Before + 1), (string) ct2ApiLogCount('ct2_affiliates', 200), 'Affiliates API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'marketing.api_affiliate_create', 'affiliate', $ct2AffiliateId), 'Affiliate API audit row was not recorded', $ct2Prefix);
    $ct2AffiliateGet = $ct2GetJson($ct2AdminSession, 'ct2_affiliates.php?search=' . rawurlencode($ct2AffiliateCode));
    $ct2AssertJson(200, $ct2AffiliateGet, true, 'Affiliates API GET did not return JSON 200');
    ct2AssertContains($ct2AffiliateGet['body'], '"affiliate_code":"' . $ct2AffiliateCode . '"', 'Affiliates API GET did not return the created affiliate', $ct2Prefix);

    ct2Log($ct2Prefix, 'Creating visa records through API.');
    $ct2VisaTypeId = ct2Probe('visa-type-id', 'VISA-SG-TOUR');
    $ct2VisaApps422Before = ct2ApiLogCount('ct2_visa_applications', 422);
    $ct2InvalidVisaApplication = $ct2PostJson($ct2AdminSession, 'ct2_visa_applications.php', ['application_reference' => $ct2ApplicationReference]);
    $ct2AssertJson(422, $ct2InvalidVisaApplication, false, 'Visa applications API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2VisaApps422Before + 1), (string) ct2ApiLogCount('ct2_visa_applications', 422), 'Visa applications API 422 log count did not increment', $ct2Prefix);

    $ct2VisaApps200Before = ct2ApiLogCount('ct2_visa_applications', 200);
    $ct2VisaApplicationCreate = $ct2PostJson($ct2AdminSession, 'ct2_visa_applications.php', [
        'ct2_visa_type_id' => (int) $ct2VisaTypeId,
        'application_reference' => $ct2ApplicationReference,
        'external_customer_id' => 'API-CUST-' . $ct2RunId,
        'external_agent_id' => $ct2AgentCode,
        'source_system' => 'ct1',
        'status' => 'submitted',
        'submission_date' => $ct2Today,
        'approval_status' => 'not_required',
        'remarks' => 'API visa application ' . $ct2RunId,
    ]);
    $ct2VisaApplicationData = $ct2AssertJson(200, $ct2VisaApplicationCreate, true, 'Visa applications API create did not return JSON 200');
    $ct2VisaApplicationId = ct2JsonValue($ct2VisaApplicationData, 'data.ct2_visa_application_id');
    ct2AssertEquals((string) ($ct2VisaApps200Before + 1), (string) ct2ApiLogCount('ct2_visa_applications', 200), 'Visa applications API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'visa.api_application_create', 'visa_application', $ct2VisaApplicationId), 'Visa applications API audit row was not recorded', $ct2Prefix);
    $ct2VisaApplicationGet = $ct2GetJson($ct2AdminSession, 'ct2_visa_applications.php?search=' . rawurlencode($ct2ApplicationReference));
    $ct2AssertJson(200, $ct2VisaApplicationGet, true, 'Visa applications API GET did not return JSON 200');
    ct2AssertContains($ct2VisaApplicationGet['body'], '"application_reference":"' . $ct2ApplicationReference . '"', 'Visa applications API GET did not return the created application', $ct2Prefix);

    $ct2ChecklistId = ct2Probe('checklist-id', $ct2ApplicationReference, 'Passport bio page');
    $ct2VisaChecklists422Before = ct2ApiLogCount('ct2_visa_checklists', 422);
    $ct2InvalidChecklist = $ct2PostJson($ct2AdminSession, 'ct2_visa_checklists.php', ['ct2_visa_application_id' => (int) $ct2VisaApplicationId]);
    $ct2AssertJson(422, $ct2InvalidChecklist, false, 'Visa checklists API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2VisaChecklists422Before + 1), (string) ct2ApiLogCount('ct2_visa_checklists', 422), 'Visa checklists API 422 log count did not increment', $ct2Prefix);

    $ct2VisaChecklists200Before = ct2ApiLogCount('ct2_visa_checklists', 200);
    $ct2ChecklistSave = $ct2PostJson($ct2AdminSession, 'ct2_visa_checklists.php', [
        'ct2_visa_application_id' => (int) $ct2VisaApplicationId,
        'ct2_application_checklist_id' => (int) $ct2ChecklistId,
        'checklist_status' => 'verified',
        'verification_notes' => 'API checklist ' . $ct2RunId,
        'file_name' => $ct2DocumentName,
        'file_path' => $ct2DocumentPath,
        'mime_type' => 'application/pdf',
    ]);
    $ct2AssertJson(200, $ct2ChecklistSave, true, 'Visa checklists API update did not return JSON 200');
    ct2AssertEquals((string) ($ct2VisaChecklists200Before + 1), (string) ct2ApiLogCount('ct2_visa_checklists', 200), 'Visa checklists API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'visa.api_checklist_update', 'visa_application', $ct2VisaApplicationId), 'Visa checklists API audit row was not recorded', $ct2Prefix);
    ct2AssertEquals('verified', ct2Probe('checklist-status', $ct2ApplicationReference, 'Passport bio page'), 'Visa checklists API did not persist the checklist status', $ct2Prefix);
    ct2AssertEquals($ct2DocumentName, ct2Probe('latest-document-name', $ct2ApplicationReference), 'Visa checklists API did not persist the document metadata', $ct2Prefix);

    $ct2VisaPayments422Before = ct2ApiLogCount('ct2_visa_payments', 422);
    $ct2InvalidPayment = $ct2PostJson($ct2AdminSession, 'ct2_visa_payments.php', ['payment_reference' => $ct2PaymentReference]);
    $ct2AssertJson(422, $ct2InvalidPayment, false, 'Visa payments API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2VisaPayments422Before + 1), (string) ct2ApiLogCount('ct2_visa_payments', 422), 'Visa payments API 422 log count did not increment', $ct2Prefix);

    $ct2VisaPayments200Before = ct2ApiLogCount('ct2_visa_payments', 200);
    $ct2PaymentCreate = $ct2PostJson($ct2AdminSession, 'ct2_visa_payments.php', [
        'ct2_visa_application_id' => (int) $ct2VisaApplicationId,
        'payment_reference' => $ct2PaymentReference,
        'amount' => '4250.00',
        'currency' => 'PHP',
        'payment_method' => 'Manual',
        'payment_status' => 'completed',
        'source_system' => 'cashier',
    ]);
    $ct2PaymentData = $ct2AssertJson(200, $ct2PaymentCreate, true, 'Visa payments API create did not return JSON 200');
    $ct2VisaPaymentId = ct2JsonValue($ct2PaymentData, 'data.ct2_visa_payment_id');
    ct2AssertEquals((string) ($ct2VisaPayments200Before + 1), (string) ct2ApiLogCount('ct2_visa_payments', 200), 'Visa payments API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'visa.api_payment_create', 'visa_payment', $ct2VisaPaymentId), 'Visa payments API audit row was not recorded', $ct2Prefix);
    $ct2PaymentGet = $ct2GetJson($ct2AdminSession, 'ct2_visa_payments.php');
    $ct2AssertJson(200, $ct2PaymentGet, true, 'Visa payments API GET did not return JSON 200');
    ct2AssertContains($ct2PaymentGet['body'], '"payment_reference":"' . $ct2PaymentReference . '"', 'Visa payments API GET did not return the created payment', $ct2Prefix);

    $ct2VisaStatus422Before = ct2ApiLogCount('ct2_visa_status', 422);
    $ct2InvalidStatus = $ct2PostJson($ct2AdminSession, 'ct2_visa_status.php', ['status' => 'completed']);
    $ct2AssertJson(422, $ct2InvalidStatus, false, 'Visa status API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2VisaStatus422Before + 1), (string) ct2ApiLogCount('ct2_visa_status', 422), 'Visa status API 422 log count did not increment', $ct2Prefix);

    $ct2VisaStatus200Before = ct2ApiLogCount('ct2_visa_status', 200);
    $ct2VisaStatusSave = $ct2PostJson($ct2AdminSession, 'ct2_visa_status.php', [
        'ct2_visa_application_id' => (int) $ct2VisaApplicationId,
        'status' => 'appointment_scheduled',
        'appointment_date' => $ct2NextWeek . ' 09:00:00',
        'embassy_reference' => 'EMB-API-' . $ct2RunId,
        'approval_status' => 'not_required',
        'remarks' => 'API status update ' . $ct2RunId,
    ]);
    $ct2AssertJson(200, $ct2VisaStatusSave, true, 'Visa status API update did not return JSON 200');
    ct2AssertEquals((string) ($ct2VisaStatus200Before + 1), (string) ct2ApiLogCount('ct2_visa_status', 200), 'Visa status API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'visa.api_status_update', 'visa_application', $ct2VisaApplicationId), 'Visa status API audit row was not recorded', $ct2Prefix);
    $ct2VisaStatusGet = $ct2GetJson($ct2AdminSession, 'ct2_visa_applications.php?search=' . rawurlencode($ct2ApplicationReference));
    $ct2AssertJson(200, $ct2VisaStatusGet, true, 'Visa applications API GET did not return JSON 200 after status update');
    ct2AssertContains($ct2VisaStatusGet['body'], '"status":"appointment_scheduled"', 'Visa applications API GET did not reflect the status update', $ct2Prefix);

    ct2Log($ct2Prefix, 'Creating financial records through API.');
    $ct2Financial403Before = ct2ApiLogCount('ct2_financial_reports', 403);
    $ct2DeskFinancial = $ct2PostJson($ct2DeskSession, 'ct2_financial_reports.php', [
        'report_code' => 'DENIED',
        'report_name' => 'Denied',
    ]);
    $ct2AssertJson(403, $ct2DeskFinancial, false, 'Financial reports API desk POST did not return JSON 403');
    ct2AssertEquals((string) ($ct2Financial403Before + 1), (string) ct2ApiLogCount('ct2_financial_reports', 403), 'Financial reports API 403 log count did not increment', $ct2Prefix);

    $ct2Financial422Before = ct2ApiLogCount('ct2_financial_reports', 422);
    $ct2InvalidFinancial = $ct2PostJson($ct2AdminSession, 'ct2_financial_reports.php', [
        'report_code' => $ct2ReportCode,
        'report_name' => $ct2ReportName,
        'report_scope' => 'invalid_scope',
    ]);
    $ct2AssertJson(422, $ct2InvalidFinancial, false, 'Financial reports API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Financial422Before + 1), (string) ct2ApiLogCount('ct2_financial_reports', 422), 'Financial reports API 422 log count did not increment', $ct2Prefix);

    $ct2Financial200Before = ct2ApiLogCount('ct2_financial_reports', 200);
    $ct2FinancialCreate = $ct2PostJson($ct2AdminSession, 'ct2_financial_reports.php', [
        'report_code' => $ct2ReportCode,
        'report_name' => $ct2ReportName,
        'report_scope' => 'cross_module',
        'report_status' => 'active',
        'default_date_range' => '30d',
        'definition_notes' => 'API report ' . $ct2RunId,
    ]);
    $ct2FinancialData = $ct2AssertJson(200, $ct2FinancialCreate, true, 'Financial reports API create did not return JSON 200');
    $ct2ReportId = ct2JsonValue($ct2FinancialData, 'data.ct2_financial_report_id');
    ct2AssertEquals((string) ($ct2Financial200Before + 1), (string) ct2ApiLogCount('ct2_financial_reports', 200), 'Financial reports API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals('1', ct2Probe('audit-count', 'financial.api_report_create', 'financial_report', $ct2ReportId), 'Financial reports API audit row was not recorded', $ct2Prefix);
    $ct2FinancialGet = $ct2GetJson($ct2AdminSession, 'ct2_financial_reports.php');
    $ct2AssertJson(200, $ct2FinancialGet, true, 'Financial reports API GET did not return JSON 200');
    ct2AssertContains($ct2FinancialGet['body'], '"report_code":"' . $ct2ReportCode . '"', 'Financial reports API GET did not return the created report', $ct2Prefix);

    $ct2FlagId = ct2Probe('flag-id', 'suppliers', 'SUP-CT2-002');
    $ct2Flags403Before = ct2ApiLogCount('ct2_financial_snapshots', 403);
    $ct2DeskSnapshots = $ct2PostJson($ct2DeskSession, 'ct2_financial_snapshots.php', [
        'ct2_reconciliation_flag_id' => (int) $ct2FlagId,
    ]);
    $ct2AssertJson(403, $ct2DeskSnapshots, false, 'Financial snapshots API desk POST did not return JSON 403');
    ct2AssertEquals((string) ($ct2Flags403Before + 1), (string) ct2ApiLogCount('ct2_financial_snapshots', 403), 'Financial snapshots API 403 log count did not increment', $ct2Prefix);

    $ct2Flags422Before = ct2ApiLogCount('ct2_financial_snapshots', 422);
    $ct2InvalidSnapshot = $ct2PostJson($ct2AdminSession, 'ct2_financial_snapshots.php', [
        'ct2_reconciliation_flag_id' => (int) $ct2FlagId,
        'flag_status' => 'bad_status',
    ]);
    $ct2AssertJson(422, $ct2InvalidSnapshot, false, 'Financial snapshots API invalid payload did not return JSON 422');
    ct2AssertEquals((string) ($ct2Flags422Before + 1), (string) ct2ApiLogCount('ct2_financial_snapshots', 422), 'Financial snapshots API 422 log count did not increment', $ct2Prefix);

    $ct2Flags200Before = ct2ApiLogCount('ct2_financial_snapshots', 200);
    $ct2FlagAuditBefore = (int) ct2Probe('audit-count', 'financial.api_flag_update', 'reconciliation_flag', $ct2FlagId);
    $ct2SnapshotSave = $ct2PostJson($ct2AdminSession, 'ct2_financial_snapshots.php', [
        'ct2_reconciliation_flag_id' => (int) $ct2FlagId,
        'flag_status' => 'acknowledged',
        'resolution_notes' => $ct2FlagNote,
    ]);
    $ct2AssertJson(200, $ct2SnapshotSave, true, 'Financial snapshots API update did not return JSON 200');
    ct2AssertEquals((string) ($ct2Flags200Before + 1), (string) ct2ApiLogCount('ct2_financial_snapshots', 200), 'Financial snapshots API 200 log count did not increment', $ct2Prefix);
    ct2AssertEquals((string) ($ct2FlagAuditBefore + 1), ct2Probe('audit-count', 'financial.api_flag_update', 'reconciliation_flag', $ct2FlagId), 'Financial snapshots API audit row did not increment', $ct2Prefix);
    ct2AssertEquals('acknowledged', ct2Probe('flag-field', 'suppliers', 'SUP-CT2-002', 'flag_status'), 'Financial snapshots API did not persist the flag status', $ct2Prefix);
    ct2AssertEquals($ct2FlagNote, ct2Probe('flag-field', 'suppliers', 'SUP-CT2-002', 'resolution_notes'), 'Financial snapshots API did not persist the resolution note', $ct2Prefix);

    ct2Log($ct2Prefix, 'CT2 API POST regression checks passed.');
} finally {
    if (is_array($ct2Server)) {
        ct2StopProcess($ct2Server['proc']);
    }
    ct2RemoveDir($ct2TempDir);
}
