<?php

namespace Forge\Modules\ForgePackageManager\Src\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgePackageManager\Src\Services\ModuleValidator;

class RemoveModuleCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'remove:module';
    }

    public function getDescription(): string
    {
        return 'Remove an installed module';
    }

    public function execute(array $args): int
    {
        if (empty($args)) {
            $this->error("Module name required");
            return 1;
        }

        $moduleName = $args[0];
        $validator = new ModuleValidator();

        try {
            $this->validateModuleName($moduleName);
            $modulePath = $this->getModulePath($moduleName);

            if (!$validator->isInstalled($moduleName)) {
                $this->error("Module {$moduleName} is not installed");
                return 1;
            }

            $this->removeModule($modulePath);
            $this->info("Module {$moduleName} removed successfully");
            return 0;

        } catch (\Throwable $e) {
            $this->error("Error removing module: " . $e->getMessage());
            return 1;
        }
    }

    private function validateModuleName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid module name format");
        }
    }

    private function getModulePath(string $moduleName): string
    {
        $path = realpath(BASE_PATH . "/modules/{$moduleName}");

        if (!$path || !is_dir($path)) {
            throw new \RuntimeException("Module directory not found");
        }

        // Prevent directory traversal
        $basePath = realpath(BASE_PATH . '/modules') . DIRECTORY_SEPARATOR;
        if (strpos($path, $basePath) !== 0) {
            throw new \RuntimeException("Invalid module path");
        }

        return $path;
    }

    private function removeModule(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        if (!rmdir($path)) {
            throw new \RuntimeException("Failed to remove module directory");
        }
    }
}