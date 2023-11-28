<?php

declare(strict_types=1);

require_once('../autoload.php');

use App\InitMiddleware;
use App\InitRoutes;
use App\InitServices;
use Kernel\Kernel;
use Kernel\ServicesContainer;

// Create services
$servicesContainer = new ServicesContainer();
$initServices = new InitServices();
$initServices($servicesContainer);

// Create app
$kernel = new Kernel($servicesContainer);

$initRoutes = new InitRoutes();
$initRoutes($kernel);

$initMiddleware = new InitMiddleware();
$initMiddleware($kernel);

// Add 2 errorHandlers one for http and one for php
/* $errorHandler = new ErrorHandler(); */
/* $kernel->addMiddleware($errorHandler); */

$kernel->run();
