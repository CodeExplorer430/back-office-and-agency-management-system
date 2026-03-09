<?php

declare(strict_types=1);

final class CT2_PromotionModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Search = null, ?string $ct2Status = null): array
    {
        $ct2Sql = 'SELECT p.*,
                c.campaign_name,
                COALESCE(redemption_summary.redemption_count, 0) AS redemption_count,
                COALESCE(redemption_summary.total_redeemed_amount, 0) AS total_redeemed_amount
            FROM ct2_promotions AS p
            INNER JOIN ct2_campaigns AS c ON c.ct2_campaign_id = p.ct2_campaign_id
            LEFT JOIN (
                SELECT
                    ct2_promotion_id,
                    COUNT(*) AS redemption_count,
                    SUM(redeemed_amount) AS total_redeemed_amount
                FROM ct2_redemption_logs
                WHERE redemption_status = "redeemed"
                GROUP BY ct2_promotion_id
            ) AS redemption_summary ON redemption_summary.ct2_promotion_id = p.ct2_promotion_id
            WHERE 1 = 1';
        $ct2Parameters = [];

        if ($ct2Search !== null && $ct2Search !== '') {
            $ct2Sql .= ' AND (
                p.promotion_name LIKE :search
                OR p.promotion_code LIKE :search
                OR c.campaign_name LIKE :search
            )';
            $ct2Parameters['search'] = '%' . $ct2Search . '%';
        }

        if ($ct2Status !== null && $ct2Status !== '') {
            $ct2Sql .= ' AND p.promotion_status = :promotion_status';
            $ct2Parameters['promotion_status'] = $ct2Status;
        }

        $ct2Sql .= ' ORDER BY p.valid_from DESC, p.created_at DESC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2PromotionId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_promotions
             WHERE ct2_promotion_id = :ct2_promotion_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_promotion_id' => $ct2PromotionId]);
        $ct2Promotion = $ct2Statement->fetch();

        return $ct2Promotion !== false ? $ct2Promotion : null;
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_promotions (
                ct2_campaign_id, promotion_code, promotion_name, promotion_type, discount_value,
                eligibility_rule, valid_from, valid_until, usage_limit, promotion_status,
                approval_status, external_booking_scope, source_system, created_by, updated_by
            ) VALUES (
                :ct2_campaign_id, :promotion_code, :promotion_name, :promotion_type, :discount_value,
                :eligibility_rule, :valid_from, :valid_until, :usage_limit, :promotion_status,
                :approval_status, :external_booking_scope, :source_system, :created_by, :updated_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_campaign_id' => $ct2Payload['ct2_campaign_id'],
                'promotion_code' => $ct2Payload['promotion_code'],
                'promotion_name' => $ct2Payload['promotion_name'],
                'promotion_type' => $ct2Payload['promotion_type'],
                'discount_value' => $ct2Payload['discount_value'],
                'eligibility_rule' => $ct2Payload['eligibility_rule'] ?: null,
                'valid_from' => $ct2Payload['valid_from'],
                'valid_until' => $ct2Payload['valid_until'],
                'usage_limit' => $ct2Payload['usage_limit'],
                'promotion_status' => $ct2Payload['promotion_status'],
                'approval_status' => $ct2Payload['approval_status'],
                'external_booking_scope' => $ct2Payload['external_booking_scope'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2PromotionId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_promotions
             SET ct2_campaign_id = :ct2_campaign_id,
                 promotion_code = :promotion_code,
                 promotion_name = :promotion_name,
                 promotion_type = :promotion_type,
                 discount_value = :discount_value,
                 eligibility_rule = :eligibility_rule,
                 valid_from = :valid_from,
                 valid_until = :valid_until,
                 usage_limit = :usage_limit,
                 promotion_status = :promotion_status,
                 approval_status = :approval_status,
                 external_booking_scope = :external_booking_scope,
                 source_system = :source_system,
                 updated_by = :updated_by
             WHERE ct2_promotion_id = :ct2_promotion_id'
        );
        $ct2Statement->execute(
            [
                'ct2_promotion_id' => $ct2PromotionId,
                'ct2_campaign_id' => $ct2Payload['ct2_campaign_id'],
                'promotion_code' => $ct2Payload['promotion_code'],
                'promotion_name' => $ct2Payload['promotion_name'],
                'promotion_type' => $ct2Payload['promotion_type'],
                'discount_value' => $ct2Payload['discount_value'],
                'eligibility_rule' => $ct2Payload['eligibility_rule'] ?: null,
                'valid_from' => $ct2Payload['valid_from'],
                'valid_until' => $ct2Payload['valid_until'],
                'usage_limit' => $ct2Payload['usage_limit'],
                'promotion_status' => $ct2Payload['promotion_status'],
                'approval_status' => $ct2Payload['approval_status'],
                'external_booking_scope' => $ct2Payload['external_booking_scope'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function updateApprovalStatus(int $ct2PromotionId, string $ct2Status, int $ct2UserId): void
    {
        $ct2PromotionStatus = 'pending_approval';
        if ($ct2Status === 'approved') {
            $ct2PromotionStatus = 'active';
        } elseif ($ct2Status === 'rejected') {
            $ct2PromotionStatus = 'paused';
        }

        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_promotions
             SET approval_status = :approval_status,
                 promotion_status = :promotion_status,
                 updated_by = :updated_by
             WHERE ct2_promotion_id = :ct2_promotion_id'
        );
        $ct2Statement->execute(
            [
                'ct2_promotion_id' => $ct2PromotionId,
                'approval_status' => $ct2Status,
                'promotion_status' => $ct2PromotionStatus,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT p.ct2_promotion_id, p.promotion_name, c.campaign_name
             FROM ct2_promotions AS p
             INNER JOIN ct2_campaigns AS c ON c.ct2_campaign_id = p.ct2_campaign_id
             WHERE p.promotion_status <> "archived"
             ORDER BY p.promotion_name ASC'
        );

        return $ct2Statement->fetchAll();
    }
}
