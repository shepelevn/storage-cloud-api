<?php

declare(strict_types=1);

namespace Models\File;

use DateTimeImmutable;
use Http\HTTPException;
use Kernel\Models\BodyData;
use LogicException;

/**
 * @property string $name
 * @property int $userId
 * @property int $folderId
 **/
class FileModel
{
    /**
     * @param non-empty-string $name
     **/
    public function __construct(
        public int $id,
        private string $name,
        private int $userId,
        private int $folderId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @return array<string, DataValue>
     **/
    public function getProperties(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'userId' => $this->userId,
            'folderId' => $this->folderId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    public function update(BodyData $parameters): void
    {
        $this->setName($parameters->checkAndGet('name'));
        $this->setFolderId($parameters->checkAndGet('folderId'));
    }


    public function checkAccess(int $userId): void
    {
        if ($this->userId !== $userId) {
            throw new HTTPException(403, 'You have no permision to view or edit this file');
        }
    }

    public function checkAreUnique(FileModel $file): void
    {
        if ($file->folderId === $this->folderId) {
            throw new HTTPException(400, "The file with a name $this->name already exists in this folder");
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFolderId(): int
    {
        return $this->folderId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param JSONValue $name
     **/
    public function setName(mixed $name): void
    {
        if (!is_string($name)) {
            throw new HTTPException(400, 'name is not string');
        }

        if ($name === '') {
            throw new HTTPException(400, 'Name is empty');
        }

        $this->name = $name;
    }

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
     * @param JSONValue $folderId
     **/
    public function setFolderId(mixed $folderId): void
    {
        if (!is_integer($folderId)) {
            throw new HTTPException(400, 'folderId is not integer');
        }

        $this->folderId = $folderId;
    }

    public function __get(string $name): int | string | bool | DateTimeImmutable | null
    {
        return match($name) {
            'name' => $this->getName(),
            'userId' => $this->getUserId(),
            'folderId' => $this->getFolderId(),
            default => throw new LogicException("Tried to get property $name which does not exist"),
        };
    }

    /**
     * @param JSONValue $value
     **/
    public function __set(string $name, mixed $value): void
    {
        switch ($name) {
            case 'name':
                $this->setName($value);
                break;
            case 'userId':
                $this->setUserId($value);
                break;
            case 'folderId':
                $this->setFolderId($value);
                break;
            default:
                throw new LogicException('Tried to set value which does not exist');
        }
    }
}
