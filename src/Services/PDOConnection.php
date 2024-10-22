<?php

declare(strict_types=1);

namespace Services;

use PDO;

class PDOConnection
{
    private PDO $connection;

    public function __construct(ConfigService $configService)
    {
        $databaseConfig = $configService->databaseConfig;

        $host = $databaseConfig['MYSQL_HOST'];
        $dbName = $databaseConfig['MYSQL_DB'];
        $username = $databaseConfig['MYSQL_USERNAME'];
        $password = $databaseConfig['MYSQL_PASSWORD'];
        $port = $databaseConfig['MYSQL_PORT'];

        $this->connection = new PDO("mysql:host=$host; port=$port; dbname=$dbName; charset=UTF8", $username, $password);
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
