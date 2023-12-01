<?php

declare(strict_types=1);

namespace Services;

use LogicException;

/**
 * @phpstan-type EnvArray array<string, string>
 **/
class ConfigService
{
    /** @var EnvArray $mainConfig **/
    public readonly array $mainConfig;
    /** @var EnvArray $databaseConfig **/
    public readonly array $databaseConfig;
    /** @var EnvArray $smtpConfig **/
    public readonly array $smtpConfig;

    public function __construct()
    {
        $this->mainConfig = self::readConfigFile(__DIR__ . '/../Config/config.env');
        $this->databaseConfig = self::readConfigFile(__DIR__ . '/../Config/Secret/database.env');
        $this->smtpConfig = self::readConfigFile(__DIR__ . '/../Config/Secret/smtp.env');
    }

    /**
     * @return EnvArray
     **/
    private static function readConfigFile(string $filePath): array
    {
        $env = parse_ini_file($filePath);

        if ($env === false) {
            throw new LogicException('Could not find the config file: ' . $filePath);
        }

        return $env;
    }
}
