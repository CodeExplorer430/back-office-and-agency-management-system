<?php

declare(strict_types=1);

abstract class CT2_BaseModel
{
    protected PDO $ct2Pdo;

    public function __construct()
    {
        $this->ct2Pdo = CT2_Database::getConnection();
    }

    protected function ct2BuildLikeFilter(array $ct2Columns, string $ct2Search, string $ct2Prefix = 'search'): array
    {
        $ct2Conditions = [];
        $ct2Parameters = [];
        $ct2SearchValue = '%' . $ct2Search . '%';

        foreach (array_values($ct2Columns) as $ct2Index => $ct2Column) {
            $ct2ParameterKey = $ct2Prefix . '_' . $ct2Index;
            $ct2Conditions[] = $ct2Column . ' LIKE :' . $ct2ParameterKey;
            $ct2Parameters[$ct2ParameterKey] = $ct2SearchValue;
        }

        return [
            'sql' => implode(' OR ', $ct2Conditions),
            'params' => $ct2Parameters,
        ];
    }
}
