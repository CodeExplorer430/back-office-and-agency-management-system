<?php

declare(strict_types=1);

final class CT2_VisaApplicationModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Search = null, ?string $ct2Status = null, ?int $ct2VisaTypeId = null): array
    {
        $ct2Sql = 'SELECT va.*,
                vt.visa_code,
                vt.country_name,
                vt.visa_category,
                vt.base_fee,
                COALESCE(payment_summary.total_paid, 0) AS total_paid
            FROM ct2_visa_applications AS va
            INNER JOIN ct2_visa_types AS vt ON vt.ct2_visa_type_id = va.ct2_visa_type_id
            LEFT JOIN (
                SELECT ct2_visa_application_id, SUM(amount) AS total_paid
                FROM ct2_visa_payments
                WHERE payment_status = "completed"
                GROUP BY ct2_visa_application_id
            ) AS payment_summary ON payment_summary.ct2_visa_application_id = va.ct2_visa_application_id
            WHERE 1 = 1';
        $ct2Parameters = [];

        if ($ct2Search !== null && $ct2Search !== '') {
            $ct2SearchFilter = $this->ct2BuildLikeFilter(
                [
                    'va.application_reference',
                    'va.external_customer_id',
                    'COALESCE(va.external_agent_id, "")',
                    'vt.country_name',
                ],
                $ct2Search,
                'visa_search'
            );
            $ct2Sql .= ' AND (' . $ct2SearchFilter['sql'] . ')';
            $ct2Parameters += $ct2SearchFilter['params'];
        }

        if ($ct2Status !== null && $ct2Status !== '') {
            $ct2Sql .= ' AND va.status = :status';
            $ct2Parameters['status'] = $ct2Status;
        }

        if ($ct2VisaTypeId !== null && $ct2VisaTypeId > 0) {
            $ct2Sql .= ' AND va.ct2_visa_type_id = :ct2_visa_type_id';
            $ct2Parameters['ct2_visa_type_id'] = $ct2VisaTypeId;
        }

        $ct2Sql .= ' ORDER BY va.created_at DESC, va.ct2_visa_application_id DESC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2VisaApplicationId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_visa_applications
             WHERE ct2_visa_application_id = :ct2_visa_application_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_visa_application_id' => $ct2VisaApplicationId]);
        $ct2Application = $ct2Statement->fetch();

        return $ct2Application !== false ? $ct2Application : null;
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_visa_applications (
                ct2_visa_type_id, application_reference, external_customer_id, external_agent_id,
                source_system, status, submission_date, appointment_date, embassy_reference,
                approval_status, documents_verified, outstanding_item_count, payment_status,
                remarks, created_by, updated_by
            ) VALUES (
                :ct2_visa_type_id, :application_reference, :external_customer_id, :external_agent_id,
                :source_system, :status, :submission_date, :appointment_date, :embassy_reference,
                :approval_status, 0, 0, "unpaid",
                :remarks, :created_by, :updated_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_type_id' => $ct2Payload['ct2_visa_type_id'],
                'application_reference' => $ct2Payload['application_reference'],
                'external_customer_id' => $ct2Payload['external_customer_id'],
                'external_agent_id' => $ct2Payload['external_agent_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'status' => $ct2Payload['status'],
                'submission_date' => $ct2Payload['submission_date'],
                'appointment_date' => $ct2Payload['appointment_date'] !== '' ? $ct2Payload['appointment_date'] : null,
                'embassy_reference' => $ct2Payload['embassy_reference'] ?: null,
                'approval_status' => $ct2Payload['approval_status'],
                'remarks' => $ct2Payload['remarks'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2VisaApplicationId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_visa_applications
             SET ct2_visa_type_id = :ct2_visa_type_id,
                 application_reference = :application_reference,
                 external_customer_id = :external_customer_id,
                 external_agent_id = :external_agent_id,
                 source_system = :source_system,
                 status = :status,
                 submission_date = :submission_date,
                 appointment_date = :appointment_date,
                 embassy_reference = :embassy_reference,
                 approval_status = :approval_status,
                 remarks = :remarks,
                 updated_by = :updated_by
             WHERE ct2_visa_application_id = :ct2_visa_application_id'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2VisaApplicationId,
                'ct2_visa_type_id' => $ct2Payload['ct2_visa_type_id'],
                'application_reference' => $ct2Payload['application_reference'],
                'external_customer_id' => $ct2Payload['external_customer_id'],
                'external_agent_id' => $ct2Payload['external_agent_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'status' => $ct2Payload['status'],
                'submission_date' => $ct2Payload['submission_date'],
                'appointment_date' => $ct2Payload['appointment_date'] !== '' ? $ct2Payload['appointment_date'] : null,
                'embassy_reference' => $ct2Payload['embassy_reference'] ?: null,
                'approval_status' => $ct2Payload['approval_status'],
                'remarks' => $ct2Payload['remarks'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function updateCaseStatus(int $ct2VisaApplicationId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_visa_applications
             SET status = :status,
                 appointment_date = :appointment_date,
                 embassy_reference = :embassy_reference,
                 approval_status = :approval_status,
                 remarks = :remarks,
                 updated_by = :updated_by
             WHERE ct2_visa_application_id = :ct2_visa_application_id'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2VisaApplicationId,
                'status' => $ct2Payload['status'],
                'appointment_date' => $ct2Payload['appointment_date'] !== '' ? $ct2Payload['appointment_date'] : null,
                'embassy_reference' => $ct2Payload['embassy_reference'] !== '' ? $ct2Payload['embassy_reference'] : null,
                'approval_status' => $ct2Payload['approval_status'],
                'remarks' => $ct2Payload['remarks'] !== '' ? $ct2Payload['remarks'] : null,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function updateApprovalStatus(int $ct2VisaApplicationId, string $ct2Status, int $ct2UserId): void
    {
        $ct2ApplicationStatus = $ct2Status === 'approved' ? 'processing' : 'rejected';
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_visa_applications
             SET approval_status = :approval_status,
                 status = :status,
                 updated_by = :updated_by
             WHERE ct2_visa_application_id = :ct2_visa_application_id'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2VisaApplicationId,
                'approval_status' => $ct2Status,
                'status' => $ct2ApplicationStatus,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function updateChecklistState(int $ct2VisaApplicationId, int $ct2OutstandingItemCount, bool $ct2DocumentsVerified): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_visa_applications
             SET outstanding_item_count = :outstanding_item_count,
                 documents_verified = :documents_verified
             WHERE ct2_visa_application_id = :ct2_visa_application_id'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2VisaApplicationId,
                'outstanding_item_count' => $ct2OutstandingItemCount,
                'documents_verified' => $ct2DocumentsVerified ? 1 : 0,
            ]
        );
    }

    public function updatePaymentStatus(int $ct2VisaApplicationId, string $ct2PaymentStatus): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_visa_applications
             SET payment_status = :payment_status
             WHERE ct2_visa_application_id = :ct2_visa_application_id'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2VisaApplicationId,
                'payment_status' => $ct2PaymentStatus,
            ]
        );
    }

    public function getSummaryCounts(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT
                COUNT(*) AS total_applications,
                SUM(CASE WHEN status IN ("submitted", "document_review", "escalated_review") THEN 1 ELSE 0 END) AS review_queue,
                SUM(CASE WHEN appointment_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS upcoming_appointments,
                SUM(CASE WHEN status IN ("approved", "released") THEN 1 ELSE 0 END) AS completed_applications
             FROM ct2_visa_applications'
        );

        return $ct2Statement->fetch() ?: [
            'total_applications' => 0,
            'review_queue' => 0,
            'upcoming_appointments' => 0,
            'completed_applications' => 0,
        ];
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_visa_application_id, application_reference, external_customer_id
             FROM ct2_visa_applications
             ORDER BY created_at DESC, ct2_visa_application_id DESC'
        );

        return $ct2Statement->fetchAll();
    }
}
