<?php

declare(strict_types=1);

final class CT2_AffiliateModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Search = null, ?string $ct2Status = null): array
    {
        $ct2Sql = 'SELECT a.*,
                COALESCE(click_summary.total_clicks, 0) AS total_clicks,
                COALESCE(click_summary.booked_clicks, 0) AS booked_clicks
            FROM ct2_affiliates AS a
            LEFT JOIN (
                SELECT
                    ct2_affiliate_id,
                    COUNT(*) AS total_clicks,
                    SUM(CASE WHEN attribution_status = "booked" THEN 1 ELSE 0 END) AS booked_clicks
                FROM ct2_referral_clicks
                GROUP BY ct2_affiliate_id
            ) AS click_summary ON click_summary.ct2_affiliate_id = a.ct2_affiliate_id
            WHERE 1 = 1';
        $ct2Parameters = [];

        if ($ct2Search !== null && $ct2Search !== '') {
            $ct2SearchFilter = $this->ct2BuildLikeFilter(
                ['a.affiliate_name', 'a.affiliate_code', 'a.referral_code', 'a.contact_name'],
                $ct2Search,
                'affiliate_search'
            );
            $ct2Sql .= ' AND (' . $ct2SearchFilter['sql'] . ')';
            $ct2Parameters += $ct2SearchFilter['params'];
        }

        if ($ct2Status !== null && $ct2Status !== '') {
            $ct2Sql .= ' AND a.affiliate_status = :affiliate_status';
            $ct2Parameters['affiliate_status'] = $ct2Status;
        }

        $ct2Sql .= ' ORDER BY a.created_at DESC';
        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2AffiliateId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_affiliates
             WHERE ct2_affiliate_id = :ct2_affiliate_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_affiliate_id' => $ct2AffiliateId]);
        $ct2Affiliate = $ct2Statement->fetch();

        return $ct2Affiliate !== false ? $ct2Affiliate : null;
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_affiliates (
                affiliate_code, affiliate_name, contact_name, email, phone, affiliate_status,
                commission_rate, payout_status, referral_code, external_partner_id, source_system,
                created_by, updated_by
            ) VALUES (
                :affiliate_code, :affiliate_name, :contact_name, :email, :phone, :affiliate_status,
                :commission_rate, :payout_status, :referral_code, :external_partner_id, :source_system,
                :created_by, :updated_by
            )'
        );
        $ct2Statement->execute(
            [
                'affiliate_code' => $ct2Payload['affiliate_code'],
                'affiliate_name' => $ct2Payload['affiliate_name'],
                'contact_name' => $ct2Payload['contact_name'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
                'affiliate_status' => $ct2Payload['affiliate_status'],
                'commission_rate' => $ct2Payload['commission_rate'],
                'payout_status' => $ct2Payload['payout_status'],
                'referral_code' => $ct2Payload['referral_code'],
                'external_partner_id' => $ct2Payload['external_partner_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2AffiliateId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_affiliates
             SET affiliate_code = :affiliate_code,
                 affiliate_name = :affiliate_name,
                 contact_name = :contact_name,
                 email = :email,
                 phone = :phone,
                 affiliate_status = :affiliate_status,
                 commission_rate = :commission_rate,
                 payout_status = :payout_status,
                 referral_code = :referral_code,
                 external_partner_id = :external_partner_id,
                 source_system = :source_system,
                 updated_by = :updated_by
             WHERE ct2_affiliate_id = :ct2_affiliate_id'
        );
        $ct2Statement->execute(
            [
                'ct2_affiliate_id' => $ct2AffiliateId,
                'affiliate_code' => $ct2Payload['affiliate_code'],
                'affiliate_name' => $ct2Payload['affiliate_name'],
                'contact_name' => $ct2Payload['contact_name'],
                'email' => $ct2Payload['email'],
                'phone' => $ct2Payload['phone'],
                'affiliate_status' => $ct2Payload['affiliate_status'],
                'commission_rate' => $ct2Payload['commission_rate'],
                'payout_status' => $ct2Payload['payout_status'],
                'referral_code' => $ct2Payload['referral_code'],
                'external_partner_id' => $ct2Payload['external_partner_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_affiliate_id, affiliate_name, referral_code
             FROM ct2_affiliates
             WHERE affiliate_status IN ("onboarding", "active", "paused")
             ORDER BY affiliate_name ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getSummaryCounts(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT
                COUNT(*) AS total_affiliates,
                SUM(CASE WHEN affiliate_status = "active" THEN 1 ELSE 0 END) AS active_affiliates,
                SUM(CASE WHEN payout_status = "hold" THEN 1 ELSE 0 END) AS blocked_payout_affiliates
             FROM ct2_affiliates'
        );

        return $ct2Statement->fetch() ?: [
            'total_affiliates' => 0,
            'active_affiliates' => 0,
            'blocked_payout_affiliates' => 0,
        ];
    }
}
