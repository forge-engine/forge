<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeLogger\Contracts\ForgeLoggerInterface;

#[Service]
#[Provides(interface: ForgeLoggerInterface::class, version: '1.0.0')]
#[Requires]
final class ForgeLoggerService implements ForgeLoggerInterface
{
    public function __construct(/** private AnotherServiceInterface $anotherService */)
    {
    }
    public function doSomething(): string
    {
        return "Doing something from the  Example module Service";
    }
}
