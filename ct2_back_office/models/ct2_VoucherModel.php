<?php

declare(strict_types=1);

final class CT2_VoucherModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Status = null): array
    {
        $ct2Sql = 'SELECT v.*,
                p.promotion_name,
                c.campaign_name
            FROM ct2_vouchers AS v
            LEFT JOIN ct2_promotions AS p ON p.ct2_promotion_id = v.ct2_promotion_id
            LEFT JOIN ct2_campaigns AS c ON c.ct2_campaign_id = p.ct2_campaign_id
            WHERE 1 = 1';
        $ct2Parameters = [];

        if ($ct2Status !== null && $ct2Status !== '') {
            $ct2Sql .= ' AND v.voucher_status = :voucher_status';
            $ct2Parameters['voucher_status'] = $ct2Status;
        }

        $ct2Sql .= ' ORDER BY v.valid_until ASC, v.created_at DESC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_vouchers (
                ct2_promotion_id, voucher_code, voucher_name, customer_scope, max_redemptions,
                redeemed_count, voucher_status, valid_from, valid_until, external_customer_id,
                source_system, created_by
            ) VALUES (
                :ct2_promotion_id, :voucher_code, :voucher_name, :customer_scope, :max_redemptions,
                :redeemed_count, :voucher_status, :valid_from, :valid_until, :external_customer_id,
                :source_system, :created_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_promotion_id' => $ct2Payload['ct2_promotion_id'] > 0 ? $ct2Payload['ct2_promotion_id'] : null,
                'voucher_code' => $ct2Payload['voucher_code'],
                'voucher_name' => $ct2Payload['voucher_name'],
                'customer_scope' => $ct2Payload['customer_scope'],
                'max_redemptions' => $ct2Payload['max_redemptions'],
                'redeemed_count' => 0,
                'voucher_status' => $ct2Payload['voucher_status'],
                'valid_from' => $ct2Payload['valid_from'],
                'valid_until' => $ct2Payload['valid_until'],
                'external_customer_id' => $ct2Payload['external_customer_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2VoucherId, array $ct2Payload): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_vouchers
             SET ct2_promotion_id = :ct2_promotion_id,
                 voucher_code = :voucher_code,
                 voucher_name = :voucher_name,
                 customer_scope = :customer_scope,
                 max_redemptions = :max_redemptions,
                 voucher_status = :voucher_status,
                 valid_from = :valid_from,
                 valid_until = :valid_until,
                 external_customer_id = :external_customer_id,
                 source_system = :source_system
             WHERE ct2_voucher_id = :ct2_voucher_id'
        );
        $ct2Statement->execute(
            [
                'ct2_voucher_id' => $ct2VoucherId,
                'ct2_promotion_id' => $ct2Payload['ct2_promotion_id'] > 0 ? $ct2Payload['ct2_promotion_id'] : null,
                'voucher_code' => $ct2Payload['voucher_code'],
                'voucher_name' => $ct2Payload['voucher_name'],
                'customer_scope' => $ct2Payload['customer_scope'],
                'max_redemptions' => $ct2Payload['max_redemptions'],
                'voucher_status' => $ct2Payload['voucher_status'],
                'valid_from' => $ct2Payload['valid_from'],
                'valid_until' => $ct2Payload['valid_until'],
                'external_customer_id' => $ct2Payload['external_customer_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
            ]
        );
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_voucher_id, voucher_code, voucher_name
             FROM ct2_vouchers
             WHERE voucher_status IN ("issued", "active")
             ORDER BY voucher_code ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getExpiringSoon(int $ct2Days = 14): array
    {
        $ct2Days = max(1, $ct2Days);
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT voucher_code, voucher_name, valid_until, max_redemptions, redeemed_count, voucher_status
             FROM ct2_vouchers
             WHERE voucher_status IN ("issued", "active")
               AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ' . $ct2Days . ' DAY)
             ORDER BY valid_until ASC, voucher_code ASC'
        );

        return $ct2Statement->fetchAll();
    }
}
