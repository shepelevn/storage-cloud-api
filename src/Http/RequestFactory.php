<?php

declare(strict_types=1);

namespace Http;

class RequestFactory
{
    public static function fromGlobals(): Request
    {
        return new Request($_REQUEST, $_POST, $_SERVER, $_FILES);
    }
}
