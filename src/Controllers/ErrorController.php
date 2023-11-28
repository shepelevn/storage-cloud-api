<?php

declare(strict_types=1);

namespace Controllers;

use Http\HTTPException;

class ErrorController
{
    public function notFound(): void
    {
        throw new HTTPException(404, 'Route not found');
    }
}
