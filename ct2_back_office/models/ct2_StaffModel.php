<?php

declare(strict_types=1);

final class CT2_StaffModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Search = null): array
    {
        $ct2Sql = 'SELECT *
            FROM ct2_staff';
        $ct2Parameters = [];

        if ($ct2Search !== null && $ct2Search !== '') {
            $ct2Sql .= ' WHERE full_name LIKE :search
                OR staff_code LIKE :search
                OR department LIKE :search
                OR team_name LIKE :search';
            $ct2Parameters['search'] = '%' . $ct2Search . '%';
        }

        $ct2Sql .= ' ORDER BY created_at DESC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2StaffId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_staff
             WHERE ct2_staff_id = :ct2_staff_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_staff_id' => $ct2StaffId]);
        $ct2Staff = $ct2Statement->fetch();

        return $ct2Staff !== false ? $ct2Staff : null;
    }

    public function getAllForAssignments(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_staff_id, staff_code, full_name, team_name
             FROM ct2_staff
             WHERE employment_status = "active"
             ORDER BY full_name ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_staff (
                staff_code, full_name, email, phone, department, position_title,
                employment_status, availability_status, team_name, notes, created_by, updated_by
             ) VALUES (
                :staff_code, :full_name, :email, :phone, :department, :position_title,
                :employment_status, :availability_status, :team_name, :notes, :created_by, :updated_by
             )'
        );
        $ct2Statement->execute(
            [
                'staff_code' => $ct2Payload['staff_code'],
                'full_name' => $ct2Payload['full_name'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
                'department' => $ct2Payload['department'],
                'position_title' => $ct2Payload['position_title'],
                'employment_status' => $ct2Payload['employment_status'],
                'availability_status' => $ct2Payload['availability_status'],
                'team_name' => $ct2Payload['team_name'],
                'notes' => $ct2Payload['notes'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2StaffId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_staff
             SET staff_code = :staff_code,
                 full_name = :full_name,
                 email = :email,
                 phone = :phone,
                 department = :department,
                 position_title = :position_title,
                 employment_status = :employment_status,
                 availability_status = :availability_status,
                 team_name = :team_name,
                 notes = :notes,
                 updated_by = :updated_by
             WHERE ct2_staff_id = :ct2_staff_id'
        );
        $ct2Statement->execute(
            [
                'ct2_staff_id' => $ct2StaffId,
                'staff_code' => $ct2Payload['staff_code'],
                'full_name' => $ct2Payload['full_name'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
                'department' => $ct2Payload['department'],
                'position_title' => $ct2Payload['position_title'],
                'employment_status' => $ct2Payload['employment_status'],
                'availability_status' => $ct2Payload['availability_status'],
                'team_name' => $ct2Payload['team_name'],
                'notes' => $ct2Payload['notes'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function getSummaryCounts(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT
                COUNT(*) AS total_staff,
                SUM(CASE WHEN employment_status = "active" THEN 1 ELSE 0 END) AS active_staff,
                SUM(CASE WHEN availability_status = "available" THEN 1 ELSE 0 END) AS available_staff
             FROM ct2_staff'
        );

        return $ct2Statement->fetch() ?: [
            'total_staff' => 0,
            'active_staff' => 0,
            'available_staff' => 0,
        ];
    }
}
