<?php

declare(strict_types=1);

final class CT2_MarketingNoteModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT n.*,
                c.campaign_name,
                a.affiliate_name
             FROM ct2_marketing_notes AS n
             LEFT JOIN ct2_campaigns AS c ON c.ct2_campaign_id = n.ct2_campaign_id
             LEFT JOIN ct2_affiliates AS a ON a.ct2_affiliate_id = n.ct2_affiliate_id
             ORDER BY n.created_at DESC, n.ct2_marketing_note_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_marketing_notes (
                ct2_campaign_id, ct2_affiliate_id, note_type, note_title, note_body, next_action_date, created_by
            ) VALUES (
                :ct2_campaign_id, :ct2_affiliate_id, :note_type, :note_title, :note_body, :next_action_date, :created_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_campaign_id' => $ct2Payload['ct2_campaign_id'] > 0 ? $ct2Payload['ct2_campaign_id'] : null,
                'ct2_affiliate_id' => $ct2Payload['ct2_affiliate_id'] > 0 ? $ct2Payload['ct2_affiliate_id'] : null,
                'note_type' => $ct2Payload['note_type'],
                'note_title' => $ct2Payload['note_title'],
                'note_body' => $ct2Payload['note_body'],
                'next_action_date' => $ct2Payload['next_action_date'] !== '' ? $ct2Payload['next_action_date'] : null,
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
