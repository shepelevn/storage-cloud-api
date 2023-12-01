<?php

declare(strict_types=1);

namespace Models\ShareRecord;

use DateTimeImmutable;
use Http\HTTPException;
use LogicException;

/**
 * @property int $userId
 * @property int $fileId
 **/
class ShareRecord
{
    public function __construct(
        public int $id,
        private int $userId,
        private int $fileId,
    ) {
    }

    /**
     * @return array<string, DataValue>
     **/
    public function getProperties(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'fileId' => $this->fileId,
        ];
    }

    public function checkAreUnique(ShareRecord $otherRecord): void
    {
        if (
            $this->userId === $otherRecord->userId &&
            $this->fileId === $otherRecord->fileId
        ) {
            throw new HTTPException(400, 'You already shared this file with this user');
        }
    }

    public function getFileId(): int | null
    {
        return $this->fileId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
    /**
     * @param JSONValue $userId
     **/
    public function setUserId(mixed $userId): void
    {
        if (!is_integer($userId)) {
            throw new HTTPException(400, 'userId is not integer');
        }

        $this->userId = $userId;
    }

    /**
     * @param JSONValue $fileId
     **/
    public function setFileId(mixed $fileId): void
    {
        if (!is_integer($fileId)) {
            throw new HTTPException(400, 'fileId is not integer');
        }

        $this->fileId = $fileId;
    }

    public function __get(string $name): int | string | bool | DateTimeImmutable | null
    {
        return match($name) {
            'userId' => $this->getUserId(),
            'fileId' => $this->getFileId(),
            default => throw new LogicException("Tried to get property $name which does not exist"),
        };
    }

    /**
     * @param JSONValue $value
     **/
    public function __set(string $name, mixed $value): void
    {
        switch ($name) {
            case 'userId':
                $this->setUserId($value);
                break;
            case 'fileId':
                $this->setFileId($value);
                break;
            default:
                throw new LogicException('Tried to set value which does not exist');
        }
    }
}
