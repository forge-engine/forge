<?php

namespace Forge\Modules\ForgePackageManager\Src\Contracts;

interface PackageManagerInterface
{
    public function installFromLock(): void;

    public function installModule(string $moduleName, ?string $version = null): void;

    public function removeModule(string $moduleName): void;
}