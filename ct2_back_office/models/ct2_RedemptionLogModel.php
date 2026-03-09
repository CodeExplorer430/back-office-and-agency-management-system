<?php

declare(strict_types=1);

final class CT2_RedemptionLogModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT rl.*,
                c.campaign_name,
                p.promotion_name,
                v.voucher_code
             FROM ct2_redemption_logs AS rl
             LEFT JOIN ct2_campaigns AS c ON c.ct2_campaign_id = rl.ct2_campaign_id
             LEFT JOIN ct2_promotions AS p ON p.ct2_promotion_id = rl.ct2_promotion_id
             LEFT JOIN ct2_vouchers AS v ON v.ct2_voucher_id = rl.ct2_voucher_id
             ORDER BY rl.redemption_date DESC, rl.ct2_redemption_log_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $this->ct2Pdo->beginTransaction();

        try {
            $ct2Statement = $this->ct2Pdo->prepare(
                'INSERT INTO ct2_redemption_logs (
                    ct2_campaign_id, ct2_promotion_id, ct2_voucher_id, redemption_date,
                    external_customer_id, external_booking_id, redeemed_amount,
                    redemption_status, source_system, created_by
                ) VALUES (
                    :ct2_campaign_id, :ct2_promotion_id, :ct2_voucher_id, :redemption_date,
                    :external_customer_id, :external_booking_id, :redeemed_amount,
                    :redemption_status, :source_system, :created_by
                )'
            );
            $ct2Statement->execute(
                [
                    'ct2_campaign_id' => $ct2Payload['ct2_campaign_id'] > 0 ? $ct2Payload['ct2_campaign_id'] : null,
                    'ct2_promotion_id' => $ct2Payload['ct2_promotion_id'] > 0 ? $ct2Payload['ct2_promotion_id'] : null,
                    'ct2_voucher_id' => $ct2Payload['ct2_voucher_id'] > 0 ? $ct2Payload['ct2_voucher_id'] : null,
                    'redemption_date' => $ct2Payload['redemption_date'],
                    'external_customer_id' => $ct2Payload['external_customer_id'] ?: null,
                    'external_booking_id' => $ct2Payload['external_booking_id'] ?: null,
                    'redeemed_amount' => $ct2Payload['redeemed_amount'],
                    'redemption_status' => $ct2Payload['redemption_status'],
                    'source_system' => $ct2Payload['source_system'] ?: null,
                    'created_by' => $ct2UserId,
                ]
            );

            $ct2RedemptionId = (int) $this->ct2Pdo->lastInsertId();

            if ($ct2Payload['ct2_voucher_id'] > 0 && $ct2Payload['redemption_status'] === 'redeemed') {
                $ct2VoucherUpdate = $this->ct2Pdo->prepare(
                    'UPDATE ct2_vouchers
                     SET redeemed_count = redeemed_count + 1,
                         voucher_status = CASE
                             WHEN redeemed_count + 1 >= max_redemptions THEN "redeemed"
                             WHEN voucher_status = "issued" THEN "active"
                             ELSE voucher_status
                         END
                     WHERE ct2_voucher_id = :ct2_voucher_id'
                );
                $ct2VoucherUpdate->execute(['ct2_voucher_id' => $ct2Payload['ct2_voucher_id']]);
            }

            $this->ct2Pdo->commit();

            return $ct2RedemptionId;
        } catch (Throwable $ct2Exception) {
            $this->ct2Pdo->rollBack();
            throw $ct2Exception;
        }
    }
}
