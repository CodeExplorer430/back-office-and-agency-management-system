<?php

declare(strict_types=1);

abstract class CT2_BaseModel
{
    protected PDO $ct2Pdo;

    public function __construct()
    {
        $this->ct2Pdo = CT2_Database::getConnection();
    }
}
