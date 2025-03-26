<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeLogger\Contracts\ForgeLoggerInterface;
use App\Modules\ForgeLogger\Contracts\LogDriverInterface;
use App\Modules\ForgeLogger\Drivers\FileDriver;
use App\Modules\ForgeLogger\Drivers\NullDriver;
use App\Modules\ForgeLogger\Drivers\SysLogDriver;
use Forge\Core\Config\Config;

#[Service]
#[Provides(interface: ForgeLoggerInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeLoggerService implements ForgeLoggerInterface
{
    private array $drivers = [];
    private string $path;
    private string $driver;

    public function __construct(private Config $config)
    {
        $this->driver = $this->config->get('app.log.driver');
        $this->path = $this->config->get('app.log.path');
        $this->initService();
    }

    private function initService(): void
    {
        $this->registerDriver('file', new FileDriver($this->path));
        $this->registerDriver('syslog', new SysLogDriver());
        $this->registerDriver('null', new NullDriver());
    }

    public function registerDriver(string $name, LogDriverInterface $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    public function log(string $message, string $level = 'INFO'): void
    {
        $driver = $this->drivers[$this->driver] ?? $this->drivers['null'];
        $driver->write("[".date('Y-m-d H:i:s')."] [$level] $message");
    }
}
