<?php

declare(strict_types=1);

require_once __DIR__ . '/ct2_validation_common.php';

ct2AssertCli();

$ct2Prefix = 'ct2-hardening';
$ct2TempDir = ct2CreateTempDir($ct2Prefix);
$ct2Server = null;
$ct2RunId = (string) time();

try {
    $ct2Today = date('Y-m-d');
    $ct2Tomorrow = date('Y-m-d', strtotime('+1 day'));
    $ct2NextWeek = date('Y-m-d', strtotime('+7 day'));
    $ct2TwoWeeks = date('Y-m-d', strtotime('+14 day'));
    $ct2ThirtyDays = date('Y-m-d', strtotime('+30 day'));
    $ct2NextYear = date('Y-m-d', strtotime('+365 day'));
    $ct2NowLocal = date('Y-m-d\TH:i');
    $ct2NextDayLocal = date('Y-m-d\TH:i', strtotime('+1 day'));
    $ct2FourDaysLocal = date('Y-m-d\TH:i', strtotime('+4 day'));
    $ct2VisaNoteNextAction = date('Y-m-d', strtotime('+3 day'));

    $ct2Port = ct2SelectPort(8092);
    $ct2BaseUrl = 'http://127.0.0.1:' . $ct2Port . '/ct2_index.php';
    $ct2ApiBaseUrl = 'http://127.0.0.1:' . $ct2Port . '/api';

    $ct2ResourceName = 'CT2 Hardening Resource ' . $ct2RunId;
    $ct2PackageName = 'CT2 Hardening Package ' . $ct2RunId;
    $ct2AllocationBookingId = 'CT2-HARD-ALLOC-' . $ct2RunId;
    $ct2BlockReason = 'CT2 Hardening Block ' . $ct2RunId;
    $ct2PlateNumber = 'CT2H-' . substr($ct2RunId, -4);
    $ct2DriverName = 'CT2 Hardening Driver ' . $ct2RunId;
    $ct2ServiceType = 'CT2 Hardening Service ' . $ct2RunId;
    $ct2ContractCode = 'CTR-HARD-' . $ct2RunId;
    $ct2ContractTitle = 'CT2 Hardening Contract ' . $ct2RunId;
    $ct2SupplierNoteTitle = 'CT2 Hardening Supplier Note ' . $ct2RunId;
    $ct2SupplierNoteBody = 'Supplier regression note ' . $ct2RunId;
    $ct2CampaignName = 'North Luzon Coach Summer Push Hardening ' . $ct2RunId;
    $ct2PromotionName = 'North Luzon Early Bird Hardening ' . $ct2RunId;
    $ct2VoucherCode = 'VOUCH-HARD-' . $ct2RunId;
    $ct2VoucherName = 'CT2 Hardening Voucher ' . $ct2RunId;
    $ct2AffiliateName = 'Biyahe Deals Network Hardening ' . $ct2RunId;
    $ct2ReferralCode = 'HREF-' . $ct2RunId;
    $ct2RedemptionBookingId = 'CT2-HARD-BOOK-' . $ct2RunId;
    $ct2ReviewBatchId = 'REV-HARD-' . $ct2RunId;
    $ct2MarketingNoteTitle = 'CT2 Hardening Marketing Note ' . $ct2RunId;
    $ct2PaymentReference = 'PAY-HARD-' . $ct2RunId;
    $ct2NotificationRecipient = 'hardening-' . $ct2RunId . '@example.com';
    $ct2FilterKey = 'hardening_key_' . $ct2RunId;
    $ct2FilterLabel = 'Hardening Filter ' . $ct2RunId;
    $ct2RunLabel = 'CT2 Hardening Run ' . $ct2RunId;

    ct2Log($ct2Prefix, 'Starting local CT2 PHP server.');
    $ct2Server = ct2StartPhpServer($ct2Port, $ct2TempDir, '?module=auth&action=login');
    $ct2Session = ct2CreateHttpSession($ct2TempDir);

    $ct2Get = static function (string $url) use ($ct2Session): array {
        return ct2HttpRequest('GET', $url, $ct2Session);
    };

    $ct2Post = static function (string $url, array $formParams, bool $followRedirects = true) use ($ct2Session): array {
        return ct2HttpRequest('POST', $url, $ct2Session, [], $formParams, null, $followRedirects);
    };

    ct2Log($ct2Prefix, 'Preparing upload fixture.');
    $ct2UploadPath = $ct2TempDir . DIRECTORY_SEPARATOR . 'ct2_hardening_upload.png';
    ct2BuildUploadFixture($ct2UploadPath);

    ct2Log($ct2Prefix, 'Signing in as seeded CT2 administrator.');
    $ct2LoginPage = $ct2Get($ct2BaseUrl . '?module=auth&action=login');
    ct2AssertStatus(200, $ct2LoginPage, 'Login page did not load', $ct2Prefix);
    $ct2SessionBeforeLogin = ct2SessionCookieValue($ct2Session);
    $ct2Csrf = ct2ExtractCsrf($ct2LoginPage['body']);
    $ct2Login = $ct2Post(
        $ct2BaseUrl . '?module=auth&action=login',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'username' => 'ct2admin',
            'password' => 'ChangeMe123!',
        ]
    );
    ct2AssertStatus(200, $ct2Login, 'Login submission did not complete', $ct2Prefix);
    ct2AssertContains($ct2Login['body'], 'Back-Office Dashboard', 'Login did not land on the dashboard', $ct2Prefix);
    $ct2SessionAfterLogin = ct2SessionCookieValue($ct2Session);
    ct2AssertNotEquals($ct2SessionBeforeLogin, $ct2SessionAfterLogin, 'Browser login did not rotate the CT2 session identifier', $ct2Prefix);
    $ct2DashboardCsrf = ct2ExtractCsrf($ct2Login['body']);
    ct2AssertNotEquals($ct2Csrf, $ct2DashboardCsrf, 'Browser login did not refresh the CT2 CSRF token after authentication', $ct2Prefix);

    ct2Log($ct2Prefix, 'Verifying dashboard and seeded read paths.');
    $ct2Dashboard = $ct2Get($ct2BaseUrl . '?module=dashboard&action=index');
    ct2AssertStatus(200, $ct2Dashboard, 'Dashboard route did not return 200', $ct2Prefix);
    ct2AssertContains($ct2Dashboard['body'], 'Back-Office Dashboard', 'Dashboard content did not render', $ct2Prefix);

    ct2Log($ct2Prefix, 'Verifying generic browser 500 handling.');
    $ct2Fault = ct2HttpRequest(
        'GET',
        $ct2BaseUrl . '?module=auth&action=login&ct2_validation_crash=1',
        $ct2Session,
        ['X-CT2-Validation-Mode' => '1']
    );
    ct2AssertStatus(500, $ct2Fault, 'Validation-only browser fault did not return 500', $ct2Prefix);
    ct2AssertContains($ct2Fault['body'], 'An unexpected error occurred. Please contact support.', 'Browser 500 response did not render the generic message', $ct2Prefix);
    ct2AssertNotContains($ct2Fault['body'], 'CT2 validation fault injection.', 'Browser 500 response leaked the validation exception message', $ct2Prefix);
    ct2AssertNotContains($ct2Fault['body'], 'RuntimeException', 'Browser 500 response leaked the exception class name', $ct2Prefix);

    $ct2AvailabilityRead = $ct2Get($ct2BaseUrl . '?module=availability&action=index&search=Skyline');
    ct2AssertStatus(200, $ct2AvailabilityRead, 'Availability search route did not return 200', $ct2Prefix);
    ct2AssertContains($ct2AvailabilityRead['body'], 'Skyline Coaster 18-Seater', 'Availability search did not render the seeded resource', $ct2Prefix);
    ct2AssertContains($ct2AvailabilityRead['body'], 'CT1-BKG-1001', 'Availability search did not render the seeded booking reference', $ct2Prefix);
    ct2AssertContains($ct2AvailabilityRead['body'], 'NAA-4581', 'Availability page did not render the seeded dispatch vehicle', $ct2Prefix);

    $ct2AgentId = ct2Probe('agent-id', 'AGT-CT2-002');
    $ct2SupplierId = ct2Probe('supplier-id', 'SUP-CT2-002');
    $ct2CampaignId = ct2Probe('campaign-id', 'CT2-MKT-001');
    $ct2PromotionId = ct2Probe('promotion-id', 'PROMO-CT2-001');
    $ct2VoucherId = ct2Probe('voucher-id', 'VOUCH-CT2-001');
    $ct2AffiliateId = ct2Probe('affiliate-id', 'AFF-CT2-001');
    $ct2VisaApplicationId = ct2Probe('visa-application-id', 'VISA-APP-001');
    $ct2ApprovalId = ct2Probe('approval-id', 'supplier', 'SUP-CT2-002');
    $ct2ChecklistId = ct2Probe('checklist-id', 'VISA-APP-001', 'Passport bio page');
    $ct2ReportRunId = ct2Probe('report-run-id', 'QA Baseline Cross-Module Run');
    $ct2FinancialReportId = ct2Probe('financial-report-id', 'CT2-OPS-001');
    $ct2FlagId = ct2Probe('flag-id', 'suppliers', 'SUP-CT2-002');

    ct2Log($ct2Prefix, 'Running positive agent update with audit verification.');
    $ct2AgentAuditBefore = (int) ct2Probe('audit-count', 'agents.update', 'agent', $ct2AgentId);
    $ct2AgentsPage = $ct2Get($ct2BaseUrl . '?module=agents&action=index&edit_id=' . rawurlencode($ct2AgentId));
    ct2AssertStatus(200, $ct2AgentsPage, 'Agents page did not load', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2AgentsPage['body']);
    $ct2AgentSave = $ct2Post(
        $ct2BaseUrl . '?module=agents&action=save',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_agent_id' => $ct2AgentId,
            'agent_code' => 'AGT-CT2-002',
            'agency_name' => 'Island Connect Tours',
            'contact_person' => 'Ramon Aquino',
            'email' => 'ramon@islandconnect.example.com',
            'phone' => '+63-917-200-0002',
            'region' => 'Visayas',
            'commission_rate' => '10.00',
            'support_level' => 'priority',
            'approval_status' => 'approved',
            'active_status' => 'active',
            'external_booking_id' => 'CT1-BKG-1002',
            'external_customer_id' => 'CT1-CUST-8802',
            'external_payment_id' => 'FIN-PAY-4402',
            'source_system' => 'ct1',
        ]
    );
    ct2AssertStatus(200, $ct2AgentSave, 'Agent update post did not complete', $ct2Prefix);
    ct2AssertContains($ct2AgentSave['body'], 'Agent profile saved successfully.', 'Agent update success flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2AgentAuditBefore + 1), ct2Probe('audit-count', 'agents.update', 'agent', $ct2AgentId), 'Agent update audit log did not increment', $ct2Prefix);

    ct2Log($ct2Prefix, 'Running invalid-CSRF supplier onboarding negative check.');
    $ct2SupplierAuditBefore = (int) ct2Probe('audit-count', 'suppliers.onboarding_update', 'supplier', $ct2SupplierId);
    $ct2SupplierDocumentsBefore = ct2Probe('supplier-onboarding-field', 'SUP-CT2-002', 'documents_status');
    $ct2SupplierReviewBefore = ct2Probe('supplier-onboarding-field', 'SUP-CT2-002', 'review_notes');
    $ct2SupplierInvalid = $ct2Post(
        $ct2BaseUrl . '?module=suppliers&action=saveOnboarding',
        [
            'ct2_csrf_token' => 'invalid-ct2-token',
            'ct2_supplier_id' => $ct2SupplierId,
            'checklist_status' => 'review_ready',
            'documents_status' => 'complete',
            'compliance_status' => 'cleared',
            'review_notes' => 'Invalid CSRF should not persist',
            'blocked_reason' => 'Invalid token test',
            'target_go_live_date' => $ct2Today,
        ]
    );
    ct2AssertStatus(200, $ct2SupplierInvalid, 'Invalid-CSRF supplier post did not complete', $ct2Prefix);
    ct2AssertContains($ct2SupplierInvalid['body'], 'Invalid request token.', 'Supplier invalid-CSRF flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) $ct2SupplierAuditBefore, ct2Probe('audit-count', 'suppliers.onboarding_update', 'supplier', $ct2SupplierId), 'Supplier invalid-CSRF request wrote an audit log', $ct2Prefix);
    ct2AssertEquals($ct2SupplierDocumentsBefore, ct2Probe('supplier-onboarding-field', 'SUP-CT2-002', 'documents_status'), 'Supplier invalid-CSRF request changed onboarding documents status', $ct2Prefix);
    ct2AssertEquals($ct2SupplierReviewBefore, ct2Probe('supplier-onboarding-field', 'SUP-CT2-002', 'review_notes'), 'Supplier invalid-CSRF request changed onboarding review notes', $ct2Prefix);

    ct2Log($ct2Prefix, 'Running supplier workflow coverage.');
    $ct2SuppliersPage = $ct2Get($ct2BaseUrl . '?module=suppliers&action=index&supplier_id=' . rawurlencode($ct2SupplierId));
    ct2AssertStatus(200, $ct2SuppliersPage, 'Suppliers page did not load', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2SuppliersPage['body']);

    $ct2SupplierReviewNote = 'Hardening supplier update ' . $ct2RunId;
    $ct2SupplierSave = $ct2Post(
        $ct2BaseUrl . '?module=suppliers&action=saveOnboarding',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_supplier_id' => $ct2SupplierId,
            'checklist_status' => 'review_ready',
            'documents_status' => 'complete',
            'compliance_status' => 'cleared',
            'review_notes' => $ct2SupplierReviewNote,
            'blocked_reason' => '',
            'target_go_live_date' => $ct2TwoWeeks,
        ]
    );
    ct2AssertStatus(200, $ct2SupplierSave, 'Supplier onboarding update did not complete', $ct2Prefix);
    ct2AssertContains($ct2SupplierSave['body'], 'Supplier onboarding record updated.', 'Supplier onboarding success flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2SupplierAuditBefore + 1), ct2Probe('audit-count', 'suppliers.onboarding_update', 'supplier', $ct2SupplierId), 'Supplier onboarding audit log did not increment', $ct2Prefix);
    ct2AssertEquals($ct2SupplierReviewNote, ct2Probe('supplier-onboarding-field', 'SUP-CT2-002', 'review_notes'), 'Supplier onboarding review notes did not persist', $ct2Prefix);

    $ct2ContractAuditBefore = (int) ct2Probe('audit-count', 'suppliers.contract_create');
    $ct2SupplierContract = $ct2Post(
        $ct2BaseUrl . '?module=suppliers&action=saveContract',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_supplier_id' => $ct2SupplierId,
            'contract_code' => $ct2ContractCode,
            'contract_title' => $ct2ContractTitle,
            'effective_date' => $ct2Today,
            'expiry_date' => $ct2ThirtyDays,
            'renewal_status' => 'not_started',
            'contract_status' => 'draft',
            'clause_summary' => 'Hardening contract summary ' . $ct2RunId,
            'mock_signature_status' => 'sent',
            'finance_handoff_status' => 'shared',
        ]
    );
    ct2AssertStatus(200, $ct2SupplierContract, 'Supplier contract save did not complete', $ct2Prefix);
    ct2AssertContains($ct2SupplierContract['body'], 'Supplier contract registered.', 'Supplier contract success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2SupplierContract['body'], $ct2ContractTitle, 'Supplier contract row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2ContractAuditBefore + 1), ct2Probe('audit-count', 'suppliers.contract_create'), 'Supplier contract audit log did not increment', $ct2Prefix);

    $ct2KpiAuditBefore = (int) ct2Probe('audit-count', 'suppliers.kpi_create');
    $ct2SupplierKpi = $ct2Post(
        $ct2BaseUrl . '?module=suppliers&action=saveKpi',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_supplier_id' => $ct2SupplierId,
            'measurement_date' => $ct2Today,
            'service_score' => '87.00',
            'delivery_score' => '85.00',
            'compliance_score' => '88.00',
            'responsiveness_score' => '86.00',
            'risk_flag' => 'watch',
            'notes' => 'Hardening KPI entry ' . $ct2RunId,
        ]
    );
    ct2AssertStatus(200, $ct2SupplierKpi, 'Supplier KPI save did not complete', $ct2Prefix);
    ct2AssertContains($ct2SupplierKpi['body'], 'Supplier KPI measurement saved.', 'Supplier KPI success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2SupplierKpi['body'], $ct2Today, 'Supplier KPI measurement date was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2KpiAuditBefore + 1), ct2Probe('audit-count', 'suppliers.kpi_create'), 'Supplier KPI audit log did not increment', $ct2Prefix);

    $ct2NoteAuditBefore = (int) ct2Probe('audit-count', 'suppliers.note_create');
    $ct2SupplierNote = $ct2Post(
        $ct2BaseUrl . '?module=suppliers&action=saveNote',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_supplier_id' => $ct2SupplierId,
            'note_type' => 'review',
            'note_title' => $ct2SupplierNoteTitle,
            'note_body' => $ct2SupplierNoteBody,
            'next_action_date' => $ct2NextWeek,
        ]
    );
    ct2AssertStatus(200, $ct2SupplierNote, 'Supplier note save did not complete', $ct2Prefix);
    ct2AssertContains($ct2SupplierNote['body'], 'Supplier relationship note recorded.', 'Supplier note success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2SupplierNote['body'], $ct2SupplierNoteTitle, 'Supplier note row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2NoteAuditBefore + 1), ct2Probe('audit-count', 'suppliers.note_create'), 'Supplier note audit log did not increment', $ct2Prefix);

    ct2Log($ct2Prefix, 'Running invalid-CSRF approval negative check.');
    $ct2ApprovalAuditBefore = (int) ct2Probe('audit-count', 'approvals.decide', 'supplier', $ct2SupplierId);
    $ct2ApprovalStatusBefore = ct2Probe('approval-status', 'supplier', 'SUP-CT2-002');
    $ct2ApprovalInvalid = $ct2Post(
        $ct2BaseUrl . '?module=approvals&action=decide',
        [
            'ct2_csrf_token' => 'invalid-ct2-token',
            'ct2_approval_workflow_id' => $ct2ApprovalId,
            'approval_status' => 'rejected',
            'decision_notes' => 'Invalid token should not persist',
        ]
    );
    ct2AssertStatus(200, $ct2ApprovalInvalid, 'Invalid-CSRF approval post did not complete', $ct2Prefix);
    ct2AssertContains($ct2ApprovalInvalid['body'], 'Invalid request token for approval decision.', 'Approval invalid-CSRF flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) $ct2ApprovalAuditBefore, ct2Probe('audit-count', 'approvals.decide', 'supplier', $ct2SupplierId), 'Approval invalid-CSRF request wrote an audit log', $ct2Prefix);
    ct2AssertEquals($ct2ApprovalStatusBefore, ct2Probe('approval-status', 'supplier', 'SUP-CT2-002'), 'Approval invalid-CSRF request changed workflow status', $ct2Prefix);

    ct2Log($ct2Prefix, 'Running positive approval decision with audit verification.');
    $ct2ApprovalsPage = $ct2Get($ct2BaseUrl . '?module=approvals&action=index');
    ct2AssertStatus(200, $ct2ApprovalsPage, 'Approvals page did not load', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2ApprovalsPage['body']);
    $ct2ApprovalSave = $ct2Post(
        $ct2BaseUrl . '?module=approvals&action=decide',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_approval_workflow_id' => $ct2ApprovalId,
            'approval_status' => 'approved',
            'decision_notes' => 'Hardening approval decision ' . $ct2RunId,
        ]
    );
    ct2AssertStatus(200, $ct2ApprovalSave, 'Approval decision did not complete', $ct2Prefix);
    ct2AssertContains($ct2ApprovalSave['body'], 'Approval decision recorded.', 'Approval success flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2ApprovalAuditBefore + 1), ct2Probe('audit-count', 'approvals.decide', 'supplier', $ct2SupplierId), 'Approval decision audit log did not increment', $ct2Prefix);
    ct2AssertEquals('approved', ct2Probe('approval-status', 'supplier', 'SUP-CT2-002'), 'Approval decision did not update workflow status to approved', $ct2Prefix);

    ct2Log($ct2Prefix, 'Running availability workflow coverage.');
    $ct2AvailabilityPage = $ct2Get($ct2BaseUrl . '?module=availability&action=index');
    ct2AssertStatus(200, $ct2AvailabilityPage, 'Availability page did not load', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2AvailabilityPage['body']);

    $ct2ResourceAuditBefore = (int) ct2Probe('audit-count', 'availability.resource_create');
    $ct2AvailabilityInvalid = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=saveResource',
        [
            'ct2_csrf_token' => 'invalid-ct2-token',
            'ct2_supplier_id' => $ct2SupplierId,
            'resource_name' => $ct2ResourceName,
            'resource_type' => 'transport',
            'capacity' => '18',
            'base_cost' => '4200.00',
            'status' => 'available',
            'notes' => 'Invalid token should not persist',
        ]
    );
    ct2AssertStatus(200, $ct2AvailabilityInvalid, 'Invalid-CSRF availability resource post did not complete', $ct2Prefix);
    ct2AssertContains($ct2AvailabilityInvalid['body'], 'Invalid request token.', 'Availability invalid-CSRF flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) $ct2ResourceAuditBefore, ct2Probe('audit-count', 'availability.resource_create'), 'Availability invalid-CSRF request wrote an audit log', $ct2Prefix);

    $ct2ResourceSave = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=saveResource',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_supplier_id' => $ct2SupplierId,
            'resource_name' => $ct2ResourceName,
            'resource_type' => 'transport',
            'capacity' => '24',
            'base_cost' => '4200.00',
            'status' => 'available',
            'notes' => 'CT2 hardening resource ' . $ct2RunId,
        ]
    );
    ct2AssertStatus(200, $ct2ResourceSave, 'Availability resource save did not complete', $ct2Prefix);
    ct2AssertContains($ct2ResourceSave['body'], 'Inventory resource saved.', 'Availability resource success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2ResourceSave['body'], $ct2ResourceName, 'Availability resource row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2ResourceAuditBefore + 1), ct2Probe('audit-count', 'availability.resource_create'), 'Availability resource audit log did not increment', $ct2Prefix);
    $ct2ResourceId = ct2Probe('resource-id', $ct2ResourceName);

    $ct2PackageAuditBefore = (int) ct2Probe('audit-count', 'availability.package_create');
    $ct2PackageSave = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=savePackage',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'package_name' => $ct2PackageName,
            'base_price' => '18999.00',
            'margin_percentage' => '18.00',
            'ct2_resource_id' => $ct2ResourceId,
            'units_required' => '1',
            'is_active' => 'on',
        ]
    );
    ct2AssertStatus(200, $ct2PackageSave, 'Availability package save did not complete', $ct2Prefix);
    ct2AssertContains($ct2PackageSave['body'], 'Tour package saved.', 'Availability package success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2PackageSave['body'], $ct2PackageName, 'Availability package row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2PackageAuditBefore + 1), ct2Probe('audit-count', 'availability.package_create'), 'Availability package audit log did not increment', $ct2Prefix);
    $ct2PackageId = ct2Probe('package-id', $ct2PackageName);

    $ct2AllocationAuditBefore = (int) ct2Probe('audit-count', 'availability.allocation_create');
    $ct2AllocationSave = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=saveAllocation',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_resource_id' => $ct2ResourceId,
            'ct2_package_id' => $ct2PackageId,
            'external_booking_id' => $ct2AllocationBookingId,
            'allocation_date' => $ct2Today,
            'pax_count' => '12',
            'reserved_units' => '1',
            'notes' => 'CT2 hardening allocation ' . $ct2RunId,
        ]
    );
    ct2AssertStatus(200, $ct2AllocationSave, 'Availability allocation save did not complete', $ct2Prefix);
    ct2AssertContains($ct2AllocationSave['body'], $ct2AllocationBookingId, 'Availability allocation row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2AllocationAuditBefore + 1), ct2Probe('audit-count', 'availability.allocation_create'), 'Availability allocation audit log did not increment', $ct2Prefix);

    $ct2BlockAuditBefore = (int) ct2Probe('audit-count', 'availability.block_create');
    $ct2BlockSave = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=saveBlock',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_resource_id' => $ct2ResourceId,
            'start_date' => $ct2NextWeek,
            'end_date' => $ct2TwoWeeks,
            'reason' => $ct2BlockReason,
            'block_type' => 'manual_soft_block',
        ]
    );
    ct2AssertStatus(200, $ct2BlockSave, 'Availability block save did not complete', $ct2Prefix);
    ct2AssertContains($ct2BlockSave['body'], 'Seasonal block created.', 'Availability block success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2BlockSave['body'], $ct2BlockReason, 'Availability block row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2BlockAuditBefore + 1), ct2Probe('audit-count', 'availability.block_create'), 'Availability block audit log did not increment', $ct2Prefix);

    $ct2VehicleAuditBefore = (int) ct2Probe('audit-count', 'availability.vehicle_create');
    $ct2VehicleSave = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=saveVehicle',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'plate_number' => $ct2PlateNumber,
            'model_name' => 'CT2 Hardening Transporter',
            'vehicle_capacity' => '24',
            'current_mileage' => '12000',
            'vehicle_status' => 'available',
        ]
    );
    ct2AssertStatus(200, $ct2VehicleSave, 'Availability vehicle save did not complete', $ct2Prefix);
    ct2AssertContains($ct2VehicleSave['body'], 'Dispatch vehicle saved.', 'Availability vehicle success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2VehicleSave['body'], $ct2PlateNumber, 'Availability vehicle row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2VehicleAuditBefore + 1), ct2Probe('audit-count', 'availability.vehicle_create'), 'Availability vehicle audit log did not increment', $ct2Prefix);
    $ct2VehicleId = ct2Probe('vehicle-id', $ct2PlateNumber);

    $ct2DriverAuditBefore = (int) ct2Probe('audit-count', 'availability.driver_create');
    $ct2DriverSave = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=saveDriver',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'driver_name' => $ct2DriverName,
            'license_expiry' => $ct2NextYear,
            'driver_status' => 'available',
        ]
    );
    ct2AssertStatus(200, $ct2DriverSave, 'Availability driver save did not complete', $ct2Prefix);
    ct2AssertContains($ct2DriverSave['body'], 'Dispatch driver saved.', 'Availability driver success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2DriverSave['body'], $ct2DriverName, 'Availability driver row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2DriverAuditBefore + 1), ct2Probe('audit-count', 'availability.driver_create'), 'Availability driver audit log did not increment', $ct2Prefix);
    $ct2DriverId = ct2Probe('driver-id', $ct2DriverName);

    $ct2DispatchAuditBefore = (int) ct2Probe('audit-count', 'availability.dispatch_create');
    $ct2DispatchSave = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=saveDispatch',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_allocation_id' => '0',
            'ct2_vehicle_id' => $ct2VehicleId,
            'ct2_driver_id' => $ct2DriverId,
            'dispatch_date' => $ct2Tomorrow,
            'dispatch_time' => $ct2NextDayLocal,
            'return_time' => $ct2FourDaysLocal,
            'start_mileage' => '12000',
            'end_mileage' => '12120',
            'dispatch_status' => 'scheduled',
        ]
    );
    ct2AssertStatus(200, $ct2DispatchSave, 'Availability dispatch save did not complete', $ct2Prefix);
    ct2AssertContains($ct2DispatchSave['body'], 'Dispatch order saved.', 'Availability dispatch success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2DispatchSave['body'], $ct2PlateNumber, 'Availability dispatch row was not rendered', $ct2Prefix);
    ct2AssertContains($ct2DispatchSave['body'], $ct2DriverName, 'Availability dispatch driver was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2DispatchAuditBefore + 1), ct2Probe('audit-count', 'availability.dispatch_create'), 'Availability dispatch audit log did not increment', $ct2Prefix);

    $ct2MaintenanceAuditBefore = (int) ct2Probe('audit-count', 'availability.maintenance_create');
    $ct2MaintenanceSave = $ct2Post(
        $ct2BaseUrl . '?module=availability&action=saveMaintenance',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'maintenance_vehicle_id' => $ct2VehicleId,
            'service_date' => $ct2Tomorrow,
            'service_type' => $ct2ServiceType,
            'maintenance_cost' => '6500.00',
            'mechanic_notes' => 'CT2 hardening maintenance ' . $ct2RunId,
        ]
    );
    ct2AssertStatus(200, $ct2MaintenanceSave, 'Availability maintenance save did not complete', $ct2Prefix);
    ct2AssertContains($ct2MaintenanceSave['body'], 'Maintenance log saved.', 'Availability maintenance success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2MaintenanceSave['body'], $ct2ServiceType, 'Availability maintenance row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2MaintenanceAuditBefore + 1), ct2Probe('audit-count', 'availability.maintenance_create'), 'Availability maintenance audit log did not increment', $ct2Prefix);

    ct2Log($ct2Prefix, 'Running marketing workflow coverage.');
    $ct2MarketingPage = $ct2Get($ct2BaseUrl . '?module=marketing&action=index');
    ct2AssertStatus(200, $ct2MarketingPage, 'Marketing page did not load', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2MarketingPage['body']);

    $ct2CampaignAuditBefore = (int) ct2Probe('audit-count', 'marketing.campaign_update');
    $ct2MarketingInvalid = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=saveCampaign',
        [
            'ct2_csrf_token' => 'invalid-ct2-token',
            'ct2_campaign_id' => $ct2CampaignId,
            'campaign_code' => 'CT2-MKT-001',
            'campaign_name' => $ct2CampaignName,
            'campaign_type' => 'seasonal',
            'channel_type' => 'hybrid',
            'start_date' => $ct2Today,
            'end_date' => $ct2ThirtyDays,
            'budget_amount' => '150000.00',
            'status' => 'active',
            'approval_status' => 'approved',
            'target_audience' => 'Invalid token should not persist',
            'external_customer_segment_id' => 'SEG-HARD-INVALID',
            'source_system' => 'crm',
        ]
    );
    ct2AssertStatus(200, $ct2MarketingInvalid, 'Invalid-CSRF marketing campaign post did not complete', $ct2Prefix);
    ct2AssertContains($ct2MarketingInvalid['body'], 'Invalid request token.', 'Marketing invalid-CSRF flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) $ct2CampaignAuditBefore, ct2Probe('audit-count', 'marketing.campaign_update'), 'Marketing invalid-CSRF request wrote an audit log', $ct2Prefix);

    $ct2CampaignSave = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=saveCampaign',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_campaign_id' => $ct2CampaignId,
            'campaign_code' => 'CT2-MKT-001',
            'campaign_name' => $ct2CampaignName,
            'campaign_type' => 'seasonal',
            'channel_type' => 'hybrid',
            'start_date' => $ct2Today,
            'end_date' => $ct2ThirtyDays,
            'budget_amount' => '155000.00',
            'status' => 'active',
            'approval_status' => 'approved',
            'target_audience' => 'CT2 hardening audience ' . $ct2RunId,
            'external_customer_segment_id' => 'SEG-HARD-' . $ct2RunId,
            'source_system' => 'crm',
        ]
    );
    ct2AssertStatus(200, $ct2CampaignSave, 'Marketing campaign save did not complete', $ct2Prefix);
    ct2AssertContains($ct2CampaignSave['body'], 'Marketing campaign saved successfully.', 'Marketing campaign success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2CampaignSave['body'], $ct2CampaignName, 'Marketing campaign row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2CampaignAuditBefore + 1), ct2Probe('audit-count', 'marketing.campaign_update'), 'Marketing campaign audit log did not increment', $ct2Prefix);

    $ct2PromotionAuditBefore = (int) ct2Probe('audit-count', 'marketing.promotion_update');
    $ct2PromotionSave = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=savePromotion',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_promotion_id' => $ct2PromotionId,
            'ct2_campaign_id' => $ct2CampaignId,
            'promotion_code' => 'PROMO-CT2-001',
            'promotion_name' => $ct2PromotionName,
            'promotion_type' => 'percentage',
            'discount_value' => '14.50',
            'eligibility_rule' => 'CT2 hardening promotion rule ' . $ct2RunId,
            'valid_from' => $ct2Today,
            'valid_until' => $ct2TwoWeeks,
            'usage_limit' => '150',
            'promotion_status' => 'active',
            'approval_status' => 'approved',
            'external_booking_scope' => 'hardening_scope_' . $ct2RunId,
            'source_system' => 'ct1',
        ]
    );
    ct2AssertStatus(200, $ct2PromotionSave, 'Marketing promotion save did not complete', $ct2Prefix);
    ct2AssertContains($ct2PromotionSave['body'], 'Promotion saved successfully.', 'Marketing promotion success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2PromotionSave['body'], $ct2PromotionName, 'Marketing promotion row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2PromotionAuditBefore + 1), ct2Probe('audit-count', 'marketing.promotion_update'), 'Marketing promotion audit log did not increment', $ct2Prefix);

    $ct2VoucherAuditBefore = (int) ct2Probe('audit-count', 'marketing.voucher_create');
    $ct2VoucherSave = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=saveVoucher',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_promotion_id' => $ct2PromotionId,
            'voucher_code' => $ct2VoucherCode,
            'voucher_name' => $ct2VoucherName,
            'customer_scope' => 'multi_use',
            'max_redemptions' => '3',
            'voucher_status' => 'active',
            'valid_from' => $ct2Today,
            'valid_until' => $ct2TwoWeeks,
            'external_customer_id' => 'CT1-CUST-HARD-' . $ct2RunId,
            'source_system' => 'crm',
        ]
    );
    ct2AssertStatus(200, $ct2VoucherSave, 'Marketing voucher save did not complete', $ct2Prefix);
    ct2AssertContains($ct2VoucherSave['body'], 'Voucher saved successfully.', 'Marketing voucher success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2VoucherSave['body'], $ct2VoucherCode, 'Marketing voucher row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2VoucherAuditBefore + 1), ct2Probe('audit-count', 'marketing.voucher_create'), 'Marketing voucher audit log did not increment', $ct2Prefix);
    $ct2VoucherId = ct2Probe('voucher-id', $ct2VoucherCode);

    $ct2AffiliateAuditBefore = (int) ct2Probe('audit-count', 'marketing.affiliate_update');
    $ct2AffiliateSave = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=saveAffiliate',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_affiliate_id' => $ct2AffiliateId,
            'affiliate_code' => 'AFF-CT2-001',
            'affiliate_name' => $ct2AffiliateName,
            'contact_name' => 'Cris Villanueva',
            'email' => 'cris@biyahenetwork.example.com',
            'phone' => '+63-917-400-0001',
            'affiliate_status' => 'active',
            'commission_rate' => '8.50',
            'payout_status' => 'ready',
            'referral_code' => 'BIYAHE-QA',
            'external_partner_id' => 'PARTNER-7701',
            'source_system' => 'partner_portal',
        ]
    );
    ct2AssertStatus(200, $ct2AffiliateSave, 'Marketing affiliate save did not complete', $ct2Prefix);
    ct2AssertContains($ct2AffiliateSave['body'], 'Affiliate profile saved successfully.', 'Marketing affiliate success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2AffiliateSave['body'], $ct2AffiliateName, 'Marketing affiliate row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2AffiliateAuditBefore + 1), ct2Probe('audit-count', 'marketing.affiliate_update'), 'Marketing affiliate audit log did not increment', $ct2Prefix);

    $ct2ReferralAuditBefore = (int) ct2Probe('audit-count', 'marketing.referral_create');
    $ct2ReferralSave = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=saveReferral',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_affiliate_id' => $ct2AffiliateId,
            'ct2_campaign_id' => $ct2CampaignId,
            'referral_code' => $ct2ReferralCode,
            'click_date' => $ct2NowLocal,
            'landing_page' => '/ct2-hardening/' . $ct2RunId,
            'external_customer_id' => 'CT1-CUST-HARD-' . $ct2RunId,
            'external_booking_id' => $ct2RedemptionBookingId,
            'attribution_status' => 'booked',
            'source_system' => 'web',
        ]
    );
    ct2AssertStatus(200, $ct2ReferralSave, 'Marketing referral save did not complete', $ct2Prefix);
    ct2AssertContains($ct2ReferralSave['body'], 'Referral click recorded.', 'Marketing referral success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2ReferralSave['body'], $ct2RedemptionBookingId, 'Marketing referral row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2ReferralAuditBefore + 1), ct2Probe('audit-count', 'marketing.referral_create'), 'Marketing referral audit log did not increment', $ct2Prefix);

    $ct2RedemptionAuditBefore = (int) ct2Probe('audit-count', 'marketing.redemption_create');
    $ct2RedemptionSave = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=saveRedemption',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_campaign_id' => $ct2CampaignId,
            'ct2_promotion_id' => $ct2PromotionId,
            'ct2_voucher_id' => $ct2VoucherId,
            'redemption_date' => $ct2NowLocal,
            'external_customer_id' => 'CT1-CUST-HARD-' . $ct2RunId,
            'external_booking_id' => $ct2RedemptionBookingId,
            'redeemed_amount' => '1800.00',
            'redemption_status' => 'redeemed',
            'source_system' => 'ct1',
        ]
    );
    ct2AssertStatus(200, $ct2RedemptionSave, 'Marketing redemption save did not complete', $ct2Prefix);
    ct2AssertContains($ct2RedemptionSave['body'], 'Redemption recorded successfully.', 'Marketing redemption success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2RedemptionSave['body'], $ct2VoucherCode, 'Marketing redemption row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2RedemptionAuditBefore + 1), ct2Probe('audit-count', 'marketing.redemption_create'), 'Marketing redemption audit log did not increment', $ct2Prefix);

    $ct2MetricAuditBefore = (int) ct2Probe('audit-count', 'marketing.metric_create');
    $ct2MetricSave = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=saveMetric',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_campaign_id' => $ct2CampaignId,
            'report_date' => $ct2Today,
            'impressions_count' => '10000',
            'click_count' => '500',
            'lead_count' => '60',
            'conversion_count' => '12',
            'attributed_revenue' => '125000.00',
            'positive_reviews' => '11',
            'neutral_reviews' => '2',
            'negative_reviews' => '1',
            'external_review_batch_id' => $ct2ReviewBatchId,
            'source_system' => 'analytics',
        ]
    );
    ct2AssertStatus(200, $ct2MetricSave, 'Marketing metric save did not complete', $ct2Prefix);
    ct2AssertContains($ct2MetricSave['body'], 'Campaign metrics saved.', 'Marketing metric success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2MetricSave['body'], $ct2Today, 'Marketing metric date was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2MetricAuditBefore + 1), ct2Probe('audit-count', 'marketing.metric_create'), 'Marketing metric audit log did not increment', $ct2Prefix);

    $ct2MarketingNoteAuditBefore = (int) ct2Probe('audit-count', 'marketing.note_create');
    $ct2MarketingNoteSave = $ct2Post(
        $ct2BaseUrl . '?module=marketing&action=saveNote',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_campaign_id' => $ct2CampaignId,
            'ct2_affiliate_id' => $ct2AffiliateId,
            'note_type' => 'performance',
            'note_title' => $ct2MarketingNoteTitle,
            'note_body' => 'Marketing regression note ' . $ct2RunId,
            'next_action_date' => $ct2NextWeek,
        ]
    );
    ct2AssertStatus(200, $ct2MarketingNoteSave, 'Marketing note save did not complete', $ct2Prefix);
    ct2AssertContains($ct2MarketingNoteSave['body'], 'Marketing note recorded.', 'Marketing note success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2MarketingNoteSave['body'], $ct2MarketingNoteTitle, 'Marketing note row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2MarketingNoteAuditBefore + 1), ct2Probe('audit-count', 'marketing.note_create'), 'Marketing note audit log did not increment', $ct2Prefix);

    ct2Log($ct2Prefix, 'Running visa checklist and auxiliary workflow coverage.');
    $ct2VisaPage = $ct2Get($ct2BaseUrl . '?module=visa&action=index');
    ct2AssertStatus(200, $ct2VisaPage, 'Visa page did not load', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2VisaPage['body']);

    $ct2VisaAuditBefore = (int) ct2Probe('audit-count', 'visa.document_checklist_update', 'visa_application', $ct2VisaApplicationId);
    $ct2ChecklistStatusBefore = ct2Probe('checklist-status', 'VISA-APP-001', 'Passport bio page');
    $ct2VisaInvalid = $ct2Post(
        $ct2BaseUrl . '?module=visa&action=saveDocumentChecklist',
        [
            'ct2_csrf_token' => 'invalid-ct2-token',
            'ct2_visa_application_id' => $ct2VisaApplicationId,
            'ct2_application_checklist_id' => $ct2ChecklistId,
            'checklist_status' => 'rejected',
            'verification_notes' => 'Invalid token should not persist',
        ]
    );
    ct2AssertStatus(200, $ct2VisaInvalid, 'Invalid-CSRF visa checklist post did not complete', $ct2Prefix);
    ct2AssertContains($ct2VisaInvalid['body'], 'Invalid request token.', 'Visa invalid-CSRF flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) $ct2VisaAuditBefore, ct2Probe('audit-count', 'visa.document_checklist_update', 'visa_application', $ct2VisaApplicationId), 'Visa invalid-CSRF request wrote an audit log', $ct2Prefix);
    ct2AssertEquals($ct2ChecklistStatusBefore, ct2Probe('checklist-status', 'VISA-APP-001', 'Passport bio page'), 'Visa invalid-CSRF request changed checklist status', $ct2Prefix);

    $ct2VisaSave = ct2HttpMultipartRequest(
        'POST',
        $ct2BaseUrl . '?module=visa&action=saveDocumentChecklist',
        $ct2Session,
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_visa_application_id' => $ct2VisaApplicationId,
            'ct2_application_checklist_id' => $ct2ChecklistId,
            'checklist_status' => 'verified',
            'verification_notes' => 'Hardening checklist upload ' . $ct2RunId,
        ],
        [
            'ct2_document_file' => [
                'path' => $ct2UploadPath,
                'mime_type' => 'image/png',
                'file_name' => basename($ct2UploadPath),
            ],
        ]
    );
    ct2AssertStatus(200, $ct2VisaSave, 'Visa upload did not complete', $ct2Prefix);
    ct2AssertContains($ct2VisaSave['body'], 'Document and checklist status updated.', 'Visa upload success flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2VisaAuditBefore + 1), ct2Probe('audit-count', 'visa.document_checklist_update', 'visa_application', $ct2VisaApplicationId), 'Visa checklist audit log did not increment', $ct2Prefix);
    ct2AssertEquals('verified', ct2Probe('checklist-status', 'VISA-APP-001', 'Passport bio page'), 'Visa checklist status did not update to verified', $ct2Prefix);
    $ct2StoredDocumentPath = ct2Probe('latest-document-path', 'VISA-APP-001');
    if (!is_file(ct2AppRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ct2StoredDocumentPath))) {
        ct2Fail($ct2Prefix, 'Stored visa document does not exist at ' . $ct2StoredDocumentPath);
    }

    $ct2VisaPaymentAuditBefore = (int) ct2Probe('audit-count', 'visa.payment_create');
    $ct2VisaPaymentSave = $ct2Post(
        $ct2BaseUrl . '?module=visa&action=savePayment',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_visa_application_id' => $ct2VisaApplicationId,
            'payment_reference' => $ct2PaymentReference,
            'external_payment_id' => 'FIN-HARD-' . $ct2RunId,
            'amount' => '3500.00',
            'currency' => 'PHP',
            'payment_method' => 'Manual',
            'payment_status' => 'completed',
            'paid_at' => $ct2NowLocal,
            'source_system' => 'cashier',
        ]
    );
    ct2AssertStatus(200, $ct2VisaPaymentSave, 'Visa payment save did not complete', $ct2Prefix);
    ct2AssertContains($ct2VisaPaymentSave['body'], 'Visa payment recorded.', 'Visa payment success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2VisaPaymentSave['body'], $ct2PaymentReference, 'Visa payment row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2VisaPaymentAuditBefore + 1), ct2Probe('audit-count', 'visa.payment_create'), 'Visa payment audit log did not increment', $ct2Prefix);

    $ct2VisaNotificationAuditBefore = (int) ct2Probe('audit-count', 'visa.notification_create');
    $ct2VisaNotificationSave = $ct2Post(
        $ct2BaseUrl . '?module=visa&action=saveNotification',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_visa_application_id' => $ct2VisaApplicationId,
            'notification_channel' => 'email',
            'recipient_reference' => $ct2NotificationRecipient,
            'notification_subject' => 'CT2 Hardening Notification ' . $ct2RunId,
            'notification_message' => 'Visa regression notification ' . $ct2RunId,
            'delivery_status' => 'sent',
        ]
    );
    ct2AssertStatus(200, $ct2VisaNotificationSave, 'Visa notification save did not complete', $ct2Prefix);
    ct2AssertContains($ct2VisaNotificationSave['body'], 'Notification log saved.', 'Visa notification success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2VisaNotificationSave['body'], $ct2NotificationRecipient, 'Visa notification row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2VisaNotificationAuditBefore + 1), ct2Probe('audit-count', 'visa.notification_create'), 'Visa notification audit log did not increment', $ct2Prefix);

    $ct2VisaNoteAuditBefore = (int) ct2Probe('audit-count', 'visa.note_create');
    $ct2VisaNoteSave = $ct2Post(
        $ct2BaseUrl . '?module=visa&action=saveNote',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_visa_application_id' => $ct2VisaApplicationId,
            'note_type' => 'review',
            'note_body' => 'CT2 hardening visa note ' . $ct2RunId,
            'next_action_date' => $ct2VisaNoteNextAction,
        ]
    );
    ct2AssertStatus(200, $ct2VisaNoteSave, 'Visa note save did not complete', $ct2Prefix);
    ct2AssertContains($ct2VisaNoteSave['body'], 'Visa case note recorded.', 'Visa note success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2VisaNoteSave['body'], $ct2VisaNoteNextAction, 'Visa note row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2VisaNoteAuditBefore + 1), ct2Probe('audit-count', 'visa.note_create'), 'Visa note audit log did not increment', $ct2Prefix);

    ct2Log($ct2Prefix, 'Running financial workflow coverage.');
    $ct2FinancialPage = $ct2Get($ct2BaseUrl . '?module=financial&action=index&ct2_report_run_id=' . rawurlencode($ct2ReportRunId) . '&source_module=suppliers&ct2_financial_report_id=' . rawurlencode($ct2FinancialReportId));
    ct2AssertStatus(200, $ct2FinancialPage, 'Financial page did not load', $ct2Prefix);
    $ct2Csrf = ct2ExtractCsrf($ct2FinancialPage['body']);

    $ct2FinancialRunAuditBefore = (int) ct2Probe('audit-count', 'financial.run_generate');
    $ct2FinancialInvalid = $ct2Post(
        $ct2BaseUrl . '?module=financial&action=runReport',
        [
            'ct2_csrf_token' => 'invalid-ct2-token',
            'ct2_financial_report_id' => $ct2FinancialReportId,
            'run_label' => $ct2RunLabel,
            'date_from' => $ct2Today,
            'date_to' => $ct2ThirtyDays,
            'module_key' => 'all',
            'source_system' => 'ct2',
        ]
    );
    ct2AssertStatus(200, $ct2FinancialInvalid, 'Invalid-CSRF financial run post did not complete', $ct2Prefix);
    ct2AssertContains($ct2FinancialInvalid['body'], 'Invalid request token.', 'Financial invalid-CSRF flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) $ct2FinancialRunAuditBefore, ct2Probe('audit-count', 'financial.run_generate'), 'Financial invalid-CSRF request wrote an audit log', $ct2Prefix);

    $ct2FinancialFilterAuditBefore = (int) ct2Probe('audit-count', 'financial.filter_create');
    $ct2FinancialFilterSave = $ct2Post(
        $ct2BaseUrl . '?module=financial&action=saveFilter',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_financial_report_id' => $ct2FinancialReportId,
            'filter_key' => $ct2FilterKey,
            'filter_label' => $ct2FilterLabel,
            'filter_type' => 'text',
            'default_value' => '',
            'sort_order' => '99',
        ]
    );
    ct2AssertStatus(200, $ct2FinancialFilterSave, 'Financial filter save did not complete', $ct2Prefix);
    ct2AssertContains($ct2FinancialFilterSave['body'], 'Report filter saved successfully.', 'Financial filter success flash was not rendered', $ct2Prefix);
    ct2AssertContains($ct2FinancialFilterSave['body'], $ct2FilterKey, 'Financial filter row was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2FinancialFilterAuditBefore + 1), ct2Probe('audit-count', 'financial.filter_create'), 'Financial filter audit log did not increment', $ct2Prefix);

    $ct2FinancialRunSave = $ct2Post(
        $ct2BaseUrl . '?module=financial&action=runReport',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_financial_report_id' => $ct2FinancialReportId,
            'run_label' => $ct2RunLabel,
            'date_from' => $ct2Today,
            'date_to' => $ct2ThirtyDays,
            'module_key' => 'all',
            'source_system' => 'ct2',
        ]
    );
    ct2AssertStatus(200, $ct2FinancialRunSave, 'Financial run generation did not complete', $ct2Prefix);
    ct2AssertContains($ct2FinancialRunSave['body'], 'Financial report run generated', 'Financial run success flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2FinancialRunAuditBefore + 1), ct2Probe('audit-count', 'financial.run_generate'), 'Financial run audit log did not increment', $ct2Prefix);
    $ct2HardeningRunId = ct2Probe('report-run-id', $ct2RunLabel);

    $ct2FinancialFlagAuditBefore = (int) ct2Probe('audit-count', 'financial.flag_update', 'reconciliation_flag', $ct2FlagId);
    $ct2FinancialFlagSave = $ct2Post(
        $ct2BaseUrl . '?module=financial&action=resolveFlag',
        [
            'ct2_csrf_token' => $ct2Csrf,
            'ct2_reconciliation_flag_id' => $ct2FlagId,
            'flag_status' => 'resolved',
            'resolution_notes' => 'Hardening reconciliation update ' . $ct2RunId,
            'ct2_report_run_id' => $ct2ReportRunId,
            'source_module' => 'suppliers',
            'flag_filter_status' => 'open',
            'ct2_financial_report_id' => $ct2FinancialReportId,
        ]
    );
    ct2AssertStatus(200, $ct2FinancialFlagSave, 'Financial flag update did not complete', $ct2Prefix);
    ct2AssertContains($ct2FinancialFlagSave['body'], 'Reconciliation flag updated successfully.', 'Financial flag success flash was not rendered', $ct2Prefix);
    ct2AssertEquals((string) ($ct2FinancialFlagAuditBefore + 1), ct2Probe('audit-count', 'financial.flag_update', 'reconciliation_flag', $ct2FlagId), 'Financial flag audit log did not increment', $ct2Prefix);
    ct2AssertEquals('resolved', ct2Probe('flag-field', 'suppliers', 'SUP-CT2-002', 'flag_status'), 'Financial flag status did not update to resolved', $ct2Prefix);

    $ct2Export = $ct2Get($ct2BaseUrl . '?module=financial&action=exportCsv&ct2_report_run_id=' . rawurlencode($ct2ReportRunId) . '&source_module=suppliers');
    ct2AssertStatus(200, $ct2Export, 'Financial export did not return 200', $ct2Prefix);
    ct2AssertHeaderContains($ct2Export, 'Content-Type', 'text/csv; charset=utf-8', 'Financial export did not return CSV content type', $ct2Prefix);
    ct2AssertContains($ct2Export['body'], 'report_run_id,report_name,run_label,source_module', 'Financial export header row is missing', $ct2Prefix);
    ct2AssertContains($ct2Export['body'], 'SUP-CT2-002', 'Financial export did not include the seeded supplier flag reference', $ct2Prefix);

    ct2Log($ct2Prefix, 'Signing out and proving stale-session writes are rejected.');
    $ct2LogoutSource = $ct2Get($ct2BaseUrl . '?module=dashboard&action=index');
    ct2AssertStatus(200, $ct2LogoutSource, 'Dashboard page did not load before logout', $ct2Prefix);
    $ct2StaleToken = ct2ExtractCsrf($ct2LogoutSource['body']);
    $ct2Logout = $ct2Post(
        $ct2BaseUrl . '?module=auth&action=logout',
        [
            'ct2_csrf_token' => $ct2StaleToken,
        ],
        false
    );
    ct2AssertStatus(302, $ct2Logout, 'Logout did not complete', $ct2Prefix);
    ct2AssertHeaderContains($ct2Logout, 'Location', 'ct2_index.php?module=auth&action=login', 'Logout did not redirect to the login page', $ct2Prefix);

    $ct2StaleAgentAuditBefore = (int) ct2Probe('audit-count', 'agents.update', 'agent', $ct2AgentId);
    $ct2StaleAgent = $ct2Post(
        $ct2BaseUrl . '?module=agents&action=save',
        [
            'ct2_csrf_token' => $ct2StaleToken,
            'ct2_agent_id' => $ct2AgentId,
            'agent_code' => 'AGT-CT2-002',
            'agency_name' => 'Island Connect Tours',
            'contact_person' => 'Ramon Aquino',
            'email' => 'ramon@islandconnect.example.com',
            'phone' => '+63-917-200-0002',
            'region' => 'Visayas',
            'commission_rate' => '10.00',
            'support_level' => 'priority',
            'approval_status' => 'approved',
            'active_status' => 'active',
            'external_booking_id' => 'CT1-BKG-1002',
            'external_customer_id' => 'CT1-CUST-8802',
            'external_payment_id' => 'FIN-PAY-4402',
            'source_system' => 'ct1',
        ],
        false
    );
    ct2AssertStatus(302, $ct2StaleAgent, 'Stale-session agent post did not redirect', $ct2Prefix);
    ct2AssertHeaderContains($ct2StaleAgent, 'Location', 'ct2_index.php?module=auth&action=login', 'Stale-session agent post did not redirect to login', $ct2Prefix);
    ct2AssertEquals((string) $ct2StaleAgentAuditBefore, ct2Probe('audit-count', 'agents.update', 'agent', $ct2AgentId), 'Stale-session agent post wrote an audit log', $ct2Prefix);

    $ct2StaleFinancialAuditBefore = (int) ct2Probe('audit-count', 'financial.filter_create');
    $ct2StaleFinancial = $ct2Post(
        $ct2BaseUrl . '?module=financial&action=saveFilter',
        [
            'ct2_csrf_token' => $ct2StaleToken,
            'ct2_financial_report_id' => $ct2FinancialReportId,
            'filter_key' => 'stale_filter_' . $ct2RunId,
            'filter_label' => 'Stale Filter ' . $ct2RunId,
            'filter_type' => 'text',
            'default_value' => '',
            'sort_order' => '100',
        ],
        false
    );
    ct2AssertStatus(302, $ct2StaleFinancial, 'Stale-session financial post did not redirect', $ct2Prefix);
    ct2AssertHeaderContains($ct2StaleFinancial, 'Location', 'ct2_index.php?module=auth&action=login', 'Stale-session financial post did not redirect to login', $ct2Prefix);
    ct2AssertEquals((string) $ct2StaleFinancialAuditBefore, ct2Probe('audit-count', 'financial.filter_create'), 'Stale-session financial post wrote an audit log', $ct2Prefix);

    ct2Log($ct2Prefix, 'Verifying protected API failures stay JSON-shaped.');
    $ct2ApiAnon = ct2HttpRequest('GET', $ct2ApiBaseUrl . '/ct2_agents.php');
    ct2AssertStatus(403, $ct2ApiAnon, 'Anonymous agents API request did not return 403', $ct2Prefix);
    ct2AssertHeaderContains($ct2ApiAnon, 'Content-Type', 'application/json; charset=utf-8', 'Anonymous agents API request did not stay JSON', $ct2Prefix);
    ct2AssertContains($ct2ApiAnon['body'], '"success":false', 'Anonymous agents API response did not return the CT2 JSON envelope', $ct2Prefix);
    ct2AssertContains($ct2ApiAnon['body'], '"error":"Forbidden."', 'Anonymous agents API response did not return the expected forbidden error', $ct2Prefix);

    $ct2ApiMethod = ct2HttpRequest('GET', $ct2ApiBaseUrl . '/ct2_auth_login.php');
    ct2AssertStatus(405, $ct2ApiMethod, 'Wrong-method auth API request did not return 405', $ct2Prefix);
    ct2AssertHeaderContains($ct2ApiMethod, 'Content-Type', 'application/json; charset=utf-8', 'Wrong-method auth API request did not stay JSON', $ct2Prefix);
    ct2AssertContains($ct2ApiMethod['body'], '"error":"Method not allowed."', 'Wrong-method auth API response did not return the expected error', $ct2Prefix);

    ct2Log($ct2Prefix, 'CT2 runtime hardening checks passed.');
} finally {
    if (is_array($ct2Server)) {
        ct2StopProcess($ct2Server['proc']);
    }
    ct2RemoveDir($ct2TempDir);
}
