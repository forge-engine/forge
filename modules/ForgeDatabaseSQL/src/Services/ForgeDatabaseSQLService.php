<?php
declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\Services;

use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeDatabaseSQL\Contracts\ForgeDatabaseSQLInterface;

#[Service]
#[Provides(interface: ForgeDatabaseSQLInterface::class, version: '0.1.0')]
#[Requires(interface: DatabaseConnectionInterface::class, version: '>=0.1.0')]
final class ForgeDatabaseSQLService implements ForgeDatabaseSQLInterface
{
    public function __construct(/** private AnotherServiceInterface $anotherService */)
    {

    }

    public function doSomething(): string
    {
        return "Doing something from the  Example module Service";
    }
}