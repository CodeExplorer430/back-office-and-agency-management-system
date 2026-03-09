<?php

declare(strict_types=1);

final class CT2_MarketingCampaignModel extends CT2_BaseModel
{
    public function getAll(?string $ct2Search = null, ?string $ct2Status = null, ?string $ct2Channel = null): array
    {
        $ct2Sql = 'SELECT c.*,
                COALESCE(promotion_summary.promotion_count, 0) AS promotion_count,
                COALESCE(metric_summary.total_impressions, 0) AS total_impressions,
                COALESCE(metric_summary.total_clicks, 0) AS total_clicks,
                COALESCE(metric_summary.total_conversions, 0) AS total_conversions,
                COALESCE(metric_summary.attributed_revenue, 0) AS attributed_revenue
            FROM ct2_campaigns AS c
            LEFT JOIN (
                SELECT ct2_campaign_id, COUNT(*) AS promotion_count
                FROM ct2_promotions
                GROUP BY ct2_campaign_id
            ) AS promotion_summary ON promotion_summary.ct2_campaign_id = c.ct2_campaign_id
            LEFT JOIN (
                SELECT
                    ct2_campaign_id,
                    SUM(impressions_count) AS total_impressions,
                    SUM(click_count) AS total_clicks,
                    SUM(conversion_count) AS total_conversions,
                    SUM(attributed_revenue) AS attributed_revenue
                FROM ct2_campaign_metrics
                GROUP BY ct2_campaign_id
            ) AS metric_summary ON metric_summary.ct2_campaign_id = c.ct2_campaign_id
            WHERE 1 = 1';
        $ct2Parameters = [];

        if ($ct2Search !== null && $ct2Search !== '') {
            $ct2Sql .= ' AND (
                c.campaign_name LIKE :search
                OR c.campaign_code LIKE :search
                OR c.campaign_type LIKE :search
                OR c.target_audience LIKE :search
            )';
            $ct2Parameters['search'] = '%' . $ct2Search . '%';
        }

        if ($ct2Status !== null && $ct2Status !== '') {
            $ct2Sql .= ' AND c.status = :status';
            $ct2Parameters['status'] = $ct2Status;
        }

        if ($ct2Channel !== null && $ct2Channel !== '') {
            $ct2Sql .= ' AND c.channel_type = :channel_type';
            $ct2Parameters['channel_type'] = $ct2Channel;
        }

        $ct2Sql .= ' ORDER BY c.start_date DESC, c.created_at DESC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2CampaignId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_campaigns
             WHERE ct2_campaign_id = :ct2_campaign_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_campaign_id' => $ct2CampaignId]);
        $ct2Campaign = $ct2Statement->fetch();

        return $ct2Campaign !== false ? $ct2Campaign : null;
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_campaigns (
                campaign_code, campaign_name, campaign_type, channel_type, start_date, end_date,
                budget_amount, status, approval_status, target_audience, external_customer_segment_id,
                source_system, created_by, updated_by
            ) VALUES (
                :campaign_code, :campaign_name, :campaign_type, :channel_type, :start_date, :end_date,
                :budget_amount, :status, :approval_status, :target_audience, :external_customer_segment_id,
                :source_system, :created_by, :updated_by
            )'
        );
        $ct2Statement->execute(
            [
                'campaign_code' => $ct2Payload['campaign_code'],
                'campaign_name' => $ct2Payload['campaign_name'],
                'campaign_type' => $ct2Payload['campaign_type'],
                'channel_type' => $ct2Payload['channel_type'],
                'start_date' => $ct2Payload['start_date'],
                'end_date' => $ct2Payload['end_date'],
                'budget_amount' => $ct2Payload['budget_amount'],
                'status' => $ct2Payload['status'],
                'approval_status' => $ct2Payload['approval_status'],
                'target_audience' => $ct2Payload['target_audience'] ?: null,
                'external_customer_segment_id' => $ct2Payload['external_customer_segment_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2CampaignId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_campaigns
             SET campaign_code = :campaign_code,
                 campaign_name = :campaign_name,
                 campaign_type = :campaign_type,
                 channel_type = :channel_type,
                 start_date = :start_date,
                 end_date = :end_date,
                 budget_amount = :budget_amount,
                 status = :status,
                 approval_status = :approval_status,
                 target_audience = :target_audience,
                 external_customer_segment_id = :external_customer_segment_id,
                 source_system = :source_system,
                 updated_by = :updated_by
             WHERE ct2_campaign_id = :ct2_campaign_id'
        );
        $ct2Statement->execute(
            [
                'ct2_campaign_id' => $ct2CampaignId,
                'campaign_code' => $ct2Payload['campaign_code'],
                'campaign_name' => $ct2Payload['campaign_name'],
                'campaign_type' => $ct2Payload['campaign_type'],
                'channel_type' => $ct2Payload['channel_type'],
                'start_date' => $ct2Payload['start_date'],
                'end_date' => $ct2Payload['end_date'],
                'budget_amount' => $ct2Payload['budget_amount'],
                'status' => $ct2Payload['status'],
                'approval_status' => $ct2Payload['approval_status'],
                'target_audience' => $ct2Payload['target_audience'] ?: null,
                'external_customer_segment_id' => $ct2Payload['external_customer_segment_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function updateApprovalStatus(int $ct2CampaignId, string $ct2Status, int $ct2UserId): void
    {
        $ct2CampaignStatus = 'pending_approval';
        if ($ct2Status === 'approved') {
            $ct2CampaignStatus = 'active';
        } elseif ($ct2Status === 'rejected') {
            $ct2CampaignStatus = 'paused';
        }

        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_campaigns
             SET approval_status = :approval_status,
                 status = :status,
                 updated_by = :updated_by
             WHERE ct2_campaign_id = :ct2_campaign_id'
        );
        $ct2Statement->execute(
            [
                'ct2_campaign_id' => $ct2CampaignId,
                'approval_status' => $ct2Status,
                'status' => $ct2CampaignStatus,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_campaign_id, campaign_name, campaign_code
             FROM ct2_campaigns
             WHERE status <> "archived"
             ORDER BY campaign_name ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getSummaryCounts(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT
                COUNT(*) AS total_campaigns,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) AS active_campaigns,
                SUM(CASE WHEN approval_status = "pending" THEN 1 ELSE 0 END) AS pending_campaigns,
                COALESCE((
                    SELECT SUM(attributed_revenue)
                    FROM ct2_campaign_metrics
                ), 0) AS attributed_revenue
             FROM ct2_campaigns'
        );

        return $ct2Statement->fetch() ?: [
            'total_campaigns' => 0,
            'active_campaigns' => 0,
            'pending_campaigns' => 0,
            'attributed_revenue' => 0,
        ];
    }

    public function getTopCampaigns(int $ct2Limit = 5): array
    {
        $ct2Limit = max(1, $ct2Limit);
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT c.campaign_name,
                c.status,
                COALESCE(SUM(m.conversion_count), 0) AS total_conversions,
                COALESCE(SUM(m.attributed_revenue), 0) AS attributed_revenue
             FROM ct2_campaigns AS c
             LEFT JOIN ct2_campaign_metrics AS m ON m.ct2_campaign_id = c.ct2_campaign_id
             GROUP BY c.ct2_campaign_id, c.campaign_name, c.status
             ORDER BY attributed_revenue DESC, total_conversions DESC, c.campaign_name ASC
             LIMIT ' . $ct2Limit
        );

        return $ct2Statement->fetchAll();
    }
}
