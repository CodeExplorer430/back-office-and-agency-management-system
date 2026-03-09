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
        $ct2VisaApplicationModel = new CT2_VisaApplicationModel();
        $ct2ApprovalModel = new CT2_ApprovalModel();
        $ct2AssignmentModel = new CT2_AssignmentModel();
        $ct2SupplierContractModel = new CT2_SupplierContractModel();
        $ct2DispatchModel = new CT2_DispatchModel();

        $this->ct2Render(
            'dashboard/ct2_home',
            [
                'ct2AgentSummary' => $ct2AgentModel->getSummaryCounts(),
                'ct2StaffSummary' => $ct2StaffModel->getSummaryCounts(),
                'ct2SupplierSummary' => $ct2SupplierModel->getSummaryCounts(),
                'ct2ResourceSummary' => $ct2ResourceModel->getSummaryCounts(),
                'ct2CampaignSummary' => $ct2CampaignModel->getSummaryCounts(),
                'ct2VisaSummary' => $ct2VisaApplicationModel->getSummaryCounts(),
                'ct2Approvals' => array_slice($ct2ApprovalModel->getAll(), 0, 5),
                'ct2Assignments' => array_slice($ct2AssignmentModel->getAll(), 0, 5),
                'ct2SupplierContracts' => array_slice($ct2SupplierContractModel->getAll(), 0, 5),
                'ct2DispatchOrders' => array_slice($ct2DispatchModel->getDispatchOrders(), 0, 5),
            ]
        );
    }
}
