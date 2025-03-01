<?php

namespace Forge\Modules\ForgePackageManager\Src\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgePackageManager\Src\Services\PackageManager;

class InstallCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'install:project';
    }

    public function getDescription(): string
    {
        return 'Install modules from forge-lock.json';
    }

    public function execute(array $args): int
    {
        try {
            /** @var PackageManager $packageManager */
            $packageManager = App::getContainer()->get(PackageManager::class);
            $packageManager->installFromLock();

            $this->success("Modules installed successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}