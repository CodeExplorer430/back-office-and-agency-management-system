<?php

declare(strict_types=1);

final class CT2_FinancialAnalyticsModel extends CT2_BaseModel
{
    public function createRun(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_report_runs (
                ct2_financial_report_id, run_label, date_from, date_to, module_key, source_system, generated_by
            ) VALUES (
                :ct2_financial_report_id, :run_label, :date_from, :date_to, :module_key, :source_system, :generated_by
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_financial_report_id' => $ct2Payload['ct2_financial_report_id'],
                'run_label' => $ct2Payload['run_label'],
                'date_from' => $ct2Payload['date_from'],
                'date_to' => $ct2Payload['date_to'],
                'module_key' => $ct2Payload['module_key'],
                'source_system' => $ct2Payload['source_system'] !== '' ? $ct2Payload['source_system'] : null,
                'generated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }

    public function getRuns(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT rr.*,
                fr.report_name,
                u.display_name AS generated_by_name,
                COALESCE(snapshot_summary.snapshot_count, 0) AS snapshot_count,
                COALESCE(flag_summary.open_flag_count, 0) AS open_flag_count
             FROM ct2_report_runs AS rr
             INNER JOIN ct2_financial_reports AS fr ON fr.ct2_financial_report_id = rr.ct2_financial_report_id
             LEFT JOIN ct2_users AS u ON u.ct2_user_id = rr.generated_by
             LEFT JOIN (
                SELECT ct2_report_run_id, COUNT(*) AS snapshot_count
                FROM ct2_financial_snapshots
                GROUP BY ct2_report_run_id
             ) AS snapshot_summary ON snapshot_summary.ct2_report_run_id = rr.ct2_report_run_id
             LEFT JOIN (
                SELECT ct2_report_run_id, COUNT(*) AS open_flag_count
                FROM ct2_reconciliation_flags
                WHERE flag_status = "open"
                GROUP BY ct2_report_run_id
             ) AS flag_summary ON flag_summary.ct2_report_run_id = rr.ct2_report_run_id
             ORDER BY rr.generated_at DESC, rr.ct2_report_run_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function getSnapshots(?int $ct2ReportRunId = null, ?string $ct2SourceModule = null): array
    {
        $ct2Sql = 'SELECT fs.*, rr.run_label, fr.report_name
            FROM ct2_financial_snapshots AS fs
            INNER JOIN ct2_report_runs AS rr ON rr.ct2_report_run_id = fs.ct2_report_run_id
            INNER JOIN ct2_financial_reports AS fr ON fr.ct2_financial_report_id = rr.ct2_financial_report_id
            WHERE 1 = 1';
        $ct2Parameters = [];

        if ($ct2ReportRunId !== null && $ct2ReportRunId > 0) {
            $ct2Sql .= ' AND fs.ct2_report_run_id = :ct2_report_run_id';
            $ct2Parameters['ct2_report_run_id'] = $ct2ReportRunId;
        }

        if ($ct2SourceModule !== null && $ct2SourceModule !== '') {
            $ct2Sql .= ' AND fs.source_module = :source_module';
            $ct2Parameters['source_module'] = $ct2SourceModule;
        }

        $ct2Sql .= ' ORDER BY fs.created_at DESC, fs.ct2_financial_snapshot_id DESC';
        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function getFlags(?int $ct2ReportRunId = null, ?string $ct2FlagStatus = null, ?string $ct2SourceModule = null): array
    {
        $ct2Sql = 'SELECT rf.*, rr.run_label, fr.report_name
            FROM ct2_reconciliation_flags AS rf
            INNER JOIN ct2_report_runs AS rr ON rr.ct2_report_run_id = rf.ct2_report_run_id
            INNER JOIN ct2_financial_reports AS fr ON fr.ct2_financial_report_id = rr.ct2_financial_report_id
            WHERE 1 = 1';
        $ct2Parameters = [];

        if ($ct2ReportRunId !== null && $ct2ReportRunId > 0) {
            $ct2Sql .= ' AND rf.ct2_report_run_id = :ct2_report_run_id';
            $ct2Parameters['ct2_report_run_id'] = $ct2ReportRunId;
        }

        if ($ct2FlagStatus !== null && $ct2FlagStatus !== '') {
            $ct2Sql .= ' AND rf.flag_status = :flag_status';
            $ct2Parameters['flag_status'] = $ct2FlagStatus;
        }

        if ($ct2SourceModule !== null && $ct2SourceModule !== '') {
            $ct2Sql .= ' AND rf.source_module = :source_module';
            $ct2Parameters['source_module'] = $ct2SourceModule;
        }

        $ct2Sql .= ' ORDER BY rf.created_at DESC, rf.ct2_reconciliation_flag_id DESC';
        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);

        return $ct2Statement->fetchAll();
    }

    public function updateFlag(int $ct2ReconciliationFlagId, array $ct2Payload, int $ct2UserId): void
    {
        $ct2ResolvedAt = $ct2Payload['flag_status'] === 'resolved' ? date('Y-m-d H:i:s') : null;
        $ct2ResolvedBy = $ct2Payload['flag_status'] === 'resolved' ? $ct2UserId : null;

        $ct2Statement = $this->ct2Pdo->prepare(
            'UPDATE ct2_reconciliation_flags
             SET flag_status = :flag_status,
                 resolution_notes = :resolution_notes,
                 resolved_by = :resolved_by,
                 resolved_at = :resolved_at
             WHERE ct2_reconciliation_flag_id = :ct2_reconciliation_flag_id'
        );
        $ct2Statement->execute(
            [
                'ct2_reconciliation_flag_id' => $ct2ReconciliationFlagId,
                'flag_status' => $ct2Payload['flag_status'],
                'resolution_notes' => $ct2Payload['resolution_notes'] !== '' ? $ct2Payload['resolution_notes'] : null,
                'resolved_by' => $ct2ResolvedBy,
                'resolved_at' => $ct2ResolvedAt,
            ]
        );
    }

    public function getSummary(): array
    {
        $ct2LatestRunId = $this->getLatestRunId();
        if ($ct2LatestRunId === null) {
            return [
                'latest_run_id' => 0,
                'snapshot_count' => 0,
                'critical_snapshots' => 0,
                'open_flags' => 0,
                'total_metric_value' => 0,
            ];
        }

        $ct2SnapshotStatement = $this->ct2Pdo->prepare(
            'SELECT
                COUNT(*) AS snapshot_count,
                SUM(CASE WHEN status_flag = "critical" THEN 1 ELSE 0 END) AS critical_snapshots,
                COALESCE(SUM(metric_value), 0) AS total_metric_value
             FROM ct2_financial_snapshots
             WHERE ct2_report_run_id = :ct2_report_run_id'
        );
        $ct2SnapshotStatement->execute(['ct2_report_run_id' => $ct2LatestRunId]);
        $ct2SnapshotSummary = $ct2SnapshotStatement->fetch() ?: [];

        $ct2FlagStatement = $this->ct2Pdo->prepare(
            'SELECT COUNT(*) AS open_flags
             FROM ct2_reconciliation_flags
             WHERE ct2_report_run_id = :ct2_report_run_id
               AND flag_status = "open"'
        );
        $ct2FlagStatement->execute(['ct2_report_run_id' => $ct2LatestRunId]);
        $ct2FlagSummary = $ct2FlagStatement->fetch() ?: [];

        return [
            'latest_run_id' => $ct2LatestRunId,
            'snapshot_count' => (int) ($ct2SnapshotSummary['snapshot_count'] ?? 0),
            'critical_snapshots' => (int) ($ct2SnapshotSummary['critical_snapshots'] ?? 0),
            'open_flags' => (int) ($ct2FlagSummary['open_flags'] ?? 0),
            'total_metric_value' => (float) ($ct2SnapshotSummary['total_metric_value'] ?? 0),
        ];
    }

    public function getExportRows(int $ct2ReportRunId, ?string $ct2SourceModule = null): array
    {
        return $this->getSnapshots($ct2ReportRunId, $ct2SourceModule);
    }

    public function generateRun(int $ct2ReportRunId, array $ct2Filters): array
    {
        $ct2Modules = $this->resolveModules((int) $ct2Filters['ct2_financial_report_id'], (string) $ct2Filters['module_key']);
        $ct2SnapshotCount = 0;
        $ct2FlagCount = 0;

        $this->ct2Pdo->beginTransaction();

        try {
            $this->clearRunData($ct2ReportRunId);

            foreach ($ct2Modules as $ct2Module) {
                if ($ct2Module === 'agents') {
                    ['snapshots' => $ct2Snapshots, 'flags' => $ct2Flags] = $this->generateAgentSnapshots($ct2ReportRunId, $ct2Filters);
                } elseif ($ct2Module === 'suppliers') {
                    ['snapshots' => $ct2Snapshots, 'flags' => $ct2Flags] = $this->generateSupplierSnapshots($ct2ReportRunId, $ct2Filters);
                } elseif ($ct2Module === 'availability') {
                    ['snapshots' => $ct2Snapshots, 'flags' => $ct2Flags] = $this->generateAvailabilitySnapshots($ct2ReportRunId);
                } elseif ($ct2Module === 'marketing') {
                    ['snapshots' => $ct2Snapshots, 'flags' => $ct2Flags] = $this->generateMarketingSnapshots($ct2ReportRunId, $ct2Filters);
                } elseif ($ct2Module === 'visa') {
                    ['snapshots' => $ct2Snapshots, 'flags' => $ct2Flags] = $this->generateVisaSnapshots($ct2ReportRunId, $ct2Filters);
                } else {
                    continue;
                }

                $ct2SnapshotCount += $ct2Snapshots;
                $ct2FlagCount += $ct2Flags;
            }

            ['snapshots' => $ct2CrossSnapshots, 'flags' => $ct2CrossFlags] = $this->generateCrossModuleSnapshot($ct2ReportRunId);
            $ct2SnapshotCount += $ct2CrossSnapshots;
            $ct2FlagCount += $ct2CrossFlags;

            $this->ct2Pdo->commit();

            return [
                'snapshot_count' => $ct2SnapshotCount,
                'flag_count' => $ct2FlagCount,
            ];
        } catch (Throwable $ct2Exception) {
            $this->ct2Pdo->rollBack();
            throw $ct2Exception;
        }
    }

    private function resolveModules(int $ct2FinancialReportId, string $ct2ModuleKey): array
    {
        if ($ct2ModuleKey !== 'all' && $ct2ModuleKey !== '') {
            return [$ct2ModuleKey];
        }

        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT report_scope
             FROM ct2_financial_reports
             WHERE ct2_financial_report_id = :ct2_financial_report_id
             LIMIT 1'
        );
        $ct2Statement->execute(['ct2_financial_report_id' => $ct2FinancialReportId]);
        $ct2Report = $ct2Statement->fetch();

        if ($ct2Report === false) {
            return ['agents', 'suppliers', 'availability', 'marketing', 'visa'];
        }

        $ct2Scope = (string) $ct2Report['report_scope'];
        if ($ct2Scope === 'cross_module') {
            return ['agents', 'suppliers', 'availability', 'marketing', 'visa'];
        }

        return [$ct2Scope];
    }

    private function clearRunData(int $ct2ReportRunId): void
    {
        $ct2FlagDelete = $this->ct2Pdo->prepare(
            'DELETE FROM ct2_reconciliation_flags
             WHERE ct2_report_run_id = :ct2_report_run_id'
        );
        $ct2FlagDelete->execute(['ct2_report_run_id' => $ct2ReportRunId]);

        $ct2SnapshotDelete = $this->ct2Pdo->prepare(
            'DELETE FROM ct2_financial_snapshots
             WHERE ct2_report_run_id = :ct2_report_run_id'
        );
        $ct2SnapshotDelete->execute(['ct2_report_run_id' => $ct2ReportRunId]);
    }

    private function generateAgentSnapshots(int $ct2ReportRunId, array $ct2Filters): array
    {
        $ct2Sql = 'SELECT ct2_agent_id, agent_code, agency_name, commission_rate, approval_status, active_status, external_payment_id, source_system
            FROM ct2_agents
            WHERE 1 = 1';
        $ct2Parameters = [];
        if ($ct2Filters['source_system'] !== '') {
            $ct2Sql .= ' AND source_system = :source_system';
            $ct2Parameters['source_system'] = $ct2Filters['source_system'];
        }

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);
        $ct2Agents = $ct2Statement->fetchAll();

        $ct2SnapshotCount = 0;
        $ct2FlagCount = 0;
        foreach ($ct2Agents as $ct2Agent) {
            $ct2StatusFlag = ($ct2Agent['approval_status'] === 'approved' && $ct2Agent['active_status'] === 'active') ? 'ok' : 'warning';
            $this->insertSnapshot(
                $ct2ReportRunId,
                'agent_commission',
                (string) $ct2Agent['agent_code'],
                'agents',
                (int) $ct2Agent['ct2_agent_id'],
                'commission_rate',
                (float) $ct2Agent['commission_rate'],
                1,
                $ct2StatusFlag,
                $ct2Agent['external_payment_id'] !== null ? (string) $ct2Agent['external_payment_id'] : '',
                'Agency: ' . (string) $ct2Agent['agency_name']
            );
            $ct2SnapshotCount++;

            if ($ct2Agent['approval_status'] === 'approved' && ($ct2Agent['external_payment_id'] === null || $ct2Agent['external_payment_id'] === '')) {
                $this->insertFlag(
                    $ct2ReportRunId,
                    'missing_payment',
                    'agents',
                    (int) $ct2Agent['ct2_agent_id'],
                    'medium',
                    'Open',
                    'Approved agent is missing an external payment reference.'
                );
                $ct2FlagCount++;
            }
        }

        return ['snapshots' => $ct2SnapshotCount, 'flags' => $ct2FlagCount];
    }

    private function generateSupplierSnapshots(int $ct2ReportRunId, array $ct2Filters): array
    {
        $ct2Sql = 'SELECT s.ct2_supplier_id, s.supplier_code, s.supplier_name, s.active_status, s.risk_level, s.external_supplier_id, s.source_system,
                COALESCE(resource_summary.total_resource_cost, 0) AS total_resource_cost,
                COALESCE(resource_summary.resource_count, 0) AS resource_count,
                COALESCE(contract_summary.expired_contracts, 0) AS expired_contracts
            FROM ct2_suppliers AS s
            LEFT JOIN (
                SELECT ct2_supplier_id, SUM(base_cost) AS total_resource_cost, COUNT(*) AS resource_count
                FROM ct2_inventory_resources
                GROUP BY ct2_supplier_id
            ) AS resource_summary ON resource_summary.ct2_supplier_id = s.ct2_supplier_id
            LEFT JOIN (
                SELECT ct2_supplier_id, SUM(CASE WHEN expiry_date < CURDATE() OR renewal_status IN ("renewal_due", "expired") THEN 1 ELSE 0 END) AS expired_contracts
                FROM ct2_supplier_contracts
                GROUP BY ct2_supplier_id
            ) AS contract_summary ON contract_summary.ct2_supplier_id = s.ct2_supplier_id
            WHERE 1 = 1';
        $ct2Parameters = [];

        if ($ct2Filters['source_system'] !== '') {
            $ct2Sql .= ' AND s.source_system = :source_system';
            $ct2Parameters['source_system'] = $ct2Filters['source_system'];
        }

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);
        $ct2Suppliers = $ct2Statement->fetchAll();

        $ct2SnapshotCount = 0;
        $ct2FlagCount = 0;
        foreach ($ct2Suppliers as $ct2Supplier) {
            $ct2StatusFlag = 'ok';
            if ((int) $ct2Supplier['expired_contracts'] > 0 || (string) $ct2Supplier['risk_level'] === 'high') {
                $ct2StatusFlag = 'critical';
            } elseif ((string) $ct2Supplier['risk_level'] === 'medium') {
                $ct2StatusFlag = 'warning';
            }

            $this->insertSnapshot(
                $ct2ReportRunId,
                'supplier_exposure',
                (string) $ct2Supplier['supplier_code'],
                'suppliers',
                (int) $ct2Supplier['ct2_supplier_id'],
                'resource_cost_exposure',
                (float) $ct2Supplier['total_resource_cost'],
                (int) $ct2Supplier['resource_count'],
                $ct2StatusFlag,
                $ct2Supplier['external_supplier_id'] !== null ? (string) $ct2Supplier['external_supplier_id'] : '',
                'Supplier: ' . (string) $ct2Supplier['supplier_name']
            );
            $ct2SnapshotCount++;

            if ((string) $ct2Supplier['active_status'] === 'active' && ($ct2Supplier['external_supplier_id'] === null || $ct2Supplier['external_supplier_id'] === '')) {
                $this->insertFlag(
                    $ct2ReportRunId,
                    'supplier_mismatch',
                    'suppliers',
                    (int) $ct2Supplier['ct2_supplier_id'],
                    'medium',
                    'Open',
                    'Active supplier is missing an external supplier identifier.'
                );
                $ct2FlagCount++;
            }

            if ((int) $ct2Supplier['expired_contracts'] > 0) {
                $this->insertFlag(
                    $ct2ReportRunId,
                    'expired_contract',
                    'suppliers',
                    (int) $ct2Supplier['ct2_supplier_id'],
                    'high',
                    'Open',
                    'Supplier has one or more expired or renewal-due contracts.'
                );
                $ct2FlagCount++;
            }
        }

        return ['snapshots' => $ct2SnapshotCount, 'flags' => $ct2FlagCount];
    }

