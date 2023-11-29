<?php

declare(strict_types=1);

namespace Kernel;

use Http\HTTPException;
use Kernel\Models\BodyData;
use LogicException;

abstract class Controller
{
    /**
     * @param array<string | int, mixed> $array
     **/
    protected static function toJson(array $array): string
    {
        $json = json_encode($array);

        if ($json === false) {
            throw new LogicException('Failed encoding User safe properties to JSON');
        }

        return $json;
    }

    protected static function argToInt(string $arg): int
    {
        if (!is_numeric($arg)) {
            throw new HTTPException(400, 'Passed id is not integer');
        }

        return intval($arg);
    }

    /**
     * @return BodyData
     **/
    protected static function fromJsonToDataObject(string $json): BodyData
    {
        $array = json_decode($json, true);

        if (!is_array($array)) {
            throw new HTTPException(400, 'The data is not an array');
        }

        return new BodyData($array);
    }
}
