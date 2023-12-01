<?php

declare(strict_types=1);

namespace Http;

use Exception;

class HTTPException extends Exception
{
    public readonly int $httpCode;

    public function __construct(int $httpCode, string $message)
    {
        parent::__construct($message);

        $this->httpCode = $httpCode;
    }
}
