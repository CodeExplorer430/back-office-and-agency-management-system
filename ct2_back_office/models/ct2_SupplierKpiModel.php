<?php

declare(strict_types=1);

final class CT2_SupplierKpiModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT k.*, s.supplier_name, s.supplier_code
             FROM ct2_supplier_kpis AS k
             INNER JOIN ct2_suppliers AS s ON s.ct2_supplier_id = k.ct2_supplier_id
             ORDER BY k.measurement_date DESC, k.created_at DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2WeightedScore = $this->calculateWeightedScore(
            (float) $ct2Payload['service_score'],
            (float) $ct2Payload['delivery_score'],
            (float) $ct2Payload['compliance_score'],
            (float) $ct2Payload['responsiveness_score']
        );

        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_supplier_kpis (
                ct2_supplier_id, measurement_date, service_score, delivery_score,
                compliance_score, responsiveness_score, weighted_score, risk_flag, notes, created_by
             ) VALUES (
                :ct2_supplier_id, :measurement_date, :service_score, :delivery_score,
                :compliance_score, :responsiveness_score, :weighted_score, :risk_flag, :notes, :created_by
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_supplier_id' => $ct2Payload['ct2_supplier_id'],
                'measurement_date' => $ct2Payload['measurement_date'],
                'service_score' => $ct2Payload['service_score'],
                'delivery_score' => $ct2Payload['delivery_score'],
                'compliance_score' => $ct2Payload['compliance_score'],
                'responsiveness_score' => $ct2Payload['responsiveness_score'],
                'weighted_score' => $ct2WeightedScore,
                'risk_flag' => $ct2Payload['risk_flag'],
                'notes' => $ct2Payload['notes'] ?: null,
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function calculateWeightedScore(float $ct2Service, float $ct2Delivery, float $ct2Compliance, float $ct2Responsiveness): float
    {
        $ct2WeightedScore = ($ct2Service * 0.3) + ($ct2Delivery * 0.25) + ($ct2Compliance * 0.3) + ($ct2Responsiveness * 0.15);

        return round($ct2WeightedScore, 2);
    }
}
