<?php
declare(strict_types=1);

namespace App\Modules\ForgeBilling\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeBilling\Contracts\ForgeBillingInterface;

#[Service]
#[Provides(interface: ForgeBillingInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeBillingService implements ForgeBillingInterface
{
    public function __construct(/** private AnotherServiceInterface $anotherService */)
    {

    }
    public function doSomething(): string
    {
        return "Doing something from the  Example module Service";
    }
}
