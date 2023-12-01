<?php

declare(strict_types=1);

namespace Kernel\Models\DataMapper\Database;

use Http\HTTPException;
use Kernel\Models\Adapters\MySQLAdapter;
use Kernel\Models\DataMapper\DataMapper;
use Kernel\Models\DataMapper\Database\DBData;

/**
 * @template TemplateModel of object
 * @template DataArray of array<string, DBData>
 * @implements DataMapper<int, TemplateModel>
 **/
abstract class DBDataMapper implements DataMapper
{
    /**
     * @param TemplateModel $object
     * @return DataArray
     **/
    abstract protected function mapObjectToArray(object $object): array;

    /**
     * @param DataArray $array
     * @return TemplateModel $object
     **/
    abstract protected function mapArrayToObject(array $array): object;

    public function __construct(protected MySQLAdapter $adapter)
    {
    }

    /**
     * @param JSONValue $value
     **/
    private static function mapOneValue(mixed $value): DBData
    {
        return new DBData($value);
    }

    /**
     * @param TemplateModel $savedObject
     **/
    public function create(object $savedObject): int
    {
        $objectArray = $this->mapObjectToArray($savedObject);
        return $this->adapter->insert($objectArray);
    }

    /**
     * @param int $id
     * @return TemplateModel | null
     **/
    public function read(string | int $id): object | null
    {
        $dataArray = $this->adapter->selectById((int) $id);

        if ($dataArray === null) {
            throw new HTTPException(404, 'object with provided id not found');
        }

        return $this->mapArrayToObject($dataArray);
    }

    /**
     * @param JSONValue $propertyValue
     * @return list<TemplateModel>
     **/
    public function getByProperty(string $propertyName, mixed $propertyValue): array
    {
        $convertedValue = self::mapOneValue($propertyValue);

        $objectArrays = $this->adapter->selectByField($propertyName, $convertedValue);
        return array_map(fn ($objectArray) => $this->mapArrayToObject($objectArray), $objectArrays);
    }

    /**
     * @param JSONValue $propertyValue
     * @return list<TemplateModel>
     **/
    public function getLikeProperty(string $propertyName, mixed $propertyValue): array
    {
        $convertedValue = self::mapOneValue($propertyValue);

        $objectArrays = $this->adapter->selectLikeField($propertyName, $convertedValue);
        return array_map(fn ($objectArray) => $this->mapArrayToObject($objectArray), $objectArrays);
    }

    /**
     * @param int $id
     * @param TemplateModel $newObject
     * **/
    public function update(string | int $id, object $newObject): bool
    {
        $objectArray = $this->mapObjectToArray($newObject);
        return $this->adapter->update((int) $id, $objectArray);
    }

    /**
     * @param int $id
     * **/
    public function delete(string | int $id): bool
    {
        return $this->adapter->delete((int) $id);
    }

    /** @return list<TemplateModel> **/
    public function list(): array
    {
        $objectArrays = $this->adapter->selectAll();
        return array_map(fn ($objectArray) => $this->mapArrayToObject($objectArray), $objectArrays);
    }
}
