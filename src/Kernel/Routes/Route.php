<?php

declare(strict_types=1);

namespace Kernel\Routes;

use Closure;
use Http\Request;
use Http\Response;
use Kernel\ServicesContainer;
use LogicException;
use Kernel\MiddlewareHandler;

class Route
{
    private Closure $action;
    public MiddlewareHandler $middlewareHandler;

    /**
     * @param list<string> $methods
     * @param array{0: class-string, 1: string} $controllerActionData
     **/
    public function __construct(
        private array $methods,
        private string $routeString,
        array $controllerActionData,
    ) {
        $this->action = function (
            Request $request,
            Response $response,
            ServicesContainer $servicesContainer,
            array $args
        ) use ($controllerActionData) {
            $controllerClass = $controllerActionData[0];
            $actionName = $controllerActionData[1];

            $controller = new $controllerClass($servicesContainer);

            return $controller->$actionName($request, $response, $args);
        };

        $this->middlewareHandler = new MiddlewareHandler();
    }

    public const PART_REGEXP = '/^{[\d\w]+}$/';

    private static function isParam(string $part): bool
    {
        $trimmedPart = trim($part);

        $pregResult = preg_match(self::PART_REGEXP, $trimmedPart);

        if ($pregResult === false) {
            throw new LogicException('Error: Failed matching route path parts for parameter');
        }

        return $pregResult === 1;
    }

    private static function getParamName(string $part): string
    {
        return mb_substr($part, 1, mb_strlen($part) - 2);
    }

    private static function normalizePath(string $path): string
    {
        if (mb_substr($path, -1, 1) !== '/') {
            return $path . '/';
        } else {
            return $path;
        }
    }

    /**
     * @return list<string>
     **/
    private static function getParts(string $path): array
    {
        return explode('/', self::normalizePath($path));
    }

    private function createRouteRegexp(): string
    {
        $route = self::normalizePath($this->routeString);

        $replacements = [
            '/\//' => '\/',
            '/\*/' => '.*',
            '/{[\d\w]+}/' => '([\d\w])*',
        ];

        foreach ($replacements as $regexp => $replacementString) {
            $route = preg_replace($regexp, $replacementString, $route);

            if (is_null($route)) {
                throw new LogicException('Error creating regular expression from route path');
            }
        }

        return "/^$route$/i";
    }

    /**
     * @return array<string, string>
     **/
    private function getArgs(string $path): array
    {
        $args = [];

        $thisRouteParts = self::getParts($this->routeString);
        $requestRouteParts = self::getParts($path);

        for ($i = 0; $i < count($thisRouteParts); $i++) {
            $thisPart = $thisRouteParts[$i];
            $requestPart = $requestRouteParts[$i];

            if (mb_strpos($thisPart, '*') !== false) {
                break;
            }

            if (self::isParam($thisPart)) {
                $args[self::getParamName($thisPart)] = $requestPart;
            }
        }

        return $args;
    }

    public function isMatch(string $method, string $path): bool
    {
        if (!in_array($method, $this->methods)) {
            return false;
        }

        $routeRegexp = self::createRouteRegexp();

        $matchResult = preg_match($routeRegexp, self::normalizePath($path));

        if ($matchResult === false) {
            throw new LogicException('Error while matching request route');
        }

        return $matchResult === 1;
    }

    public function getAction(string $path): Closure
    {
        $args = $this->getArgs($path);

        $action = $this->action;

        return fn (Request $request, Response $response, ServicesContainer $servicesContainer)
            => $action($request, $response, $servicesContainer, $args);
    }
}
