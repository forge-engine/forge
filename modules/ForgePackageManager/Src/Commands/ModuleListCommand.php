<?php

namespace Forge\Modules\ForgePackageManager\Src\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgePackageManager\Src\Services\PackageManager;

class ModuleListCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'package:list-modules';
    }

    public function getDescription(): string
    {
        return 'List modules available in the package repositories';
    }

    public function execute(array $args): int
    {
        $packageManager = new PackageManager();
        $registries = $packageManager->getRegistries();

        if (empty($registries)) {
            $this->warning("No package registries configured in forge.json.");
            $registries = [$packageManager->getDefaultRegistryDetails()]; // Fallback to default registry
        }

        $allModules = [];

        foreach ($registries as $registryDetails) {
            $registryName = $registryDetails['name'] ?? 'Default Registry';
            $this->info("Fetching module list from registry: " . $registryName);

            $modulesData = $packageManager->getModuleInfo(null); // Get ALL modules from the registry

            if (is_array($modulesData)) {
                foreach ($modulesData as $moduleName => $moduleInfo) {
                    $allModules[] = [
                        'Module' => $moduleName,
                        'Description' => $moduleInfo['description'] ?? 'No description available',
                        'Registry' => $registryName,
                        'Versions' => implode(', ', array_keys($moduleInfo['versions'] ?? [])), // List available versions
                    ];
                }
            } else {
                $this->error("Failed to load module list from registry: " . $registryName);
            }
        }

        if (empty($allModules)) {
            $this->warning("No modules found in the configured registries.");
            return 0;
        }

        $this->table(['Module', 'Description', 'Registry', 'Versions'], $allModules);

        return 0;
    }
}