<?php

declare(strict_types=1);

final class CT2_StaffController extends CT2_BaseController
{
    private CT2_StaffModel $ct2StaffModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2StaffModel = new CT2_StaffModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function index(): void
    {
        ct2_require_permission('staff.view');

        $ct2Search = trim((string) ($_GET['search'] ?? ''));
        $ct2EditId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : null;

        $this->ct2Render(
            'staff/ct2_index',
            [
                'ct2StaffMembers' => $this->ct2StaffModel->getAll($ct2Search !== '' ? $ct2Search : null),
                'ct2StaffForEdit' => $ct2EditId !== null ? $this->ct2StaffModel->findById($ct2EditId) : null,
                'ct2Search' => $ct2Search,
            ]
        );
    }

    public function save(): void
    {
        ct2_require_permission('staff.manage');

        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            ct2_flash('error', 'Invalid request token for staff save.');
            $this->ct2Redirect(['module' => 'staff', 'action' => 'index']);
        }

        $ct2Payload = $this->ct2ValidatePayload($_POST);
        $ct2StaffId = isset($_POST['ct2_staff_id']) ? (int) $_POST['ct2_staff_id'] : 0;
        $ct2UserId = (int) ct2_current_user_id();

        if ($ct2StaffId > 0) {
            $this->ct2StaffModel->update($ct2StaffId, $ct2Payload, $ct2UserId);
            $ct2Action = 'staff.update';
        } else {
            $ct2StaffId = $this->ct2StaffModel->create($ct2Payload, $ct2UserId);
            $ct2Action = 'staff.create';
        }

        $this->ct2AuditLogModel->recordAudit($ct2UserId, 'staff', $ct2StaffId, $ct2Action, $ct2Payload);

        ct2_flash('success', 'Staff profile saved successfully.');
        $this->ct2Redirect(['module' => 'staff', 'action' => 'index']);
    }

    private function ct2ValidatePayload(array $ct2Input): array
    {
        $ct2Payload = [
            'staff_code' => trim((string) ($ct2Input['staff_code'] ?? '')),
            'full_name' => trim((string) ($ct2Input['full_name'] ?? '')),
            'email' => trim((string) ($ct2Input['email'] ?? '')),
            'phone' => trim((string) ($ct2Input['phone'] ?? '')),
            'department' => trim((string) ($ct2Input['department'] ?? '')),
            'position_title' => trim((string) ($ct2Input['position_title'] ?? '')),
            'employment_status' => (string) ($ct2Input['employment_status'] ?? 'active'),
            'availability_status' => (string) ($ct2Input['availability_status'] ?? 'available'),
            'team_name' => trim((string) ($ct2Input['team_name'] ?? '')),
            'notes' => trim((string) ($ct2Input['notes'] ?? '')),
        ];

        foreach (['staff_code', 'full_name', 'email', 'phone', 'department', 'position_title', 'team_name'] as $ct2RequiredField) {
            if ($ct2Payload[$ct2RequiredField] === '') {
                throw new InvalidArgumentException('Missing required staff field: ' . $ct2RequiredField);
            }
        }

        return $ct2Payload;
    }
}
