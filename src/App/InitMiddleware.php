<?php

declare(strict_types=1);

namespace App;

use Kernel\Kernel;
use Middleware\ExceptionHandler;
use Middleware\HTTPExceptionHandler;

class InitMiddleware
{
    public function __invoke(Kernel $kernel): void
    {
        $httpExceptionHandler = new HTTPExceptionHandler();
        $kernel->middlewareHandler->addMiddleware($httpExceptionHandler);

        $exceptionHandler = new ExceptionHandler();
        $kernel->middlewareHandler->addMiddleware($exceptionHandler);
    }
}
