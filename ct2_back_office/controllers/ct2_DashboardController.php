<?php

declare(strict_types=1);

final class CT2_DashboardController extends CT2_BaseController
{
    public function index(): void
    {
        ct2_require_permission('dashboard.view');

        $ct2AgentModel = new CT2_AgentModel();
        $ct2StaffModel = new CT2_StaffModel();
        $ct2SupplierModel = new CT2_SupplierModel();
        $ct2ResourceModel = new CT2_ResourceModel();
        $ct2CampaignModel = new CT2_MarketingCampaignModel();
        $ct2FinancialAnalyticsModel = new CT2_FinancialAnalyticsModel();
        $ct2VisaApplicationModel = new CT2_VisaApplicationModel();
        $ct2ApprovalModel = new CT2_ApprovalModel();
        $ct2AssignmentModel = new CT2_AssignmentModel();
        $ct2ResourceAllocationModel = new CT2_ResourceAllocationModel();
        $ct2DispatchModel = new CT2_DispatchModel();

        $ct2AgentSummary = $ct2AgentModel->getSummaryCounts();
        $ct2StaffSummary = $ct2StaffModel->getSummaryCounts();
        $ct2SupplierSummary = $ct2SupplierModel->getSummaryCounts();
        $ct2ResourceSummary = $ct2ResourceModel->getSummaryCounts();
        $ct2CampaignSummary = $ct2CampaignModel->getSummaryCounts();
        $ct2FinancialSummary = $ct2FinancialAnalyticsModel->getSummary();
        $ct2VisaSummary = $ct2VisaApplicationModel->getSummaryCounts();

        $ct2Agents = $ct2AgentModel->getAll();
        $ct2Suppliers = $ct2SupplierModel->getAll();
        $ct2Allocations = $ct2ResourceAllocationModel->getAll();
        $ct2Approvals = $ct2ApprovalModel->getAll();
        $ct2Assignments = $ct2AssignmentModel->getAll();
        $ct2DispatchOrders = $ct2DispatchModel->getDispatchOrders();
        $ct2VisaApplications = $ct2VisaApplicationModel->getAll();

        $ct2DashboardSummary = $this->buildDashboardSummary(
            $ct2Agents,
            $ct2Allocations,
            $ct2Approvals,
            $ct2Assignments,
            $ct2DispatchOrders,
            $ct2VisaApplications,
            $ct2FinancialSummary
        );

        $this->ct2Render(
            'dashboard/ct2_home',
            [
                'ct2AgentSummary' => $ct2AgentSummary,
                'ct2StaffSummary' => $ct2StaffSummary,
                'ct2SupplierSummary' => $ct2SupplierSummary,
                'ct2ResourceSummary' => $ct2ResourceSummary,
                'ct2CampaignSummary' => $ct2CampaignSummary,
                'ct2FinancialSummary' => $ct2FinancialSummary,
                'ct2VisaSummary' => $ct2VisaSummary,
                'ct2DashboardSummary' => $ct2DashboardSummary,
                'ct2AnalyticsCards' => $this->buildAnalyticsCards(
                    $ct2DashboardSummary,
                    $ct2AgentSummary,
                    $ct2SupplierSummary,
                    $ct2ResourceSummary,
                    $ct2FinancialSummary,
                    $ct2VisaSummary
                ),
                'ct2AnalyticsBars' => $this->buildAnalyticsBars(
                    $ct2DashboardSummary,
                    $ct2SupplierSummary,
                    $ct2FinancialSummary,
                    $ct2VisaSummary
                ),
                'ct2SystemFlowChart' => $this->buildSystemFlowChart(
                    $ct2DashboardSummary,
                    $ct2FinancialSummary,
                    $ct2VisaSummary
                ),
                'ct2DepartmentGraph' => $this->buildDepartmentGraph(
                    $ct2AgentSummary,
                    $ct2StaffSummary,
                    $ct2SupplierSummary,
                    $ct2ResourceSummary,
                    $ct2CampaignSummary,
                    $ct2FinancialSummary,
                    $ct2VisaSummary
                ),
                'ct2RecentRecords' => $this->buildRecentRecords(
                    $ct2Approvals,
                    $ct2DispatchOrders,
                    $ct2VisaApplications
                ),
                'ct2QuickActions' => $this->buildQuickActions(),
                'ct2TopCampaigns' => array_slice($ct2CampaignModel->getTopCampaigns(), 0, 4),
                'ct2DispatchOrders' => array_slice($ct2DispatchOrders, 0, 5),
            ]
        );
    }

