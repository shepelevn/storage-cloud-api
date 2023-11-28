<?php

declare(strict_types=1);

namespace Models\ShareRecord;

use Kernel\Models\DataMapper\Database\DBData;
use Kernel\Models\DataMapper\Database\DBDataMapper;
use Models\ShareRecord\ShareRecord;
use RuntimeException;

/**
 * @extends DBDataMapper<ShareRecord, array<string, DBData>>
 **/
class ShareRecordMapper extends DBDataMapper
{
    /**
     * @param ShareRecord $file
     * @return array<string, DBData>
     **/
    protected function mapObjectToArray(object $file): array
    {
        $valuesArray = [
            'user_id' => $file->userId,
            'file_id' => $file->fileId,
        ];

        return array_map(fn ($value) => new DBData($value), $valuesArray);
    }

    /**
     * @param array<string, DBData> $data
     * @return ShareRecord $object
     **/
    protected function mapArrayToObject(array $data): ShareRecord
    {
        $id = $data['id']->value;
        $userId = $data['user_id']->value;
        $fileId = $data['file_id']->value;

        if (
            !is_numeric($id) ||
            !is_numeric($userId) ||
            !is_numeric($fileId)
        ) {
            throw new RuntimeException('Got wrong data type from database');
        }

        return new ShareRecord(
            (int) $id,
            (int) $userId,
            (int) $fileId,
        );
    }
}
