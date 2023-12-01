<?php

declare(strict_types=1);

namespace Http;

use RuntimeException;

class ResponseEmitter
{
    public static function emit(Response $response): void
    {

        if (headers_sent()) {
            throw new RuntimeException('Headers were already sent. The response could not be emitted!');
        }

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $header) {
            $name = $header['name'];
            $value = $header['value'];
            $responseHeader = sprintf(
                '%s: %s',
                $name,
                $value
            );
            header($responseHeader, false);
        }

        echo $response->getBody();
    }
}
