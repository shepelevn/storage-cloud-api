<?php

declare(strict_types=1);

namespace Kernel\Routes;

use Http\Request;
use RuntimeException;

class RoutesResolver
{
    /** @var list<RoutesGroup> **/
    private array $groups = [];

    public function resolveRoute(Request $request): Route
    {
        $method = $request->getMethod();
        $routePath = $request->getUri()->getPath();

        foreach ($this->groups as $group) {
            $route = $group->getMatchingRoute($method, $routePath);

            if ($route !== false) {
                return $route;
            }
        }

        throw new RuntimeException('No route matched the requested path');
    }

    public function addGroup(string $groupPath): RoutesGroup
    {
        $newGroup = new RoutesGroup($groupPath);

        $this->groups[] = $newGroup;

        return $newGroup;
    }

    /**
     * @param list<string> $methods
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addRoute(
        array $methods,
        string $routeString,
        array $controllerActionData,
    ): RoutesGroup {
        $newGroup = $this->addGroup('');

        $this->groups[] = $newGroup;

        $newGroup->addRoute($methods, $routeString, $controllerActionData);

        return $newGroup;
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addAll(string $routeString, array $controllerActionData): RoutesGroup
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addGet(string $routeString, array $controllerActionData): RoutesGroup
    {
        return $this->addRoute(['GET'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addPost(string $routeString, array $controllerActionData): RoutesGroup
    {
        return $this->addRoute(['POST'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addPut(string $routeString, array $controllerActionData): RoutesGroup
    {
        return $this->addRoute(['PUT'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addPatch(string $routeString, array $controllerActionData): RoutesGroup
    {
        return $this->addRoute(['PATCH'], $routeString, $controllerActionData);
    }

    /**
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function addDelete(string $routeString, array $controllerActionData): RoutesGroup
    {
        return $this->addRoute(['DELETE'], $routeString, $controllerActionData);
    }
}
