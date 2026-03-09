<?php

declare(strict_types=1);

final class CT2_AuditLogModel extends CT2_BaseModel
{
    public function recordAudit(?int $ct2UserId, string $ct2EntityType, ?int $ct2EntityId, string $ct2ActionKey, array $ct2Details = []): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_audit_logs (
                ct2_user_id, entity_type, entity_id, action_key, details_json, ip_address
             ) VALUES (
                :ct2_user_id, :entity_type, :entity_id, :action_key, :details_json, :ip_address
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_user_id' => $ct2UserId,
                'entity_type' => $ct2EntityType,
                'entity_id' => $ct2EntityId,
                'action_key' => $ct2ActionKey,
                'details_json' => $ct2Details === [] ? null : json_encode($ct2Details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ]
        );
    }

    public function recordApi(?int $ct2UserId, string $ct2EndpointName, string $ct2Method, int $ct2StatusCode, array $ct2RequestSummary = [], array $ct2ResponseSummary = []): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_api_logs (
                ct2_user_id, endpoint_name, http_method, status_code, request_summary, response_summary
             ) VALUES (
                :ct2_user_id, :endpoint_name, :http_method, :status_code, :request_summary, :response_summary
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_user_id' => $ct2UserId,
                'endpoint_name' => $ct2EndpointName,
                'http_method' => strtoupper($ct2Method),
                'status_code' => $ct2StatusCode,
                'request_summary' => $ct2RequestSummary === [] ? null : json_encode($ct2RequestSummary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'response_summary' => $ct2ResponseSummary === [] ? null : json_encode($ct2ResponseSummary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]
        );
    }
}