    private function buildDashboardSummary(
        array $ct2Agents,
        array $ct2Allocations,
        array $ct2Approvals,
        array $ct2Assignments,
        array $ct2DispatchOrders,
        array $ct2VisaApplications,
        array $ct2FinancialSummary
    ): array {
        $ct2UniqueBookings = [];
        $ct2PaymentRecords = 0;
        $ct2Ct1Records = 0;
        $ct2SoftBlockedAllocations = 0;
        $ct2PendingApprovals = 0;
        $ct2PendingVisaCases = 0;
        $ct2ActiveDispatches = 0;

        foreach ($ct2Agents as $ct2Agent) {
            $ct2BookingId = trim((string) ($ct2Agent['external_booking_id'] ?? ''));
            if ($ct2BookingId !== '') {
                $ct2UniqueBookings[$ct2BookingId] = true;
            }

            if (trim((string) ($ct2Agent['external_payment_id'] ?? '')) !== '') {
                $ct2PaymentRecords++;
            }

            if (strtolower((string) ($ct2Agent['source_system'] ?? '')) === 'ct1') {
                $ct2Ct1Records++;
            }
        }

        foreach ($ct2Allocations as $ct2Allocation) {
            $ct2BookingId = trim((string) ($ct2Allocation['external_booking_id'] ?? ''));
            if ($ct2BookingId !== '') {
                $ct2UniqueBookings[$ct2BookingId] = true;
                $ct2Ct1Records++;
            }

            if (($ct2Allocation['allocation_status'] ?? '') === 'soft_blocked') {
                $ct2SoftBlockedAllocations++;
            }
        }

        foreach ($ct2Approvals as $ct2Approval) {
            if (($ct2Approval['approval_status'] ?? '') === 'pending') {
                $ct2PendingApprovals++;
            }
        }

        foreach ($ct2DispatchOrders as $ct2DispatchOrder) {
            if (!in_array((string) ($ct2DispatchOrder['dispatch_status'] ?? ''), ['completed', 'cancelled'], true)) {
                $ct2ActiveDispatches++;
            }
        }

        foreach ($ct2VisaApplications as $ct2VisaApplication) {
            if (in_array((string) ($ct2VisaApplication['status'] ?? ''), ['submitted', 'document_review', 'escalated_review'], true)) {
                $ct2PendingVisaCases++;
            }
        }

        return [
            'incoming_bookings' => count($ct2UniqueBookings),
            'payment_records' => $ct2PaymentRecords,
            'ct1_records' => $ct2Ct1Records,
            'assignment_count' => count($ct2Assignments),
            'soft_blocked_allocations' => $ct2SoftBlockedAllocations,
            'dispatch_active' => $ct2ActiveDispatches,
            'pending_approvals' => $ct2PendingApprovals,
            'pending_visa_cases' => $ct2PendingVisaCases,
            'open_financial_flags' => (int) ($ct2FinancialSummary['open_flags'] ?? 0),
        ];
    }

    private function buildAnalyticsCards(
        array $ct2DashboardSummary,
        array $ct2AgentSummary,
        array $ct2SupplierSummary,
        array $ct2ResourceSummary,
        array $ct2FinancialSummary,
        array $ct2VisaSummary
    ): array {
        return [
            [
                'title' => 'CT1 Booking Intake',
                'value' => (int) $ct2DashboardSummary['incoming_bookings'],
                'meta' => 'Bookings routed into agent workload and resource planning',
                'detail' => (int) $ct2DashboardSummary['ct1_records'] . ' CT1-linked records in CT2',
                'link_params' => ['module' => 'agents', 'action' => 'index'],
            ],
            [
                'title' => 'Operational Finance',
                'value' => (int) ($ct2FinancialSummary['snapshot_count'] ?? 0),
                'meta' => 'Financial snapshots available for back-office review',
                'detail' => (int) ($ct2FinancialSummary['open_flags'] ?? 0) . ' open reconciliation flags',
                'link_params' => ['module' => 'financial', 'action' => 'index', 'flag_status' => 'open'],
            ],
            [
                'title' => 'Fulfillment Queue',
                'value' => (int) $ct2DashboardSummary['dispatch_active'],
                'meta' => 'Dispatch orders being planned or executed',
                'detail' => (int) $ct2DashboardSummary['soft_blocked_allocations'] . ' resource pressure alerts',
                'link_params' => ['module' => 'availability', 'action' => 'index'],
            ],
            [
                'title' => 'Compliance and Service',
                'value' => (int) ($ct2DashboardSummary['pending_approvals'] + $ct2VisaSummary['review_queue']),
                'meta' => 'Approvals and visa reviews waiting for action',
                'detail' => (int) $ct2AgentSummary['pending_agents'] . ' agent approvals and ' . (int) $ct2SupplierSummary['pending_suppliers'] . ' supplier approvals',
                'link_params' => ['module' => 'approvals', 'action' => 'index'],
            ],
            [
                'title' => 'Payment Handoffs',
                'value' => (int) $ct2DashboardSummary['payment_records'],
                'meta' => 'Payment-linked records ready for audit and settlement',
                'detail' => (int) ($ct2FinancialSummary['latest_run_id'] ?? 0) . ' latest report run',
                'link_params' => ['module' => 'financial', 'action' => 'index'],
            ],
            [
                'title' => 'Department Readiness',
                'value' => (int) ($ct2ResourceSummary['available_resources'] ?? 0),
                'meta' => 'Resources and teams currently available to absorb demand',
                'detail' => (int) ($ct2VisaSummary['upcoming_appointments'] ?? 0) . ' upcoming service appointments',
                'link_params' => ['module' => 'staff', 'action' => 'index'],
            ],
        ];
    }

