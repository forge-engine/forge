<?php

declare(strict_types=1);

namespace App\Modules\ForgeErrorHandler\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeErrorHandler\Contracts\ForgeErrorHandlerInterface;

#[Service]
#[Provides(interface: ForgeErrorHandlerInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeErrorHandlerService implements ForgeErrorHandlerInterface
{
    public function __construct(/** private AnotherServiceInterface $anotherService */)
    {
    }
    public function doSomething(): string
    {
        return "Doing something from the  Example module Service";
    }
}
