<?php

declare(strict_types=1);

final class CT2_UserModel extends CT2_BaseModel
{
    public function findByUsername(string $ct2Username): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT ct2_user_id, username, email, password_hash, display_name, is_active
             FROM ct2_users
             WHERE username = :username
             LIMIT 1'
        );
        $ct2Statement->execute(['username' => $ct2Username]);
        $ct2User = $ct2Statement->fetch();

        return $ct2User !== false ? $ct2User : null;
    }

    public function getHydratedUser(int $ct2UserId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT ct2_user_id, username, email, display_name
             FROM ct2_users
             WHERE ct2_user_id = :ct2_user_id AND is_active = 1
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_user_id' => $ct2UserId]);
        $ct2User = $ct2Statement->fetch();

        if ($ct2User === false) {
            return null;
        }

        $ct2User['roles'] = $this->getRoles($ct2UserId);
        $ct2User['permissions'] = $this->getPermissions($ct2UserId);

        return $ct2User;
    }

    public function getRoles(int $ct2UserId): array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT r.role_key
             FROM ct2_roles AS r
             INNER JOIN ct2_user_roles AS ur ON ur.ct2_role_id = r.ct2_role_id
             WHERE ur.ct2_user_id = :ct2_user_id
             ORDER BY r.role_key ASC'
        );
        $ct2Statement->execute(['ct2_user_id' => $ct2UserId]);

        return array_column($ct2Statement->fetchAll(), 'role_key');
    }

    public function getPermissions(int $ct2UserId): array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT DISTINCT rp.permission_key
             FROM ct2_role_permissions AS rp
             INNER JOIN ct2_roles AS r ON r.ct2_role_id = rp.ct2_role_id
             INNER JOIN ct2_user_roles AS ur ON ur.ct2_role_id = r.ct2_role_id
             WHERE ur.ct2_user_id = :ct2_user_id
             ORDER BY rp.permission_key ASC'
        );
        $ct2Statement->execute(['ct2_user_id' => $ct2UserId]);

        return array_column($ct2Statement->fetchAll(), 'permission_key');
    }

    public function updateLastLogin(int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_users
             SET last_login_at = NOW()
             WHERE ct2_user_id = :ct2_user_id'
        );
        $ct2Statement->execute(['ct2_user_id' => $ct2UserId]);
    }

    public function recordSession(int $ct2UserId, string $ct2SessionIdentifier): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_session_logs (
                ct2_user_id, session_identifier, login_at, ip_address, user_agent
             ) VALUES (
                :ct2_user_id, :session_identifier, NOW(), :ip_address, :user_agent
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_user_id' => $ct2UserId,
                'session_identifier' => $ct2SessionIdentifier,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
    }

    public function closeSession(string $ct2SessionIdentifier): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_session_logs
             SET logout_at = NOW()
             WHERE session_identifier = :session_identifier
               AND logout_at IS NULL'
        );
        $ct2Statement->execute(['session_identifier' => $ct2SessionIdentifier]);
    }
}
