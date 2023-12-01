<?php

declare(strict_types=1);

require_once('../autoload.php');

use App\InitMiddleware;
use App\InitRoutes;
use App\InitServices;
use Kernel\Kernel;
use Kernel\ServicesContainer;

$servicesContainer = new ServicesContainer();
$initServices = new InitServices();
$initServices($servicesContainer);

$kernel = new Kernel($servicesContainer);

$initRoutes = new InitRoutes();
$initRoutes($kernel);

$initMiddleware = new InitMiddleware();
$initMiddleware($kernel);

$kernel->run();
