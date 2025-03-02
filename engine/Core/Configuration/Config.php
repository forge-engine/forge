<?php

namespace Forge\Core\Configuration;

use Forge\Core\Contracts\Modules\ConfigInterface;
use Forge\Core\Traits\ConfigHashHelper;
use RuntimeException;

class Config implements ConfigInterface
{
    use ConfigHashHelper;

    private array $config = [];
    private string $envPrefix = 'FORGE_';
    private string $cacheFile;
    private bool $isProduction;
    private string $baseDir;
    private string $hashFile;

    public function __construct(string $baseDir, bool $isProduction = false)
    {
        $this->baseDir = $baseDir;
        $this->cacheFile = $baseDir . '/storage/framework/config_cache.php';
        $this->hashFile = $baseDir . '/storage/framework/config_hash.txt';
        $this->isProduction = $isProduction;

        $this->load();
    }

    /**
     * @param array<int,mixed> $defaults
     */
    public function mergeModuleDefaults(array $defaults): void
    {
        $this->config = array_replace_recursive($this->config, $defaults);
    }

    public function load(): void
    {
        if ($this->isProduction && file_exists($this->cacheFile)) {
            $this->config = require $this->cacheFile;
            if ($this->isCacheValid($this->baseDir)) {
                $this->config = require $this->cacheFile;
            }
        } else {
            $this->invalidateCache();
            $this->loadConfig();
            $this->validate();
            $this->cache();
        }
    }

    private function loadConfig(): void
    {
        $this->loadModuleDefaults();
        $this->loadAppConfigs();
        $this->loadModuleConfigs();
        $this->loadEnvironmentVars();
    }

    private function isCacheValid(string $baseDir): bool
    {
        if (!file_exists($this->cacheFile) || !file_exists($this->hashFile)) {
            return false;
        }

        $storedHash = file_get_contents($this->hashFile);
        $currentHash = $this->generateConfigHash($baseDir);
        return $storedHash === $currentHash;
    }

    private function invalidateCache(): void
    {
        if (!file_exists($this->cacheFile) || !file_exists($this->hashFile)) {
            return;
        }
        @unlink($this->cacheFile);
        @unlink($this->hashFile);
    }

    private function validate(): void
    {
        $validator = new ConfigValidator();
        $schemas = $this->loadSchemas();
        $validator->validate($this, $schemas);
    }

    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * @return array<<missing>,<missing>>
     */
    private function loadSchemas(): array
    {
        $schemas = [];
        foreach (glob('modules/*/config/schema.php') as $file) {
            $schemas = array_merge($schemas, require $file);
        }
        foreach (glob('apps/*/config/schema.php') as $file) {
            $schemas = array_merge($schemas, require $file);
        }
        return $schemas;
    }

    private function cache(): void
    {
        if (!$this->isProduction) {
            return;
        }

        file_put_contents($this->cacheFile, "<?php return " . var_export($this->config, true) . ";");
        file_put_contents($this->hashFile, $this->generateConfigHash(dirname($this->cacheFile)));
    }

    private function loadModuleDefaults(): void
    {
    }

