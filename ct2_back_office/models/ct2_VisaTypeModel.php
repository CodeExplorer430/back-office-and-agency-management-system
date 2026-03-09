<?php

declare(strict_types=1);

final class CT2_VisaTypeModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT vt.*,
                COALESCE(template_summary.template_count, 0) AS template_count
             FROM ct2_visa_types AS vt
             LEFT JOIN (
                SELECT ct2_visa_type_id, COUNT(*) AS template_count
                FROM ct2_visa_checklist_items
                GROUP BY ct2_visa_type_id
             ) AS template_summary ON template_summary.ct2_visa_type_id = vt.ct2_visa_type_id
             ORDER BY vt.country_name ASC, vt.visa_category ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2VisaTypeId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_visa_types
             WHERE ct2_visa_type_id = :ct2_visa_type_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_visa_type_id' => $ct2VisaTypeId]);
        $ct2VisaType = $ct2Statement->fetch();

        return $ct2VisaType !== false ? $ct2VisaType : null;
    }

    public function create(array $ct2Payload): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_visa_types (
                visa_code, country_name, visa_category, processing_days, biometrics_required,
                validity_period_days, base_fee, is_active
            ) VALUES (
                :visa_code, :country_name, :visa_category, :processing_days, :biometrics_required,
                :validity_period_days, :base_fee, :is_active
            )'
        );
        $ct2Statement->execute(
            [
                'visa_code' => $ct2Payload['visa_code'],
                'country_name' => $ct2Payload['country_name'],
                'visa_category' => $ct2Payload['visa_category'],
                'processing_days' => $ct2Payload['processing_days'],
                'biometrics_required' => $ct2Payload['biometrics_required'],
                'validity_period_days' => $ct2Payload['validity_period_days'],
                'base_fee' => $ct2Payload['base_fee'],
                'is_active' => $ct2Payload['is_active'],
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2VisaTypeId, array $ct2Payload): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_visa_types
             SET visa_code = :visa_code,
                 country_name = :country_name,
                 visa_category = :visa_category,
                 processing_days = :processing_days,
                 biometrics_required = :biometrics_required,
                 validity_period_days = :validity_period_days,
                 base_fee = :base_fee,
                 is_active = :is_active
             WHERE ct2_visa_type_id = :ct2_visa_type_id'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_type_id' => $ct2VisaTypeId,
                'visa_code' => $ct2Payload['visa_code'],
                'country_name' => $ct2Payload['country_name'],
                'visa_category' => $ct2Payload['visa_category'],
                'processing_days' => $ct2Payload['processing_days'],
                'biometrics_required' => $ct2Payload['biometrics_required'],
                'validity_period_days' => $ct2Payload['validity_period_days'],
                'base_fee' => $ct2Payload['base_fee'],
                'is_active' => $ct2Payload['is_active'],
            ]
        );
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_visa_type_id, visa_code, country_name, visa_category
             FROM ct2_visa_types
             WHERE is_active = 1
             ORDER BY country_name ASC, visa_category ASC'
        );

        return $ct2Statement->fetchAll();
    }
}
