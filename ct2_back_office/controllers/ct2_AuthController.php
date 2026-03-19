<?php

declare(strict_types=1);

final class CT2_AuthController extends CT2_BaseController
{
    private CT2_UserModel $ct2UserModel;
    private CT2_AuditLogModel $ct2AuditLogModel;

    public function __construct()
    {
        $this->ct2UserModel = new CT2_UserModel();
        $this->ct2AuditLogModel = new CT2_AuditLogModel();
    }

    public function login(): void
    {
        if (ct2_current_user() !== null) {
            $this->ct2Redirect(['module' => 'dashboard', 'action' => 'index']);
        }

        if (ct2_is_post()) {
            if (!ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
                ct2_flash('error', 'Your session expired. Please try again.');
                $this->ct2Redirect(['module' => 'auth', 'action' => 'landing']);
            }

            $ct2Username = trim((string) ($_POST['username'] ?? ''));
            $ct2Password = (string) ($_POST['password'] ?? '');
            $ct2User = $this->ct2UserModel->findByUsername($ct2Username);

            if (
                $ct2User === null
                || (int) $ct2User['is_active'] !== 1
                || !password_verify($ct2Password, (string) $ct2User['password_hash'])
            ) {
                ct2_flash('error', 'Invalid username or password.');
                $this->ct2Redirect(['module' => 'auth', 'action' => 'landing']);
            }

            $this->ct2UserModel->updateLastLogin((int) $ct2User['ct2_user_id']);
            $this->ct2UserModel->recordSession((int) $ct2User['ct2_user_id'], session_id());
            $ct2HydratedUser = $this->ct2UserModel->getHydratedUser((int) $ct2User['ct2_user_id']);

            if ($ct2HydratedUser === null) {
                ct2_flash('error', 'Unable to initialize your CT2 session.');
                $this->ct2Redirect(['module' => 'auth', 'action' => 'landing']);
            }

            ct2_store_user_session($ct2HydratedUser);
            $this->ct2AuditLogModel->recordAudit(
                (int) $ct2HydratedUser['ct2_user_id'],
                'auth',
                null,
                'auth.login',
                ['username' => $ct2HydratedUser['username']]
            );

            ct2_flash('success', 'Signed in successfully.');
            $this->ct2Redirect(['module' => 'dashboard', 'action' => 'index']);
        }

        $this->ct2Render('auth/ct2_login');
    }

    public function landing(): void
    {
        if (ct2_current_user() !== null) {
            $this->ct2Redirect(['module' => 'dashboard', 'action' => 'index']);
        }

        $this->ct2Render('auth/ct2_login');
    }

    public function logout(): void
    {
        ct2_require_auth();

        if (!ct2_is_post() || !ct2_verify_csrf($_POST['ct2_csrf_token'] ?? null)) {
            http_response_code(405);
            echo 'Method Not Allowed';
            return;
        }

        $ct2User = ct2_current_user();
        if ($ct2User !== null) {
            $this->ct2UserModel->closeSession(session_id());
            $this->ct2AuditLogModel->recordAudit(
                (int) $ct2User['ct2_user_id'],
                'auth',
                null,
                'auth.logout',
                ['username' => $ct2User['username']]
            );
        }

        ct2_clear_user_session();
        session_regenerate_id(true);
        ct2_flash('success', 'You have been signed out.');
        $this->ct2Redirect(['module' => 'auth', 'action' => 'login']);
    }
}
