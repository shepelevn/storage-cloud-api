<?php

declare(strict_types=1);

namespace Kernel\Models;

use Http\HTTPException;

class BodyData
{
    /**
     * @param array<string, JSONValue> $dataArray
     **/
    public function __construct(private array $dataArray)
    {
    }

    /**
     * @return JSONValue
     **/
    public function checkAndGet(string $key): mixed
    {
        if (!array_key_exists($key, $this->dataArray)) {
            throw new HTTPException(400, "Parameter $key is not found");
        }

        return $this->dataArray[$key];
    }
}
