<?php

declare(strict_types=1);

namespace Kernel;

use LogicException;

class ServicesContainer
{
    /** @var array<string, callable> **/
    public array $services = [];

    /**
     * @param callable $serviceCreator
     **/
    public function add(string $serviceName, callable $serviceCreator): void
    {
        $this->services[$serviceName] = $serviceCreator;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     **/
    public function get(string $serviceName, string $className): object
    {
        $service = $this->services[$serviceName]();

        if (get_class($service) !== $className) {
            throw new LogicException('Tried to return service with the wrong class from container');
        }

        return $service;
    }

    public function has(string $serviceName): bool
    {
        return array_key_exists($serviceName, $this->services);
    }
}
