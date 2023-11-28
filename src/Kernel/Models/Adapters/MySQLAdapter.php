<?php

declare(strict_types=1);

namespace Kernel\Models\Adapters;

use PDO;
use Kernel\Models\DataMapper\Database\DBData;
use LogicException;
use PDOStatement;
use RuntimeException;

class MySQLAdapter
{
    public function __construct(private PDO $connection, private string $table)
    {
    }

    /**
     * @param array<string, DBData> $dataArray
     **/
    public function insert(array $dataArray): int
    {
        $statementString = "INSERT INTO $this->table " .
            self::createNamesString($dataArray) .
            self::createValuesString($dataArray);


        $statement = $this->connection->prepare($statementString);

        $valuesArray = self::createValuesArray($dataArray);
        if (!$statement->execute($valuesArray)) {
            throw new RuntimeException('Could not insert new row in MySQL');
        }

        return intval($this->connection->lastInsertId());
    }

    /**
     * @return array<string, DBData>
     **/
    public function selectById(int $id): array | null
    {
        $statement = $this->connection->prepare("SELECT * FROM {$this->table} WHERE id = ?");

        $statement->execute([$id]);
        $record = self::fetchRow($statement);

        if ($record === false) {
            return null;
        }

        return self::convertToDataArray($record);
    }

    /**
     * @param DBData $value
     * @return list<array<string, DBData>>
     **/
    public function selectByField(string $name, mixed $value): array
    {
        $statement = $this->connection->prepare("SELECT * FROM {$this->table} WHERE $name = ?");

        $statement->execute([$value->simpleValue()]);

        $list = self::fetchAll($statement);
        $dataArray = array_map(fn ($record) => self::convertToDataArray($record), $list);

        return $dataArray;
    }

    /**
     * @param DBData $value
     * @return list<array<string, DBData>>
     **/
    public function selectLikeField(string $name, mixed $value): array
    {
        $statement = $this->connection->prepare("SELECT * FROM {$this->table} WHERE $name like ?");

        $simpleValue = $value->simpleValue();
        $statement->execute(["%{$simpleValue}%"]);

        $list = self::fetchAll($statement);
        $dataArray = array_map(fn ($record) => self::convertToDataArray($record), $list);

        return $dataArray;
    }

    /**
     * @return list<array<string, DBData>>
     **/
    public function selectAll(): array
    {
        $list = [];

        $statement = $this->connection->prepare("SELECT * FROM {$this->table}");

        $statement->execute();

        $list = self::fetchAll($statement);
        $dataArray = array_map(fn ($record) => self::convertToDataArray($record), $list);

        return $dataArray;
    }

    /**
     * @param array<string, DBData> $dataArray
     **/
    public function update(int $id, array $dataArray): bool
    {
        $statementString = "UPDATE $this->table SET " .
            self::createUpdateString($dataArray) .
            ' WHERE id = :id';

        $statement = $this->connection->prepare($statementString);

        $valuesArray = self::createValuesArray($dataArray);
        $valuesArray['id'] = $id;

        return $statement->execute($valuesArray);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare("DELETE FROM {$this->table} WHERE id = :id");

        return $statement->execute(['id' => $id]);
    }

    /**
     * @param array<string, DBData> $dataArray
     **/
    private static function createNamesString(array $dataArray): string
    {
        $namesArray = array_keys($dataArray);
        $namesString = self::implodeArray($namesArray);

        return '(' . $namesString  . ') ';
    }

    /**
     * @param array<string, DBData> $dataArray
     **/
    private static function createValuesString(array $dataArray): string
    {
        $namesArray = array_keys($dataArray);
        $valuesArray = array_map(fn ($name) => ':' . $name, $namesArray);
        $valuesString = self::implodeArray($valuesArray);

        return 'VALUES (' . $valuesString . ') ';
    }

    /**
     * @param array<string, DBData> $dataArray
     **/
    private static function createUpdateString(array $dataArray): string
    {
        $updateStringsArray = array_map(
            fn ($data, $name) => "$name = :$name",
            $dataArray,
            array_keys($dataArray)
        );

        return implode(', ', $updateStringsArray);
    }

    /**
     * @param list<string> $stringsArray
     **/
    private static function implodeArray(array $stringsArray): string
    {
        return implode(', ', $stringsArray);
    }

    /**
     * @param array<string, DBData> $dataArray
     * @return array <string, DataValue>
     **/
    private static function createValuesArray(array $dataArray): array
    {
        return array_map(fn ($data) => $data->simpleValue(), $dataArray);
    }

    /**
     * @return array<string, string>
     **/
    private static function fetchRow(PDOStatement $statement): array | false
    {
        $fetchResult = $statement->fetch(PDO::FETCH_ASSOC);

        if ($fetchResult === false) {
            return false;
        }

        if (!is_array($fetchResult) || array_is_list($fetchResult)) {
            throw new LogicException('Select result from MySQL is not associative array');
        }

        return $fetchResult;
    }

    /**
     * @return list<array<string, string>>
     **/
    private static function fetchAll(PDOStatement $statement): array
    {
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, string> $selectResults
     * @return array<string, DBData>
     **/
    private static function convertToDataArray(array $selectResults): array
    {
        return array_map(fn ($value) => new DBData($value), $selectResults);
    }
}
