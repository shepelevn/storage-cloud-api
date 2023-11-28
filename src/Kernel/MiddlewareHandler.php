<?php

declare(strict_types=1);

namespace Kernel;

use Http\Request;
use Kernel\ServicesContainer;

class MiddlewareHandler
{
    /** @var list<callable> **/
    private array $middlewareStack = [];

    public function addMiddleware(callable $middlewareFunction): void
    {
        $this->middlewareStack[] = $middlewareFunction;
    }

    public function createMiddlewareWrapper(callable $rootHandler, ServicesContainer $servicesContainer): callable
    {
        $wrapperHandler = $rootHandler;

        for ($i = 0; $i < count($this->middlewareStack); $i++) {
            $handler = $this->middlewareStack[$i];

            $wrapperHandler = function (Request $request) use ($handler, $wrapperHandler, $servicesContainer) {
                return $handler($request, $wrapperHandler, $servicesContainer);
            };
        }

        return $wrapperHandler;
    }
}
