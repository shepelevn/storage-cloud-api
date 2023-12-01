<?php

declare(strict_types=1);

namespace Kernel\Models\Repository;

use PDO;
use Kernel\Models\Adapters\MySQLAdapter;
use Kernel\Models\DataMapper\Database\DBDataMapper;
use Kernel\Models\DataMapper\Database\DBData;

class DBRepositoryFactory
{
    /**
     * @template TemplateModel of object
     * @param class-string<DBDataMapper<TemplateModel, array<string, DBData>>> $dataMapperClass
     * @return DBMapperRepository<TemplateModel>
     **/
    public static function createRepository(
        string $dataMapperClass,
        PDO $pdo,
        string $tableName
    ): DBMapperRepository {
        $adapter = new MySQLAdapter($pdo, $tableName);

        $dataMapper = new $dataMapperClass($adapter);

        $mapperRepository = new DBMapperRepository($dataMapper);

        return $mapperRepository;
    }
}
