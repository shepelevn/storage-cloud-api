<?php

declare(strict_types=1);

namespace Services;

use Http\HTTPException;
use Http\PostFile;
use RuntimeException;

class FilesService
{
    private int $maxFileSizeKb;
    private string $folderPath;

    public function __construct(ConfigService $configService)
    {
        $maxFileSizeMb = $configService->mainConfig['MAX_FILE_SIZE_MB'];

        if (!is_numeric($maxFileSizeMb)) {
            throw new RuntimeException('MAX_FILE_SIZE_MB is not numeric');
        }

        $this->maxFileSizeKb = intval($maxFileSizeMb * 1024);
        $this->folderPath = __DIR__ . '/../../files/';
    }

    public function save(PostFile $uploadedFile, string $name): void
    {
        $fileSizeKb = $uploadedFile->getSizeKb();
        if ($fileSizeKb > $this->maxFileSizeKb) {
            throw new HTTPException(413, 'Uploaded file is too large');
        }

        if (!$uploadedFile->save($this->getFilePath($name))) {
            throw new RuntimeException('Could not save the file.');
        }
    }

    /**
     * @return array<string, string>
     **/
    public function get(string $name): array
    {
        $filePath = $this->getFilePath($name);

        $fileData = [];
        $fileData['contents'] = file_get_contents($filePath);

        if ($fileData['contents'] === false) {
            throw new RuntimeException('Requested file not found');
        }

        $type = mime_content_type($filePath);

        if ($type === false) {
            $fileData['type'] = 'application/octet-stream';
        } else {
            $fileData['type'] = $type;
        }

        return $fileData;
    }

    public function getSizeKb(string $name): int
    {
        $filePath = $this->getFilePath($name);
        $sizeB = filesize($filePath);

        if ($sizeB === false) {
            throw new RuntimeException('File not found');
        }

        return (int) ceil($sizeB / 1024);
    }

    public function delete(string $name): void
    {
        if (!unlink($this->getFilePath($name))) {
            throw new RuntimeException('Could not delete the file');
        }

    }

    private function getFilePath(string $name): string
    {
        return $this->folderPath . $name;
    }
}
