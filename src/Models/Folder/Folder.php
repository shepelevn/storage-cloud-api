<?php

declare(strict_types=1);

namespace Models\Folder;

use DateTimeImmutable;
use Http\HTTPException;
use Kernel\Models\BodyData;
use LogicException;

/**
 * @property string $name
 * @property int $userId
 * @property int | null $parentId
 **/
class Folder
{
    /**
     * @param non-empty-string $name
     **/
    public function __construct(
        public int $id,
        private string $name,
        private int $userId,
        private int | null $parentId,
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
            'parentId' => $this->parentId,
        ];
    }

    public function update(BodyData $parameters): void
    {
        $this->setName($parameters->checkAndGet('name'));
        $this->setParentId($parameters->checkAndGet('parentId'));
    }


    public function checkAccess(int $userId): void
    {
        if ($this->userId !== $userId) {
            throw new HTTPException(403, 'You have no permision to view or edit this folder');
        }
    }

    public function checkAreUnique(Folder $folder): void
    {
        if ($folder->parentId === $this->parentId) {
            throw new HTTPException(400, "The folder with a name $this->name already exists in this folder");
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): int | null
    {
        return $this->parentId;
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
            throw new HTTPException(400, 'name is empty');
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
     * @param JSONValue $parentId
     **/
    public function setParentId(mixed $parentId): void
    {
        if (!is_integer($parentId) && !is_null($parentId)) {
            throw new HTTPException(400, 'parentId is not integer');
        }

        $this->parentId = $parentId;
    }

    public function __get(string $name): int | string | bool | DateTimeImmutable | null
    {
        return match($name) {
            'name' => $this->getName(),
            'userId' => $this->getUserId(),
            'parentId' => $this->getParentId(),
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
            case 'parentId':
                $this->setParentId($value);
                break;
            default:
                throw new LogicException('Tried to set value which does not exist');
        }
    }
}
