<?php

declare(strict_types=1);

final class CT2_DashboardController extends CT2_BaseController
{
    public function index(): void
    {
        ct2_require_permission('dashboard.view');

        $ct2AgentModel = new CT2_AgentModel();
        $ct2StaffModel = new CT2_StaffModel();
        $ct2ApprovalModel = new CT2_ApprovalModel();
        $ct2AssignmentModel = new CT2_AssignmentModel();

        $this->ct2Render(
            'dashboard/ct2_home',
            [
                'ct2AgentSummary' => $ct2AgentModel->getSummaryCounts(),
                'ct2StaffSummary' => $ct2StaffModel->getSummaryCounts(),
                'ct2Approvals' => array_slice($ct2ApprovalModel->getAll(), 0, 5),
                'ct2Assignments' => array_slice($ct2AssignmentModel->getAll(), 0, 5),
            ]
        );
    }
}
