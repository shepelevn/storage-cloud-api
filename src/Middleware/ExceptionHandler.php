<?php

declare(strict_types=1);

namespace Middleware;

use Exception;
use Http\Request;
use Http\Response;
use Kernel\ServicesContainer;
use LogicException;
use Services\ConfigService;

class ExceptionHandler
{
    public function __invoke(Request $request, callable $handler, ServicesContainer $servicesContainer): Response
    {
        try {
            return $handler($request);
        } catch (Exception $exception) {
            error_log($exception->getMessage());

            $message = 'Server side error';

            $response = (new Response())
                ->withStatus(500)
                ->withHeader('Content-type', 'application/json');

            $mainConfig = $servicesContainer->get('ConfigService', ConfigService::class)->mainConfig;
            if ($mainConfig['DEV']) {
                if ($mainConfig['DEBUG']) {
                    http_response_code(500);
                    throw $exception;
                }
                $message = $exception->getMessage();
            }

            $json = json_encode(['error' => $message]);

            if ($json === false) {
                throw new LogicException('Error creating 500 HTTP response error message');
            }

            $response->setBody($json);

            return $response;
        }
    }
}
