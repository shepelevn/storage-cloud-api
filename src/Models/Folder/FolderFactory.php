<?php

declare(strict_types=1);

namespace Models\Folder;

class FolderFactory
{
    public static function createFolderTemplate(): Folder
    {
        return new Folder(
            -1,
            '0',
            -1,
            null,
        );
    }
}
