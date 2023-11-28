<?php

declare(strict_types=1);

namespace Kernel;

use Http\RequestFactory;
use Http\Request;
use Http\Response;
use Kernel\Routes\RoutesResolver;
use Kernel\MiddlewareHandler;
use Http\ResponseEmitter;
use Kernel\ServicesContainer;

class Kernel
{
    // Add services

    public RoutesResolver $routesResolver;
    public MiddlewareHandler $middlewareHandler;

    public function __construct(private ServicesContainer $servicesContainer)
    {
        $this->routesResolver = new RoutesResolver();
        $this->middlewareHandler = new MiddlewareHandler();
    }

    public function run(): void
    {
        $request = RequestFactory::fromGlobals();
        $initialResponse = (new Response())->withHeader('Content-type', 'application/json');

        $route = $this->routesResolver->resolveRoute($request);
        $routeMethod = $route->getAction($request->getUri()->getPath());

        $bottomAction = function (
            Request $request,
        ) use (
            $initialResponse,
            $routeMethod,
        ) {
            return $routeMethod($request, $initialResponse, $this->servicesContainer);
        };

        $routeWrappedAction = $route
            ->middlewareHandler
            ->createMiddlewareWrapper($bottomAction, $this->servicesContainer);

        $wrappedAction = $this->middlewareHandler
            ->createMiddlewareWrapper($routeWrappedAction, $this->servicesContainer);

        $response = $wrappedAction($request);

        ResponseEmitter::emit($response);
    }
}