    private function generateAvailabilitySnapshots(int $ct2ReportRunId): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT p.ct2_package_id, p.package_name,
                ROUND(p.base_price + (p.base_price * (p.margin_percentage / 100)), 2) AS selling_price,
                COALESCE(resource_costs.total_resource_cost, 0) AS total_resource_cost,
                COALESCE(resource_costs.resource_count, 0) AS resource_count
             FROM ct2_tour_packages AS p
             LEFT JOIN (
                SELECT pr.ct2_package_id, SUM(pr.units_required * r.base_cost) AS total_resource_cost, COUNT(*) AS resource_count
                FROM ct2_package_resources AS pr
                INNER JOIN ct2_inventory_resources AS r ON r.ct2_resource_id = pr.ct2_resource_id
                GROUP BY pr.ct2_package_id
             ) AS resource_costs ON resource_costs.ct2_package_id = p.ct2_package_id
             ORDER BY p.package_name ASC'
        );
        $ct2Packages = $ct2Statement->fetchAll();

        $ct2SnapshotCount = 0;
        $ct2FlagCount = 0;
        foreach ($ct2Packages as $ct2Package) {
            $ct2Margin = (float) $ct2Package['selling_price'] - (float) $ct2Package['total_resource_cost'];
            $ct2StatusFlag = $ct2Margin < 0 ? 'critical' : ($ct2Margin < 50 ? 'warning' : 'ok');

            $this->insertSnapshot(
                $ct2ReportRunId,
                'resource_margin',
                'PKG-' . (string) $ct2Package['ct2_package_id'],
                'availability',
                (int) $ct2Package['ct2_package_id'],
                'package_margin',
                $ct2Margin,
                (int) $ct2Package['resource_count'],
                $ct2StatusFlag,
                '',
                'Package: ' . (string) $ct2Package['package_name']
            );
            $ct2SnapshotCount++;

            if ($ct2Margin < 0) {
                $this->insertFlag(
                    $ct2ReportRunId,
                    'margin_alert',
                    'availability',
                    (int) $ct2Package['ct2_package_id'],
                    'high',
                    'Open',
                    'Package margin is negative against linked resource costs.'
                );
                $ct2FlagCount++;
            }
        }

        return ['snapshots' => $ct2SnapshotCount, 'flags' => $ct2FlagCount];
    }

    private function generateMarketingSnapshots(int $ct2ReportRunId, array $ct2Filters): array
    {
        $ct2Sql = 'SELECT c.ct2_campaign_id, c.campaign_code, c.campaign_name, c.budget_amount, c.external_customer_segment_id, c.source_system,
                COALESCE(SUM(m.attributed_revenue), 0) AS total_revenue,
                COALESCE(SUM(m.conversion_count), 0) AS total_conversions
            FROM ct2_campaigns AS c
            LEFT JOIN ct2_campaign_metrics AS m
                ON m.ct2_campaign_id = c.ct2_campaign_id';
        $ct2Conditions = [];
        $ct2Parameters = [];

        if ($ct2Filters['date_from'] !== '' && $ct2Filters['date_to'] !== '') {
            $ct2Conditions[] = '(m.report_date BETWEEN :date_from AND :date_to OR m.report_date IS NULL)';
            $ct2Parameters['date_from'] = $ct2Filters['date_from'];
            $ct2Parameters['date_to'] = $ct2Filters['date_to'];
        }

        if ($ct2Filters['source_system'] !== '') {
            $ct2Conditions[] = '(c.source_system = :source_system OR c.source_system IS NULL)';
            $ct2Parameters['source_system'] = $ct2Filters['source_system'];
        }

        if ($ct2Conditions !== []) {
            $ct2Sql .= ' WHERE ' . implode(' AND ', $ct2Conditions);
        }

        $ct2Sql .= ' GROUP BY c.ct2_campaign_id, c.campaign_code, c.campaign_name, c.budget_amount, c.external_customer_segment_id, c.source_system
            ORDER BY c.campaign_name ASC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);
        $ct2Campaigns = $ct2Statement->fetchAll();

        $ct2SnapshotCount = 0;
        $ct2FlagCount = 0;
        foreach ($ct2Campaigns as $ct2Campaign) {
            $ct2NetValue = (float) $ct2Campaign['total_revenue'] - (float) $ct2Campaign['budget_amount'];
            $ct2StatusFlag = $ct2NetValue < 0 ? 'warning' : 'ok';

            $this->insertSnapshot(
                $ct2ReportRunId,
                'marketing_roi',
                (string) $ct2Campaign['campaign_code'],
                'marketing',
                (int) $ct2Campaign['ct2_campaign_id'],
                'net_campaign_value',
                $ct2NetValue,
                (int) $ct2Campaign['total_conversions'],
                $ct2StatusFlag,
                $ct2Campaign['external_customer_segment_id'] !== null ? (string) $ct2Campaign['external_customer_segment_id'] : '',
                'Campaign: ' . (string) $ct2Campaign['campaign_name']
            );
            $ct2SnapshotCount++;

            if ($ct2NetValue < 0) {
                $this->insertFlag(
                    $ct2ReportRunId,
                    'margin_alert',
                    'marketing',
                    (int) $ct2Campaign['ct2_campaign_id'],
                    'medium',
                    'Open',
                    'Campaign attributed revenue is below budget.'
                );
                $ct2FlagCount++;
            }
        }

        $ct2RedemptionStatement = $this->ct2Pdo->query(
            'SELECT ct2_redemption_log_id
             FROM ct2_redemption_logs
             WHERE redemption_status = "redeemed"
               AND (external_booking_id IS NULL OR external_booking_id = "")'
        );
        $ct2UnmappedRedemptions = $ct2RedemptionStatement->fetchAll();
        foreach ($ct2UnmappedRedemptions as $ct2Redemption) {
            $this->insertFlag(
                $ct2ReportRunId,
                'unmapped_reference',
                'marketing',
                (int) $ct2Redemption['ct2_redemption_log_id'],
                'medium',
                'Open',
                'Redeemed marketing record is missing an external booking reference.'
            );
            $ct2FlagCount++;
        }

        return ['snapshots' => $ct2SnapshotCount, 'flags' => $ct2FlagCount];
    }

    private function generateVisaSnapshots(int $ct2ReportRunId, array $ct2Filters): array
    {
        $ct2Sql = 'SELECT va.ct2_visa_application_id, va.application_reference, va.external_customer_id, va.payment_status, va.status, vt.base_fee,
                COALESCE(SUM(CASE WHEN vp.payment_status = "completed" THEN vp.amount ELSE 0 END), 0) AS total_paid,
                MAX(vp.external_payment_id) AS external_payment_id
            FROM ct2_visa_applications AS va
            INNER JOIN ct2_visa_types AS vt ON vt.ct2_visa_type_id = va.ct2_visa_type_id
            LEFT JOIN ct2_visa_payments AS vp ON vp.ct2_visa_application_id = va.ct2_visa_application_id';
        $ct2Conditions = [];
        $ct2Parameters = [];

        if ($ct2Filters['date_from'] !== '' && $ct2Filters['date_to'] !== '') {
            $ct2Conditions[] = '(va.submission_date BETWEEN :date_from AND :date_to)';
            $ct2Parameters['date_from'] = $ct2Filters['date_from'];
            $ct2Parameters['date_to'] = $ct2Filters['date_to'];
        }

        if ($ct2Conditions !== []) {
            $ct2Sql .= ' WHERE ' . implode(' AND ', $ct2Conditions);
        }

        $ct2Sql .= ' GROUP BY va.ct2_visa_application_id, va.application_reference, va.external_customer_id, va.payment_status, va.status, vt.base_fee
            ORDER BY va.application_reference ASC';

        $ct2Statement = $this->ct2Pdo->prepare($ct2Sql);
        $ct2Statement->execute($ct2Parameters);
        $ct2Applications = $ct2Statement->fetchAll();

        $ct2SnapshotCount = 0;
        $ct2FlagCount = 0;
        foreach ($ct2Applications as $ct2Application) {
            $ct2PaymentDelta = (float) $ct2Application['total_paid'] - (float) $ct2Application['base_fee'];
            $ct2StatusFlag = (string) $ct2Application['payment_status'] === 'paid'
                ? 'ok'
                : ((string) $ct2Application['payment_status'] === 'partial' ? 'warning' : 'critical');

            $this->insertSnapshot(
                $ct2ReportRunId,
                'visa_payments',
                (string) $ct2Application['application_reference'],
                'visa',
                (int) $ct2Application['ct2_visa_application_id'],
                'payment_delta',
                $ct2PaymentDelta,
                1,
                $ct2StatusFlag,
                $ct2Application['external_payment_id'] !== null ? (string) $ct2Application['external_payment_id'] : '',
                'External customer: ' . (string) $ct2Application['external_customer_id']
            );
            $ct2SnapshotCount++;

            if (!in_array((string) $ct2Application['status'], ['rejected', 'cancelled', 'released'], true) && (string) $ct2Application['payment_status'] !== 'paid') {
                $this->insertFlag(
                    $ct2ReportRunId,
                    'unpaid_case',
                    'visa',
                    (int) $ct2Application['ct2_visa_application_id'],
                    'high',
                    'Open',
                    'Visa application is still open without full payment coverage.'
                );
                $ct2FlagCount++;
            }
        }

        return ['snapshots' => $ct2SnapshotCount, 'flags' => $ct2FlagCount];
    }

    private function generateCrossModuleSnapshot(int $ct2ReportRunId): array
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'SELECT COALESCE(SUM(metric_value), 0) AS total_metric_value, COUNT(*) AS total_rows
             FROM ct2_financial_snapshots
             WHERE ct2_report_run_id = :ct2_report_run_id
               AND source_module IN ("availability", "marketing", "visa")'
        );
        $ct2Statement->execute(['ct2_report_run_id' => $ct2ReportRunId]);
        $ct2Summary = $ct2Statement->fetch() ?: ['total_metric_value' => 0, 'total_rows' => 0];

        $ct2MetricValue = (float) ($ct2Summary['total_metric_value'] ?? 0);
        $ct2StatusFlag = $ct2MetricValue < 0 ? 'warning' : 'ok';

        $this->insertSnapshot(
            $ct2ReportRunId,
            'cross_module_margin',
            'CT2-OPERATIONS',
            'cross_module',
            null,
            'aggregate_operational_value',
            $ct2MetricValue,
            (int) ($ct2Summary['total_rows'] ?? 0),
            $ct2StatusFlag,
            '',
            'Aggregated from availability, marketing, and visa snapshots.'
        );

        return ['snapshots' => 1, 'flags' => 0];
    }

    private function insertSnapshot(
        int $ct2ReportRunId,
        string $ct2SnapshotType,
        string $ct2ReferenceCode,
        string $ct2SourceModule,
        ?int $ct2SourceRecordId,
        string $ct2MetricLabel,
        float $ct2MetricValue,
        int $ct2MetricCount,
        string $ct2StatusFlag,
        string $ct2ExternalReferenceId,
        string $ct2Notes
    ): void {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_financial_snapshots (
                ct2_report_run_id, snapshot_type, reference_code, source_module, source_record_id,
                metric_label, metric_value, metric_count, status_flag, external_reference_id, notes
            ) VALUES (
                :ct2_report_run_id, :snapshot_type, :reference_code, :source_module, :source_record_id,
                :metric_label, :metric_value, :metric_count, :status_flag, :external_reference_id, :notes
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_report_run_id' => $ct2ReportRunId,
                'snapshot_type' => $ct2SnapshotType,
                'reference_code' => $ct2ReferenceCode,
                'source_module' => $ct2SourceModule,
                'source_record_id' => $ct2SourceRecordId,
                'metric_label' => $ct2MetricLabel,
                'metric_value' => number_format($ct2MetricValue, 2, '.', ''),
                'metric_count' => $ct2MetricCount,
                'status_flag' => $ct2StatusFlag,
                'external_reference_id' => $ct2ExternalReferenceId !== '' ? $ct2ExternalReferenceId : null,
                'notes' => $ct2Notes !== '' ? $ct2Notes : null,
            ]
        );
    }

    private function insertFlag(
        int $ct2ReportRunId,
        string $ct2FlagType,
        string $ct2SourceModule,
        int $ct2SourceRecordId,
        string $ct2Severity,
        string $ct2StatusLabel,
        string $ct2Summary
    ): void {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_reconciliation_flags (
                ct2_report_run_id, flag_type, source_module, source_record_id, severity,
                flag_status, flag_summary
            ) VALUES (
                :ct2_report_run_id, :flag_type, :source_module, :source_record_id, :severity,
                :flag_status, :flag_summary
            )'
        );
        $ct2Statement->execute(
            [
                'ct2_report_run_id' => $ct2ReportRunId,
                'flag_type' => $ct2FlagType,
                'source_module' => $ct2SourceModule,
                'source_record_id' => $ct2SourceRecordId,
                'severity' => $ct2Severity,
                'flag_status' => strtolower($ct2StatusLabel),
                'flag_summary' => $ct2Summary,
            ]
        );
    }

    private function getLatestRunId(): ?int
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT ct2_report_run_id
             FROM ct2_report_runs
             ORDER BY generated_at DESC, ct2_report_run_id DESC
             LIMIT 1'
        );
        $ct2Run = $ct2Statement->fetch();

        return $ct2Run !== false ? (int) $ct2Run['ct2_report_run_id'] : null;
    }
}
