<?php

declare(strict_types=1);

namespace Kernel\Models\Repository;

use Kernel\Models\DataMapper\DataMapper;

/**
 * @template Slug of string | int
 * @template TemplateModel of object
 * @implements Repository<Slug, TemplateModel>
 **/
class MapperRepository implements Repository
{
    /**
     * @param DataMapper<Slug, TemplateModel> $dataMapper
     **/
    public function __construct(private DataMapper $dataMapper)
    {
    }

    public function create(object $savedObject): string | int
    {
        return $this->dataMapper->create($savedObject);
    }

    public function read(string | int $slug): object | null
    {
        return $this->dataMapper->read($slug);
    }

    public function update(string | int $slug, object $newObject): bool
    {
        return $this->dataMapper->update($slug, $newObject);
    }

    public function delete(string | int $slug): bool
    {
        return $this->dataMapper->delete($slug);
    }

    public function list(): array
    {
        return $this->dataMapper->list();
    }
}
