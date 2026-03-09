<?php

declare(strict_types=1);

final class CT2_AgentModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Search = null): array
    {
        $ct2Sql = 'SELECT *
            FROM ct2_agents';
        $ct2Parameters = [];

        if ($ct2Search !== null && $ct2Search !== '') {
            $ct2SearchFilter = $this->ct2BuildLikeFilter(
                ['agency_name', 'contact_person', 'agent_code', 'region'],
                $ct2Search,
                'agent_search'
            );
            $ct2Sql .= ' WHERE (' . $ct2SearchFilter['sql'] . ')';
            $ct2Parameters += $ct2SearchFilter['params'];
        }

        $ct2Sql .= ' ORDER BY created_at DESC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2AgentId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_agents
             WHERE ct2_agent_id = :ct2_agent_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_agent_id' => $ct2AgentId]);
        $ct2Agent = $ct2Statement->fetch();

        return $ct2Agent !== false ? $ct2Agent : null;
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_agents (
                agent_code, agency_name, contact_person, email, phone, region,
                commission_rate, support_level, approval_status, active_status,
                external_booking_id, external_customer_id, external_payment_id, source_system,
                created_by, updated_by
            ) VALUES (
                :agent_code, :agency_name, :contact_person, :email, :phone, :region,
                :commission_rate, :support_level, :approval_status, :active_status,
                :external_booking_id, :external_customer_id, :external_payment_id, :source_system,
                :created_by, :updated_by
            )'
        );
        $ct2Statement->execute(
            [
                'agent_code' => $ct2Payload['agent_code'],
                'agency_name' => $ct2Payload['agency_name'],
                'contact_person' => $ct2Payload['contact_person'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
                'region' => $ct2Payload['region'],
                'commission_rate' => $ct2Payload['commission_rate'],
                'support_level' => $ct2Payload['support_level'],
                'approval_status' => $ct2Payload['approval_status'],
                'active_status' => $ct2Payload['active_status'],
                'external_booking_id' => $ct2Payload['external_booking_id'] ?: null,
                'external_customer_id' => $ct2Payload['external_customer_id'] ?: null,
                'external_payment_id' => $ct2Payload['external_payment_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2AgentId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_agents
             SET agent_code = :agent_code,
                 agency_name = :agency_name,
                 contact_person = :contact_person,
                 email = :email,
                 phone = :phone,
                 region = :region,
                 commission_rate = :commission_rate,
                 support_level = :support_level,
                 approval_status = :approval_status,
                 active_status = :active_status,
                 external_booking_id = :external_booking_id,
                 external_customer_id = :external_customer_id,
                 external_payment_id = :external_payment_id,
                 source_system = :source_system,
                 updated_by = :updated_by
             WHERE ct2_agent_id = :ct2_agent_id'
        );
        $ct2Statement->execute(
            [
                'ct2_agent_id' => $ct2AgentId,
                'agent_code' => $ct2Payload['agent_code'],
                'agency_name' => $ct2Payload['agency_name'],
                'contact_person' => $ct2Payload['contact_person'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
                'region' => $ct2Payload['region'],
                'commission_rate' => $ct2Payload['commission_rate'],
                'support_level' => $ct2Payload['support_level'],
                'approval_status' => $ct2Payload['approval_status'],
                'active_status' => $ct2Payload['active_status'],
                'external_booking_id' => $ct2Payload['external_booking_id'] ?: null,
                'external_customer_id' => $ct2Payload['external_customer_id'] ?: null,
                'external_payment_id' => $ct2Payload['external_payment_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function updateApprovalStatus(int $ct2AgentId, string $ct2Status, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_agents
             SET approval_status = :approval_status,
                 updated_by = :updated_by
             WHERE ct2_agent_id = :ct2_agent_id'
        );
        $ct2Statement->execute(
            [
                'ct2_agent_id' => $ct2AgentId,
                'approval_status' => $ct2Status,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function updateActiveStatus(int $ct2AgentId, string $ct2ActiveStatus, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_agents
             SET active_status = :active_status,
                 updated_by = :updated_by
             WHERE ct2_agent_id = :ct2_agent_id'
        );
        $ct2Statement->execute(
            [
                'ct2_agent_id' => $ct2AgentId,
                'active_status' => $ct2ActiveStatus,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function getSummaryCounts(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT
                COUNT(*) AS total_agents,
                SUM(CASE WHEN approval_status = "pending" THEN 1 ELSE 0 END) AS pending_agents,
                SUM(CASE WHEN active_status = "active" THEN 1 ELSE 0 END) AS active_agents
             FROM ct2_agents'
        );

        return $ct2Statement->fetch() ?: [
            'total_agents' => 0,
            'pending_agents' => 0,
            'active_agents' => 0,
        ];
    }
}
