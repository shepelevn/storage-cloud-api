<?php

declare(strict_types=1);

namespace Models\Folder;

use Kernel\Models\DataMapper\Database\DBData;
use Kernel\Models\DataMapper\Database\DBDataMapper;
use RuntimeException;

/**
 * @extends DBDataMapper<Folder, array<string, DBData>>
 **/
class FolderMapper extends DBDataMapper
{
    /**
     * @param Folder $folder
     * @return array<string, DBData>
     **/
    protected function mapObjectToArray(object $folder): array
    {
        $valuesArray = [
            'name' => $folder->name,
            'user_id' => $folder->userId,
            'parent_id' => $folder->parentId,
        ];

        return array_map(fn ($value) => new DBData($value), $valuesArray);
    }

    /**
     * @param array<string, DBData> $data
     * @return Folder $object
     **/
    protected function mapArrayToObject(array $data): Folder
    {
        $id = $data['id']->value;
        $name = $data['name']->value;
        $userId = $data['user_id']->value;
        $parentId = $data['parent_id']->value;

        if (
            !is_numeric($id) ||
            !is_string($name) ||
            !is_numeric($userId) ||
            !(is_numeric($parentId) || is_null($parentId))
        ) {
            throw new RuntimeException('Got wrong data type from database');
        }

        if ($name === '') {
            throw new RuntimeException('Folder name is empty');
        }

        return new Folder(
            (int) $id,
            $name,
            (int) $userId,
            is_null($parentId) ? null : intval($parentId),
        );
    }
}
