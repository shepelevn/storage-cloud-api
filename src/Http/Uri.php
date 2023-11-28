<?php

declare(strict_types=1);

namespace Http;

class Uri
{
    public function __construct(private string $path)
    {
    }

    public function getPath(): string
    {
        $path = parse_url($this->path, PHP_URL_PATH);

        if ($path === false) {
            throw new HTTPException(400, 'Could not parse request url');
        }

        if (is_null($path)) {
            return '/';
        }

        return $path;
    }
}
