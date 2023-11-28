<?php

declare(strict_types=1);

namespace Http;

class PostFile
{
    /**
     * @param HttpDataArray $fileData
     **/
    public function __construct(private array $fileData)
    {
    }

    public function getName(): string
    {
        return $this->fileData['name'];
    }

    public function getSizeKb(): int
    {
        return (int) ceil((int) $this->fileData['size'] / 1024);
    }

    public function save(string $path): bool
    {
        return move_uploaded_file($this->fileData['tmp_name'], $path);
    }
}