    private function loadEnvironmentVars(): void
    {
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, 'FORGE_')) {
                $configKey = strtolower(substr($key, strlen('FORGE_')));
                $configKey = str_replace('__', '.', $configKey);
                $this->set($configKey, $this->parseEnvValue($value));
            }
        }
    }

    private function parseEnvValue(string $value): mixed
    {
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if (is_numeric($value)) return strpos($value, '.') ? (float)$value : (int)$value;
        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (strpos($key, '.') !== false && strpos($key, '/') === false) {
            $parts = explode('.', $key);
            $appName = $parts[0];
            $configKey = implode('.', array_slice($parts, 1));

            if (isset($this->config[$appName]) && isset($this->config[$appName]['config'])) {
                $appConfig = $this->config[$appName]['config'];
                return $this->getValueFromConfig($appConfig, $configKey, $default);
            }
        }

        if (strpos($key, '/') === false) {
            $parts = explode('.', $key);
            $topLevelKey = $parts[0];

            foreach ($this->config as $appName => $appConfig) {
                if (isset($appConfig['config']) && isset($appConfig['config'][$topLevelKey])) {
                    $current = $appConfig['config'][$topLevelKey];
                    if (count($parts) > 1) {
                        $remainingKeys = array_slice($parts, 1);
                        foreach ($remainingKeys as $k) {
                            if (!is_array($current) || !isset($current[$k])) {
                                return $default;
                            }
                            $current = $current[$k];
                        }
                    }
                    return $current;
                }
            }
        }

        if (strpos($key, '/') !== false) {
            $modulePathParts = explode('/', $key);
            $moduleName = $modulePathParts[0];
            $configKey = implode('.', array_slice($modulePathParts, 1));

            if (isset($this->config[$moduleName]) && isset($this->config[$moduleName]['config'])) {
                $moduleConfig = $this->config[$moduleName]['config'];
                return $this->getValueFromConfig($moduleConfig, $configKey, $default);
            }
        }

        if (isset($this->config['config'])) {
            return $this->getValueFromConfig($this->config['config'], $key, $default);
        }

        return $default;
    }

    private function loadConfigsFromDir(string $dir, string $type, string $name): array
    {
        $configs = [];
        if (is_dir($dir)) {
            foreach (glob("{$dir}/*.php") as $file) {
                $configName = basename($file, '.php');
                $config = require $file;
                if (!is_array($config)) {
                    throw new RuntimeException("{$type} config file {$file} must return an array");
                }
                $configs[$configName] = $config;
            }
        }
        return $configs;
    }

    /**
     * @param array<int,mixed> $config
     */
    private function getValueFromConfig(array $config, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $current = $config;

        foreach ($keys as $k) {
            if (!is_array($current) || !isset($current[$k])) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }

    private function loadAppConfigs(): void
    {
        $appDir = $this->baseDir . '/apps';
        $rootDir = $this->baseDir . '/config';

        foreach (glob("{$appDir}/*") as $appNameDir) {
            $appName = basename($appNameDir);
            $appConfigDir = $appNameDir . '/config';
            $moduleConfigDir = $this->baseDir . '/modules/' . $appName . '/config';

            $appConfigs = [];
            if (is_dir($appConfigDir)) {
                $appConfigs = $this->loadConfigsFromDir($appConfigDir, 'apps', $appName);
            }

            $moduleConfigs = [];
            if (is_dir($moduleConfigDir)) {
                $moduleConfigs = $this->loadConfigsFromDir($moduleConfigDir, 'modules', $appName);
            }

            $rootConfig = [];
            if (is_dir($rootDir)) {
                $rootConfig = $this->loadConfigsFromDir($rootDir, 'config', $appName);
            }

            $this->config[$appName] = [
                'config' => array_replace_recursive($moduleConfigs, $appConfigs, $rootConfig),
                'schema' => [],
            ];
        }
    }

    private function loadModuleConfigs(): void
    {
        $moduleDir = $this->baseDir . '/modules';

        foreach (glob("{$moduleDir}/*") as $moduleNameDir) {
            $moduleName = basename($moduleNameDir);
            $moduleConfigDir = $moduleNameDir . '/config';

            $moduleConfigs = [];
            if (is_dir($moduleConfigDir)) {
                $moduleConfigs = $this->loadConfigsFromDir($moduleConfigDir, 'modules', $moduleName);
            }

            $this->config[$moduleName] = [
                'config' => $moduleConfigs,
                'schema' => [],
            ];
        }
    }

    public function merge(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->config['config'];

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        if (is_array($value) && is_array($current)) {
            $current = array_merge_recursive($current, $value);
        } else {
            $current = $value;
        }
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->config;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }
}
