<?php

declare(strict_types=1);

namespace Kernel\Models\Repository;

use Kernel\Models\DataMapper\Database\DBData;
use Kernel\Models\DataMapper\Database\DBDataMapper;

/**
 * @template TemplateModel of object
 * @extends MapperRepository<int, TemplateModel>
 **/
class DBMapperRepository extends MapperRepository
{
    /**
     * @param DBDataMapper<TemplateModel, array<string, DBData>> $dataMapper
     **/
    public function __construct(private DBDataMapper $dataMapper)
    {
        parent::__construct($dataMapper);
    }

    /**
     * @param JSONValue $propertyValue
     * @return list<TemplateModel>
     **/
    public function getByProperty(string $propertyName, mixed $propertyValue): array
    {
        return $this->dataMapper->getByProperty($propertyName, $propertyValue);
    }

    /**
     * @param JSONValue $propertyValue
     * @return list<TemplateModel>
     **/
    public function getLikeProperty(string $propertyName, mixed $propertyValue): array
    {
        return $this->dataMapper->getLikeProperty($propertyName, $propertyValue);
    }
}