    private function buildAnalyticsBars(
        array $ct2DashboardSummary,
        array $ct2SupplierSummary,
        array $ct2FinancialSummary,
        array $ct2VisaSummary
    ): array {
        return [
            [
                'label' => 'Booking intake',
                'value' => (int) $ct2DashboardSummary['incoming_bookings'],
                'meta' => 'CT1 bookings flowing into CT2',
                'link_params' => ['module' => 'agents', 'action' => 'index'],
            ],
            [
                'label' => 'Assignment workload',
                'value' => (int) $ct2DashboardSummary['assignment_count'],
                'meta' => 'Agent-to-staff allocations',
                'link_params' => ['module' => 'agents', 'action' => 'index'],
            ],
            [
                'label' => 'Dispatch planning',
                'value' => (int) $ct2DashboardSummary['dispatch_active'],
                'meta' => 'Tours waiting for or under dispatch',
                'link_params' => ['module' => 'availability', 'action' => 'index'],
            ],
            [
                'label' => 'Supplier onboarding',
                'value' => (int) ($ct2SupplierSummary['onboarding_suppliers'] ?? 0),
                'meta' => 'Partners still in review or blocked',
                'link_params' => ['module' => 'suppliers', 'action' => 'index'],
            ],
            [
                'label' => 'Financial flags',
                'value' => (int) ($ct2FinancialSummary['open_flags'] ?? 0),
                'meta' => 'Reconciliation issues to resolve',
                'link_params' => ['module' => 'financial', 'action' => 'index', 'flag_status' => 'open'],
            ],
            [
                'label' => 'Visa review queue',
                'value' => (int) ($ct2VisaSummary['review_queue'] ?? 0),
                'meta' => 'Cases needing document review',
                'link_params' => ['module' => 'visa', 'action' => 'index', 'status' => 'document_review'],
            ],
        ];
    }

    private function buildSystemFlowChart(
        array $ct2DashboardSummary,
        array $ct2FinancialSummary,
        array $ct2VisaSummary
    ): array {
        return [
            ['label' => 'CT1 intake', 'value' => (int) $ct2DashboardSummary['incoming_bookings']],
            ['label' => 'Logistics', 'value' => (int) ($ct2DashboardSummary['dispatch_active'] + $ct2DashboardSummary['soft_blocked_allocations'])],
            ['label' => 'Financials', 'value' => (int) (($ct2FinancialSummary['snapshot_count'] ?? 0) + ($ct2FinancialSummary['open_flags'] ?? 0))],
            ['label' => 'HR', 'value' => (int) $ct2DashboardSummary['assignment_count']],
            ['label' => 'Administrative', 'value' => (int) (($ct2DashboardSummary['pending_approvals'] ?? 0) + ($ct2VisaSummary['review_queue'] ?? 0))],
        ];
    }

    private function buildDepartmentGraph(
        array $ct2AgentSummary,
        array $ct2StaffSummary,
        array $ct2SupplierSummary,
        array $ct2ResourceSummary,
        array $ct2CampaignSummary,
        array $ct2FinancialSummary,
        array $ct2VisaSummary
    ): array {
        return [
            ['label' => 'Agents', 'value' => (int) ($ct2AgentSummary['active_agents'] ?? 0)],
            ['label' => 'Staff', 'value' => (int) ($ct2StaffSummary['available_staff'] ?? 0)],
            ['label' => 'Suppliers', 'value' => (int) ($ct2SupplierSummary['active_suppliers'] ?? 0)],
            ['label' => 'Resources', 'value' => (int) ($ct2ResourceSummary['available_resources'] ?? 0)],
            ['label' => 'Campaigns', 'value' => (int) ($ct2CampaignSummary['active_campaigns'] ?? 0)],
            ['label' => 'Finance', 'value' => (int) ($ct2FinancialSummary['snapshot_count'] ?? 0)],
            ['label' => 'Visa', 'value' => (int) ($ct2VisaSummary['completed_applications'] ?? 0)],
        ];
    }

