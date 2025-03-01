<?php

namespace Forge\Modules\ForgePackageManager\Src\Services;

class ModuleValidator
{
    public function isInstalled(string $moduleName): bool
    {
        $path = BASE_PATH . "/modules/{$moduleName}";
        return is_dir($path) && file_exists($path . '/forge.json');
    }

    public function validateManifest(array $manifest): void
    {

    }
}