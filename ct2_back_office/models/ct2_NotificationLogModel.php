<?php

declare(strict_types=1);

final class CT2_NotificationLogModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT nl.*, va.application_reference
             FROM ct2_notification_logs AS nl
             INNER JOIN ct2_visa_applications AS va ON va.ct2_visa_application_id = nl.ct2_visa_application_id
             ORDER BY nl.created_at DESC, nl.ct2_notification_log_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2SentAt = null;
        if ($ct2Payload['delivery_status'] === 'sent') {
            $ct2SentAt = date('Y-m-d H:i:s');
        }

        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_notification_logs (
                ct2_visa_application_id, notification_channel, recipient_reference, notification_subject,
                notification_message, delivery_status, sent_at, created_by
            ) VALUES (
                :ct2_visa_application_id, :notification_channel, :recipient_reference, :notification_subject,
                :notification_message, :delivery_status, :sent_at, :created_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_visa_application_id' => $ct2Payload['ct2_visa_application_id'],
                'notification_channel' => $ct2Payload['notification_channel'],
                'recipient_reference' => $ct2Payload['recipient_reference'],
                'notification_subject' => $ct2Payload['notification_subject'],
                'notification_message' => $ct2Payload['notification_message'],
                'delivery_status' => $ct2Payload['delivery_status'],
                'sent_at' => $ct2SentAt,
                'created_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
