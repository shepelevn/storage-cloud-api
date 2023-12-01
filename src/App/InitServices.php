<?php

declare(strict_types=1);

namespace App;

use Kernel\ServicesContainer;
use Services\AuthService;
use Services\ConfigService;
use Services\FilesService;
use Services\MailerService;
use Services\PDOConnection;

class InitServices
{
    public function __invoke(ServicesContainer $servicesContainer): void
    {
        $servicesContainer->add('ConfigService', function () {
            return new ConfigService();
        });

        $configService = $servicesContainer->get('ConfigService', ConfigService::class);

        $servicesContainer->add('PDOConnection', function () use ($configService) {
            return new PDOConnection($configService);
        });

        $servicesContainer->add('MailerService', function () use ($configService) {
            return new MailerService($configService);
        });

        $servicesContainer->add('AuthService', function () {
            return new AuthService();
        });

        $servicesContainer->add('FilesService', function () use ($configService) {
            return new FilesService($configService);
        });
    }
}
