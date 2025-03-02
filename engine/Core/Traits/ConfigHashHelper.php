<?php

namespace Forge\Core\Traits;

trait ConfigHashHelper
{
    protected function generateConfigHash(string $baseDir): string
    {
        $hasher = hash_init('sha256');
        foreach (glob("{$baseDir}/modules/*/forge.json") as $manifestFile) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
            if (isset($manifest['config']['defaults'])) {
                hash_update($hasher, json_encode($manifest['config']['defaults']));
            }
        }

        foreach (glob("{$baseDir}/config/*.php") as $configFile) {
            hash_update($hasher, file_get_contents($configFile));
        }

        foreach (glob("{$baseDir}/modules/*/config/schema.php") as $schemaFile) {
            hash_update($hasher, file_get_contents($schemaFile));
        }
        foreach (glob("{$baseDir}/apps/*/config/schema.php") as $schemaFile) {
            hash_update($hasher, file_get_contents($schemaFile));
        }

        $forgeEnvVars = [];
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, 'FORGE_')) {
                $forgeEnvVars[$key] = $value;
            }
        }
        hash_update($hasher, serialize($forgeEnvVars));

        return hash_final($hasher);
    }
}