    private function buildRecentRecords(array $ct2Approvals, array $ct2DispatchOrders, array $ct2VisaApplications): array
    {
        $ct2Records = [];

        foreach (array_slice($ct2Approvals, 0, 4) as $ct2Approval) {
            $ct2Records[] = [
                'timestamp' => strtotime((string) ($ct2Approval['requested_at'] ?? 'now')) ?: 0,
                'type' => 'Approval',
                'reference' => (string) $ct2Approval['subject_type'] . ' #' . (int) $ct2Approval['subject_id'],
                'status' => (string) $ct2Approval['approval_status'],
                'owner' => (string) ($ct2Approval['requested_by_name'] ?? 'System'),
                'module' => 'Approvals',
                'link_params' => ['module' => 'approvals', 'action' => 'index'],
            ];
        }

        foreach (array_slice($ct2DispatchOrders, 0, 4) as $ct2DispatchOrder) {
            $ct2Records[] = [
                'timestamp' => strtotime(trim((string) (($ct2DispatchOrder['dispatch_date'] ?? '') . ' ' . ($ct2DispatchOrder['dispatch_time'] ?? '')))) ?: 0,
                'type' => 'Dispatch',
                'reference' => (string) (($ct2DispatchOrder['external_booking_id'] ?? '') !== '' ? $ct2DispatchOrder['external_booking_id'] : $ct2DispatchOrder['plate_number']),
                'status' => (string) $ct2DispatchOrder['dispatch_status'],
                'owner' => (string) ($ct2DispatchOrder['full_name'] ?? $ct2DispatchOrder['plate_number']),
                'module' => 'Availability',
                'link_params' => ['module' => 'availability', 'action' => 'index'],
            ];
        }

        foreach (array_slice($ct2VisaApplications, 0, 4) as $ct2VisaApplication) {
            $ct2Records[] = [
                'timestamp' => strtotime((string) ($ct2VisaApplication['submission_date'] ?? $ct2VisaApplication['created_at'] ?? 'now')) ?: 0,
                'type' => 'Visa',
                'reference' => (string) ($ct2VisaApplication['application_reference'] ?? 'Visa case'),
                'status' => (string) ($ct2VisaApplication['status'] ?? 'draft'),
                'owner' => (string) ($ct2VisaApplication['country_name'] ?? $ct2VisaApplication['external_customer_id'] ?? 'Client'),
                'module' => 'Visa',
                'link_params' => ['module' => 'visa', 'action' => 'index'],
            ];
        }

        usort(
            $ct2Records,
            static fn (array $ct2Left, array $ct2Right): int => $ct2Right['timestamp'] <=> $ct2Left['timestamp']
        );

        return array_slice($ct2Records, 0, 8);
    }

    private function buildQuickActions(): array
    {
        return [
            [
                'title' => 'Review approvals',
                'copy' => 'Clear governance decisions blocking downstream work.',
                'link_params' => ['module' => 'approvals', 'action' => 'index'],
            ],
            [
                'title' => 'Plan dispatch',
                'copy' => 'Check booked tours, vehicles, and resource conflicts.',
                'link_params' => ['module' => 'availability', 'action' => 'index'],
            ],
            [
                'title' => 'Audit financials',
                'copy' => 'Inspect open flags and latest analytics runs.',
                'link_params' => ['module' => 'financial', 'action' => 'index', 'flag_status' => 'open'],
            ],
            [
                'title' => 'Process visa cases',
                'copy' => 'Move document-review cases toward release.',
                'link_params' => ['module' => 'visa', 'action' => 'index', 'status' => 'document_review'],
            ],
            [
                'title' => 'Update suppliers',
                'copy' => 'Handle partner onboarding and compliance readiness.',
                'link_params' => ['module' => 'suppliers', 'action' => 'index'],
            ],
            [
                'title' => 'Manage agents',
                'copy' => 'Review commissions, workload, and CT1-linked sales.',
                'link_params' => ['module' => 'agents', 'action' => 'index'],
            ],
        ];
    }
}
