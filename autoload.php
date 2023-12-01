<?php

declare(strict_types=1);

require_once(__DIR__ . '/vendor/autoload.php');

function SrcLoader(string $className): void
{
    folderLoader('src', $className);
}

/* TODO: Delete later probably */
/* function UtilsLoader(string $className): void */
/* { */
/*     folderLoader('Utils', $className); */
/* } */

function folderLoader(string $folderPath, string $className): void
{
    $filePath = __DIR__ . '/' . $folderPath . '/' . str_replace('\\', '/', $className) . '.php';

    if (file_exists($filePath)) {
        require_once $filePath;
    }
}

/* TODO: Delete later probably */
/* spl_autoload_register('UtilsLoader'); */

spl_autoload_register('SrcLoader');
