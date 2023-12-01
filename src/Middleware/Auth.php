<?php

declare(strict_types=1);

namespace Middleware;

use Http\HTTPException;
use Http\Request;
use Http\Response;
use Kernel\ServicesContainer;
use LogicException;
use Services\AuthService;

class Auth
{
    private bool $onlyAdminAccess;

    public function __construct(string $accessType = 'normal')
    {
        switch ($accessType) {
            case 'normal':
                $this->onlyAdminAccess = false;
                break;
            case 'admin':
                $this->onlyAdminAccess = true;
                break;
            default:
                throw new LogicException('Wrong access type string');
        }
    }

    public function __invoke(Request $request, callable $handler, ServicesContainer $servicesContainer): Response
    {
        $authService = $servicesContainer->get('AuthService', AuthService::class);

        if (!$authService->isLoggedIn) {
            throw new HTTPException(403, "Only logged in users can access this route");
        }

        if ($this->onlyAdminAccess) {
            if (!$authService->isAdmin) {
                throw new HTTPException(403, "Only administrator users can access this route");
            }
        }

        return $handler($request);
    }
}
