<?php

declare(strict_types=1);

namespace Kernel\Routes;

class RoutesGroup
{
    /** @var list<Route> **/
    private array $routes = [];

    public function __construct(private string $path)
    {
    }

    public function addMiddleware(callable $middlewareFunction): void
    {
        foreach ($this->routes as $route) {
            $route->middlewareHandler->addMiddleware($middlewareFunction);
        }
    }

    public function getMatchingRoute(string $method, string $path): Route | false
    {
        for ($i = 0; $i < count($this->routes); $i++) {
            $route = $this->routes[$i];

            if ($route->isMatch($method, $path)) {
                return $route;
            }
        }

        return false;
    }

    /**
     * @param list<string> $methods
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addRoute(
        array $methods,
        string $routeString,
        array $controllerActionData,
    ): Route {
        $newRoute = new Route($methods, $this->path . $routeString, $controllerActionData);

        $this->routes[] = $newRoute;

        return $newRoute;
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addAll(string $routeString, array $controllerActionData): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addGet(string $routeString, array $controllerActionData): Route
    {
        return $this->addRoute(['GET'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addPost(string $routeString, array $controllerActionData): Route
    {
        return $this->addRoute(['POST'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addPut(string $routeString, array $controllerActionData): Route
    {
        return $this->addRoute(['PUT'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addPatch(string $routeString, array $controllerActionData): Route
    {
        return $this->addRoute(['PATCH'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addDelete(string $routeString, array $controllerActionData): Route
    {
        return $this->addRoute(['DELETE'], $routeString, $controllerActionData);
    }
}
