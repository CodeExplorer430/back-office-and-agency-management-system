<?php

declare(strict_types=1);

final class CT2_ReferralClickModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT rc.*,
                a.affiliate_name,
                c.campaign_name
             FROM ct2_referral_clicks AS rc
             INNER JOIN ct2_affiliates AS a ON a.ct2_affiliate_id = rc.ct2_affiliate_id
             LEFT JOIN ct2_campaigns AS c ON c.ct2_campaign_id = rc.ct2_campaign_id
             ORDER BY rc.click_date DESC, rc.ct2_referral_click_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_referral_clicks (
                ct2_affiliate_id, ct2_campaign_id, referral_code, click_date, landing_page,
                external_customer_id, external_booking_id, attribution_status, source_system, created_by
            ) VALUES (
                :ct2_affiliate_id, :ct2_campaign_id, :referral_code, :click_date, :landing_page,
                :external_customer_id, :external_booking_id, :attribution_status, :source_system, :created_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_affiliate_id' => $ct2Payload['ct2_affiliate_id'],
                'ct2_campaign_id' => $ct2Payload['ct2_campaign_id'] > 0 ? $ct2Payload['ct2_campaign_id'] : null,
                'referral_code' => $ct2Payload['referral_code'],
                'click_date' => $ct2Payload['click_date'],
                'landing_page' => $ct2Payload['landing_page'] ?: null,
                'external_customer_id' => $ct2Payload['external_customer_id'] ?: null,
                'external_booking_id' => $ct2Payload['external_booking_id'] ?: null,
                'attribution_status' => $ct2Payload['attribution_status'],
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
