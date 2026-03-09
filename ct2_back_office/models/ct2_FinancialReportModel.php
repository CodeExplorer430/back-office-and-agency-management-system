<?php

declare(strict_types=1);

final class CT2_FinancialReportModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT fr.*,
                COALESCE(filter_summary.filter_count, 0) AS filter_count,
                COALESCE(run_summary.run_count, 0) AS run_count
             FROM ct2_financial_reports AS fr
             LEFT JOIN (
                SELECT ct2_financial_report_id, COUNT(*) AS filter_count
                FROM ct2_report_filters
                GROUP BY ct2_financial_report_id
             ) AS filter_summary ON filter_summary.ct2_financial_report_id = fr.ct2_financial_report_id
             LEFT JOIN (
                SELECT ct2_financial_report_id, COUNT(*) AS run_count
                FROM ct2_report_runs
                GROUP BY ct2_financial_report_id
             ) AS run_summary ON run_summary.ct2_financial_report_id = fr.ct2_financial_report_id
             ORDER BY fr.created_at DESC, fr.ct2_financial_report_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function findById(int $ct2FinancialReportId): ?array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_financial_reports
             WHERE ct2_financial_report_id = :ct2_financial_report_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_financial_report_id' => $ct2FinancialReportId]);
        $ct2Report = $ct2Statement->fetch();

        return $ct2Report !== false ? $ct2Report : null;
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_financial_reports (
                report_code, report_name, report_scope, report_status, default_date_range,
                definition_notes, created_by, updated_by
            ) VALUES (
                :report_code, :report_name, :report_scope, :report_status, :default_date_range,
                :definition_notes, :created_by, :updated_by
            )'
        );
        $ct2Statement->execute(
            [
                'report_code' => $ct2Payload['report_code'],
                'report_name' => $ct2Payload['report_name'],
                'report_scope' => $ct2Payload['report_scope'],
                'report_status' => $ct2Payload['report_status'],
                'default_date_range' => $ct2Payload['default_date_range'],
                'definition_notes' => $ct2Payload['definition_notes'] ?: null,
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function update(int $ct2FinancialReportId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_financial_reports
             SET report_code = :report_code,
                 report_name = :report_name,
                 report_scope = :report_scope,
                 report_status = :report_status,
                 default_date_range = :default_date_range,
                 definition_notes = :definition_notes,
                 updated_by = :updated_by
             WHERE ct2_financial_report_id = :ct2_financial_report_id'
        );
        $ct2Statement->execute(
            [
                'ct2_financial_report_id' => $ct2FinancialReportId,
                'report_code' => $ct2Payload['report_code'],
                'report_name' => $ct2Payload['report_name'],
                'report_scope' => $ct2Payload['report_scope'],
                'report_status' => $ct2Payload['report_status'],
                'default_date_range' => $ct2Payload['default_date_range'],
                'definition_notes' => $ct2Payload['definition_notes'] ?: null,
                'updated_by' => $ct2UserId,
            ]
        );
    }

    public function getAllForSelection(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_financial_report_id, report_code, report_name, report_scope
             FROM ct2_financial_reports
             WHERE report_status = "active"
             ORDER BY report_name ASC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getFilters(int $ct2FinancialReportId): array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT *
             FROM ct2_report_filters
             WHERE ct2_financial_report_id = :ct2_financial_report_id
             ORDER BY sort_order ASC, ct2_report_filter_id ASC'
        );
        $ct2Statement->execute(['ct2_financial_report_id' => $ct2FinancialReportId]);

        return $ct2Statement->fetchAll();
    }

    public function createFilter(array $ct2Payload): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_report_filters (
                ct2_financial_report_id, filter_key, filter_label, filter_type, default_value, sort_order
            ) VALUES (
                :ct2_financial_report_id, :filter_key, :filter_label, :filter_type, :default_value, :sort_order
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_financial_report_id' => $ct2Payload['ct2_financial_report_id'],
                'filter_key' => $ct2Payload['filter_key'],
                'filter_label' => $ct2Payload['filter_label'],
                'filter_type' => $ct2Payload['filter_type'],
                'default_value' => $ct2Payload['default_value'] ?: null,
                'sort_order' => $ct2Payload['sort_order'],
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
