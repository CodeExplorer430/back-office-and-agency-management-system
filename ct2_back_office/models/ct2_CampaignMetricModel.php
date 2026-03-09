<?php

declare(strict_types=1);

final class CT2_CampaignMetricModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT m.*, c.campaign_name
             FROM ct2_campaign_metrics AS m
             INNER JOIN ct2_campaigns AS c ON c.ct2_campaign_id = m.ct2_campaign_id
             ORDER BY m.report_date DESC, m.ct2_campaign_metric_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_campaign_metrics (
                ct2_campaign_id, report_date, impressions_count, click_count, lead_count,
                conversion_count, attributed_revenue, positive_reviews, neutral_reviews,
                negative_reviews, external_review_batch_id, source_system, created_by
            ) VALUES (
                :ct2_campaign_id, :report_date, :impressions_count, :click_count, :lead_count,
                :conversion_count, :attributed_revenue, :positive_reviews, :neutral_reviews,
                :negative_reviews, :external_review_batch_id, :source_system, :created_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_campaign_id' => $ct2Payload['ct2_campaign_id'],
                'report_date' => $ct2Payload['report_date'],
                'impressions_count' => $ct2Payload['impressions_count'],
                'click_count' => $ct2Payload['click_count'],
                'lead_count' => $ct2Payload['lead_count'],
                'conversion_count' => $ct2Payload['conversion_count'],
                'attributed_revenue' => $ct2Payload['attributed_revenue'],
                'positive_reviews' => $ct2Payload['positive_reviews'],
                'neutral_reviews' => $ct2Payload['neutral_reviews'],
                'negative_reviews' => $ct2Payload['negative_reviews'],
                'external_review_batch_id' => $ct2Payload['external_review_batch_id'] ?: null,
                'source_system' => $ct2Payload['source_system'] ?: null,
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
