<?php

declare(strict_types=1);

final class CT2_SupplierModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Search = null): array
    {
        $ct2Sql = 'SELECT s.*,
                o.checklist_status,
                o.documents_status,
                o.compliance_status,
                k.weighted_score AS latest_weighted_score,
                c.contract_status AS latest_contract_status,
                c.expiry_date AS latest_contract_expiry
            FROM ct2_suppliers AS s
            LEFT JOIN ct2_supplier_onboarding AS o ON o.ct2_supplier_id = s.ct2_supplier_id
            LEFT JOIN ct2_supplier_kpis AS k
                ON k.ct2_supplier_kpi_id = (
                    SELECT ct2_supplier_kpi_id
                    FROM ct2_supplier_kpis
                    WHERE ct2_supplier_id = s.ct2_supplier_id
                    ORDER BY measurement_date DESC, ct2_supplier_kpi_id DESC
                    LIMIT 1
                )
            LEFT JOIN ct2_supplier_contracts AS c
                ON c.ct2_supplier_contract_id = (
                    SELECT ct2_supplier_contract_id
                    FROM ct2_supplier_contracts
                    WHERE ct2_supplier_id = s.ct2_supplier_id
                    ORDER BY expiry_date DESC, ct2_supplier_contract_id DESC
                    LIMIT 1
                )';
        $ct2Parameters = [];

        if ($ct2Search !== null && $ct2Search !== '') {
            $ct2Sql .= ' WHERE s.supplier_name LIKE :search
                OR s.supplier_code LIKE :search
                OR s.primary_contact_name LIKE :search
                OR s.service_category LIKE :search';
            $ct2Parameters['search'] = '%' . $ct2Search . '%';
        }

        $ct2Sql .= ' ORDER BY s.created_at DESC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2SupplierId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_suppliers
             WHERE ct2_supplier_id = :ct2_supplier_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_supplier_id' => $ct2SupplierId]);
        $ct2Supplier = $ct2Statement->fetch();

        return $ct2Supplier !== false ? $ct2Supplier : null;
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_suppliers (
                supplier_code, supplier_name, supplier_type, primary_contact_name, email, phone,
                service_category, support_tier, approval_status, onboarding_status, active_status,
                risk_level, internal_owner_user_id, external_supplier_id, source_system, created_by, updated_by
            ) VALUES (
                :supplier_code, :supplier_name, :supplier_type, :primary_contact_name, :email, :phone,
                :service_category, :support_tier, :approval_status, :onboarding_status, :active_status,
                :risk_level, :internal_owner_user_id, :external_supplier_id, :source_system, :created_by, :updated_by
            )'
        );
        $ct2Statement->execute(
            [
                'supplier_code' => $ct2Payload['supplier_code'],
                'supplier_name' => $ct2Payload['supplier_name'],
                'supplier_type' => $ct2Payload['supplier_type'],
                'primary_contact_name' => $ct2Payload['primary_contact_name'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
                'service_category' => $ct2Payload['service_category'],
                'support_tier' => $ct2Payload['support_tier'],
                'approval_status' => $ct2Payload['approval_status'],
                'onboarding_status' => $ct2Payload['onboarding_status'],
                'active_status' => $ct2Payload['active_status'],
                'risk_level' => $ct2Payload['risk_level'],
                'internal_owner_user_id' => $ct2Payload['internal_owner_user_id'] ?: $ct2UserId,
                'external_supplier_id' => $ct2Payload['external_supplier_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        $ct2SupplierId = (int) $this->ct2Pdo->lastInsertId();
        $this->upsertPrimaryContact($ct2SupplierId, $ct2Payload);

        return $ct2SupplierId;
    }

    public function update(int $ct2SupplierId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_suppliers
             SET supplier_code = :supplier_code,
                 supplier_name = :supplier_name,
                 supplier_type = :supplier_type,
                 primary_contact_name = :primary_contact_name,
                 email = :email,
                 phone = :phone,
                 service_category = :service_category,
                 support_tier = :support_tier,
                 approval_status = :approval_status,
                 onboarding_status = :onboarding_status,
                 active_status = :active_status,
                 risk_level = :risk_level,
                 internal_owner_user_id = :internal_owner_user_id,
                 external_supplier_id = :external_supplier_id,
                 source_system = :source_system,
                 updated_by = :updated_by
             WHERE ct2_supplier_id = :ct2_supplier_id'
        );
        $ct2Statement->execute(
            [
                'ct2_supplier_id' => $ct2SupplierId,
                'supplier_code' => $ct2Payload['supplier_code'],
                'supplier_name' => $ct2Payload['supplier_name'],
                'supplier_type' => $ct2Payload['supplier_type'],
                'primary_contact_name' => $ct2Payload['primary_contact_name'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
                'service_category' => $ct2Payload['service_category'],
                'support_tier' => $ct2Payload['support_tier'],
                'approval_status' => $ct2Payload['approval_status'],
                'onboarding_status' => $ct2Payload['onboarding_status'],
                'active_status' => $ct2Payload['active_status'],
                'risk_level' => $ct2Payload['risk_level'],
                'internal_owner_user_id' => $ct2Payload['internal_owner_user_id'] ?: $ct2UserId,
                'external_supplier_id' => $ct2Payload['external_supplier_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );

        $this->upsertPrimaryContact($ct2SupplierId, $ct2Payload);
    }

    public function updateApprovalStatus(int $ct2SupplierId, string $ct2Status, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_suppliers
             SET approval_status = :approval_status,
                 updated_by = :updated_by
             WHERE ct2_supplier_id = :ct2_supplier_id'
        );
        $ct2Statement->execute(
            [
                'ct2_supplier_id' => $ct2SupplierId,
                'approval_status' => $ct2Status,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function getPrimaryContacts(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT sc.*, s.supplier_name
             FROM ct2_supplier_contacts AS sc
             INNER JOIN ct2_suppliers AS s ON s.ct2_supplier_id = sc.ct2_supplier_id
             WHERE sc.is_primary = 1
             ORDER BY s.supplier_name ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_supplier_id, supplier_name, supplier_code
             FROM ct2_suppliers
             ORDER BY supplier_name ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getSummaryCounts(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT
                COUNT(*) AS total_suppliers,
                SUM(CASE WHEN approval_status = "pending" THEN 1 ELSE 0 END) AS pending_suppliers,
                SUM(CASE WHEN active_status = "active" THEN 1 ELSE 0 END) AS active_suppliers,
                SUM(CASE WHEN onboarding_status IN ("draft", "in_review", "blocked") THEN 1 ELSE 0 END) AS onboarding_suppliers
             FROM ct2_suppliers'
        );

        return $ct2Statement->fetch() ?: [
            'total_suppliers' => 0,
            'pending_suppliers' => 0,
            'active_suppliers' => 0,
            'onboarding_suppliers' => 0,
        ];
    }

    private function upsertPrimaryContact(int $ct2SupplierId, array $ct2Payload): void
    {
        $ct2Check = $this->ct2Pdo->prepare(
            'SELECT ct2_supplier_contact_id
             FROM ct2_supplier_contacts
             WHERE ct2_supplier_id = :ct2_supplier_id
               AND is_primary = 1
             LIMIT 1'
        );
        $ct2Check->execute(['ct2_supplier_id' => $ct2SupplierId]);
        $ct2Existing = $ct2Check->fetch();

        if ($ct2Existing !== false) {
            $ct2Update = $this->ct2Pdo->prepare(
                'UPDATE ct2_supplier_contacts
                 SET contact_name = :contact_name,
                     role_title = :role_title,
                     email = :email,
                     phone = :phone
                 WHERE ct2_supplier_contact_id = :ct2_supplier_contact_id'
            );
            $ct2Update->execute(
                [
                    'ct2_supplier_contact_id' => $ct2Existing['ct2_supplier_contact_id'],
                    'contact_name' => $ct2Payload['primary_contact_name'],
                    'role_title' => $ct2Payload['contact_role_title'],
                    'email' => $ct2Payload['email'],
                    'phone' => $ct2Payload['phone'],
                ]
            );
            return;
        }

        $ct2Insert = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_supplier_contacts (
                ct2_supplier_id, contact_name, role_title, email, phone, is_primary
             ) VALUES (
                :ct2_supplier_id, :contact_name, :role_title, :email, :phone, 1
             )'
        );
        $ct2Insert->execute(
            [
                'ct2_supplier_id' => $ct2SupplierId,
                'contact_name' => $ct2Payload['primary_contact_name'],
                'role_title' => $ct2Payload['contact_role_title'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
            ]
        );
    }
}
