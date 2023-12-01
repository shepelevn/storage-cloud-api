<?php

declare(strict_types=1);

namespace Utils\Traits;

use DateTimeImmutable;
use Http\HTTPException;

trait TypeChecker
{
    private function checkIfString(mixed $value): void
    {
        if (!is_string($value)) {
            throw new HTTPException(400, "$value is not string");
        }
    }

    private function checkIfStringOrNull(mixed $value): void
    {
        if (!(is_null($value) || is_string($value))) {
            throw new HTTPException(400, "$value is not string or null");
        }
    }

    private function checkIfInteger(mixed $value): void
    {
        if (!is_integer($value)) {
            throw new HTTPException(400, "$value is not integer");
        }
    }

    private function checkIfBool(mixed $value): void
    {
        if (!is_bool($value)) {
            throw new HTTPException(400, "$value is not bool");
        }
    }

    private function checkIfDateTimeImmutable(mixed $value): void
    {
        if (!($value instanceof DateTimeImmutable)) {
            throw new HTTPException(400, "$value is not DateTimeImmutable");
        }
    }
}
