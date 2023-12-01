<?php

declare(strict_types=1);

namespace Models\File;

use DateTimeImmutable;

class FileFactory
{
    public static function createFileTemplate(): FileModel
    {
        return new FileModel(
            -1,
            '0',
            -1,
            -1,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );
    }
}
