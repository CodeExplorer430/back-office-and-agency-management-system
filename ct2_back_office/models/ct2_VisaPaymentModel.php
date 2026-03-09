<?php

declare(strict_types=1);

final class CT2_VisaPaymentModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT vp.*,
                va.application_reference,
                vt.country_name
             FROM ct2_visa_payments AS vp
             INNER JOIN ct2_visa_applications AS va ON va.ct2_visa_application_id = vp.ct2_visa_application_id
             INNER JOIN ct2_visa_types AS vt ON vt.ct2_visa_type_id = va.ct2_visa_type_id
             ORDER BY vp.created_at DESC, vp.ct2_visa_payment_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_visa_payments (
                ct2_visa_application_id, payment_reference, external_payment_id, amount, currency,
                payment_method, payment_status, paid_at, source_system, created_by
            ) VALUES (
                :ct2_visa_application_id, :payment_reference, :external_payment_id, :amount, :currency,
                :payment_method, :payment_status, :paid_at, :source_system, :created_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2Payload['ct2_visa_application_id'],
                'payment_reference' => $ct2Payload['payment_reference'],
                'external_payment_id' => $ct2Payload['external_payment_id'] ?: null,
                'amount' => $ct2Payload['amount'],
                'currency' => $ct2Payload['currency'],
                'payment_method' => $ct2Payload['payment_method'],
                'payment_status' => $ct2Payload['payment_status'],
                'paid_at' => $ct2Payload['paid_at'] !== '' ? $ct2Payload['paid_at'] : null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
            ]
        );

        $ct2VisaPaymentId = (int) $this->ct2Pdo->lastInsertId();
        $this->refreshApplicationPaymentStatus($ct2Payload['ct2_visa_application_id']);

        return $ct2VisaPaymentId;
    }

    public function refreshApplicationPaymentStatus(int $ct2VisaApplicationId): void
    {
        $ct2SummaryStatement = $this->ct2Pdo->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN vp.payment_status = "completed" THEN vp.amount ELSE 0 END), 0) AS total_paid,
                vt.base_fee
             FROM ct2_visa_applications AS va
             INNER JOIN ct2_visa_types AS vt ON vt.ct2_visa_type_id = va.ct2_visa_type_id
             LEFT JOIN ct2_visa_payments AS vp ON vp.ct2_visa_application_id = va.ct2_visa_application_id
             WHERE va.ct2_visa_application_id = :ct2_visa_application_id
             GROUP BY vt.base_fee'
        );
        $ct2SummaryStatement->execute(['ct2_visa_application_id' => $ct2VisaApplicationId]);
        $ct2Summary = $ct2SummaryStatement->fetch();

        if ($ct2Summary === false) {
            return;
        }

        $ct2TotalPaid = (float) $ct2Summary['total_paid'];
        $ct2BaseFee = (float) $ct2Summary['base_fee'];
        $ct2PaymentStatus = 'unpaid';
        if ($ct2TotalPaid > 0 && $ct2TotalPaid < $ct2BaseFee) {
            $ct2PaymentStatus = 'partial';
        } elseif ($ct2TotalPaid > 0 && ($ct2BaseFee <= 0 || $ct2TotalPaid >= $ct2BaseFee)) {
            $ct2PaymentStatus = 'paid';
        }

        $ct2ApplicationModel = new CT2_VisaApplicationModel();
        $ct2ApplicationModel->updatePaymentStatus($ct2VisaApplicationId, $ct2PaymentStatus);
    }
}
