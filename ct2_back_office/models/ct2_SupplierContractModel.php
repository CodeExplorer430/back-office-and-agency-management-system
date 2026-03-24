<?php

declare(strict_types=1);

final class CT2_SupplierContractModel extends CT2_BaseModel
{
    public function getAll(): array
    {
        $ct2Statement = $this->ct2Pdo->query(
            'SELECT c.*, s.supplier_name, s.supplier_code
             FROM ct2_supplier_contracts AS c
             INNER JOIN ct2_suppliers AS s ON s.ct2_supplier_id = c.ct2_supplier_id
             ORDER BY c.created_at DESC, c.ct2_supplier_contract_id DESC'
        );

        return $ct2Statement->fetchAll();
    }

    public function create(array $ct2Payload, int $ct2UserId): int
    {
        $ct2Statement = $this->ct2Pdo->prepare(
            'INSERT INTO ct2_supplier_contracts (
                ct2_supplier_id, contract_code, contract_title, effective_date, expiry_date,
                renewal_status, contract_status, clause_summary, mock_signature_status,
                finance_handoff_status, created_by, updated_by
             ) VALUES (
                :ct2_supplier_id, :contract_code, :contract_title, :effective_date, :expiry_date,
                :renewal_status, :contract_status, :clause_summary, :mock_signature_status,
                :finance_handoff_status, :created_by, :updated_by
             )'
        );
        $ct2Statement->execute(
            [
                'ct2_supplier_id' => $ct2Payload['ct2_supplier_id'],
                'contract_code' => $ct2Payload['contract_code'],
                'contract_title' => $ct2Payload['contract_title'],
                'effective_date' => $ct2Payload['effective_date'],
                'expiry_date' => $ct2Payload['expiry_date'],
                'renewal_status' => $ct2Payload['renewal_status'],
                'contract_status' => $ct2Payload['contract_status'],
                'clause_summary' => $ct2Payload['clause_summary'] ?: null,
                'mock_signature_status' => $ct2Payload['mock_signature_status'],
                'finance_handoff_status' => $ct2Payload['finance_handoff_status'],
                'created_by' => $ct2UserId,
                'updated_by' => $ct2UserId,
            ]
        );

        return (int) $this->ct2Pdo->lastInsertId();
    }
}
