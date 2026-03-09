<?php

declare(strict_types=1);

final class CT2_Database
{
    private static ?PDO $ct2Connection = null;

    public static function getConfig(): array
    {
        $ct2Config = [
            'host' => getenv('CT2_DB_HOST') ?: '127.0.0.1',
            'port' => getenv('CT2_DB_PORT') ?: '3306',
            'name' => getenv('CT2_DB_NAME') ?: 'ct2_back_office',
            'username' => getenv('CT2_DB_USER') ?: 'root',
            'password' => getenv('CT2_DB_PASS') ?: '',
            'charset' => 'utf8mb4',
        ];

        $ct2LocalConfigFile = __DIR__ . '/ct2_local.php';
        if (is_file($ct2LocalConfigFile)) {
            $ct2LocalConfig = require $ct2LocalConfigFile;
            if (is_array($ct2LocalConfig)) {
                $ct2Config = array_merge($ct2Config, $ct2LocalConfig);
            }
        }

        $ct2EnvironmentOverrides = [
            'host' => getenv('CT2_DB_HOST'),
            'port' => getenv('CT2_DB_PORT'),
            'name' => getenv('CT2_DB_NAME'),
            'username' => getenv('CT2_DB_USER'),
            'password' => getenv('CT2_DB_PASS'),
            'charset' => getenv('CT2_DB_CHARSET'),
        ];

        foreach ($ct2EnvironmentOverrides as $ct2Key => $ct2Value) {
            if ($ct2Value !== false) {
                $ct2Config[$ct2Key] = $ct2Value;
            }
        }

        return $ct2Config;
    }

    public static function getConnection(): PDO
    {
        if (self::$ct2Connection instanceof PDO) {
            return self::$ct2Connection;
        }

        $ct2Config = self::getConfig();

        $ct2Dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $ct2Config['host'],
            $ct2Config['port'],
            $ct2Config['name'],
            $ct2Config['charset']
        );

        self::$ct2Connection = new PDO(
            $ct2Dsn,
            $ct2Config['username'],
            $ct2Config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$ct2Connection;
    }
}
