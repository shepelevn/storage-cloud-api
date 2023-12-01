<?php

declare(strict_types=1);

namespace Models\File;

use DateTimeImmutable;
use Kernel\Models\DataMapper\Database\DBData;
use Kernel\Models\DataMapper\Database\DBDataMapper;
use RuntimeException;

/**
 * @extends DBDataMapper<FileModel, array<string, DBData>>
 **/
class FileMapper extends DBDataMapper
{
    /**
     * @param FileModel $folder
     * @return array<string, DBData>
     **/
    protected function mapObjectToArray(object $folder): array
    {
        $valuesArray = [
            'name' => $folder->name,
            'user_id' => $folder->userId,
            'folder_id' => $folder->folderId,
            'created_at' => $folder->createdAt,
            'updated_at' => $folder->updatedAt,
        ];

        return array_map(fn ($value) => new DBData($value), $valuesArray);
    }

    /**
     * @param array<string, DBData> $data
     * @return FileModel $object
     **/
    protected function mapArrayToObject(array $data): FileModel
    {
        $id = $data['id']->value;
        $name = $data['name']->value;
        $userId = $data['user_id']->value;
        $folderId = $data['folder_id']->value;
        $createdAt = $data['created_at']->value;
        $updatedAt = $data['updated_at']->value;

        if (
            !is_numeric($id) ||
            !is_string($name) ||
            !is_numeric($userId) ||
            !(is_numeric($folderId)) ||
            !is_string($createdAt) ||
            !is_string($updatedAt)
        ) {
            throw new RuntimeException('Got wrong data type from database');
        }

        if ($name === '') {
            throw new RuntimeException('File name is empty');
        }

        return new FileModel(
            (int) $id,
            $name,
            (int) $userId,
            (int) $folderId,
            new DateTimeImmutable($createdAt),
            new DateTimeImmutable($updatedAt),
        );
    }
}
