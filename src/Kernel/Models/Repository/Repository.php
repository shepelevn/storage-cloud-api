<?php

declare(strict_types=1);

namespace Kernel\Models\Repository;

/**
 * @template Slug of string | int
 * @template TemplateModel of object
 **/
interface Repository
{
    /**
     * @param TemplateModel $savedObject
     * @return Slug
     **/
    public function create(object $savedObject): string | int;

    /**
     * @param Slug $slug
     * @return TemplateModel | null $savedObject
     **/
    public function read(string | int $slug): object | null;

    /**
     * @param Slug $slug
     * @param TemplateModel $newObject
     * **/
    public function update(string | int $slug, object $newObject): bool;

    /**
     * @param Slug $slug
     * **/
    public function delete(string | int $slug): bool;

    /** @return list<TemplateModel> **/
    public function list(): array;
}
