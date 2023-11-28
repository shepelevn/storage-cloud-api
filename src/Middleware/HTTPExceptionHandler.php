<?php

declare(strict_types=1);

namespace Middleware;

use Http\HTTPException;
use Http\Request;
use Http\Response;
use LogicException;

class HTTPExceptionHandler
{
    public function __invoke(Request $request, callable $handler): Response
    {
        try {
            return $handler($request);
        } catch (HTTPException $httpException) {
            $response = (new Response())
                ->withStatus($httpException->httpCode)
                ->withHeader('Content-type', 'application/json');

            $json = json_encode(['error' => $httpException->getMessage()]);

            if ($json === false) {
                throw new LogicException('Error creating HTTP response message');
            }

            $response->setBody($json);

            return $response;
        }
    }
}
